-- Import this schema into a new or empty database.
-- It defines the daily log header plus routine and match child tables.

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(120) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_days (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    session_date DATE NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    benchmark VARCHAR(190) NOT NULL,
    notes TEXT NULL,
    extra_data_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_training_days_user_date (user_id, session_date),
    CONSTRAINT fk_training_days_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_routines (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    training_day_id INT UNSIGNED NOT NULL,
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
    KEY idx_training_routines_section (section_name),
    CONSTRAINT fk_training_routines_day
        FOREIGN KEY (training_day_id) REFERENCES training_days (id)
        ON DELETE CASCADE
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
