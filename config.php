<?php
declare(strict_types=1);

$env = static function (string $key, string $default): string {
    $value = getenv($key);

    if ($value === false || $value === '') {
        return $default;
    }

    return $value;
};

return [
    'db_host' => $env('DB_HOST', 'sql107.infinityfree.com'),
    'db_port' => $env('DB_PORT', '3306'),
    'db_name' => $env('DB_NAME', 'if0_41557585_rutinas'),
    'db_user' => $env('DB_USER', 'if0_41557585'),
    'db_pass' => $env('DB_PASS', 'Y64hDlGZiMEh'),
    'db_charset' => 'utf8mb4',
];
