<?php
session_start();
include '../config.php';
include '../functions/sanitasi.php';
include '../functions/secure_query.php';
include '../functions/generate_uuid.php';

$testId = isset($_GET['test_id']) ? sani($_GET['test_id']) : '';

// Validate test
$test = null;
if (!empty($testId)) {
    $testRes = querySecure($con, "SELECT * FROM tests WHERE id = ?", [$testId], 's');
    if ($testRes && mysqli_num_rows($testRes) > 0) {
        $test = mysqli_fetch_assoc($testRes);
    }
}

$errorMessage = '';
if (!$test) {
    $errorMessage = 'Link ujian tidak valid atau tes tidak ditemukan. Harap hubungi administrator.';
}

// Form submission handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $test) {
    $fullname = sani($_POST['fullname'] ?? '');
    $email = sani($_POST['email'] ?? '');
    $whatsapp = sani($_POST['whatsapp_number'] ?? '');
    $role = sani($_POST['role'] ?? '');

    if (empty($fullname) || empty($email) || empty($whatsapp) || empty($role)) {
        $formError = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $formError = 'Format email tidak valid.';
    } else {
        // 1. Check if user already exists
        $userQuery = querySecure($con, "SELECT id FROM users WHERE email = ?", [$email], 's');
        if ($userQuery && mysqli_num_rows($userQuery) > 0) {
            $userRow = mysqli_fetch_assoc($userQuery);
            $userId = $userRow['id'];
            // Update profile information
            executeSecure($con, 
                "UPDATE users SET fullname = ?, whatsapp_number = ?, role = ? WHERE id = ?", 
                [$fullname, $whatsapp, $role, $userId], 
                'ssss'
            );
        } else {
            // Create new user
            $userId = generate_uuid();
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0])) . '_' . mt_rand(100, 999);
            // Ensure unique username
            $usernameQuery = querySecure($con, "SELECT id FROM users WHERE username = ?", [$username], 's');
            if ($usernameQuery && mysqli_num_rows($usernameQuery) > 0) {
                $username .= mt_rand(10, 99);
            }
            $randomPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

            executeSecure($con, 
                "INSERT INTO users (id, fullname, username, email, whatsapp_number, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$userId, $fullname, $username, $email, $whatsapp, $hashedPassword, $role],
                'sssssss'
            );
        }

        // 2. Check for an active session to resume
        $activeSessionQuery = querySecure($con, 
            "SELECT id FROM test_user_sessions WHERE test_id = ? AND user_id = ? AND status = 'active' AND datetime_end > NOW() LIMIT 1",
            [$testId, $userId],
            'ss'
        );

        if ($activeSessionQuery && mysqli_num_rows($activeSessionQuery) > 0) {
            $sessionRow = mysqli_fetch_assoc($activeSessionQuery);
            $_SESSION['exam_session_id'] = $sessionRow['id'];
            header("Location: exam-cbt.php");
            exit;
        } else {
            // 3. Create a new session
            // Fetch all questions for this test
            $questionsRes = querySecure($con, "SELECT id FROM test_questions WHERE test_id = ?", [$testId], 's');
            $qIds = [];
            if ($questionsRes) {
                while ($qRow = mysqli_fetch_assoc($questionsRes)) {
                    $qIds[] = $qRow['id'];
                }
            }

            if (empty($qIds)) {
                $formError = 'Tes ini belum memiliki soal. Silakan hubungi administrator.';
            } else {
                // Shuffle questions order
                shuffle($qIds);
                $questionOrder = json_encode($qIds);

                $timeLimit = intval($test['answer_time']);
                $datetimeStart = date('Y-m-d H:i:s');
                $datetimeEnd = date('Y-m-d H:i:s', strtotime("+$timeLimit minutes"));
                
                $sessionId = generate_uuid();
                $status = 'active';

                $sessionInsert = executeSecure($con,
                    "INSERT INTO test_user_sessions (id, test_id, user_id, datetime_start, datetime_end, question_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$sessionId, $testId, $userId, $datetimeStart, $datetimeEnd, $questionOrder, $status],
                    'sssssss'
                );

                if ($sessionInsert) {
                    $_SESSION['exam_session_id'] = $sessionId;
                    header("Location: exam-cbt.php");
                    exit;
                } else {
                    $formError = 'Terjadi kesalahan sistem saat membuat sesi ujian.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mulai Ujian - <?= $test ? htmlspecialchars($test['title']) : 'MTC CBT' ?></title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container" style="max-width: 600px;">
    <div class="glass-panel p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="badge bg-primary px-3 py-2 rounded-pill mb-2" style="font-size: 11px; letter-spacing: 0.1em; text-transform: uppercase;">
                Mining Training Center (MTC)
            </div>
            <h2 class="text-dark">Portal Pengerjaan Soal</h2>
            <p class="text-muted" style="font-size: 14px;">Silakan isi formulir pendaftaran diri di bawah ini untuk memulai ujian.</p>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm p-4 text-center">
                <i class="bi bi-exclamation-triangle-fill text-danger fs-1 mb-3 d-block"></i>
                <h5 class="fw-bold">Ujian Tidak Tersedia</h5>
                <p class="mb-0 text-muted" style="font-size: 13px;"><?= $errorMessage ?></p>
            </div>
        <?php else: ?>

            <?php if (isset($formError)): ?>
                <div class="alert alert-danger border-0 rounded-3 shadow-sm py-2 px-3 mb-3 d-flex align-items-center" style="font-size: 13px;">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <div><?= $formError ?></div>
                </div>
            <?php endif; ?>

            <div class="card border-0 bg-white bg-opacity-50 rounded-4 p-3 mb-4 border border-white">
                <div class="row g-2 align-items-center" style="font-size: 13px;">
                    <div class="col-6">
                        <div class="text-muted">Nama Ujian:</div>
                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($test['title']) ?>"><?= htmlspecialchars($test['title']) ?></div>
                    </div>
                    <div class="col-3 border-start">
                        <div class="text-muted">Durasi:</div>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($test['answer_time']) ?> Menit</div>
                    </div>
                    <div class="col-3 border-start">
                        <div class="text-muted">Tipe:</div>
                        <div class="fw-bold text-dark text-capitalize"><?= htmlspecialchars($test['type']) ?></div>
                    </div>
                </div>
            </div>

            <form action="" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label text-dark fw-bold" style="font-size: 12px;"><i class="bi bi-person-fill me-1"></i> Nama Lengkap</label>
                    <input type="text" class="form-control" name="fullname" placeholder="Masukkan Nama Lengkap Anda" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark fw-bold" style="font-size: 12px;"><i class="bi bi-whatsapp me-1"></i> Nomor WhatsApp</label>
                    <input type="tel" class="form-control" name="whatsapp_number" placeholder="Contoh: 08123456789" required value="<?= htmlspecialchars($_POST['whatsapp_number'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark fw-bold" style="font-size: 12px;"><i class="bi bi-envelope-fill me-1"></i> Alamat Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Masukkan Alamat Email Aktif" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label text-dark fw-bold" style="font-size: 12px;"><i class="bi bi-briefcase-fill me-1"></i> Peran (Role)</label>
                    <select class="form-select" name="role" required>
                        <option value="">-- Pilih Peran --</option>
                        <option value="rekrutmen" <?= (isset($_POST['role']) && $_POST['role'] === 'rekrutmen') ? 'selected' : '' ?>>Rekrutmen / Calon Karyawan</option>
                        <option value="karyawan" <?= (isset($_POST['role']) && $_POST['role'] === 'karyawan') ? 'selected' : '' ?>>Karyawan</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 py-3 shadow">
                    <i class="bi bi-play-fill me-1"></i> Mulai Pengerjaan Ujian
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>
<script>
if (window.location.href.includes('mtc.armadamix.id')) {
    // Disable right-click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    }, false);
    // Disable Ctrl+Shift+I and F12
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey && e.shiftKey && e.key === 'I') || e.key === 'F12') {
            e.preventDefault();
        }
    }, false);
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
