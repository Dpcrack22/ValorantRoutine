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
$exerciseCatalog = [];
$routineItems = [];
$routineNames = [];
$routineItemsByName = [];

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if (!$user) {
        redirect('index.php?auth=login');
    }

    $exerciseCatalog = fetch_exercise_catalog($pdo);
    $routineItems = fetch_user_routine_items($pdo, (int) $user['id']);

    foreach ($routineItems as $routineItem) {
      $routineName = trim((string) ($routineItem['routine_name'] ?? 'Rutina principal'));
      if ($routineName === '') {
        $routineName = 'Rutina principal';
      }

      if (!isset($routineItemsByName[$routineName])) {
        $routineItemsByName[$routineName] = [];
        $routineNames[] = $routineName;
      }

      $routineItemsByName[$routineName][] = $routineItem;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Rutina - VALORANT Training Log</title>
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

  <?php if ($user && empty($user['email_verified_at'])): ?>
    <?= alert_box('info', 'Hemos enviado un correo de verificacion. Confirma tu email para mantener la cuenta activada.') ?>
  <?php endif; ?>

  <?php if ($dbError): ?>
    <?= alert_box('error', $dbError) ?>
  <?php endif; ?>

  <header class="site-header card">
    <div class="brand brand-lockup">
      <div class="brand-mark">VT</div>
      <div>
        <p class="eyebrow">Mi rutina</p>
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
      <a class="ghost-btn" href="dashboard.php">Volver al panel</a>
      <form action="actions.php" method="post" class="header-logout-form">
        <input type="hidden" name="action" value="logout" />
        <button class="primary-btn" type="submit">Cerrar sesion</button>
      </form>
    </div>
  </header>

  <main class="page-shell page-shell-two">
    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Rutina personal</p>
          <h2>Añade ejercicios del catalogo a tu rutina</h2>
        </div>
        <p class="small-muted">Estos ejercicios luego apareceran al registrar sesiones.</p>
      </div>

      <form class="entry-form compact-form" action="actions.php" method="post">
        <input type="hidden" name="action" value="add_routine_item" />
        <div class="form-grid-three">
          <label class="full">
            <span>Nombre de rutina</span>
            <input list="routineNameOptions" type="text" name="routine_name" placeholder="Ej: Rutina ranked, Warmup rapido" required />
          </label>
          <label class="full">
            <span>Ejercicio catalogo</span>
            <select name="exercise_id" required>
              <option value="">Elige un ejercicio</option>
              <?php foreach ($exerciseCatalog as $exercise): ?>
                <option value="<?= (int) $exercise['id'] ?>"><?= h((string) $exercise['platform']) ?> · <?= h((string) $exercise['exercise_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            <span>Min objetivo</span>
            <input type="text" name="target_minutes" placeholder="12.5" />
          </label>
          <label>
            <span>Accuracy objetivo</span>
            <input type="text" name="target_accuracy" placeholder="85.0" />
          </label>
          <label>
            <span>Repeticiones</span>
            <input type="number" name="routine_repetitions" min="1" value="1" />
          </label>
          <label class="full">
            <span>Notas</span>
            <input type="text" name="routine_item_notes" placeholder="Por que entra en tu rutina" />
          </label>
        </div>
        <datalist id="routineNameOptions">
          <?php foreach ($routineNames as $routineName): ?>
            <option value="<?= h((string) $routineName) ?>"></option>
          <?php endforeach; ?>
        </datalist>
        <div class="actions">
          <button class="primary-btn" type="submit">Añadir a mi rutina</button>
          <a class="secondary-btn" href="sessions.php">Ir a sesiones</a>
        </div>
      </form>
    </section>

    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Rutina actual</p>
          <h3>Rutinas guardadas para usar en sesiones</h3>
        </div>
        <p class="small-muted"><?= h((string) count($routineItemsByName)) ?> rutinas · <?= h((string) count($routineItems)) ?> ejercicios</p>
      </div>

      <?php foreach ($routineItemsByName as $routineName => $items): ?>
        <section class="sub-section">
          <div class="section-title tight">
            <div>
              <p class="panel-label"><?= h((string) $routineName) ?></p>
              <h4><?= h(format_int_es((int) count($items))) ?> ejercicios en esta rutina</h4>
            </div>
          </div>
          <div class="routine-grid">
            <?php foreach ($items as $routineItem): ?>
              <article class="routine-card">
                <div>
                  <p class="panel-label"><?= h((string) $routineItem['platform']) ?></p>
                  <h4><?= h((string) $routineItem['exercise_name']) ?></h4>
                  <p class="small-muted"><?= h(format_int_es((int) $routineItem['repetitions'])) ?> repeticiones</p>
                  <p class="small-muted"><?= h($routineItem['target_minutes'] !== null ? format_float_es((float) $routineItem['target_minutes'], 1) . ' min objetivo' : 'Sin min objetivo') ?></p>
                  <p class="small-muted"><?= h($routineItem['target_accuracy'] !== null ? format_float_es((float) $routineItem['target_accuracy'], 1) . '% accuracy objetivo' : 'Sin accuracy objetivo') ?></p>
                  <p class="small-muted"><?= h((string) ($routineItem['notes'] ?: 'Sin notas')) ?></p>
                </div>
                <form action="actions.php" method="post" onsubmit="return confirm('Eliminar este ejercicio de tu rutina?');">
                  <input type="hidden" name="action" value="delete_routine_item" />
                  <input type="hidden" name="routine_item_id" value="<?= (int) $routineItem['id'] ?>" />
                  <button class="row-action" type="submit">Quitar</button>
                </form>
              </article>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>

      <?php if (!$routineItems): ?>
        <p class="small-muted">Aun no tienes rutina propia. Añade ejercicios del catalogo para usarlos en sesiones.</p>
      <?php endif; ?>
    </section>
  </main>

  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
