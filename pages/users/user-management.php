<div>
    <?php
    include 'functions/pagination.php';
    echo showAlert();

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    if (!in_array($limit, [10, 25, 50, 100])) {
        $limit = 25;
    }
    $bank_filter = isset($_GET['bank_filter']) ? sani($_GET['bank_filter']) : '';
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

    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h5>
            <small class="text-muted">Kelola akun pengguna, hak akses, dan pengaturan bank</small>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                <i class="bi bi-person-plus-fill me-1"></i> Tambah User
            </button>
        </div>
    </div>

    <!-- Modal Tambah User -->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white py-2">
                    <h5 class="modal-title fs-6" id="staticBackdropLabel"><i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/?hal=users_user-management" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control form-control-sm" placeholder="Nama Lengkap" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="Username" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Bank yang Digunakan</label>
                            <input type="text" name="bank" list="listBankIndonesia" class="form-control form-control-sm" placeholder="Pilih atau ketik nama bank (contoh: BCA, Mandiri, BRI...)">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Foto Profile</label>
                            <input type="file" name="photo_profile" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="addUser" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm p-2 mb-2 bg-light border">
        <form method="get" action="">
            <input type="hidden" name="hal" value="<?= htmlspecialchars(sani($_GET['hal'] ?? 'users_user-management')) ?>">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, username, email, bank..." value="<?php echo isset($_GET['search']) ? htmlspecialchars(sani($_GET['search'])) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
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
                    <?php if (!empty($_GET['search']) || !empty($bank_filter)): ?>
                        <a href="?hal=users_user-management" class="btn btn-sm btn-outline-secondary" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Sticky Bulk Action Bar -->
    <div id="bulkActionBar" class="card shadow-sm border-primary mb-2 bg-primary-subtle d-none" style="position: sticky; top: 10px; z-index: 1020; transition: all 0.3s ease;">
        <div class="card-body p-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary fs-6 px-3 py-2" id="bulkSelectedBadge">
                    <i class="bi bi-check2-square me-1"></i> <span id="selectedCountText">0</span> User Terpilih
                </span>
                <span class="small text-muted d-none d-md-inline"><i class="bi bi-info-circle me-1"></i>Tip: Tahan tombol <b>Shift</b> lalu klik checkbox lain untuk memilih banyak sekaligus.</span>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary bg-white" onclick="clearAllSelections()">
                    <i class="bi bi-x-circle me-1"></i> Batal Pilihan
                </button>
                <button type="button" class="btn btn-sm btn-success px-3 shadow-sm" onclick="openBulkBankModal()">
                    <i class="bi bi-bank2 me-1"></i> <b>Set Bank Masal</b>
                </button>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm p-2 mb-1">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle" id="tableUsers" style="font-size: 12.5px; white-space: nowrap;">
                <thead class="table-light">
                    <tr>
                        <th width="40" class="text-center">
                            <input type="checkbox" class="form-check-input cursor-pointer" id="checkAllUsers" title="Pilih Semua di Halaman Ini" onchange="toggleSelectAllUsers(this)">
                        </th>
                        <th width="50">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Bank</th>
                        <th class="text-center">Foto Profile</th>
                        <th class="text-center" width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $whereClause = '';

                    if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                        $search = sani(trim($_GET['search']));
                        $whereClause .= " AND (fullname LIKE '%$search%' OR username LIKE '%$search%' OR email LIKE '%$search%' OR bank LIKE '%$search%')";
                    }

                    if (!empty($bank_filter)) {
                        if ($bank_filter === 'UNSET') {
                            $whereClause .= " AND (bank IS NULL OR bank = '' OR bank = '-')";
                        } else {
                            $whereClause .= " AND bank = '$bank_filter'";
                        }
                    }

                    $query = "SELECT * FROM users WHERE 1 = 1 " . $whereClause . " ORDER BY id DESC";

                    $pagination = makePagination($con, $query, $limit);
                    $no = $pagination['from'];

                    if (empty($pagination['data'])):
                    ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada data user yang ditemukan.</td>
                        </tr>
                    <?php
                    else:
                        foreach ($pagination['data'] as $data):
                            $userBank = trim($data['bank'] ?? '');
                    ?>
                        <tr id="row-<?= $data['id'] ?>">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input user-checkbox cursor-pointer" value="<?= htmlspecialchars($data['id']) ?>" data-name="<?= htmlspecialchars($data['fullname']) ?>" data-bank="<?= htmlspecialchars($userBank) ?>" onchange="onUserCheckboxChange()">
                            </td>
                            <td><?php echo $no++; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($data['fullname']); ?></td>
                            <td><code><?php echo htmlspecialchars($data['username']); ?></code></td>
                            <td><?php echo htmlspecialchars($data['email']); ?></td>
                            <td>
                                <?php if (!empty($userBank) && $userBank !== '-'): ?>
                                    <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-bank me-1"></i><?= htmlspecialchars($userBank) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border px-2 py-1">
                                        <i class="bi bi-dash-circle me-1"></i>Belum di-set
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (isset($data['photo_profile']) && !empty($data['photo_profile'])) { ?>
                                    <img src="<?php echo htmlspecialchars($data['photo_profile']); ?>" alt="Foto" class="rounded-circle border shadow-sm" width="32" height="32" style="object-fit: cover;">
                                <?php } else { ?>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-circle p-2"><i class="bi bi-person"></i></span>
                                <?php } ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-warning btn-sm shadow-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#editModal" onclick="upData('<?= htmlspecialchars($data['id']) ?>', '<?= htmlspecialchars(addslashes($data['fullname'])) ?>', '<?= htmlspecialchars(addslashes($data['username'])) ?>', '<?= htmlspecialchars(addslashes($data['email'])) ?>', '<?= htmlspecialchars(addslashes($data['password'])) ?>', '<?= htmlspecialchars(addslashes($userBank)) ?>')" title="Edit User">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($data['id'] != $_SESSION['admin']['id']) { ?>
                                    <a href="actions/?hal=users_user-management&deleteUser=<?= htmlspecialchars($data['id']) ?>" onclick="return confirm('Apakah yakin ingin menghapus user <?= htmlspecialchars(addslashes($data['fullname'])) ?>?')" class="btn btn-sm btn-danger shadow-sm py-0 px-2" title="Hapus User">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="card shadow-sm p-2 mb-3">
        <?= showPagination($pagination['total_pages'], $pagination['current_page']); ?>
    </div>

    <!-- Modal Edit User -->
    <div class="modal fade" id="editModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-warning text-dark py-2">
                    <h5 class="modal-title fs-6" id="editModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Data User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/?hal=users_user-management" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id_id">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="fullname" class="form-control form-control-sm" placeholder="Nama Lengkap" id="fullname_id" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="Username" id="username_id" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="Email" id="email_id" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Bank yang Digunakan</label>
                            <input type="text" name="bank" id="bank_id" list="listBankIndonesia" class="form-control form-control-sm" placeholder="Pilih atau ketik nama bank">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Password Baru</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Kosongkan jika password tidak diubah">
                            <input type="hidden" name="password_old" id="password_id">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Ganti Foto Profile</label>
                            <input type="file" name="photo_profile" class="form-control form-control-sm" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="updateUser" class="btn btn-sm btn-primary"><i class="bi bi-check-circle me-1"></i>Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Bulk Update Bank -->
    <div class="modal fade" id="modalBulkBank" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalBulkBankLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-success text-white py-2">
                    <h5 class="modal-title fs-6" id="modalBulkBankLabel">
                        <i class="bi bi-bank2 me-2"></i>Set Bank untuk User Terpilih
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actions/?hal=users_user-management" method="post" id="bulkBankForm">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3 shadow-sm" style="font-size: 13px;">
                            <i class="bi bi-info-circle-fill me-1"></i> Anda akan mengatur bank secara masal untuk <b id="bulkModalCount">0</b> user yang dipilih.
                        </div>

                        <!-- Container Hidden Inputs User IDs -->
                        <div id="bulkUserIdsContainer"></div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Pilih / Ketik Nama Bank <span class="text-danger">*</span></label>
                            <input type="text" name="bank" id="bulk_bank_input" list="listBankIndonesia" class="form-control" placeholder="Contoh: BCA, Mandiri, BRI, BNI, BSI..." required autocomplete="off">
                            <div class="form-text small">Pilih dari rekomendasi bank atau ketik nama bank baru.</div>
                        </div>

                        <!-- Quick Bank Buttons -->
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Pilihan Cepat:</label>
                            <div class="d-flex flex-wrap gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('BCA')">BCA</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('Mandiri')">Mandiri</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('BRI')">BRI</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('BNI')">BNI</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('BSI')">BSI</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('CIMB Niaga')">CIMB Niaga</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('Danamon')">Danamon</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('Permata')">Permata</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('Bank Jago')">Bank Jago</button>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0" onclick="setQuickBank('SeaBank')">SeaBank</button>
                            </div>
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
</div>

