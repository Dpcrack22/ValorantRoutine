<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = db();
$flash = flash_get();
$user = current_user($pdo);

$sessions = [];
$stats = [
    'session_count' => 0,
    'average_kda' => 0.0,
    'best_points' => 0,
];
$chartData = [];

if ($user) {
    $sessions = fetch_sessions($pdo, (int) $user['id']);
    $stats = calculate_stats($sessions);
    $chartData = build_chart_data($sessions);
}

$dayOptions = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miercoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
];

$routineSectionOptions = routine_section_options();
$matchTypeOptions = match_type_options();

function fetch_sessions(PDO $pdo, int $userId): array
{
    $sessionStatement = $pdo->prepare(
    'SELECT id, session_date, day_name, benchmark, notes, created_at, updated_at
         FROM training_days
         WHERE user_id = :user_id
         ORDER BY session_date DESC, id DESC'
    );
    $sessionStatement->execute(['user_id' => $userId]);
    $sessions = $sessionStatement->fetchAll();

    $exerciseStatement = $pdo->prepare(
    'SELECT section_name, item_name, score_points, duration_minutes, accuracy_pct, notes, sort_order
     FROM training_routines
         WHERE training_day_id = :day_id
         ORDER BY sort_order ASC, id ASC'
    );

    $dmStatement = $pdo->prepare(
    'SELECT match_type, map_name, kills, deaths, assists, kda, headshot_pct, score_points, match_result, notes, sort_order
     FROM training_matches
         WHERE training_day_id = :day_id
         ORDER BY sort_order ASC, id ASC'
    );

    foreach ($sessions as &$session) {
        $dayId = (int) $session['id'];

        $exerciseStatement->execute(['day_id' => $dayId]);
    $routines = $exerciseStatement->fetchAll();

        $dmStatement->execute(['day_id' => $dayId]);
    $matches = $dmStatement->fetchAll();

    $session['routines'] = $routines;
    $session['matches'] = $matches;
    $session['total_points'] = sum_points($routines, 'score_points') + sum_points($matches, 'score_points');
    $session['avg_kda'] = $matches ? average_from_rows($matches, 'kda') : null;
    $session['avg_hs'] = $matches ? average_from_rows($matches, 'headshot_pct') : null;
    }
    unset($session);

    return $sessions;
}

function calculate_stats(array $sessions): array
{
    $kdas = [];
    $bestPoints = 0;

    foreach ($sessions as $session) {
        $bestPoints = max($bestPoints, (int) ($session['total_points'] ?? 0));
    foreach ($session['matches'] as $match) {
      if ($match['kda'] !== null) {
        $kdas[] = (float) $match['kda'];
            }
        }
    }

    $averageKda = $kdas ? array_sum($kdas) / count($kdas) : 0.0;

    return [
        'session_count' => count($sessions),
        'average_kda' => $averageKda,
        'best_points' => $bestPoints,
    ];
}

function build_chart_data(array $sessions): array
{
    $ordered = array_reverse($sessions);
    $data = [];

    foreach ($ordered as $session) {
        $data[] = [
            'label' => format_chart_label((string) $session['session_date']),
            'points' => (int) ($session['total_points'] ?? 0),
        ];
    }

    return $data;
}

function average_from_rows(array $rows, string $key): float
{
    $values = [];

    foreach ($rows as $row) {
        if ($row[$key] !== null && $row[$key] !== '') {
            $values[] = (float) $row[$key];
        }
    }

    if (!$values) {
        return 0.0;
    }

    return array_sum($values) / count($values);
}

  function sum_points(array $exercises, string $key = 'points'): int
{
    $total = 0;
    foreach ($exercises as $exercise) {
      $value = $exercise[$key] ?? null;
      if ($value !== null && $value !== '') {
        $total += (int) $value;
      }
    }

    return $total;
}

