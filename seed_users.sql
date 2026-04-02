-- Seed completo de demostracion para importar directamente en MySQL.
-- Crea catalogo de ejercicios, rutina personal por usuario y sesiones ya enlazadas por IDs.

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM training_matches;
DELETE FROM training_routines;
DELETE FROM training_days;
DELETE FROM user_routine_items;
DELETE FROM training_exercises;
DELETE FROM users;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO training_exercises (id, platform, exercise_name, notes) VALUES
  (1, 'KovaaK''s', '1w6targets small', 'Calentamiento de flicks cortos'),
  (2, 'KovaaK''s', 'FuglaaXYLong Strafes', 'Tracking largo y controlado'),
  (3, 'KovaaK''s', 'Smoothbot', 'Seguimiento suave'),
  (4, 'Aim Lab', 'Microshot Speed', 'Velocidad y precision'),
  (5, 'Aim Lab', 'Sixshot Precision', 'Precision continua'),
  (6, 'Range', 'Bot Flick Drill', 'Flicks a bots fijos'),
  (7, 'Warmup', 'Jiggle Peek Control', 'Control de peek y reset'),
  (8, 'Valorant', 'Entry Drill', 'Entrada de site y preaim');

INSERT INTO users (id, username, email, password_hash) VALUES
  (1, 'demo', 'demo@valorant.local', 'sha256:fec0b57baff292f4c8b1713aa7f397cd7aeac6d004e4ad18e2f7f9edec36ce4a'),
  (2, 'coach', 'coach@valorant.local', 'sha256:11037e189512d8538867e4fbf877c26f526ce3e4bc040dd24b8226715fc206fa'),
  (3, 'aimlab', 'aimlab@valorant.local', 'sha256:e9882c9936292b549d47d37e61d6c19e6b08f59a6c75ec19917546a54ad02b82');

INSERT INTO user_routine_items (id, user_id, exercise_id, sort_order, target_minutes, target_accuracy, notes) VALUES
  (1, 1, 1, 0, 12.5, 85.0, 'Calentamiento base'),
  (2, 1, 2, 1, 14.0, 84.0, 'Tracking largo'),
  (3, 1, 4, 2, 10.0, 86.5, 'Rapidez y consistencia'),
  (4, 1, 6, 3, 8.0, 88.0, 'Bot flick corto'),
  (5, 1, 7, 4, 6.0, 80.0, 'Control de entrada'),
  (6, 2, 3, 0, 15.0, 87.0, 'Tracking suave'),
  (7, 2, 6, 1, 8.0, 82.5, 'Flick simple'),
  (8, 2, 8, 2, 7.0, 78.0, 'Entrada y preaim'),
  (9, 3, 4, 0, 9.0, 88.0, 'Bloque rapido'),
  (10, 3, 5, 1, 11.0, 89.0, 'Precision avanzada'),
  (11, 3, 8, 2, 7.5, 81.0, 'Entry drill');

INSERT INTO training_days (id, user_id, session_date, day_name, benchmark, notes) VALUES
  (1, 1, '2026-03-24', 'Tuesday', 'Voltaic Smoothness Guide', 'Sesion centrada en calentamiento y control.'),
  (2, 1, '2026-03-25', 'Wednesday', 'GON MACHINE for VALO v2', 'Mejor sensacion en flicks cortos.'),
  (3, 1, '2026-03-27', 'Friday', 'KovaaK''s Precision Block', 'Dia mas fuerte en consistencia.'),
  (4, 2, '2026-03-24', 'Tuesday', 'Range Routine', 'Trabajo de apuntado simple y estable.'),
  (5, 2, '2026-03-26', 'Thursday', 'Premade Duel Pack', 'Mas errores en entradas.'),
  (6, 3, '2026-03-25', 'Wednesday', 'Aim Lab Speed Pack', 'Sesion corta para ver progreso rapido.');

