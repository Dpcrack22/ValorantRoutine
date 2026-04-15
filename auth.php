<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$dbError = null;
$pdo = null;

try {
    $pdo = db();
} catch (Throwable $throwable) {
  $dbError = 'No se pudo conectar a la base de datos. Revisa la configuracion local de MySQL.';
}

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if ($user) {
        redirect('dashboard.php');
    }
}

$flash = flash_get();
$activeAuthTab = $_SESSION['auth_tab'] ?? 'login';
if (!in_array($activeAuthTab, ['login', 'register'], true)) {
    $activeAuthTab = 'login';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Acceso - VALORANT Training Log</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2422487319311981" crossorigin="anonymous"></script>
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

  <header class="site-header card">
    <div class="brand brand-lockup">
      <div class="brand-mark">VT</div>
      <div>
        <p class="eyebrow">VALORANT ROUTINE</p>
        <h1>Access Center</h1>
      </div>
    </div>

    <nav class="site-nav" aria-label="Principal">
      <a href="index.php">Inicio</a>
      <a href="#acceso">Acceso</a>
      <a href="#ventajas">Ventajas</a>
    </nav>

    <button class="site-nav-toggle" type="button" data-site-nav-toggle aria-expanded="false" aria-label="Abrir menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="header-actions">
      <a class="ghost-btn" href="index.php">Volver</a>
      <a class="primary-btn" href="dashboard.php">Ir al panel</a>
    </div>
  </header>

  <main class="auth-shell auth-shell-centered" id="acceso">
    <section class="auth-stage card">
      <div class="auth-stage__header">
        <div>
          <p class="eyebrow">Acceso privado</p>
          <h2>Entra o crea tu cuenta</h2>
        </div>
        <p class="small-muted">Tu rutina y tu diario quedan separados por usuario.</p>
      </div>

      <div class="tab-bar auth-tab-bar" role="tablist" aria-label="Acceso al sistema">
        <button class="tab-btn <?= $activeAuthTab === 'login' ? 'is-active' : '' ?>" type="button" data-auth-tab="login">Iniciar sesion</button>
        <button class="tab-btn <?= $activeAuthTab === 'register' ? 'is-active' : '' ?>" type="button" data-auth-tab="register">Registro</button>
      </div>

      <div class="auth-panels">
        <section class="auth-panel <?= $activeAuthTab === 'login' ? 'is-active' : '' ?>" data-auth-panel="login">
          <div class="auth-panel-copy">
            <p class="panel-label">Entrar</p>
            <h3>Accede a tu rutina privada</h3>
            <p class="small-muted">Usa tu usuario o email para cargar tus sesiones y tu historial.</p>
          </div>

          <form class="auth-form" action="actions.php" method="post">
            <input type="hidden" name="action" value="login" />
            <label>
              <span>Usuario o email</span>
              <input type="text" name="login_identifier" placeholder="Tu usuario o email" required />
            </label>
            <label>
              <span>Contrasena</span>
              <input type="password" name="login_password" placeholder="Tu contrasena" required />
            </label>
            <button class="primary-btn full" type="submit">Entrar</button>
          </form>
        </section>

        <section class="auth-panel <?= $activeAuthTab === 'register' ? 'is-active' : '' ?>" data-auth-panel="register">
          <div class="auth-panel-copy">
            <p class="panel-label">Nuevo usuario</p>
            <h3>Crea tu perfil y empieza a registrar sesiones</h3>
            <p class="small-muted">Tu cuenta quedará lista para guardar las rutinas y partidas de cada día.</p>
          </div>

          <form class="auth-form" action="actions.php" method="post">
            <input type="hidden" name="action" value="register" />
            <label>
              <span>Usuario</span>
              <input type="text" name="register_username" placeholder="Tu nombre de usuario" required />
            </label>
            <label>
              <span>Email</span>
              <input type="email" name="register_email" placeholder="tu@email.com" required />
            </label>
            <label>
              <span>Contrasena</span>
              <input type="password" name="register_password" placeholder="Minimo 8 caracteres" required />
            </label>
            <label>
              <span>Repetir contrasena</span>
              <input type="password" name="register_password_confirm" placeholder="Repite la contrasena" required />
            </label>
            <button class="primary-btn full" type="submit">Crear cuenta</button>
          </form>
        </section>
      </div>

      <div class="auth-stage__footer">
        <a class="ghost-btn" href="index.php">Volver a inicio</a>
        <a class="link-btn" href="dashboard.php">Ir al panel</a>
      </div>
    </section>
  </main>

  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
