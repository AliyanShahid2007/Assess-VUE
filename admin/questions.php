<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Question Bank');

$action = $_GET['action'] ?? '';
if ($action === 'delete' && isset($_GET['id'])) {
    db()->execute("DELETE FROM questions WHERE id=?", [sanitizeInt($_GET['id'])]);
    setFlash('success', 'Question deleted.');
    redirect('questions.php');
}

$search = trim($_GET['search'] ?? '');
$subjectFilter = sanitizeInt($_GET['subject'] ?? 0);
$diffFilter = $_GET['diff'] ?? '';

$where = "WHERE q.is_active=1";
$params = [];
if ($search) {
    $where .= " AND q.question_text LIKE ?";
    $params[] = "%$search%";
}
if ($subjectFilter) {
    $where .= " AND q.subject_id=?";
    $params[] = $subjectFilter;
}
if (in_array($diffFilter, ['easy','medium','hard'])) {
    $where .= " AND q.difficulty=?";
    $params[] = $diffFilter;
}

$questions = db()->fetchAll("
    SELECT q.*, sub.name as subject_name, ch.name as chapter_name
    FROM questions q
    LEFT JOIN subjects sub ON sub.id = q.subject_id
    LEFT JOIN chapters ch ON ch.id = q.chapter_id
    $where
    ORDER BY q.created_at DESC",
    $params
);

$subjects = db()->fetchAll("SELECT * FROM subjects WHERE is_active=1 ORDER BY name");
include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0"><i class="fas fa-database me-2 text-primary"></i>Question Bank (<?= count($questions) ?>)</h2>
    <div class="d-flex gap-2">
        <a href="pdf_import.php" class="btn btn-outline-primary"><i class="fas fa-file-pdf me-1"></i>Import PDF</a>
        <a href="question_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Question</a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search question text..."
                       value="<?= sanitize($search) ?>">
            </div>
            <div class="col-sm-3">
                <select name="subject" class="form-select form-select-sm">
                    <option value="0">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $subjectFilter == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="diff" class="form-select form-select-sm">
                    <option value="">All Difficulty</option>
                    <option value="easy" <?= $diffFilter === 'easy' ? 'selected' : '' ?>>Easy</option>
                    <option value="medium" <?= $diffFilter === 'medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="hard" <?= $diffFilter === 'hard' ? 'selected' : '' ?>>Hard</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="questions.php" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Question</th>
                        <th>Subject / Chapter</th>
                        <th>Correct</th>
                        <th>Marks</th>
                        <th>Difficulty</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($questions as $i => $q): ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td style="max-width:400px">
                        <p class="mb-1 fw-semibold" style="font-size:.875rem">
                            <?= sanitize(mb_substr($q['question_text'], 0, 120)) ?><?= mb_strlen($q['question_text']) > 120 ? '...' : '' ?>
                        </p>
                        <small class="text-muted">
                            A: <?= sanitize(mb_substr($q['option_a'], 0, 30)) ?>
                            B: <?= sanitize(mb_substr($q['option_b'], 0, 30)) ?>
                        </small>
                    </td>
                    <td>
                        <div><?= sanitize($q['subject_name'] ?? '—') ?></div>
                        <small class="text-muted"><?= sanitize($q['chapter_name'] ?? '') ?></small>
                    </td>
                    <td><span class="badge bg-success"><?= $q['correct_option'] ?></span></td>
                    <td>
                        <span class="fw-semibold"><?= $q['marks'] ?></span>
                        <?php if ($q['negative_marks'] > 0): ?>
                        <span class="text-danger small">-<?= $q['negative_marks'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $q['difficulty'] === 'easy' ? 'success' : ($q['difficulty'] === 'hard' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($q['difficulty']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="question_edit.php?id=<?= $q['id'] ?>" class="btn btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <a href="questions.php?action=delete&id=<?= $q['id'] ?>"
                               class="btn btn-outline-danger"
                               onclick="return confirm('Delete this question?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$questions): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No questions in bank yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
