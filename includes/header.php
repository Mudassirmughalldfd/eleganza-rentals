<?php
$pageTitle = $pageTitle ?? 'Eleganza Rentals | Premium Luxury Car Hire';
$pageDescription = $pageDescription ?? 'Premium luxury and performance car hire from Eleganza Rentals.';
$currentPage = $currentPage ?? '';
$bodyClass = $bodyClass ?? '';
$site = settings_all();
?>
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= h($pageDescription) ?>">
    <meta name="theme-color" content="#050505">
    <title><?= h($pageTitle) ?></title>
    <link rel="icon" href="<?= asset('assets/images/favicon.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>?v=20260716-responsive-controls-1">
</head>
<body class="<?= h($bodyClass) ?>">
<div class="page-transition" aria-hidden="true"></div>
<div class="scroll-progress" aria-hidden="true"><span></span></div>
<header class="site-header" data-header>
    <div class="layout-1350 header-inner">
        <a class="brand" href="<?= url('index.php') ?>" aria-label="Eleganza Rentals home">
            <img class="brand-logo" src="<?= asset('assets/images/logo.png') ?>" alt="Eleganza Rentals" fetchpriority="high">
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <a class="<?= $currentPage==='home'?'active':'' ?>" href="<?= url('index.php') ?>">Home</a>
            <a class="<?= $currentPage==='about'?'active':'' ?>" href="<?= url('about.php') ?>">About Us</a>
            <a class="<?= $currentPage==='contact'?'active':'' ?>" href="<?= url('contact.php') ?>">Contact Us</a>
        </nav>
        <div class="header-actions">
            <a class="header-phone" href="tel:<?= h($site['phone_link'] ?? '07728393135') ?>"><?= h($site['phone'] ?? '07728 393135') ?></a>
            <a class="button button-outline button-small <?= $currentPage==='book'?'is-active':'' ?>" href="<?= url('book.php') ?>">Book Now</a>
        </div>
        <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="mobile-menu" aria-label="Open menu"><span></span><span></span></button>
    </div>
</header>
<div class="mobile-menu" id="mobile-menu" data-mobile-menu>
    <div class="mobile-menu-inner">
        <a href="<?= url('index.php') ?>">Home</a>
        <a href="<?= url('about.php') ?>">About Us</a>
        <a href="<?= url('contact.php') ?>">Contact Us</a>
        <a class="button button-gold" href="<?= url('book.php') ?>">Book Now</a>
        <div class="mobile-contact"><a href="tel:<?= h($site['phone_link'] ?? '') ?>"><?= h($site['phone'] ?? '') ?></a><a href="mailto:<?= h($site['email'] ?? '') ?>"><?= h($site['email'] ?? '') ?></a></div>
    </div>
</div>
