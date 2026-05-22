<?php
session_start();
include '../config.php';
include '../functions/sanitasi.php';
include '../functions/secure_query.php';

$sessionId = $_SESSION['exam_session_id'] ?? '';

if (empty($sessionId)) {
    header("Location: index.php");
    exit;
}

// Fetch session details
$sessionRes = querySecure($con, "SELECT * FROM test_user_sessions WHERE id = ?", [$sessionId], 's');
if (!$sessionRes || mysqli_num_rows($sessionRes) === 0) {
    unset($_SESSION['exam_session_id']);
    header("Location: index.php");
    exit;
}
$session = mysqli_fetch_assoc($sessionRes);

// Check session status
if ($session['status'] !== 'active') {
    $_SESSION['completed_session_id'] = $sessionId;
    unset($_SESSION['exam_session_id']);
    header("Location: finish.php");
    exit;
}

// Check time limit
$currentTime = time();
$endTime = strtotime($session['datetime_end']);
if ($currentTime > $endTime) {
    // Session expired, update status to submitted and redirect
    executeSecure($con, "UPDATE test_user_sessions SET status = 'submitted' WHERE id = ?", [$sessionId], 's');
    $_SESSION['completed_session_id'] = $sessionId;
    unset($_SESSION['exam_session_id']);
    header("Location: finish.php");
    exit;
}

// Fetch test metadata
$testRes = querySecure($con, "SELECT * FROM tests WHERE id = ?", [$session['test_id']], 's');
$test = mysqli_fetch_assoc($testRes);

// Fetch user profile
$userRes = querySecure($con, "SELECT fullname, role FROM users WHERE id = ?", [$session['user_id']], 's');
$user = mysqli_fetch_assoc($userRes);

// Fetch questions based on the randomized order stored in session
$questionIds = json_decode($session['question_order'], true);
if (empty($questionIds)) {
    die("Error: Sesi ujian tidak memiliki daftar soal.");
}

$questionsById = [];
$allQ = querySecure($con, "SELECT * FROM test_questions WHERE test_id = ?", [$session['test_id']], 's');
while ($q = mysqli_fetch_assoc($allQ)) {
    $questionsById[$q['id']] = $q;
}

$orderedQuestions = [];
foreach ($questionIds as $qId) {
    if (isset($questionsById[$qId])) {
        $orderedQuestions[] = $questionsById[$qId];
    }
}

// Fetch choices for all questions
$choicesByQuestion = [];
$allChoices = querySecure($con, 
    "SELECT c.* FROM test_question_choices c JOIN test_questions q ON c.question_id = q.id WHERE q.test_id = ? ORDER BY c.created_at ASC", 
    [$session['test_id']], 
    's'
);
if ($allChoices) {
    while ($choice = mysqli_fetch_assoc($allChoices)) {
        $choicesByQuestion[$choice['question_id']][] = $choice;
    }
}

// Fetch medias for all questions
$mediasByQuestion = [];
$allMedias = querySecure($con,
    "SELECT m.* FROM test_question_medias m JOIN test_questions q ON m.question_id = q.id WHERE q.test_id = ? ORDER BY m.created_at ASC",
    [$session['test_id']],
    's'
);
if ($allMedias) {
    while ($media = mysqli_fetch_assoc($allMedias)) {
        $mediasByQuestion[$media['question_id']][] = $media;
    }
}

