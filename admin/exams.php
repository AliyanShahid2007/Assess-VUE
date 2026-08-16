<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Exams');

$action = $_GET['action'] ?? '';
if ($action === 'delete' && isset($_GET['id'])) {
    $id = sanitizeInt($_GET['id']);
    db()->execute("DELETE FROM exams WHERE id=?", [$id]);
    setFlash('success', 'Exam deleted.');
    redirect('exams.php');
}
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = sanitizeInt($_GET['id']);
    $e = db()->fetchOne("SELECT status FROM exams WHERE id=?", [$id]);
    if ($e) {
        $new = $e['status'] === 'active' ? 'draft' : 'active';
        db()->execute("UPDATE exams SET status=? WHERE id=?", [$new, $id]);
        setFlash('success', 'Exam status updated.');
    }
    redirect('exams.php');
}

$exams = db()->fetchAll("
    SELECT e.*, sub.name as subject_name,
           COUNT(DISTINCT eq.id) as q_count,
           COUNT(DISTINCT er.id) as attempt_count
    FROM exams e
    LEFT JOIN subjects sub ON sub.id = e.subject_id
    LEFT JOIN exam_questions eq ON eq.exam_id = e.id
    LEFT JOIN exam_results er ON er.exam_id = e.id
    GROUP BY e.id
    ORDER BY e.created_at DESC
");

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0"><i class="fas fa-clipboard-list me-2 text-primary"></i>Examinations</h2>
    <a href="exam_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Create Exam</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Exam Name</th>
                        <th>Code</th>
                        <th>Subject</th>
                        <th>Questions</th>
                        <th>Duration</th>
                        <th>Pass %</th>
                        <th>Attempts</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exams as $ex): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= sanitize($ex['exam_name']) ?></div>
                        <small class="text-muted"><?= sanitize($ex['description'] ?? '') ?></small>
                    </td>
                    <td><code><?= sanitize($ex['exam_code']) ?></code></td>
                    <td><?= sanitize($ex['subject_name'] ?? '—') ?></td>
                    <td><span class="badge bg-primary"><?= $ex['q_count'] ?></span></td>
                    <td><?= $ex['duration_minutes'] ?> min</td>
                    <td><?= $ex['passing_percentage'] ?>%</td>
                    <td><?= $ex['attempt_count'] ?></td>
                    <td>
                        <span class="badge-status <?= $ex['status'] === 'active' ? 'badge-active' : ($ex['status'] === 'archived' ? 'badge-inactive' : 'badge-pending') ?>">
                            <?= ucfirst($ex['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="exam_questions.php?exam_id=<?= $ex['id'] ?>" class="btn btn-outline-secondary" title="Manage Questions">
                                <i class="fas fa-question"></i>
                            </a>
                            <a href="exam_edit.php?id=<?= $ex['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="exams.php?action=toggle&id=<?= $ex['id'] ?>"
                               class="btn btn-outline-<?= $ex['status'] === 'active' ? 'warning' : 'success' ?>" title="Toggle Status">
                                <i class="fas fa-toggle-<?= $ex['status'] === 'active' ? 'on' : 'off' ?>"></i>
                            </a>
                            <a href="exams.php?action=delete&id=<?= $ex['id'] ?>"
                               class="btn btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this exam?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$exams): ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">No exams created yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
