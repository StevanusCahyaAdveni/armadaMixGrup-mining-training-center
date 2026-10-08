<?php
$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-t');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';

$whereClause = "WHERE t.tanggal BETWEEN '$start_date' AND '$end_date'";
if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%')";
}

// Get global HM Rate & Overtime Rate from settings
$rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
$rateRow = mysqli_fetch_assoc($rateQuery);
$tarif_hm = isset($rateRow['setting_value']) ? (float) $rateRow['setting_value'] : 17000;
$global_rate = number_format($tarif_hm, 0, ',', '.');

$rateOtQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_lembur'");
$rateOtRow = mysqli_fetch_assoc($rateOtQuery);
$tarif_lembur = isset($rateOtRow['setting_value']) ? (float) $rateOtRow['setting_value'] : 19509;
$global_ot_rate = number_format($tarif_lembur, 0, ',', '.');

$s1_7h_nominal = 7 * $tarif_hm;
$s2_2h_hm = 2 * $tarif_hm;
$s2_2h_ot = 2 * $tarif_lembur;
$day_7h_2h_total = $s1_7h_nominal + $s2_2h_hm + $s2_2h_ot;

$s2_4h_hm = 4 * $tarif_hm;
$s2_4h_ot = 4 * $tarif_lembur;
$day_7h_4h_total = $s1_7h_nominal + $s2_4h_hm + $s2_4h_ot;

