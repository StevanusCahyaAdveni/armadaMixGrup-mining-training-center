<?php
/**
 * Action API: Performance Dashboard Drill-down Details
 * Path: actions/pages/dashboard/performance-detail.php
 */

header('Content-Type: application/json; charset=utf-8');

if (!isset($con)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once __DIR__ . '/../../../config.php';
    require_once __DIR__ . '/../../../functions/sanitasi.php';
    require_once __DIR__ . '/../../../functions/secure_query.php';
}

$action = isset($_GET['action']) ? sani($_GET['action']) : (isset($_POST['action']) ? sani($_POST['action']) : '');
$date = isset($_GET['date']) ? sani($_GET['date']) : (isset($_POST['date']) ? sani($_POST['date']) : '');

if (empty($date)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tanggal (date) wajib disertakan!']);
    exit;
}

// 1. Hauling Daily Drill-down Detail
if ($action === 'get_hauling_detail') {
    $sql = "SELECT 
                t.*,
                COALESCE(t.unit_id, '') as unit,
                COALESCE(e.full_name, t.employee_id) as driver_name,
                e.employee_id as driver_nik,
                sub.full_name as substitute_name
            FROM hauling_timesheets t
            LEFT JOIN employees e ON t.employee_id = e.id
            LEFT JOIN employees sub ON t.substitute_employee_id = sub.id
            WHERE t.tanggal = '$date'
            ORDER BY t.ritase_ke ASC, t.jam_mulai ASC, t.created_at ASC";

    $res = mysqli_query($con, $sql);
    if (!$res) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
        exit;
    }

    $rows = [];
    $totalRitase = 0;
    $totalTonase = 0;
    $totalInsTonase = 0;
    $totalHmS1 = 0;
    $totalOtReal = 0;
    $totalOtEff = 0;
    $totalInsHm = 0;
    $totalOvertime = 0;
    $totalMoney = 0;

    while ($r = mysqli_fetch_assoc($res)) {
        $totalRitase++;
        $ton = (float) $r['tonase'];
        $insTon = (float) $r['earned_tonase_incentive'];
        $hm1 = (float) $r['hm_s1'];
        $otReal = (float) $r['ot_hours'];
        
        $otEff = ($otReal <= 0) ? 0 : (($otReal <= 1) ? $otReal * 1.5 : 1.5 + ($otReal - 1) * 2);
        $insHm = (float) $r['earned_hm_incentive'];
        $otAmt = (float) $r['overtime_amount'];
        $grandLine = $insTon + $insHm + $otAmt;

        $totalTonase += $ton;
        $totalInsTonase += $insTon;
        $totalHmS1 += $hm1;
        $totalOtReal += $otReal;
        $totalOtEff += $otEff;
        $totalInsHm += $insHm;
        $totalOvertime += $otAmt;
        $totalMoney += $grandLine;

        $r['tonase_formatted'] = number_format($ton, 2, ',', '.');
        $r['insentif_tonase_formatted'] = 'Rp ' . number_format($insTon, 0, ',', '.');
        $r['hm_s1_formatted'] = number_format($hm1, 1, ',', '.');
        $r['ot_real_formatted'] = number_format($otReal, 1, ',', '.');
        $r['ot_eff_formatted'] = number_format($otEff, 1, ',', '.');
        $r['insentif_hm_formatted'] = 'Rp ' . number_format($insHm, 0, ',', '.');
        $r['overtime_amount_formatted'] = 'Rp ' . number_format($otAmt, 0, ',', '.');
        $r['total_line_formatted'] = 'Rp ' . number_format($grandLine, 0, ',', '.');
        $r['durasi_step1'] = number_format((float)$r['durasi_loading_timbang'], 2, ',', '.');
        $r['durasi_step2'] = number_format((float)$r['durasi_timbang_awal_akhir'], 2, ',', '.');
        $r['durasi_step3'] = number_format((float)$r['durasi_timbang_site'], 2, ',', '.');

        $rows[] = $r;
    }

    echo json_encode([
        'status' => 'success',
        'type' => 'hauling',
        'date' => $date,
        'date_formatted' => date('d F Y', strtotime($date)),
        'summary' => [
            'total_ritase' => $totalRitase,
            'total_tonase' => number_format($totalTonase, 2, ',', '.'),
            'total_tonase_raw' => $totalTonase,
            'total_insentif_tonase' => 'Rp ' . number_format($totalInsTonase, 0, ',', '.'),
            'total_hm_s1' => number_format($totalHmS1, 1, ',', '.'),
            'total_ot_real' => number_format($totalOtReal, 1, ',', '.'),
            'total_ot_eff' => number_format($totalOtEff, 1, ',', '.'),
            'total_insentif_hm' => 'Rp ' . number_format($totalInsHm, 0, ',', '.'),
            'total_overtime' => 'Rp ' . number_format($totalOvertime, 0, ',', '.'),
            'total_money' => 'Rp ' . number_format($totalMoney, 0, ',', '.')
        ],
        'rows' => $rows
    ]);
    exit;
}

