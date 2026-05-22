-- ============================================
-- Table: test_categorys
-- Description: Auto-generated table structure
-- Generated: 2026-05-20 19:53:26
-- ============================================

CREATE TABLE IF NOT EXISTS `test_categorys` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `category_title` VARCHAR(255) NOT NULL COMMENT 'Category Title',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `test_categorys` (`id`, `category_title`) VALUES
-- ('sample-uuid-here', 'Sample Category Title');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
