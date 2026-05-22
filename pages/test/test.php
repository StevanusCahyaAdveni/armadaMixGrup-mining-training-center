<?php
include 'functions/pagination.php';

$search = isset($_GET['search']) ? sani($_GET['search']) : '';
$whereClause = '';
if (!empty($search)) {
    $searchWildcard = '%' . $search . '%';
    $whereClause = " WHERE t.title LIKE '$searchWildcard' OR c.category_title LIKE '$searchWildcard'";
}

$query = "SELECT t.*, c.category_title, (SELECT COUNT(*) FROM test_questions q WHERE q.test_id = t.id) AS total_questions FROM tests t LEFT JOIN test_categorys c ON t.category_id = c.id" . $whereClause . " ORDER BY t.created_at DESC";
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
    <!-- Action Buttons -->
    <p align="right">
        <button type="button" class="btn shadow-sm btn-sm btn-primary " data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Add New
        </button>
    </p>
    <section class="section">
        <!-- Search Form -->
        <div class="card p-2 mb-1 shadow-sm">
            <form method="GET" action="">
                <input type="hidden" name="hal" value="test_test">
                <div class="row g-1">
                    <div class="col-10">
                        <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="card p-2 mb-1 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-striped" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Test Title</th>
                            <th>Category</th>
                            <th>Test Type</th>
                            <th>Answer Time (Minutes)</th>
                            <th>Point Show (True/False)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($pagination['data'] as $row): ?>
                            <tr class="pt-1 pb-1">
                                <td><?= $no++ ?></td>
                                <td>
                                    <?= htmlspecialchars($row['title']) ?>
                                    <br>
                                    <span class="badge bg-info text-white" style="font-size: 10px;"><?= (int)$row['total_questions'] ?> Soal</span>
                                </td>
                                <td><?= htmlspecialchars($row['category_title'] ?? $row['category_id']) ?></td>
                                <td><?= htmlspecialchars($row['type']) ?></td>
                                <td><?= htmlspecialchars($row['answer_time']) ?> mins</td>
                                <td><?= htmlspecialchars($row['point_show']) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-success copy-link-btn" data-id="<?= $row['id'] ?>" title="Copy Share Link">
                                        <i class="bi bi-share"></i>
                                    </button>
                                    <a href="?hal=test_test-question&id=<?= $row['id'] ?>" class="btn btn-sm btn-info text-white" title="View Questions">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                        '<?= $row['id'] ?>',
                                        '<?= addslashes(htmlspecialchars($row['title'])) ?>',
                                        '<?= addslashes(htmlspecialchars($row['category_id'])) ?>',
                                        '<?= addslashes(htmlspecialchars($row['type'])) ?>',
                                        '<?= addslashes(htmlspecialchars($row['answer_time'])) ?>',
                                        '<?= addslashes(htmlspecialchars($row['point_show'])) ?>'
                                    )" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="actions/?hal=test_test&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Test Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php
                            $catsRes = querySecure($con, "SELECT id, category_title FROM test_categorys ORDER BY category_title ASC");
                            while ($cat = mysqli_fetch_assoc($catsRes)) {
                                echo "<option value='".htmlspecialchars($cat['id'])."'>".htmlspecialchars($cat['category_title'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Test Type</label>
                        <select class="form-select" name="type" required>
                            <option value="post test" selected>Post Test</option>
                            <option value="pre test">Pre Test</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Answer Time (Minutes)</label>
                        <input type="number" class="form-control" name="answer_time" value="45" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Point Show</label>
                        <select class="form-select" name="point_show" required>
                            <option value="true" selected>True</option>
                            <option value="false">False</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="addData" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Test</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Test Title</label>
                        <input type="text" class="form-control" name="title" id="edit_title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="edit_category_id" required>
                            <option value="">-- Select Category --</option>
                            <?php
                            $catsRes = querySecure($con, "SELECT id, category_title FROM test_categorys ORDER BY category_title ASC");
                            while ($cat = mysqli_fetch_assoc($catsRes)) {
                                echo "<option value='".htmlspecialchars($cat['id'])."'>".htmlspecialchars($cat['category_title'])."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Test Type</label>
                        <select class="form-select" name="type" id="edit_type" required>
                            <option value="post test">Post Test</option>
                            <option value="pre test">Pre Test</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Answer Time (Minutes)</label>
                        <input type="number" class="form-control" name="answer_time" id="edit_answer_time" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Point Show</label>
                        <select class="form-select" name="point_show" id="edit_point_show" required>
                            <option value="true">True</option>
                            <option value="false">False</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="updateData" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function upData(id, title, category_id, type, answer_time, point_show) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_category_id').value = category_id;
    document.getElementById('edit_type').value = type;
    document.getElementById('edit_answer_time').value = answer_time;
    document.getElementById('edit_point_show').value = point_show;
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Copy Link Handler
document.addEventListener('click', function(e) {
    if (e.target && (e.target.matches('.copy-link-btn') || e.target.closest('.copy-link-btn'))) {
        const btn = e.target.matches('.copy-link-btn') ? e.target : e.target.closest('.copy-link-btn');
        const testId = btn.getAttribute('data-id');
        
        // Construct full URL
        let path = window.location.pathname;
        let dir = path.substring(0, path.lastIndexOf('/'));
        let shareUrl = window.location.origin + dir + '/exam/?test_id=' + testId;
        
        // Copy to clipboard
        navigator.clipboard.writeText(shareUrl).then(function() {
            // Show feedback
            let originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            btn.classList.replace('btn-success', 'btn-light');
            
            alert('Link pengerjaan berhasil disalin ke clipboard!\n\nURL: ' + shareUrl);
            
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.classList.replace('btn-light', 'btn-success');
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy text: ', err);
            // Fallback for older browsers
            prompt('Salin link berikut untuk pengerjaan ujian:', shareUrl);
        });
    }
});
</script>
