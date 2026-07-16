<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$cars = cars_all(false);
$editing = null;
$editId = (string) ($_GET['edit'] ?? '');

if ($editId !== '') {
    foreach (availability_all() as $availability) {
        if (($availability['id'] ?? '') === $editId) {
            $editing = $availability;
            break;
        }
    }
}

$form = $editing ?: [
    'id' => '',
    'car_id' => (string) ($_GET['car_id'] ?? ($cars[0]['id'] ?? '')),
    'status' => 'on_hire',
    'start_at' => date('Y-m-d\TH:i'),
    'end_at' => date('Y-m-d\TH:i', strtotime('+1 day')),
    'show_return' => true,
    'public_note' => 'This vehicle is currently unavailable.',
    'private_note' => '',
];

$splitDateTime = static function (string $value): array {
    if ($value === '') {
        return ['', ''];
    }

    try {
        $dateTime = new DateTimeImmutable($value);
        return [$dateTime->format('Y-m-d'), $dateTime->format('H:i')];
    } catch (Throwable) {
        $parts = preg_split('/[T\s]+/', $value, 2) ?: [];
        return [$parts[0] ?? '', isset($parts[1]) ? substr($parts[1], 0, 5) : ''];
    }
};

$combineDateTime = static function (string $date, string $time): string {
    $date = trim($date);
    $time = trim($time);

    if ($date === '' || $time === '') {
        return '';
    }

    $candidate = $date . 'T' . $time;
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $candidate);
    $errors = DateTimeImmutable::getLastErrors();

    if (!$parsed || ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0))) {
        return '';
    }

    return $parsed->format('Y-m-d\TH:i');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        delete_availability((string) ($_POST['id'] ?? ''));
        activity('Availability deleted', (string) ($_POST['id'] ?? ''));
        flash('success', 'Availability block deleted.');
        redirect(url('admin/availability.php'));
    }

    $form['id'] = (string) ($_POST['id'] ?? '');
    $form['car_id'] = (string) ($_POST['car_id'] ?? '');
    $form['status'] = (string) ($_POST['status'] ?? 'on_hire');
    $form['start_at'] = $combineDateTime(
        (string) ($_POST['start_date'] ?? ''),
        (string) ($_POST['start_time'] ?? '')
    );
    $form['end_at'] = $combineDateTime(
        (string) ($_POST['end_date'] ?? ''),
        (string) ($_POST['end_time'] ?? '')
    );
    $form['show_return'] = isset($_POST['show_return']);
    $form['public_note'] = trim((string) ($_POST['public_note'] ?? ''));
    $form['private_note'] = trim((string) ($_POST['private_note'] ?? ''));

    $errors = [];

    if ($form['car_id'] === '' || $form['start_at'] === '' || $form['end_at'] === '') {
        $errors[] = 'Vehicle, start date, start time, return date and return time are required.';
    }

    if ($form['start_at'] !== '' && $form['end_at'] !== '' && strtotime($form['end_at']) <= strtotime($form['start_at'])) {
        $errors[] = 'Expected return date and time must be after the unavailable date and time.';
    }

    if (
        !$errors
        && availability_overlap($form['car_id'], $form['start_at'], $form['end_at'], $form['id'])
    ) {
        $errors[] = 'This period overlaps an existing availability block for the vehicle.';
    }

    if (!$errors) {
        $saved = save_availability($form);
        $car = car_by_id($form['car_id']);
        activity('Availability saved', ($car['model'] ?? 'Vehicle') . ' — ' . status_label($form['status']));
        flash('success', 'Availability date and time saved successfully.');
        redirect(url('admin/availability.php?edit=' . rawurlencode($saved['id'])));
    }

    foreach ($errors as $error) {
        flash('error', $error);
    }
}

[$startDate, $startTime] = $splitDateTime((string) $form['start_at']);
[$endDate, $endTime] = $splitDateTime((string) $form['end_at']);

