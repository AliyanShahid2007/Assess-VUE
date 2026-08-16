<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Exam Schedules');

$action = $_GET['action'] ?? '';
if ($action === 'delete' && isset($_GET['id'])) {
    db()->execute("DELETE FROM exam_schedules WHERE id=?", [sanitizeInt($_GET['id'])]);
    setFlash('success', 'Schedule deleted.');
    redirect('schedules.php');
}
if ($action === 'cancel' && isset($_GET['id'])) {
    db()->execute("UPDATE exam_schedules SET status='cancelled' WHERE id=?", [sanitizeInt($_GET['id'])]);
    setFlash('success', 'Schedule cancelled.');
    redirect('schedules.php');
}

$filter = $_GET['filter'] ?? '';
$where = "WHERE 1=1";
$params = [];
if ($filter === 'today')     { $where .= " AND es.scheduled_date = CURDATE()"; }
if ($filter === 'upcoming')  { $where .= " AND es.scheduled_date >= CURDATE() AND es.status='scheduled'"; }
if ($filter === 'completed') { $where .= " AND es.status='completed'"; }
if ($filter === 'cancelled') { $where .= " AND es.status='cancelled'"; }

$schedules = db()->fetchAll("
    SELECT es.*, s.full_name, s.student_id as stu_id, e.exam_name, e.exam_code,
           er.percentage, er.result
    FROM exam_schedules es
    JOIN students s ON s.id = es.student_id
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN exam_results er ON er.schedule_id = es.id
    $where
    ORDER BY es.scheduled_date DESC, es.start_time DESC",
    $params
);

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Exam Schedules</h2>
    <a href="schedule_add.php" class="btn btn-primary"><i class="fas fa-calendar-plus me-1"></i>Schedule Exam</a>
</div>

<!-- Filter Tabs -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="schedules.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline-primary' ?>">All</a>
            <a href="schedules.php?filter=today" class="btn btn-sm <?= $filter==='today' ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
            <a href="schedules.php?filter=upcoming" class="btn btn-sm <?= $filter==='upcoming' ? 'btn-success' : 'btn-outline-success' ?>">Upcoming</a>
            <a href="schedules.php?filter=completed" class="btn btn-sm <?= $filter==='completed' ? 'btn-info' : 'btn-outline-info' ?>">Completed</a>
            <a href="schedules.php?filter=cancelled" class="btn btn-sm <?= $filter==='cancelled' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Cancelled</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($schedules as $s): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= sanitize($s['full_name']) ?></div>
                        <small class="text-muted"><?= sanitize($s['stu_id']) ?></small>
                    </td>
                    <td>
                        <div><?= sanitize($s['exam_name']) ?></div>
                        <small class="text-muted"><code><?= sanitize($s['exam_code']) ?></code></small>
                    </td>
                    <td><?= formatDate($s['scheduled_date']) ?></td>
                    <td><?= formatTime($s['start_time']) ?></td>
                    <td><?= $s['duration_minutes'] ?> min</td>
                    <td>
                        <?php $sc = match($s['status']) {
                            'scheduled' => 'badge-scheduled', 'completed' => 'badge-completed',
                            'cancelled' => 'badge-cancelled', 'missed' => 'badge-missed',
                            default => 'badge-pending'
                        }; ?>
                        <span class="badge-status <?= $sc ?>"><?= ucfirst($s['status']) ?></span>
                    </td>
                    <td>
                        <?php if ($s['result']): ?>
                        <span class="badge-status <?= $s['result'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
                            <?= $s['result'] ?> (<?= number_format($s['percentage'], 1) ?>%)
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="schedule_edit.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if ($s['status'] === 'scheduled'): ?>
                            <a href="schedules.php?action=cancel&id=<?= $s['id'] ?>"
                               class="btn btn-outline-warning" title="Cancel"
                               onclick="return confirm('Cancel this exam?')">
                                <i class="fas fa-ban"></i>
                            </a>
                            <?php endif; ?>
                            <a href="schedules.php?action=delete&id=<?= $s['id'] ?>"
                               class="btn btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this schedule?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$schedules): ?>
                <tr><td colspan="8" class="text-center py-4 text-muted">No schedules found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
