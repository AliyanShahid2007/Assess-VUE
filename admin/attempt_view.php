<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$attemptId = sanitizeInt($_GET['id'] ?? 0);

$attempt = db()->fetchOne("
    SELECT ea.*, s.full_name, s.father_name, s.student_id as stu_id, s.cnic_number,
           s.id as student_db_id, s.profile_picture,
           e.exam_name, e.exam_code, e.total_marks, e.passing_percentage,
           es.scheduled_date, es.start_time as sched_start
    FROM exam_attempts ea
    JOIN students s ON s.id = ea.student_id
    JOIN exams e ON e.id = ea.exam_id
    JOIN exam_schedules es ON es.id = ea.schedule_id
    WHERE ea.id = ?",
    [$attemptId]
);
if (!$attempt) { setFlash('error', 'Attempt not found.'); redirect('results.php'); }

$result = db()->fetchOne("SELECT * FROM exam_results WHERE attempt_id=?", [$attemptId]);

$answers = db()->fetchAll("
    SELECT sa.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.explanation, sub.name as subject_name, ch.name as chapter_name
    FROM student_answers sa
    JOIN questions q ON q.id = sa.question_id
    LEFT JOIN subjects sub ON sub.id = q.subject_id
    LEFT JOIN chapters ch ON ch.id = q.chapter_id
    WHERE sa.attempt_id = ?
    ORDER BY sa.sort_order ASC",
    [$attemptId]
);

$violations = db()->fetchAll("SELECT * FROM exam_violations WHERE attempt_id=? ORDER BY violated_at", [$attemptId]);

define('PAGE_TITLE', 'Attempt Details');
include 'includes/header.php';

$optionLabels = ['A', 'B', 'C', 'D'];
function getOptText(array $q, string $opt): string {
    return $q['option_' . strtolower($opt)] ?? '';
}
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="results.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-clipboard-check me-2 text-primary"></i>Examination Attempt Report</h2>
    <a href="print_report.php?attempt_id=<?= $attemptId ?>" class="btn btn-outline-secondary btn-sm ms-auto" target="_blank">
        <i class="fas fa-print me-1"></i>Print Report
    </a>
</div>