// Fetch existing saved answers
$savedAnswers = [];
$allAnswers = querySecure($con, "SELECT question_id, choice_id, answer_text FROM test_user_answers WHERE user_session_id = ?", [$sessionId], 's');
if ($allAnswers) {
    while ($ans = mysqli_fetch_assoc($allAnswers)) {
        $savedAnswers[$ans['question_id']] = [
            'choice_id' => $ans['choice_id'],
            'answer_text' => $ans['answer_text']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Ujian - <?= htmlspecialchars($test['title']) ?></title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Small styling adjustments to center loading overlay */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            transition: opacity 0.3s ease;
        }
        .question-text-content {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #1e293b;
        }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div id="loading-overlay">
    <span class="cbt-loader mb-3"></span>
    <h5 class="fw-bold text-primary">Memuat Lembar Ujian...</h5>
    <p class="text-muted" style="font-size: 13px;">Harap tunggu sebentar.</p>
</div>

<!-- Header Navigation -->
<nav class="navbar navbar-cbt shadow-sm sticky-top py-2">
    <div class="container-fluid px-md-5 d-flex justify-content-between align-items-center">
        <span class="navbar-brand mb-0 h1 fw-bold text-primary fs-4">
            <i class="bi bi-mortarboard-fill me-2"></i>MTC CBT
        </span>
        <div class="d-flex align-items-center bg-white bg-opacity-75 border rounded-pill py-1 px-3">
            <div class="me-3 d-none d-md-block text-end" style="line-height: 1.2;">
                <div class="fw-bold text-dark" style="font-size: 13px;"><?= htmlspecialchars($user['fullname']) ?></div>
                <div class="text-muted text-capitalize" style="font-size: 11px;"><?= htmlspecialchars($user['role']) ?></div>
            </div>
            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; font-size: 15px;">
                <?= strtoupper(substr($user['fullname'], 0, 1)) ?>
            </div>
        </div>
    </div>
</nav>

<!-- Core Content Grid -->
<div class="container-fluid px-md-5 cbt-container">
    <div class="row g-4">
        
        <!-- Left Panel: Active Question & Choices -->
        <div class="col-lg-8 col-12">
            <div class="glass-panel question-card bg-white p-4 p-md-5 shadow-sm border border-white">
                <div id="question-area">
                    <!-- Dynamic rendering in JS -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary px-3 py-2 rounded-pill" style="font-size: 11px;">
                            Soal No. <span id="soal-number-title">1</span> dari <?= count($orderedQuestions) ?>
                        </span>
                        <span class="badge bg-secondary px-3 py-2 rounded-pill" id="question-material-badge" style="font-size: 11px; display: none;"></span>
                    </div>
                    
                    <!-- Media Area -->
                    <div id="question-media-section" class="mb-4" style="display: none;"></div>

                    <!-- Question Text -->
                    <div class="question-text-content mb-4" id="question-text-container"></div>
                    
                    <!-- Answer Input Area -->
                    <div id="choices-input-container"></div>
                </div>

                <!-- Navigation Action Buttons -->
                <div class="d-flex justify-content-between align-items-center border-top pt-4 mt-4">
                    <button type="button" class="btn btn-outline-secondary px-4 py-2" id="btn-prev" onclick="prevQuestion()">
                        <i class="bi bi-chevron-left me-1"></i> Sebelumnya
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2" id="btn-next" onclick="nextQuestion()">
                        Selanjutnya <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel: Timer & Grid Navigation -->
        <div class="col-lg-4 col-12">
            <!-- Timer Display Card -->
            <div class="glass-panel p-4 mb-4 text-center bg-white border border-white shadow-sm">
                <div class="text-muted fw-bold mb-2" style="font-size: 12px; letter-spacing: 0.05em; text-transform: uppercase;">
                    <i class="bi bi-clock-history me-1"></i> Sisa Waktu Ujian
                </div>
                <div class="timer-text py-2" id="timer-display">00:00:00</div>
            </div>

            <!-- Grid Numbers Navigation Card -->
            <div class="glass-panel p-4 bg-white border border-white shadow-sm">
                <div class="fw-bold mb-3 pb-2 border-bottom text-dark" style="font-size: 14px;">
                    <i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i> Navigasi Soal
                </div>
                
                <div class="grid-container mb-4" id="grid-navigation-container">
                    <!-- Dynamic rendering in JS -->
                </div>

                <div class="border-top pt-3">
                    <div class="d-flex gap-3 mb-4" style="font-size: 11px;">
                        <span class="d-flex align-items-center text-muted">
                            <span class="d-inline-block rounded-2 bg-success me-1 border" style="width: 12px; height: 12px;"></span> Terjawab
                        </span>
                        <span class="d-flex align-items-center text-muted">
                            <span class="d-inline-block rounded-2 bg-white me-1 border border-secondary" style="width: 12px; height: 12px;"></span> Belum Terjawab
                        </span>
                        <span class="d-flex align-items-center text-muted">
                            <span class="d-inline-block rounded-2 bg-primary-subtle me-1 border border-primary" style="width: 12px; height: 12px;"></span> Aktif
                        </span>
                    </div>

                    <button type="button" class="btn btn-danger w-100 py-3 shadow-sm fw-bold" onclick="finishExamManual()">
                        <i class="bi bi-check2-circle me-1"></i> Selesaikan Ujian
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- JavaScript CDN -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Injected Data from PHP
    const session_id = '<?= $sessionId ?>';
    const questions = <?= json_encode($orderedQuestions) ?>;
    const choices = <?= json_encode($choicesByQuestion) ?>;
    const medias = <?= json_encode($mediasByQuestion) ?>;
    const savedAnswers = <?= json_encode($savedAnswers) ?>;
    const totalQuestions = questions.length;
    
    let currentIndex = 0;
    let autosaveTimeout = null;

    // Remove Loader Overlay on DOM Ready
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('loading-overlay').style.opacity = 0;
        setTimeout(() => {
            document.getElementById('loading-overlay').style.display = 'none';
        }, 300);

        buildNavigationGrid();
        loadQuestion(0);
        startTimer();
    });

    // Build Right Navigation Grid
    function buildNavigationGrid() {
        const container = document.getElementById('grid-navigation-container');
        container.innerHTML = '';

        for (let i = 0; i < totalQuestions; i++) {
            const q = questions[i];
            const hasAnswer = savedAnswers.hasOwnProperty(q.id) && 
                (savedAnswers[q.id].choice_id !== '' || savedAnswers[q.id].answer_text !== null && savedAnswers[q.id].answer_text !== '');
            
            const btn = document.createElement('div');
            btn.className = `grid-item ${hasAnswer ? 'answered' : 'unanswered'}`;
            btn.id = `grid-item-${i}`;
            btn.innerText = i + 1;
            btn.onclick = function() { loadQuestion(i); };
            
            container.appendChild(btn);
        }
    }

    // Load Question by Index
    function loadQuestion(index) {
        if (index < 0 || index >= totalQuestions) return;
        
        currentIndex = index;
        const q = questions[currentIndex];
        const qChoices = choices[q.id] || [];
        const qMedias = medias[q.id] || [];
        const ans = savedAnswers[q.id] || { choice_id: '', answer_text: '' };

        // 1. Update Title Number and Material
        document.getElementById('soal-number-title').innerText = index + 1;
        const materialBadge = document.getElementById('question-material-badge');
        if (q.questions_material && q.questions_material.trim() !== '') {
            materialBadge.innerText = q.questions_material;
            materialBadge.style.display = 'inline-block';
        } else {
            materialBadge.style.display = 'none';
        }

        // 2. Render Media Attachments
        const mediaSec = document.getElementById('question-media-section');
        mediaSec.innerHTML = '';
        if (qMedias.length > 0) {
            mediaSec.style.display = 'block';
            qMedias.forEach(media => {
                const wrapper = document.createElement('div');
                wrapper.className = 'question-media-container text-center mb-3 bg-light p-2 rounded';
                
                const ext = media.media_extendsion.toLowerCase();
                const mediaPath = '../' + media.path; // Absolute relative path to the root media folder

                if (['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'].includes(ext)) {
                    wrapper.innerHTML = `<img src="${mediaPath}" class="img-fluid rounded question-media-img" alt="${media.media_name}" style="max-height: 220px; object-fit: contain;">`;
                } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                    wrapper.innerHTML = `<video src="${mediaPath}" controls class="w-100 rounded" style="max-height: 220px;"></video>`;
                } else if (['mp3', 'wav', 'ogg'].includes(ext)) {
                    wrapper.innerHTML = `<audio src="${mediaPath}" controls class="w-100 mt-2"></audio>`;
                } else {
                    wrapper.innerHTML = `
                        <div class="p-3 d-flex align-items-center justify-content-center text-dark">
                            <i class="bi bi-file-earmark-arrow-down-fill text-primary fs-3 me-2"></i>
                            <div>
                                <div class="fw-bold" style="font-size: 12px;">${media.media_name}</div>
                                <a href="${mediaPath}" target="_blank" class="btn btn-xs btn-primary py-1 px-2 mt-1" style="font-size: 10px;">Download Lampiran</a>
                            </div>
                        </div>`;
                }
                mediaSec.appendChild(wrapper);
            });
        } else {
            mediaSec.style.display = 'none';
        }

        // 3. Render Question Text
        document.getElementById('question-text-container').innerHTML = q.question.replace(/\n/g, '<br>');

        // 4. Render Input / Choices
        const inputSec = document.getElementById('choices-input-container');
        inputSec.innerHTML = '';

        if (q.question_type === 'Multiple Choice') {
            qChoices.forEach(choice => {
                const isSelected = ans.choice_id === choice.id;
                const wrapper = document.createElement('div');
                wrapper.className = `choice-item ${isSelected ? 'selected' : ''}`;
                wrapper.onclick = function() { selectChoice(choice.id, wrapper); };

                wrapper.innerHTML = `
                    <input type="radio" name="cbt_radio_choices" class="choice-radio-input form-check-input" value="${choice.id}" ${isSelected ? 'checked' : ''}>
                    <div style="font-size: 14px;">${choice.choice_text}</div>
                `;
                inputSec.appendChild(wrapper);
            });
        } else {
            // Form/Essay Type
            const textarea = document.createElement('textarea');
            textarea.className = 'form-control rounded-4 shadow-sm border border-secondary border-opacity-25';
            textarea.rows = 6;
            textarea.placeholder = 'Tuliskan jawaban lengkap Anda di sini... Jawaban Anda akan tersimpan otomatis saat Anda mulai berpindah soal.';
            textarea.value = ans.answer_text || '';
            textarea.onblur = function() { saveEssayAnswer(textarea.value); };
            textarea.oninput = function() { debounceSaveEssay(textarea.value); };

            inputSec.appendChild(textarea);
        }

        // 5. Update Grid Highlight
        document.querySelectorAll('.grid-item').forEach(item => {
            item.classList.remove('active-item');
        });
        const currentGridItem = document.getElementById(`grid-item-${index}`);
        if (currentGridItem) {
            currentGridItem.classList.add('active-item');
        }

        // 6. Toggle Prev/Next Buttons
        document.getElementById('btn-prev').disabled = index === 0;
        const nextBtn = document.getElementById('btn-next');
        if (index === totalQuestions - 1) {
            nextBtn.innerHTML = 'Selesai <i class="bi bi-check-lg ms-1"></i>';
            nextBtn.onclick = function() { finishExamManual(); };
        } else {
            nextBtn.innerHTML = 'Selanjutnya <i class="bi bi-chevron-right ms-1"></i>';
            nextBtn.onclick = function() { nextQuestion(); };
        }
    }

    // Navigate Questions
    function prevQuestion() {
        if (currentIndex > 0) {
            loadQuestion(currentIndex - 1);
        }
    }

    function nextQuestion() {
        if (currentIndex < totalQuestions - 1) {
            loadQuestion(currentIndex + 1);
        }
    }

    // Select Choice logic (Autosave)
    function selectChoice(choiceId, element) {
        // Update styling
        document.querySelectorAll('.choice-item').forEach(item => {
            item.classList.remove('selected');
            item.querySelector('.choice-radio-input').checked = false;
        });

        element.classList.add('selected');
        element.querySelector('.choice-radio-input').checked = true;

        const q = questions[currentIndex];
        
        // Save to temporary memory
        if (!savedAnswers[q.id]) {
            savedAnswers[q.id] = { choice_id: '', answer_text: '' };
        }
        savedAnswers[q.id].choice_id = choiceId;

        // Perform AJAX Save
        sendAnswerAjax(q.id, choiceId, '');
    }

    // Essay autosave logic
    function saveEssayAnswer(val) {
        const q = questions[currentIndex];
        if (!savedAnswers[q.id]) {
            savedAnswers[q.id] = { choice_id: '', answer_text: '' };
        }
        
        // Save only if different
        if (savedAnswers[q.id].answer_text !== val) {
            savedAnswers[q.id].answer_text = val;
            sendAnswerAjax(q.id, '', val);
        }
    }

    // Debounce Save to prevent spamming database on every keypress
    function debounceSaveEssay(val) {
        clearTimeout(autosaveTimeout);
        autosaveTimeout = setTimeout(function() {
            saveEssayAnswer(val);
        }, 1500);
    }

    // AJAX Send Answer Helper
    function sendAnswerAjax(questionId, choiceId, answerText) {
        const formData = new URLSearchParams();
        formData.append('action', 'save_answer');
        formData.append('session_id', session_id);
        formData.append('question_id', questionId);
        formData.append('choice_id', choiceId);
        formData.append('answer_text', answerText);

        fetch('actions/submit-exam.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update grid highlight color
                const gridItem = document.getElementById(`grid-item-${currentIndex}`);
                if (gridItem) {
                    gridItem.classList.remove('unanswered');
                    gridItem.classList.add('answered');
                }
            } else {
                if (data.expired) {
                    alert(data.message);
                    window.location.href = 'finish.php';
                } else {
                    console.error('Save failed:', data.message);
                }
            }
        })
        .catch(err => {
            console.error('Network Error:', err);
        });
    }

    // Countdown Timer Logic
    function startTimer() {
        const endTime = new Date("<?= date('c', $endTime) ?>").getTime();
        const display = document.getElementById('timer-display');

        const x = setInterval(function() {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance <= 0) {
                clearInterval(x);
                display.innerText = "00:00:00";
                display.classList.add('timer-critical');
                autoSubmitExam();
                return;
            }

            // Calculations for hours, minutes, and seconds
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Output format
            const formatted = 
                (hours < 10 ? "0" + hours : hours) + ":" + 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);

            display.innerText = formatted;

            // Warn user if time is less than 5 minutes (300 seconds)
            if (distance < 300000) {
                display.classList.add('timer-critical');
            } else {
                display.classList.remove('timer-critical');
            }
        }, 1000);
    }

    // Finalize Exam (Manual)
    function finishExamManual() {
        // Check if there are unanswered questions
        let unansweredCount = 0;
        for (let i = 0; i < totalQuestions; i++) {
            const q = questions[i];
            const hasAnswer = savedAnswers.hasOwnProperty(q.id) && 
                (savedAnswers[q.id].choice_id !== '' || savedAnswers[q.id].answer_text !== null && savedAnswers[q.id].answer_text !== '');
            if (!hasAnswer) unansweredCount++;
        }

        let confirmMsg = "Apakah Anda yakin ingin menyelesaikan ujian dan mengumpulkan jawaban?";
        if (unansweredCount > 0) {
            confirmMsg = `Masih ada ${unansweredCount} soal yang belum Anda jawab. Apakah Anda tetap yakin ingin mengumpulkan ujian?`;
        }

        if (confirm(confirmMsg)) {
            submitExamAjax();
        }
    }

    // Finalize Exam (Time limit expired)
    function autoSubmitExam() {
        alert("Waktu pengerjaan ujian telah habis! Jawaban Anda akan dikumpulkan secara otomatis.");
        submitExamAjax();
    }

    // AJAX Final Submission
    function submitExamAjax() {
        // Show loader overlay
        const overlay = document.getElementById('loading-overlay');
        overlay.querySelector('h5').innerText = "Mengumpulkan Ujian...";
        overlay.querySelector('p').innerText = "Jawaban sedang dikunci.";
        overlay.style.display = 'flex';
        overlay.style.opacity = 1;

        const formData = new URLSearchParams();
        formData.append('action', 'finish_exam');
        formData.append('session_id', session_id);

        fetch('actions/submit-exam.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'finish.php';
            } else {
                alert('Terjadi kesalahan saat mengumpulkan ujian: ' + data.message);
                overlay.style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Submission Error:', err);
            alert('Koneksi internet bermasalah. Kami akan mencoba mengalihkan ke halaman selesai.');
            window.location.href = 'finish.php';
        });
    }
</script>
</body>
</html>
