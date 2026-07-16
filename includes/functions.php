<?php
declare(strict_types=1);

function cfg(?string $key = null): mixed {
    global $config;
    return $key === null ? $config : ($config[$key] ?? null);
}

require_once __DIR__ . '/database.php';

function project_root(): string { return dirname(__DIR__); }
function storage_path(string $file = ''): string { return rtrim((string) cfg('storage_dir'), '/') . ($file ? '/' . ltrim($file, '/') : ''); }
function upload_path(string $file = ''): string { return rtrim((string) cfg('upload_dir'), '/') . ($file ? '/' . ltrim($file, '/') : ''); }

function h(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }

function base_path(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $marker = strpos($script, '/admin/');
    if ($marker === false) $marker = strpos($script, '/api/');
    if ($marker !== false) return rtrim(substr($script, 0, $marker), '/');
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    return $dir === '/' ? '' : $dir;
}

function url(string $path = ''): string {
    $base = base_path();
    $path = ltrim($path, '/');
    return ($base ?: '') . ($path !== '' ? '/' . $path : '/');
}

function asset(string $path): string { return url($path); }
function redirect(string $location): never { header('Location: ' . $location); exit; }

function ensure_dir(string $path): void {
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Unable to create directory: ' . $path);
    }
}

/**
 * Compatibility data layer backed by proper MySQL tables.
 * The rest of the website can continue using its original array-based helpers,
 * while every vehicle, price, media item, availability block and message is
 * stored visibly in phpMyAdmin.
 */
