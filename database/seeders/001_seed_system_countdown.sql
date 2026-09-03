-- BOARDING · Seed data
-- Run once after migrations. Safe to re-run (guarded with INSERT IGNORE / existence checks).

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ─────────────────────────────────────────────────────────────────
-- THE PERMANENT SYSTEM COUNTDOWN
-- 16 October 2030 · 09:00 AM Asia/Kolkata (= 2030-10-16 03:30:00 UTC)
-- user_id = NULL, is_system = 1
-- The uq_reminders_single_system unique key guarantees only one row
-- with is_system = 1 can ever exist, so this is safe to re-run.
-- ─────────────────────────────────────────────────────────────────
INSERT INTO reminders (
    id, user_id, category_id, wallpaper_id,
    title, description,
    target_datetime, timezone,
    display_format, priority, status,
    design, text_color, background_color, accent_color, font,
    completion_message,
    notify_enabled,
    recurrence_type,
    is_system,
    created_at, updated_at
)
SELECT
    '00000000-0000-4000-8000-000000000001',
    NULL, NULL, NULL,
    '16 October 2030',
    'The system countdown. Your next departure.',
    '2030-10-16 03:30:00', 'Asia/Kolkata',
    'dhms', 'critical', 'active',
    'cinema', NULL, NULL, NULL, NULL,
    'THE DAY HAS ARRIVED!',
    1,
    'none',
    1,
    NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM reminders WHERE is_system = 1
);

-- ─────────────────────────────────────────────────────────────────
-- BUILT-IN CATEGORIES (user_id NULL = visible to everyone)
-- NOTE: MySQL treats each NULL as distinct for unique-key purposes, so
-- (user_id, slug) uniqueness does NOT protect NULL-owned rows from
-- duplication on re-run. Fixed IDs + a guarded INSERT...SELECT are used
-- instead, mirroring the system-countdown pattern above.
-- ─────────────────────────────────────────────────────────────────
INSERT INTO categories (id, user_id, name, slug, icon, color, is_builtin)
SELECT * FROM (SELECT
    '00000000-0000-4000-8000-0000000000c1' AS id, NULL AS user_id, 'Personal'  AS name, 'personal'  AS slug, 'user'      AS icon, '#8A93A6' AS color, 1 AS is_builtin
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c2', NULL, 'Work',      'work',      'briefcase', '#3ED6C5', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c3', NULL, 'Study',     'study',     'book',      '#6C8CF5', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c4', NULL, 'Travel',    'travel',    'plane',     '#F2A93B', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c5', NULL, 'Fitness',   'fitness',   'activity',  '#4CD787', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c6', NULL, 'Birthday',  'birthday',  'gift',      '#E86FB0', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c7', NULL, 'Finance',   'finance',   'wallet',    '#D9A441', 1
    UNION ALL SELECT '00000000-0000-4000-8000-0000000000c8', NULL, 'Important', 'important', 'alert',     '#E5533D', 1
) AS builtin
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE is_builtin = 1);