// 2. Mining Daily Drill-down Detail
if ($action === 'get_mining_detail') {
    $sql = "SELECT 
                t.*,
                COALESCE(t.unit_id, '') as unit,
                COALESCE(e.full_name, t.employee_id) as operator_name,
                e.employee_id as operator_nik
            FROM employee_timesheets t
            LEFT JOIN employees e ON t.employee_id = e.id
            WHERE t.tanggal = '$date'
            ORDER BY t.shift ASC, t.shift_type ASC, e.full_name ASC, t.created_at ASC";

    $res = mysqli_query($con, $sql);
    if (!$res) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
        exit;
    }

    $rows = [];
    $totalLogs = 0;
    $totalHmc = 0;
    $totalHmS1 = 0;
    $totalHmS2 = 0;
    $totalOtEff = 0;
    $totalInsHm = 0;
    $totalOvertime = 0;
    $totalMoney = 0;
    $totalRitase = 0;
    $totalSolar = 0;

    while ($r = mysqli_fetch_assoc($res)) {
        $totalLogs++;
        $hmc = (float) $r['hmc'];
        $isS2 = ($r['shift_type'] === '2' || $r['overtime_type'] !== 'NONE');
        $hmS1 = !$isS2 ? $hmc : 0;
        $hmS2 = $isS2 ? $hmc : 0;
        
        $otEff = 0;
        if ($isS2) {
            $otEff = ($hmc <= 0) ? 0 : (($hmc <= 1) ? $hmc * 1.5 : 1.5 + ($hmc - 1) * 2);
        }

        $insHm = (float) $r['earned_hm_incentive'];
        $otAmt = (float) $r['overtime_amount'];
        $rit = (int) $r['ritase'];
        $sol = (float) $r['solar'];
        $lineMoney = $insHm + $otAmt;

        $totalHmc += $hmc;
        $totalHmS1 += $hmS1;
        $totalHmS2 += $hmS2;
        $totalOtEff += $otEff;
        $totalInsHm += $insHm;
        $totalOvertime += $otAmt;
        $totalMoney += $lineMoney;
        $totalRitase += $rit;
        $totalSolar += $sol;

        $r['hmc_formatted'] = number_format($hmc, 2, ',', '.');
        $r['hm_s1_formatted'] = number_format($hmS1, 2, ',', '.');
        $r['hm_s2_formatted'] = number_format($hmS2, 2, ',', '.');
        $r['ot_eff_formatted'] = number_format($otEff, 2, ',', '.');
        $r['insentif_hm_formatted'] = 'Rp ' . number_format($insHm, 0, ',', '.');
        $r['overtime_amount_formatted'] = 'Rp ' . number_format($otAmt, 0, ',', '.');
        $r['total_line_formatted'] = 'Rp ' . number_format($lineMoney, 0, ',', '.');
        $r['solar_formatted'] = number_format($sol, 1, ',', '.');

        $rows[] = $r;
    }

    echo json_encode([
        'status' => 'success',
        'type' => 'mining',
        'date' => $date,
        'date_formatted' => date('d F Y', strtotime($date)),
        'summary' => [
            'total_logs' => $totalLogs,
            'total_hmc' => number_format($totalHmc, 2, ',', '.'),
            'total_hm_s1' => number_format($totalHmS1, 2, ',', '.'),
            'total_hm_s2' => number_format($totalHmS2, 2, ',', '.'),
            'total_ot_eff' => number_format($totalOtEff, 2, ',', '.'),
            'total_insentif_hm' => 'Rp ' . number_format($totalInsHm, 0, ',', '.'),
            'total_overtime' => 'Rp ' . number_format($totalOvertime, 0, ',', '.'),
            'total_money' => 'Rp ' . number_format($totalMoney, 0, ',', '.'),
            'total_ritase' => number_format($totalRitase),
            'total_solar' => number_format($totalSolar, 1, ',', '.') . ' L'
        ],
        'rows' => $rows
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali!']);
exit;
