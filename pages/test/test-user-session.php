<?php
/**
 * View: test/test-user-session
 * Path: pages/test/test-user-session.php
 */

include 'functions/pagination.php';

$sessionId = isset($_GET['session_id']) ? sani($_GET['session_id']) : '';
$isPrint = isset($_GET['print']) && $_GET['print'] == 1;

if (!empty($sessionId)):
    // ==========================================
    // DETAIL OR PRINT VIEW
    // ==========================================
    
    // Fetch session details
    $sessionRes = querySecure($con, 
        "SELECT s.id AS session_id, s.test_id, s.user_id, s.datetime_start, s.datetime_end, s.status, s.question_order, s.updated_at,
                t.title AS test_title, t.point_show, t.answer_time, t.type AS test_type,
                u.fullname AS user_fullname, u.email AS user_email, u.role AS user_role, u.whatsapp_number AS user_whatsapp
         FROM test_user_sessions s
         LEFT JOIN tests t ON s.test_id = t.id
         LEFT JOIN users u ON s.user_id = u.id
         WHERE s.id = ?",
        [$sessionId],
        's'
    );

    if (!$sessionRes || mysqli_num_rows($sessionRes) === 0) {
        echo "<div class='alert alert-danger rounded-4 shadow-sm p-4'><i class='bi bi-exclamation-triangle-fill me-2'></i> Sesi ujian tidak ditemukan.</div>";
        return;
    }
    $session = mysqli_fetch_assoc($sessionRes);

    // Calculate total score from selected choices
    $scoreQuery = querySecure($con, 
        "SELECT SUM(CAST(c.point AS UNSIGNED)) AS total_score 
         FROM test_user_answers a 
         JOIN test_question_choices c ON a.choice_id = c.id 
         WHERE a.user_session_id = ? AND c.choice_true = 'true'", 
        [$sessionId], 
        's'
    );
    $totalScore = 0;
    if ($scoreQuery && $scoreRow = mysqli_fetch_assoc($scoreQuery)) {
        $totalScore = intval($scoreRow['total_score'] ?? 0);
    }

    // Calculate maximum possible score
    $maxScoreQuery = querySecure($con,
        "SELECT SUM(CAST(c.point AS UNSIGNED)) AS max_score
         FROM test_question_choices c
         JOIN test_questions q ON c.question_id = q.id
         WHERE q.test_id = ? AND c.choice_true = 'true'",
        [$session['test_id']],
        's'
    );
    $maxScore = 0;
    if ($maxScoreQuery && $maxScoreRow = mysqli_fetch_assoc($maxScoreQuery)) {
        $maxScore = intval($maxScoreRow['max_score'] ?? 0);
    }

    // Count essay/form questions
    $essayCount = 0;
    $essayQuery = querySecure($con, 
        "SELECT COUNT(*) as essay_count 
         FROM test_questions 
         WHERE test_id = ? AND question_type = 'Form'", 
        [$session['test_id']], 
        's'
    );
    if ($essayQuery && $essayRow = mysqli_fetch_assoc($essayQuery)) {
        $essayCount = intval($essayRow['essay_count'] ?? 0);
    }

    // Fetch all questions for this test
    $allQuestionsRes = querySecure($con, "SELECT * FROM test_questions WHERE test_id = ?", [$session['test_id']], 's');
    $questionsById = [];
    while ($q = mysqli_fetch_assoc($allQuestionsRes)) {
        $questionsById[$q['id']] = $q;
    }

    // Order questions based on question_order JSON or database default
    $questionIds = json_decode($session['question_order'], true);
    $orderedQuestions = [];
    if (!empty($questionIds)) {
        foreach ($questionIds as $qId) {
            if (isset($questionsById[$qId])) {
                $orderedQuestions[] = $questionsById[$qId];
            }
        }
    } else {
        $orderedQuestions = array_values($questionsById);
    }

    // Fetch all choices for this test
    $choicesByQuestion = [];
    $allChoicesRes = querySecure($con, 
        "SELECT c.* FROM test_question_choices c JOIN test_questions q ON c.question_id = q.id WHERE q.test_id = ? ORDER BY c.created_at ASC", 
        [$session['test_id']], 
        's'
    );
    if ($allChoicesRes) {
        while ($choice = mysqli_fetch_assoc($allChoicesRes)) {
            $choicesByQuestion[$choice['question_id']][] = $choice;
        }
    }

    // Fetch all media for this test
    $mediasByQuestion = [];
    $allMediasRes = querySecure($con,
        "SELECT m.* FROM test_question_medias m JOIN test_questions q ON m.question_id = q.id WHERE q.test_id = ? ORDER BY m.created_at ASC",
        [$session['test_id']],
        's'
    );
    if ($allMediasRes) {
        while ($media = mysqli_fetch_assoc($allMediasRes)) {
            $mediasByQuestion[$media['question_id']][] = $media;
        }
    }

    // Fetch candidate's saved answers
    $savedAnswers = [];
    $allAnswersRes = querySecure($con, "SELECT question_id, choice_id, answer_text FROM test_user_answers WHERE user_session_id = ?", [$sessionId], 's');
    if ($allAnswersRes) {
        while ($ans = mysqli_fetch_assoc($allAnswersRes)) {
            $savedAnswers[$ans['question_id']] = [
                'choice_id' => $ans['choice_id'],
                'answer_text' => $ans['answer_text']
            ];
        }
    }
    ?>

    <!-- Inline Print CSS -->
    <style>
        .score-circle-admin {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: radial-gradient(circle, #f8fafc 0%, #f1f5f9 100%);
            border: 3px solid #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            margin: 0 auto;
        }
        .question-card-report {
            background-color: #ffffff;
            border-left: 4px solid #cbd5e1;
            transition: all 0.2s ease;
        }
        .question-card-report.correct {
            border-left-color: #10b981;
        }
        .question-card-report.incorrect {
            border-left-color: #ef4444;
        }
        .choice-option-report {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            margin-bottom: 8px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .choice-option-report.chosen-correct {
            border-color: #10b981;
            background-color: #ecfdf5;
            color: #065f46;
            font-weight: 600;
        }
        .choice-option-report.chosen-incorrect {
            border-color: #ef4444;
            background-color: #fef2f2;
            color: #991b1b;
        }
        .choice-option-report.actual-correct {
            border-color: #10b981;
            border-style: dashed;
            background-color: #f0fdf4;
            color: #166534;
        }
        
        @media print {
            body, html, #app, #main {
                background-color: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            #sidebar, header, footer, .page-heading, .burger-btn, .no-print {
                display: none !important;
            }
            #main {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .question-card-report {
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="row">
        <!-- Back Button / Print Control -->
        <div class="col-12 mb-3 no-print d-flex justify-content-between align-items-center">
            <a href="?hal=test_test-user-session" class="btn btn-outline-secondary btn-sm shadow-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <button type="button" class="btn btn-primary btn-sm shadow-sm rounded-pill px-3" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Laporan (PDF)
            </button>
        </div>

        <!-- Candidate Info Banner -->
        <div class="col-md-8 col-12">
            <div class="card shadow-sm border-0 rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">
                    <i class="bi bi-person-badge-fill text-primary me-2"></i>Detail Kandidat Ujian
                </h5>
                <div class="row g-3" style="font-size: 13px;">
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Nama Lengkap</span>
                        <strong class="text-dark fs-6"><?= htmlspecialchars($session['user_fullname']) ?></strong>
                    </div>
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Nama Ujian</span>
                        <strong class="text-dark fs-6"><?= htmlspecialchars($session['test_title']) ?></strong>
                    </div>
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Alamat Email</span>
                        <strong class="text-dark"><?= htmlspecialchars($session['user_email']) ?></strong>
                    </div>
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Nomor WhatsApp</span>
                        <strong class="text-dark"><?= htmlspecialchars($session['user_whatsapp']) ?></strong>
                    </div>
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Peran / Role</span>
                        <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars($session['user_role']) ?></span>
                    </div>
                    <div class="col-md-6 col-12">
                        <span class="text-muted d-block">Waktu Pelaksanaan</span>
                        <strong class="text-dark">
                            <?= date('d M Y, H:i', strtotime($session['datetime_start'])) ?> - 
                            <?= date('H:i', strtotime($session['datetime_end'])) ?> WIB 
                            (<?= htmlspecialchars($session['answer_time']) ?> Menit)
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Score Summary Widget -->
        <div class="col-md-4 col-12">
            <div class="card shadow-sm border-0 rounded-4 p-4 text-center mb-4 bg-white" style="min-height: 220px;">
                <h5 class="fw-bold mb-3 text-dark border-bottom pb-2">Skor Akhir</h5>
                <div class="score-circle-admin mb-2">
                    <span class="text-muted" style="font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Poin</span>
                    <span class="text-primary fw-bold fs-3"><?= $totalScore ?></span>
                    <span class="text-muted" style="font-size: 10px;">dari max <?= $maxScore ?></span>
                </div>
                <div class="mt-2" style="font-size: 11px;">
                    <?php if ($essayCount > 0): ?>
                        <span class="badge bg-warning text-dark px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-info-circle-fill me-1"></i> <?= $essayCount ?> Soal Esai Perlu Evaluasi Manual
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success px-2.5 py-1.5 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Semua Soal Otomatis Dinilai
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Questions & Answers list -->
        <div class="col-12">
            <h4 class="fw-bold text-dark mb-3 px-1">Evaluasi Jawaban Soal</h4>
            
            <?php
            $no = 1;
            foreach ($orderedQuestions as $q):
                $ans = $savedAnswers[$q['id']] ?? null;
                $qChoices = $choicesByQuestion[$q['id']] ?? [];
                $qMedias = $mediasByQuestion[$q['id']] ?? [];

                // Determine if MCQ answer was correct
                $isCorrect = false;
                $chosenChoiceText = '';
                $correctChoiceText = '';
                
                if ($q['question_type'] === 'Multiple Choice' && $ans) {
                    foreach ($qChoices as $c) {
                        if ($c['id'] === $ans['choice_id']) {
                            $chosenChoiceText = $c['choice_text'];
                            if ($c['choice_true'] === 'true') {
                                $isCorrect = true;
                            }
                        }
                        if ($c['choice_true'] === 'true') {
                            $correctChoiceText = $c['choice_text'];
                        }
                    }
                }

                // Card class
                $cardClass = '';
                if ($q['question_type'] === 'Multiple Choice') {
                    $cardClass = $isCorrect ? 'correct' : 'incorrect';
                }
                ?>
                <div class="card shadow-sm border-0 rounded-4 p-4 mb-3 question-card-report <?= $cardClass ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3 border-bottom pb-2">
                        <span class="badge bg-light text-dark fw-bold px-3 py-1.5 rounded-pill" style="font-size: 12px;">
                            Soal No. <?= $no++ ?>
                        </span>
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize me-1 px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">
                                <?= htmlspecialchars($q['question_type']) ?>
                            </span>
                            <?php if (!empty($q['questions_material'])): ?>
                                <span class="badge bg-primary-subtle text-primary px-2.5 py-1.5 rounded-pill" style="font-size: 11px;">
                                    <?= htmlspecialchars($q['questions_material']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Question Text -->
                    <div class="text-dark fw-bold mb-3 fs-6" style="line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($q['question'])) ?>
                    </div>

                    <!-- Media Attachments -->
                    <?php if (!empty($qMedias)): ?>
                        <div class="row g-2 mb-3">
                            <?php foreach ($qMedias as $media): 
                                $ext = strtolower($media['media_extendsion']);
                                $mediaPath = $media['path'];
                                ?>
                                <div class="col-md-6 col-12">
                                    <div class="bg-light p-2 rounded text-center border">
                                        <?php if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'])): ?>
                                            <img src="<?= $mediaPath ?>" class="img-fluid rounded" style="max-height: 180px; object-fit: contain; cursor: pointer;" onclick="previewImage(this.src)" alt="<?= htmlspecialchars($media['media_name']) ?>">
                                        <?php elseif (in_array($ext, ['mp4', 'webm', 'ogg'])): ?>
                                            <video src="<?= $mediaPath ?>" controls class="w-100 rounded" style="max-height: 180px;"></video>
                                        <?php elseif (in_array($ext, ['mp3', 'wav', 'ogg'])): ?>
                                            <audio src="<?= $mediaPath ?>" controls class="w-100 mt-2"></audio>
                                        <?php else: ?>
                                            <div class="p-3 d-flex align-items-center justify-content-center text-dark">
                                                <i class="bi bi-file-earmark-arrow-down-fill text-primary fs-3 me-2"></i>
                                                <div class="text-start">
                                                    <div class="fw-bold" style="font-size: 12px;"><?= htmlspecialchars($media['media_name']) ?></div>
                                                    <a href="<?= $mediaPath ?>" target="_blank" class="btn btn-xs btn-primary py-1 px-2 mt-1" style="font-size: 10px;">Buka Dokumen</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Candidate's Answer rendering -->
                    <div class="answers-report-section mt-2">
                        <?php if ($q['question_type'] === 'Multiple Choice'): ?>
                            <div class="row g-1">
                                <?php foreach ($qChoices as $choice): 
                                    $isChosen = $ans && $choice['id'] === $ans['choice_id'];
                                    $isChoiceCorrect = $choice['choice_true'] === 'true';

                                    $optClass = '';
                                    $icon = '<i class="bi bi-circle text-muted"></i>';
                                    
                                    if ($isChosen) {
                                        if ($isChoiceCorrect) {
                                            $optClass = 'chosen-correct';
                                            $icon = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
                                        } else {
                                            $optClass = 'chosen-incorrect';
                                            $icon = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
                                        }
                                    } elseif ($isChoiceCorrect) {
                                        $optClass = 'actual-correct';
                                        $icon = '<i class="bi bi-check-circle text-success fs-5"></i>';
                                    }
                                    ?>
                                    <div class="col-12">
                                        <div class="choice-option-report <?= $optClass ?>">
                                            <div style="flex-shrink: 0;"><?= $icon ?></div>
                                            <div>
                                                <?= htmlspecialchars($choice['choice_text']) ?>
                                                <?php if (intval($choice['point']) > 0): ?>
                                                    <span class="text-muted fw-normal" style="font-size: 10px;"> (+<?= $choice['point'] ?> Poin)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Verdict summary -->
                            <div class="mt-2" style="font-size: 12px;">
                                <?php if (!$ans || empty($ans['choice_id'])): ?>
                                    <span class="text-danger fw-bold"><i class="bi bi-x-octagon-fill me-1"></i> Kandidat tidak menjawab soal ini (0 Poin)</span>
                                <?php elseif ($isCorrect): ?>
                                    <span class="text-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Jawaban Benar (+<?= intval($qChoices[array_search($ans['choice_id'], array_column($qChoices, 'id'))]['point']) ?> Poin)</span>
                                <?php else: ?>
                                    <span class="text-danger fw-bold"><i class="bi bi-x-circle-fill me-1"></i> Jawaban Salah (0 Poin)</span>
                                <?php endif; ?>
                            </div>

                        <?php else: // Form / Essay Type ?>
                            <div class="p-3 bg-light bg-opacity-75 rounded-4 border-start border-primary border-4 mb-2">
                                <span class="text-muted fw-bold d-block mb-1" style="font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase;">Jawaban Kandidat:</span>
                                <div class="text-dark fs-6" style="line-height: 1.5; white-space: pre-wrap; font-family: 'Nunito', sans-serif;">
                                    <?= (!empty($ans) && !empty($ans['answer_text'])) ? nl2br(htmlspecialchars($ans['answer_text'])) : '<span class="text-danger italic fw-semibold">Tidak ada jawaban ditulis oleh kandidat.</span>' ?>
                                </div>
                            </div>
                            <div class="text-warning fw-bold" style="font-size: 11.5px;">
                                <i class="bi bi-info-circle-fill me-1"></i> Soal tipe Esai memerlukan evaluasi dan pemberian bobot nilai manual oleh tim penguji.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($isPrint): ?>
        <script>
            window.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    window.print();
                }, 800);
            });
        </script>
    <?php endif; ?>