function data_read(string $name, mixed $default = []): mixed {
    try {
        $pdo = db();
        return match ($name) {
            'settings' => (function () use ($pdo) {
                $rows = $pdo->query('SELECT setting_key, setting_value FROM site_settings ORDER BY setting_key')->fetchAll();
                $settings = [];
                foreach ($rows as $row) $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
                return $settings;
            })(),
            'admins' => $pdo->query('SELECT id, name, email, password_hash, created_at, updated_at FROM admins ORDER BY created_at')->fetchAll(),
            'cars' => (function () use ($pdo) {
                $cars = $pdo->query('SELECT id, make, model, year, slug, starting_price, short_description, description, over_seven, published, featured, sort_order, default_status, public_note, created_at, updated_at FROM cars ORDER BY sort_order, created_at')->fetchAll();
                $priceStmt = $pdo->prepare('SELECT days, price FROM car_prices WHERE car_id = ? ORDER BY days');
                foreach ($cars as &$car) {
                    $priceStmt->execute([$car['id']]);
                    $prices = [];
                    foreach ($priceStmt->fetchAll() as $price) $prices[(string) $price['days']] = db_decimal_string($price['price']);
                    $car['prices'] = $prices;
                    $car['starting_price'] = db_decimal_string($car['starting_price']);
                    $car['published'] = (bool) $car['published'];
                    $car['featured'] = (bool) $car['featured'];
                    $car['sort_order'] = (int) $car['sort_order'];
                }
                unset($car);
                return $cars;
            })(),
            'media' => array_map(static function (array $row): array {
                return [
                    'id' => $row['id'], 'car_id' => $row['car_id'], 'type' => $row['media_type'],
                    'path' => $row['media_path'], 'poster' => $row['poster_path'] ?? '',
                    'title' => $row['title'] ?? '', 'alt' => $row['alt_text'] ?? '',
                    'sort_order' => (int) $row['sort_order'], 'created_at' => $row['created_at'],
                ];
            }, $pdo->query('SELECT * FROM vehicle_media ORDER BY car_id, sort_order, created_at')->fetchAll()),
            'availability' => array_map(static function (array $row): array {
                return [
                    'id' => $row['id'], 'car_id' => $row['car_id'], 'status' => $row['status'],
                    'start_at' => $row['start_at'], 'end_at' => $row['end_at'],
                    'public_note' => $row['public_note'] ?? '', 'private_note' => $row['private_note'] ?? '',
                    'show_return' => (bool) $row['show_return'],
                    'created_at' => $row['created_at'], 'updated_at' => $row['updated_at'],
                ];
            }, $pdo->query('SELECT * FROM availability_blocks ORDER BY start_at, end_at')->fetchAll()),
            'enquiries' => array_map(static function (array $row): array {
                return [
                    'id' => $row['id'], 'source' => $row['source'], 'subject' => $row['subject'] ?? '',
                    'name' => $row['name'], 'email' => $row['email'], 'phone' => $row['phone'],
                    'contact_method' => $row['contact_method'] ?? '', 'vehicle_id' => $row['vehicle_id'] ?? '',
                    'vehicle_name' => $row['vehicle_name'] ?? '', 'start_date' => $row['start_date'] ?? '',
                    'end_date' => $row['end_date'] ?? '', 'message' => $row['message'],
                    'status' => $row['status'], 'admin_notes' => $row['admin_notes'] ?? '',
                    'mail_status' => $row['mail_status'] ?? '', 'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                ];
            }, $pdo->query('SELECT * FROM enquiries ORDER BY created_at DESC')->fetchAll()),
            'activity' => array_map(static function (array $row): array {
                return [
                    'id' => $row['id'], 'action' => $row['action'], 'detail' => $row['detail'] ?? '',
                    'admin' => $row['admin_email'] ?? 'system', 'created_at' => $row['created_at'],
                ];
            }, $pdo->query('SELECT * FROM activity_logs ORDER BY created_at')->fetchAll()),
            default => $default,
        };
    } catch (Throwable $e) {
        if ((bool) cfg('debug')) throw $e;
        return $default;
    }
}

function data_write(string $name, mixed $data): void {
    if (!is_array($data)) throw new InvalidArgumentException('Database data must be an array.');
    $pdo = db();
    $pdo->beginTransaction();
    try {
        match ($name) {
            'settings' => db_sync_settings($pdo, $data),
            'admins' => db_sync_admins($pdo, $data),
            'cars' => db_sync_cars($pdo, $data),
            'media' => db_sync_media($pdo, $data),
            'availability' => db_sync_availability($pdo, $data),
            'enquiries' => db_sync_enquiries($pdo, $data),
            'activity' => db_sync_activity($pdo, $data),
            default => throw new InvalidArgumentException('Unknown database collection: ' . $name),
        };
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function db_decimal_string(mixed $value): string {
    $number = (float) $value;
    return floor($number) === $number ? (string) (int) $number : rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
}

function db_delete_missing(PDO $pdo, string $table, string $column, array $ids): void {
    $ids = array_values(array_unique(array_filter(array_map('strval', $ids), static fn(string $id): bool => $id !== '')));
    if (!$ids) {
        $pdo->exec("DELETE FROM `{$table}`");
        return;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $pdo->prepare("DELETE FROM `{$table}` WHERE `{$column}` NOT IN ({$placeholders})");
    $statement->execute($ids);
}

function db_sync_settings(PDO $pdo, array $settings): void {
    $statement = $pdo->prepare('INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)');
    $now = date('Y-m-d H:i:s');
    foreach ($settings as $key => $value) $statement->execute([(string) $key, (string) $value, $now]);
    db_delete_missing($pdo, 'site_settings', 'setting_key', array_keys($settings));
}

function db_sync_admins(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO admins (id, name, email, password_hash, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash), updated_at=VALUES(updated_at)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('admin_')); $ids[] = $id;
        $created = db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'));
        $updated = db_datetime($item['updated_at'] ?? '', $created);
        $statement->execute([$id, (string) ($item['name'] ?? 'Administrator'), (string) ($item['email'] ?? ''), (string) ($item['password_hash'] ?? ''), $created, $updated]);
    }
    db_delete_missing($pdo, 'admins', 'id', $ids);
}

function db_sync_cars(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO cars (id, make, model, year, slug, starting_price, short_description, description, over_seven, published, featured, sort_order, default_status, public_note, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE make=VALUES(make), model=VALUES(model), year=VALUES(year), slug=VALUES(slug), starting_price=VALUES(starting_price), short_description=VALUES(short_description), description=VALUES(description), over_seven=VALUES(over_seven), published=VALUES(published), featured=VALUES(featured), sort_order=VALUES(sort_order), default_status=VALUES(default_status), public_note=VALUES(public_note), updated_at=VALUES(updated_at)');
    $priceStatement = $pdo->prepare('INSERT INTO car_prices (car_id, days, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE price=VALUES(price)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('car_')); $ids[] = $id;
        $created = db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'));
        $updated = db_datetime($item['updated_at'] ?? '', $created);
        $statement->execute([$id, (string) ($item['make'] ?? ''), (string) ($item['model'] ?? ''), (string) ($item['year'] ?? ''), (string) ($item['slug'] ?? ''), (float) ($item['starting_price'] ?? 0), (string) ($item['short_description'] ?? ''), (string) ($item['description'] ?? ''), (string) ($item['over_seven'] ?? 'Contact us'), db_bool($item['published'] ?? false), db_bool($item['featured'] ?? false), (int) ($item['sort_order'] ?? 99), (string) ($item['default_status'] ?? 'available'), (string) ($item['public_note'] ?? ''), $created, $updated]);
        $pdo->prepare('DELETE FROM car_prices WHERE car_id = ?')->execute([$id]);
        foreach (($item['prices'] ?? []) as $days => $price) {
            if ((string) $price === '') continue;
            $priceStatement->execute([$id, (int) $days, (float) $price]);
        }
    }
    db_delete_missing($pdo, 'cars', 'id', $ids);
}

function db_sync_media(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO vehicle_media (id, car_id, media_type, media_path, poster_path, title, alt_text, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE car_id=VALUES(car_id), media_type=VALUES(media_type), media_path=VALUES(media_path), poster_path=VALUES(poster_path), title=VALUES(title), alt_text=VALUES(alt_text), sort_order=VALUES(sort_order)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('media_')); $ids[] = $id;
        $statement->execute([$id, (string) ($item['car_id'] ?? ''), (string) ($item['type'] ?? 'image'), (string) ($item['path'] ?? ''), (string) ($item['poster'] ?? ''), (string) ($item['title'] ?? ''), (string) ($item['alt'] ?? ''), (int) ($item['sort_order'] ?? 99), db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'))]);
    }
    db_delete_missing($pdo, 'vehicle_media', 'id', $ids);
}

function db_sync_availability(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO availability_blocks (id, car_id, status, start_at, end_at, public_note, private_note, show_return, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE car_id=VALUES(car_id), status=VALUES(status), start_at=VALUES(start_at), end_at=VALUES(end_at), public_note=VALUES(public_note), private_note=VALUES(private_note), show_return=VALUES(show_return), updated_at=VALUES(updated_at)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('availability_')); $ids[] = $id;
        $created = db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'));
        $statement->execute([$id, (string) ($item['car_id'] ?? ''), (string) ($item['status'] ?? 'unavailable'), db_datetime($item['start_at'] ?? '', date('Y-m-d H:i:s')), db_datetime($item['end_at'] ?? '', date('Y-m-d H:i:s')), (string) ($item['public_note'] ?? ''), (string) ($item['private_note'] ?? ''), db_bool($item['show_return'] ?? false), $created, db_datetime($item['updated_at'] ?? '', $created)]);
    }
    db_delete_missing($pdo, 'availability_blocks', 'id', $ids);
}

function db_sync_enquiries(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO enquiries (id, source, subject, name, email, phone, contact_method, vehicle_id, vehicle_name, start_date, end_date, message, status, admin_notes, mail_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE source=VALUES(source), subject=VALUES(subject), name=VALUES(name), email=VALUES(email), phone=VALUES(phone), contact_method=VALUES(contact_method), vehicle_id=VALUES(vehicle_id), vehicle_name=VALUES(vehicle_name), start_date=VALUES(start_date), end_date=VALUES(end_date), message=VALUES(message), status=VALUES(status), admin_notes=VALUES(admin_notes), mail_status=VALUES(mail_status), updated_at=VALUES(updated_at)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('enquiry_')); $ids[] = $id;
        $created = db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'));
        $vehicleId = trim((string) ($item['vehicle_id'] ?? '')) ?: null;
        $startDate = trim((string) ($item['start_date'] ?? '')) ?: null;
        $endDate = trim((string) ($item['end_date'] ?? '')) ?: null;
        $statement->execute([$id, (string) ($item['source'] ?? 'contact_page'), (string) ($item['subject'] ?? ''), (string) ($item['name'] ?? ''), (string) ($item['email'] ?? ''), (string) ($item['phone'] ?? ''), (string) ($item['contact_method'] ?? ''), $vehicleId, (string) ($item['vehicle_name'] ?? ''), $startDate, $endDate, (string) ($item['message'] ?? ''), (string) ($item['status'] ?? 'new'), (string) ($item['admin_notes'] ?? ''), (string) ($item['mail_status'] ?? ''), $created, db_datetime($item['updated_at'] ?? '', $created)]);
    }
    db_delete_missing($pdo, 'enquiries', 'id', $ids);
}

function db_sync_activity(PDO $pdo, array $items): void {
    $statement = $pdo->prepare('INSERT INTO activity_logs (id, action, detail, admin_email, created_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE action=VALUES(action), detail=VALUES(detail), admin_email=VALUES(admin_email)');
    $ids = [];
    foreach ($items as $item) {
        $id = (string) ($item['id'] ?? make_id('log_')); $ids[] = $id;
        $statement->execute([$id, (string) ($item['action'] ?? ''), (string) ($item['detail'] ?? ''), (string) ($item['admin'] ?? 'system'), db_datetime($item['created_at'] ?? '', date('Y-m-d H:i:s'))]);
    }
    db_delete_missing($pdo, 'activity_logs', 'id', $ids);
}

function make_id(string $prefix = ''): string { return $prefix . bin2hex(random_bytes(8)); }
function now_iso(): string { return (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM); }

function ensure_storage(): void {
    ensure_dir(storage_path());
    ensure_dir(upload_path());

    if (!db_configured()) {
        throw new RuntimeException('MySQL is not configured. Open install.php to complete the database setup.');
    }
    if (!db_table_exists('site_settings')) {
        throw new RuntimeException('The database tables have not been installed. Open install.php to complete setup.');
    }

    $settings = data_read('settings', []);
    if (!$settings) {
        $settings = [
            'site_name' => 'Eleganza Rentals',
            'phone' => '07728 393135',
            'phone_link' => '07728393135',
            'email' => 'hello@eleganzarentals.co.uk',
            'whatsapp' => '447728393135',
            'hero_kicker' => 'Italian Style • Premium Experience',
            'hero_title_top' => 'DRIVE',
            'hero_title_bottom' => 'EXTRAORDINARY',
            'hero_text' => 'Premium car hire for every journey. Luxury, performance and prestige — delivered with Italian style.',
            'about_intro' => 'Eleganza Rentals is built for drivers who expect more than transportation. We combine premium vehicles, attentive communication and a polished rental experience with a distinctly Italian sense of style.',
            'footer_line' => 'Elegance in every detail. Excellence on every road.',
            'smtp_enabled' => '0',
            'smtp_host' => 'smtp.hostinger.com',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => 'hello@eleganzarentals.co.uk',
            'smtp_password' => '',
            'smtp_from_email' => 'hello@eleganzarentals.co.uk',
            'smtp_from_name' => 'Eleganza Rentals',
            'notification_email' => 'hello@eleganzarentals.co.uk',
            'auto_reply_enabled' => '1',
            'notification_subject' => 'New Eleganza Rentals enquiry: {{vehicle}}',
            'auto_reply_subject' => 'We received your Eleganza Rentals enquiry',
            'auto_reply_body' => '<p>Hello {{name}},</p><p>Thank you for contacting Eleganza Rentals. We have received your enquiry for <strong>{{vehicle}}</strong> and will respond as soon as possible.</p><p>Eleganza Rentals<br>07728 393135</p>',
        ];
        data_write('settings', $settings);
    }

    $admins = data_read('admins', []);
    if (!$admins) {
        data_write('admins', [[
            'id' => 'admin_primary',
            'name' => 'Eleganza Administrator',
            'email' => 'admin@eleganzarentals.co.uk',
            'password_hash' => '$2y$12$Uy161.mh.Rw3Z4stJBi73.SxteE5bTWrZjyRB52K2pfKBwpCNEsvO',
            'created_at' => now_iso(),
            'updated_at' => now_iso(),
        ]]);
    }

    if (!data_read('cars', [])) seed_demo_data();
}

function seed_demo_data(): void {
    $cars = [
        [
            'id' => 'car_mercedes', 'make' => 'MERCEDES-BENZ', 'model' => 'AMG A 35 PREMIUM + 4MATIC EDITION',
            'year' => '2022', 'slug' => 'mercedes-benz-amg-a35-premium-4matic', 'starting_price' => '300',
            'short_description' => 'AMG performance, premium comfort and confident 4MATIC road presence.',
            'description' => 'A premium compact performance vehicle combining distinctive AMG styling, refined comfort and confident all-weather capability. Ideal for special occasions, business travel and memorable weekend drives.',
            'prices' => ['1'=>'300','2'=>'500','3'=>'700','4'=>'900','5'=>'1100','6'=>'1300','7'=>'1500'],
            'over_seven' => 'Contact us', 'published' => true, 'featured' => true, 'sort_order' => 1,
            'default_status' => 'available', 'public_note' => '', 'created_at' => now_iso(), 'updated_at' => now_iso(),
        ],
        [
            'id' => 'car_bmw', 'make' => 'BMW', 'model' => '420d XDRIVE', 'year' => '2018',
            'slug' => 'bmw-420d-xdrive', 'starting_price' => '180',
            'short_description' => 'Refined coupé styling, premium comfort and confident xDrive capability.',
            'description' => 'A sophisticated BMW coupé created for comfortable long-distance journeys and stylish city driving. Its elegant profile and composed road manners make every trip feel considered and premium.',
            'prices' => ['1'=>'180','2'=>'290','3'=>'400','4'=>'510','5'=>'620','6'=>'730','7'=>'840'],
            'over_seven' => 'Contact us', 'published' => true, 'featured' => true, 'sort_order' => 2,
            'default_status' => 'available', 'public_note' => '', 'created_at' => now_iso(), 'updated_at' => now_iso(),
        ]
    ];
    data_write('cars', $cars);

    $media = [];
    foreach ([
        ['car_mercedes','mercedes','Mercedes-Benz AMG A 35'],
        ['car_bmw','bmw','BMW 420d xDrive']
    ] as [$carId, $folder, $label]) {
        for ($i=1; $i<=4; $i++) {
            $media[] = [
                'id' => make_id('media_'), 'car_id' => $carId, 'type' => 'image',
                'path' => "uploads/cars/{$folder}/{$folder}-0{$i}.jpg", 'poster' => '',
                'title' => $label . ' view ' . $i, 'alt' => $label, 'sort_order' => $i,
                'created_at' => now_iso(),
            ];
        }
        $youtubeVideos = $carId === 'car_mercedes'
            ? [
                ['https://www.youtube.com/watch?v=5NKPcsrTdKY', '2022 Mercedes-AMG A 35 — Exterior, Interior & Sound'],
                ['https://www.youtube.com/watch?v=G04wzWRH9ME', '2022 A 35 Premium 4MATIC Edition — Walkaround'],
            ]
            : [
                ['https://www.youtube.com/watch?v=BZWHH9ScmwM', '2018 BMW 420d xDrive — Exterior & Interior'],
                ['https://www.youtube.com/watch?v=1V8ep4o3-B8', '2018 BMW 420d M Sport — Walkaround'],
            ];
        foreach ($youtubeVideos as $videoIndex => [$videoUrl, $videoTitle]) {
            $media[] = [
                'id' => make_id('media_'), 'car_id' => $carId, 'type' => 'youtube',
                'path' => $videoUrl, 'poster' => "uploads/cars/{$folder}/{$folder}-0" . ($videoIndex + 1) . '.jpg',
                'title' => $videoTitle, 'alt' => $label . ' video', 'sort_order' => 5 + $videoIndex,
                'created_at' => now_iso(),
            ];
        }
    }
    data_write('media', $media);
    data_write('availability', []);
}

function settings_all(): array { return data_read('settings', []); }
function setting(string $key, mixed $default = ''): mixed { $all = settings_all(); return $all[$key] ?? $default; }
function save_settings(array $changes): void { data_write('settings', array_merge(settings_all(), $changes)); }

function cars_all(bool $publishedOnly = false): array {
    $cars = data_read('cars', []);
    if ($publishedOnly) $cars = array_values(array_filter($cars, fn($c) => !empty($c['published'])));
    usort($cars, fn($a,$b) => ((int)($a['sort_order'] ?? 99)) <=> ((int)($b['sort_order'] ?? 99)));
    return $cars;
}

function car_by_id(string $id): ?array {
    foreach (cars_all(false) as $car) if (($car['id'] ?? '') === $id) return $car;
    return null;
}

function car_by_slug(string $slug): ?array {
    foreach (cars_all(true) as $car) if (($car['slug'] ?? '') === $slug) return $car;
    return null;
}

function save_car(array $car): array {
    $cars = cars_all(false);
    $existingIndex = null;
    foreach ($cars as $i => $existing) if (($existing['id'] ?? '') === ($car['id'] ?? '')) { $existingIndex = $i; break; }
    $car['updated_at'] = now_iso();
    if (!$car['id']) $car['id'] = make_id('car_');
    if (!$car['created_at']) $car['created_at'] = now_iso();
    if ($existingIndex === null) $cars[] = $car; else $cars[$existingIndex] = $car;
    data_write('cars', $cars);
    return $car;
}

function delete_car(string $id): void {
    data_write('cars', array_values(array_filter(cars_all(false), fn($c) => ($c['id'] ?? '') !== $id)));
    $media = data_read('media', []);
    foreach ($media as $item) if (($item['car_id'] ?? '') === $id) delete_uploaded_path((string)($item['path'] ?? ''));
    data_write('media', array_values(array_filter($media, fn($m) => ($m['car_id'] ?? '') !== $id)));
    data_write('availability', array_values(array_filter(data_read('availability', []), fn($a) => ($a['car_id'] ?? '') !== $id)));
}

function youtube_video_id(string $value): string {
    $value = trim($value);
    if ($value === '') return '';
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $value)) return $value;

    $parts = parse_url($value);
    if (!is_array($parts)) return '';
    $host = strtolower((string)($parts['host'] ?? ''));
    $host = preg_replace('/^www\./', '', $host) ?? $host;
    $path = trim((string)($parts['path'] ?? ''), '/');
    $candidate = '';

    if ($host === 'youtu.be') {
        $candidate = explode('/', $path)[0] ?? '';
    } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
        parse_str((string)($parts['query'] ?? ''), $query);
        if (!empty($query['v'])) {
            $candidate = (string)$query['v'];
        } elseif (preg_match('#^(?:embed|shorts|live)/([A-Za-z0-9_-]{11})#', $path, $match)) {
            $candidate = $match[1];
        }
    }

    return preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : '';
}

