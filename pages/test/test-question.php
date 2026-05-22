<?php
include 'functions/pagination.php';

$test_id = isset($_GET['id']) ? sani($_GET['id']) : '';
$search = isset($_GET['search']) ? sani($_GET['search']) : '';

$whereConditions = [];
if (!empty($test_id)) {
    $whereConditions[] = "q.test_id = '$test_id'";
}
if (!empty($search)) {
    $searchWildcard = '%' . $search . '%';
    $whereConditions[] = "(q.question LIKE '$searchWildcard' OR q.questions_material LIKE '$searchWildcard')";
}

$whereClause = '';
if (count($whereConditions) > 0) {
    $whereClause = " WHERE " . implode(" AND ", $whereConditions);
}

$query = "SELECT q.*, t.title as test_title FROM test_questions q LEFT JOIN tests t ON q.test_id = t.id" . $whereClause . " ORDER BY q.created_at DESC";
$pagination = makePagination($con, $query, 10);

// Fetch test title if test_id is set
$test_title = '';
if (!empty($test_id)) {
    $testResult = querySecure($con, "SELECT title FROM tests WHERE id = ?", [$test_id], 's');
    if ($testResult && $testRow = mysqli_fetch_assoc($testResult)) {
        $test_title = $testRow['title'];
    }
}
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
<div class="page-heading d-flex justify-content-between align-items-center mb-3">
    <div>
        <?php if (!empty($test_id)): ?>
            <!-- <h5 class="fw-bold mb-0">Test: <?= htmlspecialchars($test_title) ?></span> -->
        <?php endif; ?>
    </div>
    <div>
        <a href="?hal=test_test" class="btn btn-sm btn-dark"><i class="bi bi-arrow-left"></i></a>
        <button type="button" class="btn shadow-sm btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Add Questions
        </button>
    </div>
</div>

