<?php
include 'functions/pagination.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
if (!in_array($limit, [10, 25, 50, 100])) {
    $limit = 25;
}
$bank_filter = isset($_GET['bank_filter']) ? sani($_GET['bank_filter']) : '';
$level_filter = isset($_GET['level_filter']) ? sani($_GET['level_filter']) : '';
$search = isset($_GET['search']) ? sani(trim($_GET['search'])) : '';

$whereClause = "";
if (!empty($search)) {
    $whereClause .= " AND (full_name LIKE '%$search%' OR position LIKE '%$search%' OR employee_id LIKE '%$search%' OR bank LIKE '%$search%' OR nomor_rekening LIKE '%$search%')";
}

if (!empty($bank_filter)) {
    if ($bank_filter === 'UNSET') {
        $whereClause .= " AND (bank IS NULL OR bank = '' OR bank = '-')";
    } else {
        $whereClause .= " AND bank = '$bank_filter'";
    }
}

if (!empty($level_filter)) {
    $whereClause .= " AND level = '$level_filter'";
}

$query = "SELECT * FROM employees WHERE 1=1 $whereClause ORDER BY full_name ASC";
$pagination = makePagination($con, $query, $limit);
?>

<!-- Datalist Bank Populer Indonesia -->
<datalist id="listBankIndonesia">
    <option value="BCA">Bank Central Asia (BCA)</option>
    <option value="Mandiri">Bank Mandiri</option>
    <option value="BRI">Bank Rakyat Indonesia (BRI)</option>
    <option value="BNI">Bank Negara Indonesia (BNI)</option>
    <option value="BSI">Bank Syariah Indonesia (BSI)</option>
    <option value="CIMB Niaga">Bank CIMB Niaga</option>
    <option value="Danamon">Bank Danamon</option>
    <option value="Permata">Bank Permata</option>
    <option value="BTN">Bank Tabungan Negara (BTN)</option>
    <option value="Mega">Bank Mega</option>
    <option value="BTPN / Jenius">Bank BTPN / Jenius</option>
    <option value="Bank Jago">Bank Jago</option>
    <option value="SeaBank">SeaBank</option>
    <option value="Panin">Bank Panin</option>
    <option value="OCBC NISP">Bank OCBC NISP</option>
    <option value="Sinarmas">Bank Sinarmas</option>
</datalist>

<!-- Alert Message -->
<?php if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?= $_SESSION['message_type'] ?> alert-dismissible fade show shadow-sm py-2 mb-2" role="alert" style="font-size: 13px;">
        <i class="bi <?= $_SESSION['message_type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-1"></i>
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
<?php endif; ?>

