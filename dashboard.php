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
    $dbError = 'No se pudo conectar a la base de datos. Revisa la configuracion de InfinityFree.';
}

$flash = flash_get();
$user = null;
$sessions = [];
$stats = [
    'session_count' => 0,
    'average_kda' => 0.0,
  'average_kast' => 0.0,
    'best_points' => 0,
];
$chartData = [];
$routineChartData = [];
$kdaChartData = [];
$resultsChartData = [];
$dashboardCatalog = [
    'recent_sessions' => [],
    'benchmark_options' => [],
    'routine_templates' => [],
    'map_options' => [],
    'last_session' => null,
];

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if (!$user) {
        redirect('index.php?auth=login');
    }

    $sessions = fetch_sessions($pdo, (int) $user['id']);
    $stats = calculate_stats($sessions);
    $chartData = build_chart_data($sessions);
    $dashboardCatalog = build_dashboard_catalog($sessions);
    foreach (array_reverse($sessions) as $session) {
      $routinePoints = 0;

      foreach ($session['routines'] as $routine) {
        $routinePoints += (int) ($routine['score_points'] ?? 0);
      }

      $routineChartData[] = [
        'label' => date('j/n', strtotime((string) $session['session_date'])),
        'points' => $routinePoints,
      ];

      $kdaChartData[] = [
        'label' => date('j/n', strtotime((string) $session['session_date'])),
        'points' => $session['avg_kda'] !== null ? (float) $session['avg_kda'] : 0,
      ];

      $resultsChartData[] = [
        'label' => date('j/n', strtotime((string) $session['session_date'])),
        'points' => (int) ($session['win_count'] ?? 0),
      ];
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Resumen - VALORANT Training Log</title>
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

  <header class="site-header card">
    <div class="brand brand-lockup">
      <div class="brand-mark">VT</div>
      <div>
        <p class="eyebrow">Resumen general</p>
        <h1><?= $user ? h($user['username']) : 'Training Log' ?></h1>
      </div>
    </div>

    <nav class="site-nav" aria-label="Principal">
      <a href="dashboard.php">Resumen</a>
      <a href="exercises.php">Ejercicios</a>
      <a href="routine.php">Rutina</a>
      <a href="sessions.php">Sesiones</a>
    </nav>

    <div class="header-actions">
      <a class="ghost-btn" href="sessions.php">Nueva sesion</a>
      <form action="actions.php" method="post" class="header-logout-form">
        <input type="hidden" name="action" value="logout" />
        <button class="primary-btn" type="submit">Cerrar sesion</button>
      </form>
    </div>
  </header>

  <?php if ($dbError): ?>
    <main class="page-shell">
      <section class="card overview-card">
        <p class="eyebrow">Error de base de datos</p>
        <h2>La base de datos no esta respondiendo</h2>
        <p class="hero-copy">Sin la conexión MySQL la app no puede cargar. Revisa que la base exista en InfinityFree y que los datos de conexión sean correctos.</p>
        <div class="actions" style="margin-top: 18px;">
          <a class="primary-btn" href="index.php?auth=login">Volver al acceso</a>
          <a class="secondary-btn" href="index.php">Ver portada</a>
        </div>
      </section>
    </main>
  <?php else: ?>
    <main class="page-shell dashboard-overview">
      <section class="card overview-hero">
        <div class="hero-copy-block">
          <p class="eyebrow">Tu centro de control</p>
          <h2>Todo ordenado en tres pasos: ejercicios, rutina y sesiones.</h2>
          <p class="hero-copy">Este resumen solo enseña el estado general. La gestion real vive en las pantallas especificas, para que todo sea mas claro y rapido de usar.</p>
          <div class="hero-actions">
            <a class="primary-btn" href="sessions.php">Nueva sesion</a>
            <a class="secondary-btn" href="routine.php">Editar rutina</a>
          </div>
        </div>

        <div class="overview-stats">
          <article class="stat">
            <span class="stat-value"><?= h((string) $stats['session_count']) ?></span>
            <span class="stat-label">Sesiones</span>
          </article>
          <article class="stat">
            <span class="stat-value"><?= h(format_float_es($stats['average_kda'])) ?></span>
            <span class="stat-label">KDA medio</span>
          </article>
          <article class="stat">
            <span class="stat-value"><?= h(format_float_es($stats['average_kast'])) ?></span>
            <span class="stat-label">KAST medio</span>
          </article>
          <article class="stat">
            <span class="stat-value"><?= h(format_int_es($stats['best_points'])) ?></span>
            <span class="stat-label">Mejor dia</span>
          </article>
        </div>
      </section>

      <section class="feature-grid">
        <article class="card feature-card overview-link-card">
          <p class="panel-label">Ejercicios</p>
          <h3>Catálogo de ejercicios</h3>
          <p class="small-muted">Crea y organiza ejercicios por plataforma. Aqui no entrenas nada, solo preparas tu base de trabajo.</p>
          <a class="link-btn" href="exercises.php">Abrir ejercicios</a>
        </article>

        <article class="card feature-card overview-link-card">
          <p class="panel-label">Rutina</p>
          <h3>Tu lista personal</h3>
          <p class="small-muted">Añade los ejercicios que vas a repetir y se usarán luego al registrar sesiones.</p>
          <a class="link-btn" href="routine.php">Abrir rutina</a>
        </article>

        <article class="card feature-card overview-link-card">
          <p class="panel-label">Sesiones</p>
          <h3>Registro diario</h3>
          <p class="small-muted">Aquí guardas puntos, minutaje y partidas usando directamente tu rutina guardada.</p>
          <a class="link-btn" href="sessions.php">Abrir sesiones</a>
        </article>
      </section>

      <section class="card chart-card">
        <div class="section-title">
          <div>
            <p class="eyebrow">Progreso</p>
            <h3>Grafico de puntos por dia</h3>
          </div>
          <a class="secondary-btn" href="sessions.php">Ver registro completo</a>
        </div>
        <div class="chart-wrap">
          <canvas id="progressChart" width="1200" height="420"></canvas>
        </div>
      </section>

      <section class="card overview-card chart-split-grid">
        <article class="chart-card chart-card-compact">
          <div class="section-title tight">
            <div>
              <p class="eyebrow">Rutina</p>
              <h3>Puntos por dia</h3>
            </div>
          </div>
          <canvas id="routineChart" width="1200" height="360"></canvas>
        </article>
        <article class="chart-card chart-card-compact">
          <div class="section-title tight">
            <div>
              <p class="eyebrow">Partidas</p>
              <h3>KDA medio por dia</h3>
            </div>
          </div>
          <canvas id="kdaChart" width="1200" height="360"></canvas>
        </article>
      </section>

      <section class="card overview-card">
        <div class="section-title">
          <div>
            <p class="eyebrow">Resultados</p>
            <h3>Victorias por dia</h3>
          </div>
        </div>
        <div class="chart-wrap">
          <canvas id="resultsChart" width="1200" height="320"></canvas>
        </div>
      </section>

      <section class="card overview-card">
        <div class="section-title">
          <div>
            <p class="eyebrow">Actividad reciente</p>
            <h3>Ultimas sesiones</h3>
          </div>
          <a class="secondary-btn" href="sessions.php">Abrir sesiones</a>
        </div>

        <div class="recent-grid">
          <?php foreach (array_slice($dashboardCatalog['recent_sessions'], 0, 6) as $recentSession): ?>
            <a class="recent-card" href="sessions.php#session-<?= (int) $recentSession['id'] ?>">
              <strong><?= h((string) $recentSession['date_label']) ?></strong>
              <span><?= h((string) $recentSession['day_label']) ?></span>
              <small><?= h((string) $recentSession['benchmark']) ?></small>
              <em><?= h(format_int_es((int) $recentSession['total_points'])) ?> pts</em>
            </a>
          <?php endforeach; ?>
          <?php if (!$dashboardCatalog['recent_sessions']): ?>
            <p class="small-muted">Aun no hay sesiones registradas.</p>
          <?php endif; ?>
        </div>
      </section>

      <section class="card overview-card">
        <div class="section-title">
          <div>
            <p class="eyebrow">Guia rapida</p>
            <h3>Como usar la app</h3>
          </div>
        </div>

        <div class="guide-grid">
          <article class="guide-card">
            <span>01</span>
            <h4>Crea ejercicios</h4>
            <p>Ve a ejercicios y mete tu catalogo por plataforma.</p>
          </article>
          <article class="guide-card">
            <span>02</span>
            <h4>Arma tu rutina</h4>
            <p>Selecciona lo que quieres repetir en cada dia.</p>
          </article>
          <article class="guide-card">
            <span>03</span>
            <h4>Registra sesiones</h4>
            <p>Cuando tengas 3 ejercicios en rutina, saldran ahi para rellenar puntos y minutos.</p>
          </article>
        </div>
      </section>
    </main>

    <script>
      window.__CHART_DATA__ = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      window.__DASHBOARD_CHARTS__ = <?= json_encode([
        'routine' => $routineChartData,
        'kda' => $kdaChartData,
        'results' => $resultsChartData,
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      window.__DASHBOARD_DATA__ = <?= json_encode(array_merge($dashboardCatalog, [
        'routine_items' => [],
      ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
  <?php endif; ?>
</body>
</html>
