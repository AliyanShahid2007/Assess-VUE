<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Subjects & Chapters');

// Handle actions
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add_subject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    if ($name) {
        db()->execute("INSERT IGNORE INTO subjects (name, code, description, created_by) VALUES (?,?,?,?)",
            [$name, $code ?: null, $desc, $_SESSION['admin_id']]);
        setFlash('success', 'Subject added.');
    }
    redirect('subjects.php');
}
if ($action === 'add_chapter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId = sanitizeInt($_POST['subject_id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
    if ($subId && $name) {
        db()->execute("INSERT INTO chapters (subject_id, name) VALUES (?,?)", [$subId, $name]);
        setFlash('success', 'Chapter added.');
    }
    redirect('subjects.php');
}
if ($action === 'del_subject' && isset($_GET['id'])) {
    db()->execute("DELETE FROM subjects WHERE id=?", [sanitizeInt($_GET['id'])]);
    setFlash('success', 'Subject deleted.');
    redirect('subjects.php');
}
if ($action === 'del_chapter' && isset($_GET['id'])) {
    db()->execute("DELETE FROM chapters WHERE id=?", [sanitizeInt($_GET['id'])]);
    setFlash('success', 'Chapter deleted.');
    redirect('subjects.php');
}

$subjects = db()->fetchAll("SELECT * FROM subjects ORDER BY name");
$chapters = db()->fetchAll("SELECT * FROM chapters ORDER BY subject_id, sort_order");

include 'includes/header.php';
?>
<?= renderFlash() ?>

<h2 class="mb-4"><i class="fas fa-book me-2 text-primary"></i>Subjects &amp; Chapters</h2>

<div class="row g-4">
    <div class="col-12 col-md-5">
        <div class="card">
            <div class="card-header"><i class="fas fa-plus me-2"></i>Add Subject</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_subject">
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control" placeholder="Subject Name *" required>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="code" class="form-control" placeholder="Code (e.g. WD101)" style="text-transform:uppercase">
                    </div>
                    <div class="mb-2">
                        <textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Subject</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-plus me-2"></i>Add Chapter</div>
            <div class="card-body">
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add_chapter">
                    <div class="mb-2">
                        <select name="subject_id" class="form-select" required>
                            <option value="0">— Select Subject —</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= sanitize($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <input type="text" name="name" class="form-control" placeholder="Chapter Name *" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Add Chapter</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-7">
        <?php foreach ($subjects as $sub): ?>
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center">
                <div>
                    <span class="fw-semibold"><?= sanitize($sub['name']) ?></span>
                    <?php if ($sub['code']): ?>
                    <code class="ms-2"><?= sanitize($sub['code']) ?></code>
                    <?php endif; ?>
                </div>
                <a href="subjects.php?action=del_subject&id=<?= $sub['id'] ?>"
                   class="btn btn-sm btn-outline-danger ms-auto"
                   onclick="return confirm('Delete this subject?')">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
            <?php $subChaps = array_filter($chapters, fn($c) => $c['subject_id'] == $sub['id']); ?>
            <?php if ($subChaps): ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($subChaps as $ch): ?>
                <li class="list-group-item d-flex align-items-center">
                    <i class="fas fa-bookmark me-2 text-muted"></i>
                    <?= sanitize($ch['name']) ?>
                    <a href="subjects.php?action=del_chapter&id=<?= $ch['id'] ?>"
                       class="btn btn-sm btn-link text-danger ms-auto p-0"
                       onclick="return confirm('Delete this chapter?')">
                        <i class="fas fa-times"></i>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <div class="card-body py-2 text-muted small">No chapters added</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