INSERT INTO training_routines (training_day_id, user_routine_item_id, exercise_id, section_name, item_name, score_points, duration_minutes, accuracy_pct, notes, extra_data_json, sort_order) VALUES
  (1, 1, 1, 'KovaaK''s', '1w6targets small', 12840, 12.5, 84.2, 'Calentamiento limpio', NULL, 0),
  (1, 3, 4, 'Aim Lab', 'Microshot Speed', 9140, 10.0, 81.1, 'Primer bloque', NULL, 1),
  (2, 2, 2, 'KovaaK''s', 'FuglaaXYLong Strafes', 14620, 13.0, 86.4, 'Mejor tracking', NULL, 0),
  (2, 4, 6, 'Range', 'Bot Flick Drill', 7200, 8.0, 88.0, 'Muy estable', NULL, 1),
  (2, 5, 7, 'Warmup', 'Jiggle Peek Control', 5200, 6.0, 79.4, 'Peor al final', NULL, 2),
  (3, 3, 4, 'Aim Lab', 'Microshot Speed', 15110, 15.0, 88.7, 'Dia mas fuerte', NULL, 0),
  (3, 5, 7, 'Warmup', 'Jiggle Peek Control', 10330, 11.0, 87.3, 'Buena constancia', NULL, 1),
  (4, 6, 3, 'KovaaK''s', 'Smoothbot', 6400, 8.5, 79.0, 'Trabajo basico', NULL, 0),
  (4, 7, 6, 'Range', 'Bot Flick Drill', 5400, 7.0, 77.5, 'Lento pero limpio', NULL, 1),
  (5, 6, 3, 'KovaaK''s', 'Smoothbot', 11870, 12.0, 82.1, 'Mas irregular', NULL, 0),
  (5, 8, 8, 'Valorant', 'Entry Drill', 7600, 9.5, 78.8, 'Necesita mejorar', NULL, 1),
  (6, 9, 4, 'Aim Lab', 'Microshot Speed', 9800, 10.0, 85.0, 'Sesion corta pero util', NULL, 0),
  (6, 11, 8, 'Valorant', 'Entry Drill', 4300, 5.5, 80.0, 'Sesion corta', NULL, 1);

INSERT INTO training_matches (training_day_id, match_type, map_name, kills, deaths, assists, kda, headshot_pct, score_points, match_result, notes, extra_data_json, sort_order) VALUES
  (1, 'Deathmatch', 'Ascent', 23, 17, 4, 1.59, 27.50, 2400, 'W', 'Respeto mejor el crosshair placement', NULL, 0),
  (1, 'Ranked', 'Bind', 18, 19, 7, 1.32, 23.80, 1800, 'L', 'Se me fue el mid game', NULL, 1),
  (2, 'Deathmatch', 'Haven', 26, 15, 3, 1.73, 29.10, 2600, 'W', 'Mejor arranque', NULL, 0),
  (2, 'Ranked', 'Split', 21, 20, 6, 1.35, 25.00, 2100, 'OT', 'Bien en defensa, flojo en ataque', NULL, 1),
  (3, 'Deathmatch', 'Lotus', 28, 14, 2, 2.00, 31.20, 2800, 'W', 'Muy solido', NULL, 0),
  (4, 'Unrated', 'Pearl', 16, 18, 5, 1.17, 22.40, 1500, 'L', 'Demasiado pasivo', NULL, 0),
  (5, 'Ranked', 'Sunset', 20, 22, 4, 1.09, 24.60, 2000, 'L', 'Mal timing en las entradas', NULL, 0),
  (5, 'Deathmatch', 'Breeze', 24, 16, 3, 1.50, 28.10, 2300, 'W', 'Mejor lectura de duelos largos', NULL, 1),
  (6, 'Deathmatch', 'Ascent', 22, 18, 4, 1.44, 26.90, 1900, 'W', 'Sesion corta pero util', NULL, 0);
