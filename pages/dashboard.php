<?php
/**
 * View: Dashboard
 * Path: pages/dashboard.php
 */

// Count Users (Candidates & Admins)
$userCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM users");
$userCount = $userCountRes ? mysqli_fetch_assoc($userCountRes)['count'] : 0;

// Count Tests
$testCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM tests");
$testCount = $testCountRes ? mysqli_fetch_assoc($testCountRes)['count'] : 0;

// Count Questions
$questionCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM test_questions");
$questionCount = $questionCountRes ? mysqli_fetch_assoc($questionCountRes)['count'] : 0;

// Count Sessions
$sessionCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM test_user_sessions");
$sessionCount = $sessionCountRes ? mysqli_fetch_assoc($sessionCountRes)['count'] : 0;

// Active vs Completed
$activeSessionRes = querySecure($con, "SELECT COUNT(*) AS count FROM test_user_sessions WHERE status = 'active'");
$activeCount = $activeSessionRes ? mysqli_fetch_assoc($activeSessionRes)['count'] : 0;

$completedSessionRes = querySecure($con, "SELECT COUNT(*) AS count FROM test_user_sessions WHERE status = 'submitted'");
$completedCount = $completedSessionRes ? mysqli_fetch_assoc($completedSessionRes)['count'] : 0;

// Roles Breakdown for Candidates
$recruitmentCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM users WHERE role = 'rekrutmen'");
$recruitmentCount = $recruitmentCountRes ? mysqli_fetch_assoc($recruitmentCountRes)['count'] : 0;

$employeeCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM users WHERE role = 'karyawan'");
$employeeCount = $employeeCountRes ? mysqli_fetch_assoc($employeeCountRes)['count'] : 0;

// Get latest 5 sessions
$latestSessions = [];
$sessionsQuery = querySecure($con, 
    "SELECT s.id AS session_id, s.datetime_start, s.status, s.updated_at, t.title AS test_title, u.fullname AS user_fullname, u.email AS user_email, u.role AS user_role, s.test_id
     FROM test_user_sessions s
     LEFT JOIN tests t ON s.test_id = t.id
     LEFT JOIN users u ON s.user_id = u.id
     ORDER BY s.datetime_start DESC LIMIT 5"
);
if ($sessionsQuery) {
    while ($row = mysqli_fetch_assoc($sessionsQuery)) {
        // Calculate score dynamically (using our bug-free query joining true choices only)
        $scoreRes = querySecure($con, 
            "SELECT SUM(CAST(c.point AS UNSIGNED)) AS total_score 
             FROM test_user_answers a 
             JOIN test_question_choices c ON a.choice_id = c.id 
             WHERE a.user_session_id = ? AND c.choice_true = 'true'", 
            [$row['session_id']], 
            's'
        );
        $score = 0;
        if ($scoreRes && $scoreRow = mysqli_fetch_assoc($scoreRes)) {
            $score = intval($scoreRow['total_score'] ?? 0);
        }

        $maxScoreRes = querySecure($con,
            "SELECT SUM(CAST(c.point AS UNSIGNED)) AS max_score
             FROM test_question_choices c
             JOIN test_questions q ON c.question_id = q.id
             WHERE q.test_id = ? AND c.choice_true = 'true'",
            [$row['test_id']],
            's'
        );
        $maxScore = 0;
        if ($maxScoreRes && $maxScoreRow = mysqli_fetch_assoc($maxScoreRes)) {
            $maxScore = intval($maxScoreRow['max_score'] ?? 0);
        }

        $row['score'] = $score;
        $row['max_score'] = $maxScore;
        $latestSessions[] = $row;
    }
}

// Get latest 5 logs
$latestLogs = [];
$logsQuery = querySecure($con, "SELECT * FROM logs ORDER BY timestamp DESC LIMIT 5");
if ($logsQuery) {
    while ($row = mysqli_fetch_assoc($logsQuery)) {
        $latestLogs[] = $row;
    }
}

