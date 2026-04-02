<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['db_host'],
        $config['db_port'] ?? '3306',
        $config['db_name'],
        $config['db_charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        throw new RuntimeException(
            sprintf(
                'No se pudo conectar a MySQL en %s para la base de datos %s. Revisa DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASS.',
                $config['db_host'],
                $config['db_name']
            ),
            0,
            $exception
        );
    }

    return $pdo;
}
