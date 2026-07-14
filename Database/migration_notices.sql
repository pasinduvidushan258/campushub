-- ============================================================
-- CampusHub Notices Migration
-- Creates/repairs notices table for notices.php + post_notice.php
-- Safe to re-run.
-- ============================================================

CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `author_type` ENUM('admin','society') NOT NULL,
  `author_id` INT(11) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notices_created_at` (`created_at`),
  KEY `idx_notices_author` (`author_type`, `author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- If the table already exists but is missing columns, patch it.
ALTER TABLE `notices`
  ADD COLUMN IF NOT EXISTS `author_type` ENUM('admin','society') NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `author_id` INT(11) NOT NULL AFTER `author_type`,
  ADD COLUMN IF NOT EXISTS `content` TEXT NOT NULL AFTER `author_id`,
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `content`;

-- Ensure indexes exist for feed ordering and author lookups.
ALTER TABLE `notices`
  ADD INDEX IF NOT EXISTS `idx_notices_created_at` (`created_at`),
  ADD INDEX IF NOT EXISTS `idx_notices_author` (`author_type`, `author_id`);

SELECT 'Notices migration complete.' AS result;
