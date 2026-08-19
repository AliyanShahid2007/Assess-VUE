<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = $_SESSION['student_id'];
$attemptId = sanitizeInt($_GET['attempt_id'] ?? 0);

$result = db()->fetchOne("
    SELECT er.*, s.full_name, s.father_name, s.student_id as stu_id, s.cnic_number,
           s.id as student_db_id, s.profile_picture,
           e.exam_name, e.exam_code, e.passing_percentage as exam_pass_pct,
           ea.start_time, ea.end_time,
           es.scheduled_date
    FROM exam_results er
    JOIN students s ON s.id = er.student_id
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = er.schedule_id
    WHERE er.attempt_id = ? AND er.student_id = ?",
    [$attemptId, $studentId]
);

if (!$result) {
    setFlash('error', 'Result not found.');
    redirect('dashboard.php');
}

$answers = db()->fetchAll("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.explanation, sub.name as subject_name
    FROM student_answers sa
    JOIN questions q ON q.id = sa.question_id
    LEFT JOIN subjects sub ON sub.id = q.subject_id
    WHERE sa.attempt_id = ?
    ORDER BY sa.sort_order",
    [$attemptId]
);

// Chapter performance
$chapterPerf = [];
foreach ($answers as $a) {
    $sub = $a['subject_name'] ?? 'General';
    if (!isset($chapterPerf[$sub])) $chapterPerf[$sub] = ['correct' => 0, 'total' => 0];
    $chapterPerf[$sub]['total']++;
    if ($a['is_correct']) $chapterPerf[$sub]['correct']++;
}

