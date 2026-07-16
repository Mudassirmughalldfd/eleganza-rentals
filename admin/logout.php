<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
if(is_admin())activity('Admin logout',admin_user()['email']??'');
admin_logout();redirect(url('admin/login.php'));
