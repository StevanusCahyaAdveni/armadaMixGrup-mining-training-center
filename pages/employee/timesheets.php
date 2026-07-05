<?php
include 'functions/pagination.php';
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$whereClause = '';
$user_id_filter = '';

$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-d');

$dateFilter = " t.tanggal >= '$start_date' AND t.tanggal <= '$end_date'";

if (isset($_GET['user_id'])) {
    $uid = sani($_GET['user_id']);
    $user_id_filter = $uid;
    $whereClause = "WHERE t.employee_id = '$uid' AND $dateFilter";
} else {
    $whereClause = "WHERE $dateFilter";
}

if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR t.unit_id LIKE '%$search%')";
}

$query = "SELECT t.*, e.full_name 
          FROM employee_timesheets t 
          LEFT JOIN employees e ON t.employee_id = e.id 
          $whereClause 
          ORDER BY t.tanggal DESC, t.created_at DESC";

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
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- <h3>Timesheets (HM) Karyawan</h3> -->
        <span></span>
        <button type="button" class="btn shadow-sm btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Data
        </button>
    </div>
    
    <section class="section">
        <!-- Search Form -->
        <div class="card p-2 mb-1 shadow-sm">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="employee_timesheets">
                <div class="row g-1">
                    <?php if (isset($_GET['iframe'])): ?>
                        <input type="hidden" name="iframe" value="1">
                    <?php endif; ?>
                    <?php if (isset($_GET['user_id'])): ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET['user_id']) ?>">
                    <?php endif; ?>
                    <div class="col-md-3 mb-1">
                        <input type="date" class="form-control form-control-sm" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required title="Tanggal Mulai">
                    </div>
                    <div class="col-md-3 mb-1">
                        <input type="date" class="form-control form-control-sm" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required title="Tanggal Akhir">
                    </div>
                    <div class="col-md-4 mb-1">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Cari nama, unit..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2 mb-1 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-50" title="Cari Data"><i class="bi bi-search"></i> Cari</button>
                        <a href="actions/pages/employee/export-timesheets.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&user_id=<?= urlencode($user_id_filter) ?>" class="btn btn-sm btn-success w-50" title="Export Excel"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="card p-2 mb-1 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped" style="font-size: 12px; white-space: nowrap;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Shift</th>
                            <th>Nama Operator</th>
                            <th>No Lambung</th>
                            <th>Waktu Awal</th>
                            <th>Waktu Akhir</th>
                            <th>HM Awal</th>
                            <th>HM Akhir</th>
                            <th>Total HM</th>
                            <th>Istirahat</th>
                            <th>HMC</th>
                            <th>Ritase</th>
                            <th>Solar</th>
                            <th>Keterangan</th>
                            <th>Jenis Lembur</th>
                            <th>Jam Lembur</th>
                            <th>Istirahat Lembur</th>
                            <?php if (isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] !== 'HR Site'): ?>
                            <th>Uang Lembur</th>
                            <?php endif; ?>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($pagination['data'])):
                        ?>
                            <tr>
                                <td colspan="20" class="text-center text-muted py-3">Belum ada data timesheet.</td>
                            </tr>
                        <?php
                        else:
                            $no = $pagination['from'];
                            foreach ($pagination['data'] as $row): 
                                $ist_display = ($row['rest_start'] && $row['rest_end']) ? date('H:i', strtotime($row['rest_start'])) . ' - ' . date('H:i', strtotime($row['rest_end'])) : '-';
                            ?>
                                <tr class="pt-1 pb-1">
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                                    <td><?= htmlspecialchars($row['shift']) ?></td>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['unit_id']) ?></td>
                                    <td><?= htmlspecialchars($row['waktu_awal'] ? date('H:i', strtotime($row['waktu_awal'])) : '-') ?></td>
                                    <td><?= htmlspecialchars($row['waktu_akhir'] ? date('H:i', strtotime($row['waktu_akhir'])) : '-') ?></td>
                                    <td><?= htmlspecialchars($row['hm_awal']) ?></td>
                                    <td><?= htmlspecialchars($row['hm_akhir']) ?></td>
                                    <td class="fw-bold text-primary"><?= htmlspecialchars($row['total_hm']) ?></td>
                                    <td><?= $ist_display ?> <small class="text-muted">(<?= $row['ist_hm'] ?>H)</small></td>
                                    <td class="fw-bold text-success"><?= htmlspecialchars($row['hmc']) ?></td>
                                    <td><?= htmlspecialchars($row['ritase']) ?></td>
                                    <td><?= htmlspecialchars($row['solar']) ?></td>
                                    <td><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['overtime_type'] == 'NONE' ? '-' : $row['overtime_type']) ?></td>
                                    <td>
                                        <?php if ($row['overtime_type'] != 'NONE' && $row['overtime_start']): ?>
                                            <?= date('H:i', strtotime($row['overtime_start'])) ?> - <?= date('H:i', strtotime($row['overtime_end'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['overtime_rest_start'] && $row['overtime_rest_end']): ?>
                                            <?= date('H:i', strtotime($row['overtime_rest_start'])) ?> - <?= date('H:i', strtotime($row['overtime_rest_end'])) ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] !== 'HR Site'): ?>
                                    <td class="text-end fw-bold text-success">Rp <?= number_format($row['overtime_amount'] ?? 0, 0, ',', '.') ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                            '<?= $row['id'] ?>',
                                            '<?= htmlspecialchars($row['employee_id']) ?>',
                                            '<?= htmlspecialchars($row['tanggal']) ?>',
                                            '<?= htmlspecialchars($row['shift']) ?>',
                                            '<?= htmlspecialchars($row['unit_id']) ?>',
                                            '<?= htmlspecialchars($row['waktu_awal'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['waktu_akhir'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['hm_awal']) ?>',
                                            '<?= htmlspecialchars($row['hm_akhir']) ?>',
                                            '<?= htmlspecialchars($row['rest_start']) ?>',
                                            '<?= htmlspecialchars($row['rest_end']) ?>',
                                            '<?= htmlspecialchars($row['ritase']) ?>',
                                            '<?= htmlspecialchars($row['solar']) ?>',
                                            '<?= htmlspecialchars($row['keterangan'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['overtime_type'] ?? 'NONE') ?>',
                                            '<?= htmlspecialchars($row['overtime_start'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['overtime_end'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['overtime_rest_start'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['overtime_rest_end'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['hm_awal_lembur'] ?? '') ?>',
                                            '<?= htmlspecialchars($row['hm_akhir_lembur'] ?? '') ?>'
                                        )">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="actions/?hal=employee_timesheets&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus data ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; 
                        endif; ?>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data HM Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_timesheets" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
                            <label class="form-label">Pilih Karyawan / Operator</label>
                            <select class="form-select" name="employee_id" <?= $user_id_filter ? '' : 'required' ?>>
                                <option value="">-- Pilih --</option>
                                <?php
                                $empRes = querySecure($con, "SELECT id, full_name, employee_id FROM employees ORDER BY full_name ASC", [], '');
                                while ($emp = mysqli_fetch_assoc($empRes)) {
                                    echo "<option value='{$emp['id']}'>".htmlspecialchars($emp['full_name']." (".$emp['employee_id'].")")."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php if ($user_id_filter): ?>
                            <input type="hidden" name="employee_id" value="<?= htmlspecialchars($user_id_filter) ?>">
                        <?php endif; ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Shift</label>
                            <select class="form-select" name="shift" required>
                                <option value="SIANG">SIANG</option>
                                <option value="MALAM">MALAM</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No Lambung / Unit</label>
                            <input type="text" class="form-control" name="unit_id" placeholder="EXCA-45" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Awal</label>
                            <input type="time" class="form-control" name="waktu_awal" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Akhir</label>
                            <input type="time" class="form-control" name="waktu_akhir" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">HM Awal</label>
                            <input type="number" step="0.01" class="form-control" name="hm_awal" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">HM Akhir</label>
                            <input type="number" step="0.01" class="form-control" name="hm_akhir" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Mulai</label>
                            <input type="time" class="form-control" name="rest_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Selesai</label>
                            <input type="time" class="form-control" name="rest_end">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jumlah Ritase</label>
                            <input type="number" class="form-control" name="ritase" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Pemakaian Solar</label>
                            <input type="number" step="0.01" class="form-control" name="solar" value="0.00">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" name="keterangan" rows="2" placeholder="Contoh: Unit rusak ringan, cuaca hujan, dll..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3 text-primary">Data Lembur (Opsional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Lembur</label>
                            <select class="form-select" name="overtime_type" onchange="handleOvertimeTypeChange(this, false)">
                                <option value="NONE">Tidak Ada Lembur</option>
                                <option value="BIASA">Lembur Biasa</option>
                                <option value="LIBUR">Lembur Hari Libur</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jam Mulai Lembur</label>
                            <input type="time" class="form-control" name="overtime_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jam Selesai Lembur</label>
                            <input type="time" class="form-control" name="overtime_end">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Lembur Mulai</label>
                            <input type="time" class="form-control" name="overtime_rest_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Lembur Selesai</label>
                            <input type="time" class="form-control" name="overtime_rest_end">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">HM Awal Lembur</label>
                            <input type="number" step="0.01" class="form-control" name="hm_awal_lembur">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">HM Akhir Lembur</label>
                            <input type="number" step="0.01" class="form-control" name="hm_akhir_lembur">
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2" style="font-size: 13px;">
                        <i class="bi bi-info-circle"></i> Sistem akan otomatis menghitung <b>Total HM</b> dan <b>HMC</b> dari data yang Anda masukkan di atas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="addData" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Data HM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_timesheets" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6 mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
                            <label class="form-label">Pilih Karyawan / Operator</label>
                            <select class="form-select" name="employee_id" id="edit_employee_id" <?= $user_id_filter ? '' : 'required' ?>>
                                <option value="">-- Pilih --</option>
                                <?php
                                $empRes2 = querySecure($con, "SELECT id, full_name, employee_id FROM employees ORDER BY full_name ASC", [], '');
                                while ($emp2 = mysqli_fetch_assoc($empRes2)) {
                                    echo "<option value='{$emp2['id']}'>".htmlspecialchars($emp2['full_name']." (".$emp2['employee_id'].")")."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <?php if ($user_id_filter): ?>
                            <input type="hidden" name="employee_id_hidden" id="edit_employee_id_hidden">
                        <?php endif; ?>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" class="form-control" name="tanggal" id="edit_tanggal" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Shift</label>
                            <select class="form-select" name="shift" id="edit_shift" required>
                                <option value="SIANG">SIANG</option>
                                <option value="MALAM">MALAM</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">No Lambung / Unit</label>
                            <input type="text" class="form-control" name="unit_id" id="edit_unit_id" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Awal</label>
                            <input type="time" class="form-control" name="waktu_awal" id="edit_waktu_awal" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Waktu Akhir</label>
                            <input type="time" class="form-control" name="waktu_akhir" id="edit_waktu_akhir" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">HM Awal</label>
                            <input type="number" step="0.01" class="form-control" name="hm_awal" id="edit_hm_awal" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">HM Akhir</label>
                            <input type="number" step="0.01" class="form-control" name="hm_akhir" id="edit_hm_akhir" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Mulai</label>
                            <input type="time" class="form-control" name="rest_start" id="edit_rest_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Selesai</label>
                            <input type="time" class="form-control" name="rest_end" id="edit_rest_end">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jumlah Ritase</label>
                            <input type="number" class="form-control" name="ritase" id="edit_ritase">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Pemakaian Solar</label>
                            <input type="number" step="0.01" class="form-control" name="solar" id="edit_solar">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-2">
                            <label class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" name="keterangan" id="edit_keterangan" rows="2" placeholder="Contoh: Unit rusak ringan, cuaca hujan, dll..."></textarea>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3 text-primary">Data Lembur (Opsional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Jenis Lembur</label>
                            <select class="form-select" name="overtime_type" id="edit_overtime_type" onchange="handleOvertimeTypeChange(this, true)">
                                <option value="NONE">Tidak Ada Lembur</option>
                                <option value="BIASA">Lembur Biasa</option>
                                <option value="LIBUR">Lembur Hari Libur</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jam Mulai Lembur</label>
                            <input type="time" class="form-control" name="overtime_start" id="edit_overtime_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Jam Selesai Lembur</label>
                            <input type="time" class="form-control" name="overtime_end" id="edit_overtime_end">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Lembur Mulai</label>
                            <input type="time" class="form-control" name="overtime_rest_start" id="edit_overtime_rest_start">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Istirahat Lembur Selesai</label>
                            <input type="time" class="form-control" name="overtime_rest_end" id="edit_overtime_rest_end">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">HM Awal Lembur</label>
                            <input type="number" step="0.01" class="form-control" name="hm_awal_lembur" id="edit_hm_awal_lembur">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">HM Akhir Lembur</label>
                            <input type="number" step="0.01" class="form-control" name="hm_akhir_lembur" id="edit_hm_akhir_lembur">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="updateData" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function upData(id, employee_id, tanggal, shift, unit_id, waktu_awal, waktu_akhir, hm_awal, hm_akhir, rest_start, rest_end, ritase, solar, keterangan, overtime_type, overtime_start, overtime_end, overtime_rest_start, overtime_rest_end, hm_awal_lembur, hm_akhir_lembur) {
    document.getElementById('edit_id').value = id;
    if (document.getElementById('edit_employee_id')) {
        document.getElementById('edit_employee_id').value = employee_id;
    }
    if (document.getElementById('edit_employee_id_hidden')) {
        document.getElementById('edit_employee_id_hidden').value = employee_id;
    }
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_shift').value = shift;
    document.getElementById('edit_unit_id').value = unit_id;
    document.getElementById('edit_waktu_awal').value = waktu_awal;
    document.getElementById('edit_waktu_akhir').value = waktu_akhir;
    document.getElementById('edit_hm_awal').value = hm_awal;
    document.getElementById('edit_hm_akhir').value = hm_akhir;
    document.getElementById('edit_rest_start').value = rest_start;
    document.getElementById('edit_rest_end').value = rest_end;
    document.getElementById('edit_ritase').value = ritase;
    document.getElementById('edit_solar').value = solar;
    document.getElementById('edit_keterangan').value = keterangan;
    
    document.getElementById('edit_overtime_type').value = overtime_type ? overtime_type : 'NONE';
    handleOvertimeTypeChange(document.getElementById('edit_overtime_type'), true);
    
    document.getElementById('edit_overtime_start').value = overtime_start;
    document.getElementById('edit_overtime_end').value = overtime_end;
    document.getElementById('edit_overtime_rest_start').value = overtime_rest_start;
    document.getElementById('edit_overtime_rest_end').value = overtime_rest_end;
    document.getElementById('edit_hm_awal_lembur').value = hm_awal_lembur;
    document.getElementById('edit_hm_akhir_lembur').value = hm_akhir_lembur;
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}
</script>

<script>
function handleOvertimeTypeChange(selectElement, isEdit) {
    var form = selectElement.closest('form');
    var hm_awal = form.querySelector('[name="hm_awal"]');
    var hm_akhir = form.querySelector('[name="hm_akhir"]');
    var waktu_awal = form.querySelector('[name="waktu_awal"]');
    var waktu_akhir = form.querySelector('[name="waktu_akhir"]');
    
    if (selectElement.value === 'LIBUR') {
        hm_awal.value = '0';
        hm_akhir.value = '0';
        waktu_awal.value = '';
        waktu_akhir.value = '';
        
        hm_awal.removeAttribute('required');
        hm_akhir.removeAttribute('required');
        waktu_awal.removeAttribute('required');
        waktu_akhir.removeAttribute('required');
        
        hm_awal.setAttribute('readonly', 'true');
        hm_akhir.setAttribute('readonly', 'true');
        waktu_awal.setAttribute('readonly', 'true');
        waktu_akhir.setAttribute('readonly', 'true');
    } else {
        hm_awal.setAttribute('required', 'required');
        hm_akhir.setAttribute('required', 'required');
        waktu_awal.setAttribute('required', 'required');
        waktu_akhir.setAttribute('required', 'required');
        
        hm_awal.removeAttribute('readonly');
        hm_akhir.removeAttribute('readonly');
        waktu_awal.removeAttribute('readonly');
        waktu_akhir.removeAttribute('readonly');
    }
}
</script>
