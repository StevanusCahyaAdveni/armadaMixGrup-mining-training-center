<?php
/**
 * View: Dashboard Performance Mining
 * Path: pages/dashboard/dashboard-performance-mining.php
 */

// Filter Rentang Tanggal (Default: 2 bulan yang lalu sampai hari ini)
$defaultStart = date('Y-m-d', strtotime('-2 months'));
$defaultEnd = date('Y-m-d');

$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sani($_GET['start_date']) : $defaultStart;
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sani($_GET['end_date']) : $defaultEnd;

// Query Ringkasan Harian Mining dalam rentang tanggal
$sqlDaily = "SELECT 
    tanggal,
    COUNT(*) as total_logs,
    COUNT(DISTINCT employee_id) as total_operators,
    SUM(hmc) as total_hmc,
    SUM(CASE WHEN shift_type = '2' OR overtime_type != 'NONE' THEN 0 ELSE hmc END) as total_hm_s1,
    SUM(CASE WHEN shift_type = '2' OR overtime_type != 'NONE' THEN hmc ELSE 0 END) as total_hm_s2,
    SUM(earned_hm_incentive) as total_insentif_hm,
    SUM(overtime_amount) as total_overtime,
    SUM(earned_hm_incentive + overtime_amount) as total_insentif_total,
    SUM(ritase) as total_ritase,
    SUM(solar) as total_solar
FROM employee_timesheets
WHERE tanggal >= '$startDate' AND tanggal <= '$endDate'
GROUP BY tanggal
ORDER BY tanggal ASC";

$resDaily = mysqli_query($con, $sqlDaily);

$dailyData = [];
$datesArray = [];
$dateLabels = [];

// Arrays for ApexCharts
$chartHmc = [];
$chartHmS1 = [];
$chartHmS2 = [];
$chartInsentifHm = [];
$chartOvertime = [];
$chartTotalMoney = [];
$chartRitase = [];
$chartSolar = [];

// Grand Totals for KPI Cards
$kpiTotalHmc = 0;
$kpiTotalHmS1 = 0;
$kpiTotalHmS2 = 0;
$kpiTotalInsentifHm = 0;
$kpiTotalOvertime = 0;
$kpiGrandTotalMoney = 0;
$kpiTotalRitase = 0;
$kpiTotalSolar = 0;
$kpiTotalLogs = 0;

if ($resDaily) {
    while ($row = mysqli_fetch_assoc($resDaily)) {
        $dailyData[] = $row;
        $dateStr = $row['tanggal'];
        $datesArray[] = $dateStr;
        $dateLabels[] = date('d M', strtotime($dateStr));

        $hmc = (float)$row['total_hmc'];
        $hm1 = (float)$row['total_hm_s1'];
        $hm2 = (float)$row['total_hm_s2'];
        $insHm = (float)$row['total_insentif_hm'];
        $otAmt = (float)$row['total_overtime'];
        $totalMoney = (float)$row['total_insentif_total'];
        $rit = (int)$row['total_ritase'];
        $sol = (float)$row['total_solar'];

        $chartHmc[] = round($hmc, 2);
        $chartHmS1[] = round($hm1, 2);
        $chartHmS2[] = round($hm2, 2);
        $chartInsentifHm[] = round($insHm);
        $chartOvertime[] = round($otAmt);
        $chartTotalMoney[] = round($totalMoney);
        $chartRitase[] = $rit;
        $chartSolar[] = round($sol, 1);

        // Accumulate KPIs
        $kpiTotalHmc += $hmc;
        $kpiTotalHmS1 += $hm1;
        $kpiTotalHmS2 += $hm2;
        $kpiTotalInsentifHm += $insHm;
        $kpiTotalOvertime += $otAmt;
        $kpiGrandTotalMoney += $totalMoney;
        $kpiTotalRitase += $rit;
        $kpiTotalSolar += $sol;
        $kpiTotalLogs += (int)$row['total_logs'];
    }
}

$daysCount = count($dailyData);
?>

