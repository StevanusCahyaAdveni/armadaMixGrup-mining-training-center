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
        <div class="d-flex gap-2">
            <button type="button" class="btn shadow-sm btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import / Paste Excel
            </button>
            <button type="button" class="btn shadow-sm btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Data
            </button>
        </div>
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
                            <th>Tipe Shift</th>
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
                                <td colspan="21" class="text-center text-muted py-3">Belum ada data timesheet.</td>
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
                                    <td><span class="badge <?= $row['shift'] === 'MALAM' ? 'bg-dark' : 'bg-info text-dark' ?>"><?= htmlspecialchars($row['shift']) ?></span></td>
                                    <td>
                                        <?php if ($row['shift_type'] == '2' || $row['overtime_type'] != 'NONE'): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Shift 2 (OT)</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-briefcase me-1"></i>Shift 1 (Pokok)</span>
                                        <?php endif; ?>
                                    </td>
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
                                            '<?= htmlspecialchars($row['shift_type'] ?? '1') ?>',
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
                        <div class="col-md-5 mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
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
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Shift (Waktu)</label>
                            <select class="form-select" name="shift" required>
                                <option value="SIANG">SIANG</option>
                                <option value="MALAM">MALAM</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Tipe Shift</label>
                            <select class="form-select" name="shift_type" required>
                                <option value="1">Shift 1 (Pokok)</option>
                                <option value="2">Shift 2 (OT)</option>
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
                        <div class="col-md-5 mb-3 <?= $user_id_filter ? 'd-none' : '' ?>">
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
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Shift (Waktu)</label>
                            <select class="form-select" name="shift" id="edit_shift" required>
                                <option value="SIANG">SIANG</option>
                                <option value="MALAM">MALAM</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Tipe Shift</label>
                            <select class="form-select" name="shift_type" id="edit_shift_type" required>
                                <option value="1">Shift 1 (Pokok)</option>
                                <option value="2">Shift 2 (OT)</option>
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
function upData(id, employee_id, tanggal, shift, shift_type, unit_id, waktu_awal, waktu_akhir, hm_awal, hm_akhir, rest_start, rest_end, ritase, solar, keterangan, overtime_type, overtime_start, overtime_end, overtime_rest_start, overtime_rest_end, hm_awal_lembur, hm_akhir_lembur) {
    document.getElementById('edit_id').value = id;
    if (document.getElementById('edit_employee_id')) {
        document.getElementById('edit_employee_id').value = employee_id;
    }
    if (document.getElementById('edit_employee_id_hidden')) {
        document.getElementById('edit_employee_id_hidden').value = employee_id;
    }
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_shift').value = shift;
    if (document.getElementById('edit_shift_type')) {
        document.getElementById('edit_shift_type').value = shift_type || '1';
    }
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

<!-- Select2 CSS & Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
/* Custom styling for Select2 inside preview modal table */
.select2-container--bootstrap-5 .select2-selection {
    font-size: 11.5px !important;
    min-height: 28px !important;
    padding: 2px 4px !important;
    border-color: #dee2e6;
}
.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    padding-left: 2px !important;
    line-height: 22px !important;
    font-size: 11.5px !important;
}
.select2-container--bootstrap-5 .select2-dropdown .select2-results__option {
    font-size: 12px !important;
    padding: 4px 8px !important;
}
.select2-container {
    z-index: 99999 !important;
}
</style>

