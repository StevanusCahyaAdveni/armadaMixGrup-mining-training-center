<?php
include 'functions/pagination.php';
$whereClause = "";
if(isset($_GET['search'])){
    $whereClause = "AND full_name LIKE '%{$_GET['search']}%'";
}
$query = "SELECT * FROM employees WHERE 1=1 $whereClause";
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
                <input type="hidden" name="hal" value="employee_employees">
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
                            <th>Full Name</th>
                            <th>Company Name</th>
                            <th>Position</th>
                            <th>Employee Id</th>
                            <th>BPJS Tenaker No</th>
                            <th>BPJS Kes No</th>
                            <th>Merdeka Sehat</th>
                            <th>mine_permit</th>
                            <th>Induction Schedule</th>
                            <th>Mine Permit SCM</th>
                            <th>Simper Teory Test</th>
                            <th>Simper practice Test</th>
                            <th>Simper OJT</th>
                            <th>Simper Status</th>
                            <th>Gaji Pokok</th>
                            <th>Tunjangan Tetap</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($pagination['data'] as $row): ?>
                            <tr class="pt-1 pb-1">
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['company_name']) ?></td>
                                <td><?= htmlspecialchars($row['position']) ?></td>
                                <td><?= htmlspecialchars($row['employee_id']) ?></td>
                                <td><?= htmlspecialchars($row['bpjs_tenaker_no']) ?></td>
                                <td><?= htmlspecialchars($row['bpjs_kes_no']) ?></td>
                                <td><?= htmlspecialchars($row['merdeka_sehat']) ?></td>
                                <td><?= htmlspecialchars($row['mine_permit']) ?></td>
                                <td><?= htmlspecialchars($row['induction_schedule']) ?></td>
                                <td><?= htmlspecialchars($row['mine_permit_scm']) ?></td>
                                <td><?= htmlspecialchars($row['simper_teory_test']) ?></td>
                                <td><?= htmlspecialchars($row['simper_practice_test']) ?></td>
                                <td><?= htmlspecialchars($row['simper_ojt']) ?></td>
                                <td><?= htmlspecialchars($row['simper_status']) ?></td>
                                <td><?= htmlspecialchars($row['gaji_pokok']) ?></td>
                                <td><?= htmlspecialchars($row['tunjangan_tetap']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['full_name']) ?>',
                                        '<?= htmlspecialchars($row['company_name']) ?>',
                                        '<?= htmlspecialchars($row['position']) ?>',
                                        '<?= htmlspecialchars($row['employee_id']) ?>',
                                        '<?= htmlspecialchars($row['bpjs_tenaker_no']) ?>',
                                        '<?= htmlspecialchars($row['bpjs_kes_no']) ?>',
                                        '<?= htmlspecialchars($row['merdeka_sehat']) ?>',
                                        '<?= htmlspecialchars($row['mine_permit']) ?>',
                                        '<?= htmlspecialchars($row['induction_schedule']) ?>',
                                        '<?= htmlspecialchars($row['mine_permit_scm']) ?>',
                                        '<?= htmlspecialchars($row['simper_teory_test']) ?>',
                                        '<?= htmlspecialchars($row['simper_practice_test']) ?>',
                                        '<?= htmlspecialchars($row['simper_ojt']) ?>',
                                        '<?= htmlspecialchars($row['simper_status']) ?>',
                                        '<?= htmlspecialchars($row['gaji_pokok']) ?>',
                                        '<?= htmlspecialchars($row['tunjangan_tetap']) ?>'
                                    )">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=employee_employees&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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
                <h5 class="modal-title">Add New Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" name="position" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employee Id</label>
                        <input type="text" class="form-control" name="employee_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BPJS Tenaker No</label>
                        <input type="text" class="form-control" name="bpjs_tenaker_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BPJS Kes No</label>
                        <input type="text" class="form-control" name="bpjs_kes_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Merdeka Sehat</label>
                        <input type="text" class="form-control" name="merdeka_sehat" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">mine_permit</label>
                        <input type="text" class="form-control" name="mine_permit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Induction Schedule</label>
                        <input type="date" class="form-control" name="induction_schedule" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mine Permit SCM</label>
                        <input type="text" class="form-control" name="mine_permit_scm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper Teory Test</label>
                        <input type="text" class="form-control" name="simper_teory_test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper practice Test</label>
                        <input type="text" class="form-control" name="simper_practice_test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper OJT</label>
                        <input type="text" class="form-control" name="simper_ojt" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper Status</label>
                        <input type="text" class="form-control" name="simper_status" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gaji Pokok</label>
                        <input type="number" class="form-control" name="gaji_pokok" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tunjangan Tetap</label>
                        <input type="number" class="form-control" name="tunjangan_tetap" required>
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
                <h5 class="modal-title">Edit Employees</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" class="form-control" name="company_name" id="edit_company_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Position</label>
                        <input type="text" class="form-control" name="position" id="edit_position" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Employee Id</label>
                        <input type="text" class="form-control" name="employee_id" id="edit_employee_id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BPJS Tenaker No</label>
                        <input type="text" class="form-control" name="bpjs_tenaker_no" id="edit_bpjs_tenaker_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">BPJS Kes No</label>
                        <input type="text" class="form-control" name="bpjs_kes_no" id="edit_bpjs_kes_no" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Merdeka Sehat</label>
                        <input type="text" class="form-control" name="merdeka_sehat" id="edit_merdeka_sehat" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">mine_permit</label>
                        <input type="text" class="form-control" name="mine_permit" id="edit_mine_permit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Induction Schedule</label>
                        <input type="date" class="form-control" name="induction_schedule" id="edit_induction_schedule" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mine Permit SCM</label>
                        <input type="text" class="form-control" name="mine_permit_scm" id="edit_mine_permit_scm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper Teory Test</label>
                        <input type="text" class="form-control" name="simper_teory_test" id="edit_simper_teory_test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper practice Test</label>
                        <input type="text" class="form-control" name="simper_practice_test" id="edit_simper_practice_test" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper OJT</label>
                        <input type="text" class="form-control" name="simper_ojt" id="edit_simper_ojt" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Simper Status</label>
                        <input type="text" class="form-control" name="simper_status" id="edit_simper_status" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gaji Pokok</label>
                        <input type="number" class="form-control" name="gaji_pokok" id="edit_gaji_pokok" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tunjangan Tetap</label>
                        <input type="number" class="form-control" name="tunjangan_tetap" id="edit_tunjangan_tetap" required>
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
function upData(id, full_name, company_name, position, employee_id, bpjs_tenaker_no, bpjs_kes_no, merdeka_sehat, mine_permit, induction_schedule, mine_permit_scm, simper_teory_test, simper_practice_test, simper_ojt, simper_status, gaji_pokok, tunjangan_tetap) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_full_name').value = full_name;
    document.getElementById('edit_company_name').value = company_name;
    document.getElementById('edit_position').value = position;
    document.getElementById('edit_employee_id').value = employee_id;
    document.getElementById('edit_bpjs_tenaker_no').value = bpjs_tenaker_no;
    document.getElementById('edit_bpjs_kes_no').value = bpjs_kes_no;
    document.getElementById('edit_merdeka_sehat').value = merdeka_sehat;
    document.getElementById('edit_mine_permit').value = mine_permit;
    document.getElementById('edit_induction_schedule').value = induction_schedule;
    document.getElementById('edit_mine_permit_scm').value = mine_permit_scm;
    document.getElementById('edit_simper_teory_test').value = simper_teory_test;
    document.getElementById('edit_simper_practice_test').value = simper_practice_test;
    document.getElementById('edit_simper_ojt').value = simper_ojt;
    document.getElementById('edit_simper_status').value = simper_status;
    document.getElementById('edit_gaji_pokok').value = gaji_pokok;
    document.getElementById('edit_tunjangan_tetap').value = tunjangan_tetap;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>
