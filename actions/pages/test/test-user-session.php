<?php
/**
 * Action: test/test-user-session
 * Depth: 3 (actions/pages/test/)
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // No POST requests are currently expected for this module.
    redirectWithMessage('../?hal=test_test-user-session', 'Aksi tidak valid.', 'error');
} elseif (isset($_GET['delete'])) {
    $id = sani($_GET['delete']);

    // 1. Get session details to log it nicely
    $sessionQuery = querySecure($con, "SELECT s.*, u.fullname FROM test_user_sessions s JOIN users u ON s.user_id = u.id WHERE s.id = ?", [$id], 's');
    $sessionData = mysqli_fetch_assoc($sessionQuery);
    $candidateName = $sessionData ? $sessionData['fullname'] : 'Unknown Candidate';

    // 2. Delete all answers for this session
    executeSecure($con, "DELETE FROM test_user_answers WHERE user_session_id = ?", [$id], 's');

    // 3. Delete the session itself
    $deleteResult = executeSecure($con, "DELETE FROM test_user_sessions WHERE id = ?", [$id], 's');

    if ($deleteResult) {
        createLog($con, $_SESSION['admin']['email'], 'Successful MTC exam session deletion for candidate: ' . $candidateName);
        redirectWithMessage('../?hal=test_test-user-session', 'Sesi ujian kandidat berhasil dihapus!', 'success');
    } else {
        redirectWithMessage('../?hal=test_test-user-session', 'Gagal menghapus sesi ujian.', 'error');
    }
    exit;
} else {
    redirectWithMessage('../../index.php', 'Akses tidak valid.', 'error');
}
?>
