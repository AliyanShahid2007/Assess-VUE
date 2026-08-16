<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = $_SESSION['student_id'];
$attemptId = sanitizeInt($_GET['attempt_id'] ?? 0);

// Load attempt
$attempt = db()->fetchOne("
    SELECT ea.*, es.scheduled_date, es.start_time as sched_start,
           e.exam_name, e.total_questions, e.max_violations, e.passing_percentage,
           e.marks_per_question, e.negative_marks, e.id as exam_db_id,
           s.full_name
    FROM exam_attempts ea
    JOIN exam_schedules es ON es.id = ea.schedule_id
    JOIN exams e ON e.id = ea.exam_id
    JOIN students s ON s.id = ea.student_id
    WHERE ea.id = ? AND ea.student_id = ?",
    [$attemptId, $studentId]
);

if (!$attempt) {
    setFlash('error', 'Exam attempt not found.');
    redirect('dashboard.php');
}
if ($attempt['status'] === 'completed') {
    redirect("result_view.php?attempt_id=$attemptId");
}
if ($attempt['status'] === 'terminated') {
    setFlash('error', 'Your exam was terminated due to rule violations.');
    redirect('dashboard.php');
}

// Server-side timer check
$startTs = strtotime($attempt['start_time']);
$expEnd  = strtotime($attempt['expected_end_time']);
$nowTs   = time();
$remainSeconds = max(0, $expEnd - $nowTs);

// Auto-submit if time expired
if ($remainSeconds <= 0 && $attempt['status'] === 'in_progress') {
    autoSubmitExam($attemptId, $studentId, $attempt['exam_db_id'], $attempt);
    redirect("result_view.php?attempt_id=$attemptId");
}

// Load all questions for this attempt
$questions = db()->fetchAll("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d
    FROM student_answers sa
    JOIN questions q ON q.id = sa.question_id
    WHERE sa.attempt_id = ?
    ORDER BY sa.sort_order ASC",
    [$attemptId]
);

$totalQ = count($questions);
$qNum   = max(1, min($totalQ, sanitizeInt($_GET['q'] ?? $attempt['current_question'])));

$currentQ = $questions[$qNum - 1] ?? null;

// Violation count
$violations = db()->fetchOne("SELECT COUNT(*) c FROM exam_violations WHERE attempt_id=?", [$attemptId])['c'] ?? 0;

// Summary stats for navigation
$answered = 0; $marked = 0;
foreach ($questions as $q) {
    if ($q['is_answered']) $answered++;
    if ($q['is_marked']) $marked++;
}

// ---- AJAX answer save ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    header('Content-Type: application/json');
    $qid     = sanitizeInt($_POST['question_id'] ?? 0);
    $answer  = strtoupper(trim($_POST['answer'] ?? ''));
    $isMark  = (int)(bool)($_POST['mark'] ?? false);

    if (!in_array($answer, ['A','B','C','D',''])) {
        echo json_encode(['success' => false]); exit;
    }

    $qa = db()->fetchOne("SELECT * FROM student_answers WHERE attempt_id=? AND question_id=?", [$attemptId, $qid]);
    if (!$qa) { echo json_encode(['success' => false]); exit; }

    $isAnswered = $answer !== '';
    $isCorrect  = $answer !== '' ? ($answer === $qa['correct_option'] ? 1 : 0) : null;

    // Marks
    $marks = 0;
    if ($isAnswered) {
        if ($isCorrect) {
            $qa2 = db()->fetchOne("SELECT marks FROM student_answers WHERE attempt_id=? AND question_id=?", [$attemptId, $qid]);
            $marks = (float)($qa2['marks_awarded'] ?? $attempt['marks_per_question']);
            // Get actual marks from exam_questions
            $eq = db()->fetchOne("SELECT marks, negative_marks FROM exam_questions WHERE exam_id=? AND question_id=?", [$attempt['exam_db_id'], $qid]);
            if ($eq) $marks = (float)$eq['marks'];
        } else {
            $eq = db()->fetchOne("SELECT negative_marks FROM exam_questions WHERE exam_id=? AND question_id=?", [$attempt['exam_db_id'], $qid]);
            $marks = -(float)($eq ? $eq['negative_marks'] : $attempt['negative_marks']);
        }
    }

    db()->execute("UPDATE student_answers SET selected_option=?, is_answered=?, is_correct=?, is_marked=?, marks_awarded=?, answered_at=NOW() WHERE attempt_id=? AND question_id=?",
        [$answer ?: null, $isAnswered ? 1 : 0, $isCorrect, $isMark, $marks, $attemptId, $qid]);

    // Update current question
    db()->execute("UPDATE exam_attempts SET current_question=? WHERE id=?", [$qNum, $attemptId]);

    echo json_encode(['success' => true, 'answered' => $isAnswered, 'marked' => (bool)$isMark]);
    exit;
}

