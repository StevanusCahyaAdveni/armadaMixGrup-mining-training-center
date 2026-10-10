<?php
session_start();
include '../../../config.php';
include '../../../functions/sanitasi.php';
include '../../../functions/secure_query.php';

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    die("Akses ditolak. Silakan login.");
}

$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-d');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$driver_id_filter = isset($_GET['user_id']) ? sani($_GET['user_id']) : '';

$dateFilter = " t.tanggal >= '$start_date' AND t.tanggal <= '$end_date'";

if (!empty($driver_id_filter)) {
    $whereClause = "WHERE (t.employee_id = '$driver_id_filter' OR t.substitute_employee_id = '$driver_id_filter') AND $dateFilter";
} else {
    $whereClause = "WHERE $dateFilter";
}

if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR sub.full_name LIKE '%$search%' OR t.unit_id LIKE '%$search%')";
}

$query = "SELECT t.*, e.full_name as driver_name, e.employee_id as driver_nik, sub.full_name as pengganti_name
          FROM hauling_timesheets t 
          LEFT JOIN employees e ON t.employee_id = e.id 
          LEFT JOIN employees sub ON t.substitute_employee_id = sub.id
          $whereClause 
          ORDER BY t.tanggal ASC, t.ritase_ke ASC, e.full_name ASC";

$result = querySecure($con, $query, [], '');

// Generate CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Export_Hauling_Timesheets_' . $start_date . '_sd_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

$headers = [
    'No', 'Tanggal', 'Driver', 'Pengganti', 'No Lambung', 'Ritase ke-', 'Tipe Shift',
    'Tonase', 'KM Awal', 'KM Akhir', 'Total KM',
    'Jam Mulai', 'Jam Loading', 'Jam Timbang', 'Loading - Timbangan 1',
    'Jam Bongkar', 'TIMBANG AHKIR', 'Timbangan Awal - Ahkir',
    'Jam Tiba Site', 'Timbangan Ahkir - Site',
    'Rest Time', 'P5M', 'Cuci', 'Safety',
    'HM S1', 'OT', 'Tarif Tonase', 'Insentif Tonase', 'Insentif HM', 'Uang Lembur', 'Keterangan'
];

fputcsv($output, $headers, ';');

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $rowData = [
        $no++,
        $row['tanggal'],
        $row['driver_name'],
        $row['pengganti_name'] ?? '-',
        $row['unit_id'],
        $row['ritase_ke'],
        ($row['shift_type'] == '2' ? 'Shift 2 (OT)' : 'Shift 1 (Pokok)'),
        $row['tonase'],
        $row['km_awal'],
        $row['km_akhir'],
        $row['total_km'],
        $row['jam_mulai'] ? date('H:i', strtotime($row['jam_mulai'])) : '-',
        $row['jam_loading'] ? date('H:i', strtotime($row['jam_loading'])) : '-',
        $row['jam_timbang_awal'] ? date('H:i', strtotime($row['jam_timbang_awal'])) : '-',
        $row['durasi_loading_timbang'],
        $row['jam_bongkar'] ? date('H:i', strtotime($row['jam_bongkar'])) : '-',
        $row['jam_timbang_akhir'] ? date('H:i', strtotime($row['jam_timbang_akhir'])) : '-',
        $row['durasi_timbang_awal_akhir'],
        $row['jam_tiba_site'] ? date('H:i', strtotime($row['jam_tiba_site'])) : '-',
        $row['durasi_timbang_site'],
        $row['rest_time'] ? date('H:i', strtotime($row['rest_time'])) : '-',
        $row['p5m_time'] ? date('H:i', strtotime($row['p5m_time'])) : '-',
        ($row['cuci'] ? 'Ya' : 'Tidak'),
        ($row['safety'] ? 'Ya' : 'Tidak'),
        $row['hm_s1'],
        $row['ot_hours'],
        $row['tonase_rate'],
        $row['earned_tonase_incentive'],
        $row['earned_hm_incentive'],
        $row['overtime_amount'],
        $row['keterangan'] ?? '-'
    ];
    
    fputcsv($output, $rowData, ';');
}

fclose($output);
exit;
?>