function format_chart_label(string $date): string
{
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    return date('j/n', $timestamp);
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="bg-orb orb-a"></div>
  <div class="bg-orb orb-b"></div>
  <div class="bg-grid"></div>

  <?php if ($flash): ?>
    <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
  <?php endif; ?>

  <?php if (!$user): ?>
    <main class="auth-shell">
      <section class="card auth-intro">
        <div class="brand">
          <div class="brand-mark">VT</div>
          <div>
            <p class="eyebrow">PHP + MySQL</p>
            <h1>VALORANT Training Log</h1>
          </div>
        </div>

        <h2>Tu rutina de KovaaK's, Deathmatch y benchmarks guardada en base de datos.</h2>
        <p class="hero-copy">Pensada para InfinityFree: usuarios, sesiones diarias, puntos por ejercicio, DMs y grafico de progreso sin depender del navegador.</p>

        <section class="panel compact">
          <p class="panel-label">Incluye</p>
          <ul class="list">
            <li>Login y registro de usuarios</li>
            <li>Sesiones por dia con ejercicios</li>
            <li>DM KDA y headshot %</li>
            <li>Grafico de puntos por dia</li>
            <li>Guardado en MySQL</li>
          </ul>
        </section>

        <section class="panel compact">
          <p class="panel-label">Benchmarks</p>
          <div class="link-stack">
            <a class="link-btn" href="https://app.voltaic.gg/scenarios" target="_blank" rel="noreferrer">Voltaic Scenarios / Benchmarks</a>
            <a class="link-btn" href="https://www.kovaaks.com/kovaaks/playlists" target="_blank" rel="noreferrer">KovaaK's Playlists</a>
          </div>
        </section>
      </section>

      <section class="auth-grid">
        <section class="card auth-card">
          <p class="eyebrow">Entrar</p>
          <h3>Iniciar sesion</h3>
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

        <section class="card auth-card">
          <p class="eyebrow">Nuevo usuario</p>
          <h3>Crear cuenta</h3>
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
      </section>
    </main>
  <?php else: ?>
    <main class="shell">
      <aside class="sidebar card">
        <div class="brand">
          <div class="brand-mark">VT</div>
          <div>
            <p class="eyebrow">Usuario activo</p>
            <h1><?= h($user['username']) ?></h1>
          </div>
        </div>

        <section class="panel compact">
          <p class="panel-label">Cuenta</p>
          <div class="user-card">
            <strong><?= h($user['username']) ?></strong>
            <span><?= h($user['email']) ?></span>
          </div>
        </section>

        <section class="panel compact">
          <p class="panel-label">Rutina fija</p>
          <ul class="list">
            <li>KovaaK's: 35-40 min</li>
            <li>Range: 5-10 min</li>
            <li>Deathmatch: 2 partidas</li>
            <li>Lunes a viernes</li>
            <li>Sabado y domingo: descanso</li>
          </ul>
        </section>

        <section class="panel compact">
          <p class="panel-label">Benchmarks</p>
          <div class="link-stack">
            <a class="link-btn" href="https://app.voltaic.gg/scenarios" target="_blank" rel="noreferrer">Voltaic Scenarios / Benchmarks</a>
            <a class="link-btn" href="https://www.kovaaks.com/kovaaks/playlists" target="_blank" rel="noreferrer">KovaaK's Playlists</a>
          </div>
        </section>

        <form action="actions.php" method="post" class="logout-form">
          <input type="hidden" name="action" value="logout" />
          <button class="ghost-btn full" type="submit">Cerrar sesion</button>
        </form>
      </aside>

      <section class="content">
        <header class="hero card">
          <div>
            <p class="eyebrow">Daily tracking</p>
            <h2>Registra tu progreso como si estuvieras llevando una hoja de entrenamiento profesional.</h2>
            <p class="hero-copy">Puntos por ejercicio, medias de Deathmatch y una grafica diaria para ver si la rutina realmente te esta haciendo mejorar.</p>
          </div>
          <div class="hero-stats">
            <div class="stat">
              <span class="stat-value"><?= h((string) $stats['session_count']) ?></span>
              <span class="stat-label">Sesiones</span>
            </div>
            <div class="stat">
              <span class="stat-value"><?= h(format_float_es($stats['average_kda'])) ?></span>
              <span class="stat-label">KDA medio</span>
            </div>
            <div class="stat">
              <span class="stat-value"><?= h(format_int_es($stats['best_points'])) ?></span>
              <span class="stat-label">Mejor dia</span>
            </div>
          </div>
        </header>

        <section class="card chart-card">
          <div class="section-title">
            <div>
              <p class="eyebrow">Grafico</p>
              <h3>Puntos por dia</h3>
            </div>
            <p class="small-muted">Suma total de puntos de tus ejercicios</p>
          </div>
          <div class="chart-wrap">
            <canvas id="progressChart" width="1200" height="420"></canvas>
          </div>
        </section>

        <section class="card form-card">
          <div class="section-title">
            <div>
              <p class="eyebrow">Nueva entrada</p>
              <h3>Apunta una sesion estilo Excel</h3>
            </div>
          </div>

          <form class="entry-form" action="actions.php" method="post">
            <input type="hidden" name="action" value="save_session" />

            <label>
              <span>Fecha</span>
              <input id="sessionDate" type="date" name="session_date" value="<?= h(date('Y-m-d')) ?>" required />
            </label>

            <label>
              <span>Dia</span>
              <select name="day_name" required>
                <?php foreach ($dayOptions as $value => $label): ?>
                  <option value="<?= h($value) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="full">
              <span>Benchmark / playlist principal</span>
              <input type="text" name="benchmark" placeholder="Ej: GON MACHINE for VALO v2" required />
            </label>

            <div class="full sub-section">
              <div class="section-title tight">
                <div>
                  <p class="eyebrow">Rutinas</p>
                  <h4>Ejercicios diarios</h4>
                </div>
                <button id="addRoutine" class="secondary-btn" type="button">Añadir rutina</button>
              </div>
              <div id="routineRows" class="rows-grid">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <article class="entry-row routine-row">
                    <div class="row-line row-line-three">
                      <label class="inline-field">
                        <span>Seccion</span>
                        <select name="routine_section[]">
                          <?php foreach ($routineSectionOptions as $value => $label): ?>
                            <option value="<?= h($value) ?>"><?= h($label) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>
                      <label class="inline-field grow">
                        <span>Ejercicio</span>
                        <input type="text" name="routine_name[]" placeholder="Ej: 1w6targets small" />
                      </label>
                      <label class="inline-field small">
                        <span>Puntos</span>
                        <input type="text" name="routine_points[]" placeholder="12340" />
                      </label>
                    </div>
                    <div class="row-line row-line-four">
                      <label class="inline-field small">
                        <span>Min</span>
                        <input type="text" name="routine_minutes[]" placeholder="12.5" />
                      </label>
                      <label class="inline-field small">
                        <span>Accuracy %</span>
                        <input type="text" name="routine_accuracy[]" placeholder="86.4" />
                      </label>
                      <label class="inline-field grow">
                        <span>Notas</span>
                        <input type="text" name="routine_notes[]" placeholder="Sensacion, foco, error concreto..." />
                      </label>
                      <button class="remove-row" type="button" title="Eliminar rutina">-</button>
                    </div>
                  </article>
                <?php endfor; ?>
              </div>
            </div>

            <div class="full sub-section">
              <div class="section-title tight">
                <div>
                  <p class="eyebrow">Partidas</p>
                  <h4>Registro manual de partidas</h4>
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
                          <?php foreach ($matchTypeOptions as $value => $label): ?>
                            <option value="<?= h($value) ?>"><?= h($label) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </label>
                      <label class="inline-field grow">
                        <span>Mapa / modo</span>
                        <input type="text" name="match_map[]" placeholder="Ej: Ascent, DM, Ranked..." />
                      </label>
                      <label class="inline-field small">
                        <span>Puntos</span>
                        <input type="text" name="match_score[]" placeholder="0" />
                      </label>
                      <label class="inline-field small">
                        <span>Resultado</span>
                        <input type="text" name="match_result[]" placeholder="W / L / OT" />
                      </label>
                    </div>
                    <div class="row-line row-line-six">
                      <label class="inline-field small">
                        <span>K</span>
                        <input type="text" name="match_kills[]" placeholder="24" />
                      </label>
                      <label class="inline-field small">
                        <span>D</span>
                        <input type="text" name="match_deaths[]" placeholder="18" />
                      </label>
                      <label class="inline-field small">
                        <span>A</span>
                        <input type="text" name="match_assists[]" placeholder="5" />
                      </label>
                      <label class="inline-field small">
                        <span>KDA</span>
                        <input type="text" name="match_kda[]" placeholder="1.61" />
                      </label>
                      <label class="inline-field small">
                        <span>HS %</span>
                        <input type="text" name="match_hs[]" placeholder="28.4" />
                      </label>
                      <label class="inline-field grow">
                        <span>Notas</span>
                        <input type="text" name="match_notes[]" placeholder="Lectura, entradas, errores, etc." />
                      </label>
                      <button class="remove-row" type="button" title="Eliminar partida">-</button>
                    </div>
                  </article>
                <?php endfor; ?>
              </div>
            </div>

            <label class="full">
              <span>Notas generales</span>
              <textarea name="notes" rows="4" placeholder="Resumen del dia, sensacion, objetivos, errores..."></textarea>
            </label>

            <div class="actions full">
              <button class="primary-btn" type="submit">Guardar sesion</button>
              <button class="secondary-btn" id="fillToday" type="button">Fecha de hoy</button>
            </div>
          </form>
        </section>

        <section class="card table-card">
          <div class="section-title">
            <div>
              <p class="eyebrow">Historial</p>
              <h3>Entradas guardadas</h3>
            </div>
            <p class="small-muted"><?= h((string) count($sessions)) ?> sesiones almacenadas</p>
          </div>

          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Dia</th>
                  <th>Benchmark</th>
                  <th>Puntos</th>
                  <th>Rutinas</th>
                  <th>Partidas</th>
                  <th>KDA medio</th>
                  <th>HS % medio</th>
                  <th>Notas</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="logBody">
                <?php if (!$sessions): ?>
                  <tr>
                    <td class="empty" colspan="10">Aun no hay registros. Guarda tu primera sesion para empezar el seguimiento.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($sessions as $session): ?>
                    <tr>
                      <td><?= h(date('d/m/Y', strtotime((string) $session['session_date']))) ?></td>
                      <td><?= h(day_label_es((string) $session['day_name'])) ?></td>
                      <td><?= h((string) $session['benchmark']) ?></td>
                      <td><?= h(format_int_es((int) $session['total_points'])) ?></td>
                      <td class="activity-summary">
                        <?php if ($session['routines']): ?>
                          <?php foreach ($session['routines'] as $routine): ?>
                            <span>
                              <?= h((string) $routine['section_name']) ?> · <?= h((string) $routine['item_name']) ?>
                              <?php if ($routine['score_points'] !== null): ?>
                                · <?= h(format_int_es((int) $routine['score_points'])) ?> pts
                              <?php endif; ?>
                              <?php if ($routine['duration_minutes'] !== null): ?>
                                · <?= h(format_float_es((float) $routine['duration_minutes'], 1)) ?> min
                              <?php endif; ?>
                              <?php if ($routine['accuracy_pct'] !== null): ?>
                                · <?= h(format_float_es((float) $routine['accuracy_pct'], 1)) ?>%
                              <?php endif; ?>
                            </span>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span>-</span>
                        <?php endif; ?>
                      </td>
                      <td class="activity-summary">
                        <?php if ($session['matches']): ?>
                          <?php foreach ($session['matches'] as $match): ?>
                            <span>
                              <?= h((string) $match['match_type']) ?>
                              <?php if (!empty($match['map_name'])): ?>
                                · <?= h((string) $match['map_name']) ?>
                              <?php endif; ?>
                              <?php if ($match['score_points'] !== null): ?>
                                · <?= h(format_int_es((int) $match['score_points'])) ?> pts
                              <?php endif; ?>
                              <?php if ($match['kda'] !== null): ?>
                                · KDA <?= h(format_float_es((float) $match['kda'])) ?>
                              <?php endif; ?>
                              <?php if ($match['headshot_pct'] !== null): ?>
                                · HS <?= h(format_float_es((float) $match['headshot_pct'])) ?>%
                              <?php endif; ?>
                              <?php if (!empty($match['match_result'])): ?>
                                · <?= h((string) $match['match_result']) ?>
                              <?php endif; ?>
                            </span>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <span>-</span>
                        <?php endif; ?>
                      </td>
                      <td><?= h($session['avg_kda'] !== null ? format_float_es((float) $session['avg_kda']) : '-') ?></td>
                      <td><?= h($session['avg_hs'] !== null ? format_float_es((float) $session['avg_hs']) . '%' : '-') ?></td>
                      <td class="row-note"><?= h((string) ($session['notes'] ?: '-')) ?></td>
                      <td>
                        <form class="row-delete-form" action="actions.php" method="post" onsubmit="return confirm('Quieres borrar esta sesion?');">
                          <input type="hidden" name="action" value="delete_session" />
                          <input type="hidden" name="day_id" value="<?= (int) $session['id'] ?>" />
                          <button class="row-action" type="submit">Borrar</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </section>
    </main>

    <script>
      window.__CHART_DATA__ = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
    <script src="app.js"></script>
  <?php endif; ?>
</body>
</html>