$adminPageTitle = 'Availability';
$adminCurrent = 'availability';
$blocks = availability_all();
usort($blocks, fn ($a, $b) => strcmp((string) $b['start_at'], (string) $a['start_at']));

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-grid">
    <section class="form-section">
        <h2><?= $editing ? 'Edit Availability Block' : 'Add Availability Block' ?></h2>

        <form class="admin-form" method="post" data-datetime-range>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= h($form['id']) ?>">

            <label class="field">
                <span>Vehicle *</span>
                <select name="car_id" required>
                    <?php foreach ($cars as $car): ?>
                        <option value="<?= h($car['id']) ?>" <?= $form['car_id'] === $car['id'] ? 'selected' : '' ?>>
                            <?= h($car['make'] . ' ' . $car['model']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="field">
                <span>Status *</span>
                <select name="status">
                    <?php foreach (status_options() as $key => $label): ?>
                        <?php if ($key === 'available') continue; ?>
                        <option value="<?= h($key) ?>" <?= $form['status'] === $key ? 'selected' : '' ?>>
                            <?= h($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="availability-datetime-grid">
                <section class="datetime-card">
                    <div class="datetime-card-heading">
                        <span class="datetime-number">01</span>
                        <div>
                            <strong>Unavailable From</strong>
                            <small>Select the exact starting date and time</small>
                        </div>
                    </div>

                    <div class="datetime-pair">
                        <label class="field datetime-field">
                            <span>Date *</span>
                            <input
                                type="date"
                                name="start_date"
                                value="<?= h($startDate) ?>"
                                <?= $editing ? '' : 'min="' . h(date('Y-m-d')) . '"' ?>
                                required
                                data-start-date
                            >
                        </label>

                        <label class="field datetime-field">
                            <span>Time *</span>
                            <input
                                type="time"
                                name="start_time"
                                value="<?= h($startTime) ?>"
                                step="300"
                                required
                                data-start-time
                            >
                        </label>
                    </div>
                </section>

                <section class="datetime-card">
                    <div class="datetime-card-heading">
                        <span class="datetime-number">02</span>
                        <div>
                            <strong>Expected Return</strong>
                            <small>Select the exact return date and time</small>
                        </div>
                    </div>

                    <div class="datetime-pair">
                        <label class="field datetime-field">
                            <span>Date *</span>
                            <input
                                type="date"
                                name="end_date"
                                value="<?= h($endDate) ?>"
                                min="<?= h($startDate ?: date('Y-m-d')) ?>"
                                required
                                data-end-date
                            >
                        </label>

                        <label class="field datetime-field">
                            <span>Time *</span>
                            <input
                                type="time"
                                name="end_time"
                                value="<?= h($endTime) ?>"
                                step="300"
                                required
                                data-end-time
                            >
                        </label>
                    </div>
                </section>
            </div>

            <div class="datetime-summary" data-datetime-summary aria-live="polite"></div>

            <label class="check-field">
                <input type="checkbox" name="show_return" value="1" <?= !empty($form['show_return']) ? 'checked' : '' ?>>
                <span>Show expected return date and live countdown publicly</span>
            </label>

            <label class="field">
                <span>Public Note</span>
                <textarea name="public_note"><?= h($form['public_note']) ?></textarea>
            </label>

            <label class="field">
                <span>Private Admin Note</span>
                <textarea name="private_note"><?= h($form['private_note']) ?></textarea>
            </label>

            <div class="table-actions">
                <button class="admin-button" type="submit">Save Availability</button>
                <?php if ($editing): ?>
                    <a class="admin-button secondary" href="<?= url('admin/availability.php') ?>">Add New</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-header">
            <div>
                <h2>How It Works</h2>
                <p class="admin-muted">An active date block automatically overrides the car’s default status.</p>
            </div>
        </div>

        <div class="simple-list">
            <div class="simple-list-item">
                <div>
                    <strong>Choose date and time</strong><br>
                    <span>Use the calendar field for the date and the clock field for the exact time.</span>
                </div>
            </div>
            <div class="simple-list-item">
                <div>
                    <strong>Automatic public status</strong><br>
                    <span>When a block starts, the car shows On Hire, Reserved, Maintenance or Unavailable.</span>
                </div>
            </div>
            <div class="simple-list-item">
                <div>
                    <strong>Automatic return</strong><br>
                    <span>After the selected return time passes, the website returns to the car’s default status.</span>
                </div>
            </div>
            <div class="simple-list-item">
                <div>
                    <strong>Privacy</strong><br>
                    <span>Private notes are visible only in this dashboard.</span>
                </div>
            </div>
        </div>
    </section>
</div>

<section class="admin-panel">
    <div class="admin-panel-header">
        <h2>All Availability Blocks</h2>
        <span class="admin-muted"><?= count($blocks) ?> total</span>
    </div>

    <?php if (!$blocks): ?>
        <div class="empty-state">No availability periods have been added.</div>
    <?php else: ?>
        <div class="availability-list">
            <?php foreach ($blocks as $block): ?>
                <?php $car = car_by_id($block['car_id']); ?>
                <article class="availability-item">
                    <div>
                        <span class="status-badge status-<?= h($block['status']) ?>"><?= h(status_label($block['status'])) ?></span>
                        <h3><?= h(($car['make'] ?? 'Unknown') . ' ' . ($car['model'] ?? '')) ?></h3>
                        <p><strong>From:</strong> <?= h(format_date_time($block['start_at'])) ?></p>
                        <p><strong>Until:</strong> <?= h(format_date_time($block['end_at'])) ?></p>
                        <?php if ($block['public_note']): ?>
                            <p><strong>Public:</strong> <?= h($block['public_note']) ?></p>
                        <?php endif; ?>
                        <?php if ($block['private_note']): ?>
                            <p><strong>Private:</strong> <?= h($block['private_note']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="table-actions">
                        <a class="admin-button secondary small" href="<?= url('admin/availability.php?edit=' . rawurlencode($block['id'])) ?>">Edit</a>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= h($block['id']) ?>">
                            <button class="admin-button danger small" type="submit" data-confirm="Delete this availability block?">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
