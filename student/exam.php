<?php
/**
 * student/exam.php  — AssessVUE Examination Interface
 *
 * KEY ARCHITECTURE:
 *  • PHP built-in server is single-threaded. To prevent AJAX blocking the next
 *    page-load, we call session_write_close() immediately after reading session
 *    data — releasing the session lock so concurrent requests don't queue up.
 *  • All navigation uses async JS: goToQuestion() awaits saveAnswerAsync() before
 *    setting window.location.  The save URL is a dedicated save-only endpoint
 *    (save_answer.php) so the save POST never competes with the next page GET.
 *  • Marks always come from exam_questions.marks — never from student_answers.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = (int)$_SESSION['student_id'];
$attemptId = (int)($_GET['attempt_id'] ?? 0);

// Release session lock immediately so AJAX can run concurrently
session_write_close();

// ── Load attempt ─────────────────────────────────────────────
$attempt = db()->fetchOne("
    SELECT ea.*, es.scheduled_date, es.start_time as sched_start,
           e.exam_name, e.total_questions, e.max_violations, e.passing_percentage,
           e.marks_per_question, e.negative_marks, e.id as exam_db_id,
           s.full_name, s.profile_picture, s.student_id as stu_code
    FROM exam_attempts ea
    JOIN exam_schedules es ON es.id = ea.schedule_id
    JOIN exams          e  ON e.id  = ea.exam_id
    JOIN students       s  ON s.id  = ea.student_id
    WHERE ea.id = ? AND ea.student_id = ?",
    [$attemptId, $studentId]
);

if (!$attempt) {
    startSecureSession();
    setFlash('error', 'Exam attempt not found.');
    session_write_close();
    header('Location: dashboard.php'); exit;
}

if ($attempt['status'] === 'completed') {
    header("Location: result_view.php?attempt_id=$attemptId"); exit;
}
if ($attempt['status'] === 'terminated') {
    startSecureSession();
    setFlash('error', 'Your exam was terminated due to rule violations.');
    session_write_close();
    header('Location: dashboard.php'); exit;
}

// ── Server-side timer ────────────────────────────────────────
$expEnd        = strtotime($attempt['expected_end_time']);
$nowTs         = time();
$remainSeconds = max(0, $expEnd - $nowTs);

if ($remainSeconds <= 0) {
    autoSubmitExam($attemptId, $studentId, (int)$attempt['exam_db_id'], $attempt);
    header("Location: result_view.php?attempt_id=$attemptId"); exit;
}

// ── Load questions ───────────────────────────────────────────
$questions = db()->fetchAll("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d
    FROM student_answers sa
    JOIN questions q ON q.id = sa.question_id
    WHERE sa.attempt_id = ?
    ORDER BY sa.sort_order ASC",
    [$attemptId]
);

$totalQ   = count($questions);
$qNum     = max(1, min($totalQ, (int)($_GET['q'] ?? $attempt['current_question'])));
$currentQ = $questions[$qNum - 1] ?? null;

// ── Violation count ──────────────────────────────────────────
$violations = (int)(db()->fetchOne(
    "SELECT COUNT(*) c FROM exam_violations WHERE attempt_id=?", [$attemptId]
)['c'] ?? 0);

// ── Summary stats ────────────────────────────────────────────
$answered = 0; $marked = 0;
foreach ($questions as $q) {
    if ($q['is_answered']) $answered++;
    if ($q['is_marked'])   $marked++;
}

// ── AJAX: finish exam ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_exam'])) {
    header('Content-Type: application/json');
    autoSubmitExam($attemptId, $studentId, (int)$attempt['exam_db_id'], $attempt);
    echo json_encode(['success' => true, 'redirect' => "result_view.php?attempt_id=$attemptId"]);
    exit;
}

// ── AJAX: record violation ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_violation'])) {
    header('Content-Type: application/json');
    $type = substr(trim($_POST['violation_type'] ?? 'tab_switch'), 0, 50);
    $desc = substr(trim($_POST['description']    ?? ''), 0, 255);

    $vCount = (int)(db()->fetchOne(
        "SELECT COUNT(*) c FROM exam_violations WHERE attempt_id=?", [$attemptId]
    )['c'] ?? 0) + 1;

    db()->execute(
        "INSERT INTO exam_violations (attempt_id,student_id,exam_id,violation_type,description,violation_count)
         VALUES (?,?,?,?,?,?)",
        [$attemptId, $studentId, $attempt['exam_db_id'], $type, $desc, $vCount]
    );

    $maxV       = (int)$attempt['max_violations'];
    $terminated = false;
    if ($maxV > 0 && $vCount >= $maxV) {
        db()->execute(
            "UPDATE exam_attempts SET status='terminated', termination_reason='Max violations exceeded', end_time=NOW() WHERE id=?",
            [$attemptId]
        );
        calculateAndSaveResult($attemptId, $studentId, (int)$attempt['exam_db_id'], $attempt, true);
        $terminated = true;
    }
    echo json_encode(['success' => true, 'count' => $vCount, 'max' => $maxV, 'terminated' => $terminated]);
    exit;
}

// ── Helper functions ─────────────────────────────────────────
function autoSubmitExam(int $aid, int $sid, int $eid, array $a): void {
    db()->execute(
        "UPDATE exam_attempts SET status='completed', end_time=NOW(),
         time_taken_seconds=TIMESTAMPDIFF(SECOND,start_time,NOW()) WHERE id=?", [$aid]
    );
    db()->execute("UPDATE exam_schedules SET status='completed' WHERE id=?", [$a['schedule_id']]);
    calculateAndSaveResult($aid, $sid, $eid, $a, false);
}

function calculateAndSaveResult(int $aid, int $sid, int $eid, array $a, bool $violated): void {
    if (db()->fetchOne("SELECT id FROM exam_results WHERE attempt_id=?", [$aid])) return;

    $answers    = db()->fetchAll("SELECT * FROM student_answers WHERE attempt_id=?", [$aid]);
    $total      = count($answers);
    $correct    = $incorrect = $unanswered = 0;
    $obtained   = $negTotal  = 0;

    $exam       = db()->fetchOne("SELECT total_marks, passing_percentage FROM exams WHERE id=?", [$eid]);
    $totalMarks = (float)($exam['total_marks']        ?? 100);
    $passPct    = (float)($exam['passing_percentage'] ?? 60);

    foreach ($answers as $an) {
        if (!$an['is_answered']) { $unanswered++; continue; }
        $obtained += (float)$an['marks_awarded'];
        if ($an['is_correct']) { $correct++; }
        else {
            $incorrect++;
            if ((float)$an['marks_awarded'] < 0)
                $negTotal += abs((float)$an['marks_awarded']);
        }
    }

    $pct    = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : 0;
    $result = (!$violated && $pct >= $passPct) ? 'PASS' : 'FAIL';
    $att    = db()->fetchOne("SELECT time_taken_seconds, schedule_id FROM exam_attempts WHERE id=?", [$aid]);

    db()->execute(
        "INSERT IGNORE INTO exam_results
         (attempt_id,student_id,exam_id,schedule_id,total_questions,attempted_questions,
          correct_answers,incorrect_answers,unanswered,total_marks,obtained_marks,
          negative_marks_total,percentage,passing_percentage,result,violation_terminated,time_taken_seconds)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$aid, $sid, $eid, $att['schedule_id'],
         $total, $correct + $incorrect, $correct, $incorrect, $unanswered,
         $totalMarks, max(0, $obtained), $negTotal, $pct, $passPct, $result,
         $violated ? 1 : 0, $att['time_taken_seconds'] ?? 0]
    );
}

// ── Build per-question status array for JS ───────────────────
$qStatuses = [];
foreach ($questions as $q) {
    if ($q['is_answered'] && $q['is_marked'])   $qStatuses[] = 'marked';
    elseif ($q['is_answered'])                  $qStatuses[] = 'answered';
    elseif ($q['is_marked'])                    $qStatuses[] = 'marked';
    else                                        $qStatuses[] = 'unanswered';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($attempt['exam_name'], ENT_QUOTES) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <style>
    /* ══════════════════════════════════════════════════════════
       ASSESSVUE EXAM INTERFACE
       ══════════════════════════════════════════════════════════ */
    :root {
        --primary:   #1a237e;
        --accent:    #3949ab;
        --success:   #2e7d32;
        --warning:   #f57f17;
        --danger:    #c62828;
    }

    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; }
    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: #eceff1;
        overflow-x: hidden;
    }

    /* ── TOP BAR ──────────────────────────────────────────── */
    .exam-topbar {
        background: var(--primary);
        color: #fff;
        height: 58px;
        padding: 0 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        position: sticky;
        top: 0;
        z-index: 200;
        box-shadow: 0 3px 12px rgba(26,35,126,.4);
    }
    .topbar-brand {
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: -.3px;
        white-space: nowrap;
    }
    .topbar-brand span { color: #90caf9; }
    .topbar-sep { width: 1px; height: 24px; background: rgba(255,255,255,.2); }
    .topbar-exam { font-size: .88rem; font-weight: 600; flex: 1; overflow: hidden;
                   text-overflow: ellipsis; white-space: nowrap; }
    .topbar-qnum {
        font-size: .8rem; background: rgba(255,255,255,.15);
        padding: .25rem .8rem; border-radius: 20px; white-space: nowrap;
    }
    .topbar-student {
        display: flex; align-items: center; gap: .5rem; font-size: .82rem;
        background: rgba(255,255,255,.1); padding: .25rem .75rem .25rem .35rem;
        border-radius: 30px;
    }
    .topbar-avatar {
        width: 28px; height: 28px; border-radius: 50%; object-fit: cover;
        border: 2px solid rgba(255,255,255,.4); flex-shrink: 0;
        background: rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .75rem;
    }
    .topbar-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block; }
    .exam-timer {
        font-family: 'Courier New', monospace;
        font-size: 1.2rem;
        font-weight: 700;
        background: rgba(255,255,255,.15);
        padding: .3rem .9rem;
        border-radius: 8px;
        min-width: 105px;
        text-align: center;
        white-space: nowrap;
        transition: background .3s, color .3s;
    }
    .exam-timer.warning  { background: rgba(245,127,23,.3);  color: #ffe57f; }
    .exam-timer.critical { background: rgba(198,40,40,.5);   color: #ff8a80;
                           animation: timerPulse 1s infinite; }
    @keyframes timerPulse { 0%,100%{opacity:1} 50%{opacity:.55} }

    /* ── LAYOUT ───────────────────────────────────────────── */
    .exam-layout {
        display: flex;
        gap: 1.25rem;
        padding: 1.25rem;
        max-width: 1280px;
        margin: 0 auto;
        align-items: flex-start;
    }
    .exam-main    { flex: 1; min-width: 0; }
    .exam-sidebar { width: 270px; flex-shrink: 0; position: sticky; top: 70px; }

    /* ── QUESTION CARD ────────────────────────────────────── */
    .question-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,.10);
        overflow: hidden;
    }
    .question-card-header {
        background: linear-gradient(135deg, #e8eaf6, #f3f4fb);
        padding: .9rem 1.5rem;
        border-bottom: 1px solid #e0e0f0;
        display: flex;
        align-items: center;
        gap: .75rem;
    }
    .q-number-badge {
        background: var(--accent); color: #fff;
        font-size: .78rem; font-weight: 700;
        padding: .25rem .75rem; border-radius: 20px;
        white-space: nowrap;
    }
    .q-mark-badge {
        background: #fff8e1; color: #f57f17;
        border: 1px solid #ffe082;
        font-size: .75rem; font-weight: 700;
        padding: .2rem .6rem; border-radius: 20px;
        display: flex; align-items: center; gap: .3rem;
    }
    .question-card-body { padding: 1.5rem; }
    .question-text {
        font-size: 1.05rem;
        font-weight: 500;
        color: #263238;
        line-height: 1.65;
        margin-bottom: 1.5rem;
    }

    /* ── OPTIONS ──────────────────────────────────────────── */
    .option-label {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: 1rem 1.25rem;
        border: 2px solid #e8e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: border-color .15s, background .15s, transform .1s;
        margin-bottom: .75rem;
        background: #fafafa;
        user-select: none;
    }
    .option-label:hover {
        border-color: var(--accent);
        background: #f0f1fc;
        transform: translateX(3px);
    }
    .option-label.selected {
        border-color: var(--accent);
        background: #e8eaf6;
        transform: translateX(3px);
    }
    .option-label input[type=radio] { display: none; }
    .option-key {
        width: 36px; height: 36px; border-radius: 50%;
        background: #e8eaf6; color: var(--accent);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: .88rem; flex-shrink: 0;
        transition: background .15s, color .15s;
    }
    .option-label.selected .option-key,
    .option-label:hover .option-key {
        background: var(--accent); color: #fff;
    }
    .option-text { flex: 1; font-size: .95rem; color: #37474f; line-height: 1.5; }

    /* ── ACTION BUTTONS ───────────────────────────────────── */
    .exam-actions {
        display: flex; gap: .75rem; flex-wrap: wrap;
        padding: 1rem 1.5rem;
        border-top: 1px solid #f0f0f0;
        background: #f9f9fc;
        border-radius: 0 0 16px 16px;
    }
    .btn-exam { border-radius: 10px; padding: .55rem 1.35rem; font-weight: 700; font-size: .88rem; }
    .btn-exam:disabled { opacity: .6; }

    /* ── SIDEBAR ──────────────────────────────────────────── */
    .sidebar-card {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,.09);
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .sidebar-card-header {
        background: #f5f6fb;
        padding: .65rem 1rem;
        font-size: .82rem;
        font-weight: 700;
        color: var(--primary);
        border-bottom: 1px solid #ebebf5;
        display: flex; align-items: center; gap: .4rem;
    }
    .sidebar-card-body { padding: .85rem 1rem; }

    /* ── QUESTION NAV GRID ────────────────────────────────── */
    .q-grid { display: flex; flex-wrap: wrap; gap: 5px; }
    .q-btn {
        width: 34px; height: 34px;
        border-radius: 7px;
        border: 2px solid #e0e0e0;
        background: #fafafa;
        color: #90a4ae;
        font-size: .78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .15s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .q-btn:hover:not(:disabled)  { border-color: var(--accent); color: var(--accent); background: #e8eaf6; }
    .q-btn.answered  { background: #e8f5e9; border-color: #66bb6a; color: #2e7d32; }
    .q-btn.marked    { background: #fff8e1; border-color: #ffd54f; color: #e65100; }
    .q-btn.current   { background: var(--primary); border-color: var(--primary); color: #fff !important; }
    .q-btn.unanswered { background: #fafafa; border-color: #e0e0e0; color: #9e9e9e; }

    /* ── LEGEND ───────────────────────────────────────────── */
    .legend-grid { display: flex; flex-direction: column; gap: .35rem; }
    .legend-row  {
        display: flex; align-items: center; gap: .5rem;
        font-size: .78rem; color: #546e7a;
    }
    .legend-swatch {
        width: 16px; height: 16px; border-radius: 4px; flex-shrink: 0;
    }
    .legend-count { margin-left: auto; font-weight: 700; color: #37474f; }

    /* ── SUMMARY STATS ────────────────────────────────────── */
    .stat-pill {
        flex: 1; text-align: center; padding: .65rem .5rem;
        border-radius: 10px;
    }
    .stat-pill-val { font-size: 1.35rem; font-weight: 800; line-height: 1; }
    .stat-pill-lbl { font-size: .7rem; font-weight: 500; margin-top: .15rem; }

    /* ── SAVE TOAST ───────────────────────────────────────── */
    .save-toast {
        position: fixed; bottom: 1.25rem; left: 50%; transform: translateX(-50%);
        z-index: 9999;
        padding: .5rem 1.25rem;
        border-radius: 30px;
        font-size: .82rem;
        font-weight: 700;
        display: none;
        align-items: center;
        gap: .5rem;
        box-shadow: 0 6px 24px rgba(0,0,0,.18);
        white-space: nowrap;
        pointer-events: none;
        transition: opacity .2s;
    }
    .save-toast.show     { display: flex; }
    .save-toast.saving   { background: #1a237e; color: #fff; }
    .save-toast.saved    { background: #2e7d32; color: #fff; }
    .save-toast.error    { background: #c62828; color: #fff; }

    /* ── OVERLAYS ─────────────────────────────────────────── */
    .full-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,.8);
        z-index: 9998; display: none;
        align-items: center; justify-content: center;
    }
    .full-overlay.show { display: flex; }
    .overlay-box {
        background: #fff; border-radius: 20px;
        padding: 2.5rem; max-width: 460px; width: 90%;
        text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.3);
    }
    .overlay-box.danger-top { border-top: 6px solid var(--danger); }
    .overlay-icon { font-size: 3rem; margin-bottom: 1rem; }

    /* ── RESPONSIVE ───────────────────────────────────────── */
    @media (max-width: 900px) {
        .exam-layout { flex-direction: column; padding: .75rem; }
        .exam-sidebar { width: 100%; position: static; }
    }
    @media (max-width: 540px) {
        .topbar-student { display: none; }
        .topbar-exam    { font-size: .78rem; }
        .exam-timer     { font-size: 1rem; min-width: 90px; padding: .25rem .6rem; }
    }
    </style>
</head>
<body>

<!-- ═══════════════════ SAVE TOAST ═══════════════════ -->
<div class="save-toast" id="saveToast">
    <i class="fas fa-circle-notch fa-spin" id="saveToastIcon"></i>
    <span id="saveToastText">Saving…</span>
</div>

<!-- ═══════════════════ VIOLATION OVERLAY ═══════════ -->
<div class="full-overlay" id="violationOverlay">
    <div class="overlay-box danger-top">
        <div class="overlay-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <h4 class="fw-bold text-danger mb-2">Rule Violation Detected</h4>
        <p id="violationMsg" class="text-muted mb-3">You have attempted to leave the examination screen.</p>
        <div class="alert alert-warning py-2 mb-3">
            <strong>Violation <span id="vCount"><?= $violations ?></span>
            of <?= $attempt['max_violations'] ?: '∞' ?></strong>
        </div>
        <p class="text-muted small mb-3">Return immediately. Further violations may terminate the exam.</p>
        <button class="btn btn-danger btn-exam px-5" onclick="dismissViolation()">
            <i class="fas fa-arrow-left me-2"></i>Return to Exam
        </button>
    </div>
</div>

<!-- ═══════════════════ TERMINATED OVERLAY ═════════ -->
<div class="full-overlay" id="terminatedOverlay">
    <div class="overlay-box danger-top">
        <div class="overlay-icon text-danger"><i class="fas fa-ban"></i></div>
        <h4 class="fw-bold text-danger mb-2">Examination Terminated</h4>
        <p class="text-muted mb-4">You exceeded the maximum violations. The exam is marked <strong>FAIL</strong>.</p>
        <a href="result_view.php?attempt_id=<?= $attemptId ?>" class="btn btn-danger btn-exam px-5">
            <i class="fas fa-chart-bar me-2"></i>View Result
        </a>
    </div>
</div>

<!-- ═══════════════════ TOPBAR ═══════════════════════ -->
<div class="exam-topbar">
    <div class="topbar-brand">Assess<span>VUE</span></div>
    <div class="topbar-sep"></div>
    <div class="topbar-exam"><?= htmlspecialchars($attempt['exam_name'], ENT_QUOTES) ?></div>
    <div class="topbar-qnum">Q <?= $qNum ?> / <?= $totalQ ?></div>
    <div class="topbar-student">
        <div class="topbar-avatar">
            <?php if ($attempt['profile_picture']): ?>
            <img src="serve_file.php?type=profile" alt="<?= htmlspecialchars($attempt['full_name'], ENT_QUOTES) ?>"
                 onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <span style="display:none;width:100%;height:100%;border-radius:50%;background:rgba(255,255,255,.2);
                         align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:.75rem;">
                <?= strtoupper(substr($attempt['full_name'],0,1)) ?>
            </span>
            <?php else: ?>
            <span style="display:flex;width:100%;height:100%;border-radius:50%;background:rgba(255,255,255,.2);
                         align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:.75rem;">
                <?= strtoupper(substr($attempt['full_name'],0,1)) ?>
            </span>
            <?php endif; ?>
        </div>
        <div>
            <div style="font-size:.8rem;font-weight:700;line-height:1.2;"><?= htmlspecialchars($attempt['full_name'], ENT_QUOTES) ?></div>
            <div style="font-size:.68rem;opacity:.7;"><?= htmlspecialchars($attempt['stu_code'], ENT_QUOTES) ?></div>
        </div>
    </div>
    <div class="exam-timer" id="examTimer"><?= gmdate('H:i:s', $remainSeconds) ?></div>
</div>

<!-- ═══════════════════ EXAM LAYOUT ══════════════════ -->
<div class="exam-layout">

    <!-- ── MAIN QUESTION PANEL ─────────────────────── -->
    <div class="exam-main">
        <?php if ($currentQ): ?>
        <div class="question-card">

            <!-- Header -->
            <div class="question-card-header">
                <span class="q-number-badge">Question <?= $qNum ?> of <?= $totalQ ?></span>
                <?php if ($currentQ['is_marked']): ?>
                <span class="q-mark-badge"><i class="fas fa-bookmark"></i>Marked for Review</span>
                <?php else: ?>
                <span class="q-mark-badge" id="markBadge" style="display:none!important">
                    <i class="fas fa-bookmark"></i>Marked for Review
                </span>
                <?php endif; ?>
                <span class="ms-auto" style="font-size:.78rem;color:#78909c;">
                    <i class="fas fa-clock me-1"></i>PKT: <?= date('h:i A') ?>
                </span>
            </div>

            <!-- Question Body -->
            <div class="question-card-body">
                <div class="question-text"><?= nl2br(htmlspecialchars($currentQ['question_text'], ENT_QUOTES)) ?></div>

                <div id="optionsContainer">
                <?php foreach (['A','B','C','D'] as $opt):
                    $key   = 'option_' . strtolower($opt);
                    $optText = trim($currentQ[$key] ?? '');
                    if ($optText === '') continue;
                    $isSel = ($currentQ['selected_option'] === $opt);
                ?>
                <label class="option-label <?= $isSel ? 'selected' : '' ?>"
                       data-option="<?= $opt ?>"
                       onclick="selectAnswer('<?= $opt ?>', this)">
                    <input type="radio" name="answer" value="<?= $opt ?>" <?= $isSel ? 'checked' : '' ?>>
                    <span class="option-key"><?= $opt ?></span>
                    <span class="option-text"><?= htmlspecialchars($optText, ENT_QUOTES) ?></span>
                </label>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Actions -->
            <div class="exam-actions">
                <?php if ($qNum > 1): ?>
                <button type="button" class="btn btn-outline-secondary btn-exam"
                        id="btnPrev" onclick="goToQuestion(<?= $qNum - 1 ?>)">
                    <i class="fas fa-arrow-left me-1"></i>Previous
                </button>
                <?php endif; ?>

                <button type="button" id="markBtn"
                        class="btn btn-exam <?= $currentQ['is_marked'] ? 'btn-warning' : 'btn-outline-warning' ?>"
                        onclick="toggleMark()">
                    <i class="fas fa-bookmark me-1"></i>
                    <span id="markBtnText"><?= $currentQ['is_marked'] ? 'Unmark' : 'Mark for Review' ?></span>
                </button>

                <div class="ms-auto d-flex gap-2">
                    <?php if ($qNum < $totalQ): ?>
                    <button type="button" class="btn btn-primary btn-exam"
                            id="btnNext" onclick="goToQuestion(<?= $qNum + 1 ?>)">
                        Next <i class="fas fa-arrow-right ms-1"></i>
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-success btn-exam" onclick="showFinishModal()">
                        <i class="fas fa-flag-checkered me-1"></i>Finish Exam
                    </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-outline-danger btn-exam d-none d-sm-flex"
                            onclick="showFinishModal()">
                        <i class="fas fa-stop me-1"></i>End
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── SIDEBAR ─────────────────────────────────── -->
    <div class="exam-sidebar">

        <!-- Question Navigator -->
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="fas fa-th-large"></i> Question Navigator
            </div>
            <div class="sidebar-card-body">
                <div class="q-grid mb-3" id="qGrid">
                <?php foreach ($questions as $i => $q):
                    $n   = $i + 1;
                    $cls = $qStatuses[$i];
                    if ($n === $qNum) $cls = 'current';
                ?>
                <button type="button"
                        class="q-btn <?= $cls ?>"
                        id="qbtn<?= $n ?>"
                        onclick="goToQuestion(<?= $n ?>)"><?= $n ?></button>
                <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="legend-grid">
                    <div class="legend-row">
                        <div class="legend-swatch" style="background:#e8f5e9;border:2px solid #66bb6a;"></div>
                        <span>Answered</span>
                        <span class="legend-count" id="legAnswered"><?= $answered ?></span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-swatch" style="background:#fff8e1;border:2px solid #ffd54f;"></div>
                        <span>Marked</span>
                        <span class="legend-count" id="legMarked"><?= $marked ?></span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-swatch" style="background:#fafafa;border:2px solid #e0e0e0;"></div>
                        <span>Unanswered</span>
                        <span class="legend-count" id="legUnanswered"><?= $totalQ - $answered ?></span>
                    </div>
                    <div class="legend-row">
                        <div class="legend-swatch" style="background:#1a237e;border:2px solid #1a237e;border-radius:4px;"></div>
                        <span>Current</span>
                        <span class="legend-count">Q<?= $qNum ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="fas fa-chart-bar"></i> Summary
            </div>
            <div class="sidebar-card-body">
                <div class="d-flex gap-2 mb-3">
                    <div class="stat-pill" style="background:#e8f5e9;">
                        <div class="stat-pill-val text-success" id="sumAnswered"><?= $answered ?></div>
                        <div class="stat-pill-lbl text-success">Done</div>
                    </div>
                    <div class="stat-pill" style="background:#fff8e1;">
                        <div class="stat-pill-val text-warning" id="sumMarked"><?= $marked ?></div>
                        <div class="stat-pill-lbl text-warning">Review</div>
                    </div>
                    <div class="stat-pill" style="background:#f5f5f5;">
                        <div class="stat-pill-val text-secondary" id="sumLeft"><?= $totalQ - $answered ?></div>
                        <div class="stat-pill-lbl text-secondary">Left</div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div style="background:#eceff1;border-radius:8px;height:8px;overflow:hidden;margin-bottom:.75rem;">
                    <div id="progressBar"
                         style="height:100%;background:linear-gradient(90deg,#43a047,#66bb6a);border-radius:8px;
                                transition:width .4s;width:<?= $totalQ > 0 ? round($answered/$totalQ*100) : 0 ?>%;">
                    </div>
                </div>
                <div style="font-size:.75rem;color:#90a4ae;text-align:center;margin-bottom:.75rem;">
                    <span id="progressPct"><?= $totalQ > 0 ? round($answered/$totalQ*100) : 0 ?></span>% complete
                </div>

                <button class="btn btn-success btn-exam w-100" onclick="showFinishModal()">
                    <i class="fas fa-flag-checkered me-1"></i>Submit Exam
                </button>
            </div>
        </div>

        <!-- Violation Counter -->
        <?php if ((int)$attempt['max_violations'] > 0): ?>
        <div class="sidebar-card">
            <div class="sidebar-card-header">
                <i class="fas fa-shield-alt"></i> Integrity Monitor
            </div>
            <div class="sidebar-card-body">
                <div class="d-flex align-items-center gap-2">
                    <div style="flex:1;">
                        <div style="font-size:.78rem;color:#78909c;">Violations</div>
                        <div style="font-size:1.2rem;font-weight:800;color:<?= $violations > 0 ? '#c62828' : '#2e7d32'; ?>;">
                            <span id="violCount"><?= $violations ?></span> / <?= $attempt['max_violations'] ?>
                        </div>
                    </div>
                    <div style="font-size:1.75rem;color:<?= $violations > 0 ? '#ef5350' : '#43a047'; ?>;">
                        <i class="fas fa-<?= $violations > 0 ? 'exclamation-triangle' : 'check-shield' ?>"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /sidebar -->
</div><!-- /exam-layout -->

<!-- ═══════════════════ FINISH MODAL ════════════════ -->
<div class="modal fade" id="finishModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:#fff;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-flag-checkered me-2"></i>Submit Examination
                </h5>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Are you sure you want to submit your examination?</p>
                <div class="row g-2 text-center mb-3">
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#e3f2fd;">
                            <div class="fw-bold text-primary fs-5"><?= $totalQ ?></div>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#e8f5e9;">
                            <div class="fw-bold text-success fs-5" id="modalAnswered"><?= $answered ?></div>
                            <small class="text-muted">Answered</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 rounded" style="background:#fff8e1;">
                            <div class="fw-bold text-warning fs-5" id="modalUnanswered"><?= $totalQ - $answered ?></div>
                            <small class="text-muted">Unanswered</small>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning py-2 mb-0 small">
                    <i class="fas fa-info-circle me-1"></i>
                    Once submitted you cannot change your answers.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-exam" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i>Continue Exam
                </button>
                <button type="button" class="btn btn-success btn-exam" id="confirmSubmitBtn" onclick="submitExam()">
                    <i class="fas fa-check me-2"></i>Submit Now
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ============================================================
   ASSESSVUE EXAM ENGINE
   ============================================================ */

// ── Constants (from PHP) ─────────────────────────────────────
const ATTEMPT_ID      = <?= $attemptId ?>;
const CURRENT_Q       = <?= $qNum ?>;
const CURRENT_QID     = <?= $currentQ ? (int)$currentQ['question_id'] : 0 ?>;
const TOTAL_Q         = <?= $totalQ ?>;
const REMAIN_SECS_PHP = <?= $remainSeconds ?>;
const MAX_VIOLATIONS  = <?= (int)$attempt['max_violations'] ?>;
const SAVE_URL        = 'save_answer.php';   // dedicated save endpoint
const EXAM_URL        = 'exam.php';

// ── State ────────────────────────────────────────────────────
let violCount       = <?= $violations ?>;
let examDone        = false;
let selectedAnswer  = <?= json_encode($currentQ ? ($currentQ['selected_option'] ?? '') : '') ?>;
let isMarked        = <?= ($currentQ && $currentQ['is_marked']) ? 'true' : 'false' ?>;
let answeredCount   = <?= $answered ?>;
let markedCount     = <?= $marked ?>;
let saving          = false;
let saveQueue       = null;   // latest pending save params
let navLocked       = false;  // prevents double-nav during save

// Question status tracker (synced to server state on load)
const qStatus = <?= json_encode($qStatuses) ?>;

// ── TIMER ────────────────────────────────────────────────────
let remainSecs = REMAIN_SECS_PHP;
const timerEl  = document.getElementById('examTimer');

(function tick() {
    if (remainSecs <= 0) {
        timerEl.textContent = '00:00:00';
        if (!examDone) { examDone = true; submitExam(true); }
        return;
    }
    const h = Math.floor(remainSecs / 3600);
    const m = Math.floor((remainSecs % 3600) / 60);
    const s = remainSecs % 60;
    timerEl.textContent = `${pad(h)}:${pad(m)}:${pad(s)}`;
    timerEl.className = 'exam-timer' +
        (remainSecs <= 60 ? ' critical' : remainSecs <= 300 ? ' warning' : '');
    remainSecs--;
    setTimeout(tick, 1000);
})();

function pad(n) { return String(n).padStart(2,'0'); }

// ── TOAST ────────────────────────────────────────────────────
let toastTimer = null;
function showToast(state, msg) {  // state: 'saving'|'saved'|'error'
    clearTimeout(toastTimer);
    const el   = document.getElementById('saveToast');
    const icon = document.getElementById('saveToastIcon');
    const txt  = document.getElementById('saveToastText');
    el.className = 'save-toast show ' + state;
    icon.className = state === 'saving' ? 'fas fa-circle-notch fa-spin'
                   : state === 'saved'  ? 'fas fa-check-circle'
                   : 'fas fa-exclamation-circle';
    txt.textContent = msg;
    if (state !== 'saving') {
        toastTimer = setTimeout(() => { el.className = 'save-toast'; }, 2000);
    }
}

// ── SAVE ANSWER (dedicated endpoint) ────────────────────────
async function doSave(answer, mark, qid, qNum) {
    const fd = new FormData();
    fd.append('attempt_id',  ATTEMPT_ID);
    fd.append('question_id', qid);
    fd.append('answer',      answer ?? '');
    fd.append('mark',        mark ? '1' : '0');
    fd.append('q_num',       qNum);

    const resp = await fetch(SAVE_URL, { method: 'POST', body: fd });
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    return await resp.json();
}

async function saveAnswerAsync() {
    if (!CURRENT_QID || examDone) return true;
    showToast('saving', 'Saving answer…');
    saving = true;
    try {
        const data = await doSave(selectedAnswer || '', isMarked, CURRENT_QID, CURRENT_Q);
        saving = false;
        if (data.success) {
            showToast('saved', 'Answer saved ✓');
            return true;
        }
        showToast('error', 'Save failed — retrying…');
        // one retry
        try {
            const r2 = await doSave(selectedAnswer || '', isMarked, CURRENT_QID, CURRENT_Q);
            if (r2.success) { showToast('saved', 'Saved ✓'); return true; }
        } catch {}
        showToast('error', 'Could not save — check connection');
        return false;
    } catch (e) {
        saving = false;
        showToast('error', 'Save failed — retrying…');
        try {
            await new Promise(r => setTimeout(r, 800));
            const r2 = await doSave(selectedAnswer || '', isMarked, CURRENT_QID, CURRENT_Q);
            if (r2.success) { showToast('saved', 'Saved ✓'); return true; }
        } catch {}
        showToast('error', 'Could not save');
        return false;
    }
}

// ── SELECT ANSWER ────────────────────────────────────────────
function selectAnswer(opt, label) {
    if (examDone) return;
    // Visual
    document.querySelectorAll('.option-label').forEach(l => {
        l.classList.remove('selected');
    });
    label.classList.add('selected');

    const wasAnswered = !!selectedAnswer;
    selectedAnswer = opt;

    // Update sidebar counts if new answer
    if (!wasAnswered) {
        answeredCount++;
        updateCounts();
        setQBtnClass(CURRENT_Q, isMarked ? 'marked' : 'answered');
    }

    // Save (fire — navigation will await if needed)
    saveAnswerAsync();
}

// ── MARK FOR REVIEW ──────────────────────────────────────────
function toggleMark() {
    if (examDone) return;
    isMarked = !isMarked;
    const btn  = document.getElementById('markBtn');
    const btxt = document.getElementById('markBtnText');
    const bdge = document.getElementById('markBadge');
    btn.className  = 'btn btn-exam ' + (isMarked ? 'btn-warning' : 'btn-outline-warning');
    btxt.textContent = isMarked ? 'Unmark' : 'Mark for Review';
    if (bdge) bdge.style.display = isMarked ? '' : 'none!important';

    markedCount += isMarked ? 1 : -1;
    updateCounts();
    const cls = selectedAnswer ? (isMarked ? 'marked' : 'answered') : (isMarked ? 'marked' : 'unanswered');
    setQBtnClass(CURRENT_Q, cls);

    saveAnswerAsync();
}

// ── UPDATE UI COUNTS ─────────────────────────────────────────
function updateCounts() {
    const left = TOTAL_Q - answeredCount;
    document.getElementById('legAnswered').textContent  = answeredCount;
    document.getElementById('legMarked').textContent    = markedCount;
    document.getElementById('legUnanswered').textContent = left;
    document.getElementById('sumAnswered').textContent  = answeredCount;
    document.getElementById('sumMarked').textContent    = markedCount;
    document.getElementById('sumLeft').textContent      = left;
    document.getElementById('modalAnswered').textContent   = answeredCount;
    document.getElementById('modalUnanswered').textContent = left;
    const pct = TOTAL_Q > 0 ? Math.round(answeredCount / TOTAL_Q * 100) : 0;
    document.getElementById('progressBar').style.width = pct + '%';
    document.getElementById('progressPct').textContent  = pct;
}

function setQBtnClass(n, cls) {
    const btn = document.getElementById('qbtn' + n);
    if (btn) btn.className = 'q-btn ' + cls;
    qStatus[n - 1] = cls;
}

// ── NAVIGATE ─────────────────────────────────────────────────
async function goToQuestion(target) {
    if (examDone || navLocked) return;
    navLocked = true;

    // Disable nav buttons visually
    document.querySelectorAll('.q-btn, #btnPrev, #btnNext').forEach(b => b.disabled = true);
    showToast('saving', 'Saving…');

    await saveAnswerAsync();

    // Always navigate even if save failed (answer is cached on server side)
    window.location.href = `${EXAM_URL}?attempt_id=${ATTEMPT_ID}&q=${target}`;
}

// ── FINISH MODAL ─────────────────────────────────────────────
const finishModal = new bootstrap.Modal(document.getElementById('finishModal'));
function showFinishModal() { finishModal.show(); }

// ── SUBMIT EXAM ──────────────────────────────────────────────
async function submitExam(auto = false) {
    if (examDone && !auto) return;
    examDone = true;
    const btn = document.getElementById('confirmSubmitBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting…'; }

    // Final save
    await saveAnswerAsync();

    const fd = new FormData();
    fd.append('finish_exam', '1');
    const url = `${EXAM_URL}?attempt_id=${ATTEMPT_ID}&q=${CURRENT_Q}`;

    try {
        const r = await fetch(url, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) { window.location.href = d.redirect; return; }
    } catch {}
    window.location.href = `result_view.php?attempt_id=${ATTEMPT_ID}`;
}

// ── VIOLATION MONITORING ─────────────────────────────────────
let violActive = false;

function recordViolation(type, desc) {
    if (examDone) return;
    const fd = new FormData();
    fd.append('record_violation', '1');
    fd.append('violation_type',   type);
    fd.append('description',      desc);
    const url = `${EXAM_URL}?attempt_id=${ATTEMPT_ID}&q=${CURRENT_Q}`;
    fetch(url, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            violCount = d.count;
            document.getElementById('vCount').textContent    = d.count;
            const vc = document.getElementById('violCount');
            if (vc) vc.textContent = d.count;
            if (d.terminated) {
                document.getElementById('violationOverlay').classList.remove('show');
                document.getElementById('terminatedOverlay').classList.add('show');
            } else {
                showViolation(desc);
            }
        }).catch(() => {});
}

function showViolation(msg) {
    if (violActive) return;
    violActive = true;
    document.getElementById('violationMsg').textContent = msg || 'A violation was detected.';
    document.getElementById('violationOverlay').classList.add('show');
}

function dismissViolation() {
    document.getElementById('violationOverlay').classList.remove('show');
    violActive = false;
}

// Tab / window events
document.addEventListener('visibilitychange', () => {
    if (document.hidden && !examDone)
        recordViolation('tab_switch', 'Student switched browser tab or minimized window.');
});
window.addEventListener('blur', () => {
    if (!examDone) {
        setTimeout(() => {
            if (!document.hasFocus() && !examDone)
                recordViolation('window_blur', 'Student switched to another application.');
        }, 350);
    }
});
window.addEventListener('beforeunload', e => {
    if (!examDone) { e.preventDefault(); e.returnValue = ''; }
});
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('keydown', e => {
    if (e.key === 'F5' || (e.ctrlKey && ['r','R'].includes(e.key))) {
        e.preventDefault();
    }
});
</script>
</body>
</html>