<section class="section">
    <!-- Search Form -->
    <div class="card p-2 mb-1 shadow-sm">
        <form method="GET" action="">
            <input type="hidden" name="hal" value="test_test-question">
            <?php if (!empty($test_id)): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($test_id) ?>">
            <?php endif; ?>
            <div class="row g-1">
                <div class="col-10">
                    <input type="text" class="form-control form-control-sm" name="search" placeholder="Search question or material..." value="<?= htmlspecialchars($search) ?>">
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
                        <th width="5%">No</th>
                        <th width="20%">Test Title</th>
                        <th>Question & Choices</th>
                        <th width="15%">Type</th>
                        <th width="15%">Material</th>
                        <th width="12%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    foreach ($pagination['data'] as $row): 
                        // Fetch choices if question type is Multiple Choice
                        $choicesList = [];
                        if ($row['question_type'] === 'Multiple Choice') {
                            $choicesRes = querySecure($con, "SELECT * FROM test_question_choices WHERE question_id = ? ORDER BY created_at ASC", [$row['id']], 's');
                            if ($choicesRes) {
                                while ($choiceRow = mysqli_fetch_assoc($choicesRes)) {
                                    $choicesList[] = $choiceRow;
                                }
                            }
                        }
                        // Fetch medias
                        $mediasList = [];
                        $mediasRes = querySecure($con, "SELECT * FROM test_question_medias WHERE question_id = ? ORDER BY created_at ASC", [$row['id']], 's');
                        if ($mediasRes) {
                            while ($mediaRow = mysqli_fetch_assoc($mediasRes)) {
                                $mediasList[] = $mediaRow;
                            }
                        }
                    ?>
                        <tr class="pt-1 pb-1">
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['test_title'] ?? $row['test_id']) ?></td>
                            <td>
                                <div class="fw-bold"><?= nl2br(htmlspecialchars($row['question'])) ?></div>
                                <?php if (!empty($choicesList)): ?>
                                    <div class="choices-list mt-2 ps-3 border-start border-primary border-3">
                                        <ul class="list-unstyled mb-0" style="font-size: 11px;">
                                            <?php foreach ($choicesList as $c): 
                                                $isCorrect = $c['choice_true'] === 'true';
                                            ?>
                                                <li class="mb-1 <?= $isCorrect ? 'text-success fw-bold' : '' ?>">
                                                    <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-secondary' ?> me-1" style="font-size: 9px;">
                                                        <?= $isCorrect ? '✓' : '•' ?>
                                                    </span>
                                                    <?= htmlspecialchars($c['choice_text']) ?>
                                                    <span class="text-muted">(Poin: <?= htmlspecialchars($c['point']) ?>)</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($mediasList)): ?>
                                    <div class="medias-list mt-2 ps-3 border-start border-warning border-3">
                                        <ul class="list-unstyled mb-0" style="font-size: 11px;">
                                            <?php foreach ($mediasList as $m): ?>
                                                <li class="mb-1">
                                                    <span class="badge bg-warning text-dark me-1" style="font-size: 9px;"><i class="bi bi-file-earmark"></i></span>
                                                    <a href="<?= htmlspecialchars($m['path']) ?>" target="_blank" class="text-decoration-none fw-bold text-dark">
                                                        <?= htmlspecialchars($m['media_name']) ?>
                                                    </a>
                                                    <span class="text-muted">(<?= htmlspecialchars($m['media_extendsion']) ?>)</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['question_type'] === 'Multiple Choice' ? 'bg-primary' : 'bg-warning' ?>">
                                    <?= htmlspecialchars($row['question_type']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['questions_material']) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" onclick="upData(
                                    '<?= $row['id'] ?>',
                                    '<?= htmlspecialchars($row['test_id']) ?>',
                                    '<?= addslashes(htmlspecialchars($row['question'])) ?>',
                                    '<?= htmlspecialchars($row['question_type']) ?>',
                                    '<?= addslashes(htmlspecialchars($row['questions_material'])) ?>'
                                )" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="actions/?hal=test_test-question&delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')" title="Delete">
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Test Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test-question" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <!-- Collapsible Plain Text Import Panel -->
                    <div class="mb-3">
                        <button class="btn btn-sm btn-outline-info w-100" type="button" data-bs-toggle="collapse" data-bs-target="#importTextCollapse" aria-expanded="false" aria-controls="importTextCollapse">
                            <i class="bi bi-file-earmark-arrow-up"></i> Import Questions from Plain Text
                        </button>
                        <div class="collapse mt-2" id="importTextCollapse">
                            <div class="card card-body p-3 bg-light border-info">
                                <label class="form-label fw-bold mb-1" style="font-size: 12px; color: #0d6efd;"><i class="bi bi-info-circle"></i> Paste Raw Text Questions Below</label>
                                <p class="text-muted mb-2" style="font-size: 10px; line-height: 1.4;">
                                    <strong>Format:</strong><br>
                                    Use <code>*</code> for questions, <code>-</code> for choice options. Mark correct options with <code>(true : points)</code>.<br>
                                    <em>Example:</em><br>
                                    <code>* Yang berfungsi untuk memisahkan air dengan bahan bakar adalah fungsi dari</code><br>
                                    <code>- jawaban 1</code><br>
                                    <code>- jawaban 2</code><br>
                                    <code>- jawaban 3 (true : 10)</code><br>
                                    <code>- jawaban 4</code>
                                </p>
                                <textarea class="form-control form-control-sm mb-2" id="raw_import_text" rows="8" placeholder="* Question Text&#10;- Option A&#10;- Option B (true : 10)&#10;- Option C&#10;- Option D"></textarea>
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-xs btn-outline-danger py-1" onclick="document.getElementById('raw_import_text').value = '';" style="font-size: 11px;">Clear Text</button>
                                    <button type="button" class="btn btn-xs btn-primary py-1 px-3" onclick="processImportText()" style="font-size: 11px;">Parse & Import to Cards</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="add_questions_container">
                        <!-- Question Card 0 (Default) -->
                        <div class="card border mb-3 shadow-sm question-card" id="question_card_0">
                            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
                                <strong style="font-size: 13px;"><i class="bi bi-question-circle"></i> Question #<span class="q-number-span">1</span></strong>
                                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-question-btn" onclick="removeQuestionCard(this)" style="font-size: 11px; display: none;">
                                    <i class="bi bi-trash"></i> Remove
                                </button>
                            </div>
                            <div class="card-body p-2">
                                <!-- Row 1: Test & Question Type -->
                                <div class="row g-1 mb-2">
                                    <div class="col-md-6 col-12">
                                        <label class="form-label mb-0" style="font-size: 11px;">Test</label>
                                        <?php if (!empty($test_id)): ?>
                                            <input type="text" class="form-control form-control-sm" value="<?= htmlspecialchars($test_title) ?>" readonly>
                                            <input type="hidden" name="questions[0][test_id]" value="<?= htmlspecialchars($test_id) ?>">
                                        <?php else: ?>
                                            <select class="form-select form-select-sm test-select-el" name="questions[0][test_id]" required>
                                                <option value="">-- Select Test --</option>
                                                <?php
                                                $testsRes = querySecure($con, "SELECT id, title FROM tests ORDER BY title ASC");
                                                if ($testsRes) {
                                                    while ($t = mysqli_fetch_assoc($testsRes)) {
                                                        echo "<option value='".htmlspecialchars($t['id'])."'>".htmlspecialchars($t['title'])."</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <label class="form-label mb-0" style="font-size: 11px;">Question Type</label>
                                        <select class="form-select form-select-sm question-type-select" name="questions[0][question_type]" onchange="toggleCardChoices(this, 0)" required>
                                            <option value="Multiple Choice" selected>Multiple Choice</option>
                                            <option value="Form">Form</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Row 2: Text Question -->
                                <div class="mb-2">
                                    <label class="form-label mb-0" style="font-size: 11px;">Text Question</label>
                                    <textarea class="form-control form-control-sm" name="questions[0][question]" rows="2" required></textarea>
                                </div>

                                <!-- Row 3: Material of Question (Optional) -->
                                <div class="mb-2">
                                    <label class="form-label mb-0" style="font-size: 11px;">Material of Question (Optional)</label>
                                    <input type="text" class="form-control form-control-sm" name="questions[0][questions_material]">
                                </div>

                                <!-- Row 4: Choices (Multiple Choice Options) -->
                                <div class="mb-2 border p-2 rounded bg-white choices-section" id="choices_section_0">
                                    <label class="form-label fw-bold mb-1" style="font-size: 11px;"><i class="bi bi-list-check"></i> Choices (Pilihan Ganda)</label>
                                    <p class="text-muted mb-2" style="font-size: 10px;">Beri tanda bulat pada opsi benar dan tentukan poinnya.</p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm mb-1" style="font-size: 11px;">
                                            <thead>
                                                <tr class="table-secondary text-center">
                                                    <th width="10%">True?</th>
                                                    <th>Choice Text</th>
                                                    <th width="20%">Point</th>
                                                </tr>
                                            </thead>
                                            <tbody class="choices-tbody" id="choices_tbody_0">
                                                <tr class="choice-row">
                                                    <td class="text-center align-middle">
                                                        <input class="form-check-input true-radio" type="radio" name="questions[0][choice_true_index]" value="0" checked>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm choice-text" name="questions[0][choices][0][text]" placeholder="Choice A" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="questions[0][choices][0][point]" value="10" min="0" required>
                                                    </td>
                                                </tr>
                                                <tr class="choice-row">
                                                    <td class="text-center align-middle">
                                                        <input class="form-check-input true-radio" type="radio" name="questions[0][choice_true_index]" value="1">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm choice-text" name="questions[0][choices][1][text]" placeholder="Choice B" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="questions[0][choices][1][point]" value="0" min="0" required>
                                                    </td>
                                                </tr>
                                                <tr class="choice-row">
                                                    <td class="text-center align-middle">
                                                        <input class="form-check-input true-radio" type="radio" name="questions[0][choice_true_index]" value="2">
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm choice-text" name="questions[0][choices][2][text]" placeholder="Choice C">
                                                            <button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="questions[0][choices][2][point]" value="0" min="0" required>
                                                    </td>
                                                </tr>
                                                <tr class="choice-row">
                                                    <td class="text-center align-middle">
                                                        <input class="form-check-input true-radio" type="radio" name="questions[0][choice_true_index]" value="3">
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <input type="text" class="form-control form-control-sm choice-text" name="questions[0][choices][3][text]" placeholder="Choice D">
                                                            <button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm" name="questions[0][choices][3][point]" value="0" min="0" required>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" onclick="addChoiceRowToQuestion(this)" style="font-size: 11px;"><i class="bi bi-plus-circle"></i> Add Choice Option</button>
                                </div>

                                <!-- Row 5: Media Files (Optional) -->
                                <div class="mb-2 border p-2 rounded bg-white">
                                    <label class="form-label fw-bold mb-1" style="font-size: 11px;"><i class="bi bi-file-earmark-medical"></i> Media Files (Optional)</label>
                                    <div class="medias-container" id="medias_container_0">
                                        <!-- Dynamic rows -->
                                    </div>
                                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" onclick="addMediaRowToQuestion(this)" style="font-size: 11px;"><i class="bi bi-plus-circle"></i> Add Media File</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQuestionCard()"><i class="bi bi-plus-circle"></i> Add Another Question</button>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Test Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/?hal=test_test-question" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Test</label>
                        <select class="form-select" name="test_id" id="edit_test_id" required>
                            <option value="">-- Select Test --</option>
                            <?php
                            $testsRes = querySecure($con, "SELECT id, title FROM tests ORDER BY title ASC");
                            if ($testsRes) {
                                while ($t = mysqli_fetch_assoc($testsRes)) {
                                    echo "<option value='".htmlspecialchars($t['id'])."'>".htmlspecialchars($t['title'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Text Question</label>
                        <textarea class="form-control" name="question" id="edit_question" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Question Type</label>
                        <select class="form-select" name="question_type" id="edit_question_type" onchange="toggleChoices(this.value, 'edit_choices_section')" required>
                            <option value="Multiple Choice">Multiple Choice</option>
                            <option value="Form">Form</option>
                        </select>
                    </div>
                    
                    <!-- Choices Section for Edit -->
                    <div id="edit_choices_section" class="mb-3 border p-3 rounded bg-light">
                        <label class="form-label fw-bold"><i class="bi bi-list-check"></i> Choices (Pilihan Ganda)</label>
                        <p class="text-muted mb-2" style="font-size: 11px;">Sesuaikan pilihan jawaban. Beri tanda bulat pada opsi yang benar dan tentukan poinnya.</p>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0" style="font-size: 12px;">
                                <thead>
                                    <tr class="table-secondary text-center">
                                        <th width="10%">True?</th>
                                        <th>Choice Text</th>
                                        <th width="20%">Point</th>
                                    </tr>
                                </thead>
                                <tbody id="edit_choices_tbody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addChoiceRow('edit_choices_tbody', 'edit')"><i class="bi bi-plus-circle"></i> Add Choice Option</button>
                    </div>

                    <!-- Media Section for Edit -->
                    <div class="mb-3 border p-3 rounded bg-light">
                        <label class="form-label fw-bold"><i class="bi bi-file-earmark-medical"></i> Media Files</label>
                        <p class="text-muted mb-2" style="font-size: 11px;">Kelola media saat ini atau tambahkan media baru.</p>
                        
                        <!-- Existing Medias -->
                        <div id="edit_existing_medias_container" class="mb-2">
                            <!-- Populated via AJAX -->
                        </div>
                        
                        <!-- New Medias -->
                        <div id="edit_new_medias_container" class="mb-2">
                            <!-- Dynamically added file rows -->
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addMediaRow('edit_new_medias_container', 'edit')"><i class="bi bi-plus-circle"></i> Add Media File</button>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Material Of Question</label>
                        <input type="text" class="form-control" name="questions_material" id="edit_questions_material">
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

<?php
$testsOptions = '';
$testsRes = querySecure($con, "SELECT id, title FROM tests ORDER BY title ASC");
if ($testsRes) {
    while ($t = mysqli_fetch_assoc($testsRes)) {
        $testsOptions .= "<option value='".htmlspecialchars($t['id'])."'>".htmlspecialchars($t['title'])."</option>";
    }
}
?>
<script>
const testsOptionsHtml = <?= json_encode($testsOptions) ?>;
const defaultTestId = <?= json_encode($test_id) ?>;
const defaultTestTitle = <?= json_encode($test_title) ?>;

function processImportText() {
    const rawText = document.getElementById('raw_import_text').value;
    if (!rawText.trim()) {
        alert('Please paste some text first.');
        return;
    }
    
    const lines = rawText.split('\n');
    const parsedQuestions = [];
    let currentQ = null;
    
    lines.forEach(line => {
        const trimmed = line.trim();
        if (trimmed.startsWith('*')) {
            // New Question
            const questionText = trimmed.substring(1).trim();
            if (questionText) {
                currentQ = {
                    question: questionText,
                    question_type: 'Multiple Choice',
                    choices: [],
                    choice_true_index: 0,
                    questions_material: ''
                };
                parsedQuestions.push(currentQ);
            }
        } else if (trimmed.startsWith('-') && currentQ) {
            // Choice Option
            let choiceText = trimmed.substring(1).trim();
            if (choiceText) {
                let isTrue = false;
                let point = 0;
                
                // Match (true : 10) or (true) or (true:10)
                const trueRegex = /\(true\s*(?::\s*(\d+))?\)/i;
                const match = choiceText.match(trueRegex);
                if (match) {
                    isTrue = true;
                    point = match[1] ? parseInt(match[1], 10) : 10;
                    choiceText = choiceText.replace(trueRegex, '').trim();
                }
                
                currentQ.choices.push({
                    text: choiceText,
                    point: point,
                    isTrue: isTrue
                });
                
                if (isTrue) {
                    currentQ.choice_true_index = currentQ.choices.length - 1;
                }
            }
        }
    });
    
    if (parsedQuestions.length === 0) {
        alert('No valid questions found. Make sure questions start with "*" and choices start with "-".');
        return;
    }
    
    const container = document.getElementById('add_questions_container');
    if (!container) return;
    
    // Check if the current first card is empty/default
    const cards = container.querySelectorAll('.question-card');
    const isFirstCardEmpty = cards.length === 1 && 
        !container.querySelector('textarea[name="questions[0][question]"]').value.trim();
        
    if (isFirstCardEmpty) {
        container.innerHTML = '';
    }
    
    parsedQuestions.forEach((pq) => {
        const existingCards = container.querySelectorAll('.question-card');
        const nextIndex = existingCards.length;
        
        const cardDiv = document.createElement('div');
        cardDiv.innerHTML = getQuestionCardHtml(nextIndex);
        const cardEl = cardDiv.firstElementChild;
        container.appendChild(cardEl);
        
        // Fill question text
        cardEl.querySelector('textarea[name*="[question]"]').value = pq.question;
        
        // Clear choices and populate dynamically
        const tbody = cardEl.querySelector('.choices-tbody');
        tbody.innerHTML = '';
        
        pq.choices.forEach((choice, cIndex) => {
            const label = String.fromCharCode(65 + cIndex);
            const tr = document.createElement('tr');
            tr.className = 'choice-row';
            tr.innerHTML = `
                <td class="text-center align-middle">
                    <input class="form-check-input true-radio" type="radio" name="questions[${nextIndex}][choice_true_index]" value="${cIndex}" ${choice.isTrue ? 'checked' : ''}>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control form-control-sm choice-text" name="questions[${nextIndex}][choices][${cIndex}][text]" placeholder="Choice ${label}" value="${escapeHtml(choice.text)}" required>
                        ${cIndex >= 2 ? `<button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>` : ''}
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm" name="questions[${nextIndex}][choices][${cIndex}][point]" value="${choice.point}" min="0" required>
                </td>
            `;
            tbody.appendChild(tr);
        });
    });
    
    reindexAllQuestions();
    
    // Clear raw input text and collapse panel
    document.getElementById('raw_import_text').value = '';
    const collapseEl = document.getElementById('importTextCollapse');
    const bsCollapse = bootstrap.Collapse.getInstance(collapseEl) || new bootstrap.Collapse(collapseEl);
    bsCollapse.hide();
    
    alert(`Successfully imported ${parsedQuestions.length} question(s) to the form cards!`);
}


function getQuestionCardHtml(index) {
    let testFieldHtml = '';
    if (defaultTestId) {
        testFieldHtml = `
            <input type="text" class="form-control form-control-sm" value="${escapeHtml(defaultTestTitle)}" readonly>
            <input type="hidden" name="questions[${index}][test_id]" value="${escapeHtml(defaultTestId)}">
        `;
    } else {
        testFieldHtml = `
            <select class="form-select form-select-sm test-select-el" name="questions[${index}][test_id]" required>
                <option value="">-- Select Test --</option>
                ${testsOptionsHtml}
            </select>
        `;
    }

    return `
    <div class="card border mb-3 shadow-sm question-card" id="question_card_${index}">
        <div class="card-header d-flex justify-content-between align-items-center py-2 bg-light">
            <strong style="font-size: 13px;"><i class="bi bi-question-circle"></i> Question #<span class="q-number-span">${index + 1}</span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 remove-question-btn" onclick="removeQuestionCard(this)" style="font-size: 11px;">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
        <div class="card-body p-2">
            <!-- Row 1: Test & Question Type -->
            <div class="row g-1 mb-2">
                <div class="col-md-6 col-12">
                    <label class="form-label mb-0" style="font-size: 11px;">Test</label>
                    ${testFieldHtml}
                </div>
                <div class="col-md-6 col-12">
                    <label class="form-label mb-0" style="font-size: 11px;">Question Type</label>
                    <select class="form-select form-select-sm question-type-select" name="questions[${index}][question_type]" onchange="toggleCardChoices(this, ${index})" required>
                        <option value="Multiple Choice" selected>Multiple Choice</option>
                        <option value="Form">Form</option>
                    </select>
                </div>
            </div>

            <!-- Row 2: Text Question -->
            <div class="mb-2">
                <label class="form-label mb-0" style="font-size: 11px;">Text Question</label>
                <textarea class="form-control form-control-sm" name="questions[${index}][question]" rows="2" required></textarea>
            </div>

            <!-- Row 3: Material of Question (Optional) -->
            <div class="mb-2">
                <label class="form-label mb-0" style="font-size: 11px;">Material of Question (Optional)</label>
                <input type="text" class="form-control form-control-sm" name="questions[${index}][questions_material]">
            </div>

            <!-- Row 4: Choices (Multiple Choice Options) -->
            <div class="mb-2 border p-2 rounded bg-white choices-section" id="choices_section_${index}">
                <label class="form-label fw-bold mb-1" style="font-size: 11px;"><i class="bi bi-list-check"></i> Choices (Pilihan Ganda)</label>
                <p class="text-muted mb-2" style="font-size: 10px;">Beri tanda bulat pada opsi benar dan tentukan poinnya.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-1" style="font-size: 11px;">
                        <thead>
                            <tr class="table-secondary text-center">
                                <th width="10%">True?</th>
                                <th>Choice Text</th>
                                <th width="20%">Point</th>
                            </tr>
                        </thead>
                        <tbody class="choices-tbody" id="choices_tbody_${index}">
                            <tr class="choice-row">
                                <td class="text-center align-middle">
                                    <input class="form-check-input true-radio" type="radio" name="questions[${index}][choice_true_index]" value="0" checked>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm choice-text" name="questions[${index}][choices][0][text]" placeholder="Choice A" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="questions[${index}][choices][0][point]" value="10" min="0" required>
                                </td>
                            </tr>
                            <tr class="choice-row">
                                <td class="text-center align-middle">
                                    <input class="form-check-input true-radio" type="radio" name="questions[${index}][choice_true_index]" value="1">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm choice-text" name="questions[${index}][choices][1][text]" placeholder="Choice B" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="questions[${index}][choices][1][point]" value="0" min="0" required>
                                </td>
                            </tr>
                            <tr class="choice-row">
                                <td class="text-center align-middle">
                                    <input class="form-check-input true-radio" type="radio" name="questions[${index}][choice_true_index]" value="2">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm choice-text" name="questions[${index}][choices][2][text]" placeholder="Choice C">
                                        <button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="questions[${index}][choices][2][point]" value="0" min="0" required>
                                </td>
                            </tr>
                            <tr class="choice-row">
                                <td class="text-center align-middle">
                                    <input class="form-check-input true-radio" type="radio" name="questions[${index}][choice_true_index]" value="3">
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control form-control-sm choice-text" name="questions[${index}][choices][3][text]" placeholder="Choice D">
                                        <button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" name="questions[${index}][choices][3][point]" value="0" min="0" required>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" onclick="addChoiceRowToQuestion(this)" style="font-size: 11px;"><i class="bi bi-plus-circle"></i> Add Choice Option</button>
            </div>

            <!-- Row 5: Media Files (Optional) -->
            <div class="mb-2 border p-2 rounded bg-white">
                <label class="form-label fw-bold mb-1" style="font-size: 11px;"><i class="bi bi-file-earmark-medical"></i> Media Files (Optional)</label>
                <div class="medias-container" id="medias_container_${index}">
                    <!-- Dynamic rows -->
                </div>
                <button type="button" class="btn btn-xs btn-outline-success py-0 px-2" onclick="addMediaRowToQuestion(this)" style="font-size: 11px;"><i class="bi bi-plus-circle"></i> Add Media File</button>
            </div>
        </div>
    </div>`;
}

function addQuestionCard() {
    const container = document.getElementById('add_questions_container');
    if (!container) return;
    const cards = container.querySelectorAll('.question-card');
    const newIndex = cards.length;
    
    const div = document.createElement('div');
    div.innerHTML = getQuestionCardHtml(newIndex);
    container.appendChild(div.firstElementChild);
    
    reindexAllQuestions();
}

function removeQuestionCard(btn) {
    const card = btn.closest('.question-card');
    if (card) {
        card.remove();
        reindexAllQuestions();
    }
}

function reindexAllQuestions() {
    const container = document.getElementById('add_questions_container');
    if (!container) return;
    const cards = container.querySelectorAll('.question-card');
    
    cards.forEach((card, qIndex) => {
        card.id = `question_card_${qIndex}`;
        
        const numSpan = card.querySelector('.q-number-span');
        if (numSpan) numSpan.textContent = qIndex + 1;
        
        const removeBtn = card.querySelector('.remove-question-btn');
        if (removeBtn) {
            removeBtn.style.display = cards.length > 1 ? 'block' : 'none';
        }
        
        const testSelect = card.querySelector('.test-select-el') || card.querySelector('input[type="hidden"][name*="[test_id]"]');
        if (testSelect) {
            testSelect.name = `questions[${qIndex}][test_id]`;
        }
        
        const typeSelect = card.querySelector('.question-type-select');
        if (typeSelect) {
            typeSelect.name = `questions[${qIndex}][question_type]`;
            typeSelect.setAttribute('onchange', `toggleCardChoices(this, ${qIndex})`);
        }
        
        const questionText = card.querySelector('textarea[name*="[question]"]');
        if (questionText) {
            questionText.name = `questions[${qIndex}][question]`;
        }
        
        const materialInput = card.querySelector('input[name*="[questions_material]"]');
        if (materialInput) {
            materialInput.name = `questions[${qIndex}][questions_material]`;
        }
        
        const choicesSection = card.querySelector('.choices-section');
        if (choicesSection) {
            choicesSection.id = `choices_section_${qIndex}`;
        }
        
        const choicesTbody = card.querySelector('.choices-tbody');
        if (choicesTbody) {
            choicesTbody.id = `choices_tbody_${qIndex}`;
            
            const choiceRows = choicesTbody.querySelectorAll('.choice-row');
            choiceRows.forEach((row, cIndex) => {
                const radio = row.querySelector('.true-radio');
                if (radio) {
                    radio.name = `questions[${qIndex}][choice_true_index]`;
                    radio.value = cIndex;
                }
                
                const textInput = row.querySelector('.choice-text');
                if (textInput) {
                    textInput.name = `questions[${qIndex}][choices][${cIndex}][text]`;
                    textInput.placeholder = `Choice ${String.fromCharCode(65 + cIndex)}`;
                }
                
                const pointInput = row.querySelector('input[type="number"]');
                if (pointInput) {
                    pointInput.name = `questions[${qIndex}][choices][${cIndex}][point]`;
                }
            });
        }
        
        const mediasContainer = card.querySelector('.medias-container');
        if (mediasContainer) {
            mediasContainer.id = `medias_container_${qIndex}`;
            
            const mediaRows = mediasContainer.querySelectorAll('.media-input-row');
            mediaRows.forEach((row) => {
                const fileInput = row.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.name = `questions_media_files_${qIndex}[]`;
                }
                
                const nameInput = row.querySelector('input[type="text"]');
                if (nameInput) {
                    nameInput.name = `questions[${qIndex}][media_names][]`;
                }
            });
        }
    });
}

function addChoiceRowToQuestion(btn) {
    const card = btn.closest('.question-card');
    const tbody = card.querySelector('.choices-tbody');
    if (!tbody) return;
    const rowCount = tbody.children.length;
    const label = String.fromCharCode(65 + rowCount);
    
    const tr = document.createElement('tr');
    tr.className = 'choice-row';
    tr.innerHTML = `
        <td class="text-center align-middle">
            <input class="form-check-input true-radio" type="radio" name="questions_placeholder_radio" value="${rowCount}">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control form-control-sm choice-text" placeholder="Choice ${label}">
                <button type="button" class="btn btn-sm btn-danger remove-choice-btn" onclick="removeChoiceRow(this)"><i class="bi bi-trash"></i></button>
            </div>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" value="0" min="0" required>
        </td>
    `;
    tbody.appendChild(tr);
    reindexAllQuestions();
}

function removeChoiceRow(btn) {
    const tr = btn.closest('tr');
    if (tr) {
        tr.remove();
        reindexAllQuestions();
    }
}

function addMediaRowToQuestion(btn) {
    const card = btn.closest('.question-card');
    const container = card.querySelector('.medias-container');
    if (!container) return;
    
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center media-input-row';
    div.innerHTML = `
        <div class="col-6">
            <input type="file" class="form-control form-control-sm" required>
        </div>
        <div class="col-5">
            <input type="text" class="form-control form-control-sm" placeholder="Display Name (Optional)">
        </div>
        <div class="col-1 text-end">
            <button type="button" class="btn btn-sm btn-danger w-100 p-1" onclick="this.closest('.media-input-row').remove(); reindexAllQuestions();" title="Remove"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
    reindexAllQuestions();
}

function toggleCardChoices(selectEl, qIndex) {
    const card = selectEl.closest('.question-card');
    const container = card.querySelector('.choices-section');
    if (!container) return;
    if (selectEl.value === 'Multiple Choice') {
        container.style.display = 'block';
        const inputs = container.querySelectorAll('.choice-text');
        inputs.forEach((input, index) => {
            if (index < 2) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    } else {
        container.style.display = 'none';
        container.querySelectorAll('input').forEach(input => input.removeAttribute('required'));
    }
}

function toggleChoices(type, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (type === 'Multiple Choice') {
        container.style.display = 'block';
        const inputClass = containerId === 'edit_choices_section' ? '.edit-choice-text-input' : '.choice-text-input';
        container.querySelectorAll(inputClass).forEach((input, index) => {
            if (index < 2) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    } else {
        container.style.display = 'none';
        container.querySelectorAll('input').forEach(input => input.removeAttribute('required'));
    }
}

function reindexChoices(tbodyId, mode) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = tbody.querySelectorAll('tr');
    rows.forEach((tr, i) => {
        const label = String.fromCharCode(65 + i);
        
        // Update radio input value
        const radio = tr.querySelector('input[type="radio"]');
        if (radio) {
            radio.value = i;
        }
        
        // Update hidden ID input if it exists
        const hiddenId = tr.querySelector('input[type="hidden"]');
        if (hiddenId) {
            hiddenId.name = `choices[${i}][id]`;
        }
        
        // Update text input name, placeholder and required attribute
        const textInput = tr.querySelector(`.${mode}-choice-text-input`) || tr.querySelector('.choice-text-input');
        if (textInput) {
            textInput.name = `choices[${i}][text]`;
            textInput.placeholder = `Choice ${label}`;
            
            const questionType = document.getElementById(mode + '_question_type').value;
            if (i < 2 && questionType === 'Multiple Choice') {
                textInput.setAttribute('required', 'required');
            } else {
                textInput.removeAttribute('required');
            }
        }
        
        // Update point input name
        const pointInput = tr.querySelector('input[type="number"]');
        if (pointInput) {
            pointInput.name = `choices[${i}][point]`;
        }
    });
}

function addChoiceRow(tbodyId, mode) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rowCount = tbody.children.length;
    const label = String.fromCharCode(65 + rowCount);
    
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="text-center align-middle">
            <input type="hidden" name="choices[${rowCount}][id]" value="">
            <input class="form-check-input" type="radio" name="choice_true_index" value="${rowCount}">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control form-control-sm ${mode === 'edit' ? 'edit-choice-text-input' : 'choice-text-input'}" name="choices[${rowCount}][text]" placeholder="Choice ${label}">
                <button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); reindexChoices('${tbodyId}', '${mode}');"><i class="bi bi-trash"></i></button>
            </div>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" name="choices[${rowCount}][point]" value="0" min="0" required>
        </td>
    `;
    tbody.appendChild(tr);
    reindexChoices(tbodyId, mode);
}

function addMediaRow(containerId, mode) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center media-input-row';
    div.innerHTML = `
        <div class="col-6">
            <input type="file" class="form-control form-control-sm" name="media_files[]" required>
        </div>
        <div class="col-5">
            <input type="text" class="form-control form-control-sm" name="media_names[]" placeholder="Display Name (Optional)">
        </div>
        <div class="col-1 text-end">
            <button type="button" class="btn btn-sm btn-danger w-100" onclick="this.closest('.media-input-row').remove();" title="Remove"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function removeExistingMedia(btn, mediaId) {
    if (confirm('Are you sure you want to delete this media? This will permanently delete the file.')) {
        const form = btn.closest('form');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'deleted_media_ids[]';
        input.value = mediaId;
        form.appendChild(input);
        
        btn.closest('.existing-media-row').remove();
        
        const container = document.getElementById('edit_existing_medias_container');
        if (container.querySelectorAll('.existing-media-row').length === 0) {
            container.innerHTML = '<p class="text-muted" style="font-size: 11px;">No media files attached.</p>';
        }
    }
}

function loadChoices(questionId, questionType) {
    const tbody = document.getElementById('edit_choices_tbody');
    const existingMediasContainer = document.getElementById('edit_existing_medias_container');
    const newMediasContainer = document.getElementById('edit_new_medias_container');
    
    // Reset new medias
    newMediasContainer.innerHTML = '';
    
    // Set question type dropdown
    document.getElementById('edit_question_type').value = questionType;
    toggleChoices(questionType, 'edit_choices_section');
    
    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Loading choices...</td></tr>';
    existingMediasContainer.innerHTML = '<p class="text-muted" style="font-size: 11px;">Loading media files...</p>';
    
    fetch('actions/?hal=test_test-question&get_choices=' + questionId)
        .then(response => response.json())
        .then(data => {
            // Render Choices
            tbody.innerHTML = '';
            const choices = data.choices || [];
            const totalRows = Math.max(4, choices.length);
            
            for (let i = 0; i < totalRows; i++) {
                const choice = choices[i] || {};
                const idVal = choice.id || '';
                const textVal = choice.choice_text || '';
                const isTrue = choice.choice_true === 'true';
                const pointVal = choice.point !== undefined ? choice.point : (i === 0 && !idVal ? '10' : '0');
                const label = String.fromCharCode(65 + i);
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="text-center align-middle">
                        <input type="hidden" name="choices[${i}][id]" value="${idVal}">
                        <input class="form-check-input" type="radio" name="choice_true_index" value="${i}" ${isTrue || (i === 0 && choices.length === 0) ? 'checked' : ''}>
                    </td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm edit-choice-text-input" name="choices[${i}][text]" value="${escapeHtml(textVal)}" placeholder="Choice ${label}" ${i < 2 && questionType === 'Multiple Choice' ? 'required' : ''}>
                            ${i >= 2 ? `<button type="button" class="btn btn-danger" onclick="this.closest('tr').remove(); reindexChoices('edit_choices_tbody', 'edit');"><i class="bi bi-trash"></i></button>` : ''}
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm" name="choices[${i}][point]" value="${pointVal}" min="0" required>
                    </td>
                `;
                tbody.appendChild(tr);
            }
            
            // Render Medias
            existingMediasContainer.innerHTML = '';
            const medias = data.medias || [];
            if (medias.length > 0) {
                const titleLabel = document.createElement('label');
                titleLabel.className = 'form-label fw-bold text-secondary mt-1';
                titleLabel.style.fontSize = '12px';
                titleLabel.innerText = 'Existing Medias:';
                existingMediasContainer.appendChild(titleLabel);
                
                medias.forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'row g-2 mb-2 align-items-center existing-media-row';
                    div.innerHTML = `
                        <div class="col-5">
                            <a href="${m.path}" target="_blank" style="font-size: 11px;" class="text-decoration-none fw-bold text-dark">
                                <i class="bi bi-file-earmark"></i> ${escapeHtml(m.media_name)} (${m.media_extendsion})
                            </a>
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm" name="existing_media_names[${m.id}]" value="${escapeHtml(m.media_name)}" required placeholder="Display Name">
                        </div>
                        <div class="col-1 text-end">
                            <button type="button" class="btn btn-sm btn-danger w-100" onclick="removeExistingMedia(this, '${m.id}')" title="Delete Permanent"><i class="bi bi-trash"></i></button>
                        </div>
                    `;
                    existingMediasContainer.appendChild(div);
                });
            } else {
                existingMediasContainer.innerHTML = '<p class="text-muted" style="font-size: 11px;">No media files attached.</p>';
            }
            
            toggleChoices(questionType, 'edit_choices_section');
        })
        .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger">Error loading choices.</td></tr>';
            existingMediasContainer.innerHTML = '<p class="text-danger" style="font-size: 11px;">Error loading medias.</p>';
        });
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function upData(id, test_id, question, question_type, questions_material) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_test_id').value = test_id;
    document.getElementById('edit_question').value = question;
    document.getElementById('edit_questions_material').value = questions_material;
    
    loadChoices(id, question_type);
    
    var editModal = new bootstrap.Modal(document.getElementById('editModal'));
    editModal.show();
}

// Initialise choices display for Add Modal on page load
document.addEventListener('DOMContentLoaded', function() {
    reindexAllQuestions();
});
</script>
