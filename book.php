<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Book a Car | Eleganza Rentals';
$pageDescription = 'Request your preferred Eleganza Rentals vehicle and hire dates.';
$currentPage = 'book';
$extraScripts = ['assets/js/forms.js'];
require __DIR__ . '/includes/header.php';
$site = settings_all();
$cars = cars_all(true);
$selected = (string) ($_GET['vehicle'] ?? '');
$bookingData = [];
foreach ($cars as $car) {
    $status = car_status($car);
    $blocks = [];
    foreach (availability_for_car((string) $car['id']) as $block) {
        $blocks[] = [
            'start_at' => (string) ($block['start_at'] ?? ''),
            'end_at' => (string) ($block['end_at'] ?? ''),
            'status' => (string) ($block['status'] ?? 'unavailable'),
            'label' => status_label((string) ($block['status'] ?? 'unavailable')),
            'show_return' => !empty($block['show_return']),
            'public_note' => (string) ($block['public_note'] ?? ''),
        ];
    }
    $bookingData[(string) $car['id']] = [
        'name' => $car['make'].' '.$car['model'],
        'year' => (string) $car['year'],
        'status' => [
            'key' => $status['key'],
            'label' => $status['label'],
            'available' => $status['available'],
            'return_at' => $status['return_at'],
            'show_return' => $status['show_return'],
            'public_note' => $status['public_note'],
        ],
        'blocks' => $blocks,
    ];
}
?>
<main>
<section class="inner-hero book-hero">
    <div class="inner-hero-overlay"></div>
    <div class="layout-1350 inner-hero-content">
        <p class="eyebrow anim-up">Vehicle Request</p>
        <h1><span class="anim-line">Book Your</span><span class="gold-gradient anim-line delay-1">Next Drive.</span></h1>
        <p class="anim-up delay-2">Choose a vehicle and your preferred dates. The live notice below will tell you when a car is unavailable before you submit.</p>
    </div>
</section>

<section class="section booking-section">
    <div class="layout-1350 booking-layout">
        <aside class="booking-guide reveal">
            <p class="eyebrow">Booking Request</p>
            <h2>Choose Your Car.<br><span class="gold-gradient">Check Your Dates.</span></h2>
            <p>This is a booking request, not an automatic confirmation. Eleganza Rentals will contact you after reviewing your selected vehicle and dates.</p>
            <div class="booking-steps">
                <div><span>01</span><strong>Select a vehicle</strong><p>Choose from the current Eleganza collection.</p></div>
                <div><span>02</span><strong>Choose your dates</strong><p>Unavailable dates will display a clear warning.</p></div>
                <div><span>03</span><strong>Send the request</strong><p>We will confirm the final arrangements directly.</p></div>
            </div>
            <div class="booking-contact-note">
                <small>Need help before booking?</small>
                <a href="<?= url('contact.php') ?>">Contact Us instead <span>↗</span></a>
            </div>
        </aside>

        <div class="form-card booking-form-card reveal">
            <div class="form-intro">
                <p class="eyebrow">Request Your Vehicle</p>
                <h2>Book Now</h2>
                <p>Selecting a currently unavailable vehicle or conflicting dates will show a notice immediately.</p>
            </div>

            <form class="enquiry-form" data-enquiry-form data-booking-form action="<?= url('api/enquiry.php') ?>" method="post">
                <?= csrf_field() ?>
                <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
                <input type="hidden" name="source" value="book_now">

                <label class="vehicle-select-label"><span>Select Vehicle *</span>
                    <select name="vehicle_id" required data-booking-vehicle>
                        <option value="">Choose a vehicle</option>
                        <?php foreach ($cars as $car): $status = car_status($car); ?>
                            <option value="<?= h($car['id']) ?>" <?= $selected === $car['id'] ? 'selected' : '' ?>>
                                <?= h($car['make'].' '.$car['model'].' — '.$status['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="availability-notice neutral" data-availability-notice role="status" aria-live="polite">
                    <span class="availability-icon">i</span>
                    <div><strong>Select a vehicle to check availability.</strong><p>Then choose your preferred start and end dates.</p></div>
                </div>

                <div class="form-grid booking-date-grid">
                    <label><span>Preferred Start Date *</span><input type="date" name="start_date" min="<?= date('Y-m-d') ?>" required data-booking-start></label>
                    <label><span>Preferred End Date *</span><input type="date" name="end_date" min="<?= date('Y-m-d') ?>" required data-booking-end></label>
                </div>

                <div class="form-grid">
                    <label><span>Full Name *</span><input type="text" name="name" required autocomplete="name"></label>
                    <label><span>Email Address *</span><input type="email" name="email" required autocomplete="email"></label>
                    <label><span>Phone Number *</span><input type="tel" name="phone" required autocomplete="tel"></label>
                    <label><span>Preferred Contact Method</span>
                        <select name="contact_method">
                            <option value="Phone">Phone</option>
                            <option value="Email">Email</option>
                            <option value="WhatsApp">WhatsApp</option>
                        </select>
                    </label>
                </div>

                <label><span>Additional Notes</span><textarea name="message" rows="5" placeholder="Add any useful details about your journey or preferred collection time..."></textarea></label>
                <label class="consent"><input type="checkbox" name="consent" value="1" required><span>I understand this is a booking request and is only confirmed after Eleganza Rentals contacts me.</span></label>
                <button class="button button-gold form-submit" type="submit" data-booking-submit><span>Send Booking Request</span><i>→</i></button>
                <div class="form-response" data-form-response role="status"></div>
            </form>
        </div>
    </div>
</section>
</main>
<script type="application/json" id="booking-availability-data"><?= json_encode($bookingData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
