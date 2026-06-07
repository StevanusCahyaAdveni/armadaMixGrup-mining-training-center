-- ============================================
-- Table: employee_overtime
-- Description: Auto-generated table structure
-- Generated: 2026-06-07 10:18:31
-- ============================================

CREATE TABLE IF NOT EXISTS `employee_overtimes` (
  `id` VARCHAR(36) NOT NULL COMMENT 'Primary Key - UUID v4',
  `user_id` VARCHAR(255) NOT NULL COMMENT 'User Id',
  `description` VARCHAR(255) NOT NULL COMMENT 'Description',
  `overtime_start` DATETIME NOT NULL COMMENT 'Start Overtime',
  `overtime_end` DATETIME NOT NULL COMMENT 'End Overtime',
  `shift` VARCHAR(255) NOT NULL COMMENT 'Shift (Pagi dan Malam)',
  `amount` INT(11) NOT NULL COMMENT 'Amount',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation timestamp',
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Auto-generated table';

-- ============================================
-- Sample Data (Commented)
-- ============================================
-- INSERT INTO `employee_overtime` (`id`, `user_id`, `description`, `overtime_start`, `overtime_end`, `shift`, `amount`) VALUES
-- ('sample-uuid-here', 'Sample User Id', 'Sample Description', 'Sample Start Overtime', 'Sample End Overtime', 'Sample Shift (Pagi dan Malam)', 'Sample Amount');

-- ============================================
-- Notes:
-- - Primary key uses UUID v4 format (36 characters)
-- - All VARCHAR fields use utf8mb4_unicode_ci collation
-- ============================================
