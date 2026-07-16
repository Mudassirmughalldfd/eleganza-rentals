<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
$id = (string) ($_GET['id'] ?? $_POST['id'] ?? '');
$item = enquiry_by_id($id);
if (!$item) { flash('error', 'Message not found.'); redirect(url('admin/enquiries.php')); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item['status'] = (string) ($_POST['status'] ?? 'new');
    $item['admin_notes'] = trim((string) ($_POST['admin_notes'] ?? ''));
    save_enquiry($item);
    activity('Enquiry updated', $item['name'].' — '.$item['status']);
    flash('success', 'Message status and notes saved.');
    redirect(url('admin/enquiry.php?id='.rawurlencode($id)));
}
$isBooking = ($item['source'] ?? '') === 'book_now';
$adminPageTitle = ($isBooking ? 'Booking request from ' : 'Message from ').$item['name'];
$adminCurrent = 'enquiries';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-grid">
<section class="admin-panel">
    <div class="admin-panel-header">
        <div><h2><?= h($item['name']) ?></h2><p class="admin-muted"><?= h((new DateTimeImmutable($item['created_at']))->format('d F Y \a\t H:i')) ?></p></div>
        <span class="status-badge status-<?= h($item['status']==='new'?'available':($item['status']==='closed'?'maintenance':'reserved')) ?>"><?= h(ucfirst($item['status'])) ?></span>
    </div>
    <div class="simple-list">
        <div class="simple-list-item"><div><strong>Request Type</strong><br><span><?= h($isBooking ? 'Book Now — vehicle booking request' : 'Contact Us — general message') ?></span></div></div>
        <?php if (!empty($item['subject'])): ?><div class="simple-list-item"><div><strong>Subject</strong><br><span><?= h($item['subject']) ?></span></div></div><?php endif; ?>
        <div class="simple-list-item"><div><strong>Email</strong><br><span><a href="mailto:<?= h($item['email']) ?>"><?= h($item['email']) ?></a></span></div></div>
        <div class="simple-list-item"><div><strong>Phone</strong><br><span><a href="tel:<?= h(preg_replace('/\D+/', '', $item['phone'])) ?>"><?= h($item['phone']) ?></a></span></div></div>
        <?php if (!empty($item['contact_method'])): ?><div class="simple-list-item"><div><strong>Preferred Contact Method</strong><br><span><?= h($item['contact_method']) ?></span></div></div><?php endif; ?>
        <?php if ($isBooking): ?>
            <div class="simple-list-item"><div><strong>Vehicle</strong><br><span><?= h($item['vehicle_name']) ?></span></div></div>
            <div class="simple-list-item"><div><strong>Preferred Dates</strong><br><span><?= h($item['start_date'] ?: 'Not supplied') ?> to <?= h($item['end_date'] ?: 'Not supplied') ?></span></div></div>
        <?php endif; ?>
        <div class="simple-list-item"><div><strong>Email Delivery</strong><br><span><?= h($item['mail_status'] ?? 'Unknown') ?></span></div></div>
    </div>
    <h3 style="margin-top:25px"><?= $isBooking ? 'Additional Notes' : 'Customer Message' ?></h3>
    <p style="white-space:pre-wrap;color:#d7d2c8"><?= h($item['message']) ?></p>
    <div class="table-actions">
        <a class="admin-button secondary" href="mailto:<?= h($item['email']) ?>?subject=<?= rawurlencode($isBooking ? 'Your Eleganza Rentals booking request' : 'Your Eleganza Rentals enquiry') ?>">Reply by Email</a>
        <a class="admin-button secondary" target="_blank" rel="noopener" href="https://wa.me/<?= h(preg_replace('/\D+/', '', $item['phone'])) ?>">Open WhatsApp</a>
    </div>
</section>
<section class="form-section">
    <h2>Internal Management</h2>
    <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($id) ?>">
        <label class="field"><span>Message Status</span><select name="status"><?php foreach (['new'=>'New','contacted'=>'Contacted','confirmed'=>'Confirmed','closed'=>'Closed'] as $key=>$label): ?><option value="<?= h($key) ?>" <?= $item['status']===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
        <label class="field"><span>Private Admin Notes</span><textarea name="admin_notes" rows="10"><?= h($item['admin_notes'] ?? '') ?></textarea><p class="form-help">These notes are never shown publicly.</p></label>
        <button class="admin-button" type="submit">Save Changes</button>
    </form>
</section>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
