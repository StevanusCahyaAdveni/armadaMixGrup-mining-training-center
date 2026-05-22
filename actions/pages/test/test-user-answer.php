<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $question_id = sani($_POST['question_id']);
        $choice_id = sani($_POST['choice_id']);
        $user_session_id = sani($_POST['user_session_id']);
        $query = "INSERT INTO test_user_answers (id, question_id, choice_id, user_session_id) VALUES (?, ?, ?, ?)";
        $params = [$id, $question_id, $choice_id, $user_session_id];
        $types = 'ssss';
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
                window.location.href = '../?hal=test_test-user-answer';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $question_id = sani($_POST['question_id']);
        $choice_id = sani($_POST['choice_id']);
        $user_session_id = sani($_POST['user_session_id']);
        $query = "UPDATE test_user_answers SET question_id = ?, choice_id = ?, user_session_id = ? WHERE id = ?";
        $params = [$question_id, $choice_id, $user_session_id, $id];
        $types = 'ssss';
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
                window.location.href = '../?hal=test_test-user-answer';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM test_user_answers WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=test_test-user-answer';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
