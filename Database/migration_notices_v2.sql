-- ============================================================
-- CampusHub Notices Migration V2
-- Adds category, priority, and expiry_date to notices table.
-- Safe to re-run.
-- ============================================================

ALTER TABLE `notices`
  ADD COLUMN IF NOT EXISTS `category` VARCHAR(50) NOT NULL DEFAULT 'general' AFTER `content`,
  ADD COLUMN IF NOT EXISTS `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal' AFTER `category`,
  ADD COLUMN IF NOT EXISTS `expiry_date` DATETIME NULL DEFAULT NULL AFTER `priority`;

-- Useful indexes for filtering and sorting notices.
ALTER TABLE `notices`
  ADD INDEX IF NOT EXISTS `idx_notices_category` (`category`),
  ADD INDEX IF NOT EXISTS `idx_notices_priority` (`priority`),
  ADD INDEX IF NOT EXISTS `idx_notices_expiry_date` (`expiry_date`);

-- Backfill: keep existing rows compatible with defaults.
UPDATE `notices`
SET
  `category` = COALESCE(NULLIF(`category`, ''), 'general'),
  `priority` = COALESCE(`priority`, 'normal')
WHERE 1;

SELECT 'Notices V2 migration complete.' AS result;
