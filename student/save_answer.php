<?php
/**
 * student/save_answer.php
 *
 * Dedicated AJAX-only endpoint for saving a single answer.
 * Runs as a completely separate PHP request from the page-load GET,
 * so it never blocks navigation on PHP's single-threaded built-in server.
 *
 * POST params:
 *   attempt_id  — int
 *   question_id — int
 *   answer      — A|B|C|D|'' (empty = clear)
 *   mark        — 0|1
 *   q_num       — current question number (for current_question update)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
$studentId = (int)($_SESSION['student_id'] ?? 0);
// Release session lock IMMEDIATELY — no session reads/writes needed after this
session_write_close();

header('Content-Type: application/json');
header('Cache-Control: no-store');

// ── Auth check ───────────────────────────────────────────────
if (!$studentId) {
    echo json_encode(['success' => false, 'error' => 'not_authenticated']);
    exit;
}

// ── Only accept POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
$attemptId  = (int)($_POST['attempt_id']  ?? 0);
$questionId = (int)($_POST['question_id'] ?? 0);
$answer     = strtoupper(trim($_POST['answer'] ?? ''));
$isMark     = (int)(bool)($_POST['mark']  ?? 0);
$qNum       = max(1, (int)($_POST['q_num'] ?? 1));
$positionOnly = isset($_POST['position_only']);

if (!$attemptId || (!$positionOnly && !$questionId)) {
    echo json_encode(['success' => false, 'error' => 'missing_params']);
    exit;
}
if ($answer !== '' && !in_array($answer, ['A','B','C','D'], true)) {
    echo json_encode(['success' => false, 'error' => 'invalid_answer']);
    exit;
}

// ── Validate attempt ownership ───────────────────────────────
$attempt = db()->fetchOne(
    "SELECT ea.exam_id, ea.status FROM exam_attempts ea
     WHERE ea.id = ? AND ea.student_id = ?",
    [$attemptId, $studentId]
);
if (!$attempt) {
    echo json_encode(['success' => false, 'error' => 'attempt_not_found']);
    exit;
}
if ($attempt['status'] !== 'in_progress') {
    echo json_encode(['success' => false, 'error' => 'attempt_not_active', 'status' => $attempt['status']]);
    exit;
}

// Update the resume position while the client switches questions without a page reload.
if ($positionOnly) {
    db()->execute("UPDATE exam_attempts SET current_question = ? WHERE id = ?", [$qNum, $attemptId]);
    echo json_encode(['success' => true]);
    exit;
}

$examId = (int)$attempt['exam_id'];

// ── Load existing student_answer row ────────────────────────
$qa = db()->fetchOne(
    "SELECT correct_option FROM student_answers
     WHERE attempt_id = ? AND question_id = ?",
    [$attemptId, $questionId]
);
if (!$qa) {
    echo json_encode(['success' => false, 'error' => 'question_not_found']);
    exit;
}

// ── Calculate marks ──────────────────────────────────────────
$isAnswered = ($answer !== '');
$isCorrect  = $isAnswered ? ($answer === $qa['correct_option'] ? 1 : 0) : null;

$eq = db()->fetchOne(
    "SELECT marks, negative_marks FROM exam_questions
     WHERE exam_id = ? AND question_id = ?",
    [$examId, $questionId]
);
$posMarks = $eq ? (float)$eq['marks']          : 0.0;
$negMarks = $eq ? (float)$eq['negative_marks'] : 0.0;

$marksAwarded = 0.0;
if ($isAnswered) {
    $marksAwarded = $isCorrect ? $posMarks : -$negMarks;
}

// ── Update student_answers ───────────────────────────────────
db()->execute(
    "UPDATE student_answers
     SET selected_option = ?,
         is_answered     = ?,
         is_correct      = ?,
         is_marked       = ?,
         marks_awarded   = ?,
         answered_at     = NOW()
     WHERE attempt_id = ? AND question_id = ?",
    [
        $answer ?: null,
        $isAnswered ? 1 : 0,
        $isCorrect,
        $isMark,
        $marksAwarded,
        $attemptId,
        $questionId,
    ]
);

// ── Update current_question position ────────────────────────
db()->execute(
    "UPDATE exam_attempts SET current_question = ? WHERE id = ?",
    [$qNum, $attemptId]
);

echo json_encode([
    'success'    => true,
    'answered'   => $isAnswered,
    'marked'     => (bool)$isMark,
    'is_correct' => $isCorrect,   // not shown to student, for server log only
    'marks'      => $marksAwarded,
]);
