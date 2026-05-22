<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $question_id = sani($_POST['question_id']);
        $media_name = sani($_POST['media_name']);
        $path = sani($_POST['path']);
        $media_extendsion = sani($_POST['media_extendsion']);
        $query = "INSERT INTO test_question_medias (id, question_id, media_name, path, media_extendsion) VALUES (?, ?, ?, ?, ?)";
        $params = [$id, $question_id, $media_name, $path, $media_extendsion];
        $types = 'sssss';
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
                window.location.href = '../?hal=test_test-question-media';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $question_id = sani($_POST['question_id']);
        $media_name = sani($_POST['media_name']);
        $path = sani($_POST['path']);
        $media_extendsion = sani($_POST['media_extendsion']);
        $query = "UPDATE test_question_medias SET question_id = ?, media_name = ?, path = ?, media_extendsion = ? WHERE id = ?";
        $params = [$question_id, $media_name, $path, $media_extendsion, $id];
        $types = 'sssss';
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
                window.location.href = '../?hal=test_test-question-media';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM test_question_medias WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=test_test-question-media';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
