<?php 
if (isset($_SESSION['admin']) || isset($_SESSION['user_id'])) {
    $_SESSION['is_logged_in'] = true;
}

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    // Try auto-login if credentials in POST
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_login'])) {
        $email = sani($_POST['email']);
        $password = sani($_POST['password']);

        if (!empty($email) && !empty($password)) {
            $result = querySecure(
                $con,
                "SELECT * FROM users WHERE email = ?",
                [$email],
                's'
            );

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_fullname'] = $user['fullname'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_photo'] = $user['photo_profile'];
                    $_SESSION['is_logged_in'] = true;
                    $_SESSION['admin'] = $user;
                    // Continue to load page
                } else {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => 'Password tidak valid']);
                    exit;
                }
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
                exit;
            }
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Input auto login tidak lengkap']);
            exit;
        }
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Sesi login telah berakhir. Silakan login kembali.']);
        exit;
    }
}
?>