<style>
    /* Styling High Contrast untuk Dashboard Mining */
    .metric-card {
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        transition: all 0.25s ease;
        background-color: #ffffff;
        overflow: hidden;
    }
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1) !important;
    }
    .metric-title {
        color: #334155 !important;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    .metric-value {
        color: #0f172a !important;
        font-weight: 800;
        font-size: 1.55rem;
        line-height: 1.2;
    }
    .metric-unit {
        color: #475569 !important;
        font-weight: 700;
    }
    .icon-box {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .chart-card {
        border: 1px solid #cbd5e1;
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        margin-bottom: 24px;
    }
    .chart-card .card-header {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
        padding: 1.25rem 1.5rem;
    }
    .chart-card .card-header h6 {
        color: #0f172a !important;
        font-weight: 800;
        font-size: 1rem;
    }
    .chart-card .card-header small {
        color: #334155 !important;
        font-weight: 600;
        font-size: 12.5px;
    }
    .chart-badge-click {
        font-size: 11.5px;
        padding: 6px 12px;
        border-radius: 20px;
        background: #e0e7ff;
        color: #1e3a8a;
        border: 1px solid #c7d2fe;
        font-weight: 700;
        cursor: default;
    }
    .interactive-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .interactive-row:hover {
        background-color: #f1f5f9 !important;
    }
    .filter-card {
        border-radius: 16px;
        border: 2px solid #cbd5e1;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }
    .filter-label {
        color: #0f172a !important;
        font-weight: 700;
        font-size: 12.5px;
    }
    .table-high-contrast thead th {
        background-color: #0f172a !important;
        color: #ffffff !important;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 800;
        padding: 12px 14px;
        border: none;
    }
    .table-high-contrast tbody td {
        color: #0f172a;
        font-size: 13px;
        padding: 11px 14px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Badges High Contrast */
    .badge-contrast-primary {
        background-color: #dbeafe !important;
        color: #1e40af !important;
        border: 1px solid #93c5fd !important;
        font-weight: 800 !important;
    }
    .badge-contrast-success {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #6ee7b7 !important;
        font-weight: 800 !important;
    }
    .badge-contrast-info {
        background-color: #cffafe !important;
        color: #0e7490 !important;
        border: 1px solid #67e8f9 !important;
        font-weight: 800 !important;
    }
    .badge-contrast-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fcd34d !important;
        font-weight: 800 !important;
    }
    .badge-contrast-danger {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fca5a5 !important;
        font-weight: 800 !important;
    }
    .badge-contrast-dark {
        background-color: #e2e8f0 !important;
        color: #0f172a !important;
        border: 1px solid #cbd5e1 !important;
        font-weight: 800 !important;
    }
</style>

<div class="row">
    <!-- Header Page Info -->
    <div class="col-12 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h4 class="fw-bold mb-1" style="color: #0f172a; font-weight: 800;">
                    <i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Dashboard Performance Mining
                </h4>
                <p class="mb-0" style="color: #334155; font-weight: 600; font-size: 13.5px;">
                    Monitoring tren harian jam kerja alat berat (HM), lembur kerja (OT), insentif operator, dan biaya operasional mining.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge badge-contrast-primary px-3 py-2 rounded-pill">
                    <i class="bi bi-calendar3 me-1"></i> Data: <?= date('d M Y', strtotime($startDate)) ?> &ndash; <?= date('d M Y', strtotime($endDate)) ?>
                </span>
                <span class="badge badge-contrast-success px-3 py-2 rounded-pill">
                    <i class="bi bi-check-circle me-1"></i> <?= $daysCount ?> Hari Beroperasi
                </span>
            </div>
        </div>
    </div>

    <!-- Filter Rentang Tanggal -->
    <div class="col-12 mb-4">
        <div class="card filter-card p-3">
            <form method="GET" action="" class="row g-2 align-items-end">
                <input type="hidden" name="hal" value="dashboard_dashboard-performance-mining">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label filter-label mb-1">Mulai Tanggal</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm border-secondary text-dark fw-bold" value="<?= htmlspecialchars($startDate) ?>" required>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label filter-label mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm border-secondary text-dark fw-bold" value="<?= htmlspecialchars($endDate) ?>" required>
                </div>
                <div class="col-md-3 col-sm-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold shadow-sm">
                        <i class="bi bi-filter me-1"></i> Terapkan Filter
                    </button>
                    <a href="?hal=dashboard_dashboard-performance-mining" class="btn btn-light btn-sm text-dark border-secondary fw-bold" title="Reset Default (2 Bulan)">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 text-md-end text-start">
                    <span class="d-block mb-1" style="color: #334155; font-weight: 700; font-size: 12px;">Preset Cepat:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-dark btn-sm fw-bold" onclick="setPreset('2months')">2 Bulan</button>
                        <button type="button" class="btn btn-outline-dark btn-sm fw-bold" onclick="setPreset('thisMonth')">Bulan Ini</button>
                        <button type="button" class="btn btn-outline-dark btn-sm fw-bold" onclick="setPreset('last30')">30 Hari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Metric KPI Cards -->
    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-primary">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">Total Jam HM (HMC)</span>
                    <h4 class="metric-value mb-1"><?= number_format($kpiTotalHmc, 2, ',', '.') ?> <span class="fs-6 metric-unit">HM</span></h4>
                    <span class="small fw-bold" style="color: #1e40af;"><i class="bi bi-people-fill me-1"></i> <?= $kpiTotalLogs ?> Log Timesheet</span>
                </div>
                <div class="icon-box" style="background-color: #dbeafe; color: #1e40af;">
                    <i class="bi bi-speedometer"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">HM Shift 1 & Lembur (S2)</span>
                    <h4 class="metric-value mb-1"><?= number_format($kpiTotalHmS1, 2, ',', '.') ?> <span class="fs-6 metric-unit">S1</span></h4>
                    <span class="small fw-bold" style="color: #991b1b;"><i class="bi bi-stopwatch-fill me-1"></i> Lembur (S2): <?= number_format($kpiTotalHmS2, 2, ',', '.') ?> HM</span>
                </div>
                <div class="icon-box" style="background-color: #fef3c7; color: #92400e;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">Insentif HM & Overtime</span>
                    <h4 class="metric-value mb-1" style="color: #0e7490 !important;">Rp <?= number_format($kpiTotalInsentifHm, 0, ',', '.') ?></h4>
                    <span class="small fw-bold" style="color: #991b1b;"><i class="bi bi-coin me-1"></i> Overtime: Rp <?= number_format($kpiTotalOvertime, 0, ',', '.') ?></span>
                </div>
                <div class="icon-box" style="background-color: #cffafe; color: #0e7490;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">Grand Total Insentif</span>
                    <h4 class="metric-value mb-1" style="color: #047857 !important;">Rp <?= number_format($kpiGrandTotalMoney, 0, ',', '.') ?></h4>
                    <span class="small fw-bold" style="color: #334155;">
                        Ritase: <?= number_format($kpiTotalRitase) ?> | Solar: <?= number_format($kpiTotalSolar, 1, ',', '.') ?>L
                    </span>
                </div>
                <div class="icon-box" style="background-color: #d1fae5; color: #065f46;">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($daysCount === 0): ?>
    <div class="col-12 my-4">
        <div class="alert alert-warning py-4 text-center rounded-3 shadow-sm border border-warning" style="background-color: #fef3c7; color: #92400e;">
            <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2 text-warning"></i>
            <h5 class="fw-bold" style="color: #92400e;">Tidak ada data operasional Mining pada rentang tanggal ini</h5>
            <p class="mb-3" style="color: #78350f; font-weight: 600;">Silakan pilih tanggal lain atau klik reset untuk melihat data 2 bulan terakhir.</p>
            <a href="?hal=dashboard_dashboard-performance-mining" class="btn btn-primary btn-sm px-4 fw-bold">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Tampilkan Default (2 Bulan Terakhir)
            </a>
        </div>
    </div>
    <?php else: ?>

    <!-- Interactive Hint Banner -->
    <div class="col-12 mb-2">
        <div class="alert border-0 py-2.5 px-3 rounded-3 d-flex align-items-center justify-content-between shadow-sm" style="background-color: #eff6ff; border: 1.5px solid #bfdbfe !important; color: #1e3a8a;">
            <div>
                <i class="bi bi-info-circle-fill me-2 fs-6 text-primary"></i>
                <strong style="color: #1e3a8a;">Interaktif:</strong> <span style="color: #1e3a8a; font-weight: 600;">Klik pada salah satu batang grafik, titik data, atau baris pada tabel rekapitulasi untuk membuka rincian lengkap operasional per hari!</span>
            </div>
            <span class="badge badge-contrast-primary rounded-pill px-3 py-1.5">Klik Untuk Drill-down</span>
        </div>
    </div>

    <!-- Chart: Total HM, Lembur dan Insentifnya per Hari (Dual Axis) -->
    <div class="col-lg-12 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>Tren Harian Total HM (S1), Lembur (S2/OT), dan Total Insentifnya
                    </h6>
                    <small>Kenaikan dan penurunan jam kerja normal, jam lembur, serta total nominal uang insentif + overtime harian</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik data untuk detail</span>
            </div>
            <div class="card-body">
                <div id="chart-mining-hm-incentive"></div>
            </div>
        </div>
    </div>

    <!-- Secondary Chart: Komposisi HM & Overtime vs Solar / Ritase -->
    <div class="col-lg-6 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-pie-chart-fill text-warning me-2"></i>Komparasi HM Shift 1 vs HM Lembur (S2)
                    </h6>
                    <small>Perbandingan beban jam kerja reguler vs jam lembur harian</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik</span>
            </div>
            <div class="card-body">
                <div id="chart-mining-stacked"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-6 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-fuel-pump-fill text-info me-2"></i>Ritase & Konsumsi Bahan Bakar (Solar)
                    </h6>
                    <small>Volume ritase dan penggunaan solar per hari</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik</span>
            </div>
            <div class="card-body">
                <div id="chart-mining-fuel"></div>
            </div>
        </div>
    </div>

    <!-- Summary Table Section -->
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-table text-primary me-2"></i>Rekapitulasi Harian Operasional Mining
                    </h6>
                    <small>Klik salah satu baris untuk melihat rincian detail seluruh operator pada tanggal tersebut</small>
                </div>
                <span class="badge badge-contrast-primary rounded-pill px-3 py-1.5">
                    <?= count($dailyData) ?> Tanggal
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-high-contrast">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">No</th>
                                <th>Tanggal</th>
                                <th class="text-center">Jml Operator</th>
                                <th class="text-end">Total HMC (Jam)</th>
                                <th class="text-end">HM S1 (Normal)</th>
                                <th class="text-end">HM S2 (Lembur)</th>
                                <th class="text-end">Insentif HM</th>
                                <th class="text-end">Uang Overtime</th>
                                <th class="text-end">Total Uang (Rp)</th>
                                <th class="text-center">Ritase</th>
                                <th class="text-end">Solar (Liter)</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($dailyData as $r): 
                            ?>
                            <tr class="interactive-row" onclick="showMiningDetail('<?= $r['tanggal'] ?>')" title="Klik untuk lihat rincian tanggal <?= date('d M Y', strtotime($r['tanggal'])) ?>">
                                <td class="text-center fw-bold" style="color: #0f172a;"><?= $no++ ?></td>
                                <td>
                                    <span class="fw-bold" style="color: #0f172a;"><?= date('d F Y', strtotime($r['tanggal'])) ?></span>
                                    <span class="badge badge-contrast-dark ms-1"><?= date('l', strtotime($r['tanggal'])) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-contrast-primary px-2.5 py-1.5">
                                        <?= $r['total_operators'] ?> Orang
                                    </span>
                                </td>
                                <td class="text-end fw-bold" style="color: #0f172a;">
                                    <?= number_format((float)$r['total_hmc'], 2, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #1e40af;">
                                    <?= number_format((float)$r['total_hm_s1'], 2, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #991b1b;">
                                    <?= number_format((float)$r['total_hm_s2'], 2, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #334155;">
                                    Rp <?= number_format((float)$r['total_insentif_hm'], 0, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #991b1b;">
                                    Rp <?= number_format((float)$r['total_overtime'], 0, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #047857;">
                                    Rp <?= number_format((float)$r['total_insentif_total'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center fw-bold" style="color: #0f172a;">
                                    <?= number_format((int)$r['total_ritase']) ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #0e7490;">
                                    <?= number_format((float)$r['total_solar'], 1, ',', '.') ?> L
                                </td>
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3 shadow-sm" onclick="showMiningDetail('<?= $r['tanggal'] ?>')">
                                        <i class="bi bi-eye-fill me-1"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Drill-down Detail Harian Mining -->
<div class="modal fade" id="modalMiningDetail" tabindex="-1" aria-labelledby="modalMiningDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4" style="background-color: #0f172a; color: #ffffff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-primary text-white shadow-sm" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-gear-wide-connected fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="modalMiningDetailLabel">Detail Performa Mining</h5>
                        <p class="small mb-0" id="modalMiningSubtitle" style="color: #94a3b8; font-weight: 600;">Memuat data...</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Summary Statistics Pills for Clicked Date -->
                <div class="row g-2 mb-4" id="modalSummaryCards">
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Total Log Record</span>
                            <span class="fw-bold fs-6" style="color: #0f172a;" id="sumModalMiningLogs">0</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Total HMC</span>
                            <span class="fw-bold fs-6" style="color: #1e40af;" id="sumModalMiningHmc">0 HM</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">HM S1 / Lembur</span>
                            <span class="fw-bold fs-6" style="color: #0f172a;" id="sumModalMiningHmSplit">0 / 0</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Insentif HM</span>
                            <span class="fw-bold fs-6" style="color: #047857;" id="sumModalMiningInsHm">Rp 0</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-12">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Grand Total Uang (HM + OT)</span>
                            <span class="fw-bold fs-6" style="color: #047857;" id="sumModalMiningTotalMoney">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Search Input for filtering in modal -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group input-group-sm" style="max-width: 340px;">
                        <span class="input-group-text bg-white border-secondary"><i class="bi bi-search text-dark"></i></span>
                        <input type="text" id="miningSearchInput" class="form-control border-secondary text-dark fw-bold" placeholder="Cari nama operator, NIK, shift..." onkeyup="filterMiningModalTable()">
                    </div>
                    <span class="badge badge-contrast-dark rounded-pill px-3 py-1.5" id="miningRowCountBadge">0 Data</span>
                </div>

                <!-- Table Detail of Operators -->
                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-sm table-hover align-middle mb-0" id="miningDetailTable">
                        <thead style="background-color: #1e293b; color: #ffffff;">
                            <tr class="text-center" style="font-size: 11.5px; text-transform: uppercase;">
                                <th class="p-2">No</th>
                                <th class="p-2 text-start">Operator / NIK</th>
                                <th class="p-2">Shift</th>
                                <th class="p-2">Tipe Shift</th>
                                <th class="p-2">OT Type</th>
                                <th class="p-2 text-end">HMC</th>
                                <th class="p-2 text-end">Ins. HM</th>
                                <th class="p-2 text-end">Uang Lembur</th>
                                <th class="p-2 text-end">Total Uang</th>
                                <th class="p-2">Ritase</th>
                                <th class="p-2 text-end">Solar</th>
                            </tr>
                        </thead>
                        <tbody id="miningDetailTbody" style="font-size: 12.5px; color: #0f172a;">
                            <!-- Dynamic Content -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-dark btn-sm px-4 rounded-pill fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Preset helper
function setPreset(type) {
    const today = new Date();
    const endStr = today.toISOString().split('T')[0];
    let start = new Date();

    if (type === '2months') {
        start.setMonth(start.getMonth() - 2);
    } else if (type === 'thisMonth') {
        start.setDate(1);
    } else if (type === 'last30') {
        start.setDate(start.getDate() - 30);
    }

    const startStr = start.toISOString().split('T')[0];
    document.getElementById('start_date').value = startStr;
    document.getElementById('end_date').value = endStr;
    document.getElementById('start_date').form.submit();
}

<?php if ($daysCount > 0): ?>
// ApexCharts Data
const datesRaw = <?= json_encode($datesArray) ?>;
const dateLabels = <?= json_encode($dateLabels) ?>;
const dataHmc = <?= json_encode($chartHmc) ?>;
const dataHmS1 = <?= json_encode($chartHmS1) ?>;
const dataHmS2 = <?= json_encode($chartHmS2) ?>;
const dataInsentifHm = <?= json_encode($chartInsentifHm) ?>;
const dataOvertime = <?= json_encode($chartOvertime) ?>;
const dataTotalMoney = <?= json_encode($chartTotalMoney) ?>;
const dataRitase = <?= json_encode($chartRitase) ?>;
const dataSolar = <?= json_encode($chartSolar) ?>;

// Click Handler to Open Modal Detail
function triggerMiningDrillDown(index) {
    if (index !== undefined && datesRaw[index]) {
        showMiningDetail(datesRaw[index]);
    }
}

// Function to safely initialize charts once ApexCharts library is loaded
function initMiningCharts() {
    if (typeof ApexCharts === 'undefined') {
        setTimeout(initMiningCharts, 50);
        return;
    }

    // 1. Chart HM S1, HM S2 (Lembur), dan Insentif Total (Dual Axis)
    var elHmInc = document.querySelector("#chart-mining-hm-incentive");
    if (elHmInc) {
        var optionsMiningHmIncentive = {
            series: [
                {
                    name: 'HM Shift 1 (Normal)',
                    type: 'column',
                    data: dataHmS1
                },
                {
                    name: 'HM Shift 2 (Lembur)',
                    type: 'column',
                    data: dataHmS2
                },
                {
                    name: 'Total Insentif & Lembur (Rp)',
                    type: 'line',
                    data: dataTotalMoney
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: true },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerMiningDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            colors: ['#2563eb', '#dc2626', '#059669'],
            dataLabels: { enabled: false },
            labels: dateLabels,
            xaxis: {
                categories: dateLabels,
                type: 'category',
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' },
                axisTicks: { show: true, color: '#64748b' },
                tooltip: { enabled: false }
            },
            yaxis: [
                {
                    title: { text: 'Jam Kerja (HM)', style: { color: '#1e40af', fontWeight: 800 } },
                    labels: {
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) {
                            return Number(val).toLocaleString('id-ID') + ' HM';
                        }
                    }
                },
                {
                    show: false,
                    labels: {
                        formatter: function (val) {
                            return Number(val).toLocaleString('id-ID') + ' HM';
                        }
                    }
                },
                {
                    opposite: true,
                    title: { text: 'Total Uang (Rp)', style: { color: '#047857', fontWeight: 800 } },
                    labels: {
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y, opts) {
                        var seriesIndex = (opts && typeof opts.seriesIndex !== 'undefined') ? opts.seriesIndex : 0;
                        if (seriesIndex < 2) return Number(y).toLocaleString('id-ID') + ' HM';
                        return 'Rp ' + Number(y).toLocaleString('id-ID');
                    }
                }
            },
            legend: { 
                position: 'top', 
                horizontalAlign: 'left',
                labels: { colors: '#0f172a' },
                fontWeight: 700,
                fontSize: '13px'
            }
        };
        var chartMiningHmIncentive = new ApexCharts(elHmInc, optionsMiningHmIncentive);
        chartMiningHmIncentive.render();
    }

    // 2. Chart Stacked HM S1 vs S2
    var elStacked = document.querySelector("#chart-mining-stacked");
    if (elStacked) {
        var optionsMiningStacked = {
            series: [
                { name: 'HM S1 (Normal)', data: dataHmS1 },
                { name: 'HM S2 (Lembur)', data: dataHmS2 }
            ],
            chart: {
                type: 'bar',
                height: 300,
                stacked: true,
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: false },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerMiningDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            colors: ['#2563eb', '#f59e0b'],
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: {
                title: { text: 'Total Jam (HM)', style: { color: '#0f172a', fontWeight: 800 } },
                labels: { 
                    style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                    formatter: function (val) { return Number(val).toLocaleString('id-ID'); } 
                }
            },
            tooltip: {
                y: { formatter: function (val) { return Number(val).toLocaleString('id-ID') + ' HM'; } }
            },
            legend: { 
                position: 'top', 
                horizontalAlign: 'left',
                labels: { colors: '#0f172a' },
                fontWeight: 700,
                fontSize: '13px'
            }
        };
        var chartMiningStacked = new ApexCharts(elStacked, optionsMiningStacked);
        chartMiningStacked.render();
    }

    // 3. Chart Ritase & Solar
    var elFuel = document.querySelector("#chart-mining-fuel");
    if (elFuel) {
        var optionsMiningFuel = {
            series: [
                { name: 'Ritase', type: 'column', data: dataRitase },
                { name: 'Solar (Liter)', type: 'line', data: dataSolar }
            ],
            chart: {
                type: 'line',
                height: 300,
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: false },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerMiningDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            stroke: { width: [0, 3], curve: 'smooth' },
            colors: ['#7c3aed', '#0284c7'],
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: [
                {
                    title: { text: 'Ritase', style: { color: '#0f172a', fontWeight: 800 } },
                    labels: { 
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) { return Math.round(val); } 
                    }
                },
                {
                    opposite: true,
                    title: { text: 'Solar (L)', style: { color: '#0284c7', fontWeight: 800 } },
                    labels: { 
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) { return Number(val).toFixed(0) + ' L'; } 
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y, opts) {
                        var seriesIndex = (opts && typeof opts.seriesIndex !== 'undefined') ? opts.seriesIndex : 0;
                        if (seriesIndex === 0) return Number(y).toLocaleString('id-ID') + ' Rit';
                        return Number(y).toLocaleString('id-ID') + ' Liter';
                    }
                }
            },
            legend: { 
                position: 'top', 
                horizontalAlign: 'left',
                labels: { colors: '#0f172a' },
                fontWeight: 700,
                fontSize: '13px'
            }
        };
        var chartMiningFuel = new ApexCharts(elFuel, optionsMiningFuel);
        chartMiningFuel.render();
    }
}

// Safely trigger initialization
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initMiningCharts();
} else {
    document.addEventListener('DOMContentLoaded', initMiningCharts);
    window.addEventListener('load', initMiningCharts);
}
<?php endif; ?>

// Modal Drill-down AJAX Logic
let currentMiningRows = [];

function showMiningDetail(dateStr) {
    const modalEl = document.getElementById('modalMiningDetail');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('modalMiningSubtitle').innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-light"></span> Memuat data rincian untuk tanggal <strong>${dateStr}</strong>...`;
    document.getElementById('miningDetailTbody').innerHTML = `<tr><td colspan="11" class="text-center py-5" style="color: #475569;"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Mengambil data rincian operasional mining...</td></tr>`;
    document.getElementById('miningSearchInput').value = '';
    
    modal.show();

    const primaryUrl = `actions/index.php?hal=dashboard_performance-detail&action=get_mining_detail&date=${encodeURIComponent(dateStr)}`;
    const fallbackUrl = `actions/pages/dashboard/performance-detail.php?action=get_mining_detail&date=${encodeURIComponent(dateStr)}`;

    async function fetchMiningDetail() {
        try {
            let res = null;
            try {
                const response = await fetch(primaryUrl);
                const text = await response.text();
                res = JSON.parse(text);
            } catch (errPrimary) {
                console.warn('Primary fetch non-JSON, trying fallback...', errPrimary);
                const responseFallback = await fetch(fallbackUrl);
                const textFallback = await responseFallback.text();
                res = JSON.parse(textFallback);
            }

            if (res && res.status === 'success') {
                document.getElementById('modalMiningSubtitle').innerHTML = `Tanggal Operasional: <strong class="text-white">${res.date_formatted}</strong> (${res.summary.total_logs} Record Tercatat)`;
                
                // Populate KPI Summary in Modal
                document.getElementById('sumModalMiningLogs').textContent = res.summary.total_logs + ' Log';
                document.getElementById('sumModalMiningHmc').textContent = res.summary.total_hmc + ' HM';
                document.getElementById('sumModalMiningHmSplit').textContent = res.summary.total_hm_s1 + ' / ' + res.summary.total_hm_s2;
                document.getElementById('sumModalMiningInsHm').textContent = res.summary.total_insentif_hm;
                document.getElementById('sumModalMiningTotalMoney').textContent = res.summary.total_money;

                currentMiningRows = res.rows || [];
                renderMiningTable(currentMiningRows);
            } else {
                const msg = (res && res.message) ? res.message : 'Gagal memuat rincian!';
                document.getElementById('modalMiningSubtitle').innerHTML = `<span class="text-danger fw-bold">${msg}</span>`;
                document.getElementById('miningDetailTbody').innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger fw-bold">${msg}</td></tr>`;
            }
        } catch (err) {
            console.error('Fetch error:', err);
            document.getElementById('modalMiningSubtitle').innerHTML = `<span class="text-danger fw-bold">Terjadi kesalahan jaringan!</span>`;
            document.getElementById('miningDetailTbody').innerHTML = `<tr><td colspan="11" class="text-center py-4 text-danger fw-bold">Gagal menghubungi server (${err.message}). Silakan refresh halaman.</td></tr>`;
        }
    }

    fetchMiningDetail();
}

function renderMiningTable(rows) {
    const tbody = document.getElementById('miningDetailTbody');
    document.getElementById('miningRowCountBadge').textContent = rows.length + ' Data';

    if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-dark fw-bold">Tidak ada data yang cocok dengan pencarian.</td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        let isS2 = (r.shift_type === '2' || (r.overtime_type && r.overtime_type !== 'NONE'));
        let shiftBadge = isS2 
            ? `<span class="badge badge-contrast-danger">S2 (Lembur)</span>` 
            : `<span class="badge badge-contrast-primary">S1 (Normal)</span>`;

        let otTypeBadge = (r.overtime_type && r.overtime_type !== 'NONE')
            ? `<span class="badge badge-contrast-warning">${escapeHtml(r.overtime_type)}</span>`
            : `<span class="fw-bold" style="color: #64748b;">-</span>`;

        html += `<tr>
            <td class="text-center fw-bold" style="color: #0f172a;">${idx + 1}</td>
            <td>
                <div class="fw-bold" style="color: #0f172a;">${escapeHtml(r.operator_name || '-')}</div>
                <small style="color: #475569; font-weight: 700;">${escapeHtml(r.operator_nik || '-')}</small>
            </td>
            <td class="text-center fw-bold" style="color: #0f172a;">Shift ${escapeHtml(r.shift || '-')}</td>
            <td class="text-center">${shiftBadge}</td>
            <td class="text-center">${otTypeBadge}</td>
            <td class="text-end fw-bold" style="color: #0f172a;">${escapeHtml(r.hmc_formatted)}</td>
            <td class="text-end fw-bold" style="color: #334155;">${escapeHtml(r.insentif_hm_formatted)}</td>
            <td class="text-end fw-bold" style="color: #991b1b;">${escapeHtml(r.overtime_amount_formatted)}</td>
            <td class="text-end fw-bold" style="color: #047857;">${escapeHtml(r.total_line_formatted)}</td>
            <td class="text-center fw-bold" style="color: #0f172a;">${escapeHtml(r.ritase || 0)}</td>
            <td class="text-end fw-bold" style="color: #0e7490;">${escapeHtml(r.solar_formatted)} L</td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function filterMiningModalTable() {
    const q = (document.getElementById('miningSearchInput').value || '').toLowerCase().trim();
    if (!q) {
        renderMiningTable(currentMiningRows);
        return;
    }

    const filtered = currentMiningRows.filter(r => {
        const name = (r.operator_name || '').toLowerCase();
        const nik = (r.operator_nik || '').toLowerCase();
        const shift = String(r.shift || '').toLowerCase();
        const otType = (r.overtime_type || '').toLowerCase();
        return name.includes(q) || nik.includes(q) || shift.includes(q) || otType.includes(q);
    });

    renderMiningTable(filtered);
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
