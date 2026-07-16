<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Eleganza Rentals | Premium Luxury Car Hire';
$pageDescription = 'Luxury and performance vehicle hire with refined service and Italian-inspired style.';
$currentPage = 'home';
require __DIR__ . '/includes/header.php';
$site = settings_all();
$cars = cars_all(true);
?>
<main>
<section class="home-hero" data-parallax>
    <div class="hero-overlay"></div>
    <div class="layout-1350 hero-layout">
        <div class="hero-copy" data-hero-copy>
            <p class="eyebrow anim-up"><?= h($site['hero_kicker']) ?></p>
            <h1 class="hero-title"><span class="anim-line"><?= h($site['hero_title_top']) ?></span><span class="gold-gradient anim-line delay-1"><?= h($site['hero_title_bottom']) ?></span></h1>
            <p class="hero-description anim-up delay-2"><?= h($site['hero_text']) ?></p>
            <div class="hero-actions anim-up delay-3"><a class="button button-gold" href="#cars">Explore the Cars <span>→</span></a><a class="button button-outline" href="<?= url('book.php') ?>">Book Now</a></div>
        </div>
        <div class="hero-side-note anim-up delay-3"><span>01</span><p>Premium vehicles.<br>Personal service.<br>Memorable journeys.</p></div>
    </div>
    <a class="scroll-cue" href="#experience"><span></span>Scroll to discover</a>
</section>

<section id="experience" class="section trust-section">
    <div class="layout-1350 trust-grid">
        <article class="trust-card reveal"><span class="trust-number">01</span><div><h2>Premium Vehicles</h2><p>A focused collection chosen for luxury, performance and road presence.</p></div></article>
        <article class="trust-card reveal"><span class="trust-number">02</span><div><h2>Personal Service</h2><p>Direct, attentive assistance from your first enquiry to vehicle return.</p></div></article>
        <article class="trust-card reveal"><span class="trust-number">03</span><div><h2>Extraordinary Journeys</h2><p>Distinctive cars for business, leisure, celebrations and weekends away.</p></div></article>
    </div>
</section>

<section id="cars" class="section cars-section">
    <div class="layout-1350">
        <div class="section-heading-row reveal">
            <div><p class="eyebrow">Eleganza Collection</p><h2>Choose Your <span class="gold-gradient">Next Drive</span></h2></div>
            <p>Two premium cars, each with a distinct character. View every image, watch the car video and check its current availability.</p>
        </div>
        <div class="car-showcase-grid">
            <?php foreach ($cars as $index => $car): $cover = cover_media($car); $status = car_status($car); ?>
            <article class="car-showcase reveal tilt-card" data-tilt>
                <a class="car-image-link" href="<?= url('car.php?slug='.rawurlencode($car['slug'])) ?>">
                    <?php if ($cover): ?><img src="<?= asset($cover['path']) ?>" alt="<?= h($cover['alt'] ?: $car['make'].' '.$car['model']) ?>"><?php endif; ?>
                    <span class="car-index">0<?= $index+1 ?></span>
                    <span class="availability-pill status-<?= h($status['key']) ?>"><i></i><?= h($status['label']) ?></span>
                    <span class="image-shine"></span>
                </a>
                <div class="car-showcase-body">
                    <div><p class="car-make"><?= h($car['make']) ?></p><h3><?= h($car['model']) ?></h3><p class="car-year"><?= h($car['year']) ?></p></div>
                    <div class="car-price"><span>From</span><strong><?= format_money($car['starting_price']) ?></strong><small>/ day</small></div>
                    <?php if (!$status['available'] && $status['show_return'] && $status['return_at']): ?><p class="return-note">Available again <?= h(format_date_time($status['return_at'])) ?></p><?php endif; ?>
                    <div class="car-actions"><a class="button button-gold" href="<?= url('car.php?slug='.rawurlencode($car['slug'])) ?>">View Car</a><a class="text-link" href="<?= url('book.php?vehicle='.rawurlencode($car['id'])) ?>">Book This Car <span>↗</span></a></div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section story-section">
    <div class="layout-1350 story-layout">
        <div class="story-visual reveal"><img src="<?= asset('assets/images/home-story.jpg') ?>" alt="Luxury car experience"><div class="story-frame"></div><div class="story-caption">Italian inspiration.<br>British service.</div></div>
        <div class="story-copy reveal"><p class="eyebrow">The Eleganza Experience</p><h2>Luxury That Goes <span class="gold-gradient">Beyond the Drive</span></h2><p><?= h($site['about_intro']) ?></p><div class="story-values"><div><strong>01</strong><span>Beautifully presented vehicles</span></div><div><strong>02</strong><span>Clear, personal communication</span></div><div><strong>03</strong><span>A refined rental experience</span></div></div><a class="button button-outline" href="<?= url('about.php') ?>">Discover Our Story</a></div>
    </div>
</section>

<section class="cinema-section">
    <div class="cinema-bg" data-parallax-soft></div><div class="cinema-overlay"></div>
    <div class="layout-1350 cinema-content reveal"><p class="eyebrow">Motion & Presence</p><h2>Performance You Can See.<br><span class="gold-gradient">Presence You Can Feel.</span></h2><a class="round-link" href="<?= url('car.php?slug=mercedes-benz-amg-a35-premium-4matic') ?>"><span>▶</span><small>Watch vehicle video</small></a></div>
</section>

<section class="section process-section">
    <div class="layout-1350"><div class="section-heading-row reveal"><div><p class="eyebrow">Simple & Personal</p><h2>How It <span class="gold-gradient">Works</span></h2></div><p>No complicated booking engine. Speak directly with Eleganza Rentals and arrange the right car for your dates.</p></div>
    <div class="process-line reveal"><article><span>01</span><h3>Choose</h3><p>Explore each vehicle’s gallery, video and rental prices.</p></article><article><span>02</span><h3>Enquire</h3><p>Send your preferred vehicle, dates and contact details.</p></article><article><span>03</span><h3>Confirm</h3><p>We confirm availability and discuss the rental arrangements.</p></article><article><span>04</span><h3>Drive</h3><p>Collect your vehicle and enjoy an extraordinary journey.</p></article></div></div>
</section>

<section class="section final-cta"><div class="layout-1350 final-cta-inner reveal"><div><p class="eyebrow">Your Next Journey</p><h2>Ready to Drive <span class="gold-gradient">Extraordinary?</span></h2></div><div><p>Tell us which vehicle you prefer and the dates you have in mind.</p><div class="cta-actions"><a class="button button-gold" href="tel:<?= h($site['phone_link']) ?>">Call <?= h($site['phone']) ?></a><a class="button button-outline" href="<?= url('contact.php') ?>">Contact Us</a><a class="button button-gold" href="<?= url('book.php') ?>">Book Now</a></div></div></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
