<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$editId = sanitizeInt($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$q = $isEdit ? db()->fetchOne("SELECT * FROM questions WHERE id=?", [$editId]) : null;
if ($isEdit && !$q) { setFlash('error', 'Question not found.'); redirect('questions.php'); }

define('PAGE_TITLE', $isEdit ? 'Edit Question' : 'Add Question');

$subjects  = db()->fetchAll("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
$chapters  = db()->fetchAll("SELECT * FROM chapters WHERE is_active=1 ORDER BY subject_id, sort_order");
$errors = [];

$data = $q ?: [
    'subject_id' => sanitizeInt($_GET['subject_id'] ?? 0), 'chapter_id' => '', 'question_text' => '',
    'option_a' => '', 'option_b' => '', 'option_c' => '', 'option_d' => '',
    'correct_option' => 'A', 'difficulty' => 'medium', 'marks' => 1,
    'negative_marks' => 0, 'explanation' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $data = [
            'subject_id'    => sanitizeInt($_POST['subject_id'] ?? 0),
            'chapter_id'    => sanitizeInt($_POST['chapter_id'] ?? 0),
            'question_text' => trim($_POST['question_text'] ?? ''),
            'option_a'      => trim($_POST['option_a'] ?? ''),
            'option_b'      => trim($_POST['option_b'] ?? ''),
            'option_c'      => trim($_POST['option_c'] ?? ''),
            'option_d'      => trim($_POST['option_d'] ?? ''),
            'correct_option'=> strtoupper(trim($_POST['correct_option'] ?? 'A')),
            'difficulty'    => $_POST['difficulty'] ?? 'medium',
            'marks'         => sanitizeFloat($_POST['marks'] ?? 1),
            'negative_marks'=> sanitizeFloat($_POST['negative_marks'] ?? 0),
            'explanation'   => trim($_POST['explanation'] ?? ''),
        ];

        if (empty($data['subject_id']))    $errors[] = 'Please select a category / subject.';
        if (empty($data['question_text'])) $errors[] = 'Question text is required.';
        if (empty($data['option_a']))      $errors[] = 'Option A is required.';
        if (empty($data['option_b']))      $errors[] = 'Option B is required.';
        if (empty($data['option_c']))      $errors[] = 'Option C is required.';
        if (empty($data['option_d']))      $errors[] = 'Option D is required.';
        if (!in_array($data['correct_option'], ['A','B','C','D'])) $errors[] = 'Select a valid correct answer.';
        if ($data['chapter_id']) {
            $chapter = db()->fetchOne('SELECT id FROM chapters WHERE id=? AND subject_id=? AND is_active=1', [$data['chapter_id'], $data['subject_id']]);
            if (!$chapter) $errors[] = 'Selected chapter does not belong to this category.';
        }
        if ($isEdit) {
            $linkedExam = db()->fetchOne('SELECT e.exam_name FROM exam_questions eq JOIN exams e ON e.id=eq.exam_id WHERE eq.question_id=? AND e.subject_id IS NOT NULL AND e.subject_id<>? LIMIT 1', [$editId, $data['subject_id']]);
            if ($linkedExam) $errors[] = 'This question is already used in the "' . $linkedExam['exam_name'] . '" exam and cannot be moved to another category.';
        }

        if (empty($errors)) {
            if ($isEdit) {
                db()->execute("UPDATE questions SET subject_id=?,chapter_id=?,question_text=?,
                    option_a=?,option_b=?,option_c=?,option_d=?,correct_option=?,
                    difficulty=?,marks=?,negative_marks=?,explanation=? WHERE id=?",
                    [$data['subject_id']?:null, $data['chapter_id']?:null,
                     $data['question_text'], $data['option_a'], $data['option_b'],
                     $data['option_c'], $data['option_d'], $data['correct_option'],
                     $data['difficulty'], $data['marks'], $data['negative_marks'],
                     $data['explanation'], $editId]);
                setFlash('success', 'Question updated!');
            } else {
                db()->insert("INSERT INTO questions (subject_id,chapter_id,question_text,option_a,option_b,
                    option_c,option_d,correct_option,difficulty,marks,negative_marks,explanation,created_by)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    [$data['subject_id']?:null, $data['chapter_id']?:null,
                     $data['question_text'], $data['option_a'], $data['option_b'],
                     $data['option_c'], $data['option_d'], $data['correct_option'],
                     $data['difficulty'], $data['marks'], $data['negative_marks'],
                     $data['explanation'], $_SESSION['admin_id']]);
                setFlash('success', 'Question added to bank!');
            }
            $ref = $_GET['ref'] ?? '';
            redirect($ref === 'exam' && isset($_GET['exam_id']) ? "exam_questions.php?exam_id=" . (int)$_GET['exam_id'] : 'questions.php');
        }
    }
}

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="questions.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-question-circle me-2 text-primary"></i><?= PAGE_TITLE ?></h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Category / Subject <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" id="subjectSel" onchange="loadChapters()" required>
                        <option value="0">— Select Subject —</option>
                        <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $data['subject_id'] == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Chapter</label>
                    <select name="chapter_id" class="form-select" id="chapterSel">
                        <option value="0">— Select Chapter —</option>
                        <?php foreach ($chapters as $ch): ?>
                        <option value="<?= $ch['id'] ?>" data-subject="<?= $ch['subject_id'] ?>"
                            <?= $data['chapter_id'] == $ch['id'] ? 'selected' : '' ?>>
                            <?= sanitize($ch['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Difficulty</label>
                    <select name="difficulty" class="form-select">
                        <?php foreach (['easy','medium','hard'] as $d): ?>
                        <option value="<?= $d ?>" <?= $data['difficulty'] === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Marks</label>
                    <input type="number" name="marks" class="form-control" min="0" step="0.5" value="<?= $data['marks'] ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Neg. Marks</label>
                    <input type="number" name="negative_marks" class="form-control" min="0" step="0.25" value="<?= $data['negative_marks'] ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Question Text <span class="text-danger">*</span></label>
                    <textarea name="question_text" class="form-control" rows="3" required placeholder="Enter the question..."><?= sanitize($data['question_text']) ?></textarea>
                </div>

                <?php foreach (['A','B','C','D'] as $opt): $key = 'option_' . strtolower($opt); ?>
                <div class="col-md-6">
                    <label class="form-label">
                        Option <?= $opt ?>
                        <?php if ($data['correct_option'] === $opt): ?>
                        <span class="badge bg-success ms-1">Correct Answer</span>
                        <?php endif; ?>
                    </label>
                    <input type="text" name="<?= $key ?>" class="form-control" required
                           value="<?= sanitize($data[$key]) ?>" placeholder="Enter option <?= $opt ?>">
                </div>
                <?php endforeach; ?>

                <div class="col-md-6">
                    <label class="form-label">Correct Answer <span class="text-danger">*</span></label>
                    <select name="correct_option" class="form-select">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $data['correct_option'] === $opt ? 'selected' : '' ?>>
                            Option <?= $opt ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Explanation <small class="text-muted">(Optional — shown in result report)</small></label>
                    <textarea name="explanation" class="form-control" rows="2" placeholder="Why is this the correct answer?"><?= sanitize($data['explanation']) ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i><?= $isEdit ? 'Update Question' : 'Save Question' ?>
                    </button>
                    <a href="questions.php" class="btn btn-outline-secondary ms-2 px-4">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const allChapters = <?= json_encode($chapters) ?>;

function loadChapters() {
    const subId = parseInt(document.getElementById('subjectSel').value);
    const sel = document.getElementById('chapterSel');
    const current = sel.value;
    sel.innerHTML = '<option value="0">— Select Chapter —</option>';
    allChapters.filter(c => c.subject_id == subId).forEach(c => {
        const o = document.createElement('option');
        o.value = c.id; o.textContent = c.name;
        if (c.id == current) o.selected = true;
        sel.appendChild(o);
    });
}
loadChapters();
</script>
<?php include 'includes/footer.php'; ?>
