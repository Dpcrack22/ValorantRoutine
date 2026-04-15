<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/helpers.php';

$flash = flash_get();
$loggedIn = !empty($_SESSION['user_id']);
$activeAuthTab = $_GET['auth'] ?? 'login';
if (!in_array($activeAuthTab, ['login', 'register'], true)) {
  $activeAuthTab = 'login';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VALORANT Training Log</title>
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

  <header class="site-header card">
    <div class="brand brand-lockup">
      <div class="brand-mark">VT</div>
      <div>
        <p class="eyebrow">VALORANT ROUTINE</p>
        <h1>Training Log</h1>
      </div>
    </div>

    <nav class="site-nav" aria-label="Principal">
      <a href="#inicio">Inicio</a>
      <a href="#como-funciona">Como funciona</a>
      <a href="#acceso">Acceso</a>
    </nav>

    <button class="site-nav-toggle" type="button" data-site-nav-toggle aria-expanded="false" aria-label="Abrir menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="header-actions">
      <?php if ($loggedIn): ?>
        <a class="ghost-btn" href="dashboard.php">Ir al panel</a>
        <form action="actions.php" method="post" class="header-logout-form">
          <input type="hidden" name="action" value="logout" />
          <button class="primary-btn" type="submit">Cerrar sesion</button>
        </form>
      <?php else: ?>
        <button class="ghost-btn" type="button" data-auth-open="login">Iniciar sesion</button>
        <button class="primary-btn" type="button" data-auth-open="register">Crear cuenta</button>
      <?php endif; ?>
    </div>
  </header>

  <main class="landing-shell">
    <section class="hero card" id="inicio">
      <div class="hero-copy-block">
        <p class="eyebrow">Sistema de entrenamiento</p>
        <h2>Organiza tu rutina, guarda cada dia y mira si de verdad mejoras.</h2>
        <p class="hero-copy">Una plataforma pensada para jugadores de Valorant que quieren registrar KovaaK's, Aim Lab, Deathmatch y partidas reales con un flujo claro, rápido y profesional.</p>

        <div class="hero-actions">
          <button class="primary-btn" type="button" data-auth-open="register">Empezar ahora</button>
          <a class="secondary-btn" href="#como-funciona">Ver funcionamiento</a>
        </div>

        <div class="hero-pills">
          <span class="chip">Rutina diaria</span>
          <span class="chip">Grafico por dia</span>
          <span class="chip">Base de datos MySQL</span>
        </div>
      </div>

      <div class="hero-visual">
        <article class="preview-card">
          <div class="preview-card__header">
            <div class="window-dots" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
            </div>
            <span>Dashboard preview</span>
          </div>

          <div class="preview-metrics">
            <div class="preview-metric">
              <span class="preview-metric__value">01</span>
              <span class="preview-metric__label">usuario</span>
            </div>
            <div class="preview-metric">
              <span class="preview-metric__value">04</span>
              <span class="preview-metric__label">bloques diarios</span>
            </div>
            <div class="preview-metric">
              <span class="preview-metric__value">24/7</span>
              <span class="preview-metric__label">seguimiento</span>
            </div>
          </div>

          <ul class="preview-list">
            <li>Rutinas de aim con puntos, tiempo y accuracy.</li>
            <li>Registro de Deathmatch y partidas de Valorant.</li>
            <li>Historial diario con grafico de progreso.</li>
          </ul>
        </article>
      </div>
    </section>

    <section class="feature-grid" id="como-funciona">
      <article class="card feature-card">
        <p class="panel-label">Paso 1</p>
        <h3>Creas tu cuenta</h3>
        <p class="small-muted">Cada jugador tiene su perfil y su progreso separado. Nadie ve los datos de otro usuario.</p>
      </article>

      <article class="card feature-card">
        <p class="panel-label">Paso 2</p>
        <h3>Apuntas tu rutina</h3>
        <p class="small-muted">Añades ejercicios de KovaaK's, Aim Lab, Deathmatch y partidas con notas, KDA y puntuación.</p>
      </article>

      <article class="card feature-card">
        <p class="panel-label">Paso 3</p>
        <h3>Ves el progreso</h3>
        <p class="small-muted">La app guarda todo al momento y genera un gráfico diario para revisar evolución real.</p>
      </article>
    </section>

  </main>

  <div class="auth-modal" data-auth-modal hidden>
    <div class="auth-modal__backdrop" data-auth-close></div>
    <section class="auth-stage card" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">
      <button class="auth-modal__close" type="button" data-auth-close aria-label="Cerrar">×</button>

      <div class="auth-stage__header">
        <div>
          <p class="eyebrow">Acceso privado</p>
          <h2 id="authModalTitle">Entra o crea tu cuenta</h2>
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
        <a class="ghost-btn" href="index.php">Cerrar y volver</a>
        <a class="link-btn" href="dashboard.php">Ir al panel</a>
      </div>
    </section>
  </div>

  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
