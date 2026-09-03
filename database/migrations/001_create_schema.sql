-- BOARDING · Database Schema
-- MySQL 8+, InnoDB, utf8mb4
-- All timestamps stored in UTC; converted for display based on user timezone.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ─────────────────────────────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE users (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,           -- UUID, never expose sequential IDs
    name                VARCHAR(120) NOT NULL,
    email               VARCHAR(190) NOT NULL,
    password_hash       VARCHAR(255) NOT NULL,
    profile_image_path  VARCHAR(255) NULL,
    timezone            VARCHAR(64)  NOT NULL DEFAULT 'Asia/Kolkata',
    theme               ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    default_design      VARCHAR(32)  NOT NULL DEFAULT 'classic',
    default_font        VARCHAR(64)  NOT NULL DEFAULT 'system',
    default_format      VARCHAR(32)  NOT NULL DEFAULT 'dhms',
    default_share_visibility ENUM('private','link','public') NOT NULL DEFAULT 'private',
    email_verified_at   DATETIME NULL,
    failed_login_count  INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until        DATETIME NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME NULL,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- PASSWORD RESET TOKENS
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE password_resets (
    id          CHAR(36)     NOT NULL PRIMARY KEY,
    user_id     CHAR(36)     NOT NULL,
    token_hash  CHAR(64)     NOT NULL,       -- sha256 of the random token; raw token never stored
    expires_at  DATETIME     NOT NULL,
    used_at     DATETIME     NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_password_resets_token (token_hash),
    KEY idx_password_resets_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- SESSIONS (server-side session store, backs PHP sessions)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE user_sessions (
    id            CHAR(64)  NOT NULL PRIMARY KEY,  -- session id
    user_id       CHAR(36)  NOT NULL,
    ip_address    VARCHAR(45) NULL,
    user_agent    VARCHAR(255) NULL,
    last_activity DATETIME NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_sessions_user (user_id),
    KEY idx_sessions_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- CATEGORIES
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE categories (
    id          CHAR(36)     NOT NULL PRIMARY KEY,
    user_id     CHAR(36)     NULL,              -- NULL = built-in category, visible to all
    name        VARCHAR(60)  NOT NULL,
    slug        VARCHAR(60)  NOT NULL,
    icon        VARCHAR(40)  NULL,
    color       VARCHAR(7)   NULL,
    is_builtin  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categories_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_categories_user_slug (user_id, slug),
    KEY idx_categories_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- TAGS
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE tags (
    id          CHAR(36)     NOT NULL PRIMARY KEY,
    user_id     CHAR(36)     NOT NULL,
    name        VARCHAR(40)  NOT NULL,
    slug        VARCHAR(40)  NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tags_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uq_tags_user_slug (user_id, slug),
    KEY idx_tags_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- WALLPAPERS
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE wallpapers (
    id              CHAR(36)     NOT NULL PRIMARY KEY,
    user_id         CHAR(36)     NOT NULL,
    original_name   VARCHAR(255) NULL,          -- stored for reference only, never trusted for paths
    stored_path     VARCHAR(255) NOT NULL,       -- random filename on disk
    mime_type       VARCHAR(60)  NOT NULL,
    width           INT UNSIGNED NULL,
    height          INT UNSIGNED NULL,
    size_bytes      INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallpapers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    KEY idx_wallpapers_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- REMINDERS  (the core entity — includes the global system countdown)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE reminders (
    id                  CHAR(36)     NOT NULL PRIMARY KEY,
    user_id             CHAR(36)     NULL,                  -- NULL for the system countdown
    category_id         CHAR(36)     NULL,
    wallpaper_id        CHAR(36)     NULL,

    title               VARCHAR(150) NOT NULL,
    description         TEXT NULL,

    target_datetime     DATETIME NOT NULL,       -- stored in UTC
    timezone            VARCHAR(64) NOT NULL DEFAULT 'Asia/Kolkata',  -- timezone reminder was authored in

    display_format      ENUM('dhms','hm','m','s') NOT NULL DEFAULT 'dhms',
    priority             ENUM('low','normal','high','critical') NOT NULL DEFAULT 'normal',
    status              ENUM('active','completed') NOT NULL DEFAULT 'active',

    design              VARCHAR(32) NOT NULL DEFAULT 'classic',
    text_color          VARCHAR(7)  NULL,
    background_color    VARCHAR(7)  NULL,
    accent_color        VARCHAR(7)  NULL,
    font                VARCHAR(64) NULL,

    wallpaper_fit        ENUM('cover','contain','tile') NOT NULL DEFAULT 'cover',
    wallpaper_position    VARCHAR(20) NOT NULL DEFAULT 'center',
    wallpaper_brightness INT NOT NULL DEFAULT 100,   -- percent
    wallpaper_opacity    INT NOT NULL DEFAULT 100,   -- percent
    wallpaper_blur       INT NOT NULL DEFAULT 0,      -- px
    wallpaper_overlay    VARCHAR(7)  NULL,             -- dark overlay hex, applied with alpha

    completion_message  VARCHAR(255) NULL,

    notify_enabled      TINYINT(1)  NOT NULL DEFAULT 1,

    recurrence_type     ENUM('none','daily','weekly','monthly','yearly','custom') NOT NULL DEFAULT 'none',
    recurrence_rule     VARCHAR(255) NULL,   -- RFC5545-style RRULE fragment; expanded at read-time, not duplicated
    recurrence_start    DATE NULL,
    recurrence_end      DATE NULL,

    is_system           TINYINT(1) NOT NULL DEFAULT 0,

    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at        DATETIME NULL,
    deleted_at           DATETIME NULL,       -- trash; NULL = not trashed
    purge_at            DATETIME NULL,       -- when trash auto-purges permanently

    CONSTRAINT fk_reminders_user       FOREIGN KEY (user_id)      REFERENCES users(id)       ON DELETE CASCADE,
    CONSTRAINT fk_reminders_category   FOREIGN KEY (category_id)  REFERENCES categories(id)  ON DELETE SET NULL,
    CONSTRAINT fk_reminders_wallpaper  FOREIGN KEY (wallpaper_id) REFERENCES wallpapers(id)  ON DELETE SET NULL,

    KEY idx_reminders_user (user_id),
    KEY idx_reminders_target (target_datetime),
    KEY idx_reminders_status (status),
    KEY idx_reminders_category (category_id),
    KEY idx_reminders_created (created_at),
    KEY idx_reminders_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enforce "only one system countdown can ever exist" at the schema level:
-- a generated column that is always 1 for system rows, made unique.
ALTER TABLE reminders
    ADD COLUMN system_lock TINYINT AS (IF(is_system = 1, 1, NULL)) STORED,
    ADD UNIQUE KEY uq_reminders_single_system (system_lock);

-- ─────────────────────────────────────────────────────────────────
-- REMINDER ⇄ TAGS (many-to-many)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE reminder_tags (
    reminder_id CHAR(36) NOT NULL,
    tag_id      CHAR(36) NOT NULL,
    PRIMARY KEY (reminder_id, tag_id),
    CONSTRAINT fk_reminder_tags_reminder FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE,
    CONSTRAINT fk_reminder_tags_tag      FOREIGN KEY (tag_id)      REFERENCES tags(id)      ON DELETE CASCADE,
    KEY idx_reminder_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- REMINDER NOTIFICATIONS (server-side schedule, processed by cron)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE reminder_notifications (
    id              CHAR(36)   NOT NULL PRIMARY KEY,
    reminder_id     CHAR(36)   NOT NULL,
    offset_seconds  INT        NOT NULL DEFAULT 0,   -- seconds before target_datetime; 0 = at event time
    channel         ENUM('browser','email','push') NOT NULL,
    scheduled_at    DATETIME   NOT NULL,             -- precomputed target_datetime - offset_seconds
    sent_at         DATETIME   NULL,
    status          ENUM('pending','sent','failed','skipped') NOT NULL DEFAULT 'pending',
    attempts        INT UNSIGNED NOT NULL DEFAULT 0,
    last_error      VARCHAR(255) NULL,
    created_at      DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reminder_notifications_reminder FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE,
    KEY idx_notifications_scheduled (scheduled_at),
    KEY idx_notifications_status (status),
    KEY idx_notifications_reminder (reminder_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- NOTIFICATION PREFERENCES (per user, per channel)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE notification_preferences (
    user_id           CHAR(36) NOT NULL PRIMARY KEY,
    browser_enabled   TINYINT(1) NOT NULL DEFAULT 1,
    email_enabled     TINYINT(1) NOT NULL DEFAULT 0,
    push_enabled      TINYINT(1) NOT NULL DEFAULT 0,
    push_subscription TEXT NULL,   -- JSON Web Push subscription object, when push is configured
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_prefs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- REMINDER SHARES
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE reminder_shares (
    id             CHAR(36)     NOT NULL PRIMARY KEY,
    reminder_id    CHAR(36)     NOT NULL,
    share_token    CHAR(43)     NOT NULL,     -- 32 random bytes, base64url-encoded
    visibility     ENUM('private','link','public') NOT NULL DEFAULT 'link',
    view_count     INT UNSIGNED NOT NULL DEFAULT 0,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_at     DATETIME     NULL,
    CONSTRAINT fk_reminder_shares_reminder FOREIGN KEY (reminder_id) REFERENCES reminders(id) ON DELETE CASCADE,
    UNIQUE KEY uq_reminder_shares_token (share_token),
    KEY idx_reminder_shares_reminder (reminder_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- AUDIT LOGS
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id     CHAR(36)     NULL,
    event       VARCHAR(60)  NOT NULL,        -- e.g. 'reminder.created', 'auth.login'
    subject_type VARCHAR(40) NULL,
    subject_id  CHAR(36)     NULL,
    ip_address  VARCHAR(45)  NULL,
    metadata    JSON         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user (user_id),
    KEY idx_audit_event (event),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────
-- RATE LIMIT ATTEMPTS (simple DB-backed limiter; no external infra required)
-- ─────────────────────────────────────────────────────────────────
CREATE TABLE rate_limit_attempts (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bucket_key  VARCHAR(150) NOT NULL,   -- e.g. 'login:ip:1.2.3.4' or 'login:email:foo@bar.com'
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_rate_limit_bucket_time (bucket_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
