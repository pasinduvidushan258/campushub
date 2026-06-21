-- ============================================================
-- CampusHub Full Fix Migration
-- Run once against your campushub_db database.
-- Safe to re-run (uses IF NOT EXISTS / IGNORE patterns).
-- ============================================================

-- 1. Add ticket_booking_url column if missing
ALTER TABLE `events`
    ADD COLUMN IF NOT EXISTS `ticket_booking_url` VARCHAR(500) DEFAULT NULL
    AFTER `requires_ticket`;

-- 2. Ensure UNIQUE constraints exist to prevent duplicate likes/saves
--    (These silently no-op if the index already exists with the same name.)
ALTER TABLE `event_likes`
    ADD UNIQUE IF NOT EXISTS `uniq_user_event` (`user_id`, `event_id`);

ALTER TABLE `saved_events`
    ADD UNIQUE IF NOT EXISTS `uniq_user_event` (`user_id`, `event_id`);

-- 3. Make sure created_at exists on both tables
ALTER TABLE `event_likes`
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

ALTER TABLE `saved_events`
    ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 4. Verify events table has all columns needed by create_event.php
ALTER TABLE `events`
    ADD COLUMN IF NOT EXISTS `event_mode` ENUM('physical','online','hybrid') DEFAULT 'physical' AFTER `registration_link`,
    ADD COLUMN IF NOT EXISTS `requires_ticket` TINYINT(1) NOT NULL DEFAULT 0 AFTER `event_mode`,
    ADD COLUMN IF NOT EXISTS `ticket_booking_url` VARCHAR(500) DEFAULT NULL AFTER `requires_ticket`;

-- done
SELECT 'Migration complete.' AS result;
