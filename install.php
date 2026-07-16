<?php
declare(strict_types=1);

session_start();
$root = __DIR__;
$localConfigFile = $root . '/config.local.php';
$lockFile = $root . '/storage/installed.lock';
$errors = [];
$success = false;
$manualConfig = '';

function installer_h(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function installer_csrf(): string {
    if (empty($_SESSION['installer_csrf'])) $_SESSION['installer_csrf'] = bin2hex(random_bytes(24));
    return (string) $_SESSION['installer_csrf'];
}

function installer_read_json(string $path, mixed $default = []): mixed {
    if (!is_file($path)) return $default;
    $decoded = json_decode((string) file_get_contents($path), true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
}

function installer_datetime(mixed $value): string {
    try { return (new DateTimeImmutable((string) $value))->format('Y-m-d H:i:s'); }
    catch (Throwable) { return date('Y-m-d H:i:s'); }
}

function installer_run_schema(PDO $pdo, string $sql): void {
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') $pdo->exec($statement);
    }
}

function installer_import_seed(PDO $pdo, string $root, string $adminName, string $adminEmail, string $adminPassword): void {
    $settings = installer_read_json($root . '/storage/settings.json', []);
    if (!is_array($settings) || !$settings) {
        $settings = [
            'site_name' => 'Eleganza Rentals', 'phone' => '07728 393135', 'phone_link' => '07728393135',
            'email' => 'hello@eleganzarentals.co.uk', 'whatsapp' => '447728393135',
            'hero_kicker' => 'Italian Style • Premium Experience', 'hero_title_top' => 'DRIVE',
            'hero_title_bottom' => 'EXTRAORDINARY',
            'hero_text' => 'Premium car hire for every journey. Luxury, performance and prestige — delivered with Italian style.',
            'smtp_enabled' => '0', 'smtp_host' => 'smtp.hostinger.com', 'smtp_port' => '587',
            'smtp_encryption' => 'tls', 'smtp_username' => 'hello@eleganzarentals.co.uk', 'smtp_password' => '',
            'smtp_from_email' => 'hello@eleganzarentals.co.uk', 'smtp_from_name' => 'Eleganza Rentals',
            'notification_email' => 'hello@eleganzarentals.co.uk', 'auto_reply_enabled' => '1',
        ];
    }
    // An encrypted SMTP password from another installation cannot be safely reused with a new app key.
    $settings['smtp_password'] = '';
    $settingStmt = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=VALUES(updated_at)');
    foreach ($settings as $key => $value) $settingStmt->execute([(string) $key, (string) $value, date('Y-m-d H:i:s')]);

    $adminStmt = $pdo->prepare('INSERT INTO admins (id, name, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash), updated_at=VALUES(updated_at)');
    $now = date('Y-m-d H:i:s');
    $adminStmt->execute(['admin_primary', $adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), $now, $now]);

    $cars = installer_read_json($root . '/storage/cars.json', []);
    $carStmt = $pdo->prepare('INSERT INTO cars (id, make, model, year, slug, starting_price, short_description, description, over_seven, published, featured, sort_order, default_status, public_note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE make=VALUES(make), model=VALUES(model), year=VALUES(year), slug=VALUES(slug), starting_price=VALUES(starting_price), short_description=VALUES(short_description), description=VALUES(description), over_seven=VALUES(over_seven), published=VALUES(published), featured=VALUES(featured), sort_order=VALUES(sort_order), default_status=VALUES(default_status), public_note=VALUES(public_note), updated_at=VALUES(updated_at)');
    $priceStmt = $pdo->prepare('INSERT INTO car_prices (car_id, days, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE price=VALUES(price)');
    foreach ((array) $cars as $car) {
        $created = installer_datetime($car['created_at'] ?? 'now');
        $updated = installer_datetime($car['updated_at'] ?? $created);
        $carStmt->execute([
            (string) $car['id'], (string) $car['make'], (string) $car['model'], (string) $car['year'],
            (string) $car['slug'], (float) ($car['starting_price'] ?? 0), (string) ($car['short_description'] ?? ''),
            (string) ($car['description'] ?? ''), (string) ($car['over_seven'] ?? 'Contact us'),
            !empty($car['published']) ? 1 : 0, !empty($car['featured']) ? 1 : 0, (int) ($car['sort_order'] ?? 99),
            (string) ($car['default_status'] ?? 'available'), (string) ($car['public_note'] ?? ''), $created, $updated,
        ]);
        $pdo->prepare('DELETE FROM car_prices WHERE car_id = ?')->execute([(string) $car['id']]);
        foreach ((array) ($car['prices'] ?? []) as $days => $price) {
            if ((string) $price !== '') $priceStmt->execute([(string) $car['id'], (int) $days, (float) $price]);
        }
    }

    $mediaStmt = $pdo->prepare('INSERT INTO vehicle_media (id, car_id, media_type, media_path, poster_path, title, alt_text, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE car_id=VALUES(car_id), media_type=VALUES(media_type), media_path=VALUES(media_path), poster_path=VALUES(poster_path), title=VALUES(title), alt_text=VALUES(alt_text), sort_order=VALUES(sort_order)');
    foreach ((array) installer_read_json($root . '/storage/media.json', []) as $item) {
        $mediaStmt->execute([(string) $item['id'], (string) $item['car_id'], (string) ($item['type'] ?? 'image'), (string) ($item['path'] ?? ''), (string) ($item['poster'] ?? ''), (string) ($item['title'] ?? ''), (string) ($item['alt'] ?? ''), (int) ($item['sort_order'] ?? 99), installer_datetime($item['created_at'] ?? 'now')]);
    }

    $availabilityStmt = $pdo->prepare('INSERT INTO availability_blocks (id, car_id, status, start_at, end_at, public_note, private_note, show_return, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE car_id=VALUES(car_id), status=VALUES(status), start_at=VALUES(start_at), end_at=VALUES(end_at), public_note=VALUES(public_note), private_note=VALUES(private_note), show_return=VALUES(show_return), updated_at=VALUES(updated_at)');
    foreach ((array) installer_read_json($root . '/storage/availability.json', []) as $item) {
        $created = installer_datetime($item['created_at'] ?? 'now');
        $availabilityStmt->execute([(string) $item['id'], (string) $item['car_id'], (string) ($item['status'] ?? 'unavailable'), installer_datetime($item['start_at'] ?? 'now'), installer_datetime($item['end_at'] ?? 'now'), (string) ($item['public_note'] ?? ''), (string) ($item['private_note'] ?? ''), !empty($item['show_return']) ? 1 : 0, $created, installer_datetime($item['updated_at'] ?? $created)]);
    }

    $enquiryStmt = $pdo->prepare('INSERT INTO enquiries (id, source, subject, name, email, phone, contact_method, vehicle_id, vehicle_name, start_date, end_date, message, status, admin_notes, mail_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE source=VALUES(source), subject=VALUES(subject), name=VALUES(name), email=VALUES(email), phone=VALUES(phone), contact_method=VALUES(contact_method), vehicle_id=VALUES(vehicle_id), vehicle_name=VALUES(vehicle_name), start_date=VALUES(start_date), end_date=VALUES(end_date), message=VALUES(message), status=VALUES(status), admin_notes=VALUES(admin_notes), mail_status=VALUES(mail_status), updated_at=VALUES(updated_at)');
    foreach ((array) installer_read_json($root . '/storage/enquiries.json', []) as $item) {
        $created = installer_datetime($item['created_at'] ?? 'now');
        $enquiryStmt->execute([(string) $item['id'], (string) ($item['source'] ?? 'contact_page'), (string) ($item['subject'] ?? ''), (string) ($item['name'] ?? ''), (string) ($item['email'] ?? ''), (string) ($item['phone'] ?? ''), (string) ($item['contact_method'] ?? ''), trim((string) ($item['vehicle_id'] ?? '')) ?: null, (string) ($item['vehicle_name'] ?? ''), trim((string) ($item['start_date'] ?? '')) ?: null, trim((string) ($item['end_date'] ?? '')) ?: null, (string) ($item['message'] ?? ''), (string) ($item['status'] ?? 'new'), (string) ($item['admin_notes'] ?? ''), (string) ($item['mail_status'] ?? ''), $created, installer_datetime($item['updated_at'] ?? $created)]);
    }

    $activityStmt = $pdo->prepare('INSERT INTO activity_logs (id, action, detail, admin_email, created_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE action=VALUES(action), detail=VALUES(detail), admin_email=VALUES(admin_email)');
    foreach ((array) installer_read_json($root . '/storage/activity.json', []) as $item) {
        $activityStmt->execute([(string) $item['id'], (string) ($item['action'] ?? ''), (string) ($item['detail'] ?? ''), (string) ($item['admin'] ?? 'system'), installer_datetime($item['created_at'] ?? 'now')]);
    }

    $metaStmt = $pdo->prepare('INSERT INTO app_meta (meta_key, meta_value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value), updated_at=VALUES(updated_at)');
    $metaStmt->execute(['schema_version', '1.0.0', date('Y-m-d H:i:s')]);
    $metaStmt->execute(['installed_at', (new DateTimeImmutable())->format(DateTimeInterface::ATOM), date('Y-m-d H:i:s')]);
}