<?php
else:
    // ==========================================
    // LIST VIEW & EXPORT
    // ==========================================
    $search = isset($_GET['search']) ? sani($_GET['search']) : '';
    $filter_test_id = isset($_GET['test_id']) ? sani($_GET['test_id']) : '';
    $filter_date_start = isset($_GET['date_start']) ? sani($_GET['date_start']) : '';
    $filter_date_end = isset($_GET['date_end']) ? sani($_GET['date_end']) : '';

    $whereConditions = [];
    if (!empty($search)) {
        $searchWildcard = '%' . $search . '%';
        $whereConditions[] = "(u.fullname LIKE '$searchWildcard' OR u.email LIKE '$searchWildcard' OR t.title LIKE '$searchWildcard' OR u.role LIKE '$searchWildcard' OR s.status LIKE '$searchWildcard')";
    }
    
    if (!empty($filter_test_id)) {
        $whereConditions[] = "s.test_id = '$filter_test_id'";
    }
    
    if (!empty($filter_date_start)) {
        $whereConditions[] = "DATE(s.datetime_start) >= '$filter_date_start'";
    }
    
    if (!empty($filter_date_end)) {
        $whereConditions[] = "DATE(s.datetime_start) <= '$filter_date_end'";
    }

    $whereClause = '';
    if (count($whereConditions) > 0) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }

    $baseQuery = "SELECT s.id AS session_id, s.test_id, s.user_id, s.datetime_start, s.datetime_end, s.status, s.updated_at,
                     t.title AS test_title, t.point_show,
                     u.fullname AS user_fullname, u.email AS user_email, u.role AS user_role
              FROM test_user_sessions s
              LEFT JOIN tests t ON s.test_id = t.id
              LEFT JOIN users u ON s.user_id = u.id" . 
              $whereClause . " ORDER BY s.datetime_start DESC";

    $pagination = makePagination($con, $baseQuery, 10);
    ?>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message_type'] === 'error' ? 'danger' : $_SESSION['message_type'] ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
    <?php endif; ?>

    <section class="section">
        <!-- Search & Filter Card -->
        <div class="card shadow-sm border-0 rounded-4 p-3 mb-3 bg-white">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="test_test-user-session">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px;">Pencarian Umum</label>
                        <div class="input-group input-group-sm input-group-merge">
                            <span class="input-group-text border-end-0 bg-white" style="border-radius: 8px 0 0 8px;"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" style="border-radius: 0 8px 8px 0;" name="search" placeholder="Nama, email..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px;">Filter Ujian</label>
                        <select name="test_id" class="form-select form-select-sm" style="border-radius: 8px;">
                            <option value="">-- Semua Tes --</option>
                            <?php
                            $testListRes = querySecure($con, "SELECT id, title FROM tests ORDER BY title ASC", [], '');
                            while($t = mysqli_fetch_assoc($testListRes)){
                                $sel = ($filter_test_id == $t['id']) ? 'selected' : '';
                                echo "<option value='{$t['id']}' {$sel}>".htmlspecialchars($t['title'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px;">Tanggal Mulai</label>
                        <input type="date" name="date_start" class="form-control form-control-sm" style="border-radius: 8px;" value="<?= htmlspecialchars($filter_date_start) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-muted fw-bold mb-1" style="font-size: 11px;">Tanggal Akhir</label>
                        <input type="date" name="date_end" class="form-control form-control-sm" style="border-radius: 8px;" value="<?= htmlspecialchars($filter_date_end) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm fw-bold" style="border-radius: 8px; padding-top: 6px; padding-bottom: 6px;" title="Terapkan Filter">
                            <i class="bi bi-funnel-fill"></i> Filter
                        </button>
                        <button type="submit" formaction="actions/pages/test/export-user-session.php" formtarget="_blank" name="export_excel" value="1" class="btn btn-success btn-sm w-100 shadow-sm fw-bold" style="border-radius: 8px; padding-top: 6px; padding-bottom: 6px;" title="Unduh Excel">
                            <i class="bi bi-file-earmark-excel-fill"></i> Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table Container Card -->
        <div class="card shadow-sm border-0 rounded-4 p-3 mb-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover table-striped" style="font-size: 12.5px; vertical-align: middle;">
                    <thead class="table-light">
                        <tr class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.05em;">
                            <th>No</th>
                            <th>Kandidat</th>
                            <th>Tes</th>
                            <th>Durasi Sesi</th>
                            <th>Status</th>
                            <th>Skor (Pilihan Ganda)</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($pagination['data'])):
                        ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2"></i> Belum ada rekapan sesi ujian kandidat.
                                </td>
                            </tr>
                        <?php
                        else:
                            $no = $pagination['from'];
                            foreach ($pagination['data'] as $row):
                                // Calculate score dynamically for this session
                                $scoreRes = querySecure($con, 
                                    "SELECT SUM(CAST(c.point AS UNSIGNED)) AS total_score 
                                     FROM test_user_answers a 
                                     JOIN test_question_choices c ON a.choice_id = c.id 
                                     WHERE a.user_session_id = ? AND c.choice_true = 'true'", 
                                    [$row['session_id']], 
                                    's'
                                );
                                $sessScore = 0;
                                if ($scoreRes && $scoreRow = mysqli_fetch_assoc($scoreRes)) {
                                    $sessScore = intval($scoreRow['total_score'] ?? 0);
                                }

                                // Calculate max score
                                $maxScoreRes = querySecure($con,
                                    "SELECT SUM(CAST(c.point AS UNSIGNED)) AS max_score
                                     FROM test_question_choices c
                                     JOIN test_questions q ON c.question_id = q.id
                                     WHERE q.test_id = ? AND c.choice_true = 'true'",
                                    [$row['test_id']],
                                    's'
                                );
                                $sessMaxScore = 0;
                                if ($maxScoreRes && $maxScoreRow = mysqli_fetch_assoc($maxScoreRes)) {
                                    $sessMaxScore = intval($maxScoreRow['max_score'] ?? 0);
                                }

                                // Check essay count
                                $sessEssayRes = querySecure($con, "SELECT COUNT(*) as essay_count FROM test_questions WHERE test_id = ? AND question_type = 'Form'", [$row['test_id']], 's');
                                $sessEssayCount = 0;
                                if ($sessEssayRes && $sessEssayRow = mysqli_fetch_assoc($sessEssayRes)) {
                                    $sessEssayCount = intval($sessEssayRow['essay_count'] ?? 0);
                                }

                                // Status styling
                                $statusBadge = '';
                                if ($row['status'] === 'active') {
                                    $statusBadge = '<span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-1.5"><i class="bi bi-clock-fill me-1"></i> Sedang Mengerjakan</span>';
                                } else {
                                    $statusBadge = '<span class="badge bg-success-subtle text-success rounded-pill px-2.5 py-1.5"><i class="bi bi-check-circle-fill me-1"></i> Selesai</span>';
                                }
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['user_fullname']) ?></div>
                                        <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($row['user_email']) ?></span>
                                        <div style="font-size: 10.5px;">
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0.5 text-capitalize"><?= htmlspecialchars($row['user_role']) ?></span>
                                        </div>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['test_title']) ?></td>
                                    <td>
                                        <div style="font-size: 11px;"><i class="bi bi-play-circle text-primary me-1"></i><?= date('d M Y, H:i', strtotime($row['datetime_start'])) ?></div>
                                        <div style="font-size: 11px;"><i class="bi bi-stop-circle text-danger me-1"></i><?= date('d M Y, H:i', strtotime($row['datetime_end'])) ?></div>
                                    </td>
                                    <td><?= $statusBadge ?></td>
                                    <td>
                                        <div class="fw-bold text-primary fs-6"><?= $sessScore ?> <span class="text-muted fw-normal" style="font-size: 11px;">/ <?= $sessMaxScore ?></span></div>
                                        <?php if ($sessEssayCount > 0): ?>
                                            <span class="text-warning fw-semibold" style="font-size: 10px;">
                                                <i class="bi bi-pencil-square me-0.5"></i> +<?= $sessEssayCount ?> Soal Esai
                                            </span>
                                        <?php endif; ?>
                                        <div style="font-size: 9px; margin-top: 2px;">
                                            <?php if ($row['point_show'] === 'true'): ?>
                                                <span class="text-success"><i class="bi bi-eye"></i> Terbuka untuk kandidat</span>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="bi bi-eye-slash"></i> Tertutup dari kandidat</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="?hal=test_test-user-session&session_id=<?= $row['session_id'] ?>" class="btn btn-sm btn-info text-white rounded-pill px-2.5" title="Detail Jawaban">
                                                <i class="bi bi-eye"></i> Detail
                                            </a>
                                            <a href="?hal=test_test-user-session&session_id=<?= $row['session_id'] ?>&print=1" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Cetak Hasil">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <a href="actions/?hal=test_test-user-session&delete=<?= $row['session_id'] ?>" class="btn btn-sm btn-danger rounded-circle px-2" onclick="return confirm('Apakah Anda yakin ingin menghapus seluruh rekapan sesi ujian kandidat ini? Tindakan ini permanen.')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination links -->
            <?= showPagination($pagination['total_pages'], $pagination['current_page']); ?>
        </div>
    </section>
<?php endif; ?>