// ---- AJAX violation record ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_violation'])) {
    header('Content-Type: application/json');
    $type = trim($_POST['violation_type'] ?? 'tab_switch');
    $desc = trim($_POST['description'] ?? '');

    $vCount = db()->fetchOne("SELECT COUNT(*) c FROM exam_violations WHERE attempt_id=?", [$attemptId])['c'] ?? 0;
    $vCount++;

    db()->execute("INSERT INTO exam_violations (attempt_id, student_id, exam_id, violation_type, description, violation_count) VALUES (?,?,?,?,?,?)",
        [$attemptId, $studentId, $attempt['exam_db_id'], $type, $desc, $vCount]);

    $maxV = (int)$attempt['max_violations'];
    $terminated = false;
    if ($maxV > 0 && $vCount >= $maxV) {
        // Terminate
        db()->execute("UPDATE exam_attempts SET status='terminated', termination_reason='Max violations exceeded', end_time=NOW() WHERE id=?", [$attemptId]);
        // Calculate result with FAIL
        calculateAndSaveResult($attemptId, $studentId, $attempt['exam_db_id'], $attempt, true);
        $terminated = true;
    }

    echo json_encode(['success' => true, 'count' => $vCount, 'max' => $maxV, 'terminated' => $terminated]);
    exit;
}

// ---- AJAX finish exam ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_exam'])) {
    header('Content-Type: application/json');
    autoSubmitExam($attemptId, $studentId, $attempt['exam_db_id'], $attempt);
    echo json_encode(['success' => true, 'redirect' => "result_view.php?attempt_id=$attemptId"]);
    exit;
}

function autoSubmitExam(int $attemptId, int $studentId, int $examId, array $attempt): void {
    db()->execute("UPDATE exam_attempts SET status='completed', end_time=NOW(), time_taken_seconds=TIMESTAMPDIFF(SECOND,start_time,NOW()) WHERE id=?", [$attemptId]);
    db()->execute("UPDATE exam_schedules SET status='completed' WHERE id=?", [$attempt['schedule_id']]);
    calculateAndSaveResult($attemptId, $studentId, $examId, $attempt, false);
}