<!-- Import / Paste Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 95vw;">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fs-6" id="importExcelModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> Import / Paste Data Excel Unit ke Timesheets
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Step 1: Input Box -->
                <div id="importStep1">
                    <div class="alert alert-light border border-success border-opacity-25 py-2 mb-3 shadow-sm" style="font-size: 13px;">
                        <div class="fw-bold text-success mb-1"><i class="bi bi-info-circle-fill me-1"></i> Panduan Copy-Paste dari Excel Unit:</div>
                        <ol class="mb-1 ps-3">
                            <li>Buka spreadsheet / Excel laporan harian dari unit lapangan (seperti format 25 kolom standard unit).</li>
                            <li>Blok baris data yang ingin diinput, tekan <b>Ctrl + C</b> (Salin). <i>(Baris judul/header boleh ikut ter-copy, sistem otomatis mengabaikannya)</i>.</li>
                            <li>Klik pada kotak teks di bawah lalu tekan <b>Ctrl + V</b> (Tempel / Paste).</li>
                            <li>Klik tombol <b>"Proses & Preview Data"</b> untuk melihat hasil pengenalan nama operator dan perhitungan otomatis.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Paste Data Excel Di Sini:</label>
                        <textarea id="rawExcelData" class="form-control font-monospace" rows="11" style="font-size: 12px; white-space: pre;" placeholder="Paste data dari Excel di sini (contoh: 01-Jun-26	SIANG	1	EXCA-45	FITRA RAMADANA	2.645,00	2.652,00	7,00	7,00	7,05	7,05	15,00	...)"></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearPasteArea()">
                            <i class="bi bi-eraser me-1"></i> Bersihkan Kotak
                        </button>
                        <button type="button" id="btnPreviewData" class="btn btn-primary px-4 shadow-sm" onclick="processExcelPreview()">
                            <i class="bi bi-arrow-right-circle me-1"></i> Proses & Preview Data
                        </button>
                    </div>
                </div>

                <!-- Step 2: Preview & Validation Table -->
                <div id="importStep2" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <span class="badge bg-primary fs-6 cursor-pointer" id="badgeTotalRows" onclick="quickFilterStatus('ALL')" title="Klik untuk tampilkan semua" style="cursor: pointer;">0 Baris</span>
                            <span class="badge bg-success fs-6 cursor-pointer" id="badgeMatchedRows" onclick="quickFilterStatus('MATCHED')" title="Klik untuk filter Karyawan Cocok" style="cursor: pointer;">0 Karyawan Cocok</span>
                            <span class="badge bg-danger fs-6 cursor-pointer" id="badgeUnmatchedRows" onclick="quickFilterStatus('UNMATCHED')" title="Klik untuk filter Belum Cocok" style="cursor: pointer; display:none;">0 Belum Cocok</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="?hal=employee_employees" target="_blank" class="btn btn-sm btn-outline-primary" title="Buka master karyawan di tab baru untuk tambah karyawan baru">
                                <i class="bi bi-person-plus me-1"></i> Tambah Karyawan Baru (Tab Baru)
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="backToStep1()">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Kotak Input
                            </button>
                        </div>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="card p-2 mb-2 bg-light border shadow-sm">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" id="filterSearchName" class="form-control" placeholder="Cari nama operator, unit, tanggal..." onkeyup="applyPreviewFilters()">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('filterSearchName').value=''; applyPreviewFilters();" title="Hapus Pencarian">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <label class="input-group-text bg-white small">Filter Status:</label>
                                    <select id="filterStatus" class="form-select form-select-sm" onchange="applyPreviewFilters()">
                                        <option value="ALL">Semua Status</option>
                                        <option value="MATCHED">✅ Karyawan Cocok</option>
                                        <option value="UNMATCHED">⚠️ Belum Cocok / Belum Dipilih</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 text-end">
                                <span class="small fw-semibold text-muted" id="filterResultCount">Menampilkan semua</span>
                            </div>
                        </div>
                    </div>

                    <div id="unmatchedAlert" class="alert alert-warning py-2 mb-2 shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 12.5px; display: none;">
                        <div>
                            <i class="bi bi-exclamation-triangle-fill me-1 text-danger"></i> 
                            <b>Pemberitahuan:</b> Ada operator yang belum cocok di sistem. HR harus mendaftarkan karyawan terlebih dahulu di menu Karyawan. Dropdown karyawan di bawah terhubung <b>Realtime via API</b> (karyawan baru langsung muncul saat dicari).
                        </div>
                        <a href="?hal=employee_employees" target="_blank" class="btn btn-sm btn-primary py-0 px-2 text-white shadow-sm" style="font-size: 11.5px;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Master Karyawan
                        </a>
                    </div>

                    <div class="table-responsive border rounded mb-3" style="max-height: 460px; font-size: 11px;">
                        <table class="table table-sm table-hover table-striped mb-0" style="white-space: nowrap;">
                            <thead class="table-dark sticky-top" style="z-index: 5;">
                                <tr>
                                    <th class="text-center" width="40">
                                        <input type="checkbox" id="checkAllImport" class="form-check-input" checked onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>Shift</th>
                                    <th>Tipe</th>
                                    <th>No Lambung</th>
                                    <th>Nama di Excel</th>
                                    <th style="min-width: 280px; width: 300px;">Pilih Karyawan di Sistem (Select2 Realtime)</th>
                                    <th>Jam Kerja / Lembur</th>
                                    <th>HM Awal - Akhir</th>
                                    <th>Total HM</th>
                                    <th>HMC (Jam)</th>
                                    <th>Ritase</th>
                                    <th>Solar</th>
                                    <th>Insentif HM</th>
                                    <th>Uang Lembur</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small" id="selectedCountText">0 baris terpilih</div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="backToStep1()">Batal</button>
                            <button type="button" id="btnSaveBatch" class="btn btn-success px-4 shadow-sm" onclick="saveBatchImport()">
                                <i class="bi bi-check-circle-fill me-1"></i> Simpan ke Timesheets (<span id="btnSaveCount">0</span> Data)
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading Spinner State -->
                <div id="importLoading" class="text-center py-5" style="display: none;">
                    <div class="spinner-border text-success" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-3 fw-bold text-muted" id="importLoadingText">Memproses data dari Excel...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery & Select2 JS -->
