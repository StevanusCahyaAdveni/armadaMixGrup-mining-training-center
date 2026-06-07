<?php
$start_date = isset($_GET['start_date']) ? sani($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sani($_GET['end_date']) : date('Y-m-t');
$search = isset($_GET['search']) ? sani($_GET['search']) : '';

$whereClause = "WHERE t.tanggal BETWEEN '$start_date' AND '$end_date'";
if (!empty($search)) {
    $whereClause .= " AND (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%')";
}

// Get global HM Rate just for reference
$rateQuery = mysqli_query($con, "SELECT setting_value FROM settings WHERE setting_key = 'tarif_hm'");
$rateRow = mysqli_fetch_assoc($rateQuery);
$global_rate = isset($rateRow['setting_value']) ? number_format($rateRow['setting_value'], 0, ',', '.') : '0';

// Main Query: Group by employee to get total HMC, Total HM Incentive
$query = "SELECT 
            e.id as employee_id, 
            e.full_name, 
            e.employee_id as nik,
            e.gaji_pokok, 
            e.tunjangan_tetap,
            SUM(t.hmc) as total_hmc,
            SUM(t.earned_hm_incentive) as total_insentif_hm,
            SUM(t.ritase) as total_ritase,
            (SELECT SUM(CASE WHEN category = 'increasing' THEN value WHEN category = 'decreasing' THEN -value ELSE value END) 
             FROM employee_salary_increasing_decreasing s 
             WHERE s.user_id = e.id AND s.date BETWEEN '$start_date' AND '$end_date') as penambah_pengurang,
            (SELECT SUM(amount) 
             FROM employee_overtimes o 
             WHERE o.user_id = e.id AND DATE(o.overtime_start) BETWEEN '$start_date' AND '$end_date') as total_overtime
          FROM employees e
          LEFT JOIN employee_timesheets t ON e.id = t.employee_id AND t.tanggal BETWEEN '$start_date' AND '$end_date'
          GROUP BY e.id
          HAVING (e.full_name LIKE '%$search%' OR e.employee_id LIKE '%$search%')
          ORDER BY e.full_name ASC";

$result = mysqli_query($con, $query);
?>

<div class="page-heading">
    <!-- <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3>Rekap Gaji (Payroll)</h3>
            <p class="text-muted mb-0">Tarif HM Global Saat Ini: <b>Rp <?= $global_rate ?> / HM</b></p>
        </div>
        <button class="btn btn-sm btn-success shadow-sm" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div> -->

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
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Nama" value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Result Card -->
        <div class="card p-3 shadow-sm">
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
                            <th class="text-end">Gaji Pokok</th>
                            <th class="text-end">Tunjangan Tetap</th>
                            <th class="text-center">Total HMC</th>
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
                                <td class="text-end">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></td>
                                <td class="text-end">Rp <?= number_format($tunjangan, 0, ',', '.') ?></td>
                                <td class="text-center fw-bold text-primary"><?= number_format($hmc, 2, ',', '.') ?> H</td>
                                <td class="text-end">Rp <?= number_format($insentif_hm, 0, ',', '.') ?></td>
                                <td class="text-end">
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalOvertime" onclick="openOvertimeModal('<?= $row['employee_id'] ?>')">
                                        Rp <?= number_format($total_overtime, 0, ',', '.') ?>
                                    </a>
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
                            <td colspan="9" class="text-end">GRAND TOTAL PENGGAJIAN</td>
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

<script>
function openIncDecModal(userId) {
    document.getElementById('iframeIncDec').src = "?hal=employee_employee-salary-increasing-decreasing&user_id=" + userId + "&iframe=1";
}
function openOvertimeModal(userId) {
    document.getElementById('iframeOvertime').src = "?hal=employee_employee-overtime&user_id=" + userId + "&iframe=1";
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
