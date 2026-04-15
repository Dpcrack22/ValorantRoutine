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
$sessions = [];
$routineNames = [];

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if (!$user) {
        redirect('index.php?auth=login');
    }

    $sessions = fetch_sessions($pdo, (int) $user['id']);
    $routineItems = fetch_user_routine_items($pdo, (int) $user['id']);

    foreach ($routineItems as $routineItem) {
        $routineName = trim((string) ($routineItem['routine_name'] ?? 'Rutina principal'));
        if ($routineName === '') {
            $routineName = 'Rutina principal';
        }

        if (!in_array($routineName, $routineNames, true)) {
            $routineNames[] = $routineName;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Historial - VALORANT Training Log</title>
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
        <p class="eyebrow">Historial</p>
        <h1><?= $user ? h($user['username']) : 'Training Log' ?></h1>
      </div>
    </div>

    <nav class="site-nav" aria-label="Principal">
      <a href="dashboard.php">Resumen</a>
      <a href="exercises.php">Ejercicios</a>
      <a href="routine.php">Rutina</a>
      <a href="sessions.php">Sesiones</a>
      <a href="history.php">Historial</a>
    </nav>

    <button class="site-nav-toggle" type="button" data-site-nav-toggle aria-expanded="false" aria-label="Abrir menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <div class="header-actions">
      <a class="ghost-btn" href="sessions.php">Volver a sesiones</a>
      <form action="actions.php" method="post" class="header-logout-form">
        <input type="hidden" name="action" value="logout" />
        <button class="primary-btn" type="submit">Cerrar sesion</button>
      </form>
    </div>
  </header>

  <main class="page-shell page-shell-wide">
    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Edicion de historial</p>
          <h2>Sesiones guardadas</h2>
        </div>
      </div>
      <p class="small-muted">Aqui puedes ajustar benchmark, dia, rutina y notas de sesiones ya guardadas.</p>
    </section>

    <?php if (!$sessions): ?>
      <section class="card overview-card">
        <p class="small-muted">No hay sesiones registradas todavia.</p>
      </section>
    <?php else: ?>
      <?php foreach ($sessions as $session): ?>
        <section id="session-<?= (int) $session['id'] ?>" class="card overview-card">
          <div class="section-title tight">
            <div>
              <p class="eyebrow">Sesion <?= h(date('d/m/Y', strtotime((string) $session['session_date']))) ?></p>
              <h3><?= h(day_label_es((string) $session['day_name'])) ?> · <?= h((string) (($session['session_routine_name'] ?? '') !== '' ? $session['session_routine_name'] : 'Sin rutina')) ?></h3>
            </div>
            <p class="small-muted"><?= h(format_int_es((int) count($session['routines']))) ?> rutinas · <?= h(format_int_es((int) $session['match_count'])) ?> partidas</p>
          </div>

          <form class="entry-form compact-form" action="actions.php" method="post">
            <input type="hidden" name="action" value="save_history_session" />
            <input type="hidden" name="day_id" value="<?= (int) $session['id'] ?>" />

            <div class="form-grid-three">
              <label>
                <span>Fecha (solo lectura)</span>
                <input type="text" value="<?= h((string) $session['session_date']) ?>" readonly />
              </label>

              <label>
                <span>Dia</span>
                <select name="day_name" required>
                  <?php foreach (['Monday' => 'Lunes', 'Tuesday' => 'Martes', 'Wednesday' => 'Miercoles', 'Thursday' => 'Jueves', 'Friday' => 'Viernes', 'Saturday' => 'Sabado', 'Sunday' => 'Domingo'] as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= (string) $session['day_name'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label>
                <span>Rutina</span>
                <select name="session_routine_name">
                  <?php foreach ($routineNames as $routineName): ?>
                    <option value="<?= h((string) $routineName) ?>" <?= (string) ($session['session_routine_name'] ?? '') === (string) $routineName ? 'selected' : '' ?>><?= h((string) $routineName) ?></option>
                  <?php endforeach; ?>
                  <?php if (!$routineNames): ?>
                    <option value="Rutina principal" selected>Rutina principal</option>
                  <?php endif; ?>
                </select>
              </label>

              <label class="full">
                <span>Benchmark</span>
                <input type="text" name="benchmark" value="<?= h((string) $session['benchmark']) ?>" required />
              </label>

              <label class="full">
                <span>Notas</span>
                <textarea name="notes" rows="3" placeholder="Notas de la sesion"><?= h((string) ($session['notes'] ?? '')) ?></textarea>
              </label>
            </div>

            <div class="actions">
              <button class="primary-btn" type="submit">Guardar cambios</button>
            </div>
          </form>

          <div class="actions stack-actions stack-actions--compact">
            <form action="actions.php" method="post" onsubmit="return confirm('Eliminar esta sesion del historial?');">
              <input type="hidden" name="action" value="delete_session" />
              <input type="hidden" name="day_id" value="<?= (int) $session['id'] ?>" />
              <button class="secondary-btn" type="submit">Eliminar sesion</button>
            </form>
          </div>
        </section>
      <?php endforeach; ?>
    <?php endif; ?>
  </main>

  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
