-- ==========================================================
-- Table: hauling_timesheets
-- Description: Tabel data timesheet harian operasional Hauling
-- ==========================================================

CREATE TABLE IF NOT EXISTS `hauling_timesheets` (
  `id` VARCHAR(36) NOT NULL PRIMARY KEY COMMENT 'UUID v4',
  `employee_id` VARCHAR(36) NOT NULL COMMENT 'Relasi ke employees.id (Driver Utama)',
  `substitute_employee_id` VARCHAR(36) NULL DEFAULT NULL COMMENT 'Relasi ke employees.id (Driver Pengganti jika ada)',
  `tanggal` DATE NOT NULL COMMENT 'Tanggal operasional hauling',
  `unit_id` VARCHAR(100) NULL DEFAULT NULL COMMENT 'Nomor Lambung DT (contoh: DT-1246)',
  `ritase_ke` INT NOT NULL DEFAULT 1 COMMENT 'Ritase ke- (1 = Pokok/Shift 1, 2 = Lembur/Shift 2, dst)',
  `shift_type` VARCHAR(10) NOT NULL DEFAULT '1' COMMENT '1 = Shift 1 (Pokok), 2 = Shift 2 (OT / OTW)',
  `tonase` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Muatan Batubara / Material (Ton)',
  
  -- Jarak / Kilometer
  `km_awal` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'KM Awal Odometer',
  `km_akhir` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'KM Akhir Odometer',
  `total_km` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Total KM (KM Akhir - KM Awal)',
  
  -- Siklus Waktu Operasional
  `jam_mulai` TIME NULL DEFAULT NULL COMMENT 'Jam Mulai Operasi',
  `jam_loading` TIME NULL DEFAULT NULL COMMENT 'Jam Mulai Loading',
  `jam_timbang_awal` TIME NULL DEFAULT NULL COMMENT 'Jam Timbang Awal (Timbangan 1)',
  `durasi_loading_timbang` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Durasi Loading ke Timbang 1 (Jam)',
  `jam_bongkar` TIME NULL DEFAULT NULL COMMENT 'Jam Bongkar / Dumping',
  `jam_timbang_akhir` TIME NULL DEFAULT NULL COMMENT 'Jam Timbang Akhir (Timbangan 2)',
  `durasi_timbang_awal_akhir` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Durasi Timbang Awal ke Akhir (Jam)',
  `jam_tiba_site` TIME NULL DEFAULT NULL COMMENT 'Jam Tiba Kembali di Site',
  `durasi_timbang_site` DECIMAL(10,2) NULL DEFAULT 0.00 COMMENT 'Durasi Timbang Akhir ke Site (Jam)',
  
  -- Check & Compliance
  `rest_time` TIME NULL DEFAULT NULL COMMENT 'Jam Istirahat (Rest Time)',
  `p5m_time` TIME NULL DEFAULT NULL COMMENT 'Jam Briefing P5M',
  `cuci` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Status Cuci Unit (1 = Ya, 0 = Tidak)',
  `safety` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Status Safety Check (1 = Ya, 0 = Tidak)',
  
  -- Penghitungan HM & OT
  `hm_s1` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'HM Shift 1 (Standard 7 jam)',
  `ot_hours` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Jam Overtime / Lembur',
  
  -- Penghitungan Insentif & Gaji
  `tonase_rate` INT NOT NULL DEFAULT 3000 COMMENT 'Tarif Tonase (Rp 3.000 untuk S1, Rp 3.500 untuk S2/OTW)',
  `earned_tonase_incentive` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Insentif Tonase: tonase * tonase_rate',
  `earned_hm_incentive` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Insentif HM: hm_s1 * tarif_hm (Rp 17.000)',
  `overtime_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Nominal Lembur: ot_hours * tarif_ot (Rp 19.509)',
  
  `keterangan` TEXT NULL DEFAULT NULL COMMENT 'Keterangan kendala/kerusakan/unit low power, dll',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX `idx_hauling_tanggal` (`tanggal`),
  INDEX `idx_hauling_driver` (`employee_id`),
  INDEX `idx_hauling_unit` (`unit_id`),
  INDEX `idx_hauling_shift` (`shift_type`),
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
