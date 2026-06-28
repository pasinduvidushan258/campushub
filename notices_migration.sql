CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `author_type` ENUM('admin', 'society') NOT NULL,
  `author_id` INT(11) NOT NULL COMMENT 'references users.id or societies.id',
  `content` VARCHAR(500) NOT NULL COMMENT 'short tweet-like text',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
