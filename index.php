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
      <header class="site-header card">
        <div class="brand brand-lockup">
          <div class="brand-mark">VT</div>
          <div>
            <p class="eyebrow"><?= $user ? 'Training dashboard' : 'Valorant routine' ?></p>
            <h1><?= $user ? 'Training Log' : 'Training Log' ?></h1>
          </div>
        </div>

        <nav class="site-nav" aria-label="Principal">
          <?php if ($user): ?>
            <a href="#resumen">Resumen</a>
            <a href="#grafico">Grafico</a>
            <a href="#entrada">Entrada</a>
            <a href="#historial">Historial</a>
          <?php else: ?>
            <a href="#inicio">Inicio</a>
            <a href="#metodo">Metodo</a>
            <a href="#acceso">Acceso</a>
          <?php endif; ?>
        </nav>

        <div class="header-actions">
          <?php if ($user): ?>
            <span class="header-badge">Sesion activa</span>
            <form action="actions.php" method="post" class="header-logout-form">
              <input type="hidden" name="action" value="logout" />
              <button class="ghost-btn" type="submit">Cerrar sesion</button>
            </form>
          <?php else: ?>
            <button class="ghost-btn" type="button" data-auth-open="login">Iniciar sesion</button>
            <button class="primary-btn" type="button" data-auth-open="register">Crear cuenta</button>
          <?php endif; ?>
        </div>
      </header>

      <?php if (!$user): ?>
        <main class="landing-shell">
          <section class="hero card" id="inicio">
            <div class="hero-copy-block">
              <p class="eyebrow">Sistema de entrenamiento</p>
              <h2>Tu rutina de Valorant, ordenada como un producto serio.</h2>
              <p class="hero-copy">Registra sesiones de KovaaK's, Deathmatch y benchmarks con una interfaz limpia, rapida y pensada para revisar progreso real sin pelearte con el navegador.</p>

              <div class="hero-actions">
                <button class="primary-btn" type="button" data-auth-open="register">Crear cuenta</button>
                <button class="secondary-btn" type="button" data-auth-open="login">Ver acceso</button>
              </div>

              <div class="hero-pills">
                <span class="chip">PHP + MySQL</span>
                <span class="chip">Sesiones diarias</span>
                <span class="chip">Grafico de progreso</span>
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
                  <span>Training preview</span>
                </div>

                <div class="preview-metrics">
                  <div class="preview-metric">
                    <span class="preview-metric__value">04</span>
                    <span class="preview-metric__label">bloques diarios</span>
                  </div>
                  <div class="preview-metric">
                    <span class="preview-metric__value">02</span>
                    <span class="preview-metric__label">DM por sesion</span>
                  </div>
                  <div class="preview-metric">
                    <span class="preview-metric__value">MySQL</span>
                    <span class="preview-metric__label">persistencia real</span>
                  </div>
                </div>

                <ul class="preview-list">
                  <li>Rutinas con puntos, minutos y accuracy.</li>
                  <li>Partidas con KDA, HS y resultado.</li>
                  <li>Historia diaria ordenada por fecha.</li>
                </ul>
              </article>
            </div>
          </section>

          <section class="feature-grid" id="metodo">
            <article class="card feature-card">
              <p class="panel-label">Sesion diaria</p>
              <h3>Todo entra en una misma hoja</h3>
              <p class="small-muted">Benchmark, rutinas, partidas y notas quedan vinculados al mismo dia para que no pierdas contexto.</p>
            </article>

            <article class="card feature-card">
              <p class="panel-label">Seguimiento real</p>
              <h3>Puntos y medias utiles</h3>
              <p class="small-muted">La app calcula total de puntos, KDA medio y HS medio para darte una lectura rapida del entrenamiento.</p>
            </article>

            <article class="card feature-card">
              <p class="panel-label">Acceso mock</p>
              <h3>Login y registro en modo panel</h3>
              <p class="small-muted">La parte de acceso esta presentada como mock visual, pero sigue conectada a los formularios reales.</p>
            </article>
          </section>

          <section class="card access-console" id="acceso" data-auth-console>
            <div class="section-title">
              <div>
                <p class="eyebrow">Acceso</p>
                <h3>Panel de entrada</h3>
              </div>
              <p class="small-muted">Mock visual con formularios conectados a MySQL</p>
            </div>

            <div class="tab-bar" role="tablist" aria-label="Acceso al sistema">
              <button class="tab-btn <?= $activeAuthTab === 'login' ? 'is-active' : '' ?>" type="button" data-auth-tab="login">Iniciar sesion</button>
              <button class="tab-btn <?= $activeAuthTab === 'register' ? 'is-active' : '' ?>" type="button" data-auth-tab="register">Registro</button>
            </div>

            <div class="auth-panels">
              <section class="auth-panel <?= $activeAuthTab === 'login' ? 'is-active' : '' ?>" data-auth-panel="login">
                <div class="auth-panel-copy">
                  <p class="panel-label">Entrar</p>
                  <h3>Inicia sesion y recupera tu progreso</h3>
                  <p class="small-muted">Usa tu usuario o email para volver al seguimiento de sesiones y partidas.</p>
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
                  <h3>Crea tu cuenta para guardar sesiones</h3>
                  <p class="small-muted">La estructura esta pensada para probar la experiencia visual sin perder el registro real en base de datos.</p>
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
          </section>
        </main>
      <?php else: ?>
        <main class="dashboard-shell">
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
              <p class="panel-label">Resumen rapido</p>
              <div class="mini-stats">
                <div class="mini-stat">
                  <span><?= h((string) $stats['session_count']) ?></span>
                  <small>sesiones</small>
                </div>
                <div class="mini-stat">
                  <span><?= h(format_float_es($stats['average_kda'])) ?></span>
                  <small>KDA medio</small>
                </div>
                <div class="mini-stat">
                  <span><?= h(format_int_es($stats['best_points'])) ?></span>
                  <small>mejor dia</small>
                </div>
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
          </aside>

          <section class="content">
            <header class="hero card" id="resumen">
              <div class="hero-copy-block">
                <p class="eyebrow">Daily tracking</p>
                <h2>Registra tu progreso como si estuvieras llevando una hoja de entrenamiento interna.</h2>
                <p class="hero-copy">Puntos por ejercicio, medias de Deathmatch y una grafica diaria para ver si la rutina realmente te esta haciendo mejorar.</p>

                <div class="hero-actions">
                  <a class="primary-btn" href="#entrada">Nueva sesion</a>
                  <a class="secondary-btn" href="#historial">Ver historial</a>
                </div>
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

            <section class="card chart-card" id="grafico">
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

            <section class="card form-card" id="entrada">
              <div class="section-title">
                <div>
                  <p class="eyebrow">Nueva entrada</p>
                  <h3>Apunta una sesion estilo panel profesional</h3>
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

            <section class="card table-card" id="historial">
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
      <?php endif; ?>

      <script>
        window.__CHART_DATA__ = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
      </script>
      <script src="app.js"></script>
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
