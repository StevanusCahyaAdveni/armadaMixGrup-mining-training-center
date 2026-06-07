-- ============================================
-- Table: employee_salary_increasing_decreasing
-- Description: Auto-generated table structure
-- Generated: 2026-06-07 09:57:12
-- ============================================

CREATE TABLE IF NOT EXISTS `employee_salary_increasing_decreasing` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `user_id` VARCHAR(255) NOT NULL COMMENT 'User Id',
  `date` DATE NOT NULL COMMENT 'Date',
  `category` VARCHAR(255) NOT NULL COMMENT 'Category (increasing or decreasing )',
  `desc` TEXT NOT NULL COMMENT 'Desc',
  `value` INT(11) NOT NULL COMMENT 'Value',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `employee_salary_increasing_decreasing` (`id`, `user_id`, `date`, `category`, `desc`, `value`) VALUES
-- ('sample-uuid-here', 'Sample User Id', 'Sample Date', 'Sample Category (increasing or decreasing )', 'Sample Desc', 'Sample Value');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
