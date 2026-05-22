-- ============================================
-- Table: test_user_session
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 20:04:47
-- ============================================

CREATE TABLE IF NOT EXISTS `test_user_sessions` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `test_id` VARCHAR(255) NOT NULL COMMENT 'Test Id',
  `user_id` VARCHAR(255) NOT NULL COMMENT 'User Id',
  `datetime_start` DATETIME NOT NULL COMMENT 'Datetime Start',
  `datetime_end` DATETIME NOT NULL COMMENT 'Datetime End',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_user_session` (`id`, `test_id`, `user_id`, `datetime_start`, `datetime_end`) VALUES
-- ('sample-uuid-here', 'Sample Test Id', 'Sample User Id', 'Sample Datetime Start', 'Sample Datetime End');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
