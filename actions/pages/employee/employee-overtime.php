<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $user_id = sani($_POST['user_id']);
        $description = sani($_POST['description']);
        $overtime_start = sani($_POST['overtime_start']);
        $overtime_end = sani($_POST['overtime_end']);
        $shift = sani($_POST['shift']);
        $amount = sani($_POST['amount']);
        $query = "INSERT INTO employee_overtimes (id, user_id, description, overtime_start, overtime_end, shift, amount) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $user_id, $description, $overtime_start, $overtime_end, $shift, $amount];
        $types = 'sssssss';
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
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-overtime';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $user_id = sani($_POST['user_id']);
        $description = sani($_POST['description']);
        $overtime_start = sani($_POST['overtime_start']);
        $overtime_end = sani($_POST['overtime_end']);
        $shift = sani($_POST['shift']);
        $amount = sani($_POST['amount']);
        $query = "UPDATE employee_overtimes SET user_id = ?, description = ?, overtime_start = ?, overtime_end = ?, shift = ?, amount = ? WHERE id = ?";
        $params = [$user_id, $description, $overtime_start, $overtime_end, $shift, $amount, $id];
        $types = 'sssssss';
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
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-overtime';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM employee_overtimes WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-overtime';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
