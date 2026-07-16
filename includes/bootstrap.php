<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['timezone'] ?? 'Europe/London');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name'] ?? 'eleganza_session');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

// A fresh GitHub/Hostinger deployment opens the guided installer automatically.
if (!db_configured() || !db_table_exists('site_settings')) {
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== 'install.php') {
        redirect(url('install.php'));
    }
}

ensure_storage();
