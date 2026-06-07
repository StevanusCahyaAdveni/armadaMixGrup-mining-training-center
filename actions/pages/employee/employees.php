<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $full_name = sani($_POST['full_name']);
        $company_name = sani($_POST['company_name']);
        $position = sani($_POST['position']);
        $employee_id = sani($_POST['employee_id']);
        $bpjs_tenaker_no = sani($_POST['bpjs_tenaker_no']);
        $bpjs_kes_no = sani($_POST['bpjs_kes_no']);
        $merdeka_sehat = sani($_POST['merdeka_sehat']);
        $mine_permit = sani($_POST['mine_permit']);
        $induction_schedule = sani($_POST['induction_schedule']);
        $mine_permit_scm = sani($_POST['mine_permit_scm']);
        $simper_teory_test = sani($_POST['simper_teory_test']);
        $simper_practice_test = sani($_POST['simper_practice_test']);
        $simper_ojt = sani($_POST['simper_ojt']);
        $simper_status = sani($_POST['simper_status']);
        $gaji_pokok = sani($_POST['gaji_pokok']);
        $tunjangan_tetap = sani($_POST['tunjangan_tetap']);
        $query = "INSERT INTO employees (id, full_name, company_name, position, employee_id, bpjs_tenaker_no, bpjs_kes_no, merdeka_sehat, mine_permit, induction_schedule, mine_permit_scm, simper_teory_test, simper_practice_test, simper_ojt, simper_status, gaji_pokok, tunjangan_tetap) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $full_name, $company_name, $position, $employee_id, $bpjs_tenaker_no, $bpjs_kes_no, $merdeka_sehat, $mine_permit, $induction_schedule, $mine_permit_scm, $simper_teory_test, $simper_practice_test, $simper_ojt, $simper_status, $gaji_pokok, $tunjangan_tetap];
        $types = 'sssssssssssssssss';
        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            $_SESSION['message'] = 'Data berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat menambahkan data.';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = '../?hal=employee_employees';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $full_name = sani($_POST['full_name']);
        $company_name = sani($_POST['company_name']);
        $position = sani($_POST['position']);
        $employee_id = sani($_POST['employee_id']);
        $bpjs_tenaker_no = sani($_POST['bpjs_tenaker_no']);
        $bpjs_kes_no = sani($_POST['bpjs_kes_no']);
        $merdeka_sehat = sani($_POST['merdeka_sehat']);
        $mine_permit = sani($_POST['mine_permit']);
        $induction_schedule = sani($_POST['induction_schedule']);
        $mine_permit_scm = sani($_POST['mine_permit_scm']);
        $simper_teory_test = sani($_POST['simper_teory_test']);
        $simper_practice_test = sani($_POST['simper_practice_test']);
        $simper_ojt = sani($_POST['simper_ojt']);
        $simper_status = sani($_POST['simper_status']);
        $gaji_pokok = sani($_POST['gaji_pokok']);
        $tunjangan_tetap = sani($_POST['tunjangan_tetap']);
        $query = "UPDATE employees SET full_name = ?, company_name = ?, position = ?, employee_id = ?, bpjs_tenaker_no = ?, bpjs_kes_no = ?, merdeka_sehat = ?, mine_permit = ?, induction_schedule = ?, mine_permit_scm = ?, simper_teory_test = ?, simper_practice_test = ?, simper_ojt = ?, simper_status = ?, gaji_pokok = ?, tunjangan_tetap = ? WHERE id = ?";
        $params = [$full_name, $company_name, $position, $employee_id, $bpjs_tenaker_no, $bpjs_kes_no, $merdeka_sehat, $mine_permit, $induction_schedule, $mine_permit_scm, $simper_teory_test, $simper_practice_test, $simper_ojt, $simper_status, $gaji_pokok, $tunjangan_tetap, $id];
        $types = 'sssssssssssssssss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            $_SESSION['message'] = 'Data berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat memperbarui data.';
            $_SESSION['message_type'] = 'error';
        }
        echo "
            <script>
                window.location.href = '../?hal=employee_employees';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM employees WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=employee_employees';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
