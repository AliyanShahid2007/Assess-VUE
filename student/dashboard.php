<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = $_SESSION['student_id'];
$student = db()->fetchOne("SELECT * FROM students WHERE id=?", [$studentId]);
if (!$student || !$student['is_active']) {
    session_destroy();
    redirect('login.php');
}

// Get all assigned exams with schedule info
$now = date('Y-m-d H:i:s');
$schedules = db()->fetchAll("
    SELECT es.*, e.exam_name, e.exam_code, e.duration_minutes, e.total_questions,
           e.passing_percentage, e.total_marks,
           er.result, er.percentage, er.id as result_id,
           ea.id as attempt_id, ea.status as attempt_status
    FROM exam_schedules es
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN exam_attempts ea ON ea.schedule_id = es.id AND ea.student_id = ?
    LEFT JOIN exam_results er ON er.attempt_id = ea.id
    WHERE es.student_id = ?
    ORDER BY es.scheduled_date ASC, es.start_time ASC",
    [$studentId, $studentId]
);

// Determine status for each schedule
foreach ($schedules as &$s) {
    $schedDT = $s['scheduled_date'] . ' ' . $s['start_time'];
    $endDT   = $s['scheduled_date'] . ' ' . ($s['end_time'] ?? date('H:i:s', strtotime($s['start_time']) + $s['duration_minutes'] * 60));

    $nowTs   = time();
    $startTs = strtotime($schedDT);
    $endTs   = strtotime($endDT);

    if ($s['attempt_status'] === 'completed' || $s['result_id']) {
        $s['avail_status'] = 'completed';
    } elseif ($s['attempt_status'] === 'in_progress') {
        $s['avail_status'] = 'in_progress';
    } elseif ($s['status'] === 'cancelled') {
        $s['avail_status'] = 'cancelled';
    } elseif ($nowTs < $startTs) {
        $s['avail_status'] = 'upcoming';
        $s['countdown_seconds'] = $startTs - $nowTs;
    } elseif ($nowTs >= $startTs && $nowTs <= $endTs + 300 && $s['attempt_allowed']) {
        $s['avail_status'] = 'available';
    } else {
        $s['avail_status'] = 'expired';
        if ($s['status'] === 'scheduled') {
            db()->execute("UPDATE exam_schedules SET status='missed' WHERE id=?", [$s['id']]);
        }
    }
}
unset($s);

