<?php
include 'functions/pagination.php';
$whereClause = "";
$user_id_filter = "";
if (isset($_GET['user_id'])) {
    $uid = sani($_GET['user_id']);
    $whereClause = "WHERE user_id = '$uid'";
    $user_id_filter = $uid;
}
$query = "SELECT * FROM employee_overtimes $whereClause";
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
                <input type="hidden" name="hal" value="employee_employee-overtime">
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
                            <th>Description</th>
                            <th>Start Overtime</th>
                            <th>End Overtime</th>
                            <th>Shift (Pagi dan Malam)</th>
                            <th>Amount</th>
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
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= htmlspecialchars($row['overtime_start']) ?></td>
                                <td><?= htmlspecialchars($row['overtime_end']) ?></td>
                                <td><?= htmlspecialchars($row['shift']) ?></td>
                                <td>Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['user_id']) ?>',
                                        '<?= htmlspecialchars($row['description']) ?>',
                                        '<?= htmlspecialchars($row['overtime_start']) ?>',
                                        '<?= htmlspecialchars($row['overtime_end']) ?>',
                                        '<?= htmlspecialchars($row['shift']) ?>',
                                        '<?= htmlspecialchars($row['amount']) ?>'
                                    )">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=employee_employee-overtime&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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
                <h5 class="modal-title">Add New Employee-overtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employee-overtime" method="POST">
                <div class="modal-body">
                    <div class="mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
                        <label class="form-label">User Id</label>
                        <input type="text" class="form-control" name="user_id" value="<?= htmlspecialchars($user_id_filter) ?>" <?= $user_id_filter ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Overtime</label>
                        <input type="datetime-local" class="form-control" name="overtime_start" id="add_overtime_start" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Overtime</label>
                        <input type="datetime-local" class="form-control" name="overtime_end" id="add_overtime_end" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift (Pagi dan Malam)</label>
                        <select class="form-select" name="shift" required>
                            <option value="">-- Select Shift --</option>
                            <option value="Pagi">Pagi</option>
                            <option value="Malam">Malam</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" id="add_amount" required>
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
                <h5 class="modal-title">Edit Employee-overtime</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employee-overtime" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
                        <label class="form-label">User Id</label>
                        <input type="text" class="form-control" name="user_id" id="edit_user_id" <?= $user_id_filter ? 'readonly' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" id="edit_description" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Overtime</label>
                        <input type="datetime-local" class="form-control" name="overtime_start" id="edit_overtime_start" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Overtime</label>
                        <input type="datetime-local" class="form-control" name="overtime_end" id="edit_overtime_end" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shift (Pagi dan Malam)</label>
                        <select class="form-select" name="shift" id="edit_shift" required>
                            <option value="Pagi">Pagi</option>
                            <option value="Malam">Malam</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" class="form-control" name="amount" id="edit_amount" required>
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
function upData(id, user_id, description, overtime_start, overtime_end, shift, amount) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_user_id').value = user_id;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_overtime_start').value = overtime_start;
    document.getElementById('edit_overtime_end').value = overtime_end;
    document.getElementById('edit_shift').value = shift;
    document.getElementById('edit_amount').value = amount;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function calculateOvertime(startId, endId, amountId) {
    const startInput = document.getElementById(startId);
    const endInput = document.getElementById(endId);
    const amountInput = document.getElementById(amountId);

    function calculate() {
        if (!startInput.value || !endInput.value) {
            return;
        }
        const start = new Date(startInput.value);
        const end = new Date(endInput.value);
        
        if (end <= start) {
            amountInput.value = 0;
            return;
        }
        
        const diffMs = end - start;
        const diffHours = diffMs / (1000 * 60 * 60);
        
        let amount = 0;
        const hourlyRate = 19505;
        
        if (diffHours <= 1) {
            amount = diffHours * 1.5 * hourlyRate;
        } else {
            amount = (1 * 1.5 * hourlyRate) + ((diffHours - 1) * 2 * hourlyRate);
        }
        
        amountInput.value = Math.round(amount);
    }

    startInput.addEventListener('change', calculate);
    endInput.addEventListener('change', calculate);
}

// Initialize for Add Modal
calculateOvertime('add_overtime_start', 'add_overtime_end', 'add_amount');
// Initialize for Edit Modal
calculateOvertime('edit_overtime_start', 'edit_overtime_end', 'edit_amount');
</script>
