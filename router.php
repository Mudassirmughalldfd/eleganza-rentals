<?php
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (preg_match('#/(?:\.htaccess|\.user\.ini|\.env)(?:$|/)#i', $uri)) { http_response_code(404); echo 'Not found'; exit; }
$blocked = ['/storage', '/includes', '/config.php', '/README-FIRST.txt', '/HOSTINGER-DEPLOYMENT.txt', '/router.php'];
foreach ($blocked as $prefix) {
    if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
}
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) return false;
if ($uri !== '/' && is_dir($file) && is_file($file . '/index.php')) {
    require $file . '/index.php';
    return true;
}
if ($uri === '/') { require __DIR__ . '/index.php'; return true; }
http_response_code(404);
require __DIR__ . '/index.php';