function youtube_embed_url(string $value): string {
    $id = youtube_video_id($value);
    return $id === '' ? '' : 'https://www.youtube-nocookie.com/embed/' . $id . '?rel=0&modestbranding=1';
}

function youtube_thumbnail_url(string $value): string {
    $id = youtube_video_id($value);
    return $id === '' ? '' : 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg';
}

function media_all(): array { return data_read('media', []); }
function media_for_car(string $carId): array {
    $items = array_values(array_filter(media_all(), fn($m) => ($m['car_id'] ?? '') === $carId));
    usort($items, fn($a,$b) => ((int)($a['sort_order'] ?? 99)) <=> ((int)($b['sort_order'] ?? 99)));
    return $items;
}
function media_by_id(string $id): ?array { foreach(media_all() as $m) if(($m['id']??'')===$id) return $m; return null; }
function save_media(array $item): array {
    $all = media_all(); $index = null;
    foreach($all as $i=>$m) if(($m['id']??'')===($item['id']??'')) {$index=$i;break;}
    if(empty($item['id'])) $item['id']=make_id('media_');
    if(empty($item['created_at'])) $item['created_at']=now_iso();
    if($index===null) $all[]=$item; else $all[$index]=$item;
    data_write('media',$all); return $item;
}
function delete_media(string $id): void {
    $all=media_all();
    foreach($all as $m) if(($m['id']??'')===$id){delete_uploaded_path((string)($m['path']??''));break;}
    data_write('media',array_values(array_filter($all,fn($m)=>($m['id']??'')!==$id)));
}

