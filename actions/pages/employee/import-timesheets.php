<?php
// Helper: Minutes difference between two times (supporting overnight)
if (!function_exists('getMinutesDiff')) {
    function getMinutesDiff($start, $end) {
        if (!$start || !$end) return 0;
        $t1 = strtotime($start);
        $t2 = strtotime($end);
        if ($t2 < $t1) {
            $t2 += 86400; // Next day
        }
        return ($t2 - $t1) / 60;
    }
}

// Helper: Parse Excel Date (e.g. 01-Jun-26, 01/06/2026, 2026-06-01)
if (!function_exists('parseExcelDate')) {
    function parseExcelDate($dateStr) {
        $dateStr = trim($dateStr);
        if (empty($dateStr) || $dateStr === '-') return null;

        $monthMap = [
            'jan' => '01', 'januari' => '01', 'january' => '01',
            'feb' => '02', 'februari' => '02', 'february' => '02',
            'mar' => '03', 'maret' => '03', 'march' => '03',
            'apr' => '04', 'april' => '04',
            'may' => '05', 'mei' => '05',
            'jun' => '06', 'juni' => '06', 'june' => '06',
            'jul' => '07', 'juli' => '07', 'july' => '07',
            'aug' => '08', 'agu' => '08', 'ags' => '08', 'agustus' => '08', 'august' => '08',
            'sep' => '09', 'sept' => '09', 'september' => '09',
            'oct' => '10', 'okt' => '10', 'oktober' => '10', 'october' => '10',
            'nov' => '11', 'nop' => '11', 'november' => '11',
            'dec' => '12', 'des' => '12', 'desember' => '12', 'december' => '12'
        ];

        // Format DD-Mon-YY or DD-Mon-YYYY (e.g., 01-Jun-26, 01-Juni-2026)
        if (preg_match('/^(\d{1,2})[-\/\s]([A-Za-z]+)[-\/\s](\d{2,4})$/', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $monKey = strtolower($m[2]);
            $month = isset($monthMap[$monKey]) ? $monthMap[$monKey] : '01';
            $year = $m[3];
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            return "$year-$month-$day";
        }

        // Format DD/MM/YYYY or DD-MM-YYYY
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$/', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = $m[3];
            if (strlen($year) == 2) {
                $year = '20' . $year;
            }
            return "$year-$month-$day";
        }

        // Format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        // Fallback strtotime
        $ts = strtotime($dateStr);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}

// Helper: Parse Excel Time (e.g. 7,05 -> 07:05:00, 15,00 -> 15:00:00, 3,00 -> 03:00:00)
if (!function_exists('parseExcelTime')) {
    function parseExcelTime($timeStr) {
        $timeStr = trim($timeStr);
        if (empty($timeStr) || $timeStr === '-' || $timeStr === '0' || $timeStr === '0,00' || $timeStr === '0.00') {
            return null;
        }

        // Handle standard time format HH:MM or HH:MM:SS
        if (strpos($timeStr, ':') !== false) {
            $parts = explode(':', $timeStr);
            $h = (int) $parts[0];
            $m = isset($parts[1]) ? (int) $parts[1] : 0;
            $s = isset($parts[2]) ? (int) $parts[2] : 0;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        // Handle Indonesian Excel decimal time format (7,05 or 7.05 or 15,00)
        $clean = str_replace(',', '.', $timeStr);
        if (is_numeric($clean)) {
            $parts = explode('.', $clean);
            $h = (int) $parts[0];
            $m_str = isset($parts[1]) ? $parts[1] : '0';
            if (strlen($m_str) == 1) {
                $m = (int) ($m_str . '0');
            } else {
                $m = (int) substr($m_str, 0, 2);
            }
            if ($m > 59) $m = 59;
            if ($h > 23) $h = $h % 24;
            return sprintf('%02d:%02d:00', $h, $m);
        }

        return null;
    }
}

// Helper: Parse Number (e.g. 2.645,00 -> 2645.00, 7,92 -> 7.92, - -> 0)
if (!function_exists('parseExcelNumber')) {
    function parseExcelNumber($numStr) {
        $numStr = trim($numStr);
        if (empty($numStr) || $numStr === '-') return 0.00;
        // Remove dots if used as thousand separator, then change comma to dot
        $clean = str_replace(' ', '', $numStr);
        if (strpos($clean, ',') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        }
        return (float) $clean;
    }
}

// Helper: Enhanced normalize name for matching
if (!function_exists('enhancedNormalize')) {
    function enhancedNormalize($name) {
        $name = strtolower($name);
        // Remove text in parentheses (e.g. (EXCA), (BREAKER))
        $name = preg_replace('/\([^)]*\)/', ' ', $name);
        // Replace dots, dashes, commas with space
        $name = preg_replace('/[.\-,_]/', ' ', $name);
        // Remove multiple spaces
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
}

if (!function_exists('normalizeName')) {
    function normalizeName($name) {
        return enhancedNormalize($name);
    }
}

if (!function_exists('calcEffectiveOtHours')) {
    function calcEffectiveOtHours($otHours) {
        $ot = (float) $otHours;
        if ($ot <= 0) return 0.0;
        if ($ot <= 1) return $ot * 1.5;
        return 1.5 + ($ot - 1) * 2.0;
    }
}

if (!function_exists('calcOvertimeAmount')) {
    function calcOvertimeAmount($otHours, $rate = 19509) {
        $effHours = calcEffectiveOtHours($otHours);
        return round($effHours * $rate, 2);
    }
}

// Helper: Advanced Employee Matching
if (!function_exists('matchEmployeeAdvanced')) {
    function matchEmployeeAdvanced($operatorRaw, $employees, $normalizedEmpMap) {
        $normOp = enhancedNormalize($operatorRaw);
        if (empty($normOp)) return [null, ''];

        // 1. Exact normalized match
        if (isset($normalizedEmpMap[$normOp])) {
            return [$normalizedEmpMap[$normOp]['id'], $normalizedEmpMap[$normOp]['full_name']];
        }

        $opWords = explode(' ', $normOp);

        // 2. Prefix / Word containment match
        $candidates = [];
        foreach ($employees as $emp) {
            $normDb = enhancedNormalize($emp['full_name']);
            if ($normDb === $normOp) {
                return [$emp['id'], $emp['full_name']];
            }

            $dbWords = explode(' ', $normDb);

            // Check if $normOp is prefix of $normDb (e.g. "rahmat hidayat" in "rahmat hidayat anwar")
            if (strpos($normDb, $normOp) === 0) {
                $candidates[] = ['emp' => $emp, 'score' => 95];
                continue;
            }

            // Check word-by-word abbreviation (e.g., "muh jalil" vs "muhammad jalil", "la ode samsul d" vs "la ode samsul dimantara")
            $matchWordCount = 0;
            $i = 0; $j = 0;
            while ($i < count($opWords) && $j < count($dbWords)) {
                $w1 = $opWords[$i];
                $w2 = $dbWords[$j];
                if ($w1 === $w2) {
                    $matchWordCount++;
                    $i++; $j++;
                } elseif (
                    ($w1 === 'muh' || $w1 === 'm' || $w1 === 'muhammad' || $w1 === 'muhamad' || $w1 === 'muchamad' || $w1 === 'muchammad') &&
                    ($w2 === 'muh' || $w2 === 'm' || $w2 === 'muhammad' || $w2 === 'muhamad' || $w2 === 'muchamad' || $w2 === 'muchammad')
                ) {
                    $matchWordCount++;
                    $i++; $j++;
                } elseif (strlen($w1) == 1 && substr($w2, 0, 1) === $w1) {
                    $matchWordCount++;
                    $i++; $j++;
                } elseif (strlen($w1) >= 3 && strpos($w2, $w1) === 0) {
                    $matchWordCount++;
                    $i++; $j++;
                } else {
                    $j++;
                }
            }

            if ($matchWordCount >= count($opWords) && count($opWords) >= 2) {
                $candidates[] = ['emp' => $emp, 'score' => 90];
            } else {
                similar_text($normOp, $normDb, $percent);
                if ($percent >= 80) {
                    $candidates[] = ['emp' => $emp, 'score' => $percent];
                }
            }
        }

        if (!empty($candidates)) {
            usort($candidates, function($a, $b) { return $b['score'] <=> $a['score']; });
            return [$candidates[0]['emp']['id'], $candidates[0]['emp']['full_name']];
        }

        return [null, ''];
    }
}

// AJAX Preview, Search Employees, or Batch Save handling
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// 1. Real-time Employee Search API for Select2
if ($action === 'search_employees') {
    header('Content-Type: application/json');
    $q = isset($_GET['q']) ? sani($_GET['q']) : (isset($_POST['q']) ? sani($_POST['q']) : '');
    
    $where = "";
    $params = [];
    $types = "";
    if (!empty($q)) {
        $where = "WHERE full_name LIKE ? OR employee_id LIKE ?";
        $searchTerm = "%$q%";
        $params = [$searchTerm, $searchTerm];
        $types = "ss";
        $empRes = querySecure($con, "SELECT id, full_name, employee_id FROM employees $where ORDER BY full_name ASC LIMIT 50", $params, $types);
    } else {
        $empRes = querySecure($con, "SELECT id, full_name, employee_id FROM employees ORDER BY full_name ASC LIMIT 50", [], '');
    }

    $results = [];
    while ($emp = mysqli_fetch_assoc($empRes)) {
        $results[] = [
            'id'   => $emp['id'],
            'text' => $emp['full_name'] . (!empty($emp['employee_id']) ? ' (' . $emp['employee_id'] . ')' : '')
        ];
    }

    echo json_encode(['results' => $results]);
    exit;
}

// 2. Parse & Preview Excel Data
if ($action === 'preview') {
    header('Content-Type: application/json');
    $rawText = isset($_POST['raw_data']) ? $_POST['raw_data'] : '';

    if (empty(trim($rawText))) {
        echo json_encode(['status' => 'error', 'message' => 'Data input kosong! Silakan paste data dari Excel.']);
        exit;
    }

    // Fetch master employees for initial matching
    $empRes = querySecure($con, "SELECT id, full_name, employee_id FROM employees ORDER BY full_name ASC", [], '');
    $employees = [];
    $normalizedEmpMap = [];
    while ($emp = mysqli_fetch_assoc($empRes)) {
        $employees[] = $emp;
        $norm = enhancedNormalize($emp['full_name']);
        $normalizedEmpMap[$norm] = $emp;
    }

    // Fetch settings for calculation preview
    $rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
    $rateRow = mysqli_fetch_assoc($rateQuery);
    $tarif_hm = isset($rateRow['setting_value']) ? (int) $rateRow['setting_value'] : 17000;

    $rateQuery2 = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
    $rateRow2 = mysqli_fetch_assoc($rateQuery2);
    $tarif_lembur = isset($rateRow2['setting_value']) ? (float) $rateRow2['setting_value'] : 19509;

    $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
    $parsedRows = [];
    $unmatchedCount = 0;
    $matchedCount = 0;

    foreach ($lines as $lineIdx => $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Skip if row only contains whitespace, dashes, tabs, or semicolons
        $lineClean = preg_replace('/[\s\t\-\;\,]/', '', $line);
        if (empty($lineClean)) continue;

        $cols = explode("\t", $line);
        // If copied with semicolon or comma instead of tab
        if (count($cols) < 5 && strpos($line, ';') !== false) {
            $cols = explode(';', $line);
        }

        // Check if header or summary line (skip if first col is Tanggal, Keterangan, Total, Jumlah)
        $firstCol = trim($cols[0]);
        if (
            stripos($firstCol, 'tanggal') !== false || 
            stripos($firstCol, 'keterangan') !== false ||
            stripos($firstCol, 'total') !== false ||
            stripos($firstCol, 'jumlah') !== false ||
            stripos($firstCol, 'rekap') !== false
        ) {
            continue;
        }

        // Detect 28-column format (with KM Unit cols) vs 25-column format
        $is28Col = count($cols) >= 27;

        $tanggalRaw   = isset($cols[0]) ? trim($cols[0]) : '';
        $shiftRaw     = isset($cols[1]) ? strtoupper(trim($cols[1])) : 'SIANG';
        $shiftTypeRaw = isset($cols[2]) ? trim($cols[2]) : '1'; // 1=Pokok (S1), 2=Lembur (S2)
        $unitId       = isset($cols[3]) ? trim($cols[3]) : '';
        $operatorRaw  = isset($cols[4]) ? trim($cols[4]) : '';

        // Clean operator name & unit to check for empty row
        $operatorRawClean = trim($operatorRaw, " \t\n\r\0\x0B-");
        $unitIdClean = trim($unitId, " \t\n\r\0\x0B-");

        // Skip row if operator name is empty or only dash, or if it is a total/summary row
        if (empty($operatorRawClean)) {
            continue; // Skip empty row without operator
        }

        if (
            stripos($operatorRawClean, 'total') !== false || 
            stripos($operatorRawClean, 'jumlah') !== false || 
            stripos($operatorRawClean, 'subtotal') !== false
        ) {
            continue;
        }

        if ($is28Col) {
            // 28-column format with KM Unit
            $hmAwalRaw    = isset($cols[8]) ? trim($cols[8]) : '';
            $hmAkhirRaw   = isset($cols[9]) ? trim($cols[9]) : '';
            $totalHmRaw   = isset($cols[10]) ? trim($cols[10]) : '';
            $kerjaAwalRaw = isset($cols[13]) ? trim($cols[13]) : '';
            $kerjaAkhirRaw= isset($cols[14]) ? trim($cols[14]) : '';
            $istAwalRaw   = isset($cols[15]) ? trim($cols[15]) : '';
            $istAkhirRaw  = isset($cols[16]) ? trim($cols[16]) : '';
            $hmoRaw       = isset($cols[17]) ? trim($cols[17]) : '';
            $keterangan   = isset($cols[19]) ? trim($cols[19]) : '';
            $ritaseRaw    = isset($cols[20]) ? trim($cols[20]) : '0';
            $solarRaw     = isset($cols[21]) ? trim($cols[21]) : '0';
        } else {
            // 25-column standard format
            $hmAwalRaw    = isset($cols[5]) ? trim($cols[5]) : '';
            $hmAkhirRaw   = isset($cols[6]) ? trim($cols[6]) : '';
            $totalHmRaw   = isset($cols[7]) ? trim($cols[7]) : '';
            $kerjaAwalRaw = isset($cols[10]) ? trim($cols[10]) : '';
            $kerjaAkhirRaw= isset($cols[11]) ? trim($cols[11]) : '';
            $istAwalRaw   = isset($cols[12]) ? trim($cols[12]) : '';
            $istAkhirRaw  = isset($cols[13]) ? trim($cols[13]) : '';
            $hmoRaw       = isset($cols[14]) ? trim($cols[14]) : '';
            $keterangan   = isset($cols[16]) ? trim($cols[16]) : '';
            $ritaseRaw    = isset($cols[17]) ? trim($cols[17]) : '0';
            $solarRaw     = isset($cols[18]) ? trim($cols[18]) : '0';
        }

        $tanggal = parseExcelDate($tanggalRaw);
        if (!$tanggal) $tanggal = date('Y-m-d');

        $shift = (strpos($shiftRaw, 'MALAM') !== false) ? 'MALAM' : 'SIANG';
        $isOvertimeRow = ($shiftTypeRaw === '2' || stripos($keterangan, 'lembur') !== false);

        $hmAwal = parseExcelNumber($hmAwalRaw);
        $hmAkhir = parseExcelNumber($hmAkhirRaw);
        $waktuAwal = parseExcelTime($kerjaAwalRaw);
        $waktuAkhir = parseExcelTime($kerjaAkhirRaw);
        $restStart = parseExcelTime($istAwalRaw);
        $restEnd = parseExcelTime($istAkhirRaw);
        $ritase = (int) parseExcelNumber($ritaseRaw);
        $solar = parseExcelNumber($solarRaw);
        $hmoVal = parseExcelNumber($hmoRaw);

        // Employee matching
        list($matchedEmpId, $matchedEmpName) = matchEmployeeAdvanced($operatorRaw, $employees, $normalizedEmpMap);

        if (!empty($matchedEmpId)) {
            $matchedCount++;
            $employeeId = $matchedEmpId;
            $employeeName = $matchedEmpName;
        } else {
            $unmatchedCount++;
            $employeeId = null;
            $employeeName = '';
        }

        // Hitung Total HM Unit Mesin
        $totalHm = ($hmAkhir >= $hmAwal) ? ($hmAkhir - $hmAwal) : 0.00;

        // Tentukan Jam Kerja Efektif Operator ($jamKerja / HMC):
        // 1. Jika kolom HMO bernilai positif valid di Excel, gunakan langsung
        // 2. Jika HMO kosong / minus (karena formula Excel malam tanpa modulo 24), hitung dari selisih waktu kerja - istirahat
        // 3. Fallback: S1 = 7.00 jam, S2 = 2.00 jam
        if ($hmoVal > 0) {
            $jamKerja = $hmoVal;
        } else {
            $workMins = getMinutesDiff($waktuAwal, $waktuAkhir);
            $istMins = ($restStart && $restEnd) ? getMinutesDiff($restStart, $restEnd) : 0;
            $calcHours = ($workMins - $istMins) / 60;
            if ($calcHours > 0) {
                $jamKerja = round($calcHours, 2);
            } else {
                $jamKerja = $isOvertimeRow ? 2.00 : 7.00;
            }
        }

        // HMC dan Insentif HM dihitung untuk semua baris (Shift 1 maupun Shift 2): Jam Kerja x Tarif HM (Rp 17.000)
        $hmc = $jamKerja;
        $earnedHmIncentive = (int) round($hmc * $tarif_hm);

        if ($isOvertimeRow) {
            $overtimeType = 'BIASA';
            $overtimeStart = $waktuAwal ?: ($shift === 'SIANG' ? '15:00:00' : '03:00:00');
            $overtimeEnd   = $waktuAkhir ?: ($shift === 'SIANG' ? '17:00:00' : '05:00:00');
            $overtimeRestStart = $restStart;
            $overtimeRestEnd   = $restEnd;
            $hmAwalLembur = $hmAwal;
            $hmAkhirLembur = $hmAkhir;
            // Skema Lembur Baru Depnaker: Jam 1 = 1.5x, Jam 2+ = 2x x Tarif Lembur (Rp 19.509)
            $overtimeAmount = (float) calcOvertimeAmount($hmc, $tarif_lembur);
            
            // Baris lembur (Shift 2)
            $regWaktuAwal = null;
            $regWaktuAkhir = null;
            $regRestStart = null;
            $regRestEnd = null;
            $regHmAwal = 0;
            $regHmAkhir = 0;
            $regTotalHm = 0;
            $regHmc = $jamKerja;
            $regIstHm = ($restStart && $restEnd) ? round(getMinutesDiff($restStart, $restEnd) / 60, 2) : 0.00;
        } else {
            $overtimeType = 'NONE';
            $overtimeStart = null;
            $overtimeEnd = null;
            $overtimeRestStart = null;
            $overtimeRestEnd = null;
            $hmAwalLembur = null;
            $hmAkhirLembur = null;
            $overtimeAmount = 0.00;

            // Baris reguler (Shift 1)
            $regWaktuAwal = $waktuAwal ?: ($shift === 'SIANG' ? '07:00:00' : '19:00:00');
            $regWaktuAkhir = $waktuAkhir ?: ($shift === 'SIANG' ? '15:00:00' : '03:00:00');
            $regRestStart = $restStart ?: ($shift === 'SIANG' ? '12:00:00' : '00:00:00');
            $regRestEnd = $restEnd ?: ($shift === 'SIANG' ? '13:00:00' : '01:00:00');
            $regHmAwal = $hmAwal;
            $regHmAkhir = $hmAkhir;
            $regTotalHm = $totalHm;
            $regHmc = $jamKerja;
            $regIstHm = ($restStart && $restEnd) ? round(getMinutesDiff($restStart, $restEnd) / 60, 2) : 1.00;
        }

        $parsedRows[] = [
            'row_idx'            => count($parsedRows) + 1,
            'operator_raw'       => $operatorRaw,
            'employee_id'        => $employeeId,
            'employee_name'      => $employeeName,
            'tanggal'            => $tanggal,
            'shift'              => $shift,
            'shift_type'         => $shiftTypeRaw,
            'is_overtime'        => $isOvertimeRow,
            'unit_id'            => $unitId,
            'waktu_awal'         => $regWaktuAwal,
            'waktu_akhir'        => $regWaktuAkhir,
            'hm_awal'            => $regHmAwal,
            'hm_akhir'           => $regHmAkhir,
            'total_hm'           => $regTotalHm,
            'rest_start'         => $regRestStart,
            'rest_end'           => $regRestEnd,
            'ist_hm'             => $regIstHm,
            'hmc'                => $regHmc,
            'ritase'             => $ritase,
            'solar'              => $solar,
            'keterangan'         => $keterangan,
            'overtime_type'      => $overtimeType,
            'overtime_start'     => $overtimeStart,
            'overtime_end'       => $overtimeEnd,
            'overtime_rest_start'=> $overtimeRestStart,
            'overtime_rest_end'  => $overtimeRestEnd,
            'hm_awal_lembur'     => $hmAwalLembur,
            'hm_akhir_lembur'    => $hmAkhirLembur,
            'earned_hm_incentive'=> $earnedHmIncentive,
            'overtime_amount'    => $overtimeAmount,
            'applied_hm_rate'    => $tarif_hm
        ];
    }

    echo json_encode([
        'status'         => 'success',
        'total_rows'     => count($parsedRows),
        'matched_count'  => $matchedCount,
        'unmatched_count'=> $unmatchedCount,
        'rows'           => $parsedRows
    ]);
    exit;
}

// 3. Batch Save Timesheets (Strict validation - No auto-create)
if ($action === 'save_batch') {
    header('Content-Type: application/json');
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada data yang dikirim untuk disimpan!']);
        exit;
    }

    $items = $data['items'];
    $successCount = 0;
    $failedCount = 0;
    $errors = [];

    // Begin database transaction
    mysqli_begin_transaction($con);

    $insertQuery = "INSERT INTO employee_timesheets 
        (id, employee_id, tanggal, shift, shift_type, unit_id, hm_awal, hm_akhir, waktu_awal, waktu_akhir, rest_start, rest_end, ritase, solar, total_hm, ist_hm, hmc, applied_hm_rate, earned_hm_incentive, keterangan, overtime_type, overtime_start, overtime_end, overtime_rest_start, overtime_rest_end, hm_awal_lembur, hm_akhir_lembur, overtime_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($con, $insertQuery);

    if (!$stmt) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query: ' . mysqli_error($con)]);
        exit;
    }

    foreach ($items as $idx => $row) {
        $empId = isset($row['employee_id']) ? sani($row['employee_id']) : '';
        $rawOperatorName = isset($row['operator_raw']) ? trim($row['operator_raw']) : '';

        // Strict check: Employee must exist
        if (empty($empId) || $empId === '__NEW__') {
            $failedCount++;
            $errors[] = "Baris " . ($idx + 1) . " (" . ($rawOperatorName ?: 'Tanpa Nama') . "): Karyawan belum dipilih di sistem.";
            continue;
        }

        // Verify employee exists in DB
        $checkQ = querySecure($con, "SELECT id FROM employees WHERE id = ? LIMIT 1", [$empId], 's');
        if (!mysqli_fetch_assoc($checkQ)) {
            $failedCount++;
            $errors[] = "Baris " . ($idx + 1) . " (" . ($rawOperatorName ?: 'Tanpa Nama') . "): ID Karyawan tidak ditemukan di database.";
            continue;
        }

        $id = generate_uuid();
        $tanggal = sani($row['tanggal']);
        $shift = in_array($row['shift'], ['SIANG', 'MALAM']) ? $row['shift'] : 'SIANG';
        $shift_type = !empty($row['shift_type']) ? sani($row['shift_type']) : ($row['is_overtime'] ? '2' : '1');
        $unit_id = !empty($row['unit_id']) ? sani($row['unit_id']) : '-';
        $hm_awal = (float) ($row['hm_awal'] ?? 0);
        $hm_akhir = (float) ($row['hm_akhir'] ?? 0);
        $waktu_awal = !empty($row['waktu_awal']) ? sani($row['waktu_awal']) : null;
        $waktu_akhir = !empty($row['waktu_akhir']) ? sani($row['waktu_akhir']) : null;
        $rest_start = !empty($row['rest_start']) ? sani($row['rest_start']) : null;
        $rest_end = !empty($row['rest_end']) ? sani($row['rest_end']) : null;
        $ritase = (int) ($row['ritase'] ?? 0);
        $solar = (float) ($row['solar'] ?? 0);
        $total_hm = (float) ($row['total_hm'] ?? 0);
        $ist_hm = (float) ($row['ist_hm'] ?? 0);
        $hmc = (float) ($row['hmc'] ?? 0);
        $applied_hm_rate = (int) ($row['applied_hm_rate'] ?? 0);
        $earned_hm_incentive = (int) ($row['earned_hm_incentive'] ?? 0);
        $keterangan = !empty($row['keterangan']) ? sani($row['keterangan']) : null;
        $overtime_type = in_array($row['overtime_type'], ['NONE', 'BIASA', 'LIBUR']) ? $row['overtime_type'] : 'NONE';
        $overtime_start = !empty($row['overtime_start']) ? sani($row['overtime_start']) : null;
        $overtime_end = !empty($row['overtime_end']) ? sani($row['overtime_end']) : null;
        $overtime_rest_start = !empty($row['overtime_rest_start']) ? sani($row['overtime_rest_start']) : null;
        $overtime_rest_end = !empty($row['overtime_rest_end']) ? sani($row['overtime_rest_end']) : null;
        $hm_awal_lembur = !empty($row['hm_awal_lembur']) ? (float) $row['hm_awal_lembur'] : null;
        $hm_akhir_lembur = !empty($row['hm_akhir_lembur']) ? (float) $row['hm_akhir_lembur'] : null;
        $overtime_amount = (float) ($row['overtime_amount'] ?? 0);

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssddssssiddddiisssssssdd",
            $id,
            $empId,
            $tanggal,
            $shift,
            $shift_type,
            $unit_id,
            $hm_awal,
            $hm_akhir,
            $waktu_awal,
            $waktu_akhir,
            $rest_start,
            $rest_end,
            $ritase,
            $solar,
            $total_hm,
            $ist_hm,
            $hmc,
            $applied_hm_rate,
            $earned_hm_incentive,
            $keterangan,
            $overtime_type,
            $overtime_start,
            $overtime_end,
            $overtime_rest_start,
            $overtime_rest_end,
            $hm_awal_lembur,
            $hm_akhir_lembur,
            $overtime_amount
        );

        if (mysqli_stmt_execute($stmt)) {
            $successCount++;
        } else {
            $failedCount++;
            $errors[] = "Baris " . ($idx + 1) . " gagal disimpan: " . mysqli_stmt_error($stmt);
        }
    }

    mysqli_stmt_close($stmt);

    if ($successCount > 0) {
        mysqli_commit($con);
        $_SESSION['message'] = "Berhasil mengimpor $successCount data timesheet dari Excel!" . ($failedCount > 0 ? " ($failedCount baris dilewati)." : "");
        $_SESSION['message_type'] = $failedCount > 0 ? 'warning' : 'success';
        echo json_encode([
            'status'               => 'success',
            'success_count'        => $successCount,
            'failed_count'         => $failedCount,
            'errors'               => $errors,
            'message'              => "Berhasil menyimpan $successCount data!"
        ]);
    } else {
        mysqli_rollback($con);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Tidak ada data yang berhasil disimpan.',
            'errors'  => $errors
        ]);
    }
    exit;
}
?>
