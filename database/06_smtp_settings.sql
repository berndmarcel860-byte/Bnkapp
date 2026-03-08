-- =============================================================================
-- BnkApp — Migration 06: SMTP Settings
--
-- Creates the smtp_settings table which stores the active mail transport
-- configuration.  Only one row is ever active at a time (id = 1 is the
-- canonical row; the admin UI will always UPDATE that row).
-- =============================================================================

CREATE TABLE IF NOT EXISTS smtp_settings (
    id              INT UNSIGNED     NOT NULL AUTO_INCREMENT,

    -- Connection
    host            VARCHAR(255)     NOT NULL DEFAULT '',
    port            SMALLINT UNSIGNED NOT NULL DEFAULT 587,
    encryption      ENUM('tls','ssl','none') NOT NULL DEFAULT 'tls',

    -- Auth
    username        VARCHAR(255)     NOT NULL DEFAULT '',
    password        VARCHAR(512)     NOT NULL DEFAULT '',

    -- Sender identity
    from_address    VARCHAR(255)     NOT NULL DEFAULT 'noreply@example.com',
    from_name       VARCHAR(100)     NOT NULL DEFAULT 'BnkApp',

    -- Test / status
    last_tested_at  DATETIME         NULL,
    last_test_ok    TINYINT(1)       NULL COMMENT '1 = success, 0 = failure, NULL = never tested',
    last_test_error TEXT             NULL,

    updated_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ensure the canonical settings row exists so the admin UI can always UPDATE it
INSERT IGNORE INTO smtp_settings (id, host, port, encryption, username, password, from_address, from_name)
VALUES (1, '', 587, 'tls', '', '', 'noreply@example.com', 'BnkApp');
