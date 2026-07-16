<?php
require __DIR__ . '/includes/bootstrap.php';

$slug = (string)($_GET['slug'] ?? '');
$car = car_by_slug($slug);

if (!$car) {
    http_response_code(404);
    $pageTitle = 'Vehicle Not Found | Eleganza Rentals';
    $currentPage = '';
    require __DIR__ . '/includes/header.php';
    echo '<main><section class="section not-found"><div class="layout-1350"><p class="eyebrow">404</p><h1>Vehicle not found.</h1><a class="button button-gold" href="'.h(url('index.php')).'">Return Home</a></div></section></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $car['make'].' '.$car['model'].' | Eleganza Rentals';
$pageDescription = $car['short_description'];
$currentPage = '';
$extraScripts = ['assets/js/car-gallery.js'];
require __DIR__ . '/includes/header.php';

$media = media_for_car($car['id']);
$status = car_status($car);
$site = settings_all();
$first = $media[0] ?? null;
?>
<main>
<section class="car-hero">
    <div class="layout-1350 car-hero-grid">
        <div class="car-hero-copy">
            <p class="eyebrow anim-up"><?= h($car['make']) ?> • <?= h($car['year']) ?></p>
            <h1 class="anim-line"><?= h($car['model']) ?></h1>
            <p class="anim-up delay-1"><?= h($car['short_description']) ?></p>
        </div>
        <div class="car-hero-meta anim-up delay-2">
            <span class="availability-pill status-<?= h($status['key']) ?>"><i></i><?= h($status['label']) ?></span>
            <div><small>From</small><strong><?= format_money($car['starting_price']) ?></strong><span>/ day</span></div>
        </div>
    </div>
</section>

<section class="section gallery-section">
    <div class="layout-1350">
        <div class="car-gallery" data-car-gallery>
            <div class="gallery-stage" data-gallery-stage>
                <?php if ($first): ?>
                    <?php if (($first['type'] ?? '') === 'youtube'): ?>
                        <iframe
                            src="<?= h(youtube_embed_url((string)$first['path'])) ?>"
                            title="<?= h($first['title'] ?? $first['alt'] ?? 'Vehicle video') ?>"
                            loading="eager"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                    <?php elseif (($first['type'] ?? '') === 'video'): ?>
                        <video src="<?= asset($first['path']) ?>" poster="<?= !empty($first['poster']) ? asset($first['poster']) : '' ?>" controls playsinline></video>
                    <?php else: ?>
                        <img src="<?= asset($first['path']) ?>" alt="<?= h($first['alt'] ?? '') ?>">
                    <?php endif; ?>
                <?php endif; ?>
                <button class="gallery-fullscreen" type="button" data-gallery-fullscreen aria-label="Open media full screen">↗</button>
                <div class="gallery-counter"><span data-gallery-current>01</span><i></i><span><?= str_pad((string)count($media), 2, '0', STR_PAD_LEFT) ?></span></div>
            </div>

            <div class="gallery-rail" data-gallery-rail>
                <?php foreach ($media as $i => $item):
                    $type = (string)($item['type'] ?? 'image');
                    $src = $type === 'youtube' ? youtube_embed_url((string)$item['path']) : asset((string)$item['path']);
                    $poster = !empty($item['poster']) ? asset((string)$item['poster']) : '';
                    if ($type === 'youtube' && $poster === '') $poster = youtube_thumbnail_url((string)$item['path']);
                    $thumb = $type === 'image' ? asset((string)$item['path']) : $poster;
                ?>
                    <button
                        class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                        type="button"
                        data-type="<?= h($type) ?>"
                        data-src="<?= h($src) ?>"
                        data-poster="<?= h($poster) ?>"
                        data-alt="<?= h($item['alt'] ?? '') ?>"
                        data-title="<?= h($item['title'] ?? '') ?>">
                        <?php if ($type === 'video' && $thumb === ''): ?>
                            <video src="<?= asset($item['path']) ?>" muted preload="metadata" playsinline></video>
                        <?php elseif ($thumb !== ''): ?>
                            <img src="<?= h($thumb) ?>" alt="<?= h($item['alt'] ?? '') ?>">
                        <?php else: ?>
                            <span class="thumb-placeholder">Video</span>
                        <?php endif; ?>
                        <span class="thumb-index"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <?php if (in_array($type, ['video', 'youtube'], true)): ?><span class="thumb-play">▶</span><?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<section class="section car-information">
    <div class="layout-1350 car-info-grid">
        <div class="car-description reveal">
            <p class="eyebrow">Vehicle Overview</p>
            <h2><?= h($car['make']) ?><br><span class="gold-gradient"><?= h($car['model']) ?></span></h2>
            <p><?= nl2br(h($car['description'])) ?></p>
            <div class="car-facts">
                <div><span>Make</span><strong><?= h($car['make']) ?></strong></div>
                <div><span>Model</span><strong><?= h($car['model']) ?></strong></div>
                <div><span>Year</span><strong><?= h($car['year']) ?></strong></div>
                <div><span>Status</span><strong><?= h($status['label']) ?></strong></div>
            </div>
        </div>
        <div class="availability-card reveal">
            <p class="eyebrow">Current Availability</p>
            <span class="big-status status-text-<?= h($status['key']) ?>"><?= h($status['label']) ?></span>
            <?php if ($status['public_note']): ?><p><?= h($status['public_note']) ?></p><?php endif; ?>
            <?php if (!$status['available'] && $status['show_return'] && $status['return_at']): ?>
                <div class="return-box">
                    <span>Expected back</span>
                    <strong><?= h(format_date_time($status['return_at'])) ?></strong>
                    <div class="countdown" data-countdown="<?= h($status['return_at']) ?>">Calculating return time…</div>
                </div>
            <?php elseif ($status['available']): ?>
                <p>This vehicle is currently available for enquiries.</p>
            <?php endif; ?>
            <a class="button button-gold" href="<?= url('book.php?vehicle='.rawurlencode($car['id'])) ?>">Book This Car</a>
        </div>
    </div>
</section>

<section class="section pricing-section">
    <div class="layout-1350">
        <div class="section-heading-row reveal">
            <div><p class="eyebrow">Transparent Pricing</p><h2>Rental <span class="gold-gradient">Prices</span></h2></div>
            <p>For hires longer than seven days, contact Eleganza Rentals for a tailored quote.</p>
        </div>
        <div class="pricing-grid reveal">
            <?php foreach ($car['prices'] as $days => $price): ?>
                <div class="price-tile"><span><?= h($days) ?> <?= ((int)$days === 1 ? 'Day' : 'Days') ?></span><strong><?= format_money($price) ?></strong></div>
            <?php endforeach; ?>
            <div class="price-tile contact-price"><span>Over 7 Days</span><strong><?= h($car['over_seven']) ?></strong></div>
        </div>
    </div>
</section>

<section class="section car-contact">
    <div class="layout-1350 car-contact-inner reveal">
        <div><p class="eyebrow">Ready to Request This Vehicle?</p><h2>Start Your <span class="gold-gradient">Booking Request</span></h2><p>Speak directly with Eleganza Rentals or send your preferred dates through the dedicated booking form.</p></div>
        <div class="car-contact-actions">
            <a class="button button-gold" href="<?= url('book.php?vehicle='.rawurlencode($car['id'])) ?>">Book This Car</a>
            <a class="button button-outline" href="tel:<?= h($site['phone_link']) ?>">Call <?= h($site['phone']) ?></a>
            <a class="text-link" href="https://wa.me/<?= h($site['whatsapp']) ?>?text=<?= rawurlencode('Hello Eleganza Rentals, I am interested in the '.$car['make'].' '.$car['model'].'.') ?>" target="_blank" rel="noopener">WhatsApp ↗</a>
        </div>
    </div>
</section>
</main>

<div class="gallery-modal" data-gallery-modal aria-hidden="true">
    <button type="button" data-gallery-close aria-label="Close">×</button>
    <div data-gallery-modal-content></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
