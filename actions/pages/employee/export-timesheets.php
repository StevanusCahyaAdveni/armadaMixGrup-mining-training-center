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

$headers = ['No', 'Tanggal', 'Shift', 'Nama Operator', 'No Lambung', 'Waktu Awal', 'Waktu Akhir', 'HM Awal', 'HM Akhir', 'Total HM', 'Istirahat Mulai', 'Istirahat Selesai', 'Total Istirahat', 'HMC', 'Ritase', 'Solar', 'Keterangan', 'Jenis Lembur', 'Jam Lembur Mulai', 'Jam Lembur Selesai', 'Istirahat Lembur Mulai', 'Istirahat Lembur Selesai', 'HM Awal Lembur', 'HM Akhir Lembur'];

if (isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] !== 'HR Site') {
    array_push($headers, 'Uang Lembur');
}

fputcsv($output, $headers, ';');

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $rowData = [
        $no++,
        $row['tanggal'],
        $row['shift'],
        $row['full_name'],
        $row['unit_id'],
        $row['waktu_awal'] ? date('H:i', strtotime($row['waktu_awal'])) : '-',
        $row['waktu_akhir'] ? date('H:i', strtotime($row['waktu_akhir'])) : '-',
        $row['hm_awal'],
        $row['hm_akhir'],
        $row['total_hm'],
        $row['rest_start'] ? date('H:i', strtotime($row['rest_start'])) : '-',
        $row['rest_end'] ? date('H:i', strtotime($row['rest_end'])) : '-',
        $row['ist_hm'],
        $row['hmc'],
        $row['ritase'],
        $row['solar'],
        $row['keterangan'] ?? '-',
        $row['overtime_type'],
        $row['overtime_start'] ? date('H:i', strtotime($row['overtime_start'])) : '-',
        $row['overtime_end'] ? date('H:i', strtotime($row['overtime_end'])) : '-',
        $row['overtime_rest_start'] ? date('H:i', strtotime($row['overtime_rest_start'])) : '-',
        $row['overtime_rest_end'] ? date('H:i', strtotime($row['overtime_rest_end'])) : '-',
        $row['hm_awal_lembur'] ?? '-',
        $row['hm_akhir_lembur'] ?? '-'
    ];
    
    if (isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] !== 'HR Site') {
        array_push($rowData, $row['overtime_amount'] ?? 0);
    }
    
    fputcsv($output, $rowData, ';');
}

fclose($output);
exit;
?>
