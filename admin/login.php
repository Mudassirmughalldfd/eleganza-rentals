<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if(is_admin())redirect(url('admin/index.php'));
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $email=trim((string)($_POST['email']??''));$password=(string)($_POST['password']??'');
    if(admin_login($email,$password)){activity('Admin login',$email);redirect(url('admin/index.php'));}
    $error='The email address or password is incorrect.';
}
?>
<!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Admin Login | Eleganza Rentals</title><link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>"></head><body class="login-page"><main class="login-card"><img src="<?= asset('assets/images/logo.png') ?>" alt="Eleganza Rentals"><h1>Management Login</h1><p>Manage vehicles, availability, media, messages and email settings.</p><?php if($error):?><div class="admin-alert error"><?=h($error)?></div><?php endif;?><form method="post"><?=csrf_field()?><label class="field"><span>Email Address</span><input type="email" name="email" required autocomplete="username"></label><label class="field"><span>Password</span><input type="password" name="password" required autocomplete="current-password"></label><button class="admin-button" type="submit">Log In</button></form><p class="login-note">Default local login is listed in README-FIRST.txt. Change the password immediately before publishing.</p></main></body></html>