<script src="assets/vendors/jquery/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
var globalParsedRows = [];

function clearPasteArea() {
    document.getElementById('rawExcelData').value = '';
    document.getElementById('rawExcelData').focus();
}

function backToStep1() {
    document.getElementById('importStep1').style.display = 'block';
    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importLoading').style.display = 'none';
}

function processExcelPreview() {
    var raw = document.getElementById('rawExcelData').value.trim();
    if (!raw) {
        alert('Silakan paste data dari Excel terlebih dahulu!');
        return;
    }

    document.getElementById('importStep1').style.display = 'none';
    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importLoading').style.display = 'block';
    document.getElementById('importLoadingText').innerText = 'Membedah data & mencocokkan master karyawan...';

    var formData = new FormData();
    formData.append('raw_data', raw);

    fetch('actions/?hal=employee_import-timesheets&action=preview', {
        method: 'POST',
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        document.getElementById('importLoading').style.display = 'none';
        if (data.status === 'error') {
            alert(data.message || 'Terjadi kesalahan saat memproses data.');
            backToStep1();
            return;
        }

        globalParsedRows = data.rows || [];
        renderPreviewTable(data);
    })
    .catch(function(err) {
        document.getElementById('importLoading').style.display = 'none';
        alert('Gagal berkomunikasi dengan server: ' + err.message);
        backToStep1();
    });
}

function renderPreviewTable(data) {
    document.getElementById('importStep2').style.display = 'block';

    var tbody = document.getElementById('previewTableBody');
    tbody.innerHTML = '';

    if (!data.rows || data.rows.length === 0) {
        tbody.innerHTML = '<tr><td colspan="17" class="text-center py-4 text-muted">Tidak ada data yang valid untuk ditampilkan.</td></tr>';
        updateSelectedSummary();
        return;
    }

    data.rows.forEach(function(row, idx) {
        var tr = document.createElement('tr');
        var isMatched = (row.employee_id && row.employee_id.trim() !== '');
        
        if (!isMatched) {
            tr.className = 'table-warning';
        }

        var waktuStr = '-';
        if (row.is_overtime) {
            waktuStr = (row.overtime_start || '-') + ' s/d ' + (row.overtime_end || '-');
        } else if (row.waktu_awal || row.waktu_akhir) {
            waktuStr = (row.waktu_awal || '-') + ' s/d ' + (row.waktu_akhir || '-');
        }

        var hmStr = '-';
        if (row.is_overtime) {
            hmStr = (row.hm_awal_lembur || 0) + ' - ' + (row.hm_akhir_lembur || 0);
        } else if (row.hm_awal || row.hm_akhir) {
            hmStr = row.hm_awal + ' - ' + row.hm_akhir;
        }

        var insentifStr = row.earned_hm_incentive ? 'Rp ' + Number(row.earned_hm_incentive).toLocaleString('id-ID') : '-';
        var lemburStr = row.overtime_amount ? 'Rp ' + Number(row.overtime_amount).toLocaleString('id-ID') : '-';

        // Build Select2 HTML with pre-selected option if matched
        var selectHtml = '<select class="form-select form-select-sm emp-select2" data-idx="' + idx + '" style="width: 100%;">';
        if (isMatched) {
            selectHtml += '<option value="' + escapeHtml(row.employee_id) + '" selected>' + escapeHtml(row.employee_name || 'Karyawan Terpilih') + '</option>';
        } else {
            selectHtml += '<option value=""></option>';
        }
        selectHtml += '</select>';

        tr.innerHTML = 
            '<td class="text-center"><input type="checkbox" class="form-check-input row-checkbox" data-idx="' + idx + '" checked onchange="updateSelectedSummary()"></td>' +
            '<td>' + (idx + 1) + '</td>' +
            '<td>' + escapeHtml(row.tanggal) + '</td>' +
            '<td><span class="badge ' + (row.shift === 'MALAM' ? 'bg-dark' : 'bg-info text-dark') + '">' + escapeHtml(row.shift) + '</span></td>' +
            '<td>' + (row.is_overtime ? '<span class="badge bg-warning text-dark">Lembur (2)</span>' : '<span class="badge bg-secondary">Pokok (1)</span>') + '</td>' +
            '<td class="fw-bold">' + escapeHtml(row.unit_id || '-') + '</td>' +
            '<td class="fw-semibold">' + escapeHtml(row.operator_raw) + '</td>' +
            '<td>' + selectHtml + '</td>' +
            '<td>' + waktuStr + '</td>' +
            '<td>' + hmStr + '</td>' +
            '<td>' + (row.total_hm ? row.total_hm.toFixed(2) : '0.00') + '</td>' +
            '<td class="fw-bold text-primary">' + (row.hmc ? row.hmc.toFixed(2) : '0.00') + '</td>' +
            '<td>' + (row.ritase || 0) + '</td>' +
            '<td>' + (row.solar ? row.solar.toFixed(2) : '0.00') + '</td>' +
            '<td class="text-end text-success fw-bold">' + insentifStr + '</td>' +
            '<td class="text-end text-primary fw-bold">' + lemburStr + '</td>' +
            '<td class="text-muted small">' + escapeHtml(row.keterangan || '-') + '</td>';

        tbody.appendChild(tr);
    });

    // Initialize Select2 with AJAX Realtime Search for all rows
    initSelect2ForPreview();

    updateSelectedSummary();
    applyPreviewFilters();
}

