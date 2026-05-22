<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $title = sani($_POST['title']);
        $category_id = sani($_POST['category_id']);
        $type = sani($_POST['type']);
        $answer_time = sani($_POST['answer_time']);
        $point_show = sani($_POST['point_show']);
        $query = "INSERT INTO tests (id, title, category_id, type, answer_time, point_show) VALUES (?, ?, ?, ?, ?, ?)";
        $params = [$id, $title, $category_id, $type, $answer_time, $point_show];
        $types = 'ssssss';
        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful test addition ' . $title);
            redirectWithMessage('../?hal=test_test', 'Data berhasil ditambahkan!', 'success');
        } else {
            redirectWithMessage('../?hal=test_test', 'Terjadi kesalahan saat menambahkan data.', 'error');
        }
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $title = sani($_POST['title']);
        $category_id = sani($_POST['category_id']);
        $type = sani($_POST['type']);
        $answer_time = sani($_POST['answer_time']);
        $point_show = sani($_POST['point_show']);
        $query = "UPDATE tests SET title = ?, category_id = ?, type = ?, answer_time = ?, point_show = ? WHERE id = ?";
        $params = [$title, $category_id, $type, $answer_time, $point_show, $id];
        $types = 'ssssss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            createLog($con, $_SESSION['admin']['email'], 'Successful test update ' . $title);
            redirectWithMessage('../?hal=test_test', 'Data berhasil diperbarui!', 'success');
        } else {
            redirectWithMessage('../?hal=test_test', 'Terjadi kesalahan saat memperbarui data.', 'error');
        }
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Fetch test details for logging
    $testRes = querySecure($con, "SELECT title FROM tests WHERE id = ?", [$id], 's');
    $testData = mysqli_fetch_assoc($testRes);
    $title = $testData['title'] ?? $id;

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM tests WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        createLog($con, $_SESSION['admin']['email'], 'Successful test deletion ' . $title);
        redirectWithMessage('../?hal=test_test', 'Data berhasil dihapus!', 'success');
    } else {
        redirectWithMessage('../?hal=test_test', 'Terjadi kesalahan saat menghapus data.', 'error');
    }
    exit;
} else {
    // If accessed directly, redirect to homepage
    redirectWithMessage('../../index.php', 'Akses tidak valid.', 'error');
}