function cover_media(array $car): ?array {
    $items = media_for_car((string)$car['id']);
    foreach ($items as $item) if (($item['type'] ?? '') === 'image') return $item;
    return $items[0] ?? null;
}

function availability_all(): array { return data_read('availability', []); }
function availability_for_car(string $carId): array {
    $items = array_values(array_filter(availability_all(), fn($a) => ($a['car_id'] ?? '') === $carId));
    usort($items, fn($a,$b) => strcmp((string)($a['start_at']??''),(string)($b['start_at']??'')));
    return $items;
}
function save_availability(array $item): array {
    $all=availability_all();$index=null;
    foreach($all as $i=>$a) if(($a['id']??'')===($item['id']??'')){$index=$i;break;}
    if(empty($item['id']))$item['id']=make_id('availability_');
    $item['updated_at']=now_iso();if(empty($item['created_at']))$item['created_at']=now_iso();
    if($index===null)$all[]=$item;else$all[$index]=$item;
    data_write('availability',$all);return $item;
}
function delete_availability(string $id): void { data_write('availability',array_values(array_filter(availability_all(),fn($a)=>($a['id']??'')!==$id))); }

function availability_overlap(string $carId,string $start,string $end,string $ignoreId=''): bool {
    $s=strtotime($start);$e=strtotime($end);
    foreach(availability_for_car($carId) as $a){
        if(($a['id']??'')===$ignoreId)continue;
        $as=strtotime((string)$a['start_at']);$ae=strtotime((string)$a['end_at']);
        if($s<$ae && $e>$as)return true;
    }
    return false;
}

