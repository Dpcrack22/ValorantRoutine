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
    'best_points' => 0,
];
$chartData = [];
$routineChartData = [];
$matchChartData = [];
$routineItems = [];
$dashboardCatalog = [
    'recent_sessions' => [],
    'benchmark_options' => [],
    'routine_templates' => [],
    'map_options' => [],
    'last_session' => null,
];
$sessionSaved = isset($_GET['saved']);
$todayDate = date('Y-m-d');
$todayDayName = date('l');

if ($pdo instanceof PDO) {
    $user = current_user($pdo);
    if (!$user) {
        redirect('index.php?auth=login');
    }

    $sessions = fetch_sessions($pdo, (int) $user['id']);
    $stats = calculate_stats($sessions);
    $chartData = build_chart_data($sessions);
    foreach (array_reverse($sessions) as $session) {
      $routineTotal = 0;
      foreach ($session['routines'] as $routine) {
        $routineTotal += (int) ($routine['score_points'] ?? 0);
      }

      $routineChartData[] = [
        'label' => date('j/n', strtotime((string) $session['session_date'])),
        'value' => $routineTotal,
      ];

      $matchChartData[] = [
        'label' => date('j/n', strtotime((string) $session['session_date'])),
        'value' => $session['avg_kda'] !== null ? (float) $session['avg_kda'] : 0,
      ];
    }
    $routineItems = fetch_user_routine_items($pdo, (int) $user['id']);
    $dashboardCatalog = build_dashboard_catalog($sessions);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sesiones - VALORANT Training Log</title>
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
        <p class="eyebrow">Registro de sesiones</p>
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

  <main class="page-shell page-shell-wide">
    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Nueva sesion</p>
          <h2>Registra tu rutina de hoy</h2>
        </div>
        <div class="form-toolbar">
          <button class="secondary-btn" type="button" data-fill-last-session>Rellenar ultima sesion</button>
          <button id="fillToday" class="secondary-btn" type="button">Fecha de hoy</button>
        </div>
      </div>

      <form class="entry-form compact-form" action="actions.php" method="post" data-draft-form="sessions">
        <input type="hidden" name="action" value="save_session" />

        <div class="form-grid-three">
          <label>
            <span>Fecha</span>
            <input id="sessionDate" type="date" name="session_date" value="<?= h($todayDate) ?>" required />
          </label>

          <label>
            <span>Dia</span>
            <select name="day_name" required>
              <option value="Monday" <?= $todayDayName === 'Monday' ? 'selected' : '' ?>>Lunes</option>
              <option value="Tuesday" <?= $todayDayName === 'Tuesday' ? 'selected' : '' ?>>Martes</option>
              <option value="Wednesday" <?= $todayDayName === 'Wednesday' ? 'selected' : '' ?>>Miercoles</option>
              <option value="Thursday" <?= $todayDayName === 'Thursday' ? 'selected' : '' ?>>Jueves</option>
              <option value="Friday" <?= $todayDayName === 'Friday' ? 'selected' : '' ?>>Viernes</option>
              <option value="Saturday" <?= $todayDayName === 'Saturday' ? 'selected' : '' ?>>Sabado</option>
              <option value="Sunday" <?= $todayDayName === 'Sunday' ? 'selected' : '' ?>>Domingo</option>
            </select>
          </label>

          <label class="full">
            <span>Benchmark / rutina base</span>
            <input list="benchmarkOptions" type="text" name="benchmark" placeholder="Ej: KovaaK's smooth tracking" required />
          </label>
        </div>

        <p class="small-muted">La fecha y el dia se rellenan solos. Escribe un nombre claro para tu benchmark o rutina base.</p>

        <datalist id="benchmarkOptions">
          <?php foreach ($dashboardCatalog['benchmark_options'] as $benchmarkOption): ?>
            <option value="<?= h((string) $benchmarkOption) ?>"></option>
          <?php endforeach; ?>
        </datalist>

        <?php if ($routineItems): ?>
          <section class="sub-section">
            <div class="section-title tight">
              <div>
                <p class="eyebrow">Rutina de hoy</p>
                <h3>Ejercicios guardados y repeticiones</h3>
              </div>
              <p class="small-muted"><?= h((string) count($routineItems)) ?> ejercicios</p>
            </div>
            <div class="routine-summary-grid">
              <?php foreach ($routineItems as $routineItem): ?>
                <article class="routine-summary-card">
                  <p class="panel-label"><?= h((string) $routineItem['platform']) ?></p>
                  <h4><?= h((string) $routineItem['exercise_name']) ?></h4>
                  <p class="small-muted"><?= h(format_int_es((int) $routineItem['repetitions'])) ?> repeticiones</p>
                  <p class="small-muted"><?= h($routineItem['target_minutes'] !== null ? format_float_es((float) $routineItem['target_minutes'], 1) . ' min' : 'Sin min objetivo') ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          </section>
        <?php else: ?>
          <section class="sub-section">
            <p class="small-muted">No tienes rutina creada. Ve a [Rutina](routine.php) para añadir ejercicios y que aparezcan aqui.</p>
          </section>
        <?php endif; ?>

        <div class="full sub-section">
          <div class="section-title tight">
            <div>
              <p class="eyebrow">Rutinas</p>
              <h3>Registra los ejercicios ya creados en tu rutina</h3>
            </div>
            <button id="addRoutine" class="secondary-btn" type="button">Añadir serie</button>
          </div>
          <div id="routineRows" class="rows-grid">
            <?php $routineRowsToRender = $routineItems ?: [[]]; ?>
            <?php foreach ($routineRowsToRender as $routineRowItem): ?>
              <article class="entry-row routine-row">
                <div class="row-line row-line-three">
                  <label class="inline-field">
                    <span>Ejercicio de rutina</span>
                    <select name="routine_user_item_id[]">
                      <option value="">Elige un ejercicio</option>
                      <?php foreach ($routineItems as $routineItem): ?>
                        <option value="<?= (int) $routineItem['id'] ?>" <?= !empty($routineRowItem['id']) && (int) $routineRowItem['id'] === (int) $routineItem['id'] ? 'selected' : '' ?>>
                          <?= h((string) $routineItem['platform']) ?> · <?= h((string) $routineItem['exercise_name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <label class="inline-field small">
                    <span>Puntos</span>
                    <input type="text" name="routine_points[]" placeholder="12340" />
                  </label>
                  <label class="inline-field small">
                    <span>Accuracy %</span>
                    <input type="text" name="routine_accuracy[]" placeholder="86.4" />
                  </label>
                  <button class="remove-row" type="button" title="Eliminar rutina">-</button>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
          <div class="actions">
            <button class="primary-btn" type="submit" name="save_scope" value="routine">Guardar rutina</button>
          </div>
        </div>

        <div class="full sub-section">
          <div class="section-title tight">
            <div>
              <p class="eyebrow">Partidas</p>
              <h3>DM/TDM: k, d, a, KDA, headshot y resultado. Ranked: añade rondas y ACS.</h3>
            </div>
            <button id="addMatch" class="secondary-btn" type="button">Añadir partida</button>
          </div>
          <div id="matchRows" class="rows-grid">
            <?php for ($i = 0; $i < 2; $i++): ?>
              <article class="entry-row match-row">
                <div class="row-line row-line-four">
                  <label class="inline-field small">
                    <span>Tipo</span>
                    <select name="match_type[]">
                      <option value="Deathmatch">Deathmatch</option>
                      <option value="Team Deathmatch">Team Deathmatch</option>
                      <option value="Ranked">Ranked</option>
                      <option value="Unrated">Unrated</option>
                      <option value="Premier">Premier</option>
                      <option value="Custom">Custom</option>
                    </select>
                  </label>
                  <label class="inline-field small">
                    <span>Kills</span>
                    <input type="text" name="match_kills[]" placeholder="24" />
                  </label>
                  <label class="inline-field small">
                    <span>Deaths</span>
                    <input type="text" name="match_deaths[]" placeholder="18" />
                  </label>
                  <label class="inline-field small">
                    <span>Assists</span>
                    <input type="text" name="match_assists[]" placeholder="5" />
                  </label>
                  <label class="inline-field small">
                    <span>Headshot %</span>
                    <input type="text" name="match_headshot_pct[]" placeholder="27.5" />
                  </label>
                </div>
                <div class="match-note-row">
                  <span class="match-note">DM / TDM</span>
                  <span class="small-muted">Solo K/D/A, KDA, headshot y resultado.</span>
                </div>
                <div class="row-line row-line-four match-line-bottom">
                  <label class="inline-field small">
                    <span>Resultado</span>
                    <select name="match_result[]" required>
                      <option value="">Elige</option>
                      <option value="win">Win</option>
                      <option value="loss">Loss</option>
                    </select>
                  </label>
                  <div class="kda-preview-box">
                    <span class="small-muted">KDA auto</span>
                    <strong data-kda-preview>0.00</strong>
                  </div>
                  <button class="remove-row" type="button" title="Eliminar partida">-</button>
                </div>
                <div class="row-line row-line-four match-ranked-fields" data-ranked-match-fields hidden>
                  <div class="match-note-row match-note-row-ranked">
                    <span class="match-note">Ranked / Premier</span>
                    <span class="small-muted">Rondas, ACS y KAST solo aparecen aquí.</span>
                  </div>
                  <label class="inline-field small">
                    <span>Rondas a favor</span>
                    <input type="text" name="match_rounds_for[]" placeholder="13" />
                  </label>
                  <label class="inline-field small">
                    <span>Rondas en contra</span>
                    <input type="text" name="match_rounds_against[]" placeholder="4" />
                  </label>
                  <label class="inline-field small">
                    <span>ACS</span>
                    <input type="text" name="match_acs[]" placeholder="245" />
                  </label>
                  <label class="inline-field small">
                    <span>KAST %</span>
                    <input type="text" name="match_kast[]" placeholder="78.5" />
                  </label>
                </div>
              </article>
            <?php endfor; ?>
          </div>
          <div class="actions">
            <button class="primary-btn" type="submit" name="save_scope" value="matches">Guardar partidas</button>
          </div>
        </div>
      </form>
    </section>

    <section class="card overview-card">
      <div class="section-title">
        <div>
          <p class="eyebrow">Historial</p>
          <h3>Sesiones recientes</h3>
        </div>
        <a class="secondary-btn" href="dashboard.php">Ir al resumen</a>
      </div>

      <div class="table-wrap compact-table">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
                  <th>Hora</th>
              <th>Dia</th>
              <th>Benchmark</th>
                  <th>Rutinas</th>
                  <th>Partidas</th>
                  <th>Win/Loss</th>
                  <th>KDA medio</th>
            </tr>
          </thead>
          <tbody id="logBody">
            <?php if (!$sessions): ?>
              <tr>
                    <td class="empty" colspan="8">Aun no hay registros.</td>
              </tr>
            <?php else: ?>
              <?php foreach (array_slice($sessions, 0, 8) as $session): ?>
                <tr id="session-<?= (int) $session['id'] ?>">
                  <td><?= h(date('d/m/Y', strtotime((string) $session['session_date']))) ?></td>
                      <td><?= h(date('H:i', strtotime((string) $session['created_at']))) ?></td>
                  <td><?= h(day_label_es((string) $session['day_name'])) ?></td>
                  <td><?= h((string) $session['benchmark']) ?></td>
                      <td><?= h(format_int_es((int) count($session['routines']))) ?></td>
                      <td><?= h(format_int_es((int) $session['match_count'])) ?></td>
                      <td><?= h(format_int_es((int) $session['win_count'])) ?> / <?= h(format_int_es((int) $session['loss_count'])) ?></td>
                      <td><?= h($session['avg_kda'] !== null ? format_float_es((float) $session['avg_kda']) : '-') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>

  <script>
    window.__CHART_DATA__ = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.__DASHBOARD_DATA__ = <?= json_encode(array_merge($dashboardCatalog, [
      'routine_items' => $routineItems,
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    <?php if ($sessionSaved): ?>
    window.localStorage.removeItem('valorantRoutine.sessionsDraft');
    <?php endif; ?>
  </script>
  <script src="app.js?v=<?= h(asset_version('app.js')) ?>"></script>
</body>
</html>
