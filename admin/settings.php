<?php
require dirname(__DIR__) . '/includes/bootstrap.php';require_admin();$tab=(string)($_GET['tab']??'general');$settings=settings_all();
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$action=(string)($_POST['action']??'save_general');
    if($action==='save_general'){
        save_settings([
            'site_name'=>trim((string)($_POST['site_name']??'Eleganza Rentals')),
            'phone'=>trim((string)($_POST['phone']??'')),'phone_link'=>preg_replace('/\D+/','',(string)($_POST['phone']??'')),
            'email'=>trim((string)($_POST['email']??'')),'whatsapp'=>preg_replace('/\D+/','',(string)($_POST['whatsapp']??'')),
            'hero_kicker'=>trim((string)($_POST['hero_kicker']??'')),'hero_title_top'=>trim((string)($_POST['hero_title_top']??'')),
            'hero_title_bottom'=>trim((string)($_POST['hero_title_bottom']??'')),'hero_text'=>trim((string)($_POST['hero_text']??'')),
            'about_intro'=>trim((string)($_POST['about_intro']??'')),'footer_line'=>trim((string)($_POST['footer_line']??'')),
        ]);activity('Website settings updated');flash('success','Website content and contact details saved.');redirect(url('admin/settings.php?tab=general'));
    }
    if($action==='save_email'){
        try {
            $changes=[
                'smtp_enabled'=>isset($_POST['smtp_enabled'])?'1':'0','smtp_host'=>trim((string)($_POST['smtp_host']??'')),
                'smtp_port'=>trim((string)($_POST['smtp_port']??'587')),'smtp_encryption'=>(string)($_POST['smtp_encryption']??'tls'),
                'smtp_username'=>trim((string)($_POST['smtp_username']??'')),'smtp_from_email'=>trim((string)($_POST['smtp_from_email']??'')),
                'smtp_from_name'=>trim((string)($_POST['smtp_from_name']??'')),'notification_email'=>trim((string)($_POST['notification_email']??'')),
                'auto_reply_enabled'=>isset($_POST['auto_reply_enabled'])?'1':'0','notification_subject'=>trim((string)($_POST['notification_subject']??'')),
                'auto_reply_subject'=>trim((string)($_POST['auto_reply_subject']??'')),'auto_reply_body'=>(string)($_POST['auto_reply_body']??''),
            ];
            $password=(string)($_POST['smtp_password']??'');
            if($password!=='')$changes['smtp_password']=encrypt_secret($password);
            save_settings($changes);
            activity('Email settings updated');
            flash('success','SMTP and email templates saved.');
        } catch (Throwable $e) {
            error_log('Eleganza SMTP settings save failed: '.$e->getMessage());
            flash('error','Email settings could not be saved: '.$e->getMessage());
        }
        redirect(url('admin/settings.php?tab=email'));
    }
    if($action==='test_email'){
        try{
            $current=settings_all();$password=decrypt_secret((string)($current['smtp_password']??''));if($password==='')throw new RuntimeException('Enter and save the Hostinger mailbox password first.');
            $mailer=new SmtpMailer(['host'=>$current['smtp_host'],'port'=>(int)$current['smtp_port'],'encryption'=>$current['smtp_encryption'],'username'=>$current['smtp_username'],'password'=>$password,'from_email'=>$current['smtp_from_email'],'from_name'=>$current['smtp_from_name']]);
            $to=trim((string)($_POST['test_email']??$current['notification_email']));if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid test email address.');
            $mailer->send($to,'Eleganza Rentals SMTP test','<h2>SMTP is working</h2><p>This message was sent from the Eleganza Rentals admin dashboard using your saved SMTP settings.</p>',$current['smtp_from_email'],$current['smtp_from_name']);
            activity('SMTP test sent',$to);flash('success','Test email sent successfully to '.$to.'.');
        }catch(Throwable $e){flash('error','SMTP test failed: '.$e->getMessage());}
        redirect(url('admin/settings.php?tab=email'));
    }
}
$settings=settings_all();$adminPageTitle='Website & Email';$adminCurrent='settings';require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="tabs"><a class="<?=$tab==='general'?'active':''?>" href="<?=url('admin/settings.php?tab=general')?>">Website Content</a><a class="<?=$tab==='email'?'active':''?>" href="<?=url('admin/settings.php?tab=email')?>">Hostinger Email / SMTP</a></div>
<?php if($tab==='general'):?>
<form class="admin-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_general"><section class="form-section"><h2>Business Contact Details</h2><div class="field-grid"><label class="field"><span>Website Name</span><input type="text" name="site_name" value="<?=h($settings['site_name'])?>"></label><label class="field"><span>Phone Number</span><input type="text" name="phone" value="<?=h($settings['phone'])?>"></label><label class="field"><span>Public Email</span><input type="email" name="email" value="<?=h($settings['email'])?>"></label><label class="field"><span>WhatsApp Number</span><input type="text" name="whatsapp" value="<?=h($settings['whatsapp'])?>"><p class="form-help">Use international format, for example 447728393135.</p></label></div></section><section class="form-section"><h2>Homepage Hero</h2><div class="field-grid"><label class="field full"><span>Small Heading</span><input type="text" name="hero_kicker" value="<?=h($settings['hero_kicker'])?>"></label><label class="field"><span>Main Heading Line 1</span><input type="text" name="hero_title_top" value="<?=h($settings['hero_title_top'])?>"></label><label class="field"><span>Main Heading Line 2</span><input type="text" name="hero_title_bottom" value="<?=h($settings['hero_title_bottom'])?>"></label><label class="field full"><span>Hero Description</span><textarea name="hero_text"><?=h($settings['hero_text'])?></textarea></label></div></section><section class="form-section"><h2>About & Footer Text</h2><div class="field-grid"><label class="field full"><span>About Introduction</span><textarea name="about_intro"><?=h($settings['about_intro'])?></textarea></label><label class="field full"><span>Footer Closing Line</span><input type="text" name="footer_line" value="<?=h($settings['footer_line'])?>"></label></div></section><div class="form-actions"><button class="admin-button" type="submit">Save Website Settings</button></div></form>
<?php else:?>
<div class="smtp-status"><strong>Hostinger defaults included:</strong> smtp.hostinger.com, port 587, TLS/STARTTLS. Enter the password for <strong>hello@eleganzarentals.co.uk</strong>, save, then send a test email.</div>
<form class="admin-form" method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_email"><section class="form-section"><h2>SMTP Connection</h2><div class="field-grid three"><label class="check-field full"><input type="checkbox" name="smtp_enabled" value="1" <?=$settings['smtp_enabled']==='1'?'checked':''?>><span>Enable email notifications through SMTP</span></label><label class="field"><span>SMTP Host</span><input type="text" name="smtp_host" value="<?=h($settings['smtp_host'])?>"></label><label class="field"><span>SMTP Port</span><input type="number" name="smtp_port" value="<?=h($settings['smtp_port'])?>"></label><label class="field"><span>Encryption</span><select name="smtp_encryption"><option value="tls" <?=$settings['smtp_encryption']==='tls'?'selected':''?>>TLS / STARTTLS</option><option value="ssl" <?=$settings['smtp_encryption']==='ssl'?'selected':''?>>SSL</option></select></label><label class="field"><span>SMTP Username</span><input type="email" name="smtp_username" value="<?=h($settings['smtp_username'])?>"></label><label class="field"><span>SMTP Password</span><input type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="Leave blank to keep saved password"><p class="form-help">The password is encrypted before it is stored.</p></label><label class="field"><span>Notification Recipient</span><input type="email" name="notification_email" value="<?=h($settings['notification_email'])?>"></label><label class="field"><span>From Email</span><input type="email" name="smtp_from_email" value="<?=h($settings['smtp_from_email'])?>"></label><label class="field"><span>From Name</span><input type="text" name="smtp_from_name" value="<?=h($settings['smtp_from_name'])?>"></label></div></section><section class="form-section"><h2>Email Templates</h2><div class="field-grid"><label class="field full"><span>Admin Notification Subject</span><input type="text" name="notification_subject" value="<?=h($settings['notification_subject'])?>"><p class="form-help">Available placeholders: {{name}}, {{vehicle}}, {{email}}, {{phone}}, {{start_date}}, {{end_date}}.</p></label><label class="check-field full"><input type="checkbox" name="auto_reply_enabled" value="1" <?=$settings['auto_reply_enabled']==='1'?'checked':''?>><span>Send an automatic confirmation to the customer</span></label><label class="field full"><span>Customer Auto-Reply Subject</span><input type="text" name="auto_reply_subject" value="<?=h($settings['auto_reply_subject'])?>"></label><label class="field full"><span>Customer Auto-Reply HTML</span><textarea name="auto_reply_body" rows="10"><?=h($settings['auto_reply_body'])?></textarea></label></div></section><div class="form-actions"><button class="admin-button" type="submit">Save Email Settings</button></div></form>
<section class="form-section"><h2>Send a Test Email</h2><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="test_email"><div class="field-grid"><label class="field"><span>Test Recipient</span><input type="email" name="test_email" value="<?=h($settings['notification_email'])?>" required></label><div style="align-self:end"><button class="admin-button secondary" type="submit">Send SMTP Test</button></div></div></form></section>
<?php endif;?>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
