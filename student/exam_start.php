<?php
/**
 * student/exam_start.php — AssessVUE
 * Rules page + exam launch. Handles all attempt states correctly.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId  = (int)$_SESSION['student_id'];
$scheduleId = (int)($_GET['schedule_id'] ?? 0);

if (!$scheduleId) {
    setFlash('error', 'Invalid exam link.');
    redirect('dashboard.php');
}

// ── Load schedule (scheduled, active, or missed-but-still-in-window) ───────
$schedule = db()->fetchOne("
    SELECT es.*, e.exam_name, e.exam_code, e.duration_minutes, e.total_questions,
           e.total_marks, e.passing_percentage, e.max_violations, e.instructions,
           e.marks_per_question, e.negative_marks, e.shuffle_questions,
           e.id as exam_db_id
    FROM exam_schedules es
    JOIN exams e ON e.id = es.exam_id
    WHERE es.id = ? AND es.student_id = ? AND es.status IN ('scheduled','active','missed') AND es.attempt_allowed = 1",
    [$scheduleId, $studentId]
);

if (!$schedule) {
    setFlash('error', 'Exam not found or no longer available.');
    redirect('dashboard.php');
}

// If status is 'missed' but we're still within the time window, restore it to 'scheduled'
$_startTs = strtotime($schedule['scheduled_date'] . ' ' . $schedule['start_time']);
$_endTs   = $_startTs + ($schedule['duration_minutes'] * 60) + 300;
if ($schedule['status'] === 'missed' && time() >= $_startTs && time() <= $_endTs) {
    db()->execute("UPDATE exam_schedules SET status='scheduled' WHERE id=?", [$scheduleId]);
    $schedule['status'] = 'scheduled';
}

// ── Server-side time window check ───────────────────────────
$now     = time();
$startTs = strtotime($schedule['scheduled_date'] . ' ' . $schedule['start_time']);
$endTs   = $startTs + ($schedule['duration_minutes'] * 60) + 300; // +5min grace

if ($now < $startTs) {
    $diff = $startTs - $now;
    $h    = floor($diff / 3600);
    $m    = floor(($diff % 3600) / 60);
    setFlash('warning', "Exam has not started yet. Starts in {$h}h {$m}m. Scheduled: " .
        date('D, d M Y \a\t h:i A', $startTs) . ' PKT');
    redirect('dashboard.php');
}
if ($now > $endTs) {
    setFlash('error', 'The exam window has closed. Please contact your administrator.');
    redirect('dashboard.php');
}

// ── Check existing attempts ──────────────────────────────────
// 1. Already completed → go to result
$completedAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='completed'",
    [$scheduleId, $studentId]
);
if ($completedAttempt) {
    redirect("result_view.php?attempt_id={$completedAttempt['id']}");
}

// 2. Already terminated → show message, cannot retake
$terminatedAttempt = db()->fetchOne(
    "SELECT id, termination_reason FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='terminated'",
    [$scheduleId, $studentId]
);

// 3. In-progress attempt → can resume
$inProgressAttempt = db()->fetchOne(
    "SELECT id FROM exam_attempts WHERE schedule_id=? AND student_id=? AND status='in_progress'",
    [$scheduleId, $studentId]
);

// ── Handle POST: start or resume exam ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch. Please try again.');
        redirect("exam_start.php?schedule_id=$scheduleId");
    }

    // Cannot start if terminated
    if ($terminatedAttempt && !$inProgressAttempt) {
        setFlash('error', 'This exam attempt was terminated. You cannot retake it.');
        redirect('dashboard.php');
    }

    // Resume in-progress
    if ($inProgressAttempt) {
        redirect("exam.php?attempt_id={$inProgressAttempt['id']}");
    }

    // ── Create fresh attempt ─────────────────────────────────
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $expectedEnd = date('Y-m-d H:i:s', $now + $schedule['duration_minutes'] * 60);

    $attemptId = db()->insert(
        "INSERT INTO exam_attempts (schedule_id, student_id, exam_id, start_time, expected_end_time, status, ip_address)
         VALUES (?,?,?,NOW(),?,?,?)",
        [$scheduleId, $studentId, $schedule['exam_db_id'], $expectedEnd, 'in_progress', $ip]
    );

    if (!$attemptId) {
        setFlash('error', 'Could not start exam. Please try again.');
        redirect("exam_start.php?schedule_id=$scheduleId");
    }

    // Load questions
    $qSql      = "SELECT eq.question_id, eq.marks, eq.negative_marks, eq.sort_order, q.correct_option
                  FROM exam_questions eq
                  JOIN questions q ON q.id = eq.question_id
                  WHERE eq.exam_id = ?
                  ORDER BY eq.sort_order ASC";
    $questions = db()->fetchAll($qSql, [$schedule['exam_db_id']]);

    if (empty($questions)) {
        // Roll back attempt if no questions
        db()->execute("DELETE FROM exam_attempts WHERE id=?", [$attemptId]);
        setFlash('error', 'This exam has no questions configured. Contact your administrator.');
        redirect('dashboard.php');
    }

    if ($schedule['shuffle_questions']) shuffle($questions);

    foreach ($questions as $i => $q) {
        db()->execute(
            "INSERT IGNORE INTO student_answers
             (attempt_id, student_id, exam_id, question_id, correct_option, sort_order)
             VALUES (?,?,?,?,?,?)",
            [$attemptId, $studentId, $schedule['exam_db_id'], $q['question_id'], $q['correct_option'], $i + 1]
        );
    }

    db()->execute("UPDATE exam_schedules SET status='active' WHERE id=?", [$scheduleId]);
    logActivity('student', $studentId, 'exam_start', "Started exam schedule ID=$scheduleId, attempt ID=$attemptId");

    // Close session before redirect so next page (exam.php) can start it fresh
    session_write_close();
    header("Location: exam.php?attempt_id=$attemptId");
    exit;
}

// ── Load student for display ─────────────────────────────────
$student = db()->fetchOne("SELECT * FROM students WHERE id=?", [$studentId]);

// ── Build rules list ─────────────────────────────────────────
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

$isResume     = (bool)$inProgressAttempt;
$isTerminated = (bool)$terminatedAttempt && !$inProgressAttempt;
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
    :root { --primary:#1a237e; --accent:#3949ab; }
    body  { background:#f0f2f8; font-family:'Segoe UI',system-ui,sans-serif; }

    .av-navbar {
        background:linear-gradient(135deg,#1a237e,#1565c0);
        padding:0 1.5rem; height:60px;
        display:flex; align-items:center;
        box-shadow:0 3px 14px rgba(26,35,126,.35);
    }
    .av-brand { font-weight:800; font-size:1.1rem; color:#fff; text-decoration:none; }
    .av-brand span { color:#90caf9; }

    .rules-wrap { max-width:860px; margin:2rem auto; padding:0 1rem 3rem; }

    .rules-card {
        background:#fff; border-radius:20px;
        box-shadow:0 8px 40px rgba(0,0,0,.12); overflow:hidden;
    }
    .rules-hero {
        background:linear-gradient(135deg,#1a237e 0%,#283593 60%,#1565c0 100%);
        padding:2.5rem 2rem; text-align:center; color:#fff;
        position:relative; overflow:hidden;
    }
    .rules-hero::before {
        content:''; position:absolute; top:-40px; right:-40px;
        width:160px; height:160px; border-radius:50%;
        background:rgba(255,255,255,.07);
    }
    .rules-hero::after {
        content:''; position:absolute; bottom:-50px; left:-30px;
        width:120px; height:120px; border-radius:50%;
        background:rgba(255,255,255,.05);
    }
    .rules-hero-icon {
        width:72px; height:72px; border-radius:50%;
        background:rgba(255,255,255,.15); border:2px solid rgba(255,255,255,.3);
        display:flex; align-items:center; justify-content:center;
        margin:0 auto 1rem; font-size:2rem; position:relative; z-index:1;
    }
    .rules-hero h2 { font-size:1.5rem; font-weight:800; margin-bottom:.35rem; position:relative; z-index:1; }
    .rules-hero p  { opacity:.8; margin:0; font-size:.9rem; position:relative; z-index:1; }

    .info-strip {
        display:flex; flex-wrap:wrap;
        border-bottom:1px solid #eff0f6;
    }
    .info-block {
        flex:1; min-width:120px; text-align:center;
        padding:1rem .75rem; border-right:1px solid #eff0f6;
    }
    .info-block:last-child { border-right:none; }
    .info-val { font-size:1.4rem; font-weight:800; line-height:1; }
    .info-lbl { font-size:.72rem; color:#78909c; margin-top:.2rem;
                text-transform:uppercase; letter-spacing:.4px; }

    .rules-body { padding:1.75rem 2rem; }

    .candidate-bar {
        display:flex; align-items:center; gap:1rem;
        background:#f0f4ff; border:1px solid #c5cae9;
        border-radius:12px; padding:.85rem 1.25rem; margin-bottom:1.5rem;
    }
    .cand-avatar {
        width:50px; height:50px; border-radius:50%;
        background:var(--accent); color:#fff;
        display:flex; align-items:center; justify-content:center;
        font-size:1.25rem; font-weight:800; flex-shrink:0; overflow:hidden;
    }
    .cand-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; display:block; }

    .terminated-alert {
        background:#fff3f3; border:2px solid #ef9a9a; border-radius:12px;
        padding:1.25rem; margin-bottom:1.25rem; text-align:center;
    }

    .rules-section-title {
        font-size:.82rem; font-weight:700; color:var(--accent);
        text-transform:uppercase; letter-spacing:.6px; margin-bottom:.85rem;
        display:flex; align-items:center; gap:.4rem;
    }
    .rule-row {
        display:flex; align-items:flex-start; gap:.85rem;
        padding:.65rem 0; border-bottom:1px solid #f5f5f5;
    }
    .rule-row:last-child { border-bottom:none; }
    .rule-num {
        width:26px; height:26px; border-radius:50%;
        background:#e8eaf6; color:var(--accent);
        display:flex; align-items:center; justify-content:center;
        font-size:.75rem; font-weight:800; flex-shrink:0; margin-top:.1rem;
    }
    .rule-text { font-size:.88rem; color:#37474f; line-height:1.6; }

    .agree-wrap {
        background:#f8f9ff; border:2px solid #c5cae9; border-radius:12px;
        padding:1rem 1.25rem; margin-top:1.5rem; margin-bottom:1.25rem;
        display:flex; align-items:center; gap:.85rem; cursor:pointer;
        transition:border-color .15s, background .15s; user-select:none;
    }
    .agree-wrap:hover { border-color:var(--accent); background:#eff1fd; }
    .agree-wrap.checked { border-color:#43a047; background:#f1f8f1; }
    .agree-wrap input[type=checkbox] { width:18px; height:18px; cursor:pointer; flex-shrink:0; accent-color:var(--accent); }
    .agree-wrap label { font-weight:600; color:#263238; cursor:pointer; margin:0; font-size:.88rem; }

    .btn-begin {
        background:linear-gradient(135deg,#1b5e20,#388e3c);
        color:#fff; border:none; padding:.85rem 2.5rem;
        border-radius:12px; font-size:1rem; font-weight:700;
        cursor:pointer; transition:transform .15s, box-shadow .15s;
        box-shadow:0 4px 16px rgba(27,94,32,.3);
        display:inline-flex; align-items:center; gap:.5rem;
    }
    .btn-begin:hover:not(:disabled) {
        transform:translateY(-2px); box-shadow:0 8px 24px rgba(27,94,32,.35);
        color:#fff;
    }
    .btn-begin:disabled { opacity:.45; cursor:not-allowed; transform:none; box-shadow:none; }
    .btn-begin.resume {
        background:linear-gradient(135deg,#1565c0,#1976d2);
        box-shadow:0 4px 16px rgba(21,101,192,.3);
    }
    .btn-begin.resume:hover:not(:disabled) { box-shadow:0 8px 24px rgba(21,101,192,.35); }

    @media (max-width:576px) {
        .rules-body { padding:1.25rem 1rem; }
        .rules-hero  { padding:1.75rem 1rem; }
        .info-block  { min-width:100px; padding:.75rem .5rem; }
        .info-val    { font-size:1.1rem; }
    }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="av-navbar">
    <a class="av-brand" href="dashboard.php">
        <i class="fas fa-graduation-cap me-2"></i>Assess<span>VUE</span>
    </a>
    <div class="ms-auto d-flex align-items:center gap-2">
        <span style="color:rgba(255,255,255,.7);font-size:.82rem;margin-right:.75rem;">
            <i class="fas fa-clock me-1"></i><?= date('h:i A') ?> PKT
        </span>
        <a href="dashboard.php" class="btn btn-sm btn-outline-light">
            <i class="fas fa-arrow-left me-1"></i>Dashboard
        </a>
    </div>
</nav>

<div class="rules-wrap">

    <?= renderFlash() ?>

    <div class="rules-card">

        <!-- Hero -->
        <div class="rules-hero">
            <div class="rules-hero-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2><?= htmlspecialchars($schedule['exam_name'], ENT_QUOTES) ?></h2>
            <p>
                <i class="fas fa-calendar-alt me-1"></i>
                <?= date('l, d F Y', strtotime($schedule['scheduled_date'])) ?>
                &nbsp;·&nbsp;
                <i class="fas fa-clock me-1"></i>
                <?= date('h:i A', strtotime($schedule['start_time'])) ?> PKT
                <?php if ($schedule['exam_code']): ?>
                &nbsp;·&nbsp;
                <code style="background:rgba(255,255,255,.15);padding:.1rem .5rem;border-radius:4px;font-size:.82rem;">
                    <?= htmlspecialchars($schedule['exam_code'], ENT_QUOTES) ?>
                </code>
                <?php endif; ?>
            </p>
        </div>

        <!-- Info Strip -->
        <div class="info-strip">
            <div class="info-block">
                <div class="info-val text-primary"><?= (int)$schedule['total_questions'] ?></div>
                <div class="info-lbl"><i class="fas fa-question-circle me-1"></i>Questions</div>
            </div>
            <div class="info-block">
                <div class="info-val text-success"><?= (int)$schedule['duration_minutes'] ?></div>
                <div class="info-lbl"><i class="fas fa-hourglass-half me-1"></i>Minutes</div>
            </div>
            <div class="info-block">
                <div class="info-val text-warning"><?= (int)$schedule['total_marks'] ?></div>
                <div class="info-lbl"><i class="fas fa-star me-1"></i>Total Marks</div>
            </div>
            <div class="info-block">
                <div class="info-val text-info"><?= (int)$schedule['passing_percentage'] ?>%</div>
                <div class="info-lbl"><i class="fas fa-check-circle me-1"></i>Pass Mark</div>
            </div>
            <?php if ((int)$schedule['max_violations'] > 0): ?>
            <div class="info-block">
                <div class="info-val text-danger"><?= (int)$schedule['max_violations'] ?></div>
                <div class="info-lbl"><i class="fas fa-ban me-1"></i>Max Violations</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="rules-body">

            <!-- Candidate Bar -->
            <div class="candidate-bar">
                <div class="cand-avatar">
                    <?php if ($student['profile_picture']): ?>
                    <img src="serve_file.php?type=profile" alt=""
                         onerror="this.style.display='none';this.nextSibling.style.display='flex';">
                    <span style="display:none;width:100%;height:100%;border-radius:50%;
                                 background:var(--accent);align-items:center;justify-content:center;
                                 font-weight:800;color:#fff;font-size:1.1rem;">
                        <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                    </span>
                    <?php else: ?>
                    <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700;color:#263238;font-size:.95rem;">
                        <?= htmlspecialchars($student['full_name'] ?? '', ENT_QUOTES) ?>
                    </div>
                    <div style="font-size:.8rem;color:#78909c;margin-top:.1rem;">
                        <i class="fas fa-id-badge me-1"></i><?= htmlspecialchars($student['student_id'] ?? '', ENT_QUOTES) ?>
                        <?php if (!empty($student['father_name'])): ?>
                        &nbsp;·&nbsp;S/O <?= htmlspecialchars($student['father_name'], ENT_QUOTES) ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <?php if ($isResume): ?>
                    <span style="background:#e3f2fd;color:#1565c0;padding:.3rem .85rem;
                                 border-radius:20px;font-size:.78rem;font-weight:700;">
                        <i class="fas fa-play-circle me-1"></i>In Progress
                    </span>
                    <?php elseif ($isTerminated): ?>
                    <span style="background:#ffebee;color:#c62828;padding:.3rem .85rem;
                                 border-radius:20px;font-size:.78rem;font-weight:700;">
                        <i class="fas fa-ban me-1"></i>Terminated
                    </span>
                    <?php else: ?>
                    <span style="background:#e8f5e9;color:#1b5e20;padding:.3rem .85rem;
                                 border-radius:20px;font-size:.78rem;font-weight:700;">
                        <i class="fas fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>Ready
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isTerminated): ?>
            <!-- Terminated State -->
            <div class="terminated-alert">
                <div style="font-size:2.5rem;color:#ef5350;margin-bottom:.75rem;">
                    <i class="fas fa-ban"></i>
                </div>
                <h5 class="fw-bold text-danger mb-2">Exam Attempt Terminated</h5>
                <p class="text-muted mb-3" style="font-size:.9rem;">
                    This exam attempt was terminated due to rule violations.
                    You cannot retake this exam. Please contact your administrator.
                </p>
                <a href="result_view.php?attempt_id=<?= $terminatedAttempt['id'] ?>"
                   class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-chart-bar me-1"></i>View Result
                </a>
                &nbsp;
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
            </div>

            <?php else: ?>
            <!-- Rules Section -->
            <div class="rules-section-title">
                <i class="fas fa-list-ul"></i> Examination Rules &amp; Regulations
            </div>

            <div class="mb-2">
            <?php foreach ($rules as $i => $rule): ?>
            <div class="rule-row">
                <span class="rule-num"><?= $i + 1 ?></span>
                <span class="rule-text"><?= htmlspecialchars($rule, ENT_QUOTES) ?></span>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Agreement Form -->
            <form method="POST" id="examForm">
                <?= csrfField() ?>
                <div class="agree-wrap" id="agreeWrap" onclick="toggleAgree()">
                    <input type="checkbox" id="agreeChk">
                    <label for="agreeChk">
                        I have read and fully understand all the above rules.
                        I agree to comply with the examination regulations.
                    </label>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button type="submit" name="start_exam" value="1"
                            id="startBtn"
                            class="btn-begin <?= $isResume ? 'resume' : '' ?>"
                            disabled>
                        <i class="fas fa-<?= $isResume ? 'play-circle' : 'play' ?>"></i>
                        <?= $isResume ? 'Resume Examination' : 'Begin Examination' ?>
                    </button>
                    <a href="dashboard.php"
                       style="color:#78909c;font-size:.88rem;text-decoration:none;">
                        <i class="fas fa-times me-1"></i>Cancel
                    </a>
                </div>
            </form>
            <?php endif; ?>

        </div><!-- /rules-body -->
    </div><!-- /rules-card -->

    <div style="text-align:center;margin-top:1.25rem;font-size:.78rem;color:#90a4ae;">
        <i class="fas fa-lock me-1"></i>
        All activity is monitored and recorded. &nbsp;·&nbsp;
        PKT: <?= date('h:i A, d M Y') ?>
    </div>
</div>

<script>
function toggleAgree() {
    const chk  = document.getElementById('agreeChk');
    const wrap = document.getElementById('agreeWrap');
    const btn  = document.getElementById('startBtn');
    if (!chk || !btn) return;
    // If clicking directly on checkbox, let it toggle naturally; otherwise toggle manually
    // We use a small delay to let the native checkbox toggle first
    setTimeout(() => {
        wrap.classList.toggle('checked', chk.checked);
        btn.disabled = !chk.checked;
    }, 0);
}

// Also handle direct checkbox change (for keyboard / direct click on checkbox)
const chk = document.getElementById('agreeChk');
if (chk) {
    chk.addEventListener('change', function() {
        const wrap = document.getElementById('agreeWrap');
        const btn  = document.getElementById('startBtn');
        wrap.classList.toggle('checked', this.checked);
        btn.disabled = !this.checked;
    });
}

// Prevent double-submit
const form = document.getElementById('examForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const btn = document.getElementById('startBtn');
        if (btn.disabled) { e.preventDefault(); return; }
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading…';
    });
}
</script>
</body>
</html>
