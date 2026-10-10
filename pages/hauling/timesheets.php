<?php
include 'functions/pagination.php';
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$whereClause = '';
$user_id_filter = '';

$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-d');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-d');

$dateFilter = " t.tanggal >= '$start_date' AND t.tanggal <= '$end_date'";

if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $uid = sani($_GET['user_id']);
    $user_id_filter = $uid;
    $whereClause = "WHERE (t.employee_id = '$uid' OR t.substitute_employee_id = '$uid') AND $dateFilter";
} else {
    $whereClause = "WHERE $dateFilter";
}

if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR sub.full_name LIKE '%$search%' OR t.unit_id LIKE '%$search%')";
}

// Rates from settings
$rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
$rateRow = mysqli_fetch_assoc($rateQuery);
$tarif_hm = isset($rateRow['setting_value']) ? (float) $rateRow['setting_value'] : 17000;

$rateOtQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
$rateOtRow = mysqli_fetch_assoc($rateOtQuery);
$tarif_lembur = isset($rateOtRow['setting_value']) ? (float) $rateOtRow['setting_value'] : 19509;

$tonS1Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s1'");
$tonS1Row = mysqli_fetch_assoc($tonS1Q);
$tarif_tonase_s1 = isset($tonS1Row['setting_value']) ? (float) $tonS1Row['setting_value'] : 3000;

$tonS2Q = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_tonase_s2'");
$tonS2Row = mysqli_fetch_assoc($tonS2Q);
$tarif_tonase_s2 = isset($tonS2Row['setting_value']) ? (float) $tonS2Row['setting_value'] : 3500;

if (!function_exists('calcEffectiveOtHours')) {
    function calcEffectiveOtHours($otHours) {
        $ot = (float) $otHours;
        if ($ot <= 0) return 0.0;
        if ($ot <= 1) return $ot * 1.5;
        return 1.5 + ($ot - 1) * 2.0;
    }
}

// Summary Metrics for the current filter
$summaryQuery = "SELECT 
                    COUNT(t.id) as total_ritase,
                    SUM(t.tonase) as total_tonase,
                    SUM(t.total_km) as total_km,
                    SUM(t.earned_tonase_incentive) as total_insentif_tonase,
                    SUM(t.earned_hm_incentive) as total_insentif_hm,
                    SUM(
                        (CASE 
                            WHEN t.ot_hours <= 0 THEN 0 
                            WHEN t.ot_hours <= 1 THEN t.ot_hours * 1.5 
                            ELSE 1.5 + (t.ot_hours - 1) * 2 
                        END) * $tarif_lembur
                    ) as total_overtime
                 FROM hauling_timesheets t
                 LEFT JOIN employees e ON t.employee_id = e.id
                 LEFT JOIN employees sub ON t.substitute_employee_id = sub.id
                 $whereClause";
$summaryRes = mysqli_query($con, $summaryQuery);
$summaryData = mysqli_fetch_assoc($summaryRes) ?: [];

$query = "SELECT t.*, e.full_name as driver_name, e.employee_id as driver_nik, sub.full_name as substitute_name
          FROM hauling_timesheets t 
          LEFT JOIN employees e ON t.employee_id = e.id 
          LEFT JOIN employees sub ON t.substitute_employee_id = sub.id
          $whereClause 
          ORDER BY t.tanggal DESC, t.ritase_ke ASC, t.created_at DESC";

