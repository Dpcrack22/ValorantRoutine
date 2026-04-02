<?php
declare(strict_types=1);

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function asset_version(string $path): string
{
    $absolutePath = __DIR__ . '/' . ltrim($path, '/');
    $modifiedTime = @filemtime($absolutePath);

    return $modifiedTime !== false ? (string) $modifiedTime : (string) time();
}

function redirect(string $path = 'index.php'): never
{
    header('Location: ' . $path);
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function alert_box(string $type, string $message): string
{
    return sprintf(
        '<div class="flash flash-%s" role="alert"><span class="flash__message">%s</span><button class="flash__close" type="button" data-flash-close aria-label="Cerrar aviso">×</button></div>',
        h($type),
        h($message)
    );
}

function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $statement = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => (int) $_SESSION['user_id']]);
    $user = $statement->fetch();

    return $user ?: null;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        flash_set('error', 'Debes iniciar sesion primero.');
        redirect('index.php?auth=login');
    }
}

function parse_int_or_null($value): ?int
{
    $cleaned = preg_replace('/[^0-9-]/', '', (string) $value);
    if ($cleaned === null || trim($cleaned) === '' || $cleaned === '-') {
        return null;
    }

    return (int) $cleaned;
}

function parse_float_or_null($value): ?float
{
    $cleaned = str_replace(',', '.', preg_replace('/[^0-9,\.\-]/', '', (string) $value) ?? '');
    if (trim($cleaned) === '' || $cleaned === '-' || $cleaned === '.') {
        return null;
    }

    if (!is_numeric($cleaned)) {
        return null;
    }

    return (float) $cleaned;
}

function hash_password_sha256(string $password): string
{
    return 'sha256:' . hash('sha256', $password);
}

function verify_password(string $password, string $storedHash): bool
{
    if (substr($storedHash, 0, 7) === 'sha256:') {
        $expected = substr($storedHash, 7);
        return hash_equals($expected, hash('sha256', $password));
    }

    return password_verify($password, $storedHash);
}

function format_int_es(int $value): string
{
    return number_format($value, 0, ',', '.');
}

function format_float_es(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals, ',', '.');
}

function day_label_es(string $dayName): string
{
    $labels = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miercoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sabado',
        'Sunday' => 'Domingo',
    ];

    return $labels[$dayName] ?? $dayName;
}

function routine_section_options(): array
{
    return [
        'KovaaK\'s' => 'KovaaK\'s',
        'Range' => 'Range',
        'Aim Lab' => 'Aim Lab',
        'Warmup' => 'Warmup',
        'Other' => 'Other',
    ];
}

function match_type_options(): array
{
    return [
        'Deathmatch' => 'Deathmatch',
        'Team Deathmatch' => 'Team Deathmatch',
        'Ranked' => 'Ranked',
        'Unrated' => 'Unrated',
        'Premier' => 'Premier',
        'Custom' => 'Custom',
    ];
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $cacheKey = $table . '.' . $column;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $statement = $pdo->prepare(
        'SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table_name
           AND column_name = :column_name
         LIMIT 1'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    $cache[$cacheKey] = (bool) $statement->fetchColumn();

    return $cache[$cacheKey];
}
