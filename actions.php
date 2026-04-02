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

    case 'delete_session':
        handle_delete_session($pdo);
        break;

    case 'logout':
        handle_logout();
        break;

    default:
        flash_set('error', 'Accion no valida.');
        redirect('index.php');
}

function handle_register(PDO $pdo): void
{
    $username = trim((string) ($_POST['register_username'] ?? ''));
    $email = trim((string) ($_POST['register_email'] ?? ''));
    $password = (string) ($_POST['register_password'] ?? '');
    $confirmPassword = (string) ($_POST['register_password_confirm'] ?? '');

    if (mb_strlen($username) < 3) {
        flash_set('error', 'El usuario debe tener al menos 3 caracteres.');
        redirect('index.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('error', 'El email no es valido.');
        redirect('index.php');
    }

    if (mb_strlen($password) < 8) {
        flash_set('error', 'La contrasena debe tener al menos 8 caracteres.');
        redirect('index.php');
    }

    if ($password !== $confirmPassword) {
        flash_set('error', 'Las contrasenas no coinciden.');
        redirect('index.php');
    }

    $statement = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
    $statement->execute([
        'username' => $username,
        'email' => $email,
    ]);

    if ($statement->fetch()) {
        flash_set('error', 'Ese usuario o email ya existe.');
        redirect('index.php');
    }

    $insert = $pdo->prepare(
        'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
    );
    $insert->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $pdo->lastInsertId();
    flash_set('success', 'Cuenta creada y sesion iniciada.');
    redirect('index.php');
}

function handle_login(PDO $pdo): void
{
    $identifier = trim((string) ($_POST['login_identifier'] ?? ''));
    $password = (string) ($_POST['login_password'] ?? '');

    if ($identifier === '' || $password === '') {
        flash_set('error', 'Completa usuario/email y contrasena.');
        redirect('index.php');
    }

    $statement = $pdo->prepare(
        'SELECT id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier LIMIT 1'
    );
    $statement->execute(['identifier' => $identifier]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        flash_set('error', 'Credenciales incorrectas.');
        redirect('index.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    flash_set('success', 'Bienvenido de nuevo.');
    redirect('index.php');
}

function handle_save_session(PDO $pdo): void
{
    require_login();

    $sessionDate = trim((string) ($_POST['session_date'] ?? ''));
    $dayName = trim((string) ($_POST['day_name'] ?? ''));
    $benchmark = trim((string) ($_POST['benchmark'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($sessionDate === '' || $dayName === '' || $benchmark === '') {
        flash_set('error', 'Fecha, dia y benchmark son obligatorios.');
        redirect('index.php');
    }

    $routineSections = array_values((array) ($_POST['routine_section'] ?? $_POST['exercise_section'] ?? []));
    $routineNames = array_values((array) ($_POST['routine_name'] ?? $_POST['exercise_name'] ?? []));
    $routinePoints = array_values((array) ($_POST['routine_points'] ?? $_POST['exercise_points'] ?? []));
    $routineMinutes = array_values((array) ($_POST['routine_minutes'] ?? []));
    $routineAccuracy = array_values((array) ($_POST['routine_accuracy'] ?? []));
    $routineNotes = array_values((array) ($_POST['routine_notes'] ?? []));

    $matchTypes = array_values((array) ($_POST['match_type'] ?? $_POST['dm_type'] ?? []));
    $matchMaps = array_values((array) ($_POST['match_map'] ?? []));
    $matchScores = array_values((array) ($_POST['match_score'] ?? []));
    $matchKills = array_values((array) ($_POST['match_kills'] ?? []));
    $matchDeaths = array_values((array) ($_POST['match_deaths'] ?? []));
    $matchAssists = array_values((array) ($_POST['match_assists'] ?? []));
    $matchKdas = array_values((array) ($_POST['match_kda'] ?? $_POST['dm_kda'] ?? []));
    $matchHs = array_values((array) ($_POST['match_hs'] ?? $_POST['dm_hs'] ?? []));
    $matchResults = array_values((array) ($_POST['match_result'] ?? []));
    $matchNotes = array_values((array) ($_POST['match_notes'] ?? []));

    $routines = [];
    $routineCount = max(
        count($routineSections),
        count($routineNames),
        count($routinePoints),
        count($routineMinutes),
        count($routineAccuracy),
        count($routineNotes)
    );

    for ($index = 0; $index < $routineCount; $index += 1) {
        $section = trim((string) ($routineSections[$index] ?? ''));
        $name = trim((string) ($routineNames[$index] ?? ''));
        $pointsRaw = trim((string) ($routinePoints[$index] ?? ''));
        $minutesRaw = trim((string) ($routineMinutes[$index] ?? ''));
        $accuracyRaw = trim((string) ($routineAccuracy[$index] ?? ''));
        $note = trim((string) ($routineNotes[$index] ?? ''));

        if ($section === '' && $name === '' && $pointsRaw === '' && $minutesRaw === '' && $accuracyRaw === '' && $note === '') {
            continue;
        }

        if ($section === '' || $name === '') {
            flash_set('error', 'Cada rutina debe tener seccion y nombre.');
            redirect('index.php');
        }

        $points = null;
        if ($pointsRaw !== '') {
            $points = parse_int_or_null($pointsRaw);
            if ($points === null) {
                flash_set('error', 'Los puntos de cada rutina deben ser numericos.');
                redirect('index.php');
            }
        }

        $minutes = null;
        if ($minutesRaw !== '') {
            $minutes = parse_float_or_null($minutesRaw);
            if ($minutes === null) {
                flash_set('error', 'Los minutos de cada rutina deben ser numericos.');
                redirect('index.php');
            }
        }

        $accuracy = null;
        if ($accuracyRaw !== '') {
            $accuracy = parse_float_or_null($accuracyRaw);
            if ($accuracy === null) {
                flash_set('error', 'El porcentaje de cada rutina debe ser numerico.');
                redirect('index.php');
            }
        }

        $routines[] = [
            'section_name' => $section,
            'item_name' => $name,
            'score_points' => $points,
            'duration_minutes' => $minutes,
            'accuracy_pct' => $accuracy,
            'notes' => $note !== '' ? $note : null,
        ];
    }

    $matches = [];
    $matchCount = max(
        count($matchTypes),
        count($matchMaps),
        count($matchScores),
        count($matchKills),
        count($matchDeaths),
        count($matchAssists),
        count($matchKdas),
        count($matchHs),
        count($matchResults),
        count($matchNotes)
    );

    for ($index = 0; $index < $matchCount; $index += 1) {
        $type = trim((string) ($matchTypes[$index] ?? ''));
        $map = trim((string) ($matchMaps[$index] ?? ''));
        $scoreRaw = trim((string) ($matchScores[$index] ?? ''));
        $killsRaw = trim((string) ($matchKills[$index] ?? ''));
        $deathsRaw = trim((string) ($matchDeaths[$index] ?? ''));
        $assistsRaw = trim((string) ($matchAssists[$index] ?? ''));
        $kdaRaw = trim((string) ($matchKdas[$index] ?? ''));
        $hsRaw = trim((string) ($matchHs[$index] ?? ''));
        $result = trim((string) ($matchResults[$index] ?? ''));
        $note = trim((string) ($matchNotes[$index] ?? ''));

        if ($type === '' && $map === '' && $scoreRaw === '' && $killsRaw === '' && $deathsRaw === '' && $assistsRaw === '' && $kdaRaw === '' && $hsRaw === '' && $result === '' && $note === '') {
            continue;
        }

        if ($type === '') {
            flash_set('error', 'Cada partida debe tener un tipo.');
            redirect('index.php');
        }

        $kills = null;
        if ($killsRaw !== '') {
            $kills = parse_int_or_null($killsRaw);
            if ($kills === null) {
                flash_set('error', 'Las kills de cada partida deben ser numericas.');
                redirect('index.php');
            }
        }

        $deaths = null;
        if ($deathsRaw !== '') {
            $deaths = parse_int_or_null($deathsRaw);
            if ($deaths === null) {
                flash_set('error', 'Las deaths de cada partida deben ser numericas.');
                redirect('index.php');
            }
        }

        $assists = null;
        if ($assistsRaw !== '') {
            $assists = parse_int_or_null($assistsRaw);
            if ($assists === null) {
                flash_set('error', 'Las assists de cada partida deben ser numericas.');
                redirect('index.php');
            }
        }

        $scorePoints = null;
        if ($scoreRaw !== '') {
            $scorePoints = parse_int_or_null($scoreRaw);
            if ($scorePoints === null) {
                flash_set('error', 'Los puntos de cada partida deben ser numericos.');
                redirect('index.php');
            }
        }

        $kda = null;
        if ($kdaRaw !== '') {
            $kda = parse_float_or_null($kdaRaw);
            if ($kda === null) {
                flash_set('error', 'El KDA de cada partida debe ser numerico.');
                redirect('index.php');
            }
        }

        $hs = null;
        if ($hsRaw !== '') {
            $hs = parse_float_or_null($hsRaw);
            if ($hs === null) {
                flash_set('error', 'El headshot % de cada partida debe ser numerico.');
                redirect('index.php');
            }
        }

        $matches[] = [
            'match_type' => $type,
            'map_name' => $map !== '' ? $map : null,
            'kills' => $kills,
            'deaths' => $deaths,
            'assists' => $assists,
            'kda' => $kda,
            'headshot_pct' => $hs,
            'score_points' => $scorePoints,
            'match_result' => $result !== '' ? $result : null,
            'notes' => $note !== '' ? $note : null,
        ];
    }

    if (!$routines && !$matches) {
        flash_set('error', 'Añade al menos una rutina o una partida.');
        redirect('index.php');
    }

    $userId = (int) $_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        $deleteExisting = $pdo->prepare('DELETE FROM training_days WHERE user_id = :user_id AND session_date = :session_date');
        $deleteExisting->execute([
            'user_id' => $userId,
            'session_date' => $sessionDate,
        ]);

        $insertDay = $pdo->prepare(
            'INSERT INTO training_days (user_id, session_date, day_name, benchmark, notes) VALUES (:user_id, :session_date, :day_name, :benchmark, :notes)'
        );
        $insertDay->execute([
            'user_id' => $userId,
            'session_date' => $sessionDate,
            'day_name' => $dayName,
            'benchmark' => $benchmark,
            'notes' => $notes,
        ]);

        $dayId = (int) $pdo->lastInsertId();

        if ($routines) {
            $insertRoutine = $pdo->prepare(
                'INSERT INTO training_routines (training_day_id, section_name, item_name, score_points, duration_minutes, accuracy_pct, notes, extra_data_json, sort_order) VALUES (:training_day_id, :section_name, :item_name, :score_points, :duration_minutes, :accuracy_pct, :notes, :extra_data_json, :sort_order)'
            );

            foreach ($routines as $order => $routine) {
                $insertRoutine->execute([
                    'training_day_id' => $dayId,
                    'section_name' => $routine['section_name'],
                    'item_name' => $routine['item_name'],
                    'score_points' => $routine['score_points'],
                    'duration_minutes' => $routine['duration_minutes'],
                    'accuracy_pct' => $routine['accuracy_pct'],
                    'notes' => $routine['notes'],
                    'extra_data_json' => null,
                    'sort_order' => $order,
                ]);
            }
        }

        if ($matches) {
            $insertMatch = $pdo->prepare(
                'INSERT INTO training_matches (training_day_id, match_type, map_name, kills, deaths, assists, kda, headshot_pct, score_points, match_result, notes, extra_data_json, sort_order) VALUES (:training_day_id, :match_type, :map_name, :kills, :deaths, :assists, :kda, :headshot_pct, :score_points, :match_result, :notes, :extra_data_json, :sort_order)'
            );

            foreach ($matches as $order => $match) {
                $insertMatch->execute([
                    'training_day_id' => $dayId,
                    'match_type' => $match['match_type'],
                    'map_name' => $match['map_name'],
                    'kills' => $match['kills'],
                    'deaths' => $match['deaths'],
                    'assists' => $match['assists'],
                    'kda' => $match['kda'],
                    'headshot_pct' => $match['headshot_pct'],
                    'score_points' => $match['score_points'],
                    'match_result' => $match['match_result'],
                    'notes' => $match['notes'],
                    'extra_data_json' => null,
                    'sort_order' => $order,
                ]);
            }
        }

        $pdo->commit();
        flash_set('success', 'Sesion guardada correctamente.');
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        flash_set('error', 'No se pudo guardar la sesion. Revisa la base de datos.');
    }

    redirect('index.php');
}

function handle_delete_session(PDO $pdo): void
{
    require_login();

    $dayId = parse_int_or_null($_POST['day_id'] ?? null);
    if ($dayId === null) {
        flash_set('error', 'Sesion invalida.');
        redirect('index.php');
    }

    $delete = $pdo->prepare('DELETE FROM training_days WHERE id = :id AND user_id = :user_id');
    $delete->execute([
        'id' => $dayId,
        'user_id' => (int) $_SESSION['user_id'],
    ]);

    flash_set('success', 'Sesion eliminada.');
    redirect('index.php');
}

function handle_logout(): void
{
    session_unset();
    session_destroy();
    session_start();
    flash_set('success', 'Sesion cerrada.');
    redirect('index.php');
}