// Daily registration trend for ApexCharts over last 7 days
$chartDays = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $dateLabel = date('D, d M', strtotime("-$i days"));
    $chartDays[] = $dateLabel;
    
    $dailyCountRes = querySecure($con, "SELECT COUNT(*) AS count FROM test_user_sessions WHERE DATE(datetime_start) = ?", [$dateStr], 's');
    $chartData[] = $dailyCountRes ? intval(mysqli_fetch_assoc($dailyCountRes)['count'] ?? 0) : 0;
}

// Determine greeting based on current local hour
$hour = date('H');
if ($hour < 11) {
    $greeting = "Selamat Pagi";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
} elseif ($hour < 19) {
    $greeting = "Selamat Sore";
} else {
    $greeting = "Selamat Malam";
}
?>

<style>
    .welcome-card {
        background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
        border-radius: 20px;
        color: #ffffff;
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        overflow: hidden;
        position: relative;
        z-index: 1;
    }
    .welcome-card::after {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        bottom: -100px;
        right: -100px;
        z-index: -1;
    }
    .metric-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #ffffff;
        overflow: hidden;
    }
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.05) !important;
    }
    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    .avatar-sm {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }
</style>

<div class="row">
    <!-- Welcome Banner -->
    <div class="col-12 mb-4">
        <div class="card welcome-card p-4 p-md-5 border-0">
            <div class="row align-items-center">
                <div class="col-md-8 col-12">
                    <h3 class="fw-bold mb-2 text-white"><?= $greeting ?>, <?= htmlspecialchars($_SESSION['admin']['fullname'] ?? 'Administrator') ?>!</h3>
                    <p class="text-white text-opacity-75 mb-0" style="font-size: 14px; max-width: 600px;">
                        Selamat datang di Mining Training Center (MTC) Dashboard. Kelola paket ujian, review lembar jawaban kandidat rekrutmen/karyawan secara dinamis, dan tinjau performa peserta CBT secara real-time.
                    </p>
                </div>
                <div class="col-md-4 col-12 text-md-end text-start mt-3 mt-md-0">
                    <div class="badge bg-white bg-opacity-25 text-primary fs-6 py-2.5 px-3.5 rounded-pill">
                        <i class="bi bi-calendar3 me-2"></i> <?= date('d M Y') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <!-- Bank Soal -->
    <div class="col-xl-3 col-md-6 col-12 mb-4">
        <div class="card metric-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Bank Soal</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $questionCount ?></h3>
                    <span class="text-success" style="font-size: 11px; font-weight: 500;"><i class="bi bi-check-circle-fill"></i> Terintegrasi</span>
                </div>
                <div class="icon-box bg-success bg-opacity-10 text-success">
                    <i class="bi bi-database-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Paket Tes -->
    <div class="col-xl-3 col-md-6 col-12 mb-4">
        <div class="card metric-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Paket Ujian</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $testCount ?></h3>
                    <span class="text-primary" style="font-size: 11px; font-weight: 500;"><i class="bi bi-clipboard-check-fill"></i> Terdaftar</span>
                </div>
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-clipboard2-check-fill"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Sesi Berjalan (Active Sessions) -->
    <div class="col-xl-3 col-md-6 col-12 mb-4">
        <div class="card metric-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Sesi Berjalan</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $activeCount ?></h3>
                    <span class="text-warning" style="font-size: 11px; font-weight: 600; text-transform: uppercase;"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> Aktif CBT</span>
                </div>
                <div class="icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-activity"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Kandidat Terdaftar -->
    <div class="col-xl-3 col-md-6 col-12 mb-4">
        <div class="card metric-card shadow-sm p-3 h-100">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted d-block mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase;">Kandidat</span>
                    <h3 class="fw-bold mb-0 text-dark"><?= $userCount ?></h3>
                    <span class="text-info" style="font-size: 11px; font-weight: 500;"><i class="bi bi-person-fill-check"></i> Peserta Ujian</span>
                </div>
                <div class="icon-box bg-info bg-opacity-10 text-info">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="col-lg-8 col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-graph-up text-primary me-2"></i>Tren Sesi Ujian CBT</h5>
            <p class="text-muted mb-3" style="font-size: 12px;">Jumlah peserta yang memulai ujian baru selama 7 hari terakhir.</p>
            <div id="chart-sessions-trend" style="min-height: 280px;"></div>
        </div>
    </div>
    <div class="col-lg-4 col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-pie-chart text-primary me-2"></i>Peran Kandidat</h5>
            <p class="text-muted mb-3" style="font-size: 12px;">Perbandingan peserta Rekrutmen vs Karyawan.</p>
            <div id="chart-roles-donut" style="min-height: 280px;"></div>
        </div>
    </div>

    <!-- Recent Submissions & System Logs -->
    <div class="col-xl-7 col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Pengerjaan Ujian Terbaru</h5>
                    <p class="text-muted mb-0" style="font-size: 12px;">Daftar 5 sesi pengerjaan ujian terbaru oleh peserta.</p>
                </div>
                <a href="?hal=test_test-user-session" class="btn btn-sm btn-light rounded-pill px-3 shadow-sm" style="font-size: 11.5px;">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle" style="font-size: 12px; margin-bottom: 0;">
                    <thead class="table-light">
                        <tr style="font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.03em;">
                            <th>Kandidat</th>
                            <th>Ujian</th>
                            <th>Skor / Status</th>
                            <th>Mulai</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($latestSessions)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-journal-x fs-3 d-block mb-1"></i> Belum ada aktivitas ujian terkini.
                                </td>
                            </tr>
                        <?php else: 
                            foreach ($latestSessions as $sess):
                                $roleBadge = $sess['user_role'] === 'rekrutmen' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary';
                                $statusBadge = $sess['status'] === 'active' ? 'bg-primary text-white' : 'bg-success bg-opacity-10 text-success border border-success';
                                $statusText = $sess['status'] === 'active' ? 'Aktif' : 'Selesai';
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($sess['user_fullname']) ?></div>
                                        <span class="badge <?= $roleBadge ?> py-0.5 px-2 text-capitalize" style="font-size: 9px;"><?= htmlspecialchars($sess['user_role']) ?></span>
                                    </td>
                                    <td class="fw-semibold text-dark text-truncate" style="max-width: 150px;"><?= htmlspecialchars($sess['test_title'] ?? 'Ujian') ?></td>
                                    <td>
                                        <?php if ($sess['status'] === 'active'): ?>
                                            <span class="badge rounded-pill <?= $statusBadge ?> px-2.5 py-1" style="font-size: 9.5px;"><i class="bi bi-clock-fill me-0.5"></i> <?= $statusText ?></span>
                                        <?php else: ?>
                                            <div class="fw-bold text-success" style="font-size: 13px;">
                                                <?= $sess['score'] ?> <span class="text-muted" style="font-size: 10px;">/ <?= $sess['max_score'] ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 11px; white-space: nowrap;"><?= date('d M, H:i', strtotime($sess['datetime_start'])) ?></td>
                                    <td class="text-center">
                                        <a href="?hal=test_test-user-session&session_id=<?= $sess['session_id'] ?>" class="btn btn-xs btn-outline-primary py-1 px-2.5 rounded-pill" style="font-size: 10px;">Detail</a>
                                    </td>
                                </tr>
                            <?php endforeach; 
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- System Logs Card -->
    <div class="col-xl-5 col-12 mb-4">
        <div class="card shadow-sm border-0 rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold text-dark mb-1"><i class="bi bi-journal-text text-primary me-2"></i>Log Aktivitas Terkini</h5>
            <p class="text-muted mb-3" style="font-size: 12px;">Riwayat tindakan operasional sistem oleh tim admin.</p>
            <div class="list-group list-group-flush" style="font-size: 11.5px;">
                <?php if (empty($latestLogs)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-shield-slash fs-3 d-block mb-1"></i> Belum ada rekaman log sistem.
                    </div>
                <?php else:
                    foreach ($latestLogs as $log): 
                        // Find a nice icon and color based on log description
                        $iconClass = 'bi-info-circle text-primary bg-primary-subtle';
                        $desc = $log['description'];
                        if (stripos($desc, 'delete') !== false) {
                            $iconClass = 'bi-trash text-danger bg-danger-subtle';
                        } elseif (stripos($desc, 'add') !== false || stripos($desc, 'insert') !== false) {
                            $iconClass = 'bi-plus-circle text-success bg-success-subtle';
                        } elseif (stripos($desc, 'update') !== false) {
                            $iconClass = 'bi-pencil text-warning bg-warning-subtle';
                        }
                    ?>
                        <div class="list-group-item px-0 py-2.5 border-0 d-flex align-items-start gap-2.5">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; flex-shrink: 0; font-size: 13px; background-color: rgba(var(--bs-primary-rgb), 0.1);">
                                <i class="bi <?= explode(' ', $iconClass)[0] ?> <?= explode(' ', $iconClass)[1] ?>"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="text-dark fw-bold text-truncate"><?= htmlspecialchars($log['user']) ?></div>
                                <div class="text-muted text-break" style="font-size: 11px; line-height: 1.3;"><?= htmlspecialchars($log['description']) ?></div>
                            </div>
                            <div class="text-end" style="flex-shrink: 0; font-size: 10px;">
                                <span class="text-muted d-block"><?= date('H:i', strtotime($log['timestamp'])) ?></span>
                                <span class="text-muted badge bg-light text-secondary px-1 py-0" style="font-size: 9px;"><?= htmlspecialchars($log['ip_address']) ?></span>
                            </div>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Render Dynamically Configured Charts -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Trend Sesi Ujian CBT (Area Chart)
        var optionsSessionsTrend = {
            series: [{
                name: 'Sesi Ujian Baru',
                data: <?= json_encode($chartData) ?>
            }],
            chart: {
                height: 280,
                type: 'area',
                toolbar: {
                    show: false
                },
                zoom: {
                    enabled: false
                }
            },
            colors: ['#3b82f6'],
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 4
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: <?= json_encode($chartDays) ?>,
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                },
                labels: {
                    style: {
                        colors: '#64748b',
                        fontFamily: 'Outfit, sans-serif',
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                min: 0,
                tickAmount: 4,
                labels: {
                    style: {
                        colors: '#64748b',
                        fontFamily: 'Outfit, sans-serif',
                        fontSize: '11px'
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM'
                }
            }
        };
        var chartSessionsTrend = new ApexCharts(document.querySelector("#chart-sessions-trend"), optionsSessionsTrend);
        chartSessionsTrend.render();

        // Distribusi Peran Kandidat (Donut Chart)
        var optionsRolesDonut = {
            series: [<?= $recruitmentCount ?>, <?= $employeeCount ?>],
            labels: ['Rekrutmen', 'Karyawan'],
            colors: ['#06b6d4', '#64748b'],
            chart: {
                type: 'donut',
                width: '100%',
                height: 280
            },
            stroke: {
                width: 0
            },
            legend: {
                position: 'bottom',
                fontSize: '12px',
                fontFamily: 'Outfit, sans-serif',
                labels: {
                    colors: '#475569'
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px',
                                fontFamily: 'Outfit, sans-serif',
                                color: '#64748b'
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontFamily: 'Outfit, sans-serif',
                                color: '#1e293b',
                                fontWeight: '700',
                                formatter: function (val) {
                                    return val;
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total Peserta',
                                color: '#94a3b8',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        };
        var chartRolesDonut = new ApexCharts(document.querySelector("#chart-roles-donut"), optionsRolesDonut);
        chartRolesDonut.render();
    });
</script>