function car_status(array $car): array {
    $now=time();$active=null;$next=null;
    foreach(availability_for_car((string)$car['id']) as $block){
        $s=strtotime((string)($block['start_at']??''));$e=strtotime((string)($block['end_at']??''));
        if($s <= $now && $e > $now){$active=$block;break;}
        if($s > $now && ($next===null || $s<strtotime((string)$next['start_at'])))$next=$block;
    }
    if($active){
        $status=$active['status']??'unavailable';
        return ['key'=>$status,'label'=>status_label($status),'available'=>false,'return_at'=>$active['end_at']??'',
            'show_return'=>!empty($active['show_return']),'public_note'=>$active['public_note']??'','next'=>$next];
    }
    $default=$car['default_status']??'available';
    return ['key'=>$default,'label'=>status_label($default),'available'=>$default==='available','return_at'=>'','show_return'=>false,'public_note'=>$car['public_note']??'','next'=>$next];
}
function status_label(string $status): string { return match($status){'available'=>'Available Now','reserved'=>'Reserved','on_hire'=>'Currently On Hire','maintenance'=>'Maintenance','unavailable'=>'Temporarily Unavailable','available_soon'=>'Available Soon',default=>ucwords(str_replace('_',' ',$status))}; }
function status_options(): array { return ['available'=>'Available','reserved'=>'Reserved','on_hire'=>'On Hire','maintenance'=>'Maintenance','unavailable'=>'Temporarily Unavailable','available_soon'=>'Available Soon']; }

