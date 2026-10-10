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

// Helper: Parse Excel Date (e.g. 01/10/2026, 01-Oct-26, 2026-10-01)
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

        // Format DD-Mon-YY or DD-Mon-YYYY (e.g., 01-Oct-26, 01-Oktober-2026)
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

        $ts = strtotime($dateStr);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}

// Helper: Parse Excel Time (e.g. 07.00, 15:03, 15.29 -> 15:29:00)
if (!function_exists('parseExcelTime')) {
    function parseExcelTime($timeStr) {
        $timeStr = trim($timeStr);
        if (empty($timeStr) || $timeStr === '-' || $timeStr === '0' || $timeStr === '0,00' || $timeStr === '0.00') {
            return null;
        }

        if (strpos($timeStr, ':') !== false) {
            $parts = explode(':', $timeStr);
            $h = (int) $parts[0];
            $m = isset($parts[1]) ? (int) $parts[1] : 0;
            $s = isset($parts[2]) ? (int) $parts[2] : 0;
            return sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

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

// Helper: Parse Excel Number (e.g. 53,17 -> 53.17, 1.011 -> 1011, - -> 0)
if (!function_exists('parseExcelNumber')) {
    function parseExcelNumber($numStr) {
        $numStr = trim($numStr);
        if (empty($numStr) || $numStr === '-') return 0.00;
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
        $name = preg_replace('/\([^)]*\)/', ' ', $name);
        $name = preg_replace('/[.\-,_]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }
}

// Helper: Advanced Driver Matching
if (!function_exists('matchHaulingDriver')) {
    function matchHaulingDriver($driverRaw, $employees, $normalizedEmpMap) {
        $normOp = enhancedNormalize($driverRaw);
        if (empty($normOp)) return [null, ''];

        // 1. Exact match
        if (isset($normalizedEmpMap[$normOp])) {
            return [$normalizedEmpMap[$normOp]['id'], $normalizedEmpMap[$normOp]['full_name']];
        }

        $opWords = explode(' ', $normOp);

        // 2. Prefix / abbreviation match
        $candidates = [];
        foreach ($employees as $emp) {
            $normDb = enhancedNormalize($emp['full_name']);
            if ($normDb === $normOp) {
                return [$emp['id'], $emp['full_name']];
            }

            $dbWords = explode(' ', $normDb);

            if (strpos($normDb, $normOp) === 0) {
                $candidates[] = ['emp' => $emp, 'score' => 95];
                continue;
            }

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

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// 1. Real-time Employee Search API for Select2
if ($action === 'search_employees') {
    header('Content-Type: application/json');
    $q = isset($_GET['q']) ? sani($_GET['q']) : (isset($_POST['q']) ? sani($_POST['q']) : '');
    
    $where = "";
    $params = [];
    $types = "";
    if (!empty($q)) {
        $where = "WHERE full_name LIKE ? OR employee_id LIKE ? OR position LIKE ?";
        $searchTerm = "%$q%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        $types = "sss";
        $empRes = querySecure($con, "SELECT id, full_name, employee_id, position, level FROM employees $where ORDER BY (level = 'hauling') DESC, full_name ASC LIMIT 50", $params, $types);
    } else {
        $empRes = querySecure($con, "SELECT id, full_name, employee_id, position, level FROM employees ORDER BY (level = 'hauling') DESC, full_name ASC LIMIT 50", [], '');
    }

    $results = [];
    while ($emp = mysqli_fetch_assoc($empRes)) {
        $levelBadge = ($emp['level'] === 'hauling') ? '[Hauling] ' : '[Mining] ';
        $results[] = [
            'id'   => $emp['id'],
            'text' => $levelBadge . $emp['full_name'] . (!empty($emp['employee_id']) ? ' (' . $emp['employee_id'] . ')' : '') . ' - ' . $emp['position']
        ];
    }

    echo json_encode(['results' => $results]);
    exit;
}

// 2. Parse & Preview Excel Data
if ($action === 'preview') {
    header('Content-Type: application/json');

    $rawData = isset($_POST['raw_data']) ? $_POST['raw_data'] : '';
    if (empty(trim($rawData))) {
        echo json_encode(['status' => 'error', 'message' => 'Data input kosong!']);
        exit;
    }

    // Get current global rates
    $hmRateQ = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
    $hmRateRow = mysqli_fetch_assoc($hmRateQ);
    $tarif_hm = isset($hmRateRow['setting_value']) ? (float) $hmRateRow['setting_value'] : 17000;

    $otRateQ = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
    $otRateRow = mysqli_fetch_assoc($otRateQ);
    $tarif_lembur = isset($otRateRow['setting_value']) ? (float) $otRateRow['setting_value'] : 19509;

    $tonS1Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s1'");
    $tonS1Row = mysqli_fetch_assoc($tonS1Q);
    $tarif_tonase_s1 = isset($tonS1Row['setting_value']) ? (float) $tonS1Row['setting_value'] : 3000;

    $tonS2Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s2'");
    $tonS2Row = mysqli_fetch_assoc($tonS2Q);
    $tarif_tonase_s2 = isset($tonS2Row['setting_value']) ? (float) $tonS2Row['setting_value'] : 3500;

    // Load master employees
    $empRes = mysqli_query($con, "SELECT id, full_name, employee_id, level FROM employees");
    $employees = [];
    $normalizedEmpMap = [];
    while ($row = mysqli_fetch_assoc($empRes)) {
        $employees[] = $row;
        $norm = enhancedNormalize($row['full_name']);
        $normalizedEmpMap[$norm] = $row;
    }

    $lines = explode("\n", $rawData);
    $parsedRows = [];
    $matchedCount = 0;
    $unmatchedCount = 0;

    foreach ($lines as $lineIndex => $line) {
        $line = trim($line);
        if (empty($line)) continue;

        $cols = explode("\t", $line);
        // If not tab separated, try semicolon or comma
        if (count($cols) < 5) {
            if (strpos($line, ';') !== false) {
                $cols = explode(';', $line);
            }
        }

        if (count($cols) < 4) continue;

        // Skip header lines
        if (stripos($cols[0], 'tanggal') !== false || stripos($cols[1] ?? '', 'driver') !== false) {
            continue;
        }

        $tanggalRaw = trim($cols[0] ?? '');
        $tanggal = parseExcelDate($tanggalRaw);
        if (!$tanggal) continue;

        $driverRaw = trim($cols[1] ?? '');
        $penggantiRaw = trim($cols[2] ?? '');
        $unitId = strtoupper(trim($cols[3] ?? ''));
        $ritaseKe = (int) (trim($cols[4] ?? '1') ?: 1);
        $tonase = parseExcelNumber($cols[5] ?? '0');
        $kmAwal = parseExcelNumber($cols[6] ?? '0');
        $kmAkhir = parseExcelNumber($cols[7] ?? '0');
        $totalKm = parseExcelNumber($cols[8] ?? '0');
        if ($totalKm <= 0 && $kmAkhir > $kmAwal && $kmAwal > 0) {
            $totalKm = $kmAkhir - $kmAwal;
        }

        $jamMulai = parseExcelTime($cols[9] ?? '');
        $jamLoading = parseExcelTime($cols[10] ?? '');
        $jamTimbangAwal = parseExcelTime($cols[11] ?? '');
        $durasiLoadingTimbang = parseExcelNumber($cols[12] ?? '0');
        $jamBongkar = parseExcelTime($cols[13] ?? '');
        $jamTimbangAkhir = parseExcelTime($cols[14] ?? '');
        $durasiTimbangAwalAkhir = parseExcelNumber($cols[15] ?? '0');
        $jamTibaSite = parseExcelTime($cols[16] ?? '');
        $durasiTimbangSite = parseExcelNumber($cols[17] ?? '0');
        $restTime = parseExcelTime($cols[18] ?? '');
        $p5mTime = parseExcelTime($cols[19] ?? '');

        $cuciRaw = trim($cols[20] ?? '');
        $cuci = (!empty($cuciRaw) && ($cuciRaw === '✓' || strtolower($cuciRaw) === 'v' || $cuciRaw === '1' || strtolower($cuciRaw) === 'true')) ? 1 : 0;

        $safetyRaw = trim($cols[21] ?? '');
        $safety = (!empty($safetyRaw) && ($safetyRaw === '✓' || strtolower($safetyRaw) === 'v' || $safetyRaw === '1' || strtolower($safetyRaw) === 'true')) ? 1 : 0;

        $hmS1 = parseExcelNumber($cols[22] ?? '0');
        $otHours = parseExcelNumber($cols[23] ?? '0');
        $keterangan = trim($cols[24] ?? '');

        // Shift type & Tonase Calculation
        // Shift 1: Ritase 1, Tonase * 3.000
        // Shift 2 / OTW: Ritase 2, Tonase * 3.500
        $shiftType = ($ritaseKe >= 2) ? '2' : '1';
        $tonaseRate = ($shiftType === '2') ? (int) $tarif_tonase_s2 : (int) $tarif_tonase_s1;
        $earnedTonase = round($tonase * $tonaseRate, 2);

        // HM & OT Calculation with Depnaker Overtime Scale (1st hr 1.5x, 2nd hr+ 2x)
        $earnedHm = round($hmS1 * $tarif_hm, 2);
        $earnedOt = calcOvertimeAmount($otHours, $tarif_lembur);

        // Employee Matching
        list($driverId, $driverName) = matchHaulingDriver($driverRaw, $employees, $normalizedEmpMap);
        list($subDriverId, $subDriverName) = matchHaulingDriver($penggantiRaw, $employees, $normalizedEmpMap);

        if (!empty($driverId)) {
            $matchedCount++;
        } else {
            $unmatchedCount++;
        }

        $parsedRows[] = [
            'tanggal'                   => $tanggal,
            'driver_raw'                => $driverRaw,
            'employee_id'               => $driverId ?: '',
            'employee_name'             => $driverName ?: '',
            'pengganti_raw'             => $penggantiRaw,
            'substitute_employee_id'    => $subDriverId ?: '',
            'substitute_employee_name'  => $subDriverName ?: '',
            'unit_id'                   => $unitId,
            'ritase_ke'                 => $ritaseKe,
            'shift_type'                => $shiftType,
            'tonase'                    => $tonase,
            'km_awal'                   => $kmAwal,
            'km_akhir'                  => $kmAkhir,
            'total_km'                  => $totalKm,
            'jam_mulai'                 => $jamMulai,
            'jam_loading'               => $jamLoading,
            'jam_timbang_awal'          => $jamTimbangAwal,
            'durasi_loading_timbang'    => $durasiLoadingTimbang,
            'jam_bongkar'               => $jamBongkar,
            'jam_timbang_akhir'         => $jamTimbangAkhir,
            'durasi_timbang_awal_akhir' => $durasiTimbangAwalAkhir,
            'jam_tiba_site'             => $jamTibaSite,
            'durasi_timbang_site'       => $durasiTimbangSite,
            'rest_time'                 => $restTime,
            'p5m_time'                  => $p5mTime,
            'cuci'                      => $cuci,
            'safety'                    => $safety,
            'hm_s1'                     => $hmS1,
            'ot_hours'                  => $otHours,
            'tonase_rate'               => $tonaseRate,
            'earned_tonase_incentive'   => $earnedTonase,
            'earned_hm_incentive'       => $earnedHm,
            'overtime_amount'           => $earnedOt,
            'keterangan'                => $keterangan
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

// 3. Batch Save Imported Data to Database
if ($action === 'save_batch') {
    header('Content-Type: application/json');

    $jsonInput = file_get_contents('php://input');
    $payload = json_decode($jsonInput, true);

    if (!$payload || !isset($payload['rows']) || !is_array($payload['rows'])) {
        echo json_encode(['status' => 'error', 'message' => 'Format payload tidak valid!']);
        exit;
    }

    $rowsToSave = $payload['rows'];
    if (empty($rowsToSave)) {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada baris yang dipilih untuk disimpan!']);
        exit;
    }

    mysqli_begin_transaction($con);

    $insertedCount = 0;
    $errors = [];

    $insertSql = "INSERT INTO hauling_timesheets (
        id, employee_id, substitute_employee_id, tanggal, unit_id, 
        ritase_ke, shift_type, tonase, km_awal, km_akhir, total_km,
        jam_mulai, jam_loading, jam_timbang_awal, durasi_loading_timbang,
        jam_bongkar, jam_timbang_akhir, durasi_timbang_awal_akhir, jam_tiba_site, durasi_timbang_site,
        rest_time, p5m_time, cuci, safety, hm_s1, ot_hours,
        tonase_rate, earned_tonase_incentive, earned_hm_incentive, overtime_amount, keterangan
    ) VALUES (
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?, ?, 
        ?, ?, ?, ?, ?
    )";

    $stmt = mysqli_prepare($con, $insertSql);
    if (!$stmt) {
        mysqli_rollback($con);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mempersiapkan query: ' . mysqli_error($con)]);
        exit;
    }

    foreach ($rowsToSave as $idx => $r) {
        $empId = trim($r['employee_id'] ?? '');
        if (empty($empId)) {
            continue; // Skip rows without matched employee
        }

        $id = generate_uuid();
        $subEmpId = !empty($r['substitute_employee_id']) ? trim($r['substitute_employee_id']) : null;
        $tanggal = sani($r['tanggal']);
        $unitId = sani($r['unit_id'] ?? '');
        $ritaseKe = (int) ($r['ritase_ke'] ?? 1);
        $shiftType = sani($r['shift_type'] ?? '1');
        $tonase = (float) ($r['tonase'] ?? 0);
        $kmAwal = (float) ($r['km_awal'] ?? 0);
        $kmAkhir = (float) ($r['km_akhir'] ?? 0);
        $totalKm = (float) ($r['total_km'] ?? 0);

        $jamMulai = !empty($r['jam_mulai']) ? sani($r['jam_mulai']) : null;
        $jamLoading = !empty($r['jam_loading']) ? sani($r['jam_loading']) : null;
        $jamTimbangAwal = !empty($r['jam_timbang_awal']) ? sani($r['jam_timbang_awal']) : null;
        $durLoadingTimbang = (float) ($r['durasi_loading_timbang'] ?? 0);
        $jamBongkar = !empty($r['jam_bongkar']) ? sani($r['jam_bongkar']) : null;
        $jamTimbangAkhir = !empty($r['jam_timbang_akhir']) ? sani($r['jam_timbang_akhir']) : null;
        $durTimbangAwalAkhir = (float) ($r['durasi_timbang_awal_akhir'] ?? 0);
        $jamTibaSite = !empty($r['jam_tiba_site']) ? sani($r['jam_tiba_site']) : null;
        $durTimbangSite = (float) ($r['durasi_timbang_site'] ?? 0);

        $restTime = !empty($r['rest_time']) ? sani($r['rest_time']) : null;
        $p5mTime = !empty($r['p5m_time']) ? sani($r['p5m_time']) : null;
        $cuci = (int) ($r['cuci'] ?? 0);
        $safety = (int) ($r['safety'] ?? 0);
        $hmS1 = (float) ($r['hm_s1'] ?? 0);
        $otHours = (float) ($r['ot_hours'] ?? 0);

        $tonaseRate = (int) ($r['tonase_rate'] ?? ($shiftType === '2' ? 3500 : 3000));
        $earnedTonase = (float) ($r['earned_tonase_incentive'] ?? ($tonase * $tonaseRate));
        $earnedHm = (float) ($r['earned_hm_incentive'] ?? ($hmS1 * 17000));
        $overtimeAmt = (float) ($r['overtime_amount'] ?? ($otHours * 19509));
        $keterangan = !empty($r['keterangan']) ? sani($r['keterangan']) : null;

        // Bind 31 parameters
        mysqli_stmt_bind_param(
            $stmt,
            'sssssisddddssssssssssiiiiidddds',
            $id, $empId, $subEmpId, $tanggal, $unitId,
            $ritaseKe, $shiftType, $tonase, $kmAwal, $kmAkhir, $totalKm,
            $jamMulai, $jamLoading, $jamTimbangAwal, $durLoadingTimbang,
            $jamBongkar, $jamTimbangAkhir, $durTimbangAwalAkhir, $jamTibaSite, $durTimbangSite,
            $restTime, $p5mTime, $cuci, $safety, $hmS1, $otHours,
            $tonaseRate, $earnedTonase, $earnedHm, $overtimeAmt, $keterangan
        );

        if (mysqli_stmt_execute($stmt)) {
            $insertedCount++;
        } else {
            $errors[] = "Baris " . ($idx + 1) . ": " . mysqli_stmt_error($stmt);
        }
    }

    mysqli_stmt_close($stmt);

    if ($insertedCount > 0) {
        mysqli_commit($con);
        $_SESSION['message'] = "Berhasil mengimpor $insertedCount data timesheet hauling ke sistem!";
        $_SESSION['message_type'] = 'success';

        echo json_encode([
            'status'         => 'success',
            'inserted_count' => $insertedCount,
            'errors'         => $errors
        ]);
    } else {
        mysqli_rollback($con);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Tidak ada data yang berhasil disimpan. Pastikan karyawan sudah dipilih!',
            'errors'  => $errors
        ]);
    }
    exit;
}
