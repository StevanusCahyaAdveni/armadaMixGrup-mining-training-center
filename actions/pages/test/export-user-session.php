<?php
session_start();
include '../../../config.php';
include '../../../functions/sanitasi.php';
include '../../../functions/secure_query.php';

// Cek autentikasi admin
if (!isset($_SESSION['admin'])) {
    die("Akses ditolak.");
}

$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$filter_test_id = isset($_GET['test_id']) ? sani($_GET['test_id']) : '';
$filter_date_start = isset($_GET['date_start']) ? sani($_GET['date_start']) : '';
$filter_date_end = isset($_GET['date_end']) ? sani($_GET['date_end']) : '';

$whereConditions = [];
if (!empty($search)) {
    $searchWildcard = '%' . $search . '%';
    $whereConditions[] = "(u.fullname LIKE '$searchWildcard' OR u.email LIKE '$searchWildcard' OR t.title LIKE '$searchWildcard' OR u.role LIKE '$searchWildcard' OR s.status LIKE '$searchWildcard')";
}

if (!empty($filter_test_id)) {
    $whereConditions[] = "s.test_id = '$filter_test_id'";
}

if (!empty($filter_date_start)) {
    $whereConditions[] = "DATE(s.datetime_start) >= '$filter_date_start'";
}

if (!empty($filter_date_end)) {
    $whereConditions[] = "DATE(s.datetime_start) <= '$filter_date_end'";
}

$whereClause = '';
if (count($whereConditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
}

$baseQuery = "SELECT s.id AS session_id, s.test_id, s.user_id, s.datetime_start, s.datetime_end, s.status, s.updated_at,
                 t.title AS test_title, t.point_show,
                 u.fullname AS user_fullname, u.email AS user_email, u.role AS user_role
          FROM test_user_sessions s
          LEFT JOIN tests t ON s.test_id = t.id
          LEFT JOIN users u ON s.user_id = u.id" . 
          $whereClause . " ORDER BY s.datetime_start DESC";

header("Content-type: text/csv");
header("Content-Disposition: attachment; filename=Export_Sesi_Ujian_" . date('Ymd_His') . ".csv");

$output = fopen('php://output', 'w');
// Set pipe '|' as delimiter
$delimiter = '|';

// Output CSV Header
fputcsv($output, [
    'No', 
    'Nama Kandidat', 
    'Email', 
    'Role', 
    'Judul Tes', 
    'Waktu Mulai', 
    'Waktu Selesai', 
    'Status', 
    'Skor (Pilihan Ganda)', 
    'Max Skor (Pilihan Ganda)', 
    'Jumlah Soal Esai'
], $delimiter);

$res = querySecure($con, $baseQuery, [], '');
$no = 1;
while ($row = mysqli_fetch_assoc($res)) {
    // Score calculations
    $scoreRes = querySecure($con, "SELECT SUM(CAST(c.point AS UNSIGNED)) AS total_score FROM test_user_answers a JOIN test_question_choices c ON a.choice_id = c.id WHERE a.user_session_id = ? AND c.choice_true = 'true'", [$row['session_id']], 's');
    $sessScore = 0;
    if ($scoreRes && $scoreRow = mysqli_fetch_assoc($scoreRes)) {
        $sessScore = intval($scoreRow['total_score'] ?? 0);
    }

    $maxScoreRes = querySecure($con, "SELECT SUM(CAST(c.point AS UNSIGNED)) AS max_score FROM test_question_choices c JOIN test_questions q ON c.question_id = q.id WHERE q.test_id = ? AND c.choice_true = 'true'", [$row['test_id']], 's');
    $sessMaxScore = 0;
    if ($maxScoreRes && $maxScoreRow = mysqli_fetch_assoc($maxScoreRes)) {
        $sessMaxScore = intval($maxScoreRow['max_score'] ?? 0);
    }

    $sessEssayRes = querySecure($con, "SELECT COUNT(*) as essay_count FROM test_questions WHERE test_id = ? AND question_type = 'Form'", [$row['test_id']], 's');
    $sessEssayCount = 0;
    if ($sessEssayRes && $sessEssayRow = mysqli_fetch_assoc($sessEssayRes)) {
        $sessEssayCount = intval($sessEssayRow['essay_count'] ?? 0);
    }

    $statusText = ($row['status'] === 'active') ? 'Sedang Mengerjakan' : 'Selesai';
    
    // Output row data
    fputcsv($output, [
        $no++,
        $row['user_fullname'],
        $row['user_email'],
        $row['user_role'],
        $row['test_title'],
        $row['datetime_start'],
        $row['datetime_end'],
        $statusText,
        $sessScore,
        $sessMaxScore,
        $sessEssayCount
    ], $delimiter);
}

fclose($output);
?>