function initSelect2ForPreview() {
    $('#previewTableBody .emp-select2').each(function() {
        var $select = $(this);
        $select.select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#importExcelModal'),
            placeholder: '-- Cari / Pilih Karyawan --',
            allowClear: true,
            width: '100%',
            ajax: {
                url: 'actions/?hal=employee_import-timesheets&action=search_employees',
                dataType: 'json',
                delay: 200,
                data: function(params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: false // Realtime: always query fresh data from server
            }
        }).on('select2:select', function(e) {
            var idx = parseInt($(this).attr('data-idx'));
            var selectedData = e.params.data;
            onEmployeeSelectChange(idx, selectedData.id, selectedData.text, this);
        }).on('select2:clear', function(e) {
            var idx = parseInt($(this).attr('data-idx'));
            onEmployeeSelectChange(idx, '', '', this);
        });
    });
}

function onEmployeeSelectChange(idx, empId, empName, selectEl) {
    if (globalParsedRows[idx]) {
        globalParsedRows[idx].employee_id = empId || '';
        globalParsedRows[idx].employee_name = empName || '';
        
        var tr = selectEl.closest('tr');
        if (tr) {
            if (empId) {
                tr.classList.remove('table-warning', 'table-danger');
            } else {
                tr.classList.add('table-warning');
            }
        }
        updateSelectedSummary();
        applyPreviewFilters();
    }
}

function quickFilterStatus(status) {
    document.getElementById('filterStatus').value = status;
    applyPreviewFilters();
}

