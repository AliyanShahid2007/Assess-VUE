<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$exam_id = sanitizeInt($_GET['exam_id'] ?? 0);
$exam = db()->fetchOne("SELECT * FROM exams WHERE id=?", [$exam_id]);
if (!$exam) { setFlash('error','Exam not found.'); redirect('exams.php'); }

define('PAGE_TITLE', 'Exam Questions: ' . $exam['exam_name']);

// Handle remove question
if (isset($_GET['remove'])) {
    $qid = sanitizeInt($_GET['remove']);
    db()->execute("DELETE FROM exam_questions WHERE exam_id=? AND question_id=?", [$exam_id, $qid]);
    setFlash('success', 'Question removed.');
    redirect("exam_questions.php?exam_id=$exam_id");
}

// Handle add selected questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_questions'])) {
    $qids = array_map('intval', $_POST['q_ids'] ?? []);
    $order = db()->fetchOne("SELECT MAX(sort_order) as m FROM exam_questions WHERE exam_id=?", [$exam_id])['m'] ?? 0;
    foreach ($qids as $qid) {
        $exists = db()->fetchOne("SELECT id FROM exam_questions WHERE exam_id=? AND question_id=?", [$exam_id, $qid]);
        if (!$exists) {
            $order++;
            db()->execute("INSERT INTO exam_questions (exam_id,question_id,sort_order,marks,negative_marks) VALUES (?,?,?,?,?)",
                [$exam_id, $qid, $order, $exam['marks_per_question'], $exam['negative_marks']]);
        }
    }
    setFlash('success', count($qids) . ' question(s) added.');
    redirect("exam_questions.php?exam_id=$exam_id");
}

// Current questions
$currentQs = db()->fetchAll("
    SELECT eq.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_option,
           q.difficulty, sub.name as subject_name, ch.name as chapter_name
    FROM exam_questions eq
    JOIN questions q ON q.id = eq.question_id
    LEFT JOIN subjects sub ON sub.id = q.subject_id
    LEFT JOIN chapters ch ON ch.id = q.chapter_id
    WHERE eq.exam_id = ?
    ORDER BY eq.sort_order ASC",
    [$exam_id]
);

// Available questions (not yet added)
$subjects = db()->fetchAll("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$filterSubject = sanitizeInt($_GET['filter_subject'] ?? 0);
$filterSearch  = trim($_GET['filter_search'] ?? '');

$aWhere = "WHERE q.is_active=1 AND q.id NOT IN (SELECT question_id FROM exam_questions WHERE exam_id=?)";
$aParams = [$exam_id];
if ($filterSubject) {
    $aWhere .= " AND q.subject_id=?";
    $aParams[] = $filterSubject;
}
if ($filterSearch) {
    $aWhere .= " AND q.question_text LIKE ?";
    $aParams[] = "%$filterSearch%";
}
$availableQs = db()->fetchAll("
    SELECT q.*, sub.name as subject_name, ch.name as chapter_name
    FROM questions q
    LEFT JOIN subjects sub ON sub.id = q.subject_id
    LEFT JOIN chapters ch ON ch.id = q.chapter_id
    $aWhere
    ORDER BY q.created_at DESC
    LIMIT 100",
    $aParams
);

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-3 gap-3">
    <a href="exams.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Exams</a>
    <div>
        <h2 class="mb-0"><?= sanitize($exam['exam_name']) ?></h2>
        <small class="text-muted">Code: <?= sanitize($exam['exam_code']) ?> | Duration: <?= $exam['duration_minutes'] ?> min | Pass: <?= $exam['passing_percentage'] ?>%</small>
    </div>
</div>

<div class="row g-4">
    <!-- Current Questions in Exam -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="fas fa-list-ol me-2 text-primary"></i>Questions in Exam (<?= count($currentQs) ?>)</div>
                <span class="badge bg-primary"><?= count($currentQs) ?>/<?= $exam['total_questions'] ?> Set</span>
            </div>
            <div class="card-body p-0" style="max-height:600px;overflow-y:auto">
                <?php if ($currentQs): ?>
                <div class="list-group list-group-flush">
                <?php foreach ($currentQs as $i => $q): ?>
                <div class="list-group-item">
                    <div class="d-flex align-items-start gap-2">
                        <span class="question-number mt-1"><?= $i+1 ?></span>
                        <div class="flex-grow-1">
                            <p class="mb-1 fw-semibold" style="font-size:.9rem"><?= sanitize(mb_substr($q['question_text'], 0, 120)) ?><?= mb_strlen($q['question_text']) > 120 ? '...' : '' ?></p>
                            <small class="text-muted">
                                Correct: <span class="badge bg-success"><?= $q['correct_option'] ?></span>
                                | <?= $q['subject_name'] ?? '—' ?>
                                | Marks: <?= $q['marks'] ?>
                                <span class="badge bg-<?= $q['difficulty'] === 'easy' ? 'success' : ($q['difficulty'] === 'hard' ? 'danger' : 'warning') ?> ms-1">
                                    <?= ucfirst($q['difficulty']) ?>
                                </span>
                            </small>
                        </div>
                        <div class="ms-auto d-flex gap-1">
                            <a href="question_edit.php?id=<?= $q['question_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="exam_questions.php?exam_id=<?= $exam_id ?>&remove=<?= $q['question_id'] ?>"
                               class="btn btn-sm btn-outline-danger" title="Remove"
                               onclick="return confirm('Remove this question from exam?')">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">No questions added yet</div>
                <?php endif; ?>
            </div>
            <?php if (count($currentQs) < $exam['total_questions']): ?>
            <div class="card-footer bg-warning-subtle text-warning-emphasis">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <?= $exam['total_questions'] - count($currentQs) ?> more question(s) needed
            </div>
            <?php else: ?>
            <div class="card-footer bg-success-subtle text-success-emphasis">
                <i class="fas fa-check-circle me-1"></i>All <?= $exam['total_questions'] ?> questions set!
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Question Bank / Add Questions -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-database me-2 text-success"></i>Add from Question Bank</div>
            <div class="card-body border-bottom">
                <form method="GET" class="row g-2">
                    <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
                    <div class="col-8">
                        <input type="text" name="filter_search" class="form-control form-control-sm"
                               placeholder="Search questions..." value="<?= sanitize($filterSearch) ?>">
                    </div>
                    <div class="col-4">
                        <button class="btn btn-sm btn-outline-primary w-100" type="submit">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="add_questions" value="1">
                <div class="card-body p-0" style="max-height:520px;overflow-y:auto">
                    <?php if ($availableQs): ?>
                    <div class="list-group list-group-flush">
                    <?php foreach ($availableQs as $q): ?>
                    <div class="list-group-item">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="q_ids[]" value="<?= $q['id'] ?>" id="q<?= $q['id'] ?>">
                            <label class="form-check-label w-100" for="q<?= $q['id'] ?>">
                                <p class="mb-1" style="font-size:.875rem"><?= sanitize(mb_substr($q['question_text'], 0, 100)) ?><?= mb_strlen($q['question_text']) > 100 ? '...' : '' ?></p>
                                <small class="text-muted">
                                    <?= $q['subject_name'] ?? '—' ?>
                                    <?= $q['chapter_name'] ? ' > ' . $q['chapter_name'] : '' ?>
                                    | Correct: <span class="badge bg-success"><?= $q['correct_option'] ?></span>
                                </small>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No questions found. <a href="question_add.php">Add questions</a> or <a href="pdf_import.php">import PDF</a>.
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($availableQs): ?>
                <div class="card-footer d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Add Selected
                    </button>
                    <a href="question_add.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i>Create New Question
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
