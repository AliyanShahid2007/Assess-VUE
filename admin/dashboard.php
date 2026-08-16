<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

define('PAGE_TITLE', 'Dashboard');

// ---- Stats ----
$stats = [
    'total_students'   => db()->fetchOne("SELECT COUNT(*) c FROM students")['c'],
    'active_students'  => db()->fetchOne("SELECT COUNT(*) c FROM students WHERE is_active=1")['c'],
    'total_exams'      => db()->fetchOne("SELECT COUNT(*) c FROM exams")['c'],
    'active_exams'     => db()->fetchOne("SELECT COUNT(*) c FROM exams WHERE status='active'")['c'],
    'scheduled'        => db()->fetchOne("SELECT COUNT(*) c FROM exam_schedules WHERE status='scheduled'")['c'],
    'upcoming_today'   => db()->fetchOne("SELECT COUNT(*) c FROM exam_schedules WHERE scheduled_date=CURDATE() AND status='scheduled'")['c'],
    'completed'        => db()->fetchOne("SELECT COUNT(*) c FROM exam_results")['c'],
    'passed'           => db()->fetchOne("SELECT COUNT(*) c FROM exam_results WHERE result='PASS'")['c'],
    'failed'           => db()->fetchOne("SELECT COUNT(*) c FROM exam_results WHERE result='FAIL'")['c'],
    'total_questions'  => db()->fetchOne("SELECT COUNT(*) c FROM questions WHERE is_active=1")['c'],
];

// ---- Recent Results ----
$recentResults = db()->fetchAll("
    SELECT er.*, s.full_name, s.student_id as stu_id, e.exam_name, e.exam_code,
           ea.start_time, ea.end_time
    FROM exam_results er
    JOIN students s ON s.id = er.student_id
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    ORDER BY er.calculated_at DESC
    LIMIT 10
");

// ---- Upcoming Schedules ----
$upcomingSchedules = db()->fetchAll("
    SELECT es.*, s.full_name, s.student_id as stu_id, e.exam_name, e.duration_minutes
    FROM exam_schedules es
    JOIN students s ON s.id = es.student_id
    JOIN exams e ON e.id = es.exam_id
    WHERE es.scheduled_date >= CURDATE() AND es.status = 'scheduled'
    ORDER BY es.scheduled_date ASC, es.start_time ASC
    LIMIT 8
");

include 'includes/header.php';
?>

<?= renderFlash() ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['total_students'] ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card purple">
            <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['total_exams'] ?></div>
                <div class="stat-label">Total Exams</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card orange">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['scheduled'] ?></div>
                <div class="stat-label">Scheduled Exams</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card cyan">
            <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['upcoming_today'] ?></div>
                <div class="stat-label">Today's Exams</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card teal">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-thumbs-up"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['passed'] ?></div>
                <div class="stat-label">Passed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card red">
            <div class="stat-icon"><i class="fas fa-thumbs-down"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['failed'] ?></div>
                <div class="stat-label">Failed</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card yellow">
            <div class="stat-icon"><i class="fas fa-database"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['total_questions'] ?></div>
                <div class="stat-label">Questions Bank</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
    <div class="card-header d-flex align-items-center">
        <i class="fas fa-bolt me-2 text-warning"></i> Quick Actions
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6 col-sm-4 col-md-2">
                <a href="student_add.php" class="quick-action">
                    <i class="fas fa-user-plus"></i>
                    <span>Add Student</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="exam_add.php" class="quick-action">
                    <i class="fas fa-plus-square"></i>
                    <span>Create Exam</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="pdf_import.php" class="quick-action">
                    <i class="fas fa-file-upload"></i>
                    <span>Upload PDF</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="schedule_add.php" class="quick-action">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Schedule Exam</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="results.php" class="quick-action">
                    <i class="fas fa-chart-bar"></i>
                    <span>View Results</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <a href="students.php" class="quick-action">
                    <i class="fas fa-users"></i>
                    <span>View Students</span>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Upcoming Schedules -->
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="fas fa-calendar-alt me-2 text-primary"></i> Upcoming Schedules</div>
                <a href="schedules.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if ($upcomingSchedules): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Date &amp; Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($upcomingSchedules as $s): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($s['full_name']) ?></div>
                                    <small class="text-muted"><?= sanitize($s['stu_id']) ?></small>
                                </td>
                                <td><?= sanitize($s['exam_name']) ?></td>
                                <td>
                                    <div><?= formatDate($s['scheduled_date']) ?></div>
                                    <small class="text-muted"><?= formatTime($s['start_time']) ?></small>
                                </td>
                                <td><span class="badge-status badge-scheduled">Scheduled</span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                    No upcoming exams scheduled
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Results -->
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="fas fa-chart-bar me-2 text-success"></i> Recent Results</div>
                <a href="results.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentResults): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Exam</th>
                                <th>Score</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentResults as $r): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= sanitize($r['full_name']) ?></div>
                                    <small class="text-muted"><?= sanitize($r['stu_id']) ?></small>
                                </td>
                                <td>
                                    <div><?= sanitize($r['exam_name']) ?></div>
                                    <small class="text-muted"><?= sanitize($r['exam_code']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= number_format($r['percentage'], 1) ?>%</div>
                                    <small class="text-muted"><?= $r['obtained_marks'] ?>/<?= $r['total_marks'] ?></small>
                                </td>
                                <td>
                                    <span class="badge-status <?= $r['result'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
                                        <?= $r['result'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                    No results yet
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
