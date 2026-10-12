<?php
/**
 * View: Dashboard Performance Hauling
 * Path: pages/dashboard/dashboard-performance-hauling.php
 */

// Filter Rentang Tanggal (Default: 2 bulan yang lalu sampai hari ini)
$defaultStart = date('Y-m-d', strtotime('-2 months'));
$defaultEnd = date('Y-m-d');

$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? sani($_GET['start_date']) : $defaultStart;
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? sani($_GET['end_date']) : $defaultEnd;

// Query Ringkasan Harian Hauling dalam rentang tanggal
$sqlDaily = "SELECT 
    tanggal,
    COUNT(*) as total_ritase,
    SUM(tonase) as total_tonase,
    SUM(earned_tonase_incentive) as total_insentif_tonase,
    SUM(hm_s1) as total_hm_s1,
    SUM(ot_hours) as total_ot_real,
    SUM(earned_hm_incentive) as total_insentif_hm,
    SUM(overtime_amount) as total_overtime,
    SUM(earned_tonase_incentive + earned_hm_incentive + overtime_amount) as grand_total_uang,
    AVG(durasi_loading_timbang) as avg_step1,
    AVG(durasi_timbang_awal_akhir) as avg_step2,
    AVG(durasi_timbang_site) as avg_step3,
    AVG(durasi_loading_timbang + durasi_timbang_awal_akhir + durasi_timbang_site) as avg_trip_durasi,
    AVG(CASE WHEN jam_mulai IS NOT NULL AND jam_mulai != '' AND jam_mulai != '00:00:00' THEN TIME_TO_SEC(jam_mulai) END) as avg_mulai_sec
FROM hauling_timesheets
WHERE tanggal >= '$startDate' AND tanggal <= '$endDate'
GROUP BY tanggal
ORDER BY tanggal ASC";

$resDaily = mysqli_query($con, $sqlDaily);

$dailyData = [];
$datesArray = [];
$dateLabels = [];

// Arrays for ApexCharts
$chartTonase = [];
$chartInsentifTonase = [];
$chartRitase = [];
$chartHmS1 = [];
$chartOtHours = [];
$chartTotalHmOtUang = [];
$chartStep1 = [];
$chartStep2 = [];
$chartStep3 = [];
$chartMulaiHour = [];
$chartMulaiTimeStr = [];

// Grand Totals for KPI Cards
$kpiTotalTonase = 0;
$kpiTotalInsentifTonase = 0;
$kpiTotalRitase = 0;
$kpiTotalHmS1 = 0;
$kpiTotalOtReal = 0;
$kpiTotalHmOtUang = 0;
$kpiSumTripDurasi = 0;
$kpiSumMulaiSec = 0;
$kpiCountMulai = 0;

if ($resDaily) {
    while ($row = mysqli_fetch_assoc($resDaily)) {
        $dailyData[] = $row;
        $dateStr = $row['tanggal'];
        $datesArray[] = $dateStr;
        $dateLabels[] = date('d M', strtotime($dateStr));

        $ton = (float)$row['total_tonase'];
        $insTon = (float)$row['total_insentif_tonase'];
        $rit = (int)$row['total_ritase'];
        $hm1 = (float)$row['total_hm_s1'];
        $ot = (float)$row['total_ot_real'];
        $hmOtUang = (float)($row['total_insentif_hm'] + $row['total_overtime']);
        $step1 = round((float)$row['avg_step1'], 2);
        $step2 = round((float)$row['avg_step2'], 2);
        $step3 = round((float)$row['avg_step3'], 2);
        $mulaiSec = (float)$row['avg_mulai_sec'];

        $chartTonase[] = round($ton, 2);
        $chartInsentifTonase[] = round($insTon);
        $chartRitase[] = $rit;
        $chartHmS1[] = round($hm1, 1);
        $chartOtHours[] = round($ot, 1);
        $chartTotalHmOtUang[] = round($hmOtUang);
        $chartStep1[] = $step1;
        $chartStep2[] = $step2;
        $chartStep3[] = $step3;

        if ($mulaiSec > 0) {
            $mulaiHourDecimal = round($mulaiSec / 3600, 2);
            $h = floor($mulaiSec / 3600);
            $m = floor(($mulaiSec % 3600) / 60);
            $timeFormatted = sprintf('%02d:%02d', $h, $m);
            $chartMulaiHour[] = $mulaiHourDecimal;
            $chartMulaiTimeStr[] = $timeFormatted;
            $kpiSumMulaiSec += $mulaiSec;
            $kpiCountMulai++;
        } else {
            $chartMulaiHour[] = null;
            $chartMulaiTimeStr[] = '-';
        }

        // Accumulate KPIs
        $kpiTotalTonase += $ton;
        $kpiTotalInsentifTonase += $insTon;
        $kpiTotalRitase += $rit;
        $kpiTotalHmS1 += $hm1;
        $kpiTotalOtReal += $ot;
        $kpiTotalHmOtUang += $hmOtUang;
        $kpiSumTripDurasi += (float)$row['avg_trip_durasi'];
    }
}

