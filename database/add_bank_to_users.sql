-- Migration: Menambahkan kolom bank pada tabel users
ALTER TABLE `users` ADD COLUMN `bank` VARCHAR(100) NULL AFTER `role`;
