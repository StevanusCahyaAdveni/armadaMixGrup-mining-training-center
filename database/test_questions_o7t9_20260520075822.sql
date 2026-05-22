-- ============================================
-- Table: test_questions
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 19:58:22
-- ============================================

CREATE TABLE IF NOT EXISTS `test_questions` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `test_id` VARCHAR(255) NOT NULL COMMENT 'Test Id',
  `question` TEXT NOT NULL COMMENT 'Text Question',
  `question_type` VARCHAR(255) NOT NULL COMMENT 'Multiple Choice or Form',
  `questions_material` VARCHAR(255) NOT NULL COMMENT 'Materi Of Question',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_questions` (`id`, `test_id`, `question`, `question_type`, `questions_material`) VALUES
-- ('sample-uuid-here', 'Sample Test Id', 'Sample Text Question', 'Sample Multiple Choice or Form', 'Sample Materi Of Question');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
