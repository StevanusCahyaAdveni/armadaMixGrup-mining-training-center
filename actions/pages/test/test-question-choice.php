<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $question_id = sani($_POST['question_id']);
        $choice_text = sani($_POST['choice_text']);
        $choice_true = sani($_POST['choice_true']);
        $point = sani($_POST['point']);
        $query = "INSERT INTO test_question_choices (id, question_id, choice_text, choice_true, point) VALUES (?, ?, ?, ?, ?)";
        $params = [$id, $question_id, $choice_text, $choice_true, $point];
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
                window.location.href = '../?hal=test_test-question-choice';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $question_id = sani($_POST['question_id']);
        $choice_text = sani($_POST['choice_text']);
        $choice_true = sani($_POST['choice_true']);
        $point = sani($_POST['point']);
        $query = "UPDATE test_question_choices SET question_id = ?, choice_text = ?, choice_true = ?, point = ? WHERE id = ?";
        $params = [$question_id, $choice_text, $choice_true, $point, $id];
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
                window.location.href = '../?hal=test_test-question-choice';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM test_question_choices WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=test_test-question-choice';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
