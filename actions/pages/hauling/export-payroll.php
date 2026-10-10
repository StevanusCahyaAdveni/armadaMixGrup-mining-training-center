<?php
session_start();
include '../../../config.php';
include '../../../functions/sanitasi.php';
include '../../../functions/secure_query.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    die("Akses ditolak. Silakan login.");
}

$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-t');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$calc_mode = isset($_GET['calc_mode']) ? sani($_GET['calc_mode']) : 'tonase';

$rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
$rateRow = mysqli_fetch_assoc($rateQuery);
$tarif_hm = isset($rateRow['setting_value']) ? (float) $rateRow['setting_value'] : 17000;

$rateOtQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
$rateOtRow = mysqli_fetch_assoc($rateOtQuery);
$tarif_lembur = isset($rateOtRow['setting_value']) ? (float) $rateOtRow['setting_value'] : 19509;

$tonS1Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s1'");
$tonS1Row = mysqli_fetch_assoc($tonS1Q);
$tarif_tonase_s1 = isset($tonS1Row['setting_value']) ? (float) $tonS1Row['setting_value'] : 3000;

$tonS2Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s2'");
$tonS2Row = mysqli_fetch_assoc($tonS2Q);
$tarif_tonase_s2 = isset($tonS2Row['setting_value']) ? (float) $tonS2Row['setting_value'] : 3500;

$query = "SELECT 
            e.id as employee_id, 
            e.full_name, 
            e.employee_id as nik,
            e.bank,
            e.nomor_rekening,
            e.gaji_pokok, 
            e.tunjangan_tetap,
            COUNT(t.id) as total_ritase,
            SUM(t.tonase) as total_tonase,
            SUM(CASE WHEN t.shift_type = '1' OR t.ritase_ke = 1 THEN t.tonase ELSE 0 END) as tonase_s1,
            SUM(CASE WHEN t.shift_type = '2' OR t.ritase_ke >= 2 THEN t.tonase ELSE 0 END) as tonase_s2,
            SUM(CASE WHEN t.shift_type = '1' OR t.ritase_ke = 1 THEN t.earned_tonase_incentive ELSE 0 END) as insentif_tonase_s1,
            SUM(CASE WHEN t.shift_type = '2' OR t.ritase_ke >= 2 THEN t.earned_tonase_incentive ELSE 0 END) as insentif_tonase_s2,
            SUM(t.earned_tonase_incentive) as total_insentif_tonase,
            SUM(t.hm_s1) as total_hm_s1,
            SUM(t.ot_hours) as total_ot_hours,
            SUM(t.earned_hm_incentive) as total_insentif_hm,
            SUM(t.overtime_amount) as total_overtime,
            (SELECT SUM(CASE WHEN category = 'increasing' THEN value WHEN category = 'decreasing' THEN -value ELSE value END) 
             FROM employee_salary_increasing_decreasing s 
             WHERE s.user_id = e.id AND s.date BETWEEN '$start_date' AND '$end_date') as penambah_pengurang
          FROM employees e
          INNER JOIN hauling_timesheets t ON e.id = t.employee_id AND t.tanggal BETWEEN '$start_date' AND '$end_date'
          GROUP BY e.id
          HAVING (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%' OR e.bank LIKE '%$search%')
          ORDER BY e.full_name ASC";

$result = mysqli_query($con, $query);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Payroll_Hauling_' . $calc_mode . '_' . $start_date . '_sd_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

if ($calc_mode === 'tonase') {
    $headers = [
        'No', 'NIK', 'Nama Driver', 'Bank', 'No Rekening', 'Total Ritase',
        'Tonase Shift 1', 'Insentif S1 (3.000)', 'Tonase Shift 2', 'Insentif S2 (3.500)',
        'Total Tonase', 'Total Insentif Tonase', 'Gaji Pokok', 'Tunjangan Tetap', 'Penambah/Pengurang', 'Take Home Pay'
    ];
    fputcsv($output, $headers, ';');

    $no = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $gapok = (float)($r['gaji_pokok'] ?? 0);
        $tunj = (float)($r['tunjangan_tetap'] ?? 0);
        $incDec = (float)($r['penambah_pengurang'] ?? 0);
        $insTon = (float)($r['total_insentif_tonase'] ?? 0);
        $thp = $gapok + $tunj + $insTon + $incDec;

        fputcsv($output, [
            $no++,
            $r['nik'] ?: '-',
            $r['full_name'],
            $r['bank'] ?: '-',
            $r['nomor_rekening'] ?: '-',
            $r['total_ritase'],
            $r['tonase_s1'],
            $r['insentif_tonase_s1'],
            $r['tonase_s2'],
            $r['insentif_tonase_s2'],
            $r['total_tonase'],
            $insTon,
            $gapok,
            $tunj,
            $incDec,
            $thp
        ], ';');
    }
} elseif ($calc_mode === 'hm') {
    $headers = [
        'No', 'NIK', 'Nama Driver', 'Bank', 'No Rekening', 'Total Ritase',
        'HM Shift 1', 'Insentif HM (17.000)', 'Overtime (Jam OT)', 'Uang Lembur (19.509)',
        'Total Insentif HM', 'Gaji Pokok', 'Tunjangan Tetap', 'Penambah/Pengurang', 'Take Home Pay'
    ];
    fputcsv($output, $headers, ';');

    $no = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $gapok = (float)($r['gaji_pokok'] ?? 0);
        $tunj = (float)($r['tunjangan_tetap'] ?? 0);
        $incDec = (float)($r['penambah_pengurang'] ?? 0);
        $insHm = (float)($r['total_insentif_hm'] ?? 0) + (float)($r['total_overtime'] ?? 0);
        $thp = $gapok + $tunj + $insHm + $incDec;

        fputcsv($output, [
            $no++,
            $r['nik'] ?: '-',
            $r['full_name'],
            $r['bank'] ?: '-',
            $r['nomor_rekening'] ?: '-',
            $r['total_ritase'],
            $r['total_hm_s1'],
            $r['total_insentif_hm'],
            $r['total_ot_hours'],
            $r['total_overtime'],
            $insHm,
            $gapok,
            $tunj,
            $incDec,
            $thp
        ], ';');
    }
} else {
    $headers = [
        'No', 'NIK', 'Nama Driver', 'Total Ritase', 'Total Tonase',
        'Insentif Tonase', 'Insentif HM', 'Selisih (Tonase - HM)',
        'THP Skema Tonase', 'THP Skema HM'
    ];
    fputcsv($output, $headers, ';');

    $no = 1;
    while ($r = mysqli_fetch_assoc($result)) {
        $gapok = (float)($r['gaji_pokok'] ?? 0);
        $tunj = (float)($r['tunjangan_tetap'] ?? 0);
        $incDec = (float)($r['penambah_pengurang'] ?? 0);
        $insTon = (float)($r['total_insentif_tonase'] ?? 0);
        $insHm = (float)($r['total_insentif_hm'] ?? 0) + (float)($r['total_overtime'] ?? 0);
        $thpTon = $gapok + $tunj + $insTon + $incDec;
        $thpHm = $gapok + $tunj + $insHm + $incDec;
        $selisih = $insTon - $insHm;

        fputcsv($output, [
            $no++,
            $r['nik'] ?: '-',
            $r['full_name'],
            $r['total_ritase'],
            $r['total_tonase'],
            $insTon,
            $insHm,
            $selisih,
            $thpTon,
            $thpHm
        ], ';');
    }
}

fclose($output);
exit;
?>
