CREATE TABLE IF NOT EXISTS `employee_timesheets` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY COMMENT 'UUID v4',
  `employee_id` VARCHAR(36) NOT NULL COMMENT 'Relasi ke tabel employees.id',
  `tanggal` DATE NOT NULL COMMENT 'Tanggal operasional',
  `shift` ENUM('SIANG', 'MALAM') NOT NULL COMMENT 'Shift kerja',
  `unit_id` VARCHAR(100) COMMENT 'Nomor Lambung Alat (contoh: EXCA-45)',
  
  `hm_awal` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'HM saat mesin menyala',
  `hm_akhir` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'HM saat mesin mati',
  `rest_start` TIME COMMENT 'Jam mulai istirahat',
  `rest_end` TIME COMMENT 'Jam selesai istirahat',
  `ritase` INT DEFAULT 0 COMMENT 'Jumlah Ritase',
  `solar` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Pemakaian Solar',
  
  `total_hm` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'hm_akhir - hm_awal',
  `ist_hm` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Konversi jam istirahat',
  `hmc` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'total_hm + ist_hm',
  
  `applied_hm_rate` INT DEFAULT 0 COMMENT 'Tarif HM dari settings',
  `earned_hm_incentive` INT DEFAULT 0 COMMENT 'HMC * applied_hm_rate',

  -- `keterangan` TEXT DEFAULT NULL COMMENT 'Keterangan tambahan (Opsional)',

  `overtime_type` ENUM('NONE', 'BIASA', 'LIBUR') DEFAULT 'NONE' COMMENT 'Jenis lembur',
  `overtime_start` TIME NULL COMMENT 'Jam mulai lembur',
  `overtime_end` TIME NULL COMMENT 'Jam selesai lembur',
  `hm_awal_lembur` DECIMAL(10,2) NULL COMMENT 'HM mesin saat mulai lembur',
  `hm_akhir_lembur` DECIMAL(10,2) NULL COMMENT 'HM mesin saat selesai lembur',
  `overtime_amount` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal uang lembur dihitung sistem',

  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
