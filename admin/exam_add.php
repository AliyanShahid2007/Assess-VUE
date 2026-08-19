<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$editId = sanitizeInt($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$exam = $isEdit ? db()->fetchOne("SELECT * FROM exams WHERE id=?", [$editId]) : null;
if ($isEdit && !$exam) { setFlash('error', 'Exam not found.'); redirect('exams.php'); }

define('PAGE_TITLE', $isEdit ? 'Edit Exam' : 'Create Exam');
$subjects = db()->fetchAll("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$errors = [];

$data = $exam ?: [
    'exam_name' => '', 'exam_code' => '', 'description' => '', 'subject_id' => '',
    'total_questions' => 50, 'marks_per_question' => 2, 'negative_marks' => 0,
    'total_marks' => 100, 'passing_percentage' => 60, 'duration_minutes' => 60,
    'max_violations' => 3, 'shuffle_questions' => 0, 'shuffle_options' => 0,
    'instructions' => '', 'status' => 'draft'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $data = [
            'exam_name'          => trim($_POST['exam_name'] ?? ''),
            'exam_code'          => strtoupper(trim($_POST['exam_code'] ?? '')),
            'description'        => trim($_POST['description'] ?? ''),
            'subject_id'         => sanitizeInt($_POST['subject_id'] ?? 0),
            'total_questions'    => sanitizeInt($_POST['total_questions'] ?? 50),
            'marks_per_question' => sanitizeFloat($_POST['marks_per_question'] ?? 1),
            'negative_marks'     => sanitizeFloat($_POST['negative_marks'] ?? 0),
            // Every exam is scored out of 100; individual question marks are distributed from this.
            'total_marks'        => 100.00,
            'passing_percentage' => sanitizeFloat($_POST['passing_percentage'] ?? 60),
            'duration_minutes'   => sanitizeInt($_POST['duration_minutes'] ?? 60),
            'max_violations'     => sanitizeInt($_POST['max_violations'] ?? 3),
            'shuffle_questions'  => (int)isset($_POST['shuffle_questions']),
            'shuffle_options'    => (int)isset($_POST['shuffle_options']),
            'instructions'       => trim($_POST['instructions'] ?? ''),
            'status'             => $_POST['status'] ?? 'draft',
        ];

        if (empty($data['exam_name'])) $errors[] = 'Exam name is required.';
        if (empty($data['exam_code'])) $errors[] = 'Exam code is required.';
        if (empty($data['subject_id'])) $errors[] = 'Please select an exam category / subject.';
        if ($data['duration_minutes'] < 5) $errors[] = 'Duration must be at least 5 minutes.';
        if ($isEdit && $data['subject_id']) {
            $differentCategoryQuestion = db()->fetchOne('SELECT q.id FROM exam_questions eq JOIN questions q ON q.id=eq.question_id WHERE eq.exam_id=? AND (q.subject_id IS NULL OR q.subject_id<>?) LIMIT 1', [$editId, $data['subject_id']]);
            if ($differentCategoryQuestion) $errors[] = 'This exam already contains questions from another category. Remove or recategorize them before changing the exam category.';
        }

        // Unique code check
        $codeExists = db()->fetchOne(
            "SELECT id FROM exams WHERE exam_code=? AND id != ?",
            [$data['exam_code'], $editId]
        );
        if ($codeExists) $errors[] = 'Exam code already exists.';

        if (empty($errors)) {
            if ($isEdit) {
                db()->execute("UPDATE exams SET exam_name=?,exam_code=?,description=?,subject_id=?,
                    total_questions=?,marks_per_question=?,negative_marks=?,total_marks=?,
                    passing_percentage=?,duration_minutes=?,max_violations=?,shuffle_questions=?,
                    shuffle_options=?,instructions=?,status=? WHERE id=?",
                    [...array_values($data), $editId]);
                distributeExamMarks($editId);
                setFlash('success', 'Exam updated!');
                redirect('exams.php');
            } else {
                $newId = db()->insert("INSERT INTO exams (exam_name,exam_code,description,subject_id,
                    total_questions,marks_per_question,negative_marks,total_marks,
                    passing_percentage,duration_minutes,max_violations,shuffle_questions,
                    shuffle_options,instructions,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [...array_values($data), $_SESSION['admin_id']]);
                setFlash('success', 'Exam created! Now add questions.');
                redirect("exam_questions.php?exam_id=$newId");
            }
        }
    }
}

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="exams.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2 text-primary"></i><?= PAGE_TITLE ?></h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<form method="POST">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Basic Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Exam Name <span class="text-danger">*</span></label>
                            <input type="text" name="exam_name" class="form-control" required
                                   value="<?= sanitize($data['exam_name']) ?>" placeholder="e.g. Web Development Assessment">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Exam Code <span class="text-danger">*</span></label>
                            <input type="text" name="exam_code" class="form-control" required
                                   value="<?= sanitize($data['exam_code']) ?>" placeholder="e.g. WD-2024" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category / Subject <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" required>
                                <option value="0">— Select Subject —</option>
                                <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $data['subject_id'] == $sub['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($sub['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="active" <?= $data['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="archived" <?= $data['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= sanitize($data['description']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><i class="fas fa-cog me-2"></i>Exam Configuration</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Total Questions</label>
                            <input type="number" name="total_questions" class="form-control" min="1"
                                   value="<?= $data['total_questions'] ?>" oninput="calcTotal()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Marks Per Question</label>
                            <input type="number" name="marks_per_question" id="marksPerQuestion" class="form-control" step="0.01"
                                   value="<?= $data['marks_per_question'] ?>" readonly>
                            <small class="text-muted">Calculated automatically from total marks and question count.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Negative Marks</label>
                            <input type="number" name="negative_marks" class="form-control" min="0" step="0.25"
                                   value="<?= $data['negative_marks'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Marks</label>
                            <input type="number" name="total_marks" id="totalMarks" class="form-control"
                                   value="100" readonly>
                            <small class="text-muted">Fixed at 100 for every exam.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Passing Percentage (%)</label>
                            <input type="number" name="passing_percentage" class="form-control" min="1" max="100"
                                   value="<?= $data['passing_percentage'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (Minutes)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="5"
                                   value="<?= $data['duration_minutes'] ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max Violations Before Termination</label>
                            <input type="number" name="max_violations" class="form-control" min="0"
                                   value="<?= $data['max_violations'] ?>">
                            <small class="text-muted">0 = disabled</small>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" name="shuffle_questions" id="sq"
                                       <?= $data['shuffle_questions'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sq">Shuffle Questions</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="shuffle_options" id="so"
                                       <?= $data['shuffle_options'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="so">Shuffle Options</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-file-alt me-2"></i>Instructions</div>
                <div class="card-body">
                    <textarea name="instructions" class="form-control" rows="12"
                              placeholder="Custom exam instructions shown to student..."><?= sanitize($data['instructions']) ?></textarea>
                    <small class="text-muted">Leave blank for default instructions.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save me-2"></i><?= $isEdit ? 'Save Changes' : 'Create Exam' ?>
        </button>
        <a href="exams.php" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>

<script>
function calcMarksPerQuestion() {
    const q = parseFloat(document.querySelector('[name=total_questions]').value) || 0;
    const total = parseFloat(document.getElementById('totalMarks').value) || 0;
    document.getElementById('marksPerQuestion').value = q > 0 ? (total / q).toFixed(2) : '0.00';
}
document.querySelector('[name=total_questions]').addEventListener('input', calcMarksPerQuestion);
</script>
<?php include 'includes/footer.php'; ?>
