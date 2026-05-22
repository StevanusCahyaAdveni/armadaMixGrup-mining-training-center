<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $category_title = sani($_POST['category_title']);
        $query = "INSERT INTO test_categorys (id, category_title) VALUES (?, ?)";
        $params = [$id, $category_title];
        $types = 'ss';
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
                window.location.href = '../?hal=test_test-category';
            </script>
        ";
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $category_title = sani($_POST['category_title']);
        $query = "UPDATE test_categorys SET category_title = ? WHERE id = ?";
        $params = [$category_title, $id];
        $types = 'ss';
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
                window.location.href = '../?hal=test_test-category';
            </script>
        ";
    }
    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // Hapus data
    $deleteResult = executeSecure($con, "DELETE FROM test_categorys WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    echo "
            <script>
                window.location.href = '../?hal=test_test-category';
            </script>
        ";
    exit;
} else {
    // If accessed directly, redirect to homepage
    header('Location: ../../index.php');
    exit;
}