function calculateAndSaveResult(int $attemptId, int $studentId, int $examId, array $attempt, bool $violated): void {
    // Check if result already exists
    if (db()->fetchOne("SELECT id FROM exam_results WHERE attempt_id=?", [$attemptId])) return;

    $answers = db()->fetchAll("SELECT * FROM student_answers WHERE attempt_id=?", [$attemptId]);

    $total   = count($answers);
    $correct = 0; $incorrect = 0; $unanswered = 0;
    $obtained = 0; $negTotal = 0;

    // Get exam total marks
    $exam = db()->fetchOne("SELECT total_marks, passing_percentage FROM exams WHERE id=?", [$examId]);
    $totalMarks = (float)($exam['total_marks'] ?? 100);
    $passPct    = (float)($exam['passing_percentage'] ?? 60);

    foreach ($answers as $a) {
        if (!$a['is_answered']) {
            $unanswered++;
        } elseif ($a['is_correct']) {
            $correct++;
            $obtained += (float)$a['marks_awarded'];
        } else {
            $incorrect++;
            $obtained += (float)$a['marks_awarded']; // negative marks already negative
            if ($a['marks_awarded'] < 0) $negTotal += abs((float)$a['marks_awarded']);
        }
    }

    $pct    = $totalMarks > 0 ? round(($obtained / $totalMarks) * 100, 2) : 0;
    $result = (!$violated && $pct >= $passPct) ? 'PASS' : 'FAIL';

    $att = db()->fetchOne("SELECT time_taken_seconds, schedule_id FROM exam_attempts WHERE id=?", [$attemptId]);

    db()->execute("INSERT IGNORE INTO exam_results (attempt_id, student_id, exam_id, schedule_id, total_questions, attempted_questions, correct_answers, incorrect_answers, unanswered, total_marks, obtained_marks, negative_marks_total, percentage, passing_percentage, result, violation_terminated, time_taken_seconds) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
        [$attemptId, $studentId, $examId, $att['schedule_id'],
         $total, $correct + $incorrect, $correct, $incorrect, $unanswered,
         $totalMarks, max(0, $obtained), $negTotal, $pct, $passPct, $result,
         $violated ? 1 : 0, $att['time_taken_seconds'] ?? 0]);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($attempt['exam_name']) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body class="exam-mode">

<!-- Exam Top Bar -->
<div class="exam-topbar">
    <div class="exam-title">
        <i class="fas fa-graduation-cap me-2"></i><?= sanitize($attempt['exam_name']) ?>
    </div>
    <div class="exam-student-info d-none d-md-block">
        <i class="fas fa-user me-1"></i><?= sanitize($attempt['full_name']) ?>
    </div>
    <div class="exam-qnum">
        Q <?= $qNum ?> / <?= $totalQ ?>
    </div>
    <div class="exam-timer" id="examTimer">
        <?= gmdate('H:i:s', $remainSeconds) ?>
    </div>
</div>

<!-- Violation Overlay (hidden) -->
<div class="violation-overlay d-none" id="violationOverlay">
    <div class="violation-box">
        <div style="font-size:3rem" class="text-danger mb-3"><i class="fas fa-exclamation-triangle"></i></div>
        <h4 class="text-danger fw-bold">Examination Rule Violation Detected</h4>
        <p id="violationMsg">You have attempted to leave the examination screen.</p>
        <div class="alert alert-warning py-2">
            <strong>Violation <span id="vCount">1</span> of <?= $attempt['max_violations'] ?: '∞' ?></strong>
        </div>
        <p class="text-muted small">Return to examination immediately. Further violations may result in exam termination.</p>
        <button class="btn btn-danger px-4" onclick="dismissViolation()">
            <i class="fas fa-arrow-left me-2"></i>Return to Examination
        </button>
    </div>
</div>

<!-- Terminated Overlay (hidden) -->
<div class="violation-overlay d-none" id="terminatedOverlay">
    <div class="violation-box">
        <div style="font-size:3rem" class="text-danger mb-3"><i class="fas fa-ban"></i></div>
        <h4 class="text-danger fw-bold">Examination Terminated</h4>
        <p>You have exceeded the maximum allowed violations. Your examination has been automatically terminated and marked as <strong>FAIL</strong>.</p>
        <a href="result_view.php?attempt_id=<?= $attemptId ?>" class="btn btn-danger px-4 mt-2">
            View Result
        </a>
    </div>
</div>

<div class="exam-layout">
    <!-- Main Question Panel -->
    <div class="exam-main">
        <?php if ($currentQ): ?>
        <div class="question-box">
            <div class="question-label">
                Question <?= $qNum ?> of <?= $totalQ ?>
                <?php if ($currentQ['is_marked']): ?>
                <span class="badge bg-warning text-dark ms-2"><i class="fas fa-bookmark me-1"></i>Marked for Review</span>
                <?php endif; ?>
            </div>
            <div class="question-text"><?= htmlspecialchars($currentQ['question_text']) ?></div>

            <div id="optionsContainer">
                <?php foreach (['A','B','C','D'] as $opt):
                    $key = 'option_' . strtolower($opt);
                    $isSel = $currentQ['selected_option'] === $opt;
                ?>
                <label class="option-label <?= $isSel ? 'selected' : '' ?>" data-option="<?= $opt ?>"
                       onclick="selectAnswer('<?= $opt ?>', this)">
                    <input type="radio" name="answer" value="<?= $opt ?>" <?= $isSel ? 'checked' : '' ?>>
                    <span class="option-badge"><?= $opt ?></span>
                    <span class="option-text"><?= htmlspecialchars($currentQ[$key] ?? '') ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="exam-actions mt-3">
                <?php if ($qNum > 1): ?>
                <a href="exam.php?attempt_id=<?= $attemptId ?>&q=<?= $qNum-1 ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Previous
                </a>
                <?php endif; ?>

                <button type="button" class="btn <?= $currentQ['is_marked'] ? 'btn-warning' : 'btn-outline-warning' ?>" id="markBtn"
                        onclick="toggleMark()">
                    <i class="fas fa-bookmark me-1"></i>
                    <?= $currentQ['is_marked'] ? 'Unmark' : 'Mark for Review' ?>
                </button>

                <?php if ($qNum < $totalQ): ?>
                <a href="exam.php?attempt_id=<?= $attemptId ?>&q=<?= $qNum+1 ?>" class="btn btn-primary">
                    Next <i class="fas fa-arrow-right ms-1"></i>
                </a>
                <?php else: ?>
                <button type="button" class="btn btn-success" onclick="showFinishModal()">
                    <i class="fas fa-flag-checkered me-1"></i>Finish Exam
                </button>
                <?php endif; ?>

                <button type="button" class="btn btn-outline-danger ms-auto d-none d-sm-block" onclick="showFinishModal()">
                    <i class="fas fa-stop me-1"></i>End Exam
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Panel -->
    <div class="exam-nav-panel">
        <div class="card mb-3">
            <div class="card-header py-2"><i class="fas fa-th me-2"></i>Questions</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php foreach ($questions as $i => $q): ?>
                    <?php
                        $n = $i+1;
                        $cls = 'unanswered';
                        if ($n === $qNum) $cls = 'current';
                        elseif ($q['is_answered'] && $q['is_marked']) $cls = 'marked';
                        elseif ($q['is_answered']) $cls = 'answered';
                        elseif ($q['is_marked']) $cls = 'marked';
                    ?>
                    <a href="exam.php?attempt_id=<?= $attemptId ?>&q=<?= $n ?>"
                       class="q-nav-btn <?= $cls ?>"><?= $n ?></a>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="nav-legend">
                    <div class="legend-item">
                        <div class="legend-dot" style="background:#43a047;border:1px solid #43a047;border-radius:3px"></div>
                        <span>Answered (<?= $answered ?>)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background:#fff3e0;border:1px solid #f9a825;border-radius:3px"></div>
                        <span>Marked (<?= $marked ?>)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background:#fafafa;border:1px solid #e0e0e0;border-radius:3px"></div>
                        <span>Not Answered (<?= $totalQ - $answered ?>)</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background:#3949ab;border:1px solid #1a237e;border-radius:3px"></div>
                        <span>Current</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="card">
            <div class="card-body py-2">
                <div class="row g-1 text-center">
                    <div class="col-6">
                        <div class="p-2 bg-success bg-opacity-10 rounded">
                            <div class="fw-bold text-success"><?= $answered ?></div>
                            <small class="text-muted">Answered</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-secondary bg-opacity-10 rounded">
                            <div class="fw-bold text-secondary"><?= $totalQ - $answered ?></div>
                            <small class="text-muted">Remaining</small>
                        </div>
                    </div>
                </div>
                <button class="btn btn-success btn-sm w-100 mt-2" onclick="showFinishModal()">
                    <i class="fas fa-flag-checkered me-1"></i>Finish Examination
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Finish Modal -->
<div class="modal fade" id="finishModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-flag-checkered me-2"></i>Submit Examination</h5>
            </div>
            <div class="modal-body">
                <p class="fw-semibold mb-3">Are you sure you want to submit your examination?</p>
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 bg-primary bg-opacity-10 rounded">
                            <div class="fw-bold text-primary"><?= $totalQ ?></div>
                            <small>Total</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-success bg-opacity-10 rounded">
                            <div class="fw-bold text-success" id="finalAnswered"><?= $answered ?></div>
                            <small>Answered</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 bg-secondary bg-opacity-10 rounded">
                            <div class="fw-bold text-secondary" id="finalUnanswered"><?= $totalQ - $answered ?></div>
                            <small>Unanswered</small>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <small><i class="fas fa-exclamation-triangle me-1"></i>
                    Once submitted, you cannot modify your answers.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i>Cancel (Continue Exam)
                </button>
                <button type="button" class="btn btn-success" id="confirmSubmitBtn" onclick="submitExam()">
                    <i class="fas fa-check me-2"></i>Submit Examination
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ATTEMPT_ID     = <?= $attemptId ?>;
const CURRENT_Q      = <?= $qNum ?>;
const CURRENT_Q_ID   = <?= $currentQ ? $currentQ['question_id'] : 0 ?>;
const REMAIN_SECONDS = <?= $remainSeconds ?>;
const MAX_VIOLATIONS = <?= (int)$attempt['max_violations'] ?>;
let violationCount   = <?= $violations ?>;
let examSubmitted    = false;
let selectedAnswer   = <?= json_encode($currentQ ? ($currentQ['selected_option'] ?? '') : '') ?>;
let isMarked         = <?= $currentQ ? ($currentQ['is_marked'] ? 'true' : 'false') : 'false' ?>;

// ---- Timer ----
let remainSecs = REMAIN_SECONDS;
const timerEl  = document.getElementById('examTimer');

function updateTimer() {
    if (remainSecs <= 0) {
        timerEl.textContent = '00:00:00';
        if (!examSubmitted) { examSubmitted = true; submitExam(true); }
        return;
    }
    const h = Math.floor(remainSecs / 3600);
    const m = Math.floor((remainSecs % 3600) / 60);
    const s = remainSecs % 60;
    timerEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;

    if (remainSecs <= 60) { timerEl.className = 'exam-timer critical'; }
    else if (remainSecs <= 300) { timerEl.className = 'exam-timer warning'; }

    remainSecs--;
    setTimeout(updateTimer, 1000);
}
updateTimer();

// ---- Select Answer ----
function selectAnswer(opt, labelEl) {
    document.querySelectorAll('.option-label').forEach(l => l.classList.remove('selected'));
    labelEl.classList.add('selected');
    selectedAnswer = opt;
    saveAnswer(opt, isMarked ? 1 : 0);
}

function saveAnswer(answer, mark) {
    const fd = new FormData();
    fd.append('ajax_save', '1');
    fd.append('question_id', CURRENT_Q_ID);
    fd.append('answer', answer);
    fd.append('mark', mark);
    fetch(window.location.href.split('?')[0] + '?attempt_id=' + ATTEMPT_ID + '&q=' + CURRENT_Q, {
        method: 'POST', body: fd
    }).catch(() => {});
}

// ---- Mark for Review ----
function toggleMark() {
    isMarked = !isMarked;
    const btn = document.getElementById('markBtn');
    btn.innerHTML = isMarked
        ? '<i class="fas fa-bookmark me-1"></i>Unmark'
        : '<i class="fas fa-bookmark me-1"></i>Mark for Review';
    btn.className = isMarked ? 'btn btn-warning' : 'btn btn-outline-warning';
    saveAnswer(selectedAnswer || '', isMarked ? 1 : 0);
}

// ---- Finish Modal ----
const finishModal = new bootstrap.Modal(document.getElementById('finishModal'));
function showFinishModal() { finishModal.show(); }

// ---- Submit ----
function submitExam(auto = false) {
    if (examSubmitted && !auto) return;
    examSubmitted = true;
    const btn = document.getElementById('confirmSubmitBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...'; }

    const fd = new FormData();
    fd.append('finish_exam', '1');
    fetch(window.location.href.split('?')[0] + '?attempt_id=' + ATTEMPT_ID + '&q=' + CURRENT_Q, {
        method: 'POST', body: fd
    })
    .then(r => r.json())
    .then(d => { if (d.success) window.location.href = d.redirect; })
    .catch(() => { window.location.href = 'result_view.php?attempt_id=' + ATTEMPT_ID; });
}

// ---- Violation Monitoring ----
let violationActive = false;

function recordViolation(type, desc) {
    if (examSubmitted) return;
    const fd = new FormData();
    fd.append('record_violation', '1');
    fd.append('violation_type', type);
    fd.append('description', desc);
    fetch(window.location.href.split('?')[0] + '?attempt_id=' + ATTEMPT_ID + '&q=' + CURRENT_Q, {
        method: 'POST', body: fd
    })
    .then(r => r.json())
    .then(data => {
        violationCount = data.count;
        document.getElementById('vCount').textContent = data.count;
        if (data.terminated) {
            document.getElementById('violationOverlay').classList.add('d-none');
            document.getElementById('terminatedOverlay').classList.remove('d-none');
        } else {
            showViolation(desc);
        }
    }).catch(() => {});
}

function showViolation(msg) {
    if (violationActive) return;
    violationActive = true;
    document.getElementById('violationMsg').textContent = msg || 'A rule violation was detected.';
    document.getElementById('vCount').textContent = violationCount;
    document.getElementById('violationOverlay').classList.remove('d-none');
}

function dismissViolation() {
    document.getElementById('violationOverlay').classList.add('d-none');
    violationActive = false;
}

// Tab visibility
document.addEventListener('visibilitychange', () => {
    if (document.hidden && !examSubmitted) {
        recordViolation('tab_switch', 'Student switched browser tab or minimized window.');
    }
});

// Window blur/focus
window.addEventListener('blur', () => {
    if (!examSubmitted) {
        setTimeout(() => {
            if (!document.hasFocus() && !examSubmitted) {
                recordViolation('window_blur', 'Student switched to another application or window.');
            }
        }, 300);
    }
});

// Before unload
window.addEventListener('beforeunload', e => {
    if (!examSubmitted) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Disable right click on exam
document.addEventListener('contextmenu', e => e.preventDefault());
</script>
</body>
</html>
