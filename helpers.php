<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
        redirect('index.php');
    }
}

function parse_int_or_null(mixed $value): ?int
{
    $cleaned = preg_replace('/[^0-9-]/', '', (string) $value);
    if ($cleaned === null || trim($cleaned) === '' || $cleaned === '-') {
        return null;
    }

    return (int) $cleaned;
}

function parse_float_or_null(mixed $value): ?float
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