<script>
function upData(id, fullname, username, email, password, bank) {
    document.getElementById('id_id').value = id;
    document.getElementById('fullname_id').value = fullname;
    document.getElementById('username_id').value = username;
    document.getElementById('email_id').value = email;
    document.getElementById('password_id').value = password;
    document.getElementById('bank_id').value = bank ? bank : '';
}

function setQuickBank(bankName) {
    document.getElementById('bulk_bank_input').value = bankName;
}

// Shift + Click Checkbox Selection Logic
let lastCheckedUserBox = null;

document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('click', function(e) {
            if (!lastCheckedUserBox) {
                lastCheckedUserBox = this;
                onUserCheckboxChange();
                return;
            }

            // If Shift key is pressed during click
            if (e.shiftKey) {
                const boxesArray = Array.from(document.querySelectorAll('.user-checkbox'));
                const start = boxesArray.indexOf(this);
                const end = boxesArray.indexOf(lastCheckedUserBox);

                const [min, max] = [Math.min(start, end), Math.max(start, end)];

                for (let i = min; i <= max; i++) {
                    boxesArray[i].checked = lastCheckedUserBox.checked;
                }
            }

            lastCheckedUserBox = this;
            onUserCheckboxChange();
        });
    });
});

function toggleSelectAllUsers(masterCheckbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = masterCheckbox.checked;
    });
    onUserCheckboxChange();
}

function onUserCheckboxChange() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const checked = document.querySelectorAll('.user-checkbox:checked');
    const masterCheckbox = document.getElementById('checkAllUsers');
    const bulkBar = document.getElementById('bulkActionBar');
    const selectedCountText = document.getElementById('selectedCountText');

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

function clearAllSelections() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    const masterCheckbox = document.getElementById('checkAllUsers');
    if (masterCheckbox) {
        masterCheckbox.checked = false;
        masterCheckbox.indeterminate = false;
    }
    onUserCheckboxChange();
}

function openBulkBankModal() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    if (checked.length === 0) {
        alert('Silakan pilih minimal 1 user terlebih dahulu.');
        return;
    }

    const container = document.getElementById('bulkUserIdsContainer');
    container.innerHTML = '';

    checked.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'user_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkModalCount').innerText = checked.length;
    document.getElementById('bulk_bank_input').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalBulkBank'));
    modal.show();
}
</script>