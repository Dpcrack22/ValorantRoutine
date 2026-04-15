<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

$action = (string) ($_POST['action'] ?? '');

switch ($action) {
    case 'register':
        handle_register($pdo);
        break;

    case 'login':
        handle_login($pdo);
        break;

    case 'save_session':
        handle_save_session($pdo);
        break;

    case 'add_exercise':
        handle_add_exercise($pdo);
        break;

    case 'add_routine_item':
        handle_add_routine_item($pdo);
        break;

    case 'delete_routine_item':
        handle_delete_routine_item($pdo);
        break;

    case 'delete_session':
        handle_delete_session($pdo);
        break;

    case 'save_history_session':
        handle_save_history_session($pdo);
        break;

    case 'logout':
        handle_logout();
        break;

    default:
        flash_set('error', 'Accion no valida.');
        redirect('index.php');
}

function auth_error(string $tab, string $message): void
{
    $_SESSION['auth_tab'] = $tab;
    flash_set('error', $message);
    redirect('index.php?auth=' . rawurlencode($tab));
}

function handle_register(PDO $pdo): void
{
    $username = trim((string) ($_POST['register_username'] ?? ''));
    $email = trim((string) ($_POST['register_email'] ?? ''));
    $password = (string) ($_POST['register_password'] ?? '');
    $confirmPassword = (string) ($_POST['register_password_confirm'] ?? '');

    if (mb_strlen($username) < 3) {
        auth_error('register', 'El usuario debe tener al menos 3 caracteres.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        auth_error('register', 'El email no es valido.');
    }

    if (mb_strlen($password) < 8) {
        auth_error('register', 'La contrasena debe tener al menos 8 caracteres.');
    }

    if ($password !== $confirmPassword) {
        auth_error('register', 'Las contrasenas no coinciden.');
    }

    $statement = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $statement->execute([
        'username' => $username,
        'email' => $email,
    ]);

    if ($statement->fetch()) {
        auth_error('register', 'Ese usuario o email ya existe.');
    }

    $verificationToken = generate_verification_token();
    $verificationTokenHash = hash_verification_token($verificationToken);
    $verificationUrl = app_url('verify_email.php?email=' . rawurlencode($email) . '&token=' . rawurlencode($verificationToken));

    $userColumns = ['username', 'email', 'password_hash'];
    $userPlaceholders = [':username', ':email', ':password_hash'];
    $userValues = [
        'username' => $username,
        'email' => $email,
        'password_hash' => hash_password_sha256($password),
    ];

    if (column_exists($pdo, 'users', 'email_verified_at')) {
        $userColumns[] = 'email_verified_at';
        $userPlaceholders[] = ':email_verified_at';
        $userValues['email_verified_at'] = null;
    }

    if (column_exists($pdo, 'users', 'email_verification_token_hash')) {
        $userColumns[] = 'email_verification_token_hash';
        $userPlaceholders[] = ':email_verification_token_hash';
        $userValues['email_verification_token_hash'] = $verificationTokenHash;
    }

    if (column_exists($pdo, 'users', 'email_verification_sent_at')) {
        $userColumns[] = 'email_verification_sent_at';
        $userPlaceholders[] = ':email_verification_sent_at';
        $userValues['email_verification_sent_at'] = date('Y-m-d H:i:s');
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (' . implode(', ', $userColumns) . ') VALUES (' . implode(', ', $userPlaceholders) . ')'
    );
    $insert->execute($userValues);

    $mailSent = send_verification_email($email, $username, $verificationUrl);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    unset($_SESSION['auth_tab']);
    if ($mailSent) {
        flash_set('success', 'Cuenta creada. Te hemos enviado un correo de verificacion.');
    } else {
        flash_set('info', 'Cuenta creada, pero no se pudo enviar el correo de verificacion. Abre manualmente: ' . $verificationUrl);
    }
    redirect('index.php');
}

function handle_login(PDO $pdo): void
{
    $identifier = trim((string) ($_POST['login_identifier'] ?? ''));
    $password = (string) ($_POST['login_password'] ?? '');

    if ($identifier === '' || $password === '') {
        auth_error('login', 'Completa usuario/email y contrasena.');
    }

    $loginFields = 'id, username, email, password_hash';
    if (column_exists($pdo, 'users', 'email_verified_at')) {
        $loginFields .= ', email_verified_at';
    } else {
        $loginFields .= ', NULL AS email_verified_at';
    }

    $statement = $pdo->prepare(
        'SELECT ' . $loginFields . ' FROM users WHERE username = :username OR email = :email LIMIT 1'
    );
    $statement->execute([
        'username' => $identifier,
        'email' => $identifier,
    ]);
    $user = $statement->fetch();

    if (!$user || !verify_password($password, (string) $user['password_hash'])) {
        auth_error('login', 'Credenciales incorrectas.');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['auth_tab']);
    if (!empty($user['email_verified_at'])) {
        flash_set('success', 'Bienvenido de nuevo.');
    } else {
        flash_set('info', 'Bienvenido de nuevo. Recuerda verificar tu correo para activar la cuenta.');
    }
    redirect('index.php');
}

function handle_add_exercise(PDO $pdo): void
{
    require_login();

    $platform = trim((string) ($_POST['exercise_platform'] ?? ''));
    $exerciseName = trim((string) ($_POST['exercise_name'] ?? ''));
    $notes = trim((string) ($_POST['exercise_notes'] ?? ''));

    if ($platform === '' || $exerciseName === '') {
        flash_set('error', 'Plataforma y ejercicio son obligatorios.');
        redirect('dashboard.php');
    }

    $insert = $pdo->prepare(
        'INSERT INTO training_exercises (platform, exercise_name, notes) VALUES (:platform, :exercise_name, :notes)
         ON DUPLICATE KEY UPDATE notes = VALUES(notes), updated_at = CURRENT_TIMESTAMP'
    );
    $insert->execute([
        'platform' => $platform,
        'exercise_name' => $exerciseName,
        'notes' => $notes !== '' ? $notes : null,
    ]);

    flash_set('success', 'Ejercicio añadido al catalogo.');
    redirect('exercises.php');
}

function handle_add_routine_item(PDO $pdo): void
{
    require_login();

    $userId = (int) $_SESSION['user_id'];
    $exerciseId = parse_int_or_null($_POST['exercise_id'] ?? null);
    $routineName = trim((string) ($_POST['routine_name'] ?? ''));
    $repetitions = parse_int_or_null($_POST['routine_repetitions'] ?? null) ?? 1;
    $targetMinutes = parse_float_or_null($_POST['target_minutes'] ?? null);
    $targetAccuracy = parse_float_or_null($_POST['target_accuracy'] ?? null);
    $notes = trim((string) ($_POST['routine_item_notes'] ?? ''));

    if ($routineName === '') {
        $routineName = 'Rutina principal';
    }

    if ($exerciseId === null) {
        flash_set('error', 'Selecciona un ejercicio valido.');
        redirect('dashboard.php');
    }

    $statement = $pdo->prepare('SELECT id FROM training_exercises WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $exerciseId]);

    if (!$statement->fetch()) {
        flash_set('error', 'Ese ejercicio no existe.');
        redirect('dashboard.php');
    }

    if ($repetitions < 1) {
        flash_set('error', 'Las repeticiones deben ser al menos 1.');
        redirect('routine.php');
    }

    $sortStatement = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM user_routine_items WHERE user_id = :user_id');
    $sortStatement->execute(['user_id' => $userId]);
    $nextOrder = (int) ($sortStatement->fetch()['next_order'] ?? 1);

    $routineColumns = ['user_id', 'exercise_id', 'sort_order', 'target_minutes', 'target_accuracy', 'notes'];
    $routinePlaceholders = [':user_id', ':exercise_id', ':sort_order', ':target_minutes', ':target_accuracy', ':notes'];
    $routineValues = [
        'user_id' => $userId,
        'exercise_id' => $exerciseId,
        'sort_order' => $nextOrder,
        'target_minutes' => $targetMinutes,
        'target_accuracy' => $targetAccuracy,
        'notes' => $notes !== '' ? $notes : null,
    ];

    if (column_exists($pdo, 'user_routine_items', 'routine_name')) {
        $routineColumns[] = 'routine_name';
        $routinePlaceholders[] = ':routine_name';
        $routineValues['routine_name'] = $routineName;
    }

    if (column_exists($pdo, 'user_routine_items', 'repetitions')) {
        $routineColumns[] = 'repetitions';
        $routinePlaceholders[] = ':repetitions';
        $routineValues['repetitions'] = $repetitions;
    }

    $insert = $pdo->prepare(
        'INSERT INTO user_routine_items (' . implode(', ', $routineColumns) . ')
         VALUES (' . implode(', ', $routinePlaceholders) . ')'
    );

    try {
        $insert->execute($routineValues);
    } catch (Throwable $throwable) {
        flash_set('error', 'Ese ejercicio ya existe en la rutina seleccionada.');
        redirect('routine.php');
    }

    flash_set('success', 'Ejercicio añadido a tu rutina.');
    $_SESSION['preferred_routine_name'] = $routineName;
    redirect('routine.php');
}

function handle_delete_routine_item(PDO $pdo): void
{
    require_login();

    $routineItemId = parse_int_or_null($_POST['routine_item_id'] ?? null);
    if ($routineItemId === null) {
        flash_set('error', 'Elemento de rutina invalido.');
        redirect('dashboard.php');
    }

    $delete = $pdo->prepare('DELETE FROM user_routine_items WHERE id = :id AND user_id = :user_id');
    $delete->execute([
        'id' => $routineItemId,
        'user_id' => (int) $_SESSION['user_id'],
    ]);

    flash_set('success', 'Ejercicio eliminado de tu rutina.');
    redirect('routine.php');
}

function handle_save_session(PDO $pdo): void
{
    require_login();

    $userId = (int) $_SESSION['user_id'];
    $saveScope = (string) ($_POST['save_scope'] ?? 'full');
    if (!in_array($saveScope, ['full', 'routine', 'matches'], true)) {
        $saveScope = 'full';
    }

    $sessionDate = trim((string) ($_POST['session_date'] ?? ''));
    $dayName = trim((string) ($_POST['day_name'] ?? ''));
    $sessionRoutineName = trim((string) ($_POST['session_routine_name'] ?? ''));
    $benchmarkRoutineItemId = parse_int_or_null($_POST['benchmark_routine_item_id'] ?? null);
    $benchmark = trim((string) ($_POST['benchmark'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($sessionRoutineName === '') {
        $sessionRoutineName = 'Rutina principal';
    }

    if ($sessionDate === '' || $dayName === '') {
        flash_set('error', 'Fecha y dia son obligatorios.');
        redirect('sessions.php');
    }

    $todayDate = date('Y-m-d');
    if ($sessionDate !== $todayDate) {
        flash_set('error', 'Desde Sesiones solo puedes guardar la fecha de hoy. Para editar o registrar otros dias usa el apartado de historial.');
        redirect('sessions.php');
    }

    $_SESSION['preferred_routine_name'] = $sessionRoutineName;

    $hasSessionRoutineNameColumn = column_exists($pdo, 'training_days', 'session_routine_name');
    $hasRoutineNameColumn = column_exists($pdo, 'user_routine_items', 'routine_name');

    $existingDayFields = 'id, benchmark';
    if ($hasSessionRoutineNameColumn) {
        $existingDayFields .= ', session_routine_name';
    }

    $existingDayStatement = $pdo->prepare(
        'SELECT ' . $existingDayFields . '
         FROM training_days
         WHERE user_id = :user_id AND session_date = :session_date
         LIMIT 1'
    );
    $existingDayStatement->execute([
        'user_id' => $userId,
        'session_date' => $sessionDate,
    ]);
    $existingDay = $existingDayStatement->fetch() ?: null;

    if ($existingDay && $sessionDate !== $todayDate) {
        flash_set('error', 'Las sesiones de dias anteriores no se editan desde esta pantalla. Solo puedes editar la sesion de hoy desde Sesiones.');
        redirect('sessions.php');
    }

    if ($existingDay && trim((string) ($existingDay['benchmark'] ?? '')) !== '' && $benchmark === '') {
        $benchmark = trim((string) $existingDay['benchmark']);
    }

    if ($existingDay && $hasSessionRoutineNameColumn && trim((string) ($existingDay['session_routine_name'] ?? '')) !== '' && $sessionRoutineName === 'Rutina principal') {
        $sessionRoutineName = trim((string) $existingDay['session_routine_name']);
    }

    $allowedRoutineNames = [];
    if ($hasRoutineNameColumn) {
        $routineNameStatement = $pdo->prepare(
            'SELECT DISTINCT routine_name
             FROM user_routine_items
             WHERE user_id = :user_id
             ORDER BY routine_name ASC'
        );
        $routineNameStatement->execute(['user_id' => $userId]);
        foreach ($routineNameStatement->fetchAll() as $row) {
            $name = trim((string) ($row['routine_name'] ?? ''));
            if ($name !== '') {
                $allowedRoutineNames[] = $name;
            }
        }

        if ($allowedRoutineNames && !in_array($sessionRoutineName, $allowedRoutineNames, true)) {
            flash_set('error', 'Selecciona una rutina valida para esta sesion.');
            redirect('sessions.php');
        }
    }

    if ($benchmarkRoutineItemId !== null) {
        $benchmarkStatement = $pdo->prepare(
            'SELECT uri.id, te.platform, te.exercise_name
             FROM user_routine_items uri
             INNER JOIN training_exercises te ON te.id = uri.exercise_id
             WHERE uri.id = :id AND uri.user_id = :user_id
             LIMIT 1'
        );
        $benchmarkStatement->execute([
            'id' => $benchmarkRoutineItemId,
            'user_id' => $userId,
        ]);
        $benchmarkRow = $benchmarkStatement->fetch();

        if (!$benchmarkRow) {
            flash_set('error', 'Selecciona un benchmark valido de tu rutina.');
            redirect('sessions.php');
        }

        $benchmark = trim((string) $benchmarkRow['platform']) . ' · ' . trim((string) $benchmarkRow['exercise_name']);
    }

    if ($benchmark === '') {
        flash_set('error', 'Fecha, dia y benchmark son obligatorios.');
        redirect('sessions.php');
    }

    $routineLookup = [];
    $routines = [];
    $routineSnapshotRows = [];
    if ($saveScope !== 'matches') {
        $routineItemIds = array_values((array) ($_POST['routine_user_item_id'] ?? []));
        $routinePoints = array_values((array) ($_POST['routine_points'] ?? $_POST['exercise_points'] ?? []));
        $routineAccuracy = array_values((array) ($_POST['routine_accuracy'] ?? []));

        $routineIds = [];
        foreach ($routineItemIds as $routineItemId) {
            $parsedId = parse_int_or_null($routineItemId);
            if ($parsedId !== null) {
                $routineIds[] = $parsedId;
            }
        }

        if ($routineIds) {
            $placeholders = implode(',', array_fill(0, count($routineIds), '?'));
            $routineColumns = 'uri.id AS routine_item_id, uri.exercise_id, uri.target_minutes, uri.target_accuracy, uri.notes AS routine_notes';
            if ($hasRoutineNameColumn) {
                $routineColumns .= ', uri.routine_name';
            }
            $routineColumns .= ', te.platform, te.exercise_name';

            $routineStatement = $pdo->prepare(
                'SELECT ' . $routineColumns . '
                 FROM user_routine_items uri
                 INNER JOIN training_exercises te ON te.id = uri.exercise_id
                 WHERE uri.user_id = ? AND uri.id IN (' . $placeholders . ')'
            );
            $routineStatement->execute(array_merge([$userId], $routineIds));

            foreach ($routineStatement->fetchAll() as $row) {
                $routineLookup[(int) $row['routine_item_id']] = $row;
            }
        }

        $routineCount = max(
            count($routineItemIds),
            count($routinePoints),
            count($routineAccuracy)
        );

        for ($index = 0; $index < $routineCount; $index += 1) {
            $routineItemId = parse_int_or_null($routineItemIds[$index] ?? null);
            $pointsRaw = trim((string) ($routinePoints[$index] ?? ''));
            $accuracyRaw = trim((string) ($routineAccuracy[$index] ?? ''));

            if (($routineItemId === null || $routineItemId === 0) && $pointsRaw === '' && $accuracyRaw === '') {
                continue;
            }

            if ($routineItemId === null || $routineItemId <= 0) {
                flash_set('error', 'Selecciona un ejercicio valido de tu rutina.');
                redirect('sessions.php');
            }

            $routineData = $routineLookup[$routineItemId] ?? null;
            if (!$routineData) {
                flash_set('error', 'Una de las rutinas seleccionadas no pertenece a tu cuenta.');
                redirect('sessions.php');
            }

            if ($hasRoutineNameColumn) {
                $rowRoutineName = trim((string) ($routineData['routine_name'] ?? ''));
                if ($rowRoutineName !== '' && $rowRoutineName !== $sessionRoutineName) {
                    flash_set('error', 'Solo puedes registrar ejercicios de la rutina seleccionada.');
                    redirect('sessions.php');
                }
            }

            $exerciseId = (int) $routineData['exercise_id'];
            $section = (string) $routineData['platform'];
            $name = (string) $routineData['exercise_name'];

            $points = null;
            if ($pointsRaw !== '') {
                $points = parse_int_or_null($pointsRaw);
                if ($points === null) {
                    flash_set('error', 'Los puntos de cada rutina deben ser numericos.');
                    redirect('sessions.php');
                }
            }

            $accuracy = null;
            if ($accuracyRaw !== '') {
                $accuracy = parse_float_or_null($accuracyRaw);
                if ($accuracy === null) {
                    flash_set('error', 'El porcentaje de cada rutina debe ser numerico.');
                    redirect('sessions.php');
                }
            }

            $routines[] = [
                'user_routine_item_id' => $routineItemId,
                'exercise_id' => $exerciseId,
                'section_name' => $section,
                'item_name' => $name,
                'score_points' => $points,
                'accuracy_pct' => $accuracy,
            ];

            $routineSnapshotRows[] = [
                'user_routine_item_id' => $routineItemId,
                'exercise_id' => $exerciseId,
                'section_name' => $section,
                'item_name' => $name,
                'score_points' => $points,
                'duration_minutes' => null,
                'accuracy_pct' => $accuracy,
                'notes' => null,
            ];
        }
    }

    $matches = [];
    $matchSnapshotRows = [];
    if ($saveScope !== 'routine') {
        $matchTypes = array_values((array) ($_POST['match_type'] ?? $_POST['dm_type'] ?? []));
        $matchKills = array_values((array) ($_POST['match_kills'] ?? []));
        $matchDeaths = array_values((array) ($_POST['match_deaths'] ?? []));
        $matchAssists = array_values((array) ($_POST['match_assists'] ?? []));
        $matchResults = array_values((array) ($_POST['match_result'] ?? []));
        $matchHeadshots = array_values((array) ($_POST['match_headshot_pct'] ?? []));
        $matchRoundsFor = array_values((array) ($_POST['match_rounds_for'] ?? []));
        $matchRoundsAgainst = array_values((array) ($_POST['match_rounds_against'] ?? []));
        $matchAcsValues = array_values((array) ($_POST['match_acs'] ?? []));
        $matchKastValues = array_values((array) ($_POST['match_kast'] ?? []));

        $matchCount = max(
            count($matchTypes),
            count($matchKills),
            count($matchDeaths),
            count($matchAssists),
            count($matchResults),
            count($matchHeadshots),
            count($matchRoundsFor),
            count($matchRoundsAgainst),
            count($matchAcsValues),
            count($matchKastValues)
        );

        for ($index = 0; $index < $matchCount; $index += 1) {
            $type = trim((string) ($matchTypes[$index] ?? ''));
            $killsRaw = trim((string) ($matchKills[$index] ?? ''));
            $deathsRaw = trim((string) ($matchDeaths[$index] ?? ''));
            $assistsRaw = trim((string) ($matchAssists[$index] ?? ''));
            $result = trim((string) ($matchResults[$index] ?? ''));
            $headshotRaw = trim((string) ($matchHeadshots[$index] ?? ''));
            $roundsForRaw = trim((string) ($matchRoundsFor[$index] ?? ''));
            $roundsAgainstRaw = trim((string) ($matchRoundsAgainst[$index] ?? ''));
            $acsRaw = trim((string) ($matchAcsValues[$index] ?? ''));
            $kastRaw = trim((string) ($matchKastValues[$index] ?? ''));

            if ($type === '' && $killsRaw === '' && $deathsRaw === '' && $assistsRaw === '' && $result === '' && $headshotRaw === '' && $roundsForRaw === '' && $roundsAgainstRaw === '' && $acsRaw === '' && $kastRaw === '') {
                continue;
            }

            if ($type === '') {
                flash_set('error', 'Cada partida debe tener un tipo.');
                redirect('sessions.php');
            }

            $isCompetitiveMatch = in_array($type, ['Ranked', 'Premier'], true);

            $kills = null;
            if ($killsRaw !== '') {
                $kills = parse_int_or_null($killsRaw);
                if ($kills === null) {
                    flash_set('error', 'Las kills de cada partida deben ser numericas.');
                    redirect('sessions.php');
                }
            }

            $deaths = null;
            if ($deathsRaw !== '') {
                $deaths = parse_int_or_null($deathsRaw);
                if ($deaths === null) {
                    flash_set('error', 'Las deaths de cada partida deben ser numericas.');
                    redirect('sessions.php');
                }
            }

            $assists = null;
            if ($assistsRaw !== '') {
                $assists = parse_int_or_null($assistsRaw);
                if ($assists === null) {
                    flash_set('error', 'Las assists de cada partida deben ser numericas.');
                    redirect('sessions.php');
                }
            }

            $normalizedResult = strtolower($result);

            $roundsFor = null;
            if ($roundsForRaw !== '') {
                $roundsFor = parse_int_or_null($roundsForRaw);
                if ($roundsFor === null) {
                    flash_set('error', 'Las rondas a favor deben ser numericas.');
                    redirect('sessions.php');
                }
            }

            $roundsAgainst = null;
            if ($roundsAgainstRaw !== '') {
                $roundsAgainst = parse_int_or_null($roundsAgainstRaw);
                if ($roundsAgainst === null) {
                    flash_set('error', 'Las rondas en contra deben ser numericas.');
                    redirect('sessions.php');
                }
            }

            if ($normalizedResult === '') {
                flash_set('error', 'Cada partida debe indicar win o loss.');
                redirect('sessions.php');
            }

            if (!in_array($normalizedResult, ['win', 'w', 'loss', 'lose', 'l'], true)) {
                flash_set('error', 'El resultado de la partida debe ser win o loss.');
                redirect('sessions.php');
            }

            if ($isCompetitiveMatch && ($roundsFor === null || $roundsAgainst === null)) {
                flash_set('error', 'Las partidas Ranked y Premier necesitan rondas a favor y en contra.');
                redirect('sessions.php');
            }

            $headshotPct = null;
            if ($headshotRaw !== '') {
                $headshotPct = parse_float_or_null($headshotRaw);
                if ($headshotPct === null) {
                    flash_set('error', 'El porcentaje de headshot debe ser numerico.');
                    redirect('sessions.php');
                }
            }

            $acs = null;
            if ($acsRaw !== '') {
                $acs = parse_float_or_null($acsRaw);
                if ($acs === null) {
                    flash_set('error', 'El ACS debe ser numerico.');
                    redirect('sessions.php');
                }
            }

            $kast = null;
            if ($kastRaw !== '') {
                $kast = parse_float_or_null($kastRaw);
                if ($kast === null) {
                    flash_set('error', 'El KAST debe ser numerico.');
                    redirect('sessions.php');
                }
            }

            if (!$isCompetitiveMatch) {
                $roundsFor = null;
                $roundsAgainst = null;
                $acs = null;
                $kast = null;
            }

            $deathsForKda = max(1, (int) $deaths);
            $kda = ((int) $kills + (int) $assists) / $deathsForKda;

            $matches[] = [
                'match_type' => $type,
                'kills' => $kills,
                'deaths' => $deaths,
                'assists' => $assists,
                'kda' => $kda,
                'headshot_pct' => $headshotPct,
                'rounds_for' => $roundsFor,
                'rounds_against' => $roundsAgainst,
                'acs' => $acs,
                'kast' => $kast,
                'score_points' => null,
                'match_result' => $normalizedResult,
                'notes' => null,
            ];

            $matchSnapshotRows[] = [
                'match_type' => $type,
                'map_name' => null,
                'kills' => $kills,
                'deaths' => $deaths,
                'assists' => $assists,
                'kda' => $kda,
                'headshot_pct' => $headshotPct,
                'rounds_for' => $roundsFor,
                'rounds_against' => $roundsAgainst,
                'acs' => $acs,
                'kast' => $kast,
                'score_points' => null,
                'match_result' => $normalizedResult,
                'notes' => null,
            ];
        }
    }

    if ($saveScope === 'routine' && !$routines) {
        flash_set('error', 'Añade al menos una rutina.');
        redirect('sessions.php');
    }

    if ($saveScope === 'matches' && !$matches) {
        flash_set('error', 'Añade al menos una partida.');
        redirect('sessions.php');
    }

    if ($saveScope === 'full' && !$routines && !$matches) {
        flash_set('error', 'Añade al menos una rutina o una partida.');
        redirect('sessions.php');
    }

    $dayExtraData = json_encode([
        'session_routine_name' => $sessionRoutineName,
        'benchmark' => $benchmark,
        'notes' => $notes,
        'save_scope' => $saveScope,
        'routines' => $routineSnapshotRows,
        'matches' => $matchSnapshotRows,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    try {
        $pdo->beginTransaction();

        if ($existingDay) {
            $dayId = (int) $existingDay['id'];

            $dayUpdateParts = ['day_name = :day_name', 'benchmark = :benchmark', 'notes = :notes', 'updated_at = CURRENT_TIMESTAMP'];
            $dayUpdateValues = [
                'id' => $dayId,
                'user_id' => $userId,
                'day_name' => $dayName,
                'benchmark' => $benchmark,
                'notes' => $notes,
            ];

            if (column_exists($pdo, 'training_days', 'benchmark_routine_item_id')) {
                $dayUpdateParts[] = 'benchmark_routine_item_id = :benchmark_routine_item_id';
                $dayUpdateValues['benchmark_routine_item_id'] = $benchmarkRoutineItemId;
            }

            if ($hasSessionRoutineNameColumn) {
                $dayUpdateParts[] = 'session_routine_name = :session_routine_name';
                $dayUpdateValues['session_routine_name'] = $sessionRoutineName;
            }

            if (column_exists($pdo, 'training_days', 'extra_data_json')) {
                $dayUpdateParts[] = 'extra_data_json = :extra_data_json';
                $dayUpdateValues['extra_data_json'] = $dayExtraData;
            }

            $updateDay = $pdo->prepare('UPDATE training_days SET ' . implode(', ', $dayUpdateParts) . ' WHERE id = :id AND user_id = :user_id');
            $updateDay->execute($dayUpdateValues);
        } else {
            $dayColumns = ['user_id', 'session_date', 'day_name', 'benchmark', 'notes'];
            $dayPlaceholders = [':user_id', ':session_date', ':day_name', ':benchmark', ':notes'];
            $dayValues = [
                'user_id' => $userId,
                'session_date' => $sessionDate,
                'day_name' => $dayName,
                'benchmark' => $benchmark,
                'notes' => $notes,
            ];

            if (column_exists($pdo, 'training_days', 'benchmark_routine_item_id')) {
                $dayColumns[] = 'benchmark_routine_item_id';
                $dayPlaceholders[] = ':benchmark_routine_item_id';
                $dayValues['benchmark_routine_item_id'] = $benchmarkRoutineItemId;
            }

            if ($hasSessionRoutineNameColumn) {
                $dayColumns[] = 'session_routine_name';
                $dayPlaceholders[] = ':session_routine_name';
                $dayValues['session_routine_name'] = $sessionRoutineName;
            }

            if (column_exists($pdo, 'training_days', 'extra_data_json')) {
                $dayColumns[] = 'extra_data_json';
                $dayPlaceholders[] = ':extra_data_json';
                $dayValues['extra_data_json'] = $dayExtraData;
            }

            $insertDay = $pdo->prepare('INSERT INTO training_days (' . implode(', ', $dayColumns) . ') VALUES (' . implode(', ', $dayPlaceholders) . ')');
            $insertDay->execute($dayValues);

            $dayId = (int) $pdo->lastInsertId();
        }

        if ($saveScope !== 'matches') {
            $pdo->prepare('DELETE FROM training_routines WHERE training_day_id = :day_id')->execute(['day_id' => $dayId]);

            if ($routines) {
                $insertRoutine = $pdo->prepare('INSERT INTO training_routines (training_day_id, user_routine_item_id, exercise_id, section_name, item_name, score_points, duration_minutes, accuracy_pct, notes, extra_data_json, sort_order) VALUES (:training_day_id, :user_routine_item_id, :exercise_id, :section_name, :item_name, :score_points, :duration_minutes, :accuracy_pct, :notes, :extra_data_json, :sort_order)');

                foreach ($routines as $order => $routine) {
                    $routineExtraData = json_encode([
                        'user_routine_item_id' => $routine['user_routine_item_id'],
                        'exercise_id' => $routine['exercise_id'],
                        'section_name' => $routine['section_name'],
                        'item_name' => $routine['item_name'],
                        'score_points' => $routine['score_points'],
                        'accuracy_pct' => $routine['accuracy_pct'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $insertRoutine->execute([
                        'training_day_id' => $dayId,
                        'user_routine_item_id' => $routine['user_routine_item_id'],
                        'exercise_id' => $routine['exercise_id'],
                        'section_name' => $routine['section_name'],
                        'item_name' => $routine['item_name'],
                        'score_points' => $routine['score_points'],
                        'duration_minutes' => null,
                        'accuracy_pct' => $routine['accuracy_pct'],
                        'notes' => null,
                        'extra_data_json' => $routineExtraData,
                        'sort_order' => $order,
                    ]);
                }
            }
        }

        if ($saveScope !== 'routine') {
            $pdo->prepare('DELETE FROM training_matches WHERE training_day_id = :day_id')->execute(['day_id' => $dayId]);

            if ($matches) {
                $matchColumns = ['training_day_id', 'match_type', 'map_name', 'kills', 'deaths', 'assists', 'kda', 'headshot_pct', 'score_points', 'match_result', 'notes', 'extra_data_json', 'sort_order'];
                $matchPlaceholders = [':training_day_id', ':match_type', ':map_name', ':kills', ':deaths', ':assists', ':kda', ':headshot_pct', ':score_points', ':match_result', ':notes', ':extra_data_json', ':sort_order'];

                if (column_exists($pdo, 'training_matches', 'rounds_for')) {
                    $matchColumns[] = 'rounds_for';
                    $matchPlaceholders[] = ':rounds_for';
                }

                if (column_exists($pdo, 'training_matches', 'rounds_against')) {
                    $matchColumns[] = 'rounds_against';
                    $matchPlaceholders[] = ':rounds_against';
                }

                if (column_exists($pdo, 'training_matches', 'acs')) {
                    $matchColumns[] = 'acs';
                    $matchPlaceholders[] = ':acs';
                }

                if (column_exists($pdo, 'training_matches', 'kast')) {
                    $matchColumns[] = 'kast';
                    $matchPlaceholders[] = ':kast';
                }

                $insertMatch = $pdo->prepare('INSERT INTO training_matches (' . implode(', ', $matchColumns) . ') VALUES (' . implode(', ', $matchPlaceholders) . ')');

                foreach ($matches as $order => $match) {
                    $matchExtraData = json_encode([
                        'match_type' => $match['match_type'],
                        'kills' => $match['kills'],
                        'deaths' => $match['deaths'],
                        'assists' => $match['assists'],
                        'kda' => $match['kda'],
                        'headshot_pct' => $match['headshot_pct'],
                        'rounds_for' => $match['rounds_for'],
                        'rounds_against' => $match['rounds_against'],
                        'acs' => $match['acs'],
                        'kast' => $match['kast'],
                        'match_result' => $match['match_result'],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    $matchValues = [
                        'training_day_id' => $dayId,
                        'match_type' => $match['match_type'],
                        'map_name' => null,
                        'kills' => $match['kills'],
                        'deaths' => $match['deaths'],
                        'assists' => $match['assists'],
                        'kda' => $match['kda'],
                        'headshot_pct' => $match['headshot_pct'],
                        'score_points' => null,
                        'match_result' => $match['match_result'],
                        'notes' => null,
                        'extra_data_json' => $matchExtraData,
                        'sort_order' => $order,
                        'rounds_for' => $match['rounds_for'],
                        'rounds_against' => $match['rounds_against'],
                        'acs' => $match['acs'],
                        'kast' => $match['kast'],
                    ];

                    $insertMatch->execute($matchValues);
                }
            }
        }

        $pdo->commit();
        flash_set('success', 'Sesion guardada correctamente.');
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message = (string) $throwable->getMessage();
        $looksLikeSchemaIssue = stripos($message, 'Unknown column') !== false
            || stripos($message, 'Base table') !== false
            || stripos($message, 'doesn\'t exist') !== false
            || stripos($message, 'SQLSTATE[42S') !== false;

        if ($looksLikeSchemaIssue) {
            flash_set('error', 'No se pudo guardar la sesion porque la base de datos parece desactualizada. Importa schema.sql en una base nueva y vuelve a probar.');
        } else {
            flash_set('error', 'No se pudo guardar la sesion. Revisa la base de datos.');
        }
    }

    redirect('sessions.php?saved=1');
}

function handle_delete_session(PDO $pdo): void
{
    require_login();

    $dayId = parse_int_or_null($_POST['day_id'] ?? null);
    if ($dayId === null) {
        flash_set('error', 'Sesion invalida.');
        redirect('dashboard.php');
    }

    $delete = $pdo->prepare('DELETE FROM training_days WHERE id = :id AND user_id = :user_id');
    $delete->execute([
        'id' => $dayId,
        'user_id' => (int) $_SESSION['user_id'],
    ]);

    flash_set('success', 'Sesion eliminada.');
    redirect('dashboard.php');
}

function handle_save_history_session(PDO $pdo): void
{
    require_login();

    $dayId = parse_int_or_null($_POST['day_id'] ?? null);
    $dayName = trim((string) ($_POST['day_name'] ?? ''));
    $sessionRoutineName = trim((string) ($_POST['session_routine_name'] ?? ''));
    $benchmark = trim((string) ($_POST['benchmark'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($dayId === null || $dayName === '' || $benchmark === '') {
        flash_set('error', 'Dia, benchmark y sesion son obligatorios para actualizar el historial.');
        redirect('history.php');
    }

    $check = $pdo->prepare('SELECT id FROM training_days WHERE id = :id AND user_id = :user_id LIMIT 1');
    $check->execute([
        'id' => $dayId,
        'user_id' => (int) $_SESSION['user_id'],
    ]);

    if (!$check->fetch()) {
        flash_set('error', 'No puedes editar una sesion que no te pertenece.');
        redirect('history.php');
    }

    $parts = ['day_name = :day_name', 'benchmark = :benchmark', 'notes = :notes', 'updated_at = CURRENT_TIMESTAMP'];
    $values = [
        'id' => $dayId,
        'user_id' => (int) $_SESSION['user_id'],
        'day_name' => $dayName,
        'benchmark' => $benchmark,
        'notes' => $notes,
    ];

    if (column_exists($pdo, 'training_days', 'session_routine_name')) {
        $parts[] = 'session_routine_name = :session_routine_name';
        $values['session_routine_name'] = $sessionRoutineName !== '' ? $sessionRoutineName : 'Rutina principal';
    }

    $statement = $pdo->prepare('UPDATE training_days SET ' . implode(', ', $parts) . ' WHERE id = :id AND user_id = :user_id');
    $statement->execute($values);

    flash_set('success', 'Sesion del historial actualizada.');
    redirect('history.php#session-' . (string) $dayId);
}

function handle_logout(): void
{
    session_unset();
    session_destroy();
    session_start();
    flash_set('success', 'Sesion cerrada.');
    redirect('index.php');
}
