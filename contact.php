<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Contact Eleganza Rentals | General Enquiries';
$pageDescription = 'Contact Eleganza Rentals with general questions, support requests or business enquiries.';
$currentPage = 'contact';
$extraScripts = ['assets/js/forms.js'];
require __DIR__ . '/includes/header.php';
$site = settings_all();
?>
<main>
<section class="inner-hero contact-hero">
    <div class="inner-hero-overlay"></div>
    <div class="layout-1350 inner-hero-content">
        <p class="eyebrow anim-up">Contact Eleganza Rentals</p>
        <h1><span class="anim-line">How Can We</span><span class="gold-gradient anim-line delay-1">Help You?</span></h1>
        <p class="anim-up delay-2">Use this page for general questions, support or business enquiries. To request a vehicle for specific dates, use Book Now.</p>
    </div>
</section>

<section class="section contact-section">
    <div class="layout-1350 contact-layout">
        <div class="contact-panel reveal">
            <p class="eyebrow">General Enquiries</p>
            <h2>Direct Support.<br><span class="gold-gradient">Personal Communication.</span></h2>
            <p>Ask a general question, request assistance or speak with the Eleganza Rentals team. Vehicle and date requests are handled separately on the Book Now page.</p>
            <a class="booking-promo" href="<?= url('book.php') ?>">
                <span><small>Ready to choose dates?</small><strong>Go to Book Now</strong></span><i>→</i>
            </a>
            <div class="contact-methods">
                <a href="tel:<?= h($site['phone_link']) ?>"><span>Call</span><strong><?= h($site['phone']) ?></strong><i>↗</i></a>
                <a href="mailto:<?= h($site['email']) ?>"><span>Email</span><strong><?= h($site['email']) ?></strong><i>↗</i></a>
                <a href="https://wa.me/<?= h($site['whatsapp']) ?>" target="_blank" rel="noopener"><span>WhatsApp</span><strong>Start a conversation</strong><i>↗</i></a>
            </div>
        </div>

        <div class="form-card reveal">
            <div class="form-intro">
                <p class="eyebrow">Send a Message</p>
                <h2>Contact Us</h2>
                <p>We will respond using the email address or phone number you provide.</p>
            </div>
            <form class="enquiry-form" data-enquiry-form action="<?= url('api/enquiry.php') ?>" method="post">
                <?= csrf_field() ?>
                <input class="honeypot" type="text" name="website" tabindex="-1" autocomplete="off">
                <input type="hidden" name="source" value="contact_page">
                <div class="form-grid">
                    <label><span>Full Name *</span><input type="text" name="name" required autocomplete="name"></label>
                    <label><span>Email Address *</span><input type="email" name="email" required autocomplete="email"></label>
                    <label><span>Phone Number *</span><input type="tel" name="phone" required autocomplete="tel"></label>
                    <label><span>Enquiry Type *</span>
                        <select name="subject" required>
                            <option value="">Select a reason</option>
                            <option value="General question">General question</option>
                            <option value="Existing rental support">Existing rental support</option>
                            <option value="Business or partnership enquiry">Business or partnership enquiry</option>
                            <option value="Other enquiry">Other enquiry</option>
                        </select>
                    </label>
                </div>
                <label><span>Your Message *</span><textarea name="message" rows="7" required placeholder="How can we help?"></textarea></label>
                <label class="consent"><input type="checkbox" name="consent" value="1" required><span>I agree that Eleganza Rentals may use these details to respond to my enquiry.</span></label>
                <button class="button button-gold form-submit" type="submit"><span>Send Message</span><i>→</i></button>
                <div class="form-response" data-form-response role="status"></div>
            </form>
        </div>
    </div>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
