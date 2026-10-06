<?php

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addData'])) {
        $id = generate_uuid();
        $full_name = sani($_POST['full_name']);
        $position = sani($_POST['position']);
        $join_date = sani($_POST['join_date']) ?: null;
        $employee_id = !empty($_POST['employee_id']) ? sani($_POST['employee_id']) : ('EMP-' . strtoupper(substr(uniqid(), -5)));
        $bank = !empty($_POST['bank']) ? sani($_POST['bank']) : null;
        $nomor_rekening = !empty($_POST['nomor_rekening']) ? sani($_POST['nomor_rekening']) : null;

        $query = "INSERT INTO employees (id, full_name, position, join_date, employee_id, bank, nomor_rekening) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$id, $full_name, $position, $join_date, $employee_id, $bank, $nomor_rekening];
        $types = 'sssssss';
        $insertResult = executeSecure($con, $query, $params, $types);

        if ($insertResult) {
            $_SESSION['message'] = 'Data karyawan berhasil ditambahkan!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat menambahkan data.';
            $_SESSION['message_type'] = 'error';
        }
        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
        header("Location: $redirectUrl");
        exit;
    }

    if (isset($_POST['updateData'])) {
        $id = sani($_POST['id']);
        $full_name = sani($_POST['full_name']);
        $position = sani($_POST['position']);
        $join_date = sani($_POST['join_date']) ?: null;
        $bank = !empty($_POST['bank']) ? sani($_POST['bank']) : null;
        $nomor_rekening = !empty($_POST['nomor_rekening']) ? sani($_POST['nomor_rekening']) : null;

        $query = "UPDATE employees SET full_name = ?, position = ?, join_date = ?, bank = ?, nomor_rekening = ? WHERE id = ?";
        $params = [$full_name, $position, $join_date, $bank, $nomor_rekening, $id];
        $types = 'ssssss';
        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            $_SESSION['message'] = 'Data karyawan berhasil diperbarui!';
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Terjadi kesalahan saat memperbarui data.';
            $_SESSION['message_type'] = 'error';
        }
        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
        header("Location: $redirectUrl");
        exit;
    }

    if (isset($_POST['bulkUpdateBank'])) {
        $employee_ids = isset($_POST['employee_ids']) && is_array($_POST['employee_ids']) ? $_POST['employee_ids'] : [];
        $bank = isset($_POST['bank']) ? trim(sani($_POST['bank'])) : '';
        $nomor_rekening = isset($_POST['nomor_rekening']) ? trim(sani($_POST['nomor_rekening'])) : '';

        if (empty($employee_ids)) {
            $_SESSION['message'] = 'Tidak ada karyawan yang dipilih!';
            $_SESSION['message_type'] = 'warning';
            $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
            header("Location: $redirectUrl");
            exit;
        }

        if (empty($bank)) {
            $_SESSION['message'] = 'Nama bank tidak boleh kosong!';
            $_SESSION['message_type'] = 'warning';
            $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
            header("Location: $redirectUrl");
            exit;
        }

        $cleanIds = [];
        foreach ($employee_ids as $eid) {
            $cleaned = sani(trim($eid));
            if (!empty($cleaned)) {
                $cleanIds[] = $cleaned;
            }
        }

        if (empty($cleanIds)) {
            $_SESSION['message'] = 'Daftar karyawan tidak valid!';
            $_SESSION['message_type'] = 'warning';
            $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
            header("Location: $redirectUrl");
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        
        if (!empty($nomor_rekening)) {
            // Update both bank and nomor_rekening
            $types = 'ss' . str_repeat('s', count($cleanIds));
            $params = array_merge([$bank, $nomor_rekening], $cleanIds);
            $query = "UPDATE employees SET bank = ?, nomor_rekening = ? WHERE id IN ($placeholders)";
        } else {
            // Update bank only
            $types = 's' . str_repeat('s', count($cleanIds));
            $params = array_merge([$bank], $cleanIds);
            $query = "UPDATE employees SET bank = ? WHERE id IN ($placeholders)";
        }

        $updateResult = executeSecure($con, $query, $params, $types);

        if ($updateResult) {
            $count = count($cleanIds);
            $rekMsg = !empty($nomor_rekening) ? " dan nomor rekening ($nomor_rekening)" : "";
            $_SESSION['message'] = "Berhasil memperbarui bank ($bank)$rekMsg untuk $count karyawan terpilih!";
            $_SESSION['message_type'] = 'success';
        } else {
            $_SESSION['message'] = 'Gagal melakukan pembaruan bank masal.';
            $_SESSION['message_type'] = 'error';
        }

        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
        header("Location: $redirectUrl");
        exit;
    }

    exit;
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);
    $deleteResult = executeSecure($con, "DELETE FROM employees WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        $_SESSION['message'] = 'Data berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan saat menghapus data.';
        $_SESSION['message_type'] = 'error';
    }
    $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '../?hal=employee_employees';
    header("Location: $redirectUrl");
    exit;
} else {
    header('Location: ../../index.php');
    exit;
}