function enquiries_all(): array { $items=data_read('enquiries',[]); usort($items,fn($a,$b)=>strcmp((string)($b['created_at']??''),(string)($a['created_at']??''))); return $items; }
function enquiry_by_id(string $id): ?array { foreach(enquiries_all() as $e)if(($e['id']??'')===$id)return $e;return null; }
function save_enquiry(array $item): array {
    $all=data_read('enquiries',[]);$index=null;
    foreach($all as $i=>$e)if(($e['id']??'')===($item['id']??'')){$index=$i;break;}
    if(empty($item['id']))$item['id']=make_id('enquiry_');
    if(empty($item['created_at']))$item['created_at']=now_iso();$item['updated_at']=now_iso();
    if($index===null)$all[]=$item;else$all[$index]=$item;data_write('enquiries',$all);return $item;
}
function delete_enquiry(string $id): void { data_write('enquiries',array_values(array_filter(data_read('enquiries',[]),fn($e)=>($e['id']??'')!==$id))); }

function excerpt(string $text, int $limit = 180): string { $text=trim(preg_replace('/\s+/u',' ',$text)??$text); return strlen($text)>$limit?substr($text,0,$limit-3).'...':$text; }
function format_money(mixed $amount): string { return '£' . number_format((float)$amount, 0); }
function format_date_time(string $value): string { if(!$value)return ''; try{return (new DateTimeImmutable($value))->format('j F Y \a\t g:i A');}catch(Throwable){return $value;} }
function datetime_local(string $value): string { if(!$value)return ''; try{return (new DateTimeImmutable($value))->format('Y-m-d\TH:i');}catch(Throwable){return $value;} }
function slugify(string $text): string { $text=strtolower(trim($text));$text=preg_replace('/[^a-z0-9]+/','-',$text)??'';return trim($text,'-'); }

