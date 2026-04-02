<?php
declare(strict_types=1);

function fetch_sessions(PDO $pdo, int $userId): array
{
    $hasBenchmarkRoutineItemId = column_exists($pdo, 'training_days', 'benchmark_routine_item_id');
    $hasMatchRounds = column_exists($pdo, 'training_matches', 'rounds_for') && column_exists($pdo, 'training_matches', 'rounds_against');
    $hasMatchHeadshot = column_exists($pdo, 'training_matches', 'headshot_pct');
    $hasMatchAcs = column_exists($pdo, 'training_matches', 'acs');
    $hasMatchKast = column_exists($pdo, 'training_matches', 'kast');

    $benchmarkRoutineItemIdSelect = $hasBenchmarkRoutineItemId ? 'benchmark_routine_item_id' : 'NULL AS benchmark_routine_item_id';
    $matchRoundsForSelect = $hasMatchRounds ? 'rounds_for' : 'NULL AS rounds_for';
    $matchRoundsAgainstSelect = $hasMatchRounds ? 'rounds_against' : 'NULL AS rounds_against';
    $matchHeadshotSelect = $hasMatchHeadshot ? 'headshot_pct' : 'NULL AS headshot_pct';
    $matchAcsSelect = $hasMatchAcs ? 'acs' : 'NULL AS acs';
    $matchKastSelect = $hasMatchKast ? 'kast' : 'NULL AS kast';

    $sessionStatement = $pdo->prepare(
        'SELECT id, session_date, day_name, ' . $benchmarkRoutineItemIdSelect . ', benchmark, notes, created_at, updated_at
         FROM training_days
         WHERE user_id = :user_id
         ORDER BY session_date DESC, id DESC'
    );
    $sessionStatement->execute(['user_id' => $userId]);
    $sessions = $sessionStatement->fetchAll();

    $exerciseStatement = $pdo->prepare(
        'SELECT user_routine_item_id, exercise_id, section_name, item_name, score_points, duration_minutes, accuracy_pct, notes, sort_order, ' . (column_exists($pdo, 'training_routines', 'repetitions') ? 'repetitions' : '1 AS repetitions') . '
         FROM training_routines
         WHERE training_day_id = :day_id
         ORDER BY sort_order ASC, id ASC'
    );

    $matchStatement = $pdo->prepare(
        'SELECT match_type, map_name, kills, deaths, assists, kda, ' . $matchHeadshotSelect . ', ' . $matchRoundsForSelect . ', ' . $matchRoundsAgainstSelect . ', ' . $matchAcsSelect . ', ' . $matchKastSelect . ', score_points, match_result, notes, sort_order
         FROM training_matches
         WHERE training_day_id = :day_id
         ORDER BY sort_order ASC, id ASC'
    );

    foreach ($sessions as &$session) {
        $dayId = (int) $session['id'];

        $exerciseStatement->execute(['day_id' => $dayId]);
        $routines = $exerciseStatement->fetchAll();

        $matchStatement->execute(['day_id' => $dayId]);
        $matches = $matchStatement->fetchAll();

        $session['routines'] = $routines;
        $session['matches'] = $matches;
        $session['total_points'] = sum_points($routines, 'score_points') + sum_points($matches, 'score_points');
        $session['avg_kda'] = $matches ? average_from_rows($matches, 'kda') : null;
        $session['avg_hs'] = $matches ? average_from_rows($matches, 'headshot_pct') : null;
        $session['avg_kast'] = $matches ? average_from_rows($matches, 'kast') : null;
        $session['match_count'] = count($matches);
        $session['win_count'] = 0;
        $session['loss_count'] = 0;

        foreach ($matches as $match) {
            $result = strtolower(trim((string) ($match['match_result'] ?? '')));
            if ($result === 'win' || $result === 'w') {
                $session['win_count'] += 1;
            }

            if ($result === 'loss' || $result === 'lose' || $result === 'l') {
                $session['loss_count'] += 1;
            }
        }
    }
    unset($session);

    return $sessions;
}

function fetch_exercise_catalog(PDO $pdo): array
{
    $statement = $pdo->query(
        'SELECT id, platform, exercise_name, notes
         FROM training_exercises
         ORDER BY platform ASC, exercise_name ASC'
    );

    return $statement ? $statement->fetchAll() : [];
}

