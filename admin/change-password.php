<?php
require dirname(__DIR__) . '/includes/bootstrap.php';require_admin();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();$password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
    if(strlen($password)<12)$error='Use at least 12 characters.';elseif($password!==$confirm)$error='The passwords do not match.';else{update_admin_password((string)admin_user()['id'],$password);activity('Admin password changed');flash('success','Password changed successfully.');redirect(url('admin/change-password.php'));}
}
$adminPageTitle='Change Password';$adminCurrent='password';require dirname(__DIR__) . '/includes/admin-header.php';
?>
<?php if($error):?><div class="admin-alert error"><?=h($error)?></div><?php endif;?><section class="form-section" style="max-width:650px"><h2>Administrator Password</h2><p class="admin-muted">Change the default password before uploading the website publicly.</p><form method="post"><?=csrf_field()?><label class="field"><span>New Password</span><input type="password" name="password" minlength="12" required autocomplete="new-password"></label><label class="field"><span>Confirm New Password</span><input type="password" name="confirm" minlength="12" required autocomplete="new-password"></label><button class="admin-button" type="submit">Change Password</button></form></section>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
