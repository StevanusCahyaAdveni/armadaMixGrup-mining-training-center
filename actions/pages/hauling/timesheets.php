<?php

// Rates from settings
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $employee_id = sani($_POST['employee_id']);
        $substitute_employee_id = !empty($_POST['substitute_employee_id']) ? sani($_POST['substitute_employee_id']) : null;
        $tanggal = sani($_POST['tanggal']);
        $unit_id = strtoupper(sani($_POST['unit_id']));
        $ritase_ke = (int) (sani($_POST['ritase_ke'] ?? '1') ?: 1);
        $shift_type = sani($_POST['shift_type'] ?? ($ritase_ke >= 2 ? '2' : '1'));
        
        $tonase = (float) (sani($_POST['tonase'] ?? '0'));
        $km_awal = (float) (sani($_POST['km_awal'] ?? '0'));
        $km_akhir = (float) (sani($_POST['km_akhir'] ?? '0'));
        $total_km = (float) (sani($_POST['total_km'] ?? '0'));
        if ($total_km <= 0 && $km_akhir > $km_awal && $km_awal > 0) {
            $total_km = $km_akhir - $km_awal;
        }

        $jam_mulai = !empty($_POST['jam_mulai']) ? sani($_POST['jam_mulai']) : null;
        $jam_loading = !empty($_POST['jam_loading']) ? sani($_POST['jam_loading']) : null;
        $jam_timbang_awal = !empty($_POST['jam_timbang_awal']) ? sani($_POST['jam_timbang_awal']) : null;
        $durasi_loading_timbang = (float) (sani($_POST['durasi_loading_timbang'] ?? '0'));
        $jam_bongkar = !empty($_POST['jam_bongkar']) ? sani($_POST['jam_bongkar']) : null;
        $jam_timbang_akhir = !empty($_POST['jam_timbang_akhir']) ? sani($_POST['jam_timbang_akhir']) : null;
        $durasi_timbang_awal_akhir = (float) (sani($_POST['durasi_timbang_awal_akhir'] ?? '0'));
        $jam_tiba_site = !empty($_POST['jam_tiba_site']) ? sani($_POST['jam_tiba_site']) : null;
        $durasi_timbang_site = (float) (sani($_POST['durasi_timbang_site'] ?? '0'));

        $rest_time = !empty($_POST['rest_time']) ? sani($_POST['rest_time']) : null;
        $p5m_time = !empty($_POST['p5m_time']) ? sani($_POST['p5m_time']) : null;
        $cuci = isset($_POST['cuci']) ? 1 : 0;
        $safety = isset($_POST['safety']) ? 1 : 0;

        $hm_s1 = (float) (sani($_POST['hm_s1'] ?? '0'));
        $ot_hours = (float) (sani($_POST['ot_hours'] ?? '0'));
        $keterangan = !empty($_POST['keterangan']) ? sani($_POST['keterangan']) : null;

        $tonase_rate = ($shift_type === '2') ? (int) $tarif_tonase_s2 : (int) $tarif_tonase_s1;
        $earned_tonase_incentive = round($tonase * $tonase_rate, 2);
        $earned_hm_incentive = round($hm_s1 * $tarif_hm, 2);
        $overtime_amount = round($ot_hours * $tarif_lembur, 2);

        $query = "INSERT INTO hauling_timesheets (
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

        $params = [
            $id, $employee_id, $substitute_employee_id, $tanggal, $unit_id,
            $ritase_ke, $shift_type, $tonase, $km_awal, $km_akhir, $total_km,
            $jam_mulai, $jam_loading, $jam_timbang_awal, $durasi_loading_timbang,
            $jam_bongkar, $jam_timbang_akhir, $durasi_timbang_awal_akhir, $jam_tiba_site, $durasi_timbang_site,
            $rest_time, $p5m_time, $cuci, $safety, $hm_s1, $ot_hours,
            $tonase_rate, $earned_tonase_incentive, $earned_hm_incentive, $overtime_amount, $keterangan
        ];
        $types = 'sssssisddddssssssssssiiiiidddds';

        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            $_SESSION['message'] = 'Data timesheet hauling berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat menambahkan data: ' . mysqli_error($con);
            $_SESSION['message_type'] = 'error';
        }

        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=hauling_timesheets';
        header("Location: $redirectUrl");
        exit;
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $employee_id = sani($_POST['employee_id']);
        $substitute_employee_id = !empty($_POST['substitute_employee_id']) ? sani($_POST['substitute_employee_id']) : null;
        $tanggal = sani($_POST['tanggal']);
        $unit_id = strtoupper(sani($_POST['unit_id']));
        $ritase_ke = (int) (sani($_POST['ritase_ke'] ?? '1') ?: 1);
        $shift_type = sani($_POST['shift_type'] ?? ($ritase_ke >= 2 ? '2' : '1'));
        
        $tonase = (float) (sani($_POST['tonase'] ?? '0'));
        $km_awal = (float) (sani($_POST['km_awal'] ?? '0'));
        $km_akhir = (float) (sani($_POST['km_akhir'] ?? '0'));
        $total_km = (float) (sani($_POST['total_km'] ?? '0'));
        if ($total_km <= 0 && $km_akhir > $km_awal && $km_awal > 0) {
            $total_km = $km_akhir - $km_awal;
        }

        $jam_mulai = !empty($_POST['jam_mulai']) ? sani($_POST['jam_mulai']) : null;
        $jam_loading = !empty($_POST['jam_loading']) ? sani($_POST['jam_loading']) : null;
        $jam_timbang_awal = !empty($_POST['jam_timbang_awal']) ? sani($_POST['jam_timbang_awal']) : null;
        $durasi_loading_timbang = (float) (sani($_POST['durasi_loading_timbang'] ?? '0'));
        $jam_bongkar = !empty($_POST['jam_bongkar']) ? sani($_POST['jam_bongkar']) : null;
        $jam_timbang_akhir = !empty($_POST['jam_timbang_akhir']) ? sani($_POST['jam_timbang_akhir']) : null;
        $durasi_timbang_awal_akhir = (float) (sani($_POST['durasi_timbang_awal_akhir'] ?? '0'));
        $jam_tiba_site = !empty($_POST['jam_tiba_site']) ? sani($_POST['jam_tiba_site']) : null;
        $durasi_timbang_site = (float) (sani($_POST['durasi_timbang_site'] ?? '0'));

        $rest_time = !empty($_POST['rest_time']) ? sani($_POST['rest_time']) : null;
        $p5m_time = !empty($_POST['p5m_time']) ? sani($_POST['p5m_time']) : null;
        $cuci = isset($_POST['cuci']) ? 1 : 0;
        $safety = isset($_POST['safety']) ? 1 : 0;

        $hm_s1 = (float) (sani($_POST['hm_s1'] ?? '0'));
        $ot_hours = (float) (sani($_POST['ot_hours'] ?? '0'));
        $keterangan = !empty($_POST['keterangan']) ? sani($_POST['keterangan']) : null;

        $tonase_rate = ($shift_type === '2') ? (int) $tarif_tonase_s2 : (int) $tarif_tonase_s1;
        $earned_tonase_incentive = round($tonase * $tonase_rate, 2);
        $earned_hm_incentive = round($hm_s1 * $tarif_hm, 2);
        $overtime_amount = round($ot_hours * $tarif_lembur, 2);

        $query = "UPDATE hauling_timesheets SET
            employee_id = ?, substitute_employee_id = ?, tanggal = ?, unit_id = ?,
            ritase_ke = ?, shift_type = ?, tonase = ?, km_awal = ?, km_akhir = ?, total_km = ?,
            jam_mulai = ?, jam_loading = ?, jam_timbang_awal = ?, durasi_loading_timbang = ?,
            jam_bongkar = ?, jam_timbang_akhir = ?, durasi_timbang_awal_akhir = ?, jam_tiba_site = ?, durasi_timbang_site = ?,
            rest_time = ?, p5m_time = ?, cuci = ?, safety = ?, hm_s1 = ?, ot_hours = ?,
            tonase_rate = ?, earned_tonase_incentive = ?, earned_hm_incentive = ?, overtime_amount = ?, keterangan = ?
            WHERE id = ?";

        $params = [
            $employee_id, $substitute_employee_id, $tanggal, $unit_id,
            $ritase_ke, $shift_type, $tonase, $km_awal, $km_akhir, $total_km,
            $jam_mulai, $jam_loading, $jam_timbang_awal, $durasi_loading_timbang,
            $jam_bongkar, $jam_timbang_akhir, $durasi_timbang_awal_akhir, $jam_tiba_site, $durasi_timbang_site,
            $rest_time, $p5m_time, $cuci, $safety, $hm_s1, $ot_hours,
            $tonase_rate, $earned_tonase_incentive, $earned_hm_incentive, $overtime_amount, $keterangan,
            $id
        ];
        $types = 'ssssisddddssssssssssiiiiiddddss';

        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            $_SESSION['message'] = 'Data timesheet hauling berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat memperbarui data: ' . mysqli_error($con);
            $_SESSION['message_type'] = 'error';
        }

        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=hauling_timesheets';
        header("Location: $redirectUrl");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);
    $query = "DELETE FROM hauling_timesheets WHERE id = ?";
    $deleteResult = executeSecure($con, $query, [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data timesheet hauling berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus data timesheet hauling.';
        $_SESSION['message_type'] = 'error';
    }

    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=hauling_timesheets';
    header("Location: $redirectUrl");
    exit;
}
