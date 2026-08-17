<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId  = (int)$_SESSION['student_id'];
$scheduleId = (int)($_GET['schedule_id'] ?? 0);

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

$now     = time();
$startTs = strtotime($schedule['scheduled_date'] . ' ' . $schedule['start_time']);
$endTs   = $startTs + ($schedule['duration_minutes'] * 60) + 300;

if ($now < $startTs) {
    setFlash('warning', 'Exam has not started yet. Please wait for the scheduled time.');
    redirect('dashboard.php');
}
if ($now > $endTs) {
    setFlash('error', 'The exam window has closed.');
    redirect('dashboard.php');
}

$existingAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='in_progress'",
    [$scheduleId, $studentId]
);
$completedAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='completed'",
    [$scheduleId, $studentId]
);
if ($completedAttempt) {
    redirect("result_view.php?attempt_id={$completedAttempt['id']}");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid token.'); redirect('dashboard.php');
    }
    if ($existingAttempt) {
        redirect("exam.php?attempt_id={$existingAttempt['id']}");
    }

    $ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $expectedEnd = date('Y-m-d H:i:s', $now + $schedule['duration_minutes'] * 60);
    $attemptId   = db()->insert(
        "INSERT INTO exam_attempts (schedule_id, student_id, exam_id, start_time, expected_end_time, status, ip_address) VALUES (?,?,?,NOW(),?,?,?)",
        [$scheduleId, $studentId, $schedule['exam_db_id'], $expectedEnd, 'in_progress', $ip]
    );

    $questions = db()->fetchAll("
        SELECT eq.question_id, eq.marks, eq.negative_marks, eq.sort_order, q.correct_option
        FROM exam_questions eq
        JOIN questions q ON q.id = eq.question_id
        WHERE eq.exam_id = ? ORDER BY eq.sort_order ASC",
        [$schedule['exam_db_id']]
    );
    if ($schedule['shuffle_questions']) shuffle($questions);

    foreach ($questions as $i => $q) {
        db()->execute(
            "INSERT IGNORE INTO student_answers (attempt_id, student_id, exam_id, question_id, correct_option, sort_order) VALUES (?,?,?,?,?,?)",
            [$attemptId, $studentId, $schedule['exam_db_id'], $q['question_id'], $q['correct_option'], $i + 1]
        );
    }
    db()->execute("UPDATE exam_schedules SET status='active' WHERE id=?", [$scheduleId]);
    logActivity('student', $studentId, 'exam_start', "Started exam schedule: $scheduleId");
    redirect("exam.php?attempt_id=$attemptId");
}

$student = db()->fetchOne("SELECT * FROM students WHERE id=?", [$studentId]);

$defaultRules = [
    'Do NOT leave the examination screen or navigate to other pages.',
    'Do NOT switch browser tabs, windows, or applications during the exam.',
    'Do NOT minimize the browser window at any time.',
    'Do NOT use the browser\'s Back or Forward navigation buttons.',
    'One question is displayed at a time — use the navigation panel to move between questions.',
    'You can mark questions for review and return to them before submitting.',
    'Your answers are automatically saved when you select an option.',
    'The exam timer continues running even if you accidentally refresh the page.',
    'Exceeding the violation limit will automatically terminate and fail your exam.',
    'Submit before the timer expires — the system will auto-submit when time runs out.',
    'All decisions of the examination system are final and binding.',
];
$customRules = $schedule['instructions']
    ? array_filter(array_map('trim', explode("\n", $schedule['instructions'])))
    : [];
