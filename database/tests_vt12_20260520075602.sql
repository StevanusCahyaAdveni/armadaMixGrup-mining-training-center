-- ============================================
-- Table: tests
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 19:56:02
-- ============================================

CREATE TABLE IF NOT EXISTS `tests` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `title` VARCHAR(255) NOT NULL COMMENT 'Test Title',
  `category_id` VARCHAR(255) NOT NULL COMMENT 'Category Id',
  `type` VARCHAR(255) NOT NULL COMMENT 'Test Type',
  `answer_time` VARCHAR(255) NOT NULL COMMENT 'Answer Time (Minutes)',
  `point_show` VARCHAR(255) NOT NULL COMMENT 'Point Show (True False)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `tests` (`id`, `title`, `category_id`, `type`, `answer_time`, `point_show`) VALUES
-- ('sample-uuid-here', 'Sample Test Title', 'Sample Category Id', 'Sample Test Type', 'Sample Answer Time (Minutes)', 'Sample Point Show (True False)');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
