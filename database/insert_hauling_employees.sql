-- ========================================================
-- File: database/insert_hauling_employees.sql
-- Deskripsi: Query untuk menambahkan & sinkronisasi data karyawan
--            dengan level 'hauling' (Driver Hauling)
-- Tanggal: 2026-10-10 10:18:58
-- ========================================================

-- 1. Update level karyawan yang sudah ada menjadi 'hauling'
UPDATE `employees` 
SET `level` = 'hauling', 
    `position` = IF(`position` = '' OR `position` IS NULL, 'Driver Hauling', `position`)
WHERE UPPER(TRIM(`full_name`)) IN (
    'ABDUL SADAM',
    'ADAM SETIAWAN',
    'ALMAUN',
    'ANDRI AFIYANTO',
    'ANGGI PRADITO SITUMORANG',
    'ANJEMAY ON KARAMASA',
    'APRIANUS GELONG',
    'ARDHY ANANG SYAPUTRA',
    'ARDIYANSYAH SADIA S',
    'BAGAS ADI SAPUTRO',
    'BAHRUN',
    'BAMBANG',
    'BIMA HIDAYATULLAH',
    'DARMA SAPUTRA',
    'DARMAWAN SISWANTO',
    'DENNI IRAWAN',
    'FERDI',
    'HASRUDIN',
    'IDUL ETENDING',
    'IRJAL',
    'IZAL MUNTAHAR',
    'JONI HARIYANTO',
    'JUMAHARUDDIN',
    'KADEK WIDIARSA',
    'LA ODE SAMSUL DIMANTARA',
    'MIRWANTO',
    'MUH JALIL',
    'MUHAMMAD FURQAN',
    'MUHAMMAD GUNTUR',
    'MUHLIS',
    'MULHAN JAYA',
    'MUNIF MAFANDA UNGGAHI',
    'NANDAR PUTRAWAN',
    'NAWIRUDDIN',
    'NONO SABARNO',
    'RAHMAT HIDAYAT',
    'RENDI SAPUTRA',
    'RUDI HARTONO',
    'SAMSUL ANWAR',
    'SUHARIANTO',
    'SUPARDIN',
    'TATAN HADIANSYAH',
    'UNGGUL SUBEKTI',
    'ZAENAL MUSTAFID'
);

-- 2. Tambahkan (INSERT) karyawan hauling baru jika belum terdaftar
INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-001', 'ABDUL SADAM', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ABDUL SADAM');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-002', 'ADAM SETIAWAN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ADAM SETIAWAN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-003', 'ALMAUN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ALMAUN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-004', 'ANDRI AFIYANTO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ANDRI AFIYANTO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-005', 'ANGGI PRADITO SITUMORANG', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ANGGI PRADITO SITUMORANG');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-006', 'ANJEMAY ON KARAMASA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ANJEMAY ON KARAMASA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-007', 'APRIANUS GELONG', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'APRIANUS GELONG');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-008', 'ARDHY ANANG SYAPUTRA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ARDHY ANANG SYAPUTRA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-009', 'ARDIYANSYAH SADIA S', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ARDIYANSYAH SADIA S');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-010', 'BAGAS ADI SAPUTRO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'BAGAS ADI SAPUTRO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-011', 'BAHRUN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'BAHRUN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-012', 'BAMBANG', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'BAMBANG');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-013', 'BIMA HIDAYATULLAH', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'BIMA HIDAYATULLAH');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-014', 'DARMA SAPUTRA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'DARMA SAPUTRA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-015', 'DARMAWAN SISWANTO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'DARMAWAN SISWANTO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-016', 'DENNI IRAWAN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'DENNI IRAWAN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-017', 'FERDI', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'FERDI');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-018', 'HASRUDIN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'HASRUDIN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-019', 'IDUL ETENDING', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'IDUL ETENDING');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-020', 'IRJAL', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'IRJAL');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-021', 'IZAL MUNTAHAR', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'IZAL MUNTAHAR');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-022', 'JONI HARIYANTO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'JONI HARIYANTO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-023', 'JUMAHARUDDIN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'JUMAHARUDDIN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-024', 'KADEK WIDIARSA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'KADEK WIDIARSA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-025', 'LA ODE SAMSUL DIMANTARA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'LA ODE SAMSUL DIMANTARA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-026', 'MIRWANTO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MIRWANTO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-027', 'MUH JALIL', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MUH JALIL');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-028', 'MUHAMMAD FURQAN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MUHAMMAD FURQAN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-029', 'MUHAMMAD GUNTUR', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MUHAMMAD GUNTUR');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-030', 'MUHLIS', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MUHLIS');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-031', 'MULHAN JAYA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MULHAN JAYA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-032', 'MUNIF MAFANDA UNGGAHI', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'MUNIF MAFANDA UNGGAHI');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-033', 'NANDAR PUTRAWAN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'NANDAR PUTRAWAN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-034', 'NAWIRUDDIN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'NAWIRUDDIN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-035', 'NONO SABARNO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'NONO SABARNO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-036', 'RAHMAT HIDAYAT', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'RAHMAT HIDAYAT');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-037', 'RENDI SAPUTRA', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'RENDI SAPUTRA');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-038', 'RUDI HARTONO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'RUDI HARTONO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-039', 'SAMSUL ANWAR', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'SAMSUL ANWAR');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-040', 'SUHARIANTO', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'SUHARIANTO');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-041', 'SUPARDIN', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'SUPARDIN');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-042', 'TATAN HADIANSYAH', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'TATAN HADIANSYAH');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-043', 'UNGGUL SUBEKTI', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'UNGGUL SUBEKTI');

INSERT INTO `employees` (`id`, `employee_id`, `full_name`, `company_name`, `position`, `level`, `join_date`, `gaji_pokok`, `tunjangan_tetap`, `created_at`, `updated_at`)
SELECT UUID(), 'HAUL-044', 'ZAENAL MUSTAFID', 'AMG', 'Driver Hauling', 'hauling', '2026-01-01', 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `employees` WHERE UPPER(TRIM(`full_name`)) = 'ZAENAL MUSTAFID');