function applyPreviewFilters() {
    var search = (document.getElementById('filterSearchName').value || '').toLowerCase().trim();
    var status = document.getElementById('filterStatus').value;
    
    var tbody = document.getElementById('previewTableBody');
    var trs = tbody.querySelectorAll('tr');
    var visibleCount = 0;

    trs.forEach(function(tr) {
        var cb = tr.querySelector('.row-checkbox');
        if (!cb) return;
        var idx = parseInt(cb.getAttribute('data-idx'));
        var row = globalParsedRows[idx];
        if (!row) return;

        var isMatched = (row.employee_id && row.employee_id.trim() !== '');

        // Status match
        var statusMatch = true;
        if (status === 'MATCHED') {
            statusMatch = isMatched;
        } else if (status === 'UNMATCHED') {
            statusMatch = !isMatched;
        }

        // Text search match
        var textMatch = true;
        if (search) {
            var rawText = (
                (row.operator_raw || '') + ' ' + 
                (row.employee_name || '') + ' ' + 
                (row.unit_id || '') + ' ' + 
                (row.tanggal || '') + ' ' + 
                (row.shift || '') + ' ' + 
                (row.keterangan || '')
            ).toLowerCase();
            textMatch = (rawText.indexOf(search) !== -1);
        }

        if (statusMatch && textMatch) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    var countText = document.getElementById('filterResultCount');
    if (search || status !== 'ALL') {
        countText.innerText = 'Menampilkan ' + visibleCount + ' dari ' + globalParsedRows.length + ' baris';
    } else {
        countText.innerText = 'Menampilkan semua (' + globalParsedRows.length + ' baris)';
    }
}

function toggleSelectAll(masterCheckbox) {
    var checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(function(cb) {
        var tr = cb.closest('tr');
        if (tr && tr.style.display !== 'none') {
            cb.checked = masterCheckbox.checked;
        }
    });
    updateSelectedSummary();
}

function updateSelectedSummary() {
    var checkboxes = document.querySelectorAll('.row-checkbox:checked');
    var totalSelected = checkboxes.length;
    document.getElementById('btnSaveCount').innerText = totalSelected;
    document.getElementById('selectedCountText').innerText = totalSelected + ' dari ' + globalParsedRows.length + ' baris terpilih';

    var matchedCount = 0;
    var emptyCount = 0;

    globalParsedRows.forEach(function(r) {
        if (r.employee_id && r.employee_id.trim() !== '') {
            matchedCount++;
        } else {
            emptyCount++;
        }
    });

    document.getElementById('badgeTotalRows').innerText = globalParsedRows.length + ' Baris';
    document.getElementById('badgeMatchedRows').innerText = matchedCount + ' Karyawan Cocok';
    
    var badgeUnmatched = document.getElementById('badgeUnmatchedRows');
    var alertUnmatched = document.getElementById('unmatchedAlert');
    if (emptyCount > 0) {
        badgeUnmatched.style.display = 'inline-block';
        badgeUnmatched.innerText = emptyCount + ' Belum Cocok';
        alertUnmatched.style.display = 'flex';
    } else {
        badgeUnmatched.style.display = 'none';
        alertUnmatched.style.display = 'none';
    }
    
    var btnSave = document.getElementById('btnSaveBatch');
    btnSave.disabled = (totalSelected === 0);
}

function saveBatchImport() {
    var selectedCheckboxes = document.querySelectorAll('.row-checkbox:checked');
    if (selectedCheckboxes.length === 0) {
        alert('Pilih setidaknya satu baris data untuk disimpan.');
        return;
    }

    var itemsToSave = [];
    var missingEmpRows = [];

    selectedCheckboxes.forEach(function(cb) {
        var idx = parseInt(cb.getAttribute('data-idx'));
        var row = globalParsedRows[idx];
        if (row) {
            if (!row.employee_id || row.employee_id.trim() === '') {
                missingEmpRows.push('Baris ' + (idx + 1) + ': ' + (row.operator_raw || 'Tanpa Nama'));
            } else {
                itemsToSave.push(row);
            }
        }
    });

    if (missingEmpRows.length > 0) {
        alert('⚠️ Peringatan: Ada ' + missingEmpRows.length + ' baris data yang belum dipilih karyawan di sistem:\n\n' + 
            missingEmpRows.slice(0, 8).join('\n') + (missingEmpRows.length > 8 ? '\n...dan ' + (missingEmpRows.length - 8) + ' baris lainnya' : '') + 
            '\n\nSilakan pilih karyawan di dropdown terlebih dahulu (atau daftarkan dulu di menu Karyawan jika belum ada), atau hilangkan centang (uncheck) pada baris tersebut sebelum menyimpan!');
        return;
    }

    if (itemsToSave.length === 0) {
        alert('Tidak ada data valid yang dapat disimpan.');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menyimpan ' + itemsToSave.length + ' data timesheet ke database?')) {
        return;
    }

    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importLoading').style.display = 'block';
    document.getElementById('importLoadingText').innerText = 'Menyimpan ' + itemsToSave.length + ' data ke database...';

    fetch('actions/?hal=employee_import-timesheets&action=save_batch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ items: itemsToSave })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        document.getElementById('importLoading').style.display = 'none';
        if (data.status === 'success') {
            alert('Sukses! ' + data.message);
            window.location.reload();
        } else {
            alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
            document.getElementById('importStep2').style.display = 'block';
        }
    })
    .catch(function(err) {
        document.getElementById('importLoading').style.display = 'none';
        alert('Gagal menyimpan ke server: ' + err.message);
        document.getElementById('importStep2').style.display = 'block';
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>