<!-- Header Section -->
<div class="page-heading">
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge text-primary me-2"></i>Data Karyawan (Employees)</h5>
            <small class="text-muted">Kelola master data karyawan, divisi / level (Mining / Hauling), jabatan, dan nomor rekening bank</small>
        </div>
        <button type="button" class="btn shadow-sm btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle me-1"></i> Tambah Karyawan
        </button>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card p-2 mb-2 shadow-sm bg-light border">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="employee_employees">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Cari nama, NIK, jabatan, bank..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select name="level_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Level --</option>
                            <option value="mining" <?= $level_filter === 'mining' ? 'selected' : '' ?>>⚙️ Mining</option>
                            <option value="hauling" <?= $level_filter === 'hauling' ? 'selected' : '' ?>>🚚 Hauling</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="bank_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Bank --</option>
                            <option value="UNSET" <?= $bank_filter === 'UNSET' ? 'selected' : '' ?>>⚠️ Belum di-set</option>
                            <option value="BCA" <?= $bank_filter === 'BCA' ? 'selected' : '' ?>>BCA</option>
                            <option value="Mandiri" <?= $bank_filter === 'Mandiri' ? 'selected' : '' ?>>Mandiri</option>
                            <option value="BRI" <?= $bank_filter === 'BRI' ? 'selected' : '' ?>>BRI</option>
                            <option value="BNI" <?= $bank_filter === 'BNI' ? 'selected' : '' ?>>BNI</option>
                            <option value="BSI" <?= $bank_filter === 'BSI' ? 'selected' : '' ?>>BSI</option>
                            <option value="CIMB Niaga" <?= $bank_filter === 'CIMB Niaga' ? 'selected' : '' ?>>CIMB Niaga</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="limit" class="form-select form-select-sm" onchange="this.form.submit()" title="Jumlah data per halaman">
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10 / hal</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25 / hal</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 / hal</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 / hal</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                        <?php if (!empty($search) || !empty($bank_filter) || !empty($level_filter)): ?>
                            <a href="?hal=employee_employees" class="btn btn-sm btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sticky Bulk Action Bar -->
        <div id="bulkActionBarEmp" class="card shadow-sm border-primary mb-2 bg-primary-subtle d-none" style="position: sticky; top: 10px; z-index: 1020; transition: all 0.3s ease;">
            <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary fs-6 px-3 py-2" id="bulkSelectedBadgeEmp">
                        <i class="bi bi-check2-square me-1"></i> <span id="selectedEmpCountText">0</span> Karyawan Terpilih
                    </span>
                    <span class="small text-muted d-none d-md-inline"><i class="bi bi-info-circle me-1"></i>Tip: Tahan tombol <b>Shift</b> lalu klik checkbox lain untuk memilih banyak sekaligus.</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary bg-white" onclick="clearAllEmpSelections()">
                        <i class="bi bi-x-circle me-1"></i> Batal Pilihan
                    </button>
                    <button type="button" class="btn btn-sm btn-warning px-3 shadow-sm text-dark" onclick="openBulkLevelEmpModal()">
                        <i class="bi bi-tag-fill me-1"></i> <b>Set Level Masal</b>
                    </button>
                    <button type="button" class="btn btn-sm btn-success px-3 shadow-sm" onclick="openBulkBankEmpModal()">
                        <i class="bi bi-bank2 me-1"></i> <b>Set Bank Masal</b>
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card p-2 mb-1 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped align-middle" id="tableEmployees" style="font-size: 12.5px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">
                                <input type="checkbox" class="form-check-input cursor-pointer" id="checkAllEmployees" title="Pilih Semua di Halaman Ini" onchange="toggleSelectAllEmployees(this)">
                            </th>
                            <th width="50">No</th>
                            <th>NIK / ID</th>
                            <th>Nama Lengkap</th>
                            <th>Level / Divisi</th>
                            <th>Jabatan (Position)</th>
                            <th>Bank & No. Rekening</th>
                            <th>Tanggal Bergabung</th>
                            <th class="text-center" width="90">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $pagination['from'];
                        if (empty($pagination['data'])):
                        ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Tidak ada data karyawan yang ditemukan.</td>
                            </tr>
                        <?php
                        else:
                            foreach ($pagination['data'] as $row): 
                                $empBank = trim($row['bank'] ?? '');
                                $empRek = trim($row['nomor_rekening'] ?? '');
                                $empLevel = strtolower(trim($row['level'] ?? 'mining'));
                        ?>
                            <tr id="row-emp-<?= $row['id'] ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input emp-checkbox cursor-pointer" value="<?= htmlspecialchars($row['id']) ?>" data-name="<?= htmlspecialchars($row['full_name']) ?>" data-bank="<?= htmlspecialchars($empBank) ?>" data-rek="<?= htmlspecialchars($empRek) ?>" onchange="onEmpCheckboxChange()">
                                </td>
                                <td><?= $no++ ?></td>
                                <td><code><?= htmlspecialchars($row['employee_id'] ?? '-') ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td>
                                    <?php if ($empLevel === 'hauling'): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-truck me-1"></i>Hauling</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-gear me-1"></i>Mining</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($row['position']) ?></span></td>
                                <td>
                                    <?php if (!empty($empBank) && $empBank !== '-'): ?>
                                        <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">
                                            <i class="bi bi-bank me-1"></i><?= htmlspecialchars($empBank) ?>
                                        </span>
                                        <?php if (!empty($empRek)): ?>
                                            <code class="ms-1 text-dark fw-bold"><?= htmlspecialchars($empRek) ?></code>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1">
                                            <i class="bi bi-dash-circle me-1"></i>Belum di-set
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($row['join_date']) ? date('d-m-Y', strtotime($row['join_date'])) : '-' ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning shadow-sm py-0 px-2" onclick="upData(
                                        '<?= htmlspecialchars($row['id']) ?>',
                                        '<?= htmlspecialchars(addslashes($row['full_name'])) ?>',
                                        '<?= htmlspecialchars(addslashes($row['position'])) ?>',
                                        '<?= htmlspecialchars(addslashes($empLevel)) ?>',
                                        '<?= htmlspecialchars(addslashes($row['join_date'] ?? '')) ?>',
                                        '<?= htmlspecialchars(addslashes($row['employee_id'] ?? '')) ?>',
                                        '<?= htmlspecialchars(addslashes($empBank)) ?>',
                                        '<?= htmlspecialchars(addslashes($empRek)) ?>'
                                    )" title="Edit Karyawan">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=employee_employees&delete=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-danger shadow-sm py-0 px-2" onclick="return confirm('Apakah yakin ingin menghapus karyawan <?= htmlspecialchars(addslashes($row['full_name'])) ?>?')" title="Hapus Karyawan">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="mt-2">
                <?= showPagination($pagination['total_pages'], $pagination['current_page']); ?>
            </div>
        </div>
    </section>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="POST">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="full_name" placeholder="Nama Lengkap Karyawan" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Level / Divisi <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="level" required>
                                <option value="mining">⚙️ Mining (Alat Berat / Mining)</option>
                                <option value="hauling">🚚 Hauling (Dump Truck / Hauling)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jabatan / Posisi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="position" placeholder="Contoh: Driver DT, OP Exca..." required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">NIK / ID Karyawan</label>
                        <input type="text" class="form-control form-control-sm" name="employee_id" placeholder="Kosongkan untuk generate otomatis">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bank yang Digunakan</label>
                            <input type="text" class="form-control form-control-sm" name="bank" list="listBankIndonesia" placeholder="Contoh: BCA, Mandiri, BRI...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor Rekening</label>
                            <input type="text" class="form-control form-control-sm" name="nomor_rekening" placeholder="Contoh: 1234567890">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Tanggal Bergabung (Join Date)</label>
                        <input type="date" class="form-control form-control-sm" name="join_date">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="addData" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-pencil-square me-2"></i>Edit Data Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Level / Divisi <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="level" id="edit_level" required>
                                <option value="mining">⚙️ Mining</option>
                                <option value="hauling">🚚 Hauling</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Jabatan / Posisi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="position" id="edit_position" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Bank yang Digunakan</label>
                            <input type="text" class="form-control form-control-sm" name="bank" id="edit_bank" list="listBankIndonesia" placeholder="Pilih atau ketik nama bank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nomor Rekening</label>
                            <input type="text" class="form-control form-control-sm" name="nomor_rekening" id="edit_nomor_rekening" placeholder="Nomor Rekening">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Tanggal Bergabung (Join Date)</label>
                        <input type="date" class="form-control form-control-sm" name="join_date" id="edit_join_date">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="updateData" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bulk Update Level Karyawan -->
