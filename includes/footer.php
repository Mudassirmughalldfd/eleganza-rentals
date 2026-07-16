<?php $site = settings_all(); ?>
<footer class="site-footer">
    <div class="layout-1350 footer-main">
        <div class="footer-brand">
            <img src="<?= asset('assets/images/logo.png') ?>" alt="Eleganza Rentals">
            <p>Italian Style • Premium Experience</p>
            <p class="footer-copy">Luxury, performance and prestige — delivered with Italian style.</p>
        </div>
        <div class="footer-column">
            <h3>Navigate</h3>
            <a href="<?= url('index.php') ?>">Home</a>
            <a href="<?= url('about.php') ?>">About Us</a>
            <a href="<?= url('contact.php') ?>">Contact Us</a>
            <a href="<?= url('book.php') ?>">Book Now</a>
        </div>
        <div class="footer-column">
            <h3>Vehicles</h3>
            <?php foreach (cars_all(true) as $footerCar): ?>
                <a href="<?= url('car.php?slug='.rawurlencode($footerCar['slug'])) ?>"><?= h($footerCar['make'].' '.$footerCar['model']) ?></a>
            <?php endforeach; ?>
        </div>
        <div class="footer-column">
            <h3>Contact</h3>
            <a href="tel:<?= h($site['phone_link'] ?? '') ?>"><?= h($site['phone'] ?? '') ?></a>
            <a href="mailto:<?= h($site['email'] ?? '') ?>"><?= h($site['email'] ?? '') ?></a>
            <a href="https://wa.me/<?= h($site['whatsapp'] ?? '') ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </div>
    <div class="layout-1350 footer-bottom">
        <span>© <?= date('Y') ?> Eleganza Rentals. All rights reserved.</span>
        <span><?= h($site['footer_line'] ?? '') ?></span>
    </div>
</footer>
<script src="<?= asset('assets/js/main.js') ?>?v=20260716-responsive-controls-1"></script>
<?php if (!empty($extraScripts)): foreach ($extraScripts as $script): ?><script src="<?= asset($script) ?>"></script><?php endforeach; endif; ?>
</body>
</html>
