<?php
require_admin();
$adminPageTitle = $adminPageTitle ?? 'Dashboard';
$adminCurrent = $adminCurrent ?? 'dashboard';
$admin = admin_user();
?>
<!doctype html><html lang="en-GB"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= h($adminPageTitle) ?> | Eleganza Admin</title><link rel="icon" href="<?= asset('assets/images/favicon.png') ?>"><link rel="stylesheet" href="<?= asset('assets/css/admin.css') ?>?v=20260716-responsive-controls-1"></head><body class="admin-body">
<div class="admin-shell">
<aside class="admin-sidebar" data-admin-sidebar>
    <a class="admin-brand" href="<?= url('admin/index.php') ?>"><img src="<?= asset('assets/images/logo.png') ?>" alt="Eleganza Rentals"><span>Management</span></a>
    <nav>
        <a class="<?= $adminCurrent==='dashboard'?'active':'' ?>" href="<?= url('admin/index.php') ?>">Dashboard</a>
        <a class="<?= $adminCurrent==='cars'?'active':'' ?>" href="<?= url('admin/cars.php') ?>">Vehicles</a>
        <a class="<?= $adminCurrent==='media'?'active':'' ?>" href="<?= url('admin/media.php') ?>">Images & Videos</a>
        <a class="<?= $adminCurrent==='availability'?'active':'' ?>" href="<?= url('admin/availability.php') ?>">Availability</a>
        <a class="<?= $adminCurrent==='enquiries'?'active':'' ?>" href="<?= url('admin/enquiries.php') ?>">Messages</a>
        <a class="<?= $adminCurrent==='settings'?'active':'' ?>" href="<?= url('admin/settings.php') ?>">Website & Email</a>
        <a class="<?= $adminCurrent==='database'?'active':'' ?>" href="<?= url('admin/database.php') ?>">Database</a>
        <a class="<?= $adminCurrent==='password'?'active':'' ?>" href="<?= url('admin/change-password.php') ?>">Change Password</a>
    </nav>
    <div class="admin-sidebar-footer"><span><?= h($admin['name'] ?? 'Administrator') ?></span><a href="<?= url('index.php') ?>" target="_blank">View Website</a><a href="<?= url('admin/logout.php') ?>">Log out</a></div>
</aside>
<main class="admin-main">
    <header class="admin-topbar"><button type="button" class="admin-menu-button" data-admin-menu>☰</button><div><p>Eleganza Rentals</p><h1><?= h($adminPageTitle) ?></h1></div><a class="admin-view-site" href="<?= url('index.php') ?>" target="_blank">Open Website ↗</a></header>
    <div class="admin-content">
        <?php foreach(flashes() as $flash): ?><div class="admin-alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endforeach; ?>