$alreadyInstalled = is_file($localConfigFile) && is_file($lockFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyInstalled) {
    if (!hash_equals(installer_csrf(), (string) ($_POST['_csrf'] ?? ''))) {
        $errors[] = 'Your setup session expired. Refresh the page and try again.';
    }

    $host = trim((string) ($_POST['db_host'] ?? 'localhost'));
    $port = max(1, (int) ($_POST['db_port'] ?? 3306));
    $database = trim((string) ($_POST['db_name'] ?? ''));
    $username = trim((string) ($_POST['db_user'] ?? ''));
    $password = (string) ($_POST['db_password'] ?? '');
    $adminName = trim((string) ($_POST['admin_name'] ?? 'Eleganza Administrator'));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? 'admin@eleganzarentals.co.uk'));
    $adminPassword = (string) ($_POST['admin_password'] ?? '');

    if ($database === '' || $username === '') $errors[] = 'Database name and database username are required.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid admin email address.';
    if (strlen($adminPassword) < 10) $errors[] = 'The admin password must contain at least 10 characters.';

    if (!$errors) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
            ]);
            installer_run_schema($pdo, (string) file_get_contents($root . '/database/schema.sql'));
            installer_import_seed($pdo, $root, $adminName, $adminEmail, $adminPassword);

            $appKey = base64_encode(random_bytes(32));
            $configArray = [
                'app_key' => $appKey,
                'database' => [
                    'host' => $host, 'port' => $port, 'name' => $database,
                    'username' => $username, 'password' => $password, 'charset' => 'utf8mb4',
                ],
            ];
            $manualConfig = "<?php\nreturn " . var_export($configArray, true) . ";\n";
            if (@file_put_contents($localConfigFile, $manualConfig, LOCK_EX) === false) {
                $errors[] = 'The database was installed, but PHP could not create config.local.php. Copy the configuration shown below into a new config.local.php file.';
            } else {
                if (!is_dir(dirname($lockFile))) @mkdir(dirname($lockFile), 0775, true);
                @file_put_contents($lockFile, 'Installed ' . date(DATE_ATOM));
                $success = true;
                $alreadyInstalled = true;
            }
        } catch (Throwable $e) {
            $errors[] = 'Database setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en-GB">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Eleganza Rentals</title>
<style>
:root{--gold:#cda451;--gold2:#efd081;--bg:#050505;--panel:#0d0d0d;--line:rgba(205,164,81,.28);--text:#f7f3eb;--muted:#aaa69d}*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 85% 10%,rgba(205,164,81,.12),transparent 30%),var(--bg);color:var(--text);font-family:Arial,sans-serif;min-height:100vh}.wrap{width:min(960px,calc(100% - 32px));margin:50px auto}.brand{display:flex;align-items:center;gap:18px;margin-bottom:24px}.brand img{width:190px;max-height:100px;object-fit:contain}.brand span{color:var(--gold2);letter-spacing:.18em;text-transform:uppercase;font-size:12px}.panel{background:rgba(13,13,13,.96);border:1px solid var(--line);padding:34px;box-shadow:0 25px 80px rgba(0,0,0,.45)}h1{font-family:Georgia,serif;font-size:50px;font-weight:500;margin:0 0 12px}p{color:var(--muted);line-height:1.7}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.full{grid-column:1/-1}label{display:block}label span{display:block;color:var(--gold2);font-size:11px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:8px}input{width:100%;background:#070707;color:#fff;border:1px solid rgba(255,255,255,.14);padding:15px 16px;outline:none}input:focus{border-color:var(--gold)}.button{display:inline-flex;background:linear-gradient(135deg,var(--gold2),#b98a34);color:#080808;border:0;padding:16px 25px;text-transform:uppercase;letter-spacing:.13em;font-weight:700;cursor:pointer;margin-top:18px}.notice{padding:15px 17px;margin:18px 0;border:1px solid}.error{border-color:#9d4545;background:rgba(157,69,69,.12)}.success{border-color:#4d8a59;background:rgba(77,138,89,.12)}code,pre{background:#080808;border:1px solid var(--line);padding:14px;display:block;overflow:auto;color:#ead89f}.links{display:flex;gap:12px;flex-wrap:wrap}.links a{color:#080808;background:var(--gold2);padding:12px 18px;text-decoration:none;font-weight:700}@media(max-width:700px){.wrap{margin:18px auto}.panel{padding:22px}.grid{grid-template-columns:1fr}.full{grid-column:auto}h1{font-size:38px}.brand{flex-direction:column;align-items:flex-start}}
</style>
</head>
<body><div class="wrap"><div class="brand"><img src="assets/images/logo.png" alt="Eleganza Rentals"><span>MySQL Installation</span></div><div class="panel">
<?php if ($alreadyInstalled && !$success): ?>
<h1>Database is installed.</h1><p>Your website is connected to MySQL. The tables will now appear inside the selected database in phpMyAdmin.</p><div class="links"><a href="index.php">Open Website</a><a href="admin/">Open Admin</a></div><p>For security, this installer is locked. To reinstall, delete <code>config.local.php</code> and <code>storage/installed.lock</code>.</p>
<?php elseif ($success): ?>
<div class="notice success">Installation completed successfully.</div><h1>Eleganza is ready.</h1><p>The database tables, initial cars, prices, images, videos, settings and secure admin account have been created.</p><div class="links"><a href="index.php">Open Website</a><a href="admin/">Open Admin Dashboard</a></div>
<?php else: ?>
<h1>Connect the website to MySQL.</h1><p>Create an empty MySQL database in XAMPP/phpMyAdmin or Hostinger hPanel, then enter its credentials below. Existing Eleganza car data will be imported automatically.</p>
<?php foreach ($errors as $error): ?><div class="notice error"><?= installer_h($error) ?></div><?php endforeach; ?>
<form method="post"><input type="hidden" name="_csrf" value="<?= installer_h(installer_csrf()) ?>"><div class="grid">
<label><span>Database Host</span><input name="db_host" value="<?= installer_h($_POST['db_host'] ?? 'localhost') ?>" required></label>
<label><span>Database Port</span><input type="number" name="db_port" value="<?= installer_h($_POST['db_port'] ?? '3306') ?>" required></label>
<label><span>Database Name</span><input name="db_name" value="<?= installer_h($_POST['db_name'] ?? 'eleganza_rentals') ?>" required></label>
<label><span>Database Username</span><input name="db_user" value="<?= installer_h($_POST['db_user'] ?? 'root') ?>" required></label>
<label class="full"><span>Database Password</span><input type="password" name="db_password" value=""></label>
<label><span>Admin Name</span><input name="admin_name" value="<?= installer_h($_POST['admin_name'] ?? 'Eleganza Administrator') ?>" required></label>
<label><span>Admin Email</span><input type="email" name="admin_email" value="<?= installer_h($_POST['admin_email'] ?? 'admin@eleganzarentals.co.uk') ?>" required></label>
<label class="full"><span>New Admin Password</span><input type="password" name="admin_password" minlength="10" required placeholder="Use at least 10 characters"></label>
</div><button class="button" type="submit">Install Database</button></form>
<?php if ($manualConfig !== ''): ?><h2>Manual config.local.php</h2><p>Create <strong>config.local.php</strong> in the website root and paste this exact content:</p><pre><?= installer_h($manualConfig) ?></pre><?php endif; ?>
<?php endif; ?>
</div></div></body></html>
