<?php
$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-t');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$calc_mode = isset($_GET['calc_mode']) ? sani($_GET['calc_mode']) : 'tonase'; // 'tonase', 'hm', 'compare'

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

// Query: Group by driver in hauling_timesheets + employees with level 'hauling'
$query = "SELECT 
            e.id as employee_id, 
            e.full_name, 
            e.employee_id as nik,
            e.bank,
            e.nomor_rekening,
            e.gaji_pokok, 
            e.tunjangan_tetap,
            e.level,
            COUNT(t.id) as total_ritase,
            SUM(t.tonase) as total_tonase,
            SUM(CASE WHEN t.shift_type = '1' OR t.ritase_ke = 1 THEN t.tonase ELSE 0 END) as tonase_s1,
            SUM(CASE WHEN t.shift_type = '2' OR t.ritase_ke >= 2 THEN t.tonase ELSE 0 END) as tonase_s2,
            SUM(CASE WHEN t.shift_type = '1' OR t.ritase_ke = 1 THEN t.earned_tonase_incentive ELSE 0 END) as insentif_tonase_s1,
            SUM(CASE WHEN t.shift_type = '2' OR t.ritase_ke >= 2 THEN t.earned_tonase_incentive ELSE 0 END) as insentif_tonase_s2,
            SUM(t.earned_tonase_incentive) as total_insentif_tonase,
            SUM(t.hm_s1) as total_hm_s1,
            SUM(t.ot_hours) as total_ot_hours,
            SUM(
                CASE 
                    WHEN t.ot_hours <= 0 THEN 0
                    WHEN t.ot_hours <= 1 THEN t.ot_hours * 1.5
                    ELSE 1.5 + (t.ot_hours - 1) * 2
                END
            ) as total_eff_ot_hours,
            SUM(t.earned_hm_incentive) as total_insentif_hm,
            SUM(
                CASE 
                    WHEN t.overtime_amount > 0 THEN t.overtime_amount
                    ELSE (CASE 
                            WHEN t.ot_hours <= 0 THEN 0
                            WHEN t.ot_hours <= 1 THEN t.ot_hours * 1.5
                            ELSE 1.5 + (t.ot_hours - 1) * 2
                          END) * $tarif_lembur
                END
            ) as total_overtime,
            (SELECT SUM(CASE WHEN category = 'increasing' THEN value WHEN category = 'decreasing' THEN -value ELSE value END) 
             FROM employee_salary_increasing_decreasing s 
             WHERE s.user_id = e.id AND s.date BETWEEN '$start_date' AND '$end_date') as penambah_pengurang
          FROM employees e
          INNER JOIN hauling_timesheets t ON e.id = t.employee_id AND t.tanggal BETWEEN '$start_date' AND '$end_date'
          GROUP BY e.id
          HAVING (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%' OR e.bank LIKE '%$search%')
          ORDER BY e.full_name ASC";

$result = mysqli_query($con, $query);

// Summary totals
$totalDrivers = 0;
$grandTotalRitase = 0;
$grandTotalTonase = 0;
$grandTonaseS1 = 0;
$grandTonaseS2 = 0;
$grandInsentifTonase = 0;
$grandInsentifHm = 0;
$grandTotalThpTonase = 0;
$grandTotalThpHm = 0;

$driverList = [];
if ($result) {
    while ($r = mysqli_fetch_assoc($result)) {
        // Fallback calculations if zero
        if ($r['total_insentif_tonase'] <= 0 && $r['total_tonase'] > 0) {
            $r['insentif_tonase_s1'] = $r['tonase_s1'] * $tarif_tonase_s1;
            $r['insentif_tonase_s2'] = $r['tonase_s2'] * $tarif_tonase_s2;
            $r['total_insentif_tonase'] = $r['insentif_tonase_s1'] + $r['insentif_tonase_s2'];
        }
        if ($r['total_insentif_hm'] <= 0 && $r['total_hm_s1'] > 0) {
            $r['total_insentif_hm'] = $r['total_hm_s1'] * $tarif_hm;
        }
        if ($r['total_overtime'] <= 0 && $r['total_eff_ot_hours'] > 0) {
            $r['total_overtime'] = $r['total_eff_ot_hours'] * $tarif_lembur;
        }

        $gapok = (float) ($r['gaji_pokok'] ?? 0);
        $tunj = (float) ($r['tunjangan_tetap'] ?? 0);
        $incDec = (float) ($r['penambah_pengurang'] ?? 0);
        
        $thpTonase = $gapok + $tunj + (float)$r['total_insentif_tonase'] + $incDec;
        $thpHm = $gapok + $tunj + (float)$r['total_insentif_hm'] + (float)$r['total_overtime'] + $incDec;

        $r['thp_tonase'] = $thpTonase;
        $r['thp_hm'] = $thpHm;

        $totalDrivers++;
        $grandTotalRitase += (int) $r['total_ritase'];
        $grandTotalTonase += (float) $r['total_tonase'];
        $grandTonaseS1 += (float) $r['tonase_s1'];
        $grandTonaseS2 += (float) $r['tonase_s2'];
        $grandInsentifTonase += (float) $r['total_insentif_tonase'];
        $grandInsentifHm += (float) ($r['total_insentif_hm'] + $r['total_overtime']);
        $grandTotalThpTonase += $thpTonase;
        $grandTotalThpHm += $thpHm;

        $driverList[] = $r;
    }
}
?>

<div class="page-heading">
    <!-- Rate & Scheme Info Banner -->
    <div class="card border-0 shadow-sm mb-3 bg-light d-print-none">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary px-3 py-2 fs-6"><i class="bi bi-truck me-1"></i>Rekap Gaji & Payroll Hauling</span>
                    <span class="badge bg-success px-3 py-2 fs-6">Tonase S1 (Pokok): Rp <?= number_format($tarif_tonase_s1, 0, ',', '.') ?> / Ton</span>
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">Tonase S2 / OTW: Rp <?= number_format($tarif_tonase_s2, 0, ',', '.') ?> / Ton</span>
                    <span class="badge bg-secondary px-3 py-2 fs-6">Tarif HM: Rp <?= number_format($tarif_hm, 0, ',', '.') ?> | OT: Rp <?= number_format($tarif_lembur, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-success shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Cetak Laporan
                    </button>
                    <a href="actions/pages/hauling/export-payroll.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&calc_mode=<?= $calc_mode ?>" class="btn btn-sm btn-success shadow-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </a>
                </div>
            </div>
            
            <div class="row g-2 text-muted" style="font-size: 12.5px;">
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white h-100">
                        <b>1. Skema Tonase (Standard Hauling):</b><br>
                        • Shift 1 (Ritase 1): <b>Tonase &times; Rp <?= number_format($tarif_tonase_s1, 0, ',', '.') ?></b><br>
                        • Shift 2 / OTW (Ritase 2+): <b>Tonase &times; Rp <?= number_format($tarif_tonase_s2, 0, ',', '.') ?></b>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white h-100">
                        <b>2. Skema HM (Standard Mining):</b><br>
                        • Shift 1 (Pokok): <b>HM S1 &times; Rp <?= number_format($tarif_hm, 0, ',', '.') ?></b><br>
                        • Overtime (OT): <b>Jam Efektif &times; Rp <?= number_format($tarif_lembur, 0, ',', '.') ?></b><br>
                        <small class="text-primary">*Formula Lembur: 1 jam pertama = 1.5x, jam ke-2 dst = 2x</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white h-100">
                        <b>Catatan Penggajian:</b><br>
                        • Data dihitung dari rekap input harian Timesheets Hauling.<br>
                        • Payroll dapat ditinjau berdasarkan Skema Tonase maupun Skema HM.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards (Mini Dashboard) -->
    <div class="row g-2 mb-3 d-print-none">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 46px; height: 46px; min-width: 46px; background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff;">
                        <i class="bi bi-people-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Driver & Ritase</div>
                        <div class="d-flex align-items-baseline gap-1 flex-wrap">
                            <h5 class="mb-0 fw-bold text-dark"><?= $totalDrivers ?> <span class="small fw-normal text-muted" style="font-size: 12px;">Driver</span></h5>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0" style="font-size: 11px;"><?= number_format($grandTotalRitase) ?> Rit</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 46px; height: 46px; min-width: 46px; background: linear-gradient(135deg, #10b981, #047857); color: #ffffff;">
                        <i class="bi bi-box-seam-fill fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Total Tonase</div>
                        <h5 class="mb-0 fw-bold text-dark"><?= number_format($grandTotalTonase, 2, ',', '.') ?> <span class="small fw-normal text-muted" style="font-size: 12px;">Ton</span></h5>
                        <div class="text-muted small" style="font-size: 10.5px;">S1: <b class="text-dark"><?= number_format($grandTonaseS1, 1) ?>T</b> | S2: <b class="text-dark"><?= number_format($grandTonaseS2, 1) ?>T</b></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 46px; height: 46px; min-width: 46px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff;">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Insentif Tonase</div>
                        <h5 class="mb-0 fw-bold" style="color: #b45309 !important;">Rp <?= number_format($grandInsentifTonase, 0, ',', '.') ?></h5>
                        <div class="text-muted small" style="font-size: 10.5px;">S1 @3k / S2 @3.5k</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border h-100 mb-0" style="background: #ffffff !important; border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center rounded-3 shadow-sm" style="width: 46px; height: 46px; min-width: 46px; background: linear-gradient(135deg, #06b6d4, #0e7490); color: #ffffff;">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="text-secondary small fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Grand Total (THP)</div>
                        <h5 class="mb-0 fw-bold" style="color: #047857 !important;">Rp <?= number_format($grandTotalThpTonase, 0, ',', '.') ?></h5>
                        <div class="text-muted small" style="font-size: 10.5px;">Skema HM: <b class="text-dark">Rp <?= number_format($grandTotalThpHm, 0, ',', '.') ?></b></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card p-3 mb-2 shadow-sm d-print-none bg-light border">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="hauling_payroll">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-semibold">Mulai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="<?= $start_date ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-semibold">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="<?= $end_date ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-semibold">Cari Driver / NIK</label>
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Nama driver..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1 fw-semibold">Mode Penghitungan Gaji</label>
                        <select name="calc_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="tonase" <?= $calc_mode === 'tonase' ? 'selected' : '' ?>>🚚 Skema Tonase (S1: 3.000 / S2: 3.500)</option>
                            <option value="hm" <?= $calc_mode === 'hm' ? 'selected' : '' ?>>⏱️ Skema HM (HM 17k / OT 19.5k)</option>
                            <option value="compare" <?= $calc_mode === 'compare' ? 'selected' : '' ?>>⚖️ Perbandingan Kedua Skema</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-sm btn-primary px-4 shadow-sm"><i class="bi bi-filter me-1"></i> Terapkan Filter</button>
                </div>
            </form>
        </div>

        <!-- Result Card -->
        <div class="card p-2 shadow-sm">
            <div class="mb-3 text-center d-none d-print-block">
                <h4>Laporan Penggajian Driver Hauling</h4>
                <p class="mb-1">Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
                <p class="fw-bold">Mode Perhitungan: <?= strtoupper($calc_mode) ?></p>
            </div>

            <!-- Tab Buttons for quick switching -->
            <ul class="nav nav-tabs mb-2 d-print-none" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?= $calc_mode === 'tonase' ? 'active fw-bold' : '' ?>" href="?hal=hauling_payroll&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&calc_mode=tonase">
                        <i class="bi bi-truck me-1"></i> Skema Tonase (Standar Hauling)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $calc_mode === 'hm' ? 'active fw-bold' : '' ?>" href="?hal=hauling_payroll&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&calc_mode=hm">
                        <i class="bi bi-clock-history me-1"></i> Skema HM (Standar Mining)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $calc_mode === 'compare' ? 'active fw-bold' : '' ?>" href="?hal=hauling_payroll&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&search=<?= urlencode($search) ?>&calc_mode=compare">
                        <i class="bi bi-arrow-left-right me-1"></i> Perbandingan Kedua Skema
                    </a>
                </li>
            </ul>

            <div class="table-responsive">
                <?php if ($calc_mode === 'tonase'): ?>
                <!-- ==================== SKEMA TONASE TABLE ==================== -->
                <table id="payrollTonaseTable" class="table table-bordered table-hover table-sm align-middle" style="font-size: 12px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th width="40">No</th>
                            <th>NIK</th>
                            <th>Nama Driver</th>
                            <th>Bank & Rekening</th>
                            <th class="text-center">Total Rit</th>
                            <th class="text-end">Tonase S1 (Pokok)</th>
                            <th class="text-end">Insentif S1 (@3.000)</th>
                            <th class="text-end">Tonase S2 (OTW)</th>
                            <th class="text-end">Insentif S2 (@3.500)</th>
                            <th class="text-end">Total Tonase</th>
                            <th class="text-end fw-bold" style="background-color: #fef3c7 !important; color: #92400e !important;">Total Insentif Tonase</th>
                            <th class="text-end">Penambah/Pengurang</th>
                            <th class="text-end fw-bold" style="background-color: #d1fae5 !important; color: #065f46 !important;">Take Home Pay</th>
                            <th class="text-center d-print-none" width="60">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (empty($driverList)):
                        ?>
                            <tr>
                                <td colspan="14" class="text-center text-muted py-4">Tidak ada data hauling ditemukan pada rentang tanggal ini.</td>
                            </tr>
                        <?php
                        else:
                            foreach ($driverList as $row):
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= htmlspecialchars($row['nik'] ?: '-') ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td>
                                    <?php if (!empty($row['bank'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['bank']) ?></span>
                                        <?php if (!empty($row['nomor_rekening'])): ?>
                                             <code class="ms-1"><?= htmlspecialchars($row['nomor_rekening']) ?></code>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum di-set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold">
                                    <a href="#" class="text-primary" onclick="openDriverTimesheets('<?= $row['employee_id'] ?>', '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')">
                                        <?= number_format($row['total_ritase']) ?> Rit
                                    </a>
                                </td>
                                <td class="text-end"><?= number_format($row['tonase_s1'], 2, ',', '.') ?> T</td>
                                <td class="text-end">Rp <?= number_format($row['insentif_tonase_s1'], 0, ',', '.') ?></td>
                                <td class="text-end"><?= number_format($row['tonase_s2'], 2, ',', '.') ?> T</td>
                                <td class="text-end">Rp <?= number_format($row['insentif_tonase_s2'], 0, ',', '.') ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($row['total_tonase'], 2, ',', '.') ?> T</td>
                                <td class="text-end fw-bold" style="background-color: #fffbeb !important; color: #92400e !important;">
                                    Rp <?= number_format($row['total_insentif_tonase'], 0, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalIncDec" onclick="openIncDecModal('<?= $row['employee_id'] ?>')">
                                        <?= ($row['penambah_pengurang'] ?? 0) < 0 ? '- Rp ' . number_format(abs($row['penambah_pengurang']), 0, ',', '.') : 'Rp ' . number_format($row['penambah_pengurang'] ?? 0, 0, ',', '.') ?>
                                    </a>
                                </td>
                                <td class="text-end fw-bold fs-6" style="background-color: #ecfdf5 !important; color: #047857 !important;">
                                    Rp <?= number_format($row['thp_tonase'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openDriverTimesheets('<?= $row['employee_id'] ?>', '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')" title="Detail Ritase Driver">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                    <?php if (!empty($driverList)): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL KESELURUHAN</td>
                            <td class="text-center"><?= number_format($grandTotalRitase) ?> Rit</td>
                            <td class="text-end"><?= number_format($grandTonaseS1, 2, ',', '.') ?> T</td>
                            <td class="text-end">-</td>
                            <td class="text-end"><?= number_format($grandTonaseS2, 2, ',', '.') ?> T</td>
                            <td class="text-end">-</td>
                            <td class="text-end text-success"><?= number_format($grandTotalTonase, 2, ',', '.') ?> T</td>
                            <td class="text-end fw-bold" style="background-color: #fef3c7 !important; color: #92400e !important;">Rp <?= number_format($grandInsentifTonase, 0, ',', '.') ?></td>
                            <td></td>
                            <td class="text-end fw-bold fs-6" style="background-color: #d1fae5 !important; color: #065f46 !important;">Rp <?= number_format($grandTotalThpTonase, 0, ',', '.') ?></td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>

                <?php elseif ($calc_mode === 'hm'): ?>
                <!-- ==================== SKEMA HM TABLE ==================== -->
                <table id="payrollHmTable" class="table table-bordered table-hover table-sm align-middle" style="font-size: 12px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th width="40">No</th>
                            <th>NIK</th>
                            <th>Nama Driver</th>
                            <th>Bank & Rekening</th>
                            <th class="text-center">Total Rit</th>
                            <th class="text-center">HM Shift 1 (Pokok)</th>
                            <th class="text-end">Insentif HM (@17.000)</th>
                            <th class="text-center">Jam OT (Real/Efektif)</th>
                            <th class="text-end">Uang Lembur (@19.509)</th>
                            <th class="text-end fw-bold" style="background-color: #dbeafe !important; color: #1e3a8a !important;">Total Insentif HM</th>
                            <th class="text-end">Penambah/Pengurang</th>
                            <th class="text-end fw-bold" style="background-color: #d1fae5 !important; color: #065f46 !important;">Take Home Pay (HM)</th>
                            <th class="text-center d-print-none" width="60">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (empty($driverList)):
                        ?>
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">Tidak ada data hauling ditemukan pada rentang tanggal ini.</td>
                            </tr>
                        <?php
                        else:
                            foreach ($driverList as $row):
                                $totalInsHmOt = (float)$row['total_insentif_hm'] + (float)$row['total_overtime'];
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code><?= htmlspecialchars($row['nik'] ?: '-') ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td>
                                    <?php if (!empty($row['bank'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($row['bank']) ?></span>
                                        <?php if (!empty($row['nomor_rekening'])): ?>
                                            <code class="ms-1"><?= htmlspecialchars($row['nomor_rekening']) ?></code>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum di-set</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold">
                                    <a href="#" class="text-primary" onclick="openDriverTimesheets('<?= $row['employee_id'] ?>', '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')">
                                        <?= number_format($row['total_ritase']) ?> Rit
                                    </a>
                                </td>
                                <td class="text-center"><?= number_format($row['total_hm_s1'], 1, ',', '.') ?> H</td>
                                <td class="text-end">Rp <?= number_format($row['total_insentif_hm'], 0, ',', '.') ?></td>
                                <td class="text-center">
                                    <b><?= number_format($row['total_ot_hours'], 1, ',', '.') ?> Jam</b>
                                    <?php if ((float)$row['total_eff_ot_hours'] > (float)$row['total_ot_hours']): ?>
                                        <br><span class="badge bg-warning-subtle text-dark border" style="font-size: 10px;" title="Jam Lembur Efektif (1.5x jam ke-1, 2x jam ke-2 dst)">Ef: <?= number_format($row['total_eff_ot_hours'], 1, ',', '.') ?> Jam</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">Rp <?= number_format($row['total_overtime'], 0, ',', '.') ?></td>
                                <td class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">
                                    Rp <?= number_format($totalInsHmOt, 0, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalIncDec" onclick="openIncDecModal('<?= $row['employee_id'] ?>')">
                                        <?= ($row['penambah_pengurang'] ?? 0) < 0 ? '- Rp ' . number_format(abs($row['penambah_pengurang']), 0, ',', '.') : 'Rp ' . number_format($row['penambah_pengurang'] ?? 0, 0, ',', '.') ?>
                                    </a>
                                </td>
                                <td class="text-end fw-bold fs-6" style="background-color: #ecfdf5 !important; color: #047857 !important;">
                                    Rp <?= number_format($row['thp_hm'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openDriverTimesheets('<?= $row['employee_id'] ?>', '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')" title="Detail Ritase Driver">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                    <?php if (!empty($driverList)): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL KESELURUHAN</td>
                            <td class="text-center"><?= number_format($grandTotalRitase) ?> Rit</td>
                            <td class="text-center">-</td>
                            <td class="text-end">-</td>
                            <td class="text-center">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end fw-bold" style="background-color: #dbeafe !important; color: #1e3a8a !important;">Rp <?= number_format($grandInsentifHm, 0, ',', '.') ?></td>
                            <td></td>
                            <td class="text-end fw-bold fs-6" style="background-color: #d1fae5 !important; color: #065f46 !important;">Rp <?= number_format($grandTotalThpHm, 0, ',', '.') ?></td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>

                <?php else: ?>
                <!-- ==================== MODE PERBANDINGAN TABLE ==================== -->
                <table id="payrollCompareTable" class="table table-bordered table-hover table-sm align-middle" style="font-size: 12px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th width="40">No</th>
                            <th>Nama Driver</th>
                            <th class="text-center">Total Rit</th>
                            <th class="text-end">Total Tonase</th>
                            <th class="text-end fw-bold" style="background-color: #fef3c7 !important; color: #92400e !important;">Insentif Tonase</th>
                            <th class="text-end fw-bold" style="background-color: #dbeafe !important; color: #1e3a8a !important;">Insentif HM + OT</th>
                            <th class="text-end">Selisih (Tonase - HM)</th>
                            <th class="text-center">Skema Lebih Menguntungkan</th>
                            <th class="text-end fw-bold" style="background-color: #d1fae5 !important; color: #065f46 !important;">THP Skema Tonase</th>
                            <th class="text-end fw-bold" style="background-color: #dbeafe !important; color: #1e3a8a !important;">THP Skema HM</th>
                            <th class="text-center d-print-none" width="60">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if (empty($driverList)):
                        ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">Tidak ada data hauling ditemukan pada rentang tanggal ini.</td>
                            </tr>
                        <?php
                        else:
                            foreach ($driverList as $row):
                                $insTon = (float) $row['total_insentif_tonase'];
                                $insHm = (float) $row['total_insentif_hm'] + (float) $row['total_overtime'];
                                $selisih = $insTon - $insHm;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td class="text-center fw-bold"><?= number_format($row['total_ritase']) ?> Rit</td>
                                <td class="text-end"><?= number_format($row['total_tonase'], 2, ',', '.') ?> T</td>
                                <td class="text-end fw-bold" style="background-color: #fffbeb !important; color: #92400e !important;">
                                    Rp <?= number_format($insTon, 0, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">
                                    Rp <?= number_format($insHm, 0, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold <?= $selisih >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= $selisih >= 0 ? '+ Rp ' . number_format($selisih, 0, ',', '.') : '- Rp ' . number_format(abs($selisih), 0, ',', '.') ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($selisih >= 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-truck me-1"></i>Skema Tonase (+<?= number_format(abs($selisih), 0, ',', '.') ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-clock me-1"></i>Skema HM (+<?= number_format(abs($selisih), 0, ',', '.') ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold" style="background-color: #f0fdf4 !important; color: #047857 !important;">Rp <?= number_format($row['thp_tonase'], 0, ',', '.') ?></td>
                                <td class="text-end fw-bold" style="background-color: #eff6ff !important; color: #1e3a8a !important;">Rp <?= number_format($row['thp_hm'], 0, ',', '.') ?></td>
                                <td class="text-center d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openDriverTimesheets('<?= $row['employee_id'] ?>', '<?= htmlspecialchars(addslashes($row['full_name'])) ?>')" title="Detail Ritase Driver">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                    <?php if (!empty($driverList)): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL KESELURUHAN</td>
                            <td class="text-end fw-bold" style="background-color: #fef3c7 !important; color: #92400e !important;">Rp <?= number_format($grandInsentifTonase, 0, ',', '.') ?></td>
                            <td class="text-end fw-bold" style="background-color: #dbeafe !important; color: #1e3a8a !important;">Rp <?= number_format($grandInsentifHm, 0, ',', '.') ?></td>
                            <td class="text-end <?= ($grandInsentifTonase - $grandInsentifHm) >= 0 ? 'text-success' : 'text-danger' ?>">
                                Rp <?= number_format($grandInsentifTonase - $grandInsentifHm, 0, ',', '.') ?>
                            </td>
                            <td></td>
                            <td class="text-end fw-bold fs-6" style="background-color: #d1fae5 !important; color: #065f46 !important;">Rp <?= number_format($grandTotalThpTonase, 0, ',', '.') ?></td>
                            <td class="text-end fw-bold fs-6" style="background-color: #dbeafe !important; color: #1e3a8a !important;">Rp <?= number_format($grandTotalThpHm, 0, ',', '.') ?></td>
                            <td class="d-print-none"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Modal Penambah/Pengurang -->
<div class="modal fade" id="modalIncDec" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Penambah / Pengurang Gaji</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="iframeIncDec" src="" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Timesheet Driver -->
<div class="modal fade" id="modalDriverTimesheets" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-truck me-2"></i>Rincian Timesheet Hauling: <span id="modalDriverTitle"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="iframeDriverTimesheets" src="" style="width: 100%; height: 500px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openIncDecModal(employeeId) {
    var startDate = '<?= $start_date ?>';
    var endDate = '<?= $end_date ?>';
    var url = 'index.php?hal=employee_employee-salary-increasing-decreasing&user_id=' + employeeId + '&start_date=' + startDate + '&end_date=' + endDate + '&iframe=1';
    document.getElementById('iframeIncDec').src = url;
}

function openDriverTimesheets(employeeId, driverName) {
    document.getElementById('modalDriverTitle').innerText = driverName;
    var startDate = '<?= $start_date ?>';
    var endDate = '<?= $end_date ?>';
    var url = 'index.php?hal=hauling_timesheets&user_id=' + employeeId + '&start_date=' + startDate + '&end_date=' + endDate + '&iframe=1';
    document.getElementById('iframeDriverTimesheets').src = url;
    var modal = new bootstrap.Modal(document.getElementById('modalDriverTimesheets'));
    modal.show();
}
</script>
