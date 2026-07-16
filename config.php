<?php
declare(strict_types=1);

/**
 * Safe configuration committed to GitHub.
 * Real database credentials are stored in config.local.php, which is ignored by Git.
 */
$defaults = [
    'app_name' => 'Eleganza Rentals',
    'timezone' => 'Europe/London',
    'app_key' => '',
    'upload_dir' => __DIR__ . '/uploads',
    'storage_dir' => __DIR__ . '/storage',
    'max_image_bytes' => 10 * 1024 * 1024,
    'max_video_bytes' => 80 * 1024 * 1024,
    'session_name' => 'eleganza_session',
    'debug' => false,
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => '',
        'username' => '',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];

$local = [];
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $loaded = require $localFile;
    if (is_array($loaded)) {
        $local = $loaded;
    }
}

$config = array_replace_recursive($defaults, $local);

// Environment variables are useful on GitHub Actions, containers and some hosting platforms.
$envMap = [
    'DB_HOST' => ['database', 'host'],
    'DB_PORT' => ['database', 'port'],
    'DB_NAME' => ['database', 'name'],
    'DB_USER' => ['database', 'username'],
    'DB_PASSWORD' => ['database', 'password'],
    'APP_KEY' => ['app_key'],
];
foreach ($envMap as $envName => $path) {
    $value = getenv($envName);
    if ($value === false || $value === '') {
        continue;
    }
    if (count($path) === 1) {
        $config[$path[0]] = $value;
    } else {
        $config[$path[0]][$path[1]] = $path[1] === 'port' ? (int) $value : $value;
    }
}

return $config;
