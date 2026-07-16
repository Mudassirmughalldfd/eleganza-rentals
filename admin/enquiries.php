<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = (string) ($_POST['id'] ?? '');
    if ($action === 'delete') {
        $e = enquiry_by_id($id);
        delete_enquiry($id);
        activity('Enquiry deleted', $e['name'] ?? $id);
        flash('success', 'Message deleted.');
    }
    redirect(url('admin/enquiries.php'));
}
$statusFilter = (string) ($_GET['status'] ?? 'all');
$typeFilter = (string) ($_GET['type'] ?? 'all');
$query = trim((string) ($_GET['q'] ?? ''));
$items = enquiries_all();
if ($statusFilter !== 'all') $items = array_values(array_filter($items, fn($e) => ($e['status'] ?? 'new') === $statusFilter));
if ($typeFilter !== 'all') $items = array_values(array_filter($items, fn($e) => ($e['source'] ?? 'contact_page') === $typeFilter));
if ($query !== '') {
    $items = array_values(array_filter($items, function ($e) use ($query) {
        $hay = strtolower(($e['name'] ?? '').' '.($e['email'] ?? '').' '.($e['phone'] ?? '').' '.($e['vehicle_name'] ?? '').' '.($e['subject'] ?? ''));
        return str_contains($hay, strtolower($query));
    }));
}
$adminPageTitle = 'Messages';
$adminCurrent = 'enquiries';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<section class="admin-panel">
    <div class="admin-panel-header">
        <div><h2>Contact Messages & Booking Requests</h2><p class="admin-muted">Contact Us messages and Book Now requests are saved separately by request type.</p></div>
        <a class="admin-button secondary small" href="<?= url('admin/export-enquiries.php') ?>">Export CSV</a>
    </div>
    <form method="get" class="field-grid three" style="margin-bottom:20px">
        <label class="field"><span>Search</span><input type="search" name="q" value="<?= h($query) ?>" placeholder="Name, subject, email or vehicle"></label>
        <label class="field"><span>Request Type</span><select name="type"><option value="all">All request types</option><option value="book_now" <?= $typeFilter==='book_now'?'selected':'' ?>>Booking requests</option><option value="contact_page" <?= $typeFilter==='contact_page'?'selected':'' ?>>Contact messages</option></select></label>
        <label class="field"><span>Status</span><select name="status"><option value="all">All statuses</option><?php foreach (['new'=>'New','contacted'=>'Contacted','confirmed'=>'Confirmed','closed'=>'Closed'] as $key=>$label): ?><option value="<?= h($key) ?>" <?= $statusFilter===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></label>
        <div style="align-self:end"><button class="admin-button" type="submit">Filter</button></div>
    </form>

    <?php if (!$items): ?>
        <div class="empty-state">No messages match this view.</div>
    <?php else: foreach ($items as $item):
        $isBooking = ($item['source'] ?? '') === 'book_now';
        $title = $isBooking ? ($item['vehicle_name'] ?? 'Vehicle booking') : (($item['subject'] ?? '') ?: 'General enquiry');
    ?>
        <article class="message-card <?= ($item['status'] ?? 'new') === 'new' ? 'unread' : '' ?>">
            <div class="message-card-top">
                <div>
                    <div class="message-meta">
                        <span><?= h(strtoupper($item['status'] ?? 'new')) ?></span>
                        <span><?= h($isBooking ? 'BOOKING REQUEST' : 'CONTACT MESSAGE') ?></span>
                        <span><?= h((new DateTimeImmutable($item['created_at']))->format('d M Y, H:i')) ?></span>
                    </div>
                    <h3><?= h($item['name']) ?> — <?= h($title) ?></h3>
                    <p><?= h(excerpt((string) ($item['message'] ?? ''), 180)) ?></p>
                </div>
                <div class="table-actions">
                    <a class="admin-button secondary small" href="<?= url('admin/enquiry.php?id='.rawurlencode($item['id'])) ?>">Open</a>
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= h($item['id']) ?>"><button class="admin-button danger small" type="submit" data-confirm="Delete this message?">Delete</button></form>
                </div>
            </div>
        </article>
    <?php endforeach; endif; ?>
</section>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