<div class="row g-4">
    <!-- Student Info -->
    <div class="col-12 col-md-4">
        <div class="card">
            <div class="card-header"><i class="fas fa-user me-2"></i>Student Information</div>
            <div class="card-body text-center">
                <?php if ($attempt['profile_picture']): ?>
                    <img src="serve_file.php?type=profile&id=<?= $attempt['student_db_id'] ?>"
                         class="profile-avatar mb-3" style="width:100px;height:100px">
                <?php else: ?>
                    <div class="avatar-placeholder mx-auto mb-3" style="width:100px;height:100px;font-size:2.5rem;border-radius:50%">
                        <?= strtoupper(substr($attempt['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <h5 class="mb-1"><?= sanitize($attempt['full_name']) ?></h5>
                <p class="text-muted mb-1"><?= sanitize($attempt['father_name'] ?? '') ?></p>
                <span class="badge bg-primary"><?= sanitize($attempt['stu_id']) ?></span>
                <div class="mt-2 text-muted small"><?= sanitize($attempt['cnic_number'] ?? '') ?></div>
            </div>
        </div>
    </div>

    <!-- Exam Info + Result -->
    <div class="col-12 col-md-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-clipboard-list me-2"></i>Examination Information</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm mb-0">
                            <tr><th class="text-muted">Exam Name</th><td class="fw-semibold"><?= sanitize($attempt['exam_name']) ?></td></tr>
                            <tr><th class="text-muted">Exam Code</th><td><code><?= sanitize($attempt['exam_code']) ?></code></td></tr>
                            <tr><th class="text-muted">Scheduled Date</th><td><?= formatDate($attempt['scheduled_date']) ?></td></tr>
                            <tr><th class="text-muted">Start Time</th><td><?= $attempt['start_time'] ? formatDateTime($attempt['start_time']) : '—' ?></td></tr>
                            <tr><th class="text-muted">End Time</th><td><?= $attempt['end_time'] ? formatDateTime($attempt['end_time']) : '—' ?></td></tr>
                            <tr><th class="text-muted">Time Taken</th><td><?= $result ? secondsToTime($result['time_taken_seconds'] ?? 0) : '—' ?></td></tr>
                        </table>
                    </div>
                    <?php if ($result): ?>
                    <div class="col-md-6">
                        <div class="text-center p-3 rounded" style="background:<?= $result['result'] === 'PASS' ? '#e8f5e9' : '#ffebee' ?>">
                            <div style="font-size:3rem;font-weight:800;color:<?= $result['result'] === 'PASS' ? '#1b5e20' : '#b71c1c' ?>">
                                <?= $result['result'] ?>
                            </div>
                            <div style="font-size:2rem;font-weight:700">
                                <?= number_format($result['percentage'], 1) ?>%
                            </div>
                            <div class="text-muted"><?= $result['obtained_marks'] ?>/<?= $result['total_marks'] ?> Marks</div>
                        </div>
                        <div class="row g-1 mt-2">
                            <div class="col-6"><div class="bg-success bg-opacity-10 p-2 rounded text-center">
                                <div class="fw-bold text-success"><?= $result['correct_answers'] ?></div>
                                <small class="text-muted">Correct</small>
                            </div></div>
                            <div class="col-6"><div class="bg-danger bg-opacity-10 p-2 rounded text-center">
                                <div class="fw-bold text-danger"><?= $result['incorrect_answers'] ?></div>
                                <small class="text-muted">Incorrect</small>
                            </div></div>
                            <div class="col-6"><div class="bg-secondary bg-opacity-10 p-2 rounded text-center">
                                <div class="fw-bold text-secondary"><?= $result['unanswered'] ?></div>
                                <small class="text-muted">Unanswered</small>
                            </div></div>
                            <div class="col-6"><div class="bg-info bg-opacity-10 p-2 rounded text-center">
                                <div class="fw-bold text-info"><?= $result['total_questions'] ?></div>
                                <small class="text-muted">Total</small>
                            </div></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($violations): ?>
        <div class="card mt-3">
            <div class="card-header text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Violations (<?= count($violations) ?>)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>#</th><th>Type</th><th>Description</th><th>Time</th></tr></thead>
                        <tbody>
                        <?php foreach ($violations as $i => $v): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><span class="badge bg-warning text-dark"><?= sanitize($v['violation_type']) ?></span></td>
                            <td><?= sanitize($v['description'] ?? '') ?></td>
                            <td><?= formatDateTime($v['violated_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Question-by-Question Analysis -->
<div class="card mt-4">
    <div class="card-header"><i class="fas fa-list-ol me-2"></i>Question-by-Question Analysis</div>
    <div class="card-body p-0">
        <?php foreach ($answers as $i => $a): ?>
        <div class="p-3 border-bottom">
            <div class="d-flex align-items-start gap-3">
                <span class="question-number flex-shrink-0"><?= $i+1 ?></span>
                <div class="flex-grow-1">
                    <p class="mb-2 fw-semibold"><?= sanitize($a['question_text']) ?></p>
                    <div class="row g-2 mb-2">
                        <?php foreach (['A','B','C','D'] as $opt): ?>
                        <?php
                            $optKey = 'option_' . strtolower($opt);
                            $isSel  = $a['selected_option'] === $opt;
                            $isCorr = $a['correct_option'] === $opt;
                            $cls    = '';
                            if ($isCorr) $cls = 'bg-success bg-opacity-15 border-success';
                            elseif ($isSel && !$isCorr) $cls = 'bg-danger bg-opacity-15 border-danger';
                        ?>
                        <div class="col-md-6">
                            <div class="p-2 rounded border <?= $cls ?>">
                                <span class="badge <?= $isCorr ? 'bg-success' : ($isSel && !$isCorr ? 'bg-danger' : 'bg-secondary') ?> me-2"><?= $opt ?></span>
                                <?= sanitize($a[$optKey]) ?>
                                <?php if ($isCorr): ?><i class="fas fa-check text-success ms-1"></i><?php endif; ?>
                                <?php if ($isSel && !$isCorr): ?><i class="fas fa-times text-danger ms-1"></i><?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex gap-3 small">
                        <span>Student: <strong><?= $a['selected_option'] ?: '—' ?></strong></span>
                        <span>Correct: <strong class="text-success"><?= $a['correct_option'] ?></strong></span>
                        <span>Marks:
                            <strong class="<?= $a['marks_awarded'] > 0 ? 'text-success' : ($a['marks_awarded'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                <?= $a['marks_awarded'] ?>
                            </strong>
                        </span>
                        <span class="badge-status <?= $a['is_correct'] ? 'badge-pass' : ($a['is_answered'] ? 'badge-fail' : 'badge-pending') ?>">
                            <?= $a['is_correct'] ? 'Correct' : ($a['is_answered'] ? 'Incorrect' : 'Unanswered') ?>
                        </span>
                        <?php if ($a['subject_name']): ?><span class="text-muted"><?= sanitize($a['subject_name']) ?></span><?php endif; ?>
                    </div>
                    <?php if ($a['explanation']): ?>
                    <div class="mt-2 p-2 bg-info bg-opacity-10 rounded small">
                        <strong>Explanation:</strong> <?= sanitize($a['explanation']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$answers): ?>
        <div class="text-center py-4 text-muted">No answer data found</div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
