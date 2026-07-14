-- ============================================================
-- CampusHub Notifications Migration
-- Creates notifications storage and login fingerprint tracking.
-- Safe to re-run on MySQL 8+/MariaDB 10.4+.
-- ============================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `recipient_user_id` INT NOT NULL,
  `actor_user_id` INT NULL,
  `actor_society_id` INT NULL,
  `type` VARCHAR(80) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `entity_type` VARCHAR(40) NULL,
  `entity_id` INT NULL,
  `link_url` VARCHAR(255) NULL,
  `dedupe_key` VARCHAR(180) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_recipient_read_created` (`recipient_user_id`, `is_read`, `created_at`),
  KEY `idx_notifications_type` (`type`),
  UNIQUE KEY `uq_notifications_dedupe_key` (`dedupe_key`),
  CONSTRAINT `fk_notifications_recipient_user`
    FOREIGN KEY (`recipient_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_login_fingerprints` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `fingerprint` VARCHAR(64) NOT NULL,
  `last_ip` VARCHAR(64) NULL,
  `last_seen_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_login_fingerprints_user_fp` (`user_id`, `fingerprint`),
  CONSTRAINT `fk_user_login_fingerprints_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `event_comments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `parent_comment_id` INT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_comments_event` (`event_id`),
  KEY `idx_event_comments_user` (`user_id`),
  KEY `idx_event_comments_parent` (`parent_comment_id`),
  CONSTRAINT `fk_event_comments_event`
    FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_comments_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_comments_parent`
    FOREIGN KEY (`parent_comment_id`) REFERENCES `event_comments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `society_members` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `society_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `joined_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_society_members_society_user` (`society_id`, `user_id`),
  KEY `idx_society_members_user` (`user_id`),
  CONSTRAINT `fk_society_members_society`
    FOREIGN KEY (`society_id`) REFERENCES `societies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_society_members_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `society_member_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `society_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_society_member_requests_society_status` (`society_id`, `status`),
  KEY `idx_society_member_requests_user` (`user_id`),
  UNIQUE KEY `uq_society_member_requests_open` (`society_id`, `user_id`, `status`),
  CONSTRAINT `fk_society_member_requests_society`
    FOREIGN KEY (`society_id`) REFERENCES `societies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_society_member_requests_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_society_member_requests_reviewer`
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `society_reports` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `society_id` INT NOT NULL,
  `reporter_user_id` INT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_society_reports_society` (`society_id`),
  KEY `idx_society_reports_reporter` (`reporter_user_id`),
  CONSTRAINT `fk_society_reports_society`
    FOREIGN KEY (`society_id`) REFERENCES `societies`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_society_reports_reporter`
    FOREIGN KEY (`reporter_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SELECT 'Notifications migration complete.' AS result;
