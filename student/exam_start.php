<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId  = $_SESSION['student_id'];
$scheduleId = sanitizeInt($_GET['schedule_id'] ?? 0);

// Validate schedule belongs to student
$schedule = db()->fetchOne("
    SELECT es.*, e.exam_name, e.exam_code, e.duration_minutes, e.total_questions,
           e.total_marks, e.passing_percentage, e.max_violations, e.instructions,
           e.marks_per_question, e.negative_marks, e.shuffle_questions,
           e.id as exam_db_id
    FROM exam_schedules es
    JOIN exams e ON e.id = es.exam_id
    WHERE es.id = ? AND es.student_id = ? AND es.status IN ('scheduled','active') AND es.attempt_allowed = 1",
    [$scheduleId, $studentId]
);

if (!$schedule) {
    setFlash('error', 'Exam not found or not available.');
    redirect('dashboard.php');
}

// Server-side time validation
$now = time();
$startTs = strtotime($schedule['scheduled_date'] . ' ' . $schedule['start_time']);
$endTs   = $startTs + ($schedule['duration_minutes'] * 60) + 300; // 5min grace

if ($now < $startTs) {
    setFlash('warning', 'Exam has not started yet. Please wait for the scheduled time.');
    redirect('dashboard.php');
}
if ($now > $endTs) {
    setFlash('error', 'The exam window has closed.');
    redirect('dashboard.php');
}

// Check for existing in-progress attempt
$existingAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='in_progress'",
    [$scheduleId, $studentId]
);
// Check if already completed
$completedAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='completed'",
    [$scheduleId, $studentId]
);
if ($completedAttempt) {
    redirect("result_view.php?attempt_id={$completedAttempt['id']}");
}

// If POST — student accepted rules, start exam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid token.'); redirect('dashboard.php');
    }

    if ($existingAttempt) {
        // Resume
        redirect("exam.php?attempt_id={$existingAttempt['id']}");
    }

    // Create new attempt
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $expectedEnd = date('Y-m-d H:i:s', $now + $schedule['duration_minutes'] * 60);

    $attemptId = db()->insert(
        "INSERT INTO exam_attempts (schedule_id, student_id, exam_id, start_time, expected_end_time, status, ip_address) VALUES (?,?,?,NOW(),?,?,?)",
        [$scheduleId, $studentId, $schedule['exam_db_id'], $expectedEnd, 'in_progress', $ip]
    );

    // Load questions
    $qSql = "SELECT eq.question_id, eq.marks, eq.negative_marks, eq.sort_order,
                    q.correct_option
             FROM exam_questions eq
             JOIN questions q ON q.id = eq.question_id
             WHERE eq.exam_id = ?
             ORDER BY eq.sort_order ASC";
    $questions = db()->fetchAll($qSql, [$schedule['exam_db_id']]);

    if ($schedule['shuffle_questions']) {
        shuffle($questions);
    }

    // Pre-populate student_answers rows
    foreach ($questions as $i => $q) {
        db()->execute("INSERT IGNORE INTO student_answers (attempt_id, student_id, exam_id, question_id, correct_option, sort_order) VALUES (?,?,?,?,?,?)",
            [$attemptId, $studentId, $schedule['exam_db_id'], $q['question_id'], $q['correct_option'], $i+1]);
    }

    // Update schedule status
    db()->execute("UPDATE exam_schedules SET status='active' WHERE id=?", [$scheduleId]);
    logActivity('student', $studentId, 'exam_start', "Started exam schedule: $scheduleId");

    redirect("exam.php?attempt_id=$attemptId");
}

$student = db()->fetchOne("SELECT * FROM students WHERE id=?", [$studentId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Rules — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body style="background: #f0f2f5">

<nav class="student-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <div class="brand"><i class="fas fa-graduation-cap me-2"></i><?= APP_NAME ?></div>
        </div>
    </div>
</nav>

<div class="rules-container px-3">
    <div class="rules-box">
        <div class="rules-header">
            <div class="mb-3" style="font-size:3rem"><i class="fas fa-shield-alt text-primary"></i></div>
            <h3 class="fw-bold text-primary"><?= sanitize($schedule['exam_name']) ?></h3>
            <p class="text-muted mb-0">Please read all rules before beginning the examination</p>
        </div>

        <!-- Exam Info Summary -->
        <div class="row g-2 mb-4">
            <div class="col-6 col-md-3">
                <div class="text-center p-3 bg-primary bg-opacity-10 rounded">
                    <div class="fw-bold text-primary" style="font-size:1.4rem"><?= $schedule['total_questions'] ?></div>
                    <small class="text-muted">Questions</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 bg-success bg-opacity-10 rounded">
                    <div class="fw-bold text-success" style="font-size:1.4rem"><?= $schedule['duration_minutes'] ?> min</div>
                    <small class="text-muted">Duration</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 bg-warning bg-opacity-10 rounded">
                    <div class="fw-bold text-warning" style="font-size:1.4rem"><?= $schedule['total_marks'] ?></div>
                    <small class="text-muted">Total Marks</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center p-3 bg-info bg-opacity-10 rounded">
                    <div class="fw-bold text-info" style="font-size:1.4rem"><?= $schedule['passing_percentage'] ?>%</div>
                    <small class="text-muted">Passing</small>
                </div>
            </div>
        </div>

        <!-- Student Info -->
        <div class="alert alert-primary">
            <strong><i class="fas fa-user me-2"></i>Candidate:</strong>
            <?= sanitize($student['full_name'] ?? '') ?> &nbsp;|&nbsp;
            <strong>ID:</strong> <?= sanitize($student['student_id'] ?? '') ?>
        </div>

        <!-- Rules -->
        <h5 class="fw-bold mb-3"><i class="fas fa-list-ul me-2"></i>Examination Rules & Regulations</h5>
        <?php
        $defaultRules = [
            'Do NOT leave the examination screen or navigate to other pages.',
            'Do NOT switch browser tabs or windows during the examination.',
            'Do NOT minimize the browser window.',
            'Do NOT refresh the examination page unnecessarily.',
            'Do NOT attempt to go back or forward using browser navigation.',
            'One question will be displayed at a time — use navigation to move between questions.',
            'You can mark questions for review and return to them later.',
            'Your answers are automatically saved when you select an option.',
            'The timer will continue even if you accidentally refresh the page.',
            'If you exceed the violation limit, your examination will be automatically terminated and marked as FAIL.',
            'Submit your examination before the timer expires, otherwise it will be auto-submitted.',
            'All decisions of the examination system are final.',
        ];
        $customRules = $schedule['instructions'] ? array_filter(array_map('trim', explode("\n", $schedule['instructions']))) : [];
        $rules = $customRules ?: $defaultRules;
        ?>
        <div class="mb-4">
            <?php foreach ($rules as $i => $rule): ?>
            <div class="rule-item">
                <span class="rule-num"><?= $i+1 ?></span>
                <span><?= sanitize($rule) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Accept & Start -->
        <form method="POST">
            <?= csrfField() ?>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="acceptRules" onchange="document.getElementById('startBtn').disabled = !this.checked">
                <label class="form-check-label fw-semibold" for="acceptRules">
                    I have read and agree to all examination rules and regulations.
                </label>
            </div>
            <div class="d-flex gap-3">
                <button type="submit" name="start_exam" value="1" class="btn btn-success btn-lg px-5" id="startBtn" disabled>
                    <i class="fas fa-play me-2"></i>
                    <?= $existingAttempt ? 'Continue Examination' : 'Start Examination' ?>
                </button>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg px-4">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </form>
    </div>
</div>
</body>
</html>
