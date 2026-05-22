-- ============================================
-- Table: test_user_answers
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 20:08:06
-- ============================================

CREATE TABLE IF NOT EXISTS `test_user_answers` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `question_id` VARCHAR(255) NOT NULL COMMENT 'Question Id',
  `choice_id` VARCHAR(255) NOT NULL COMMENT 'If Question Multiple Choice',
  `user_session_id` VARCHAR(255) NOT NULL COMMENT 'User Session ID',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_user_answers` (`id`, `question_id`, `choice_id`, `user_session_id`) VALUES
-- ('sample-uuid-here', 'Sample Question Id', 'Sample If Question Multiple Choice', 'Sample User Session ID');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
