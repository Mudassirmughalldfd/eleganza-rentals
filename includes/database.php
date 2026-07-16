<?php
declare(strict_types=1);

function db_configured(): bool {
    $database = cfg('database');
    return is_array($database)
        && trim((string) ($database['name'] ?? '')) !== ''
        && trim((string) ($database['username'] ?? '')) !== '';
}

function db(): PDO {
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $database = cfg('database');
    if (!is_array($database) || !db_configured()) {
        throw new RuntimeException('The MySQL database is not configured. Open install.php to complete setup.');
    }

    $host = (string) ($database['host'] ?? 'localhost');
    $port = (int) ($database['port'] ?? 3306);
    $name = (string) ($database['name'] ?? '');
    $charset = (string) ($database['charset'] ?? 'utf8mb4');
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

    $connection = new PDO($dsn, (string) ($database['username'] ?? ''), (string) ($database['password'] ?? ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}",
    ]);

    return $connection;
}

function db_table_exists(string $table): bool {
    try {
        $statement = db()->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $statement->execute([$table]);
        return (int) $statement->fetchColumn() > 0;
    } catch (Throwable) {
        return false;
    }
}

function db_datetime(mixed $value, ?string $fallback = null): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    try {
        return (new DateTimeImmutable($value))->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return $fallback;
    }
}

function db_bool(mixed $value): int {
    return !empty($value) ? 1 : 0;
}

function db_name(): string {
    $database = cfg('database');
    return is_array($database) ? (string) ($database['name'] ?? '') : '';
}
