<?php

$token = $_GET['token'] ?? '';

if (! hash_equals('az-diag-2090-2026', $token)) {
    http_response_code(404);
    echo 'Not Found';
    exit;
}

header('Content-Type: application/json');

$root = dirname(__DIR__);

$env = static function (string $key): ?string {
    $value = getenv($key);

    if ($value === false && isset($_SERVER[$key])) {
        $value = $_SERVER[$key];
    }

    return $value === false ? null : $value;
};

$masked = static function (?string $value): ?string {
    if ($value === null || $value === '') {
        return $value;
    }

    return substr($value, 0, 2).'***'.substr($value, -2);
};

$database = [
    'connection' => $env('DB_CONNECTION'),
    'host' => $env('DB_HOST'),
    'port' => $env('DB_PORT'),
    'database' => $env('DB_DATABASE'),
    'username' => $masked($env('DB_USERNAME')),
    'password_set' => ($env('DB_PASSWORD') ?? '') !== '',
];

$pdoSqlsrv = [
    'available' => in_array('sqlsrv', PDO::getAvailableDrivers(), true),
    'connected' => false,
    'error' => null,
];

if ($database['connection'] === 'sqlsrv' && $pdoSqlsrv['available'] && $database['password_set']) {
    try {
        $dsn = sprintf(
            'sqlsrv:Server=tcp:%s,%s;Database=%s;Encrypt=yes;TrustServerCertificate=false',
            $database['host'],
            $database['port'] ?: '1433',
            $database['database']
        );

        $pdo = new PDO($dsn, $env('DB_USERNAME'), $env('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $pdo->query('SELECT 1');
        $pdoSqlsrv['connected'] = true;
    } catch (Throwable $e) {
        $pdoSqlsrv['error'] = $e->getMessage();
    }
}

echo json_encode([
    'php_version' => PHP_VERSION,
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? null,
    'laravel_files' => [
        'root' => $root,
        'vendor_autoload' => file_exists($root.'/vendor/autoload.php'),
        'bootstrap_app' => file_exists($root.'/bootstrap/app.php'),
    ],
    'writable' => [
        'storage' => is_writable($root.'/storage'),
        'storage_logs' => is_writable($root.'/storage/logs'),
        'framework_cache' => is_writable($root.'/storage/framework/cache'),
        'framework_sessions' => is_writable($root.'/storage/framework/sessions'),
        'framework_views' => is_writable($root.'/storage/framework/views'),
        'bootstrap_cache' => is_writable($root.'/bootstrap/cache'),
    ],
    'env' => [
        'APP_ENV' => $env('APP_ENV'),
        'APP_DEBUG' => $env('APP_DEBUG'),
        'APP_KEY_set' => ($env('APP_KEY') ?? '') !== '',
        'APP_KEY_prefix' => substr((string) $env('APP_KEY'), 0, 7),
    ],
    'database' => $database,
    'extensions' => [
        'sqlsrv' => extension_loaded('sqlsrv'),
        'pdo_sqlsrv' => extension_loaded('pdo_sqlsrv'),
        'openssl' => extension_loaded('openssl'),
        'mbstring' => extension_loaded('mbstring'),
        'pdo_drivers' => PDO::getAvailableDrivers(),
    ],
    'pdo_sqlsrv' => $pdoSqlsrv,
], JSON_PRETTY_PRINT);