<div class="modal fade" id="modalBulkLevelEmp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalBulkLevelEmpLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fs-6" id="modalBulkLevelEmpLabel">
                    <i class="bi bi-tag-fill me-2"></i>Set Level Masal (Mining / Hauling)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="post" id="bulkLevelEmpForm">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3 shadow-sm" style="font-size: 13px;">
                        <i class="bi bi-info-circle-fill me-1"></i> Anda akan mengubah Level / Divisi secara masal untuk <b id="bulkModalLevelEmpCount">0</b> karyawan terpilih.
                    </div>

                    <!-- Container Hidden Inputs Employee IDs -->
                    <div id="bulkLevelEmpIdsContainer"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih Level / Divisi Baru <span class="text-danger">*</span></label>
                        <select name="level" class="form-select" required>
                            <option value="mining">⚙️ Mining (Operasional Alat Berat & Pit)</option>
                            <option value="hauling">🚚 Hauling (Operasional DT & Pengangkutan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="bulkUpdateLevel" class="btn btn-sm btn-warning text-dark px-3"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan Level</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Bulk Update Bank Karyawan -->
<div class="modal fade" id="modalBulkBankEmp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalBulkBankEmpLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fs-6" id="modalBulkBankEmpLabel">
                    <i class="bi bi-bank2 me-2"></i>Set Bank Masal untuk Karyawan Terpilih
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="actions/?hal=employee_employees" method="post" id="bulkBankEmpForm">
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3 shadow-sm" style="font-size: 13px;">
                        <i class="bi bi-info-circle-fill me-1"></i> Anda akan mengatur bank secara masal untuk <b id="bulkModalEmpCount">0</b> karyawan yang dipilih.
                    </div>

                    <!-- Container Hidden Inputs Employee IDs -->
                    <div id="bulkEmpIdsContainer"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Pilih / Ketik Nama Bank <span class="text-danger">*</span></label>
                        <input type="text" name="bank" id="bulk_emp_bank_input" list="listBankIndonesia" class="form-control" placeholder="Contoh: BCA, Mandiri, BRI, BNI, BSI..." required autocomplete="off">
                        <div class="form-text small">Pilih dari rekomendasi bank atau ketik nama bank baru.</div>
                    </div>

                    <!-- Quick Bank Buttons -->
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">Pilihan Cepat Bank:</label>
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('BCA')">BCA</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('Mandiri')">Mandiri</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('BRI')">BRI</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('BNI')">BNI</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('BSI')">BSI</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('CIMB Niaga')">CIMB Niaga</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('Danamon')">Danamon</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('Permata')">Permata</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('Bank Jago')">Bank Jago</button>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBankEmp('SeaBank')">SeaBank</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Nomor Rekening (Opsional)</label>
                        <input type="text" name="nomor_rekening" class="form-control form-control-sm" placeholder="Kosongkan jika nomor rekening tiap karyawan berbeda">
                        <div class="form-text small text-muted">Isi hanya jika semua karyawan yang dipilih menggunakan nomor rekening/referensi yang sama.</div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="bulkUpdateBank" class="btn btn-sm btn-success px-3"><i class="bi bi-check2-circle me-1"></i>Simpan Perubahan Bank</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function upData(id, full_name, position, level, join_date, employee_id, bank, nomor_rekening) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_full_name').value = full_name;
    document.getElementById('edit_position').value = position;
    if (document.getElementById('edit_level')) {
        document.getElementById('edit_level').value = level ? level.toLowerCase() : 'mining';
    }
    document.getElementById('edit_join_date').value = join_date;
    document.getElementById('edit_bank').value = bank ? bank : '';
    document.getElementById('edit_nomor_rekening').value = nomor_rekening ? nomor_rekening : '';
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

function setQuickBankEmp(bankName) {
    document.getElementById('bulk_emp_bank_input').value = bankName;
}

function openBulkLevelEmpModal() {
    const checked = document.querySelectorAll('.emp-checkbox:checked');
    if (checked.length === 0) {
        alert('Silakan pilih minimal 1 karyawan terlebih dahulu.');
        return;
    }

    const container = document.getElementById('bulkLevelEmpIdsContainer');
    container.innerHTML = '';

    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'employee_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkModalLevelEmpCount').innerText = checked.length;

    const modal = new bootstrap.Modal(document.getElementById('modalBulkLevelEmp'));
    modal.show();
}

