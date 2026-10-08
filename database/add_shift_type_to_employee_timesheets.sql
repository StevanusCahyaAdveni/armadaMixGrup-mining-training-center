-- Migration: Menambahkan kolom shift_type (Shift 1 / Shift 2) pada tabel employee_timesheets
ALTER TABLE `employee_timesheets` 
ADD COLUMN `shift_type` VARCHAR(10) NOT NULL DEFAULT '1' COMMENT '1 = Shift 1 (Pokok), 2 = Shift 2 (Lembur/OT)' 
AFTER `shift`;
