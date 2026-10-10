<?php
session_start();
include '../../../config.php';
include '../../../functions/sanitasi.php';
include '../../../functions/secure_query.php';

// Cek autentikasi
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    die("Akses ditolak. Silakan login.");
}

$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-d');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$user_id_filter = isset($_GET['user_id']) ? sani($_GET['user_id']) : '';

$dateFilter = " t.tanggal >= '$start_date' AND t.tanggal <= '$end_date'";

if (!empty($user_id_filter)) {
    $whereClause = "WHERE t.employee_id = '$user_id_filter' AND $dateFilter";
} else {
    $whereClause = "WHERE $dateFilter";
}

if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR t.unit_id LIKE '%$search%')";
}

$query = "SELECT t.*, e.full_name 
          FROM employee_timesheets t 
          LEFT JOIN employees e ON t.employee_id = e.id 
          $whereClause 
          ORDER BY t.tanggal ASC, e.full_name ASC";

$result = querySecure($con, $query, [], '');

// Generate CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Export_Timesheets_' . $start_date . '_sd_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

$headers = ['No', 'Tanggal', 'Shift', 'Tipe Shift', 'Nama Operator', 'No Lambung', 'Waktu Awal', 'Waktu Akhir', 'HM Awal', 'HM Akhir', 'Total HM', 'HMC (Jam)', 'Nominal HM 1', 'Nominal HM 2', 'Jam OT Real', 'Jam OT Efektif', 'Uang Lembur', 'Total Nominal', 'Ritase', 'Solar', 'Keterangan'];

fputcsv($output, $headers, ';');

$rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
$rateRow = mysqli_fetch_assoc($rateQuery);
$tarif_hm = isset($rateRow['setting_value']) ? (float) $rateRow['setting_value'] : 17000;

$rateOtQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
$rateOtRow = mysqli_fetch_assoc($rateOtQuery);
$tarif_lembur = isset($rateOtRow['setting_value']) ? (float) $rateOtRow['setting_value'] : 19509;

if (!function_exists('calcEffectiveOtHours')) {
    function calcEffectiveOtHours($otHours) {
        $ot = (float) $otHours;
        if ($ot <= 0) return 0.0;
        if ($ot <= 1) return $ot * 1.5;
        return 1.5 + ($ot - 1) * 2.0;
    }
}

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $isShift2 = ($row['shift_type'] == '2' || $row['overtime_type'] != 'NONE');
    $appliedRate = (float) ($row['applied_hm_rate'] ?: $tarif_hm);
    $hmcVal = (float) $row['hmc'];

    if (!$isShift2) {
        $nomHm1 = $hmcVal * $appliedRate;
        $nomHm2 = 0;
        $otReal = 0;
        $otEff = 0;
        $nomOt = 0;
    } else {
        $nomHm1 = 0;
        $nomHm2 = $hmcVal * $appliedRate;
        $otReal = $hmcVal;
        $otEff = calcEffectiveOtHours($otReal);
        $nomOt = round($otEff * $tarif_lembur, 2);
    }
    $totalNominal = $nomHm1 + $nomHm2 + $nomOt;

    $rowData = [
        $no++,
        $row['tanggal'],
        $row['shift'],
        $isShift2 ? 'Shift 2 (OT)' : 'Shift 1 (Pokok)',
        $row['full_name'],
        $row['unit_id'],
        $row['waktu_awal'] ? date('H:i', strtotime($row['waktu_awal'])) : '-',
        $row['waktu_akhir'] ? date('H:i', strtotime($row['waktu_akhir'])) : '-',
        $row['hm_awal'],
        $row['hm_akhir'],
        $row['total_hm'],
        $row['hmc'],
        $nomHm1,
        $nomHm2,
        $otReal,
        $otEff,
        $nomOt,
        $totalNominal,
        $row['ritase'],
        $row['solar'],
        $row['keterangan'] ?? '-'
    ];
    
    fputcsv($output, $rowData, ';');
}

fclose($output);
exit;
?>