function csrf_token(): string { if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(24)); return $_SESSION['_csrf']; }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.h(csrf_token()).'">'; }
function verify_csrf(): void { $token=(string)($_POST['_csrf']??''); if(!$token||!hash_equals((string)($_SESSION['_csrf']??''),$token)){http_response_code(419);exit('Your session expired. Please go back and try again.');} }

function flash(string $type,string $message): void { $_SESSION['_flash'][]=['type'=>$type,'message'=>$message]; }
function flashes(): array { $f=$_SESSION['_flash']??[];unset($_SESSION['_flash']);return $f; }

function admin_user(): ?array { return $_SESSION['admin_user']??null; }
function is_admin(): bool { return admin_user()!==null; }
function require_admin(): void { if(!is_admin())redirect(url('admin/login.php')); }
function admin_login(string $email,string $password): bool {
    foreach(data_read('admins',[]) as $admin){
        if(strcasecmp((string)$admin['email'],$email)===0 && password_verify($password,(string)$admin['password_hash'])){
            session_regenerate_id(true);$_SESSION['admin_user']=['id'=>$admin['id'],'name'=>$admin['name'],'email'=>$admin['email']];return true;
        }
    }return false;
}
function admin_logout(): void { unset($_SESSION['admin_user']);session_regenerate_id(true); }
function update_admin_password(string $id,string $password): void { $all=data_read('admins',[]);foreach($all as &$a)if(($a['id']??'')===$id){$a['password_hash']=password_hash($password,PASSWORD_DEFAULT);$a['updated_at']=now_iso();}data_write('admins',$all); }

