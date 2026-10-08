<?php
// Function to calculate minutes difference between two times
function getMinutesDiff($start, $end) {
    if (!$start || !$end) return 0;
    $t1 = strtotime($start);
    $t2 = strtotime($end);
    if ($t2 < $t1) {
        $t2 += 86400; // Next day
    }
    return ($t2 - $t1) / 60;
}

if (isset($_POST['addData']) || isset($_POST['updateData'])) {
    $employee_id = sani($_POST['employee_id']);
    $tanggal = sani($_POST['tanggal']);
    $shift = sani($_POST['shift']);
    $unit_id = sani($_POST['unit_id']);
    $hm_awal = (float) $_POST['hm_awal'];
    $hm_akhir = (float) $_POST['hm_akhir'];
    $waktu_awal = !empty($_POST['waktu_awal']) ? sani($_POST['waktu_awal']) : null;
    $waktu_akhir = !empty($_POST['waktu_akhir']) ? sani($_POST['waktu_akhir']) : null;
    $rest_start = !empty($_POST['rest_start']) ? sani($_POST['rest_start']) : null;
    $rest_end = !empty($_POST['rest_end']) ? sani($_POST['rest_end']) : null;
    $ritase = (int) $_POST['ritase'];
    $solar = (float) $_POST['solar'];
    $keterangan = !empty($_POST['keterangan']) ? sani($_POST['keterangan']) : null;
    
    // Lembur
    $overtime_type = isset($_POST['overtime_type']) ? sani($_POST['overtime_type']) : 'NONE';
    $overtime_start = !empty($_POST['overtime_start']) ? sani($_POST['overtime_start']) : null;
    $overtime_end = !empty($_POST['overtime_end']) ? sani($_POST['overtime_end']) : null;
    $overtime_rest_start = !empty($_POST['overtime_rest_start']) ? sani($_POST['overtime_rest_start']) : null;
    $overtime_rest_end = !empty($_POST['overtime_rest_end']) ? sani($_POST['overtime_rest_end']) : null;
    $hm_awal_lembur = !empty($_POST['hm_awal_lembur']) ? (float) $_POST['hm_awal_lembur'] : null;
    $hm_akhir_lembur = !empty($_POST['hm_akhir_lembur']) ? (float) $_POST['hm_akhir_lembur'] : null;

    // Calculations
    $total_hm = $hm_akhir - $hm_awal;
    
    // Time Calculation (Work Duration - Rest Duration)
    $work_mins = getMinutesDiff($waktu_awal, $waktu_akhir);
    
    $ist_mins = 0;
    if ($rest_start && $rest_end) {
        $ist_mins = getMinutesDiff($rest_start, $rest_end);
    }
    
    $ist_hm = $ist_mins / 60;
    $effective_work_hours = ($work_mins - $ist_mins) / 60;
    if ($effective_work_hours < 0) $effective_work_hours = 0;
    
    $hmc = $effective_work_hours; // HMC is now representing Total Working Hours

    // Get HM Rate from settings
    $rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
    $rateRow = mysqli_fetch_assoc($rateQuery);
    $applied_hm_rate = isset($rateRow['setting_value']) ? (int) $rateRow['setting_value'] : 17000;

    // Get Tarif Lembur
    $rateQuery2 = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
    $rateRow2 = mysqli_fetch_assoc($rateQuery2);
    $tarif_lembur = isset($rateRow2['setting_value']) ? (float) $rateRow2['setting_value'] : 19509;

    $shift_type = isset($_POST['shift_type']) ? sani($_POST['shift_type']) : '1';

    $overtime_amount = 0;
    if ($shift_type === '2') {
        // Shift 2 selalu dihitung sebagai lembur (OT)
        $overtime_type = 'BIASA';
        $overtime_amount = round($hmc * $tarif_lembur, 2);
    } elseif ($overtime_type !== 'NONE') {
        $ot_hours = 0;
        if ($overtime_start && $overtime_end) {
            $diff_mins = getMinutesDiff($overtime_start, $overtime_end);
            $ot_rest_mins = 0;
            if ($overtime_rest_start && $overtime_rest_end) {
                $ot_rest_mins = getMinutesDiff($overtime_rest_start, $overtime_rest_end);
            }
            $ot_hours = ($diff_mins - $ot_rest_mins) / 60;
            if ($ot_hours < 0) $ot_hours = 0;
        } elseif ($hm_akhir_lembur && $hm_awal_lembur && $hm_akhir_lembur > $hm_awal_lembur) {
            $ot_hours = $hm_akhir_lembur - $hm_awal_lembur;
        } else {
            $ot_hours = $effective_work_hours;
        }

        // Jika jam lembur terpisah dari jam reguler dalam satu form input, akumulasikan ke total HMC
        if ($overtime_start && $overtime_end && $effective_work_hours > 0) {
            $hmc = $effective_work_hours + $ot_hours;
        }

        // Sesuai skema baru: Jam lembur dikalikan tarif lembur (Rp 19.509)
        $overtime_amount = round($ot_hours * $tarif_lembur, 2);
    }

    // Insentif HM: Total Jam Kerja x Tarif HM (Rp 17.000)
    $earned_hm_incentive = (int) round($hmc * $applied_hm_rate);

    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $query = "INSERT INTO employee_timesheets (id, employee_id, tanggal, shift, shift_type, unit_id, hm_awal, hm_akhir, waktu_awal, waktu_akhir, rest_start, rest_end, ritase, solar, total_hm, ist_hm, hmc, applied_hm_rate, earned_hm_incentive, keterangan, overtime_type, overtime_start, overtime_end, overtime_rest_start, overtime_rest_end, hm_awal_lembur, hm_akhir_lembur, overtime_amount) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $employee_id, $tanggal, $shift, $shift_type, $unit_id, $hm_awal, $hm_akhir, $waktu_awal, $waktu_akhir, $rest_start, $rest_end, $ritase, $solar, $total_hm, $ist_hm, $hmc, $applied_hm_rate, $earned_hm_incentive, $keterangan, $overtime_type, $overtime_start, $overtime_end, $overtime_rest_start, $overtime_rest_end, $hm_awal_lembur, $hm_akhir_lembur, $overtime_amount];
        $types = "ssssssddssssiddddiisssssssdd";
        
        if (executeSecure($con, $query, $params, $types)) {
            $_SESSION['message'] = 'Data timesheet berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menambahkan data!';
            $_SESSION['message_type'] = 'error';
        }
        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_timesheets';
        header("Location: $redirectUrl");
        exit;
    } 
    elseif (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $query = "UPDATE employee_timesheets SET 
                    employee_id = ?, tanggal = ?, shift = ?, shift_type = ?, unit_id = ?, 
                    hm_awal = ?, hm_akhir = ?, waktu_awal = ?, waktu_akhir = ?, rest_start = ?, rest_end = ?, 
                    ritase = ?, solar = ?, total_hm = ?, ist_hm = ?, hmc = ?, 
                    applied_hm_rate = ?, earned_hm_incentive = ?, keterangan = ?,
                    overtime_type = ?, overtime_start = ?, overtime_end = ?, overtime_rest_start = ?, overtime_rest_end = ?, hm_awal_lembur = ?, hm_akhir_lembur = ?, overtime_amount = ? 
                  WHERE id = ?";
        $params = [$employee_id, $tanggal, $shift, $shift_type, $unit_id, $hm_awal, $hm_akhir, $waktu_awal, $waktu_akhir, $rest_start, $rest_end, $ritase, $solar, $total_hm, $ist_hm, $hmc, $applied_hm_rate, $earned_hm_incentive, $keterangan, $overtime_type, $overtime_start, $overtime_end, $overtime_rest_start, $overtime_rest_end, $hm_awal_lembur, $hm_akhir_lembur, $overtime_amount, $id];
        $types = "sssssddssssiddddiisssssssdds";
        
        if (executeSecure($con, $query, $params, $types)) {
            $_SESSION['message'] = 'Data timesheet berhasil diupdate!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal mengupdate data!';
            $_SESSION['message_type'] = 'error';
        }
        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_timesheets';
        header("Location: $redirectUrl");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);
    $query = "DELETE FROM employee_timesheets WHERE id = ?";
    if (executeSecure($con, $query, [$id], "s")) {
        $_SESSION['message'] = 'Data timesheet berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus data!';
        $_SESSION['message_type'] = 'error';
    }
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_timesheets';
    header("Location: $redirectUrl");
    exit;
} 
?>
