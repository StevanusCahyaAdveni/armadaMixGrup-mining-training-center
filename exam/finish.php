<?php
session_start();
include '../config.php';
include '../functions/sanitasi.php';
include '../functions/secure_query.php';

$sessionId = $_SESSION['completed_session_id'] ?? (isset($_GET['session_id']) ? sani($_GET['session_id']) : '');

if (empty($sessionId)) {
    header("Location: index.php");
    exit;
}

// Fetch session details
$sessionRes = querySecure($con, "SELECT * FROM test_user_sessions WHERE id = ?", [$sessionId], 's');
if (!$sessionRes || mysqli_num_rows($sessionRes) === 0) {
    header("Location: index.php");
    exit;
}
$session = mysqli_fetch_assoc($sessionRes);

// Fetch test details
$testRes = querySecure($con, "SELECT * FROM tests WHERE id = ?", [$session['test_id']], 's');
$test = mysqli_fetch_assoc($testRes);

// Fetch user details
$userRes = querySecure($con, "SELECT * FROM users WHERE id = ?", [$session['user_id']], 's');
$user = mysqli_fetch_assoc($userRes);

// Calculate score if point_show is true
$totalScore = 0;
$maxScore = 0;
$showPoints = $test['point_show'] === 'true';

if ($showPoints) {
    // Calculate candidate score (sum of points from selected correct choices)
    $scoreQuery = querySecure($con, 
        "SELECT SUM(CAST(c.point AS UNSIGNED)) AS total_score 
         FROM test_user_answers a 
         JOIN test_question_choices c ON a.choice_id = c.id 
         WHERE a.user_session_id = ? AND c.choice_true = 'true'", 
        [$sessionId], 
        's'
    );
    if ($scoreQuery && $scoreRow = mysqli_fetch_assoc($scoreQuery)) {
        $totalScore = intval($scoreRow['total_score'] ?? 0);
    }

    // Calculate maximum possible score of the test (sum of max points for correct choices)
    $maxScoreQuery = querySecure($con,
        "SELECT SUM(CAST(c.point AS UNSIGNED)) AS max_score
         FROM test_question_choices c
         JOIN test_questions q ON c.question_id = q.id
         WHERE q.test_id = ? AND c.choice_true = 'true'",
        [$session['test_id']],
        's'
    );
    if ($maxScoreQuery && $maxScoreRow = mysqli_fetch_assoc($maxScoreQuery)) {
        $maxScore = intval($maxScoreRow['max_score'] ?? 0);
    }
}

// Check if there are form/essay questions (which cannot be auto-scored)
$essayCount = 0;
$essayQuery = querySecure($con, "SELECT COUNT(*) as essay_count FROM test_questions WHERE test_id = ? AND question_type = 'Form'", [$session['test_id']], 's');
if ($essayQuery && $essayRow = mysqli_fetch_assoc($essayQuery)) {
    $essayCount = intval($essayRow['essay_count'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ujian Selesai - <?= htmlspecialchars($test['title']) ?></title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .score-circle {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: radial-gradient(circle, #eff6ff 0%, #dbeafe 100%);
            border: 4px solid #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 0 auto;
        }
        @media print {
            body {
                background: none !important;
            }
            .glass-panel {
                box-shadow: none !important;
                border: none !important;
                background: none !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">

<div class="container" style="max-width: 600px;">
    <div class="glass-panel p-4 p-md-5 text-center shadow border border-white">
        
        <div class="mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-check-circle-fill fs-1"></i>
            </div>
            <h2 class="text-dark">Ujian Selesai!</h2>
            <p class="text-muted" style="font-size: 14px;">Terima kasih, jawaban Anda telah berhasil disimpan dan dikumpulkan.</p>
        </div>

        <!-- Candidate & Test Information Card -->
        <div class="card border-0 bg-white bg-opacity-75 rounded-4 p-4 text-start mb-4 border border-white">
            <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Detail Pengerjaan</h5>
            
            <div class="row g-3" style="font-size: 13px;">
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">Nama Lengkap</span>
                    <strong class="text-dark"><?= htmlspecialchars($user['fullname']) ?></strong>
                </div>
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">Ujian</span>
                    <strong class="text-dark"><?= htmlspecialchars($test['title']) ?></strong>
                </div>
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">Email</span>
                    <strong class="text-dark"><?= htmlspecialchars($user['email']) ?></strong>
                </div>
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">No. WhatsApp</span>
                    <strong class="text-dark"><?= htmlspecialchars($user['whatsapp_number']) ?></strong>
                </div>
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">Peran / Role</span>
                    <strong class="text-dark text-capitalize"><?= htmlspecialchars($user['role']) ?></strong>
                </div>
                <div class="col-sm-6 col-12">
                    <span class="text-muted d-block">Waktu Selesai</span>
                    <strong class="text-dark"><?= date('d M Y - H:i', strtotime($session['updated_at'])) ?> WIB</strong>
                </div>
            </div>
        </div>

        <!-- Scores Display -->
        <?php if ($showPoints): ?>
            <div class="mb-4 py-2">
                <div class="score-circle mb-3">
                    <div class="text-muted" style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Skor Tes</div>
                    <div class="text-primary fw-bold fs-2"><?= $totalScore ?></div>
                    <div class="text-muted" style="font-size: 11px;">dari max <?= $maxScore ?></div>
                </div>
                
                <?php if ($essayCount > 0): ?>
                    <p class="text-warning fw-bold mb-0" style="font-size: 11px;">
                        <i class="bi bi-info-circle-fill me-1"></i> 
                        Tes ini memiliki <?= $essayCount ?> soal esai yang perlu dinilai manual oleh admin.
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info border-0 rounded-4 p-3 mb-4 text-start" style="font-size: 13px;">
                <div class="d-flex">
                    <i class="bi bi-info-circle-fill fs-4 text-info me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Hasil Disimpan</h6>
                        <p class="mb-0 text-muted" style="line-height: 1.4;">Jawaban Anda telah berhasil tersimpan dalam sistem kami. Evaluasi hasil tes akan dilakukan secara internal oleh tim penguji.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="pt-2 no-print">
            <button type="button" class="btn btn-secondary px-4 py-2 rounded-3 me-2 mb-2" onclick="window.print()" style="font-size: 14px;">
                <i class="bi bi-printer me-1"></i> Cetak Hasil
            </button>
            <a href="index.php?test_id=<?= htmlspecialchars($session['test_id']) ?>" class="btn btn-outline-primary px-4 py-2 rounded-3 me-2 mb-2" style="font-size: 14px;">
                <i class="bi bi-arrow-clockwise me-1"></i> Mulai Ulang / Baru
            </a>
            <button type="button" class="btn btn-primary-custom px-4 py-2 shadow mb-2" onclick="window.close()" style="font-size: 14px;">
                <i class="bi bi-x-circle me-1"></i> Keluar
            </button>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
