<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$pdo = db();
$tables = ['admins','site_settings','cars','car_prices','vehicle_media','availability_blocks','enquiries','activity_logs','app_meta'];
$filename = 'eleganza-database-' . date('Y-m-d-His') . '.sql';
header('Content-Type: application/sql; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

echo "-- Eleganza Rentals MySQL backup\n";
echo "-- Generated: " . date(DATE_ATOM) . "\n";
echo "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    if ($create) {
        echo "DROP TABLE IF EXISTS `{$table}`;\n" . $create[1] . ";\n\n";
    }
}

foreach ($tables as $table) {
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) continue;
    foreach ($rows as $row) {
        $columns = array_map(static fn(string $column): string => '`' . str_replace('`', '``', $column) . '`', array_keys($row));
        $values = [];
        foreach ($row as $value) $values[] = $value === null ? 'NULL' : $pdo->quote((string) $value);
        echo "INSERT INTO `{$table}` (" . implode(',', $columns) . ") VALUES (" . implode(',', $values) . ");\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
activity('Database exported', $filename);
exit;