function fetch_user_routine_items(PDO $pdo, int $userId): array
{
    $hasRepetitions = column_exists($pdo, 'user_routine_items', 'repetitions');

    $statement = $pdo->prepare(
        'SELECT uri.id, uri.user_id, uri.exercise_id, uri.sort_order, ' . ($hasRepetitions ? 'uri.repetitions' : '1 AS repetitions') . ', uri.target_minutes, uri.target_accuracy, uri.notes,
                te.platform, te.exercise_name, te.notes AS exercise_notes
         FROM user_routine_items uri
         INNER JOIN training_exercises te ON te.id = uri.exercise_id
         WHERE uri.user_id = :user_id
         ORDER BY uri.sort_order ASC, uri.id ASC'
    );
    $statement->execute(['user_id' => $userId]);

    return $statement->fetchAll();
}

function calculate_stats(array $sessions): array
{
    $kdas = [];
    $kasts = [];
    $bestPoints = 0;

    foreach ($sessions as $session) {
        $bestPoints = max($bestPoints, (int) ($session['total_points'] ?? 0));

        foreach ($session['matches'] as $match) {
            if ($match['kda'] !== null) {
                $kdas[] = (float) $match['kda'];
            }

            if ($match['kast'] !== null) {
                $kasts[] = (float) $match['kast'];
            }
        }
    }

    $averageKda = $kdas ? array_sum($kdas) / count($kdas) : 0.0;
    $averageKast = $kasts ? array_sum($kasts) / count($kasts) : 0.0;
    $wins = 0;
    $losses = 0;

    foreach ($sessions as $session) {
        $wins += (int) ($session['win_count'] ?? 0);
        $losses += (int) ($session['loss_count'] ?? 0);
    }

    return [
        'session_count' => count($sessions),
        'average_kda' => $averageKda,
        'average_kast' => $averageKast,
        'best_points' => $bestPoints,
        'win_count' => $wins,
        'loss_count' => $losses,
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

function sum_points(array $rows, string $key = 'points'): int
{
    $total = 0;

    foreach ($rows as $row) {
        $value = $row[$key] ?? null;
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

function build_dashboard_catalog(array $sessions): array
{
    $recentSessions = [];
    $benchmarkOptions = [];
    $routineTemplates = [];
    $mapOptions = [];

    $seenBenchmarks = [];
    $seenRoutines = [];
    $seenMaps = [];

    foreach ($sessions as $session) {
        $benchmark = trim((string) ($session['benchmark'] ?? ''));
        if ($benchmark !== '' && !isset($seenBenchmarks[$benchmark])) {
            $seenBenchmarks[$benchmark] = true;
            $benchmarkOptions[] = $benchmark;
        }

        if (count($recentSessions) < 8) {
            $recentSessions[] = [
                'id' => (int) $session['id'],
                'session_date' => (string) $session['session_date'],
                'date_label' => date('d/m/Y', strtotime((string) $session['session_date'])) ?: (string) $session['session_date'],
                'day_label' => day_label_es((string) $session['day_name']),
                'benchmark' => $benchmark,
                'total_points' => (int) ($session['total_points'] ?? 0),
            ];
        }

        foreach ($session['routines'] as $routine) {
            $sectionName = trim((string) ($routine['section_name'] ?? ''));
            $itemName = trim((string) ($routine['item_name'] ?? ''));
            $key = $sectionName . '|' . $itemName;

            if ($sectionName !== '' && $itemName !== '' && !isset($seenRoutines[$key])) {
                $seenRoutines[$key] = true;
                $routineTemplates[] = [
                    'section_name' => $sectionName,
                    'item_name' => $itemName,
                    'score_points' => $routine['score_points'] ?? null,
                    'duration_minutes' => $routine['duration_minutes'] ?? null,
                    'accuracy_pct' => $routine['accuracy_pct'] ?? null,
                ];
            }
        }

        foreach ($session['matches'] as $match) {
            $mapName = trim((string) ($match['map_name'] ?? ''));

            if ($mapName !== '' && !isset($seenMaps[$mapName])) {
                $seenMaps[$mapName] = true;
                $mapOptions[] = $mapName;
            }
        }
    }

    return [
        'recent_sessions' => $recentSessions,
        'benchmark_options' => $benchmarkOptions,
        'routine_templates' => array_slice($routineTemplates, 0, 12),
        'map_options' => array_slice($mapOptions, 0, 12),
        'last_session' => $sessions[0] ?? null,
    ];
}
