-- ============================================
-- Table: test_question_choices
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 20:02:13
-- ============================================

CREATE TABLE IF NOT EXISTS `test_question_choices` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `question_id` VARCHAR(255) NOT NULL COMMENT 'Question Id',
  `choice_text` TEXT NOT NULL COMMENT 'Choice Text',
  `choice_true` VARCHAR(255) NOT NULL COMMENT 'True Or False',
  `point` VARCHAR(255) NOT NULL COMMENT 'Choice Point',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_question_choices` (`id`, `question_id`, `choice_text`, `choice_true`, `point`) VALUES
-- ('sample-uuid-here', 'Sample Question Id', 'Sample Choice Text', 'Sample True Or False', 'Sample Choice Point');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