function encrypt_secret(string $plain): string {
    if($plain==='')return '';$key=base64_decode((string)cfg('app_key'),true);if($key===false||strlen($key)<32)$key=hash('sha256',(string)cfg('app_key'),true);
    $key=substr($key,0,SODIUM_CRYPTO_SECRETBOX_KEYBYTES);$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    return 'enc:'.base64_encode($nonce.sodium_crypto_secretbox($plain,$nonce,$key));
}
function decrypt_secret(string $stored): string {
    if($stored===''||!str_starts_with($stored,'enc:'))return $stored;$raw=base64_decode(substr($stored,4),true);if($raw===false)return '';
    $key=base64_decode((string)cfg('app_key'),true);if($key===false||strlen($key)<32)$key=hash('sha256',(string)cfg('app_key'),true);$key=substr($key,0,SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    $nonce=substr($raw,0,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);$cipher=substr($raw,SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);$plain=sodium_crypto_secretbox_open($cipher,$nonce,$key);return $plain===false?'':$plain;
}

function safe_upload(array $file,string $carSlug): array {
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Upload failed with code '.($file['error']??'unknown').'.');
    $tmp=(string)$file['tmp_name'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'';
    $imageTypes=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$videoTypes=['video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'];
    $type='';$ext='';$max=0;
    if(isset($imageTypes[$mime])){$type='image';$ext=$imageTypes[$mime];$max=(int)cfg('max_image_bytes');}
    elseif(isset($videoTypes[$mime])){$type='video';$ext=$videoTypes[$mime];$max=(int)cfg('max_video_bytes');}
    else throw new RuntimeException('Only JPG, PNG, WebP, MP4, WebM and MOV files are allowed.');
    if((int)($file['size']??0)>$max)throw new RuntimeException('The uploaded file is too large.');
    $folder='cars/'.slugify($carSlug);ensure_dir(upload_path($folder));
    $name=date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.'.$ext;$dest=upload_path($folder.'/'.$name);
    if(!move_uploaded_file($tmp,$dest))throw new RuntimeException('Could not move the uploaded file.');
    return ['type'=>$type,'path'=>'uploads/'.$folder.'/'.$name];
}
function delete_uploaded_path(string $path): void {
    if(!str_starts_with($path,'uploads/'))return;$real=realpath(project_root().'/'.$path);$root=realpath(upload_path());
    if($real&&$root&&str_starts_with($real,$root)&&is_file($real))@unlink($real);
}

function activity(string $action,string $detail=''): void {
    $items=data_read('activity',[]);$items[]=['id'=>make_id('log_'),'action'=>$action,'detail'=>$detail,'admin'=>admin_user()['email']??'system','created_at'=>now_iso()];
    if(count($items)>500)$items=array_slice($items,-500);data_write('activity',$items);
}

function template_replace(string $template,array $data): string { foreach($data as $k=>$v)$template=str_replace('{{'.$k.'}}',(string)$v,$template);return $template; }

function send_enquiry_emails(array $enquiry): array {
    $settings=settings_all();
    if(($settings['smtp_enabled']??'0')!=='1')return ['sent'=>false,'message'=>'SMTP is disabled. Enquiry was saved.'];
    $password=decrypt_secret((string)($settings['smtp_password']??''));
    if($password==='')return ['sent'=>false,'message'=>'SMTP password is not configured. Enquiry was saved.'];
    $vehicle=$enquiry['vehicle_name']?:'General enquiry';
    $rawVars=['name'=>$enquiry['name'],'vehicle'=>$vehicle,'email'=>$enquiry['email'],'phone'=>$enquiry['phone'],'start_date'=>($enquiry['start_date']??'')?:'Not supplied','end_date'=>($enquiry['end_date']??'')?:'Not supplied','message'=>$enquiry['message']??'','subject'=>$enquiry['subject']??'','source'=>$enquiry['source']??'website'];
    $subjectVars=array_map(fn($value)=>preg_replace('/[\r\n]+/',' ',(string)$value)??'', $rawVars);
    $htmlVars=array_map(fn($value)=>h((string)$value), $rawVars);
    $htmlVars['message']=nl2br($htmlVars['message']);
    $mailer=new SmtpMailer([
        'host'=>$settings['smtp_host']??'smtp.hostinger.com','port'=>(int)($settings['smtp_port']??587),'encryption'=>$settings['smtp_encryption']??'tls',
        'username'=>$settings['smtp_username']??'','password'=>$password,'from_email'=>$settings['smtp_from_email']??$settings['email'],'from_name'=>$settings['smtp_from_name']??'Eleganza Rentals'
    ]);
    $subject=template_replace((string)($settings['notification_subject']??'New website enquiry: {{vehicle}}'),$subjectVars);
    $body='<h2>'.(($enquiry['source']??'')==='book_now'?'New Eleganza Rentals booking request':'New Eleganza Rentals contact message').'</h2><table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse"><tr><td><strong>Name</strong></td><td>'.h($enquiry['name']).'</td></tr><tr><td><strong>Email</strong></td><td>'.h($enquiry['email']).'</td></tr><tr><td><strong>Phone</strong></td><td>'.h($enquiry['phone']).'</td></tr><tr><td><strong>Request Type</strong></td><td>'.h(($enquiry['source']??'')==='book_now'?'Booking request':'Contact message').'</td></tr><tr><td><strong>Subject</strong></td><td>'.h($enquiry['subject']??'').'</td></tr><tr><td><strong>Preferred Contact</strong></td><td>'.h($enquiry['contact_method']??'Not supplied').'</td></tr><tr><td><strong>Vehicle</strong></td><td>'.h($vehicle).'</td></tr><tr><td><strong>Dates</strong></td><td>'.h($enquiry['start_date']??'').' to '.h($enquiry['end_date']??'').'</td></tr></table><h3>Message</h3><p>'.nl2br(h($enquiry['message']??'')).'</p>';
    try {
        $mailer->send((string)($settings['notification_email']??$settings['email']),$subject,$body,(string)$enquiry['email'],(string)$enquiry['name']);
        if(($settings['auto_reply_enabled']??'1')==='1'){
            $autoSubject=template_replace((string)$settings['auto_reply_subject'],$subjectVars);$autoBody=template_replace((string)$settings['auto_reply_body'],$htmlVars);
            $mailer->send((string)$enquiry['email'],$autoSubject,$autoBody,(string)($settings['email']??''),'Eleganza Rentals');
        }
        return ['sent'=>true,'message'=>'Email notification sent.'];
    }catch(Throwable $e){return ['sent'=>false,'message'=>$e->getMessage()];}
}