// Recent results
$results = db()->fetchAll("
    SELECT er.*, e.exam_name, ea.start_time, es.scheduled_date
    FROM exam_results er
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = er.schedule_id
    WHERE er.student_id = ?
    ORDER BY er.calculated_at DESC
    LIMIT 5",
    [$studentId]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body>
<!-- Student Navbar -->
<nav class="student-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-3">
            <div class="brand"><i class="fas fa-graduation-cap me-2"></i><?= APP_NAME ?></div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <?php if ($student['profile_picture']): ?>
                        <img src="../admin/serve_file.php?type=profile&id=<?= $studentId ?>"
                             class="stu-nav-avatar">
                    <?php else: ?>
                        <div class="stu-nav-placeholder"><?= strtoupper(substr($student['full_name'],0,1)) ?></div>
                    <?php endif; ?>
                    <div>
                        <div class="nav-student-name"><?= sanitize($student['full_name']) ?></div>
                        <div class="nav-student-id"><?= sanitize($student['student_id']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <?= renderFlash() ?>

    <!-- Profile Summary -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card text-center student-profile-card">
                <div class="card-body">
                    <?php if ($student['profile_picture']): ?>
                        <img src="../admin/serve_file.php?type=profile&id=<?= $studentId ?>"
                             class="student-profile-pic mb-2">
                    <?php else: ?>
                        <div class="stu-avatar-lg mx-auto mb-2"><?= strtoupper(substr($student['full_name'],0,1)) ?></div>
                    <?php endif; ?>
                    <h5 class="mb-0"><?= sanitize($student['full_name']) ?></h5>
                    <p class="text-muted small"><?= sanitize($student['father_name'] ?? '') ?></p>
                    <span class="badge bg-success px-3"><?= sanitize($student['student_id']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-9">
            <!-- Exam Cards -->
            <h5 class="fw-semibold mb-3"><i class="fas fa-clipboard-list me-2 text-primary"></i>My Assigned Examinations</h5>
            <?php if ($schedules): ?>
            <div class="row g-3">
            <?php foreach ($schedules as $s): ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="exam-card <?= $s['avail_status'] ?>">
                    <div class="exam-card-header">
                        <?php
                        $statusIcon = match($s['avail_status']) {
                            'available'    => '<i class="fas fa-play-circle text-success"></i>',
                            'upcoming'     => '<i class="fas fa-clock text-warning"></i>',
                            'completed'    => '<i class="fas fa-check-circle text-info"></i>',
                            'in_progress'  => '<i class="fas fa-spinner fa-spin text-primary"></i>',
                            'expired'      => '<i class="fas fa-times-circle text-secondary"></i>',
                            'cancelled'    => '<i class="fas fa-ban text-muted"></i>',
                            default        => ''
                        };
                        echo $statusIcon;
                        ?>
                        <span class="exam-name"><?= sanitize($s['exam_name']) ?></span>
                    </div>
                    <div class="exam-card-body">
                        <div class="exam-meta">
                            <i class="fas fa-calendar me-1"></i><?= formatDate($s['scheduled_date']) ?>
                            &nbsp;|&nbsp;
                            <i class="fas fa-clock me-1"></i><?= formatTime($s['start_time']) ?>
                        </div>
                        <div class="exam-meta">
                            <i class="fas fa-question-circle me-1"></i><?= $s['total_questions'] ?> Qs
                            &nbsp;|&nbsp;
                            <i class="fas fa-hourglass me-1"></i><?= $s['duration_minutes'] ?> min
                            &nbsp;|&nbsp;
                            Pass: <?= $s['passing_percentage'] ?>%
                        </div>

                        <?php if ($s['avail_status'] === 'available'): ?>
                            <a href="exam_start.php?schedule_id=<?= $s['id'] ?>" class="btn btn-success btn-sm w-100 mt-2">
                                <i class="fas fa-play me-1"></i>Start Examination
                            </a>
                        <?php elseif ($s['avail_status'] === 'in_progress'): ?>
                            <a href="exam_start.php?schedule_id=<?= $s['id'] ?>" class="btn btn-primary btn-sm w-100 mt-2">
                                <i class="fas fa-arrow-right me-1"></i>Continue Exam
                            </a>
                        <?php elseif ($s['avail_status'] === 'upcoming'): ?>
                            <div class="countdown-box mt-2" data-seconds="<?= $s['countdown_seconds'] ?? 0 ?>">
                                <i class="fas fa-hourglass-start me-1"></i>
                                Starts in: <span class="countdown-timer">--:--:--</span>
                            </div>
                        <?php elseif ($s['avail_status'] === 'completed'): ?>
                            <div class="result-summary mt-2">
                                <span class="<?= $s['result'] === 'PASS' ? 'text-success' : 'text-danger' ?> fw-bold">
                                    <?= $s['result'] ?> — <?= number_format($s['percentage'], 1) ?>%
                                </span>
                            </div>
                            <a href="result_view.php?attempt_id=<?= $s['attempt_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-1">
                                <i class="fas fa-file-alt me-1"></i>View Report
                            </a>
                        <?php elseif ($s['avail_status'] === 'expired'): ?>
                            <div class="text-muted small mt-2"><i class="fas fa-times-circle me-1"></i>Examination window closed</div>
                        <?php elseif ($s['avail_status'] === 'cancelled'): ?>
                            <div class="text-muted small mt-2"><i class="fas fa-ban me-1"></i>Cancelled by administrator</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No exams assigned yet. Your administrator will assign examinations to your account.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Results -->
    <?php if ($results): ?>
    <div class="card">
        <div class="card-header fw-semibold"><i class="fas fa-history me-2 text-primary"></i>Recent Results</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Score</th>
                            <th>Result</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= sanitize($r['exam_name']) ?></td>
                        <td><?= formatDate($r['scheduled_date']) ?></td>
                        <td><?= $r['obtained_marks'] ?>/<?= $r['total_marks'] ?></td>
                        <td>
                            <span class="badge-status <?= $r['result'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
                                <?= $r['result'] ?> (<?= number_format($r['percentage'], 1) ?>%)
                            </span>
                        </td>
                        <td>
                            <a href="result_view.php?attempt_id=<?= $r['attempt_id'] ?? '' ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Countdown timers
document.querySelectorAll('.countdown-box').forEach(box => {
    let secs = parseInt(box.dataset.seconds) || 0;
    const el = box.querySelector('.countdown-timer');
    const tick = () => {
        if (secs <= 0) { el.textContent = 'Starting soon...'; location.reload(); return; }
        const h = Math.floor(secs/3600), m = Math.floor((secs%3600)/60), s = secs%60;
        el.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        secs--;
        setTimeout(tick, 1000);
    };
    tick();
});
</script>
</body>
</html>