$daysCount = count($dailyData);
$kpiAvgTrip = $daysCount > 0 ? round($kpiSumTripDurasi / $daysCount, 2) : 0;
$kpiAvgMulaiTime = '-';
if ($kpiCountMulai > 0) {
    $avgSec = $kpiSumMulaiSec / $kpiCountMulai;
    $kpiAvgMulaiTime = sprintf('%02d:%02d WIB', floor($avgSec / 3600), floor(($avgSec % 3600) / 60));
}
?>

<style>
    /* Styling High Contrast untuk Dashboard */
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
                    <i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard Performance Hauling
                </h4>
                <p class="mb-0" style="color: #334155; font-weight: 600; font-size: 13.5px;">
                    Monitoring tren harian tonase, ritase, jam kerja/lembur, efisiensi waktu siklus tiap step, dan waktu start operasional.
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
                <input type="hidden" name="hal" value="dashboard_dashboard-performance-hauling">
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
                    <a href="?hal=dashboard_dashboard-performance-hauling" class="btn btn-light btn-sm text-dark border-secondary fw-bold" title="Reset Default (2 Bulan)">
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
                    <span class="metric-title d-block mb-1">Total Tonase</span>
                    <h4 class="metric-value mb-1"><?= number_format($kpiTotalTonase, 2, ',', '.') ?> <span class="fs-6 metric-unit">Ton</span></h4>
                    <span class="small fw-bold" style="color: #1e40af;"><i class="bi bi-cash-stack me-1"></i> Rp <?= number_format($kpiTotalInsentifTonase, 0, ',', '.') ?></span>
                </div>
                <div class="icon-box" style="background-color: #dbeafe; color: #1e40af;">
                    <i class="bi bi-truck-front-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">Total Ritase</span>
                    <h4 class="metric-value mb-1"><?= number_format($kpiTotalRitase) ?> <span class="fs-6 metric-unit">Trip</span></h4>
                    <span class="small fw-bold" style="color: #0369a1;"><i class="bi bi-graph-up-arrow me-1"></i> <?= $daysCount > 0 ? round($kpiTotalRitase / $daysCount, 1) : 0 ?> Rit/Hari</span>
                </div>
                <div class="icon-box" style="background-color: #cffafe; color: #0e7490;">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">HM S1 & Lembur (OT)</span>
                    <h4 class="metric-value mb-1"><?= number_format($kpiTotalHmS1, 1, ',', '.') ?> <span class="fs-6 metric-unit">HM</span></h4>
                    <span class="small fw-bold" style="color: #991b1b;"><i class="bi bi-clock-fill me-1"></i> OT: <?= number_format($kpiTotalOtReal, 1, ',', '.') ?> Jam</span>
                </div>
                <div class="icon-box" style="background-color: #fef3c7; color: #92400e;">
                    <i class="bi bi-stopwatch-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 col-12 mb-3">
        <div class="card metric-card shadow-sm p-3 h-100 border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="metric-title d-block mb-1">Uang HM & Lembur</span>
                    <h4 class="metric-value mb-1" style="color: #047857 !important;">Rp <?= number_format($kpiTotalHmOtUang, 0, ',', '.') ?></h4>
                    <span class="small fw-bold" style="color: #047857;"><i class="bi bi-lightning-charge-fill me-1"></i> Start: <?= $kpiAvgMulaiTime ?></span>
                </div>
                <div class="icon-box" style="background-color: #d1fae5; color: #065f46;">
                    <i class="bi bi-wallet-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <?php if ($daysCount === 0): ?>
    <div class="col-12 my-4">
        <div class="alert alert-warning py-4 text-center rounded-3 shadow-sm border border-warning" style="background-color: #fef3c7; color: #92400e;">
            <i class="bi bi-exclamation-triangle-fill fs-2 d-block mb-2 text-warning"></i>
            <h5 class="fw-bold" style="color: #92400e;">Tidak ada data operasional Hauling pada rentang tanggal ini</h5>
            <p class="mb-3" style="color: #78350f; font-weight: 600;">Silakan pilih tanggal lain atau klik reset untuk melihat data 2 bulan terakhir.</p>
            <a href="?hal=dashboard_dashboard-performance-hauling" class="btn btn-primary btn-sm px-4 fw-bold">
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

    <!-- Chart 1: Tonase & Insentif Tonase per Hari -->
    <div class="col-lg-8 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>Tren Harian Tonase & Insentif Tonase
                    </h6>
                    <small>Kenaikan / penurunan tonase produksi dan nominal insentif tonase per hari</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik data untuk detail</span>
            </div>
            <div class="card-body">
                <div id="chart-tonase-incentive"></div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Total Ritase per Hari -->
    <div class="col-lg-4 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-arrow-repeat text-info me-2"></i>Total Ritase Harian
                    </h6>
                    <small>Frekuensi ritase hauling per hari</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik</span>
            </div>
            <div class="card-body">
                <div id="chart-ritase"></div>
            </div>
        </div>
    </div>

    <!-- Chart 3: Total HM, Lembur dan Insentifnya per Hari -->
    <div class="col-lg-12 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history text-warning me-2"></i>Total HM S1, Jam Lembur (OT), dan Nominal Uang HM+OT per Hari
                    </h6>
                    <small>Evaluasi jam operasional unit hauling beserta pengeluaran insentif jam kerja & lembur</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik data untuk detail</span>
            </div>
            <div class="card-body">
                <div id="chart-hm-ot"></div>
            </div>
        </div>
    </div>

    <!-- Chart 4: Rata-rata Waktu Siklus Tiap Step Operasional per Hari -->
    <div class="col-lg-7 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-hourglass-split text-success me-2"></i>Rata-rata Waktu Diperlukan Tiap Step per Hari
                    </h6>
                    <small>Step 1 (Loading &rarr; Timbang), Step 2 (Timbang Awal &rarr; Akhir), Step 3 (Timbang &rarr; Site) dalam Jam</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik</span>
            </div>
            <div class="card-body">
                <div id="chart-step-duration"></div>
            </div>
        </div>
    </div>

    <!-- Chart 5: Rata-rata Jam Mulai Operasional per Hari -->
    <div class="col-lg-5 col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-alarm text-danger me-2"></i>Rata-rata Waktu Mulai Operasional per Hari
                    </h6>
                    <small>Tren ketepatan waktu armada memulai ritase harian (WIB)</small>
                </div>
                <span class="chart-badge-click"><i class="bi bi-cursor-fill me-1"></i>Klik</span>
            </div>
            <div class="card-body">
                <div id="chart-start-time"></div>
            </div>
        </div>
    </div>

    <!-- Summary Table Section -->
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="bi bi-table text-primary me-2"></i>Rekapitulasi Harian Operasional Hauling
                    </h6>
                    <small>Klik salah satu baris untuk membuka modal rincian detail seluruh ritase pada tanggal tersebut</small>
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
                                <th class="text-center">Total Ritase</th>
                                <th class="text-end">Tonase (Ton)</th>
                                <th class="text-end">Insentif Tonase</th>
                                <th class="text-center">HM S1</th>
                                <th class="text-center">Lembur (OT)</th>
                                <th class="text-end">Uang HM + OT</th>
                                <th class="text-center">Avg Trip Durasi</th>
                                <th class="text-center">Avg Jam Mulai</th>
                                <th class="text-center" style="width: 100px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($dailyData as $r): 
                                $sec = (float)$r['avg_mulai_sec'];
                                $mulaiTxt = '-';
                                if ($sec > 0) {
                                    $mulaiTxt = sprintf('%02d:%02d', floor($sec / 3600), floor(($sec % 3600) / 60));
                                }
                                $avgTripTxt = number_format((float)$r['avg_trip_durasi'], 2, ',', '.') . ' Jam';
                                $hmOtMoney = (float)($r['total_insentif_hm'] + $r['total_overtime']);
                            ?>
                            <tr class="interactive-row" onclick="showHaulingDetail('<?= $r['tanggal'] ?>')" title="Klik untuk lihat rincian tanggal <?= date('d M Y', strtotime($r['tanggal'])) ?>">
                                <td class="text-center fw-bold" style="color: #0f172a;"><?= $no++ ?></td>
                                <td>
                                    <span class="fw-bold" style="color: #0f172a;"><?= date('d F Y', strtotime($r['tanggal'])) ?></span>
                                    <span class="badge badge-contrast-dark ms-1"><?= date('l', strtotime($r['tanggal'])) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-contrast-primary px-2.5 py-1.5">
                                        <?= $r['total_ritase'] ?> Rit
                                    </span>
                                </td>
                                <td class="text-end fw-bold" style="color: #0f172a;">
                                    <?= number_format((float)$r['total_tonase'], 2, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #047857;">
                                    Rp <?= number_format((float)$r['total_insentif_tonase'], 0, ',', '.') ?>
                                </td>
                                <td class="text-center fw-bold" style="color: #1e40af;">
                                    <?= number_format((float)$r['total_hm_s1'], 1, ',', '.') ?>
                                </td>
                                <td class="text-center fw-bold" style="color: #991b1b;">
                                    <?= number_format((float)$r['total_ot_real'], 1, ',', '.') ?>
                                </td>
                                <td class="text-end fw-bold" style="color: #0f172a;">
                                    Rp <?= number_format($hmOtMoney, 0, ',', '.') ?>
                                </td>
                                <td class="text-center fw-bold" style="color: #334155;">
                                    <?= $avgTripTxt ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-contrast-info"><?= $mulaiTxt ?></span>
                                </td>
                                <td class="text-center" onclick="event.stopPropagation();">
                                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3 shadow-sm" onclick="showHaulingDetail('<?= $r['tanggal'] ?>')">
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

<!-- Modal Drill-down Detail Harian Hauling -->
<div class="modal fade" id="modalHaulingDetail" tabindex="-1" aria-labelledby="modalHaulingDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4" style="background-color: #0f172a; color: #ffffff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box bg-primary text-white shadow-sm" style="width: 48px; height: 48px; border-radius: 12px;">
                        <i class="bi bi-truck-front-fill fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="modalHaulingDetailLabel">Detail Performa Hauling</h5>
                        <p class="small mb-0" id="modalHaulingSubtitle" style="color: #94a3b8; font-weight: 600;">Memuat data...</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4" style="background-color: #f8fafc;">
                <!-- Summary Statistics Pills for Clicked Date -->
                <div class="row g-2 mb-4" id="modalSummaryCards">
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Total Ritase</span>
                            <span class="fw-bold fs-6" style="color: #0f172a;" id="sumModalRitase">0</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Total Tonase</span>
                            <span class="fw-bold fs-6" style="color: #1e40af;" id="sumModalTonase">0 Ton</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Insentif Tonase</span>
                            <span class="fw-bold fs-6" style="color: #047857;" id="sumModalInsTonase">Rp 0</span>
                        </div>
                    </div>
                    <div class="col-md-2 col-6">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Total HM / OT</span>
                            <span class="fw-bold fs-6" style="color: #0f172a;" id="sumModalHmOt">0 / 0</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-12">
                        <div class="p-2.5 rounded-3 bg-white border text-center shadow-sm">
                            <span class="d-block small" style="color: #475569; font-weight: 800; font-size: 11px;">Uang HM + Lembur</span>
                            <span class="fw-bold fs-6" style="color: #047857;" id="sumModalHmOtMoney">Rp 0</span>
                        </div>
                    </div>
                </div>

                <!-- Search Input for filtering in modal -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="input-group input-group-sm" style="max-width: 340px;">
                        <span class="input-group-text bg-white border-secondary"><i class="bi bi-search text-dark"></i></span>
                        <input type="text" id="haulingSearchInput" class="form-control border-secondary text-dark fw-bold" placeholder="Cari driver, NIK, atau unit..." onkeyup="filterHaulingModalTable()">
                    </div>
                    <span class="badge badge-contrast-dark rounded-pill px-3 py-1.5" id="rowCountBadge">0 Ritase</span>
                </div>

                <!-- Table Detail of Trips -->
                <div class="table-responsive bg-white rounded-3 border">
                    <table class="table table-sm table-hover align-middle mb-0" id="haulingDetailTable">
                        <thead style="background-color: #1e293b; color: #ffffff;">
                            <tr class="text-center" style="font-size: 11.5px; text-transform: uppercase;">
                                <th class="p-2">Rit</th>
                                <th class="p-2 text-start">Driver / Karyawan</th>
                                <th class="p-2">Unit</th>
                                <th class="p-2 text-end">Tonase</th>
                                <th class="p-2 text-end">Ins. Tonase</th>
                                <th class="p-2">HM S1</th>
                                <th class="p-2">OT (Real/Ef)</th>
                                <th class="p-2 text-end">Ins. HM</th>
                                <th class="p-2 text-end">Uang Lembur</th>
                                <th class="p-2">Mulai</th>
                                <th class="p-2">Step (1/2/3)</th>
                                <th class="p-2 text-end">Total Uang</th>
                            </tr>
                        </thead>
                        <tbody id="haulingDetailTbody" style="font-size: 12.5px; color: #0f172a;">
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
const dataTonase = <?= json_encode($chartTonase) ?>;
const dataInsentifTonase = <?= json_encode($chartInsentifTonase) ?>;
const dataRitase = <?= json_encode($chartRitase) ?>;
const dataHmS1 = <?= json_encode($chartHmS1) ?>;
const dataOtHours = <?= json_encode($chartOtHours) ?>;
const dataTotalHmOtUang = <?= json_encode($chartTotalHmOtUang) ?>;
const dataStep1 = <?= json_encode($chartStep1) ?>;
const dataStep2 = <?= json_encode($chartStep2) ?>;
const dataStep3 = <?= json_encode($chartStep3) ?>;
const dataMulaiHour = <?= json_encode($chartMulaiHour) ?>;
const dataMulaiTimeStr = <?= json_encode($chartMulaiTimeStr) ?>;

// Click Handler to Open Modal Detail
function triggerDrillDown(index) {
    if (index !== undefined && datesRaw[index]) {
        showHaulingDetail(datesRaw[index]);
    }
}

// Function to safely initialize charts once ApexCharts library is loaded
function initHaulingCharts() {
    if (typeof ApexCharts === 'undefined') {
        setTimeout(initHaulingCharts, 50);
        return;
    }

    // 1. Chart Tonase & Insentif Tonase (Dual Axis)
    var elTonase = document.querySelector("#chart-tonase-incentive");
    if (elTonase) {
        var optionsTonaseIncentive = {
            series: [
                {
                    name: 'Total Tonase (Ton)',
                    type: 'column',
                    data: dataTonase
                },
                {
                    name: 'Insentif Tonase (Rp)',
                    type: 'line',
                    data: dataInsentifTonase
                }
            ],
            chart: {
                height: 340,
                type: 'line',
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: true },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            colors: ['#2563eb', '#059669'],
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
                    title: { text: 'Tonase (Ton)', style: { color: '#1e40af', fontWeight: 800 } },
                    labels: {
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) {
                            return Number(val).toLocaleString('id-ID') + ' T';
                        }
                    }
                },
                {
                    opposite: true,
                    title: { text: 'Insentif (Rp)', style: { color: '#047857', fontWeight: 800 } },
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
                        if (seriesIndex === 0) return Number(y).toLocaleString('id-ID') + ' Ton';
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
        var chartTonaseIncentive = new ApexCharts(elTonase, optionsTonaseIncentive);
        chartTonaseIncentive.render();
    }

    // 2. Chart Ritase (Bar/Area)
    var elRitase = document.querySelector("#chart-ritase");
    if (elRitase) {
        var optionsRitase = {
            series: [{
                name: 'Total Ritase',
                data: dataRitase
            }],
            chart: {
                type: 'area',
                height: 340,
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: false },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            colors: ['#4f46e5'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.75,
                    opacityTo: 0.15,
                    stops: [0, 90, 100]
                }
            },
            stroke: { curve: 'smooth', width: 2.5 },
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: {
                title: { text: 'Ritase', style: { color: '#0f172a', fontWeight: 800 } },
                labels: { 
                    style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                    formatter: function (val) { return Math.round(val); } 
                }
            },
            tooltip: {
                y: { formatter: function (val) { return val + " Ritase"; } }
            }
        };
        var chartRitase = new ApexCharts(elRitase, optionsRitase);
        chartRitase.render();
    }

    // 3. Chart Total HM S1, OT Hours, dan Uang HM+OT
    var elHmOt = document.querySelector("#chart-hm-ot");
    if (elHmOt) {
        var optionsHmOt = {
            series: [
                {
                    name: 'HM S1 (Jam)',
                    type: 'column',
                    data: dataHmS1
                },
                {
                    name: 'Jam Lembur / OT (Jam)',
                    type: 'column',
                    data: dataOtHours
                },
                {
                    name: 'Total Uang HM + OT (Rp)',
                    type: 'line',
                    data: dataTotalHmOtUang
                }
            ],
            chart: {
                height: 320,
                type: 'line',
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: true },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            colors: ['#2563eb', '#dc2626', '#059669'],
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: [
                {
                    title: { text: 'Jam Kerja (HM)', style: { color: '#0f172a', fontWeight: 800 } },
                    labels: { 
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) { return Number(val).toLocaleString('id-ID') + ' Jam'; } 
                    }
                },
                {
                    show: false,
                    labels: { formatter: function (val) { return Number(val).toLocaleString('id-ID') + ' Jam'; } }
                },
                {
                    opposite: true,
                    title: { text: 'Nominal (Rp)', style: { color: '#059669', fontWeight: 800 } },
                    labels: { 
                        style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                        formatter: function (val) { return 'Rp ' + Number(val).toLocaleString('id-ID'); } 
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (y, opts) {
                        var seriesIndex = (opts && typeof opts.seriesIndex !== 'undefined') ? opts.seriesIndex : 0;
                        if (seriesIndex < 2) return Number(y).toLocaleString('id-ID') + ' Jam';
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
        var chartHmOt = new ApexCharts(elHmOt, optionsHmOt);
        chartHmOt.render();
    }

    // 4. Chart Waktu Tiap Step Operasional per Hari
    var elStep = document.querySelector("#chart-step-duration");
    if (elStep) {
        var optionsStepDuration = {
            series: [
                {
                    name: 'Step 1: Loading &rarr; Timbang Awal',
                    data: dataStep1
                },
                {
                    name: 'Step 2: Timbang Awal &rarr; Timbang Akhir',
                    data: dataStep2
                },
                {
                    name: 'Step 3: Timbang Akhir &rarr; Site',
                    data: dataStep3
                }
            ],
            chart: {
                type: 'bar',
                height: 320,
                stacked: true,
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: true },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            colors: ['#0284c7', '#d97706', '#059669'],
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: {
                title: { text: 'Rata-rata Durasi (Jam)', style: { color: '#0f172a', fontWeight: 800 } },
                labels: { 
                    style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                    formatter: function (val) { return Number(val).toFixed(1) + ' Jam'; } 
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        var h = Math.floor(val);
                        var m = Math.round((val - h) * 60);
                        return Number(val).toFixed(2) + ' Jam (' + h + 'j ' + m + 'm)';
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
        var chartStepDuration = new ApexCharts(elStep, optionsStepDuration);
        chartStepDuration.render();
    }

    // 5. Chart Rata-rata Jam Mulai Operasional per Hari
    var elStart = document.querySelector("#chart-start-time");
    if (elStart) {
        var optionsStartTime = {
            series: [{
                name: 'Rata-rata Jam Mulai',
                data: dataMulaiHour
            }],
            chart: {
                type: 'line',
                height: 320,
                foreColor: '#0f172a',
                fontFamily: 'Nunito, sans-serif',
                toolbar: { show: false },
                events: {
                    dataPointSelection: function(event, chartContext, config) {
                        triggerDrillDown(config.dataPointIndex);
                    }
                }
            },
            grid: {
                borderColor: '#cbd5e1',
                strokeDashArray: 3
            },
            colors: ['#0891b2'],
            stroke: { curve: 'smooth', width: 3 },
            markers: { size: 5, hover: { size: 7 } },
            xaxis: { 
                categories: dateLabels,
                labels: { style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' } },
                axisBorder: { show: true, color: '#64748b' }
            },
            yaxis: {
                title: { text: 'Waktu Mulai (WIB)', style: { color: '#0f172a', fontWeight: 800 } },
                labels: {
                    style: { colors: '#0f172a', fontWeight: 700, fontSize: '11px' },
                    formatter: function (val) {
                        if (!val) return '-';
                        var h = Math.floor(val);
                        var m = Math.round((val - h) * 60);
                        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val, opts) {
                        var dataPointIndex = (opts && typeof opts.dataPointIndex !== 'undefined') ? opts.dataPointIndex : 0;
                        return (dataMulaiTimeStr[dataPointIndex] || '-') + ' WIB';
                    }
                }
            }
        };
        var chartStartTime = new ApexCharts(elStart, optionsStartTime);
        chartStartTime.render();
    }
}

// Safely trigger initialization
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initHaulingCharts();
} else {
    document.addEventListener('DOMContentLoaded', initHaulingCharts);
    window.addEventListener('load', initHaulingCharts);
}
<?php endif; ?>

// Modal Drill-down AJAX Logic
let currentHaulingRows = [];

function showHaulingDetail(dateStr) {
    const modalEl = document.getElementById('modalHaulingDetail');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    document.getElementById('modalHaulingSubtitle').innerHTML = `<span class="spinner-border spinner-border-sm me-1 text-light"></span> Memuat data rincian untuk tanggal <strong>${dateStr}</strong>...`;
    document.getElementById('haulingDetailTbody').innerHTML = `<tr><td colspan="12" class="text-center py-5" style="color: #475569;"><span class="spinner-border spinner-border-sm me-2 text-primary"></span>Mengambil data rincian operasional...</td></tr>`;
    document.getElementById('haulingSearchInput').value = '';
    
    modal.show();

    const primaryUrl = `actions/index.php?hal=dashboard_performance-detail&action=get_hauling_detail&date=${encodeURIComponent(dateStr)}`;
    const fallbackUrl = `actions/pages/dashboard/performance-detail.php?action=get_hauling_detail&date=${encodeURIComponent(dateStr)}`;

    async function fetchHaulingDetail() {
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
                document.getElementById('modalHaulingSubtitle').innerHTML = `Tanggal Operasional: <strong class="text-white">${res.date_formatted}</strong> (${res.summary.total_ritase} Ritase Tercatat)`;
                
                // Populate KPI Summary in Modal
                document.getElementById('sumModalRitase').textContent = res.summary.total_ritase + ' Rit';
                document.getElementById('sumModalTonase').textContent = res.summary.total_tonase + ' Ton';
                document.getElementById('sumModalInsTonase').textContent = res.summary.total_insentif_tonase;
                document.getElementById('sumModalHmOt').textContent = res.summary.total_hm_s1 + ' / ' + res.summary.total_ot_real + ' Jam';
                document.getElementById('sumModalHmOtMoney').textContent = 'Rp ' + Number(String(res.summary.total_money).replace(/[^0-9]/g, '') || 0).toLocaleString('id-ID');

                currentHaulingRows = res.rows || [];
                renderHaulingTable(currentHaulingRows);
            } else {
                const msg = (res && res.message) ? res.message : 'Gagal memuat rincian!';
                document.getElementById('modalHaulingSubtitle').innerHTML = `<span class="text-danger fw-bold">${msg}</span>`;
                document.getElementById('haulingDetailTbody').innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger fw-bold">${msg}</td></tr>`;
            }
        } catch (err) {
            console.error('Fetch error:', err);
            document.getElementById('modalHaulingSubtitle').innerHTML = `<span class="text-danger fw-bold">Terjadi kesalahan jaringan!</span>`;
            document.getElementById('haulingDetailTbody').innerHTML = `<tr><td colspan="12" class="text-center py-4 text-danger fw-bold">Gagal menghubungi server (${err.message}). Silakan refresh halaman.</td></tr>`;
        }
    }

    fetchHaulingDetail();
}

function renderHaulingTable(rows) {
    const tbody = document.getElementById('haulingDetailTbody');
    document.getElementById('rowCountBadge').textContent = rows.length + ' Ritase';

    if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="12" class="text-center py-4 text-dark fw-bold">Tidak ada baris data yang cocok dengan pencarian.</td></tr>`;
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        let driverSub = '';
        if (r.substitute_name) {
            driverSub = `<small class="d-block fw-bold" style="color: #b45309; font-size:10.5px;"><i class="bi bi-person-fill-exclamation"></i> Pengganti: ${escapeHtml(r.substitute_name)}</small>`;
        }
        
        let mulaiTxt = r.jam_mulai ? r.jam_mulai.substring(0, 5) : '-';

        html += `<tr>
            <td class="text-center fw-bold" style="color: #0f172a;">${escapeHtml(r.ritase_ke || (idx + 1))}</td>
            <td>
                <div class="fw-bold" style="color: #0f172a;">${escapeHtml(r.driver_name || '-')}</div>
                <small style="color: #475569; font-weight: 700;">${escapeHtml(r.driver_nik || '-')}</small>
                ${driverSub}
            </td>
            <td class="text-center fw-bold" style="color: #1e40af;">${escapeHtml(r.unit || '-')}</td>
            <td class="text-end fw-bold" style="color: #0f172a;">${escapeHtml(r.tonase_formatted)}</td>
            <td class="text-end fw-bold" style="color: #047857;">${escapeHtml(r.insentif_tonase_formatted)}</td>
            <td class="text-center fw-bold" style="color: #1e40af;">${escapeHtml(r.hm_s1_formatted)}</td>
            <td class="text-center fw-bold" style="color: #991b1b;" title="Real: ${escapeHtml(r.ot_real_formatted)} | Efektif: ${escapeHtml(r.ot_eff_formatted)}">
                ${escapeHtml(r.ot_real_formatted)} <span class="badge badge-contrast-danger ms-1" style="font-size:9.5px;">Ef: ${escapeHtml(r.ot_eff_formatted)}</span>
            </td>
            <td class="text-end fw-bold" style="color: #334155;">${escapeHtml(r.insentif_hm_formatted)}</td>
            <td class="text-end fw-bold" style="color: #991b1b;">${escapeHtml(r.overtime_amount_formatted)}</td>
            <td class="text-center"><span class="badge badge-contrast-dark">${mulaiTxt}</span></td>
            <td class="text-center fw-bold" style="color: #334155; font-size:10.5px;">
                ${escapeHtml(r.durasi_step1)}j / ${escapeHtml(r.durasi_step2)}j / ${escapeHtml(r.durasi_step3)}j
            </td>
            <td class="text-end fw-bold" style="color: #0f172a;">${escapeHtml(r.total_line_formatted)}</td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function filterHaulingModalTable() {
    const q = (document.getElementById('haulingSearchInput').value || '').toLowerCase().trim();
    if (!q) {
        renderHaulingTable(currentHaulingRows);
        return;
    }

    const filtered = currentHaulingRows.filter(r => {
        const name = (r.driver_name || '').toLowerCase();
        const nik = (r.driver_nik || '').toLowerCase();
        const unit = (r.unit || '').toLowerCase();
        const sub = (r.substitute_name || '').toLowerCase();
        return name.includes(q) || nik.includes(q) || unit.includes(q) || sub.includes(q);
    });

    renderHaulingTable(filtered);
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
