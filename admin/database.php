<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$pdo = db();
$databaseName = db_name();
$serverVersion = (string) $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$tables = ['admins','site_settings','cars','car_prices','vehicle_media','availability_blocks','enquiries','activity_logs','app_meta'];
$counts = [];
foreach ($tables as $table) {
    try { $counts[$table] = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(); }
    catch (Throwable) { $counts[$table] = null; }
}
$adminPageTitle = 'Database';
$adminCurrent = 'database';
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="stats-grid">
    <div class="stat-card"><span>Database</span><strong><?= h($databaseName) ?></strong><small>Connected</small></div>
    <div class="stat-card"><span>MySQL / MariaDB</span><strong><?= h($serverVersion) ?></strong><small><?= h(strtoupper($driver)) ?> driver</small></div>
    <div class="stat-card"><span>Cars</span><strong><?= h((string) ($counts['cars'] ?? 0)) ?></strong><small>Database records</small></div>
    <div class="stat-card"><span>Messages</span><strong><?= h((string) ($counts['enquiries'] ?? 0)) ?></strong><small>Contact & booking requests</small></div>
</div>

<section class="admin-panel">
    <div class="admin-panel-header">
        <div><h2>MySQL Database Status</h2><p class="admin-muted">All website content is now stored in real MySQL tables and will appear in phpMyAdmin.</p></div>
        <a class="admin-button" href="<?= url('admin/database-export.php') ?>">Download SQL Backup</a>
    </div>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Table</th><th>Purpose</th><th>Records</th></tr></thead>
            <tbody>
            <?php
            $labels = [
                'admins'=>'Admin users and password hashes', 'site_settings'=>'Website and SMTP settings',
                'cars'=>'Vehicle details', 'car_prices'=>'Exact 1–7 day pricing',
                'vehicle_media'=>'Images, uploaded videos and YouTube links', 'availability_blocks'=>'On-hire, reserved and maintenance dates',
                'enquiries'=>'Contact messages and booking requests', 'activity_logs'=>'Admin activity history',
                'app_meta'=>'Installation and schema information',
            ];
            foreach ($tables as $table): ?>
                <tr><td><code><?= h($table) ?></code></td><td><?= h($labels[$table] ?? '') ?></td><td><?= $counts[$table] === null ? 'Unavailable' : h((string) $counts[$table]) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-grid">
    <div class="admin-panel">
        <h2>phpMyAdmin</h2>
        <p class="admin-muted">Open phpMyAdmin from XAMPP or Hostinger hPanel, then select <strong><?= h($databaseName) ?></strong>. The tables listed above will be visible in the left sidebar.</p>
        <p class="admin-muted">The website does not store live records in JSON anymore. The JSON files included with the package are only initial migration data.</p>
    </div>
    <div class="admin-panel">
        <h2>Safe Backups</h2>
        <p class="admin-muted">Download a SQL backup before major vehicle, price, availability or settings changes. The backup contains database records but not uploaded image/video files.</p>
        <a class="admin-button secondary" href="<?= url('admin/database-export.php') ?>">Export Database</a>
    </div>
</section>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>
