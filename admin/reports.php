<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Reports');

$stats = [
    'total_students'  => db()->fetchOne("SELECT COUNT(*) c FROM students")['c'],
    'total_exams'     => db()->fetchOne("SELECT COUNT(*) c FROM exams")['c'],
    'total_results'   => db()->fetchOne("SELECT COUNT(*) c FROM exam_results")['c'],
    'pass'            => db()->fetchOne("SELECT COUNT(*) c FROM exam_results WHERE result='PASS'")['c'],
    'fail'            => db()->fetchOne("SELECT COUNT(*) c FROM exam_results WHERE result='FAIL'")['c'],
    'avg_pct'         => db()->fetchOne("SELECT ROUND(AVG(percentage),1) v FROM exam_results")['v'] ?? 0,
    'violations'      => db()->fetchOne("SELECT COUNT(DISTINCT attempt_id) c FROM exam_violations")['c'],
];

// Top performers
$topStudents = db()->fetchAll("
    SELECT s.full_name, s.student_id as stu_id, AVG(er.percentage) as avg_pct,
           COUNT(er.id) as exam_count, SUM(CASE WHEN er.result='PASS' THEN 1 ELSE 0 END) as pass_count
    FROM exam_results er JOIN students s ON s.id = er.student_id
    GROUP BY er.student_id ORDER BY avg_pct DESC LIMIT 10
");

// Exam pass rates
$examStats = db()->fetchAll("
    SELECT e.exam_name, COUNT(er.id) as total_attempts,
           SUM(CASE WHEN er.result='PASS' THEN 1 ELSE 0 END) as passes,
           ROUND(AVG(er.percentage),1) as avg_pct
    FROM exam_results er JOIN exams e ON e.id = er.exam_id
    GROUP BY er.exam_id ORDER BY total_attempts DESC LIMIT 10
");

include 'includes/header.php';
?>
<?= renderFlash() ?>
<h2 class="mb-4"><i class="fas fa-chart-line me-2 text-primary"></i>Reports &amp; Analytics</h2>

<div class="row g-3 mb-4">
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card blue"><div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['total_students'] ?></div><div class="stat-label">Students</div></div></div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card teal"><div class="stat-icon"><i class="fas fa-clipboard"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['total_results'] ?></div><div class="stat-label">Attempts</div></div></div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card green"><div class="stat-icon"><i class="fas fa-check"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['pass'] ?></div><div class="stat-label">Passed</div></div></div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card red"><div class="stat-icon"><i class="fas fa-times"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['fail'] ?></div><div class="stat-label">Failed</div></div></div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card yellow"><div class="stat-icon"><i class="fas fa-percent"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['avg_pct'] ?>%</div><div class="stat-label">Avg Score</div></div></div>
    </div>
    <div class="col-md-2 col-sm-4 col-6">
        <div class="stat-card orange"><div class="stat-icon"><i class="fas fa-exclamation"></i></div>
            <div class="stat-info"><div class="stat-value"><?= $stats['violations'] ?></div><div class="stat-label">Violations</div></div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-trophy me-2 text-warning"></i>Top Performers</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>#</th><th>Student</th><th>ID</th><th>Exams</th><th>Pass</th><th>Avg</th></tr></thead>
                    <tbody>
                    <?php foreach ($topStudents as $i => $s): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= sanitize($s['full_name']) ?></td>
                        <td><?= sanitize($s['stu_id']) ?></td>
                        <td><?= $s['exam_count'] ?></td>
                        <td><span class="badge bg-success"><?= $s['pass_count'] ?></span></td>
                        <td class="fw-bold text-<?= $s['avg_pct'] >= 60 ? 'success' : 'danger' ?>"><?= $s['avg_pct'] ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-clipboard-list me-2 text-primary"></i>Exam Pass Rates</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Exam</th><th>Attempts</th><th>Pass</th><th>Pass Rate</th><th>Avg</th></tr></thead>
                    <tbody>
                    <?php foreach ($examStats as $e): ?>
                    <tr>
                        <td><?= sanitize($e['exam_name']) ?></td>
                        <td><?= $e['total_attempts'] ?></td>
                        <td><?= $e['passes'] ?></td>
                        <td>
                            <?php $rate = $e['total_attempts'] ? round($e['passes']/$e['total_attempts']*100) : 0; ?>
                            <div class="progress" style="height:8px;width:80px">
                                <div class="progress-bar bg-success" style="width:<?= $rate ?>%"></div>
                            </div>
                            <small><?= $rate ?>%</small>
                        </td>
                        <td class="fw-bold"><?= $e['avg_pct'] ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
