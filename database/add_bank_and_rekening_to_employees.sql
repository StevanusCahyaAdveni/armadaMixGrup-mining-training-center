-- Migration: Menambahkan kolom bank dan nomor_rekening pada tabel employees
ALTER TABLE `employees` 
ADD COLUMN `bank` VARCHAR(100) NULL AFTER `employee_id`,
ADD COLUMN `nomor_rekening` VARCHAR(100) NULL AFTER `bank`;
