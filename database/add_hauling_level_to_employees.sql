-- Migration: Add level column to employees table
-- Default: 'mining', Options: 'mining', 'hauling'

ALTER TABLE `employees`
ADD COLUMN `level` VARCHAR(50) NOT NULL DEFAULT 'mining' COMMENT 'Level/Departemen: mining, hauling' AFTER `position`;
