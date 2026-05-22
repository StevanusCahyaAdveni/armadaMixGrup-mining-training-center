<?php
session_start();
header('Content-Type: application/json');

include '../../config.php';
include '../../functions/sanitasi.php';
include '../../functions/secure_query.php';
include '../../functions/generate_uuid.php';

$action = isset($_POST['action']) ? sani($_POST['action']) : '';
$sessionId = isset($_POST['session_id']) ? sani($_POST['session_id']) : '';

if (empty($action) || empty($sessionId)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
    exit;
}

// Verify Session
$sessionRes = querySecure($con, "SELECT * FROM test_user_sessions WHERE id = ?", [$sessionId], 's');
if (!$sessionRes || mysqli_num_rows($sessionRes) === 0) {
    echo json_encode(['success' => false, 'message' => 'Sesi ujian tidak ditemukan.']);
    exit;
}

$session = mysqli_fetch_assoc($sessionRes);

// Check if already submitted
if ($session['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Ujian sudah dikumpulkan sebelumnya.']);
    exit;
}

// Check if time expired
$currentTime = time();
$endTime = strtotime($session['datetime_end']);
if ($currentTime > $endTime) {
    // Auto submit the exam if time has expired
    executeSecure($con, "UPDATE test_user_sessions SET status = 'submitted' WHERE id = ?", [$sessionId], 's');
    $_SESSION['completed_session_id'] = $sessionId;
    if (isset($_SESSION['exam_session_id'])) {
        unset($_SESSION['exam_session_id']);
    }
    echo json_encode(['success' => false, 'message' => 'Waktu ujian telah habis. Jawaban Anda disimpan dan dikumpulkan otomatis.', 'expired' => true]);
    exit;
}

if ($action === 'save_answer') {
    $questionId = isset($_POST['question_id']) ? sani($_POST['question_id']) : '';
    $choiceId = isset($_POST['choice_id']) ? sani($_POST['choice_id']) : '';
    $answerText = isset($_POST['answer_text']) ? sani($_POST['answer_text']) : '';

    if (empty($questionId)) {
        echo json_encode(['success' => false, 'message' => 'Question ID tidak boleh kosong.']);
        exit;
    }

    // Check if question exists in this test
    $questionRes = querySecure($con, "SELECT id, question_type FROM test_questions WHERE id = ? AND test_id = ?", [$questionId, $session['test_id']], 'ss');
    if (!$questionRes || mysqli_num_rows($questionRes) === 0) {
        echo json_encode(['success' => false, 'message' => 'Soal tidak valid.']);
        exit;
    }
    $question = mysqli_fetch_assoc($questionRes);

    // Check if user answer already exists
    $ansQuery = querySecure($con, "SELECT id FROM test_user_answers WHERE user_session_id = ? AND question_id = ?", [$sessionId, $questionId], 'ss');
    
    if ($ansQuery && mysqli_num_rows($ansQuery) > 0) {
        $ansRow = mysqli_fetch_assoc($ansQuery);
        $answerId = $ansRow['id'];
        
        // Update existing answer
        $updateRes = executeSecure($con, 
            "UPDATE test_user_answers SET choice_id = ?, answer_text = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
            [$choiceId, $answerText, $answerId],
            'sss'
        );
        $success = $updateRes !== false;
    } else {
        // Insert new answer
        $answerId = generate_uuid();
        $insertRes = executeSecure($con,
            "INSERT INTO test_user_answers (id, question_id, choice_id, user_session_id, answer_text) VALUES (?, ?, ?, ?, ?)",
            [$answerId, $questionId, $choiceId, $sessionId, $answerText],
            'sssss'
        );
        $success = $insertRes !== false;
    }

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Jawaban berhasil disimpan.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan jawaban ke database.']);
    }
    exit;
} 

elseif ($action === 'finish_exam') {
    // Mark exam session as submitted
    $finishRes = executeSecure($con, "UPDATE test_user_sessions SET status = 'submitted' WHERE id = ?", [$sessionId], 's');
    
    if ($finishRes) {
        $_SESSION['completed_session_id'] = $sessionId;
        if (isset($_SESSION['exam_session_id'])) {
            unset($_SESSION['exam_session_id']);
        }
        echo json_encode(['success' => true, 'message' => 'Ujian berhasil diselesaikan.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengumpulkan ujian.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
exit;
?>
