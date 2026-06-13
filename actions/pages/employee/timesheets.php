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
    $rest_start = !empty($_POST['rest_start']) ? sani($_POST['rest_start']) : null;
    $rest_end = !empty($_POST['rest_end']) ? sani($_POST['rest_end']) : null;
    $ritase = (int) $_POST['ritase'];
    $solar = (float) $_POST['solar'];
    $keterangan = !empty($_POST['keterangan']) ? sani($_POST['keterangan']) : null;

    // Calculations
    $total_hm = $hm_akhir - $hm_awal;
    
    $ist_hm = 0;
    if ($rest_start && $rest_end) {
        $mins = getMinutesDiff($rest_start, $rest_end);
        $ist_hm = $mins / 60;
    }
    
    $hmc = $total_hm + $ist_hm;

    // Get HM Rate from settings
    $rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
    $rateRow = mysqli_fetch_assoc($rateQuery);
    $applied_hm_rate = isset($rateRow['setting_value']) ? (int) $rateRow['setting_value'] : 0;
    
    $earned_hm_incentive = $hmc * $applied_hm_rate;

    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $query = "INSERT INTO employee_timesheets (id, employee_id, tanggal, shift, unit_id, hm_awal, hm_akhir, rest_start, rest_end, ritase, solar, total_hm, ist_hm, hmc, applied_hm_rate, earned_hm_incentive, keterangan) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $employee_id, $tanggal, $shift, $unit_id, $hm_awal, $hm_akhir, $rest_start, $rest_end, $ritase, $solar, $total_hm, $ist_hm, $hmc, $applied_hm_rate, $earned_hm_incentive, $keterangan];
        $types = "sssssddssiddddiis";
        
        if (executeSecure($con, $query, $params, $types)) {
            $_SESSION['message'] = 'Data timesheet berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal menambahkan data!';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_timesheets';
            </script>
        ";
    } 
    elseif (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $query = "UPDATE employee_timesheets SET 
                    employee_id = ?, tanggal = ?, shift = ?, unit_id = ?, 
                    hm_awal = ?, hm_akhir = ?, rest_start = ?, rest_end = ?, 
                    ritase = ?, solar = ?, total_hm = ?, ist_hm = ?, hmc = ?, 
                    applied_hm_rate = ?, earned_hm_incentive = ?, keterangan = ? 
                  WHERE id = ?";
        $params = [$employee_id, $tanggal, $shift, $unit_id, $hm_awal, $hm_akhir, $rest_start, $rest_end, $ritase, $solar, $total_hm, $ist_hm, $hmc, $applied_hm_rate, $earned_hm_incentive, $keterangan, $id];
        $types = "ssssddssiddddiiss";
        
        if (executeSecure($con, $query, $params, $types)) {
            $_SESSION['message'] = 'Data timesheet berhasil diupdate!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal mengupdate data!';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_timesheets';
            </script>
        ";
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
    echo "
        <script>
            window.location.href = document.referrer ? document.referrer : '../?hal=employee_timesheets';
        </script>
    ";
}
?>
