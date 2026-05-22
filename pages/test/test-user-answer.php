<?php
include 'functions/pagination.php';
$query = "SELECT * FROM test_user_answers";
$pagination = makePagination($con, $query, 10);
?>

<!-- Alert Message -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show" role="alert">
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<!-- Header Section -->
<div class="page-heading">
    <!-- Action Buttons -->
    <p align="right">
        <button type="button" class="btn shadow-sm btn-sm btn-primary " data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Add New
        </button>
    </p>
    <section class="section">
        <!-- Search Form -->
        <div class="card p-2 mb-1 shadow-sm">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="test_test-user-answer">
                <div class="row g-1">
                    <div class="col-10">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?= $_GET['search'] ?? '' ?>">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="card p-2 mb-1 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Question Id</th>
                            <th>If Question Multiple Choice</th>
                            <th>User Session ID</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($pagination['data'] as $row): ?>
                            <tr class="pt-1 pb-1">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['question_id']) ?></td>
                                <td><?= htmlspecialchars($row['choice_id']) ?></td>
                                <td><?= htmlspecialchars($row['user_session_id']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['question_id']) ?>',
                                        '<?= htmlspecialchars($row['choice_id']) ?>',
                                        '<?= htmlspecialchars($row['user_session_id']) ?>'
                                    )">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=test_test-user-answer&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <?= showPagination($pagination['total_pages'], $pagination['current_page']); ?>
        </div>
    </section>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Test-user-answer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test-user-answer" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Question Id</label>
                        <input type="text" class="form-control" name="question_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">If Question Multiple Choice</label>
                        <input type="text" class="form-control" name="choice_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Session ID</label>
                        <input type="text" class="form-control" name="user_session_id" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="addData" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Test-user-answer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test-user-answer" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Question Id</label>
                        <input type="text" class="form-control" name="question_id" id="edit_question_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">If Question Multiple Choice</label>
                        <input type="text" class="form-control" name="choice_id" id="edit_choice_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">User Session ID</label>
                        <input type="text" class="form-control" name="user_session_id" id="edit_user_session_id" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="updateData" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function upData(id, question_id, choice_id, user_session_id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_question_id').value = question_id;
    document.getElementById('edit_choice_id').value = choice_id;
    document.getElementById('edit_user_session_id').value = user_session_id;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>
