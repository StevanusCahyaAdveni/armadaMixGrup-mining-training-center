-- ============================================
-- Table: test_question_medias
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 20:00:20
-- ============================================

CREATE TABLE IF NOT EXISTS `test_question_medias` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `question_id` VARCHAR(255) NOT NULL COMMENT 'Question Id',
  `media_name` TEXT NOT NULL COMMENT 'Media Name',
  `path` TEXT NOT NULL COMMENT 'File Path',
  `media_extendsion` VARCHAR(255) NOT NULL COMMENT 'Media Extendsion',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_question_medias` (`id`, `question_id`, `media_name`, `path`, `media_extendsion`) VALUES
-- ('sample-uuid-here', 'Sample Question Id', 'Sample Media Name', 'Sample File Path', 'Sample Media Extendsion');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
