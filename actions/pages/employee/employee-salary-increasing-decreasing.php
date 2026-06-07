<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $user_id = sani($_POST['user_id']);
        $date = sani($_POST['date']);
        $category = sani($_POST['category']);
        $desc = sani($_POST['desc']);
        $value = sani($_POST['value']);
        $query = "INSERT INTO employee_salary_increasing_decreasing (id, user_id, date, category, `desc`, value) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$id, $user_id, $date, $category, $desc, $value];
        $types = 'ssssss';
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
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-salary-increasing-decreasing';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $user_id = sani($_POST['user_id']);
        $date = sani($_POST['date']);
        $category = sani($_POST['category']);
        $desc = sani($_POST['desc']);
        $value = sani($_POST['value']);
        $query = "UPDATE employee_salary_increasing_decreasing SET user_id = ?, date = ?, category = ?, `desc` = ?, value = ? WHERE id = ?";
        $params = [$user_id, $date, $category, $desc, $value, $id];
        $types = 'ssssss';
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
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-salary-increasing-decreasing';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM employee_salary_increasing_decreasing WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = document.referrer ? document.referrer : '../?hal=employee_employee-salary-increasing-decreasing';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
