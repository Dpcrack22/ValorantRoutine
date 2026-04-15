<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/app_context.php';

$dbError = null;
$pdo = null;

try {
    $pdo = db();
} catch (Throwable $throwable) {
  $dbError = 'No se pudo conectar a la base de datos. Revisa la configuracion local de MySQL.';
}

$flash = flash_get();
$user = null;
$exerciseCatalog = [];

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if (!$user) {
        redirect('index.php?auth=login');
    }

    $exerciseCatalog = fetch_exercise_catalog($pdo);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ejercicios - VALORANT Training Log</title>
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
        <p class="eyebrow">Catalogo de ejercicios</p>
        <h1><?= $user ? h($user['username']) : 'Training Log' ?></h1>
      </div>
    </div>

    <nav class="site-nav" aria-label="Principal">
      <a href="dashboard.php">Resumen</a>
      <a href="exercises.php">Ejercicios</a>
      <a href="routine.php">Rutina</a>
      <a href="sessions.php">Sesiones</a>
    </nav>

    <button class="site-nav-toggle" type="button" data-site-nav-toggle aria-expanded="false" aria-label="Abrir menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="header-actions">
      <a class="ghost-btn" href="dashboard.php">Volver al panel</a>
      <form action="actions.php" method="post" class="header-logout-form">
        <input type="hidden" name="action" value="logout" />
        <button class="primary-btn" type="submit">Cerrar sesion</button>
      </form>
    </div>
  </header>

  <main class="page-shell">
    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Nuevo ejercicio</p>
          <h2>Añade ejercicios al catalogo</h2>
        </div>
        <p class="small-muted">Aqui defines ejercicios reutilizables por plataforma.</p>
      </div>

      <form class="entry-form compact-form" action="actions.php" method="post">
        <input type="hidden" name="action" value="add_exercise" />
        <div class="form-grid-three">
          <label>
            <span>Plataforma</span>
            <select name="exercise_platform" required>
              <option value="KovaaK's">KovaaK's</option>
              <option value="Aim Lab">Aim Lab</option>
              <option value="Range">Range</option>
              <option value="Warmup">Warmup</option>
              <option value="Valorant">Valorant</option>
              <option value="Other">Other</option>
            </select>
          </label>
          <label class="full">
            <span>Ejercicio</span>
            <input type="text" name="exercise_name" placeholder="Ej: Multishot, 1w6targets small" required />
          </label>
          <label class="full">
            <span>Notas</span>
            <input type="text" name="exercise_notes" placeholder="Descripcion corta" />
          </label>
        </div>
        <div class="actions">
          <button class="primary-btn" type="submit">Guardar ejercicio</button>
          <a class="secondary-btn" href="routine.php">Ir a mi rutina</a>
        </div>
      </form>
    </section>

    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Catalogo actual</p>
          <h3>Ejercicios guardados</h3>
        </div>
        <p class="small-muted"><?= h((string) count($exerciseCatalog)) ?> ejercicios</p>
      </div>

      <div class="catalog-grid">
        <?php foreach ($exerciseCatalog as $exercise): ?>
          <article class="catalog-card">
            <p class="panel-label"><?= h((string) $exercise['platform']) ?></p>
            <h4><?= h((string) $exercise['exercise_name']) ?></h4>
            <p class="small-muted"><?= h((string) ($exercise['notes'] ?: 'Sin notas')) ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (!$exerciseCatalog): ?>
          <p class="small-muted">Aun no hay ejercicios en el catalogo.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