$timeTaken = $result['time_taken_seconds'];
$pct = (float)$result['percentage'];
$perfLevel = $pct >= 90 ? 'Excellent' : ($pct >= 75 ? 'Very Good' : ($pct >= 60 ? 'Good' : 'Needs Improvement'));
$perfColor = $pct >= 90 ? 'success' : ($pct >= 75 ? 'info' : ($pct >= 60 ? 'warning' : 'danger'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Result — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body style="background:#f0f2f5">

<!-- No print nav -->
<nav class="student-navbar no-print">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <div class="brand"><i class="fas fa-graduation-cap me-2"></i><?= APP_NAME ?></div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-print me-1"></i>Print Report
                </button>
                <a href="dashboard.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container py-4" style="max-width:1000px">

    <!-- Result Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4 align-items-center">
                <!-- Student Photo -->
                <div class="col-auto">
                    <?php if ($result['profile_picture']): ?>
                        <img src="serve_file.php?type=profile"
                             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid <?= $result['result'] === 'PASS' ? '#43a047' : '#e53935' ?>">
                    <?php else: ?>
                        <div style="width:100px;height:100px;border-radius:50%;background:#e8eaf6;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#3949ab">
                            <?= strtoupper(substr($result['full_name'],0,1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Student Info -->
                <div class="col flex-grow-1">
                    <h4 class="fw-bold mb-1"><?= sanitize($result['full_name']) ?></h4>
                    <div class="text-muted">
                        <?php if ($result['father_name']): ?>
                        <span>Father: <?= sanitize($result['father_name']) ?></span> &nbsp;|&nbsp;
                        <?php endif; ?>
                        <span>ID: <strong><?= sanitize($result['stu_id']) ?></strong></span>
                        <?php if ($result['cnic_number']): ?>
                        &nbsp;|&nbsp;<span>CNIC: <?= sanitize($result['cnic_number']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted mt-1">
                        <span><?= sanitize($result['exam_name']) ?></span>
                        &nbsp;|&nbsp;<code><?= sanitize($result['exam_code']) ?></code>
                    </div>
                </div>
                <!-- Result Badge -->
                <div class="col-auto text-center">
                    <div class="<?= $result['result'] === 'PASS' ? 'result-pass-bg' : 'result-fail-bg' ?> p-4 px-5">
                        <div class="result-badge <?= $result['result'] === 'PASS' ? 'text-success' : 'text-danger' ?>">
                            <?= $result['result'] ?>
                        </div>
                        <div style="font-size:2rem;font-weight:700"><?= number_format($pct, 2) ?>%</div>
                        <div class="text-muted">Net Score: <?= number_format((float)$result['obtained_marks'], 2) ?> / <?= number_format((float)$result['total_marks'], 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Details -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Exam Details</div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted">Exam Date</th><td><?= formatDate($result['scheduled_date']) ?></td></tr>
                        <tr><th class="text-muted">Start Time</th><td><?= $result['start_time'] ? formatTime($result['start_time']) : '—' ?></td></tr>
                        <tr><th class="text-muted">End Time</th><td><?= $result['end_time'] ? formatTime($result['end_time']) : '—' ?></td></tr>
                        <tr><th class="text-muted">Time Taken</th><td><?= $timeTaken ? secondsToTime($timeTaken) : '—' ?></td></tr>
                        <tr><th class="text-muted">Pass Mark</th><td><?= $result['passing_percentage'] ?>%</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><i class="fas fa-chart-pie me-2"></i>Score Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="p-2 bg-primary bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-primary fs-5"><?= $result['total_questions'] ?></div>
                            <small class="text-muted">Total Qs</small>
                        </div></div>
                        <div class="col-6"><div class="p-2 bg-info bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-info fs-5"><?= $result['attempted_questions'] ?></div>
                            <small class="text-muted">Attempted</small>
                        </div></div>
                        <div class="col-4"><div class="p-2 bg-success bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-success fs-5"><?= $result['correct_answers'] ?></div>
                            <small class="text-muted">Correct</small>
                        </div></div>
                        <div class="col-4"><div class="p-2 bg-danger bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-danger fs-5"><?= $result['incorrect_answers'] ?></div>
                            <small class="text-muted">Wrong</small>
                        </div></div>
                        <div class="col-4"><div class="p-2 bg-secondary bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-secondary fs-5"><?= $result['unanswered'] ?></div>
                            <small class="text-muted">Skipped</small>
                        </div></div>
                        <div class="col-6"><div class="p-2 bg-success bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-success fs-6"><?= number_format((float)($result['obtained_marks'] + ($result['negative_marks_total'] ?? 0)), 2) ?></div>
                            <small class="text-muted">Correct Marks</small>
                        </div></div>
                        <div class="col-6"><div class="p-2 bg-danger bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-danger fs-6"><?= number_format(-(float)($result['negative_marks_total'] ?? 0), 2) ?></div>
                            <small class="text-muted">Wrong Marks</small>
                        </div></div>
                        <div class="col-6"><div class="p-2 bg-primary bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-primary fs-6"><?= number_format((float)$result['obtained_marks'], 2) ?></div>
                            <small class="text-muted">Net Score</small>
                        </div></div>
                        <div class="col-6"><div class="p-2 bg-info bg-opacity-10 rounded text-center">
                            <div class="fw-bold text-info fs-6"><?= number_format($pct, 2) ?>%</div>
                            <small class="text-muted">Percentage</small>
                        </div></div>
                    </div>
                    <?php if ($result['violation_terminated']): ?>
                    <div class="alert alert-danger py-1 mt-2 mb-0 small">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Terminated due to rule violations.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Analysis -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-chart-bar me-2"></i>Performance Analysis</div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-4 text-center">
                    <div class="badge bg-<?= $perfColor ?> px-4 py-2 fs-6 mb-2"><?= $perfLevel ?></div>
                    <div class="progress mt-2" style="height:12px">
                        <div class="progress-bar bg-<?= $perfColor ?>" style="width:<?= $pct ?>%"></div>
                    </div>
                    <small class="text-muted"><?= number_format($pct, 1) ?>% Overall</small>
                </div>
                <div class="col-md-8">
                    <?php if ($chapterPerf): ?>
                    <div class="row g-2">
                        <?php foreach ($chapterPerf as $sub => $perf): ?>
                        <?php $subPct = $perf['total'] > 0 ? round(($perf['correct'] / $perf['total']) * 100) : 0; ?>
                        <div class="col-12">
                            <div class="d-flex align-items-center gap-2">
                                <div style="width:120px;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    <?= sanitize($sub) ?>
                                </div>
                                <div class="progress flex-grow-1" style="height:10px">
                                    <div class="progress-bar bg-<?= $subPct >= 75 ? 'success' : ($subPct >= 50 ? 'warning' : 'danger') ?>"
                                         style="width:<?= $subPct ?>%"></div>
                                </div>
                                <div style="width:40px;font-size:.85rem;text-align:right"><?= $subPct ?>%</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Analysis (toggle) -->
    <div class="card no-print mb-3">
        <div class="card-header d-flex align-items-center">
            <div><i class="fas fa-list-ol me-2"></i>Detailed Question Analysis</div>
            <button class="btn btn-sm btn-outline-primary ms-auto" type="button"
                    data-bs-toggle="collapse" data-bs-target="#qAnalysis">
                Show/Hide
            </button>
        </div>
        <div class="collapse" id="qAnalysis">
            <div class="card-body p-0">
                <?php foreach ($answers as $i => $a): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex align-items-start gap-3">
                        <span class="badge bg-<?= $a['is_correct'] ? 'success' : ($a['is_answered'] ? 'danger' : 'secondary') ?> flex-shrink-0 mt-1">
                            <?= $i+1 ?>
                        </span>
                        <div class="flex-grow-1">
                            <p class="mb-1 fw-semibold small"><?= sanitize($a['question_text']) ?></p>
                            <div class="d-flex gap-2 flex-wrap small">
                                <span>Your Answer: <strong><?= $a['selected_option'] ? 'Option ' . $a['selected_option'] . ' — ' . sanitize($a['option_' . strtolower($a['selected_option'])]) : '—' ?></strong></span>
                                <span class="text-success">Correct: <strong>Option <?= $a['correct_option'] ?></strong></span>
                                <span class="fw-bold <?= $a['marks_awarded'] > 0 ? 'text-success' : ($a['marks_awarded'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                    <?= $a['marks_awarded'] > 0 ? '+' : '' ?><?= $a['marks_awarded'] ?> marks
                                </span>
                                <span class="badge-status <?= $a['is_correct'] ? 'badge-pass' : ($a['is_answered'] ? 'badge-fail' : 'badge-pending') ?>">
                                    <?= $a['is_correct'] ? 'Correct' : ($a['is_answered'] ? 'Incorrect' : 'Skipped') ?>
                                </span>
                            </div>
                            <?php if ($a['explanation']): ?>
                            <div class="mt-1 small text-muted">
                                <i class="fas fa-lightbulb me-1 text-warning"></i><?= sanitize($a['explanation']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 no-print">
        <button onclick="window.print()" class="btn btn-outline-primary">
            <i class="fas fa-print me-2"></i>Print Report
        </button>
        <a href="dashboard.php" class="btn btn-primary">
            <i class="fas fa-home me-2"></i>Back to Dashboard
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