$pagination = makePagination($con, $query, 15);
?>

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
            <h5 class="mb-0 fw-bold"><i class="bi bi-truck text-primary me-2"></i>Timesheets Hauling (Dump Truck)</h5>
            <small class="text-muted">Kelola ritase harian, tonase muatan, jam operasional, dan insentif driver hauling</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn shadow-sm btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import / Paste Excel
            </button>
            <button type="button" class="btn shadow-sm btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Data Hauling
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards (Mini Dashboard) -->
    <div class="row g-2 mb-2">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 44px; height: 44px; min-width: 44px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff;">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Ritase</div>
                        <h5 class="mb-0 fw-bold text-dark"><?= number_format($summaryData['total_ritase'] ?? 0) ?> <span class="small fw-normal text-muted" style="font-size: 12px;">Rit</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 44px; height: 44px; min-width: 44px; background: linear-gradient(135deg, #10b981, #047857); color: #ffffff;">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Tonase</div>
                        <h5 class="mb-0 fw-bold text-dark"><?= number_format($summaryData['total_tonase'] ?? 0, 2, ',', '.') ?> <span class="small fw-normal text-muted" style="font-size: 12px;">Ton</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 44px; height: 44px; min-width: 44px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Insentif Tonase</div>
                        <h5 class="mb-0 fw-bold" style="color: #b45309 !important;">Rp <?= number_format($summaryData['total_insentif_tonase'] ?? 0, 0, ',', '.') ?></h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 44px; height: 44px; min-width: 44px; background: linear-gradient(135deg, #06b6d4, #0e7490); color: #ffffff;">
                        <i class="bi bi-speedometer2 fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Jarak / KM</div>
                        <h5 class="mb-0 fw-bold text-dark"><?= number_format($summaryData['total_km'] ?? 0, 1, ',', '.') ?> <span class="small fw-normal text-muted" style="font-size: 12px;">KM</span></h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <section class="section">
        <!-- Search & Filter Form -->
        <div class="card p-2 mb-2 shadow-sm bg-light border">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="hauling_timesheets">
                <div class="row g-2 align-items-center">
                    <?php if (isset($_GET['iframe'])): ?>
                        <input type="hidden" name="iframe" value="1">
                    <?php endif; ?>
                    <?php if (isset($_GET['user_id'])): ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET['user_id']) ?>">
                    <?php endif; ?>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">Mulai</span>
                            <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required title="Tanggal Mulai">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">Sampai</span>
                            <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required title="Tanggal Akhir">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="Cari nama driver, no lambung DT..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-sm btn-primary w-50" title="Cari Data"><i class="bi bi-filter"></i> Filter</button>
                        <a href="actions/pages/hauling/export-timesheets.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&user_id=<?= urlencode($user_id_filter) ?>" class="btn btn-sm btn-success w-50" title="Export Excel / CSV"><i class="bi bi-file-earmark-excel"></i> Excel</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="card p-2 mb-1 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped align-middle mb-0" style="font-size: 11.5px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th width="35">No</th>
                            <th>Tanggal</th>
                            <th>Driver (Utama)</th>
                            <th>Pengganti</th>
                            <th>No Lambung</th>
                            <th class="text-center">Ritase</th>
                            <th class="text-center">Shift</th>
                            <th class="text-end">Tonase (Ton)</th>
                            <th>KM (Awal / Akhir / Tot)</th>
                            <th>Jam Operasi</th>
                            <th class="text-center">Cuci / Safety</th>
                            <th class="text-end" style="background-color: #fef3c7 !important; color: #92400e !important;">Nominal Tonase</th>
                            <th class="text-end">Nominal HM 1</th>
                            <th class="text-end">Nominal HM 2</th>
                            <th class="text-center">Jam OT</th>
                            <th class="text-end" style="background-color: #eff6ff !important; color: #1e3a8a !important;">Uang Lembur (OT)</th>
                            <th class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">Total Insentif HM</th>
                            <th>Keterangan</th>
                            <th class="text-center" width="70">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $pagination['from'];
                        if (empty($pagination['data'])):
                        ?>
                            <tr>
                                <td colspan="19" class="text-center py-4 text-muted">Belum ada data timesheet hauling untuk periode ini.</td>
                            </tr>
                        <?php
                        else:
                            foreach ($pagination['data'] as $row): 
                                $isShift2 = ($row['shift_type'] === '2' || $row['ritase_ke'] >= 2);

                                $hmS1 = (float) $row['hm_s1'];
                                $nomHm1 = $hmS1 * $tarif_hm;
                                $nomHm2 = 0;

                                $otHours = (float) $row['ot_hours'];
                                $effOtHours = calcEffectiveOtHours($otHours);
                                $nomOt = round($effOtHours * $tarif_lembur, 2);
                                $totalHmRow = $nomHm1 + $nomHm2 + $nomOt;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td class="fw-bold">
                                    <?= htmlspecialchars($row['driver_name']) ?>
                                    <?php if (!empty($row['driver_nik'])): ?>
                                        <small class="text-muted d-block" style="font-size: 10px;"><?= htmlspecialchars($row['driver_nik']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['substitute_name'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['substitute_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-dark"><?= htmlspecialchars($row['unit_id'] ?: '-') ?></span></td>
                                <td class="text-center fw-bold">Rit <?= $row['ritase_ke'] ?></td>
                                <td class="text-center">
                                    <?php if ($isShift2): ?>
                                        <span class="badge bg-warning text-dark">Shift 2 (OT)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">Shift 1 (Pokok)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold text-success"><?= number_format($row['tonase'], 2, ',', '.') ?></td>
                                <td>
                                    <small><?= number_format($row['km_awal'], 0, ',', '.') ?> → <?= number_format($row['km_akhir'], 0, ',', '.') ?></small>
                                    <span class="badge bg-light text-dark border ms-1"><?= number_format($row['total_km'], 0, ',', '.') ?> KM</span>
                                </td>
                                <td>
                                    <?= $row['jam_mulai'] ? date('H:i', strtotime($row['jam_mulai'])) : '-' ?> → 
                                    <?= $row['jam_tiba_site'] ? date('H:i', strtotime($row['jam_tiba_site'])) : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?= $row['cuci'] ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Cuci ✓</span>' : '' ?>
                                    <?= $row['safety'] ? '<span class="badge bg-info-subtle text-info border border-info-subtle">Safety ✓</span>' : '' ?>
                                    <?= (!$row['cuci'] && !$row['safety']) ? '<span class="text-muted">-</span>' : '' ?>
                                </td>
                                <td class="text-end fw-bold" style="background-color: #fffbeb !important; color: #92400e !important;">
                                    Rp <?= number_format($row['earned_tonase_incentive'], 0, ',', '.') ?>
                                    <div class="text-muted small" style="font-size: 9.5px;">@Rp <?= number_format($row['tonase_rate'], 0, ',', '.') ?></div>
                                </td>
                                <td class="text-end">
                                    <?php if ($nomHm1 > 0): ?>
                                        Rp <?= number_format($nomHm1, 0, ',', '.') ?>
                                        <div class="text-muted small" style="font-size: 9.5px;"><?= number_format($hmS1, 1) ?>H @17k</div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="text-muted">-</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($otHours > 0): ?>
                                        <span class="badge bg-light text-dark border" title="<?= $otHours ?> Jam Real = <?= $effOtHours ?> Jam Efektif"><?= number_format($otHours, 1, ',', '.') ?>H <small class="text-muted">(<?= number_format($effOtHours, 1, ',', '.') ?>J)</small></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">
                                    <?php if ($nomOt > 0): ?>
                                        Rp <?= number_format($nomOt, 0, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="text-muted">Rp 0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">
                                    Rp <?= number_format($totalHmRow, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['keterangan'])): ?>
                                        <span class="text-truncate d-inline-block text-muted" style="max-width: 140px;" title="<?= htmlspecialchars($row['keterangan']) ?>">
                                            <?= htmlspecialchars($row['keterangan']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-warning shadow-sm py-0 px-2" onclick='upData(<?= json_encode($row) ?>)' title="Edit Data">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=hauling_timesheets&delete=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-outline-danger shadow-sm py-0 px-2" onclick="return confirm('Apakah yakin ingin menghapus baris timesheet ini?')" title="Hapus Data">
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-plus-circle-fill me-2"></i>Tambah Data Timesheet Hauling</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=hauling_timesheets" method="POST">
                <div class="modal-body p-3">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Driver Utama <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="employee_id" required>
                                <option value="">-- Pilih Driver --</option>
                                <?php
                                $driverRes = mysqli_query($con, "SELECT id, full_name, employee_id, level FROM employees ORDER BY (level='hauling') DESC, full_name ASC");
                                while ($d = mysqli_fetch_assoc($driverRes)) {
                                    $lbl = ($d['level'] === 'hauling' ? '[Hauling] ' : '[Mining] ') . $d['full_name'] . (!empty($d['employee_id']) ? " ({$d['employee_id']})" : '');
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($lbl) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Driver Pengganti (Opsional)</label>
                            <select class="form-select form-select-sm" name="substitute_employee_id">
                                <option value="">-- Tidak Ada / Sendiri --</option>
                                <?php
                                mysqli_data_seek($driverRes, 0);
                                while ($d = mysqli_fetch_assoc($driverRes)) {
                                    $lbl = ($d['level'] === 'hauling' ? '[Hauling] ' : '[Mining] ') . $d['full_name'] . (!empty($d['employee_id']) ? " ({$d['employee_id']})" : '');
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($lbl) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">No Lambung DT <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="unit_id" placeholder="Contoh: DT-1246" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ritase ke- <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="ritase_ke" id="add_ritase_ke" onchange="autoSetShiftType(this, 'add_shift_type')">
                                <option value="1">1 (Ritase Pertama / Shift 1)</option>
                                <option value="2">2 (Ritase Kedua / Shift 2 / OT)</option>
                                <option value="3">3 (Ritase Ketiga / OT)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tipe Shift</label>
                            <select class="form-select form-select-sm" name="shift_type" id="add_shift_type">
                                <option value="1">Shift 1 (Pokok @Rp 3.000/Ton)</option>
                                <option value="2">Shift 2 / OTW (OT @Rp 3.500/Ton)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-success">Tonase (Ton) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="tonase" placeholder="Contoh: 53.17" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">KM Awal</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="km_awal" id="add_km_awal" placeholder="KM Awal" oninput="calcKm('add')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">KM Akhir</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="km_akhir" id="add_km_akhir" placeholder="KM Akhir" oninput="calcKm('add')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Total KM</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="total_km" id="add_total_km" placeholder="Total KM">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-2">
                            <label class="form-label small">Jam Mulai</label>
                            <input type="time" class="form-control form-control-sm" name="jam_mulai">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Jam Loading</label>
                            <input type="time" class="form-control form-control-sm" name="jam_loading">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Timbang Awal</label>
                            <input type="time" class="form-control form-control-sm" name="jam_timbang_awal">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Jam Bongkar</label>
                            <input type="time" class="form-control form-control-sm" name="jam_bongkar">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Timbang Akhir</label>
                            <input type="time" class="form-control form-control-sm" name="jam_timbang_akhir">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tiba Site</label>
                            <input type="time" class="form-control form-control-sm" name="jam_tiba_site">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small">Rest Time</label>
                            <input type="time" class="form-control form-control-sm" name="rest_time" value="12:00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">P5M Time</label>
                            <input type="time" class="form-control form-control-sm" name="p5m_time" value="07:00">
                        </div>
                        <div class="col-md-3 d-flex align-items-center gap-3 pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cuci" id="add_cuci" checked value="1">
                                <label class="form-check-label small fw-semibold" for="add_cuci">Cuci Unit</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="safety" id="add_safety" checked value="1">
                                <label class="form-check-label small fw-semibold" for="add_safety">Safety Check</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row g-1">
                                <div class="col-6">
                                    <label class="form-label small">HM S1</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" name="hm_s1" value="7.00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">OT (Jam)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" name="ot_hours" value="2.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Keterangan / Catatan Kendala</label>
                        <textarea class="form-control form-control-sm" name="keterangan" rows="2" placeholder="Contoh: Unit low power, bocor tyre, antri loading..."></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="addData" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-warning text-dark py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-pencil-square me-2"></i>Edit Data Timesheet Hauling</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=hauling_timesheets" method="POST">
                <div class="modal-body p-3">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Driver Utama <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="employee_id" id="edit_employee_id" required>
                                <option value="">-- Pilih Driver --</option>
                                <?php
                                mysqli_data_seek($driverRes, 0);
                                while ($d = mysqli_fetch_assoc($driverRes)) {
                                    $lbl = ($d['level'] === 'hauling' ? '[Hauling] ' : '[Mining] ') . $d['full_name'] . (!empty($d['employee_id']) ? " ({$d['employee_id']})" : '');
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($lbl) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Driver Pengganti (Opsional)</label>
                            <select class="form-select form-select-sm" name="substitute_employee_id" id="edit_substitute_employee_id">
                                <option value="">-- Tidak Ada / Sendiri --</option>
                                <?php
                                mysqli_data_seek($driverRes, 0);
                                while ($d = mysqli_fetch_assoc($driverRes)) {
                                    $lbl = ($d['level'] === 'hauling' ? '[Hauling] ' : '[Mining] ') . $d['full_name'] . (!empty($d['employee_id']) ? " ({$d['employee_id']})" : '');
                                    echo "<option value='{$d['id']}'>" . htmlspecialchars($lbl) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="tanggal" id="edit_tanggal" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">No Lambung DT <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="unit_id" id="edit_unit_id" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Ritase ke- <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="ritase_ke" id="edit_ritase_ke" onchange="autoSetShiftType(this, 'edit_shift_type')">
                                <option value="1">1 (Ritase Pertama / Shift 1)</option>
                                <option value="2">2 (Ritase Kedua / Shift 2 / OT)</option>
                                <option value="3">3 (Ritase Ketiga / OT)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tipe Shift</label>
                            <select class="form-select form-select-sm" name="shift_type" id="edit_shift_type">
                                <option value="1">Shift 1 (Pokok @Rp 3.000/Ton)</option>
                                <option value="2">Shift 2 / OTW (OT @Rp 3.500/Ton)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-2 bg-light p-2 rounded border">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-success">Tonase (Ton) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="tonase" id="edit_tonase" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">KM Awal</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="km_awal" id="edit_km_awal" oninput="calcKm('edit')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">KM Akhir</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="km_akhir" id="edit_km_akhir" oninput="calcKm('edit')">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Total KM</label>
                            <input type="number" step="0.01" class="form-control form-control-sm" name="total_km" id="edit_total_km">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-2">
                            <label class="form-label small">Jam Mulai</label>
                            <input type="time" class="form-control form-control-sm" name="jam_mulai" id="edit_jam_mulai">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Jam Loading</label>
                            <input type="time" class="form-control form-control-sm" name="jam_loading" id="edit_jam_loading">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Timbang Awal</label>
                            <input type="time" class="form-control form-control-sm" name="jam_timbang_awal" id="edit_jam_timbang_awal">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Jam Bongkar</label>
                            <input type="time" class="form-control form-control-sm" name="jam_bongkar" id="edit_jam_bongkar">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Timbang Akhir</label>
                            <input type="time" class="form-control form-control-sm" name="jam_timbang_akhir" id="edit_jam_timbang_akhir">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tiba Site</label>
                            <input type="time" class="form-control form-control-sm" name="jam_tiba_site" id="edit_jam_tiba_site">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small">Rest Time</label>
                            <input type="time" class="form-control form-control-sm" name="rest_time" id="edit_rest_time">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">P5M Time</label>
                            <input type="time" class="form-control form-control-sm" name="p5m_time" id="edit_p5m_time">
                        </div>
                        <div class="col-md-3 d-flex align-items-center gap-3 pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cuci" id="edit_cuci" value="1">
                                <label class="form-check-label small fw-semibold" for="edit_cuci">Cuci Unit</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="safety" id="edit_safety" value="1">
                                <label class="form-check-label small fw-semibold" for="edit_safety">Safety Check</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="row g-1">
                                <div class="col-6">
                                    <label class="form-label small">HM S1</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" name="hm_s1" id="edit_hm_s1">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">OT (Jam)</label>
                                    <input type="number" step="0.01" class="form-control form-control-sm" name="ot_hours" id="edit_ot_hours">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Keterangan / Catatan Kendala</label>
                        <textarea class="form-control form-control-sm" name="keterangan" id="edit_keterangan" rows="2"></textarea>
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

<!-- Select2 CSS & Theme -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<style>
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 96vw;">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fs-6" id="importExcelModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i> Import / Paste Data Excel Timesheets Hauling
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- Step 1: Input Box -->
                <div id="importStep1">
                    <div class="alert alert-light border border-success border-opacity-25 py-2 mb-3 shadow-sm" style="font-size: 13px;">
                        <div class="fw-bold text-success mb-1"><i class="bi bi-info-circle-fill me-1"></i> Panduan Copy-Paste dari Spreadsheet Timesheet Hauling:</div>
                        <ol class="mb-1 ps-3">
                            <li>Buka Excel / Spreadsheet laporan hauling lapangan (format 25 kolom: Tanggal, Driver, Pengganti, No Lambung, Ritase ke-, Tonase, KM Awal, KM Akhir, Total KM, Jam Mulai s/d Jam Tiba Site, Cuci, Safety, HM S1, OT, Keterangan).</li>
                            <li>Blok baris data yang ingin diinput, tekan <b>Ctrl + C</b> (Salin). <i>(Baris judul/header boleh ikut ter-copy, sistem otomatis mengabaikannya)</i>.</li>
                            <li>Klik pada kotak teks di bawah lalu tekan <b>Ctrl + V</b> (Tempel / Paste).</li>
                            <li>Klik tombol <b>"Proses & Preview Data"</b> untuk melihat hasil pencocokan driver dan perhitungan tonase / HM otomatis.</li>
                        </ol>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Paste Data Excel Hauling Di Sini:</label>
                        <textarea id="rawExcelData" class="form-control font-monospace" rows="11" style="font-size: 12px; white-space: pre;" placeholder="Paste data dari Excel di sini (contoh: 01/10/2026	MUHLIS		DT-1246	1	53,17	868	1011	143	07.00	09.21	15.03	5,82	15.29	15.30	0,27	18.27	2,97	12.00	07.00	✓	✓	7	2	...)"></textarea>
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
                            <span class="badge bg-success fs-6 cursor-pointer" id="badgeMatchedRows" onclick="quickFilterStatus('MATCHED')" title="Klik untuk filter Driver Cocok" style="cursor: pointer;">0 Driver Cocok</span>
                            <span class="badge bg-danger fs-6 cursor-pointer" id="badgeUnmatchedRows" onclick="quickFilterStatus('UNMATCHED')" title="Klik untuk filter Belum Cocok" style="cursor: pointer; display:none;">0 Belum Cocok</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="?hal=employee_employees" target="_blank" class="btn btn-sm btn-outline-primary" title="Buka master karyawan di tab baru">
                                <i class="bi bi-person-plus me-1"></i> Master Karyawan (Tab Baru)
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
                                    <input type="text" id="filterSearchName" class="form-control" placeholder="Cari nama driver, no lambung, tanggal..." onkeyup="applyPreviewFilters()">
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
                                        <option value="MATCHED">✅ Driver Cocok</option>
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
                            <b>Pemberitahuan:</b> Ada nama driver yang belum cocok di master karyawan. Anda dapat memilih driver menggunakan dropdown realtime di bawah atau menambahkan karyawan baru di master karyawan.
                        </div>
                        <a href="?hal=employee_employees" target="_blank" class="btn btn-sm btn-primary py-0 px-2 text-white shadow-sm" style="font-size: 11.5px;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Master Karyawan
                        </a>
                    </div>

                    <div class="table-responsive border rounded mb-3" style="max-height: 480px; font-size: 11px;">
                        <table class="table table-sm table-hover table-striped mb-0" style="white-space: nowrap;">
                            <thead class="table-dark sticky-top" style="z-index: 5;">
                                <tr>
                                    <th class="text-center" width="40">
                                        <input type="checkbox" id="checkAllImport" class="form-check-input" checked onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>No Lambung</th>
                                    <th>Nama di Excel</th>
                                    <th style="min-width: 280px; width: 300px;">Pilih Driver di Sistem (Select2 Realtime)</th>
                                    <th>Pengganti</th>
                                    <th>Ritase / Shift</th>
                                    <th>Tonase</th>
                                    <th>KM (Awal-Akhir)</th>
                                    <th>Total KM</th>
                                    <th>Jam Operasi</th>
                                    <th>Cuci / Safety</th>
                                    <th>HM / OT</th>
                                    <th>Insentif Tonase</th>
                                    <th>Insentif HM</th>
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
                                <i class="bi bi-check-circle-fill me-1"></i> Simpan ke Timesheets Hauling (<span id="btnSaveCount">0</span> Data)
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

function autoSetShiftType(selectEl, targetId) {
    var val = parseInt(selectEl.value);
    var target = document.getElementById(targetId);
    if (target) {
        if (val >= 2) {
            target.value = '2';
        } else {
            target.value = '1';
        }
    }
}

function calcKm(prefix) {
    var awal = parseFloat(document.getElementById(prefix + '_km_awal').value) || 0;
    var akhir = parseFloat(document.getElementById(prefix + '_km_akhir').value) || 0;
    if (akhir > awal && awal > 0) {
        document.getElementById(prefix + '_total_km').value = (akhir - awal).toFixed(2);
    }
}

function upData(row) {
    document.getElementById('edit_id').value = row.id || '';
    document.getElementById('edit_employee_id').value = row.employee_id || '';
    document.getElementById('edit_substitute_employee_id').value = row.substitute_employee_id || '';
    document.getElementById('edit_tanggal').value = row.tanggal || '';
    document.getElementById('edit_unit_id').value = row.unit_id || '';
    document.getElementById('edit_ritase_ke').value = row.ritase_ke || '1';
    document.getElementById('edit_shift_type').value = row.shift_type || '1';
    document.getElementById('edit_tonase').value = row.tonase || '0';
    document.getElementById('edit_km_awal').value = row.km_awal || '0';
    document.getElementById('edit_km_akhir').value = row.km_akhir || '0';
    document.getElementById('edit_total_km').value = row.total_km || '0';
    
    document.getElementById('edit_jam_mulai').value = row.jam_mulai ? row.jam_mulai.substring(0, 5) : '';
    document.getElementById('edit_jam_loading').value = row.jam_loading ? row.jam_loading.substring(0, 5) : '';
    document.getElementById('edit_jam_timbang_awal').value = row.jam_timbang_awal ? row.jam_timbang_awal.substring(0, 5) : '';
    document.getElementById('edit_jam_bongkar').value = row.jam_bongkar ? row.jam_bongkar.substring(0, 5) : '';
    document.getElementById('edit_jam_timbang_akhir').value = row.jam_timbang_akhir ? row.jam_timbang_akhir.substring(0, 5) : '';
    document.getElementById('edit_jam_tiba_site').value = row.jam_tiba_site ? row.jam_tiba_site.substring(0, 5) : '';

    document.getElementById('edit_rest_time').value = row.rest_time ? row.rest_time.substring(0, 5) : '';
    document.getElementById('edit_p5m_time').value = row.p5m_time ? row.p5m_time.substring(0, 5) : '';
    
    document.getElementById('edit_cuci').checked = (parseInt(row.cuci) === 1);
    document.getElementById('edit_safety').checked = (parseInt(row.safety) === 1);
    
    document.getElementById('edit_hm_s1').value = row.hm_s1 || '0';
    document.getElementById('edit_ot_hours').value = row.ot_hours || '0';
    document.getElementById('edit_keterangan').value = row.keterangan || '';

    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

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
    document.getElementById('importLoadingText').innerText = 'Membedah data hauling & mencocokkan master driver...';

    var formData = new FormData();
    formData.append('raw_data', raw);

    fetch('actions/?hal=hauling_import-timesheets&action=preview', {
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
        tbody.innerHTML = '<tr><td colspan="17" class="text-center py-4 text-muted">Tidak ada data hauling yang valid untuk ditampilkan.</td></tr>';
        updateSelectedSummary();
        return;
    }

    data.rows.forEach(function(row, idx) {
        var tr = document.createElement('tr');
        var isMatched = (row.employee_id && row.employee_id.trim() !== '');
        
        if (!isMatched) {
            tr.className = 'table-warning';
        }

        var waktuStr = (row.jam_mulai ? row.jam_mulai.substring(0, 5) : '-') + ' s/d ' + (row.jam_tiba_site ? row.jam_tiba_site.substring(0, 5) : '-');
        var insentifTonStr = row.earned_tonase_incentive ? 'Rp ' + Number(row.earned_tonase_incentive).toLocaleString('id-ID') : '-';
        var insentifHmStr = (row.earned_hm_incentive || row.overtime_amount) ? 'Rp ' + Number(row.earned_hm_incentive + row.overtime_amount).toLocaleString('id-ID') : '-';

        // Select2 HTML for Driver
        var selectHtml = '<select class="form-select form-select-sm driver-select2" data-idx="' + idx + '" style="width: 100%;">';
        if (isMatched) {
            selectHtml += '<option value="' + escapeHtml(row.employee_id) + '" selected>' + escapeHtml(row.employee_name || 'Driver Terpilih') + '</option>';
        } else {
            selectHtml += '<option value=""></option>';
        }
        selectHtml += '</select>';

        tr.innerHTML = 
            '<td class="text-center"><input type="checkbox" class="form-check-input row-checkbox" data-idx="' + idx + '" checked onchange="updateSelectedSummary()"></td>' +
            '<td>' + (idx + 1) + '</td>' +
            '<td>' + escapeHtml(row.tanggal) + '</td>' +
            '<td class="fw-bold"><span class="badge bg-dark">' + escapeHtml(row.unit_id || '-') + '</span></td>' +
            '<td class="fw-semibold">' + escapeHtml(row.driver_raw) + '</td>' +
            '<td>' + selectHtml + '</td>' +
            '<td>' + (row.pengganti_raw ? escapeHtml(row.pengganti_raw) : '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-center">' + (row.shift_type === '2' ? '<span class="badge bg-warning text-dark">Rit ' + row.ritase_ke + ' (S2/OT)</span>' : '<span class="badge bg-primary">Rit ' + row.ritase_ke + ' (S1)</span>') + '</td>' +
            '<td class="text-end fw-bold text-success">' + (row.tonase ? row.tonase.toFixed(2) : '0.00') + '</td>' +
            '<td>' + (row.km_awal || 0) + ' → ' + (row.km_akhir || 0) + '</td>' +
            '<td class="fw-semibold">' + (row.total_km ? row.total_km.toFixed(1) : '0') + ' KM</td>' +
            '<td>' + waktuStr + '</td>' +
            '<td class="text-center">' + (row.cuci ? '✓' : '-') + ' / ' + (row.safety ? '✓' : '-') + '</td>' +
            '<td class="text-center">' + (row.hm_s1 || 0) + 'H / ' + (row.ot_hours || 0) + 'OT</td>' +
            '<td class="text-end text-primary fw-bold">' + insentifTonStr + '</td>' +
            '<td class="text-end text-secondary">' + insentifHmStr + '</td>' +
            '<td class="text-muted small">' + escapeHtml(row.keterangan || '-') + '</td>';

        tbody.appendChild(tr);
    });

    initSelect2ForPreview();
    updateSelectedSummary();
    applyPreviewFilters();
}

function initSelect2ForPreview() {
    $('#previewTableBody .driver-select2').each(function() {
        var $select = $(this);
        $select.select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#importExcelModal'),
            placeholder: '-- Cari / Pilih Driver --',
            allowClear: true,
            width: '100%',
            ajax: {
                url: 'actions/?hal=hauling_import-timesheets&action=search_employees',
                dataType: 'json',
                delay: 200,
                data: function(params) {
                    return { q: params.term || '' };
                },
                processResults: function(data) {
                    return { results: data.results || [] };
                },
                cache: false
            }
        }).on('select2:select', function(e) {
            var idx = parseInt($(this).attr('data-idx'));
            var selectedData = e.params.data;
            onDriverSelectChange(idx, selectedData.id, selectedData.text, this);
        }).on('select2:clear', function(e) {
            var idx = parseInt($(this).attr('data-idx'));
            onDriverSelectChange(idx, '', '', this);
        });
    });
}

function onDriverSelectChange(idx, empId, empName, selectEl) {
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
        var rowData = globalParsedRows[idx];
        if (!rowData) return;

        var isMatched = (rowData.employee_id && rowData.employee_id.trim() !== '');

        var matchesStatus = true;
        if (status === 'MATCHED' && !isMatched) matchesStatus = false;
        if (status === 'UNMATCHED' && isMatched) matchesStatus = false;

        var matchesSearch = true;
        if (search) {
            var textToSearch = [
                rowData.driver_raw || '',
                rowData.employee_name || '',
                rowData.pengganti_raw || '',
                rowData.unit_id || '',
                rowData.tanggal || '',
                rowData.keterangan || ''
            ].join(' ').toLowerCase();

            if (textToSearch.indexOf(search) === -1) {
                matchesSearch = false;
            }
        }

        if (matchesStatus && matchesSearch) {
            tr.style.display = '';
            visibleCount++;
        } else {
            tr.style.display = 'none';
        }
    });

    var countText = document.getElementById('filterResultCount');
    if (countText) {
        countText.innerText = 'Menampilkan ' + visibleCount + ' dari ' + globalParsedRows.length + ' baris';
    }
}

function toggleSelectAll(masterCb) {
    var tbody = document.getElementById('previewTableBody');
    var cbs = tbody.querySelectorAll('.row-checkbox');
    cbs.forEach(function(cb) {
        var tr = cb.closest('tr');
        if (tr && tr.style.display !== 'none') {
            cb.checked = masterCb.checked;
        }
    });
    updateSelectedSummary();
}

function updateSelectedSummary() {
    var tbody = document.getElementById('previewTableBody');
    var cbs = tbody.querySelectorAll('.row-checkbox:checked');
    var totalRows = globalParsedRows.length;
    var matchedCount = 0;
    var unmatchedCount = 0;

    globalParsedRows.forEach(function(r) {
        if (r.employee_id && r.employee_id.trim() !== '') {
            matchedCount++;
        } else {
            unmatchedCount++;
        }
    });

    document.getElementById('badgeTotalRows').innerText = totalRows + ' Total Baris';
    document.getElementById('badgeMatchedRows').innerText = matchedCount + ' Driver Cocok';
    
    var unBadge = document.getElementById('badgeUnmatchedRows');
    var unAlert = document.getElementById('unmatchedAlert');
    if (unmatchedCount > 0) {
        unBadge.style.display = 'inline-block';
        unBadge.innerText = unmatchedCount + ' Belum Cocok';
        unAlert.style.display = 'flex';
    } else {
        unBadge.style.display = 'none';
        unAlert.style.display = 'none';
    }

    var selectedCount = cbs.length;
    document.getElementById('selectedCountText').innerText = selectedCount + ' baris dicentang';
    document.getElementById('btnSaveCount').innerText = selectedCount;
}

function saveBatchImport() {
    var tbody = document.getElementById('previewTableBody');
    var cbs = tbody.querySelectorAll('.row-checkbox:checked');
    if (cbs.length === 0) {
        alert('Silakan pilih minimal 1 baris untuk disimpan!');
        return;
    }

    var selectedData = [];
    var unselectedDrivers = [];

    cbs.forEach(function(cb) {
        var idx = parseInt(cb.getAttribute('data-idx'));
        var row = globalParsedRows[idx];
        if (row) {
            if (!row.employee_id || row.employee_id.trim() === '') {
                unselectedDrivers.push(row.driver_raw || ('Baris ' + (idx + 1)));
            } else {
                selectedData.push(row);
            }
        }
    });

    if (unselectedDrivers.length > 0) {
        var proceed = confirm(
            'Perhatian: Ada ' + unselectedDrivers.length + ' data driver yang belum dipilih di sistem:\n' +
            unselectedDrivers.slice(0, 5).join(', ') + (unselectedDrivers.length > 5 ? '... dan lainnya' : '') + 
            '\n\nBaris tanpa driver terpilih TIDAK AKAN disimpan. Apakah Anda ingin melanjutkan menyimpan ' + selectedData.length + ' data yang valid?'
        );
        if (!proceed) return;
    }

    if (selectedData.length === 0) {
        alert('Tidak ada data valid yang dapat disimpan. Silakan pilih driver di dropdown terlebih dahulu!');
        return;
    }

    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importLoading').style.display = 'block';
    document.getElementById('importLoadingText').innerText = 'Menyimpan ' + selectedData.length + ' data hauling ke database...';

    fetch('actions/?hal=hauling_import-timesheets&action=save_batch', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ rows: selectedData })
    })
    .then(function(res) { return res.json(); })
    .then(function(resData) {
        document.getElementById('importLoading').style.display = 'none';
        if (resData.status === 'success') {
            window.location.reload();
        } else {
            alert(resData.message || 'Gagal menyimpan data.');
            document.getElementById('importStep2').style.display = 'block';
        }
    })
    .catch(function(err) {
        document.getElementById('importLoading').style.display = 'none';
        alert('Terjadi kesalahan jaringan: ' + err.message);
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
