CREATE TABLE IF NOT EXISTS `settings` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY COMMENT 'UUID v4',
  `setting_key` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Key identitas setting',
  `setting_desc` VARCHAR(255) COMMENT 'Deskripsi setting',
  `setting_value` VARCHAR(255) COMMENT 'Nilai setting',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`id`, `setting_key`, `setting_desc`, `setting_value`) 
VALUES (UUID(), 'tarif_hm', 'Tarif HM', '17000')
ON DUPLICATE KEY UPDATE `setting_value` = '17000';