// Shift + Click Checkbox Selection Logic for Employees
let lastCheckedEmpBox = null;

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.emp-checkbox');

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('click', function(e) {
            if (!lastCheckedEmpBox) {
                lastCheckedEmpBox = this;
                onEmpCheckboxChange();
                return;
            }

            // If Shift key is pressed during click
            if (e.shiftKey) {
                const boxesArray = Array.from(document.querySelectorAll('.emp-checkbox'));
                const start = boxesArray.indexOf(this);
                const end = boxesArray.indexOf(lastCheckedEmpBox);

                const [min, max] = [Math.min(start, end), Math.max(start, end)];

                for (let i = min; i <= max; i++) {
                    boxesArray[i].checked = lastCheckedEmpBox.checked;
                }
            }

            lastCheckedEmpBox = this;
            onEmpCheckboxChange();
        });
    });
});

function toggleSelectAllEmployees(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
    onEmpCheckboxChange();
}

function onEmpCheckboxChange() {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    const checked = document.querySelectorAll('.emp-checkbox:checked');
    const masterCheckbox = document.getElementById('checkAllEmployees');
    const bulkBar = document.getElementById('bulkActionBarEmp');
    const selectedCountText = document.getElementById('selectedEmpCountText');

    if (masterCheckbox) {
        masterCheckbox.checked = (checkboxes.length > 0 && checked.length === checkboxes.length);
        masterCheckbox.indeterminate = (checked.length > 0 && checked.length < checkboxes.length);
    }

    // Highlight selected rows
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row) {
            if (cb.checked) {
                row.classList.add('table-primary');
            } else {
                row.classList.remove('table-primary');
            }
        }
    });

    if (checked.length > 0) {
        bulkBar.classList.remove('d-none');
        selectedCountText.innerText = checked.length;
    } else {
        bulkBar.classList.add('d-none');
        selectedCountText.innerText = '0';
    }
}

function clearAllEmpSelections() {
    const checkboxes = document.querySelectorAll('.emp-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const masterCheckbox = document.getElementById('checkAllEmployees');
    if (masterCheckbox) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
    }
    onEmpCheckboxChange();
}

function openBulkBankEmpModal() {
    const checked = document.querySelectorAll('.emp-checkbox:checked');
    if (checked.length === 0) {
        alert('Silakan pilih minimal 1 karyawan terlebih dahulu.');
        return;
    }

    const container = document.getElementById('bulkEmpIdsContainer');
    container.innerHTML = '';

    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'employee_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkModalEmpCount').innerText = checked.length;
    document.getElementById('bulk_emp_bank_input').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalBulkBankEmp'));
    modal.show();
}
</script>
