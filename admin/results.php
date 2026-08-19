<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Examination Results');

$search = trim($_GET['search'] ?? '');
$resultFilter = $_GET['result'] ?? '';
$examFilter = sanitizeInt($_GET['exam_id'] ?? 0);

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (s.full_name LIKE ? OR s.student_id LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
if (in_array($resultFilter, ['PASS','FAIL'])) {
    $where .= " AND er.result=?"; $params[] = $resultFilter;
}
if ($examFilter) {
    $where .= " AND er.exam_id=?"; $params[] = $examFilter;
}

$results = db()->fetchAll("
    SELECT er.*, s.full_name, s.student_id as stu_id, s.id as student_db_id,
           e.exam_name, e.exam_code,
           ea.start_time, ea.end_time, ea.id as attempt_id,
           es.scheduled_date
    FROM exam_results er
    JOIN students s ON s.id = er.student_id
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = er.schedule_id
    $where
    ORDER BY er.calculated_at DESC",
    $params
);

$exams = db()->fetchAll("SELECT id, exam_name FROM exams ORDER BY exam_name");
include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Examination Results</h2>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search student..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-sm-3">
                <select name="exam_id" class="form-select form-select-sm">
                    <option value="0">All Exams</option>
                    <?php foreach ($exams as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $examFilter == $e['id'] ? 'selected' : '' ?>>
                        <?= sanitize($e['exam_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="result" class="form-select form-select-sm">
                    <option value="">All Results</option>
                    <option value="PASS" <?= $resultFilter === 'PASS' ? 'selected' : '' ?>>Pass</option>
                    <option value="FAIL" <?= $resultFilter === 'FAIL' ? 'selected' : '' ?>>Fail</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="results.php" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fas fa-list me-2"></i>All Results (<?= count($results) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Exam</th>
                        <th>Date</th>
                        <th>Score Breakdown</th>
                        <th>Percentage</th>
                        <th>Pass %</th>
                        <th>Time Taken</th>
                        <th>Result</th>
                        <th>Violation</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $r): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= sanitize($r['full_name']) ?></div>
                        <small class="text-muted"><?= sanitize($r['stu_id']) ?></small>
                    </td>
                    <td>
                        <div><?= sanitize($r['exam_name']) ?></div>
                        <small class="text-muted"><code><?= sanitize($r['exam_code']) ?></code></small>
                    </td>
                    <td>
                        <div><?= formatDate($r['scheduled_date']) ?></div>
                        <small class="text-muted"><?= $r['start_time'] ? formatTime($r['start_time']) : '—' ?></small>
                    </td>
                    <td class="small">
                        <div>Correct: <strong class="text-success"><?= (int)$r['correct_answers'] ?>/<?= (int)$r['total_questions'] ?></strong></div>
                        <div>Incorrect: <strong class="text-danger"><?= (int)$r['incorrect_answers'] ?></strong></div>
                        <div>Score (out of 100): <strong><?= number_format((float)$r['percentage'], 2) ?>/100</strong></div>
                    </td>
                    <td class="fw-bold <?= $r['percentage'] >= $r['passing_percentage'] ? 'text-success' : 'text-danger' ?>">
                        <?= number_format((float)$r['percentage'], 2) ?>%
                    </td>
                    <td class="text-muted"><?= $r['passing_percentage'] ?>%</td>
                    <td><?= $r['time_taken_seconds'] ? secondsToTime($r['time_taken_seconds']) : '—' ?></td>
                    <td>
                        <span class="badge-status <?= $r['result'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
                            <?= $r['result'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($r['violation_terminated']): ?>
                        <span class="badge bg-danger">Yes</span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="attempt_view.php?id=<?= $r['attempt_id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$results): ?>
                <tr><td colspan="10" class="text-center py-4 text-muted">No results yet</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
