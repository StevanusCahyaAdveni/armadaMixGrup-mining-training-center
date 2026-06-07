<?php
include 'functions/pagination.php';
$whereClause = "";
$user_id_filter = "";
if (isset($_GET['user_id'])) {
    $uid = sani($_GET['user_id']);
    $whereClause = "WHERE user_id = '$uid'";
    $user_id_filter = $uid;
}
$query = "SELECT * FROM employee_salary_increasing_decreasing $whereClause";
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
                <input type="hidden" name="hal" value="employee_employee-salary-increasing-decreasing">
                <div class="row g-1">
                    <?php if (isset($_GET['iframe'])): ?>
                        <input type="hidden" name="iframe" value="1">
                    <?php endif; ?>
                    <?php if (isset($_GET['user_id'])): ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET['user_id']) ?>">
                    <?php endif; ?>
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
                            <th>User Id</th>
                            <th>Date</th>
                            <th>Category (increasing or decreasing )</th>
                            <th>Desc</th>
                            <th>Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($pagination['data'] as $row): ?>
                            <tr class="pt-1 pb-1">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['user_id']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['category']) ?></td>
                                <td><?= htmlspecialchars($row['desc']) ?></td>
                                <td><?= htmlspecialchars($row['value']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['user_id']) ?>',
                                        '<?= htmlspecialchars($row['date']) ?>',
                                        '<?= htmlspecialchars($row['category']) ?>',
                                        '<?= htmlspecialchars($row['desc']) ?>',
                                        '<?= htmlspecialchars($row['value']) ?>'
                                    )">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=employee_employee-salary-increasing-decreasing&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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
                <h5 class="modal-title">Add New Employee-salary-increasing-decreasing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employee-salary-increasing-decreasing" method="POST">
                <div class="modal-body">
                    <div class="mb-3 d-none">
                        <label class="form-label">User Id</label>
                        <input type="text" class="form-control" name="user_id" value="<?= htmlspecialchars($user_id_filter) ?>" <?= $user_id_filter ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (increasing or decreasing )</label>
                        <select class="form-select" name="category" required>
                            <option value="">-- Select --</option>
                            <option value="increasing">Increasing (Penambah)</option>
                            <option value="decreasing">Decreasing (Pengurang)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Desc</label>
                        <textarea class="form-control" name="desc" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="number" class="form-control" name="value" required>
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
                <h5 class="modal-title">Edit Employee-salary-increasing-decreasing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employee-salary-increasing-decreasing" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3 d-none">
                        <label class="form-label">User Id</label>
                        <input type="text" class="form-control" name="user_id" id="edit_user_id" <?= $user_id_filter ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="date" id="edit_date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (increasing or decreasing )</label>
                        <select class="form-select" name="category" id="edit_category" required>
                            <option value="increasing">Increasing (Penambah)</option>
                            <option value="decreasing">Decreasing (Pengurang)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Desc</label>
                        <textarea class="form-control" name="desc" id="edit_desc" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Value</label>
                        <input type="number" class="form-control" name="value" id="edit_value" required>
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
function upData(id, user_id, date, category, desc, value) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_user_id').value = user_id;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_category').value = category;
    document.getElementById('edit_desc').value = desc;
    document.getElementById('edit_value').value = value;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>