// Main Query: Group by employee to get total HMC, Total HM Incentive
$query = "SELECT 
            e.id as employee_id, 
            e.full_name, 
            e.employee_id as nik,
            e.gaji_pokok, 
            e.tunjangan_tetap,
            SUM(t.hmc) as total_hmc,
            SUM(CASE WHEN t.shift_type = '1' THEN t.hmc ELSE 0 END) as hmc_s1,
            SUM(CASE WHEN t.shift_type = '2' THEN t.hmc ELSE 0 END) as hmc_s2,
            SUM(t.earned_hm_incentive) as total_insentif_hm,
            SUM(t.ritase) as total_ritase,
            (SELECT SUM(CASE WHEN category = 'increasing' THEN value WHEN category = 'decreasing' THEN -value ELSE value END) 
             FROM employee_salary_increasing_decreasing s 
             WHERE s.user_id = e.id AND s.date BETWEEN '$start_date' AND '$end_date') as penambah_pengurang,
            (SELECT SUM(overtime_amount) 
             FROM employee_timesheets ts 
             WHERE ts.employee_id = e.id AND ts.tanggal BETWEEN '$start_date' AND '$end_date') as total_overtime
          FROM employees e
          LEFT JOIN employee_timesheets t ON e.id = t.employee_id AND t.tanggal BETWEEN '$start_date' AND '$end_date'
          GROUP BY e.id
          HAVING (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%')
          ORDER BY e.full_name ASC";

$result = mysqli_query($con, $query);
?>

<div class="page-heading">
    <!-- Info Banner Aturan Kerja & Penggajian Baru -->
    <div class="card border-0 shadow-sm mb-3 bg-light d-print-none">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary px-3 py-2 fs-6">Skema Penggajian Unit Lapangan</span>
                    <span class="badge bg-success px-3 py-2 fs-6">Tarif HM: Rp <?= $global_rate ?> / HM</span>
                    <span class="badge bg-warning text-dark px-3 py-2 fs-6">Tarif Lembur (OT): Rp <?= $global_ot_rate ?> / Jam</span>
                </div>
                <button class="btn btn-sm btn-outline-success shadow-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>
            <div class="row g-2 text-muted" style="font-size: 12.5px;">
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white">
                        <b>Shift Kerja Operasional:</b><br>
                        • Pagi: <b>07.00 - 17.00</b> (Istirahat 12.00-13.00)<br>
                        • Malam: <b>19.00 - 05.00</b> (Istirahat 00.00-01.00)
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white">
                        <b>Skema Perhitungan Dinamis:</b><br>
                        • Shift 1 (Pokok): <b>Jam Kerja &times; Rp <?= $global_rate ?></b> (OT = Rp 0)<br>
                        • Shift 2 (Lembur): <b>(Jam &times; Rp <?= $global_rate ?>) + (Jam &times; Rp <?= $global_ot_rate ?>)</b>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white">
                        <b>Simulasi Harian Operator:</b><br>
                        • S1 7H + S2 4H: <b>Rp <?= number_format($day_7h_4h_total, 0, ',', '.') ?></b> (HM Rp 187k + OT Rp <?= number_format($s2_4h_ot, 0, ',', '.') ?>)<br>
                        • S1 7H + S2 2H: <b>Rp <?= number_format($day_7h_2h_total, 0, ',', '.') ?></b> (HM Rp 153k + OT Rp <?= number_format($s2_2h_ot, 0, ',', '.') ?>)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="section">
        <!-- Filter Card -->
        <div class="card p-3 mb-3 shadow-sm d-print-none">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="employee_payroll">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Mulai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="<?= $start_date ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Sampai Tanggal</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="<?= $end_date ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Cari Karyawan</label>
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Nama..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Result Card -->
        <div class="card p-2 shadow-sm">
            <div class="mb-3 text-center d-none d-print-block">
                <h4>Laporan Penggajian Karyawan</h4>
                <p>Periode: <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></p>
            </div>
            
            <div class="table-responsive">
                <table id="payrollTable" class="table table-bordered table-hover table-sm" style="font-size: 13px; white-space: nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>NIK</th>
                            <th>Nama Karyawan</th>
                            <!-- <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Tunjangan Tetap</th> -->
                            <th class="text-center">Total Jam Kerja</th>
                            <th class="text-end">Insentif HM</th>
                            <th class="text-end">Uang Lembur</th>
                            <th class="text-end">Penambah/Pengurang</th>
                            <th class="text-end bg-success text-white">Take Home Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $grand_total = 0;
                        if (mysqli_num_rows($result) == 0):
                        ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">Tidak ada data ditemukan pada rentang tanggal ini.</td>
                            </tr>
                        <?php
                        else:
                            while ($row = mysqli_fetch_assoc($result)):
                                $gaji_pokok = $row['gaji_pokok'];
                                $tunjangan = $row['tunjangan_tetap'];
                                $insentif_hm = $row['total_insentif_hm'] ? $row['total_insentif_hm'] : 0;
                                $hmc = $row['total_hmc'] ? $row['total_hmc'] : 0;
                                $penambah_pengurang = $row['penambah_pengurang'] ? $row['penambah_pengurang'] : 0;
                                $total_overtime = $row['total_overtime'] ? $row['total_overtime'] : 0;
                                
                                $take_home_pay = $gaji_pokok + $tunjangan + $insentif_hm + $penambah_pengurang + $total_overtime;
                                $grand_total += $take_home_pay;
                        ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nik']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></td>
                                <!-- <td class="text-end">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></td>
                                <td class="text-end">Rp <?= number_format($tunjangan, 0, ',', '.') ?></td> -->
                                <td class="text-center">
                                    <a href="#" class="fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#modalTimesheets" onclick="openTimesheetsModal('<?= $row['employee_id'] ?>')">
                                        <?= number_format($hmc, 2, ',', '.') ?> H
                                    </a>
                                    <?php if ($hmc > 0): ?>
                                    <div class="mt-1" style="font-size: 10.5px;">
                                        <span class="badge bg-secondary" title="Jam Kerja Shift 1 (Pokok)">S1: <?= number_format($row['hmc_s1'] ?? 0, 1, ',', '.') ?>H</span>
                                        <span class="badge bg-warning text-dark" title="Jam Kerja Shift 2 (Lembur)">S2: <?= number_format($row['hmc_s2'] ?? 0, 1, ',', '.') ?>H</span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">Rp <?= number_format($insentif_hm, 0, ',', '.') ?></td>
                                <td class="text-end">
                                    Rp <?= number_format($total_overtime, 0, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalIncDec" onclick="openIncDecModal('<?= $row['employee_id'] ?>')">
                                        <?= $penambah_pengurang < 0 ? '- Rp ' . number_format(abs($penambah_pengurang), 0, ',', '.') : 'Rp ' . number_format($penambah_pengurang, 0, ',', '.') ?>
                                    </a>
                                </td>
                                <td class="text-end fw-bold bg-light text-success">Rp <?= number_format($take_home_pay, 0, ',', '.') ?></td>
                            </tr>
                        <?php 
                            endwhile; 
                        endif;
                        ?>
                    </tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="7" class="text-end">GRAND TOTAL PENGGAJIAN</td>
                            <td class="text-end text-success">Rp <?= number_format($grand_total, 0, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Modal Penambah/Pengurang -->
<div class="modal fade" id="modalIncDec" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Penambah/Pengurang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="iframeIncDec" src="" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Overtime -->
<div class="modal fade" id="modalOvertime" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Lembur Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="iframeOvertime" src="" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Modal Timesheets -->
<div class="modal fade" id="modalTimesheets" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Data HM (Timesheets) Karyawan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="iframeTimesheets" src="" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openIncDecModal(userId) {
    document.getElementById('iframeIncDec').src = "?hal=employee_employee-salary-increasing-decreasing&user_id=" + userId + "&iframe=1";
}
function openOvertimeModal(userId) {
    document.getElementById('iframeOvertime').src = "?hal=employee_employee-overtime&user_id=" + userId + "&iframe=1";
}
function openTimesheetsModal(userId) {
    document.getElementById('iframeTimesheets').src = "?hal=employee_timesheets&user_id=" + userId + "&iframe=1";
}
</script>

<!-- DataTables & Buttons CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css"/>

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<!-- Buttons & JSZip for Excel -->
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(document).ready(function() {
    $('#payrollTable').DataTable({
        paging: false,
        searching: false,
        info: false,
        ordering: false,
        dom: '<"mb-3"B>rt',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i> Export Excel',
                className: 'btn btn-success btn-sm shadow-sm'
            }
        ]
    });
});
</script>
