-- Import this schema into a new or empty database.
-- It defines the daily log header plus routine and match child tables.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS training_matches;
DROP TABLE IF EXISTS training_routines;
DROP TABLE IF EXISTS training_days;
DROP TABLE IF EXISTS user_routine_items;
DROP TABLE IF EXISTS training_exercises;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL DEFAULT NULL,
    email_verification_token_hash CHAR(64) NULL,
    email_verification_sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_exercises (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    platform VARCHAR(40) NOT NULL,
    exercise_name VARCHAR(190) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_training_exercises_platform_name (platform, exercise_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_routine_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    routine_name VARCHAR(120) NOT NULL DEFAULT 'Rutina principal',
    exercise_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    repetitions INT UNSIGNED NOT NULL DEFAULT 1,
    target_minutes DECIMAL(6,2) NULL,
    target_accuracy DECIMAL(5,2) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_routine_user_name_exercise (user_id, routine_name, exercise_id),
    KEY idx_user_routine_user (user_id),
    KEY idx_user_routine_name (routine_name),
    KEY idx_user_routine_exercise (exercise_id),
    CONSTRAINT fk_user_routine_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_user_routine_exercise
        FOREIGN KEY (exercise_id) REFERENCES training_exercises (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_days (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    session_routine_name VARCHAR(120) NULL,
    benchmark_routine_item_id INT UNSIGNED NULL,
    benchmark VARCHAR(190) NOT NULL,
    notes TEXT NULL,
    extra_data_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_training_days_user_date (user_id, session_date),
    KEY idx_training_days_benchmark_item (benchmark_routine_item_id),
    CONSTRAINT fk_training_days_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_training_days_benchmark_item
        FOREIGN KEY (benchmark_routine_item_id) REFERENCES user_routine_items (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_routines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    training_day_id INT UNSIGNED NOT NULL,
    user_routine_item_id INT UNSIGNED NULL,
    exercise_id INT UNSIGNED NULL,
    section_name VARCHAR(50) NOT NULL,
    item_name VARCHAR(190) NOT NULL,
    score_points INT NULL,
    duration_minutes DECIMAL(6,2) NULL,
    accuracy_pct DECIMAL(5,2) NULL,
    notes TEXT NULL,
    extra_data_json LONGTEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_training_routines_day (training_day_id),
    KEY idx_training_routines_user_item (user_routine_item_id),
    KEY idx_training_routines_exercise (exercise_id),
    KEY idx_training_routines_section (section_name),
    CONSTRAINT fk_training_routines_day
        FOREIGN KEY (training_day_id) REFERENCES training_days (id)
        ON DELETE CASCADE
    ,CONSTRAINT fk_training_routines_user_item
        FOREIGN KEY (user_routine_item_id) REFERENCES user_routine_items (id)
        ON DELETE SET NULL
    ,CONSTRAINT fk_training_routines_exercise
        FOREIGN KEY (exercise_id) REFERENCES training_exercises (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_matches (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    training_day_id INT UNSIGNED NOT NULL,
    match_type VARCHAR(40) NOT NULL,
    map_name VARCHAR(80) NULL,
    kills SMALLINT UNSIGNED NULL,
    deaths SMALLINT UNSIGNED NULL,
    assists SMALLINT UNSIGNED NULL,
    kda DECIMAL(5,2) NULL,
    headshot_pct DECIMAL(5,2) NULL,
    rounds_for SMALLINT UNSIGNED NULL,
    rounds_against SMALLINT UNSIGNED NULL,
    acs DECIMAL(6,2) NULL,
    kast DECIMAL(5,2) NULL,
    score_points INT NULL,
    match_result VARCHAR(20) NULL,
    notes TEXT NULL,
    extra_data_json LONGTEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_training_matches_day (training_day_id),
    KEY idx_training_matches_type (match_type),
    CONSTRAINT fk_training_matches_day
        FOREIGN KEY (training_day_id) REFERENCES training_days (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