$rules = $customRules ?: $defaultRules;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Instructions — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <style>
    :root { --primary: #1a237e; --accent: #3949ab; }
    body { background: #f0f2f8; font-family: 'Segoe UI', system-ui, sans-serif; }

    .av-navbar {
        background: linear-gradient(135deg, #1a237e, #1565c0);
        padding: 0 1.5rem; height: 60px;
        display: flex; align-items: center;
        box-shadow: 0 3px 14px rgba(26,35,126,.35);
    }
    .av-brand { font-weight: 800; font-size: 1.1rem; color: #fff; text-decoration: none; }
    .av-brand span { color: #90caf9; }

    .rules-wrap { max-width: 860px; margin: 2rem auto; padding: 0 1rem; }

    .rules-card {
        background: #fff; border-radius: 20px;
        box-shadow: 0 8px 40px rgba(0,0,0,.12);
        overflow: hidden;
    }
    .rules-hero {
        background: linear-gradient(135deg, #1a237e 0%, #283593 60%, #1565c0 100%);
        padding: 2.5rem 2rem;
        text-align: center;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .rules-hero::before {
        content: '';
        position: absolute; top: -40px; right: -40px;
        width: 160px; height: 160px; border-radius: 50%;
        background: rgba(255,255,255,.07);
    }
    .rules-hero-icon {
        width: 72px; height: 72px; border-radius: 50%;
        background: rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 1rem; font-size: 2rem;
    }
    .rules-hero h2 { font-size: 1.5rem; font-weight: 800; margin-bottom: .35rem; }
    .rules-hero p  { opacity: .8; margin: 0; font-size: .9rem; }

    .rules-info-strip {
        display: flex; flex-wrap: wrap; gap: 0;
        border-bottom: 1px solid #eff0f6;
    }
    .info-block {
        flex: 1; min-width: 130px; text-align: center;
        padding: 1rem .75rem;
        border-right: 1px solid #eff0f6;
    }
    .info-block:last-child { border-right: none; }
    .info-val { font-size: 1.4rem; font-weight: 800; line-height: 1; }
    .info-lbl { font-size: .73rem; color: #78909c; margin-top: .2rem; text-transform: uppercase; letter-spacing: .4px; }

    .rules-body { padding: 1.75rem 2rem; }

    .candidate-bar {
        display: flex; align-items: center; gap: 1rem;
        background: #f0f4ff; border: 1px solid #c5cae9;
        border-radius: 12px; padding: .85rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    .candidate-avatar {
        width: 48px; height: 48px; border-radius: 50%;
        background: var(--accent); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; font-weight: 800; flex-shrink: 0;
        overflow: hidden;
    }
    .candidate-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

    .rules-section-title {
        font-size: .82rem; font-weight: 700;
        color: var(--accent); text-transform: uppercase;
        letter-spacing: .6px; margin-bottom: .85rem;
        display: flex; align-items: center; gap: .4rem;
    }

    .rule-row {
        display: flex; align-items: flex-start; gap: .85rem;
        padding: .65rem 0; border-bottom: 1px solid #f5f5f5;
        transition: background .15s;
    }
    .rule-row:last-child { border-bottom: none; }
    .rule-row:hover { background: #fafbff; margin: 0 -.5rem; padding-left: .5rem; padding-right: .5rem; border-radius: 8px; }
    .rule-num {
        width: 26px; height: 26px; border-radius: 50%;
        background: #e8eaf6; color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-size: .75rem; font-weight: 800; flex-shrink: 0; margin-top: .1rem;
    }
    .rule-text { font-size: .88rem; color: #37474f; line-height: 1.6; }

    .agree-box {
        background: #f8f9ff; border: 2px solid #c5cae9;
        border-radius: 12px; padding: 1.1rem 1.25rem;
        margin-top: 1.5rem; margin-bottom: 1.25rem;
        display: flex; align-items: center; gap: .85rem;
        cursor: pointer; transition: border-color .15s, background .15s;
    }
    .agree-box:hover { border-color: var(--accent); background: #eff1fd; }
    .agree-box input { width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; }
    .agree-box label { font-weight: 600; color: #263238; cursor: pointer; margin: 0; font-size: .9rem; }

    .btn-start-exam {
        background: linear-gradient(135deg, #1b5e20, #388e3c);
        color: #fff; border: none;
        padding: .85rem 2.5rem;
        border-radius: 12px;
        font-size: 1rem; font-weight: 700;
        cursor: pointer; transition: transform .15s, box-shadow .15s;
        box-shadow: 0 4px 16px rgba(27,94,32,.3);
    }
    .btn-start-exam:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(27,94,32,.35);
    }
    .btn-start-exam:disabled {
        opacity: .5; cursor: not-allowed; transform: none; box-shadow: none;
    }

    @media (max-width: 576px) {
        .rules-body { padding: 1.25rem 1rem; }
        .rules-hero  { padding: 1.75rem 1rem; }
        .info-block  { min-width: 100px; padding: .75rem .5rem; }
        .info-val    { font-size: 1.1rem; }
    }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="av-navbar">
    <a class="av-brand" href="dashboard.php">
        <i class="fas fa-graduation-cap me-2"></i>Assess<span>VUE</span>
    </a>
    <a href="dashboard.php" class="btn btn-sm btn-outline-light ms-auto">
        <i class="fas fa-arrow-left me-1"></i>Dashboard
    </a>
</nav>

<div class="rules-wrap">

    <?= renderFlash() ?>

    <div class="rules-card">

        <!-- Hero -->
        <div class="rules-hero">
            <div class="rules-hero-icon"><i class="fas fa-shield-alt"></i></div>
            <h2><?= htmlspecialchars($schedule['exam_name'], ENT_QUOTES) ?></h2>
            <p>
                <i class="fas fa-calendar-alt me-1"></i>
                <?= date('l, d F Y', strtotime($schedule['scheduled_date'])) ?>
                &nbsp;·&nbsp;
                <i class="fas fa-clock me-1"></i>
                <?= date('h:i A', strtotime($schedule['start_time'])) ?> PKT
                &nbsp;·&nbsp;
                <code style="background:rgba(255,255,255,.15);padding:.1rem .5rem;border-radius:4px;font-size:.85rem;">
                    <?= htmlspecialchars($schedule['exam_code'] ?? '', ENT_QUOTES) ?>
                </code>
            </p>
        </div>

        <!-- Info Strip -->
        <div class="rules-info-strip">
            <div class="info-block">
                <div class="info-val text-primary"><?= $schedule['total_questions'] ?></div>
                <div class="info-lbl"><i class="fas fa-question-circle me-1"></i>Questions</div>
            </div>
            <div class="info-block">
                <div class="info-val text-success"><?= $schedule['duration_minutes'] ?></div>
                <div class="info-lbl"><i class="fas fa-clock me-1"></i>Minutes</div>
            </div>
            <div class="info-block">
                <div class="info-val text-warning"><?= $schedule['total_marks'] ?></div>
                <div class="info-lbl"><i class="fas fa-star me-1"></i>Total Marks</div>
            </div>
            <div class="info-block">
                <div class="info-val text-info"><?= $schedule['passing_percentage'] ?>%</div>
                <div class="info-lbl"><i class="fas fa-check me-1"></i>Pass Mark</div>
            </div>
            <?php if ((int)$schedule['max_violations'] > 0): ?>
            <div class="info-block">
                <div class="info-val text-danger"><?= $schedule['max_violations'] ?></div>
                <div class="info-lbl"><i class="fas fa-ban me-1"></i>Max Violations</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="rules-body">

            <!-- Candidate Info -->
            <div class="candidate-bar">
                <div class="candidate-avatar">
                    <?php if ($student['profile_picture']): ?>
                        <img src="serve_file.php?type=profile" alt=""
                             onerror="this.parentElement.textContent='<?= strtoupper(substr($student['full_name'],0,1)) ?>'">
                    <?php else: ?>
                        <?= strtoupper(substr($student['full_name'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div style="font-weight:700;color:#263238;font-size:.95rem;">
                        <?= htmlspecialchars($student['full_name'] ?? '', ENT_QUOTES) ?>
                    </div>
                    <div style="font-size:.8rem;color:#78909c;">
                        <i class="fas fa-id-badge me-1"></i><?= htmlspecialchars($student['student_id'] ?? '', ENT_QUOTES) ?>
                        <?php if ($student['father_name']): ?>
                        &nbsp;·&nbsp; S/O <?= htmlspecialchars($student['father_name'], ENT_QUOTES) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ms-auto">
                    <span style="background:#e8f5e9;color:#1b5e20;padding:.3rem .8rem;border-radius:20px;font-size:.78rem;font-weight:700;">
                        <i class="fas fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                        <?= $existingAttempt ? 'In Progress' : 'Ready to Start' ?>
                    </span>
                </div>
            </div>

            <!-- Rules -->
            <div class="rules-section-title">
                <i class="fas fa-list-ul"></i> Examination Rules & Regulations
            </div>

            <div>
            <?php foreach ($rules as $i => $rule): ?>
            <div class="rule-row">
                <span class="rule-num"><?= $i + 1 ?></span>
                <span class="rule-text"><?= htmlspecialchars($rule, ENT_QUOTES) ?></span>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Agreement + Start -->
            <form method="POST" id="startForm">
                <?= csrfField() ?>
                <div class="agree-box" onclick="toggleAgree()">
                    <input type="checkbox" id="agreeChk" onchange="updateBtn()">
                    <label for="agreeChk">
                        I have read and fully understand all the above rules.
                        I agree to comply with the examination regulations.
                    </label>
                </div>
                <div class="d-flex gap-3 flex-wrap align-items-center">
                    <button type="submit" name="start_exam" value="1"
                            class="btn-start-exam" id="startBtn" disabled>
                        <i class="fas fa-<?= $existingAttempt ? 'play-circle' : 'play' ?> me-2"></i>
                        <?= $existingAttempt ? 'Continue Examination' : 'Begin Examination' ?>
                    </button>
                    <a href="dashboard.php" style="color:#78909c;font-size:.88rem;text-decoration:none;">
                        <i class="fas fa-times me-1"></i>Cancel & Return
                    </a>
                </div>
            </form>

        </div>
    </div>

    <div style="text-align:center;margin-top:1.25rem;font-size:.78rem;color:#90a4ae;">
        <i class="fas fa-lock me-1"></i>
        All activity during this examination is monitored and recorded. &nbsp;·&nbsp; PKT: <?= date('h:i A, d M Y') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAgree() {
    const chk = document.getElementById('agreeChk');
    chk.checked = !chk.checked;
    updateBtn();
}
function updateBtn() {
    document.getElementById('startBtn').disabled =
        !document.getElementById('agreeChk').checked;
}
// Prevent accidental form double-submit
document.getElementById('startForm').addEventListener('submit', function() {
    document.getElementById('startBtn').disabled = true;
    document.getElementById('startBtn').innerHTML =
        '<i class="fas fa-spinner fa-spin me-2"></i>Loading…';
});
</script>
</body>
</html>
