<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$flash = null;
$dbError = null;
$pdo = null;

try {
    $pdo = db();
} catch (Throwable $throwable) {
    $dbError = 'No se pudo conectar a la base de datos para verificar el correo.';
}

$email = trim((string) ($_GET['email'] ?? ''));
$token = trim((string) ($_GET['token'] ?? ''));

if ($pdo instanceof PDO && $email !== '' && $token !== '') {
  if (!column_exists($pdo, 'users', 'email_verification_token_hash') || !column_exists($pdo, 'users', 'email_verified_at')) {
    flash_set('error', 'Tu base de datos no tiene el soporte de verificacion por email. Reimporta schema.sql en una base nueva.');
    redirect('index.php');
  }

    $statement = $pdo->prepare('SELECT id, email_verified_at, email_verification_token_hash FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    if (!$user) {
        flash_set('error', 'No encontramos ninguna cuenta con ese correo.');
        redirect('index.php');
    }

    if (!empty($user['email_verified_at'])) {
        flash_set('success', 'Tu correo ya estaba verificado.');
        redirect('index.php');
    }

    $storedHash = trim((string) ($user['email_verification_token_hash'] ?? ''));
    $tokenHash = hash_verification_token($token);

    if ($storedHash === '' || !hash_equals($storedHash, $tokenHash)) {
        flash_set('error', 'El enlace de verificacion no es valido o ya caducó.');
        redirect('index.php');
    }

    $update = $pdo->prepare(
        'UPDATE users
         SET email_verified_at = CURRENT_TIMESTAMP,
             email_verification_token_hash = NULL,
             email_verification_sent_at = NULL
         WHERE id = :id'
    );
    $update->execute(['id' => (int) $user['id']]);

    flash_set('success', 'Correo verificado correctamente. Ya puedes usar la cuenta con normalidad.');
    redirect('index.php');
}

$flash = flash_get();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verificar correo - VALORANT Training Log</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css?v=<?= h(asset_version('styles.css')) ?>" />
</head>
<body>
  <div class="bg-orb orb-a"></div>
  <div class="bg-orb orb-b"></div>
  <div class="bg-grid"></div>

  <?php if ($flash): ?>
    <?= alert_box((string) $flash['type'], (string) $flash['message']) ?>
  <?php endif; ?>

  <?php if ($dbError): ?>
    <?= alert_box('error', $dbError) ?>
  <?php endif; ?>

  <main class="page-shell page-shell-wide">
    <section class="card overview-card verification-card">
      <p class="eyebrow">Verificacion de correo</p>
      <h2>Comprueba tu bandeja de entrada</h2>
      <p class="hero-copy">Si abriste este enlace desde el correo, tu cuenta se activa al instante. Si llegaste aqui sin token, vuelve al acceso y revisa el correo de confirmacion.</p>
      <div class="actions" style="margin-top: 18px;">
        <a class="primary-btn" href="index.php">Ir al inicio</a>
        <a class="secondary-btn" href="index.php?auth=login">Entrar</a>
      </div>
    </section>
  </main>
</body>
</html>