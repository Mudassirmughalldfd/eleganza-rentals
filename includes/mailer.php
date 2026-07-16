<?php
declare(strict_types=1);

final class SmtpMailer {
    private array $config;
    private $socket = null;
    public function __construct(array $config){$this->config=$config;}

    public function send(string $to,string $subject,string $html,string $replyEmail='',string $replyName=''): void {
        $host=(string)$this->config['host'];$port=(int)$this->config['port'];$enc=(string)$this->config['encryption'];
        $remote=($enc==='ssl'?'ssl://':'tcp://').$host.':'.$port;
        $this->socket=@stream_socket_client($remote,$errno,$errstr,20,STREAM_CLIENT_CONNECT);
        if(!$this->socket)throw new RuntimeException("SMTP connection failed: {$errstr} ({$errno})");
        stream_set_timeout($this->socket,20);$this->expect([220]);
        $domain=$_SERVER['SERVER_NAME']??'localhost';$this->command('EHLO '.$domain,[250]);
        if($enc==='tls'){
            $this->command('STARTTLS',[220]);
            if(!stream_socket_enable_crypto($this->socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('Could not start TLS encryption.');
            $this->command('EHLO '.$domain,[250]);
        }
        $this->command('AUTH LOGIN',[334]);$this->command(base64_encode((string)$this->config['username']),[334]);$this->command(base64_encode((string)$this->config['password']),[235]);
        $from=(string)$this->config['from_email'];$this->command('MAIL FROM:<'.$from.'>',[250]);$this->command('RCPT TO:<'.$to.'>',[250,251]);$this->command('DATA',[354]);
        $headers=[];$headers[]='Date: '.date(DATE_RFC2822);$headers[]='Message-ID: <'.bin2hex(random_bytes(12)).'@'.$domain.'>';
        $headers[]='From: '.$this->encodeHeader((string)$this->config['from_name']).' <'.$from.'>';$headers[]='To: <'.$to.'>';
        if($replyEmail)$headers[]='Reply-To: '.$this->encodeHeader($replyName).' <'.$replyEmail.'>';
        $headers[]='Subject: '.$this->encodeHeader($subject);$headers[]='MIME-Version: 1.0';$headers[]='Content-Type: text/html; charset=UTF-8';$headers[]='Content-Transfer-Encoding: 8bit';
        $payload=implode("\r\n",$headers)."\r\n\r\n".$html;$payload=preg_replace('/(?m)^\./','..',$payload)??$payload;
        fwrite($this->socket,$payload."\r\n.\r\n");$this->expect([250]);$this->command('QUIT',[221]);fclose($this->socket);$this->socket=null;
    }
    private function command(string $command,array $codes): string { fwrite($this->socket,$command."\r\n");return $this->expect($codes); }
    private function expect(array $codes): string {
        $response='';
        while(($line=fgets($this->socket,515))!==false){$response.=$line;if(strlen($line)>=4&&$line[3]===' ')break;}
        $code=(int)substr($response,0,3);if(!in_array($code,$codes,true))throw new RuntimeException('SMTP error: '.trim($response));return $response;
    }
    private function encodeHeader(string $text): string { return '=?UTF-8?B?'.base64_encode($text).'?='; }
}
