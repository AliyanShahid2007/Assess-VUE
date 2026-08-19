<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$attemptId = sanitizeInt($_GET['attempt_id'] ?? 0);

$result = db()->fetchOne("
    SELECT er.*, s.full_name, s.father_name, s.student_id as stu_id, s.cnic_number,
           s.id as student_db_id, s.profile_picture,
           e.exam_name, e.exam_code,
           ea.start_time, ea.end_time,
           es.scheduled_date
    FROM exam_results er
    JOIN students s ON s.id = er.student_id
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = er.schedule_id
    WHERE er.attempt_id = ?",
    [$attemptId]
);
if (!$result) die('Result not found.');

$timeTaken = $result['time_taken_seconds'];
$pct = (float)$result['percentage'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exam Report — <?= sanitize($result['full_name']) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; color: #000; background: #fff; }
        .page { width: 210mm; min-height: 297mm; padding: 15mm 15mm 10mm; margin: 0 auto; }
        .report-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #1a237e; padding-bottom: 10px; margin-bottom: 15px; }
        .org-name { font-size: 20px; font-weight: 800; color: #1a237e; }
        .report-title { font-size: 14px; font-weight: 700; color: #555; }
        .student-photo { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #1a237e; }
        .photo-placeholder { width: 80px; height: 80px; border-radius: 50%; background: #e8eaf6; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 800; color: #3949ab; border: 3px solid #1a237e; }
        .student-info-section { display: flex; gap: 20px; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .info-table th, .info-table td { padding: 6px 8px; border: 1px solid #dee2e6; font-size: 11px; }
        .info-table th { background: #f8f9fa; font-weight: 700; color: #555; width: 30%; }
        .result-box { text-align: center; padding: 15px 25px; border-radius: 8px; }
        .result-pass { background: #e8f5e9; border: 2px solid #43a047; }
        .result-fail { background: #ffebee; border: 2px solid #e53935; }
        .result-text { font-size: 28px; font-weight: 900; }
        .result-pass .result-text { color: #1b5e20; }
        .result-fail .result-text { color: #b71c1c; }
        .result-pct { font-size: 18px; font-weight: 700; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
        .stat-item { text-align: center; padding: 8px; border: 1px solid #dee2e6; border-radius: 4px; }
        .stat-val { font-size: 16px; font-weight: 800; }
        .stat-lbl { font-size: 9px; color: #888; margin-top: 2px; }
        .section-title { font-size: 13px; font-weight: 700; color: #1a237e; border-bottom: 2px solid #1a237e; padding-bottom: 4px; margin: 12px 0 8px; }
        .footer { margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 8px; text-align: center; font-size: 10px; color: #888; }
        .watermark { text-align: center; padding: 8px; background: <?= $result['result'] === 'PASS' ? '#e8f5e9' : '#ffebee' ?>; border-radius: 4px; font-size: 11px; }
        @media print {
            @page { size: A4; margin: 10mm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none; }
        }
        .no-print { position: fixed; top: 10px; right: 10px; z-index: 999; }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:8px 16px;background:#1a237e;color:#fff;border:none;border-radius:6px;cursor:pointer">
        🖨 Print / Save PDF
    </button>
    <button onclick="window.close()" style="padding:8px 16px;background:#666;color:#fff;border:none;border-radius:6px;cursor:pointer;margin-left:8px">
        Close
    </button>
</div>

<div class="page">
    <!-- Header -->
    <div class="report-header">
        <div>
            <div class="org-name"><span style="font-size:22px">🎓</span> <?= APP_NAME ?></div>
            <div class="report-title">Official Examination Result Certificate</div>
        </div>
        <div class="result-box <?= $result['result'] === 'PASS' ? 'result-pass' : 'result-fail' ?>">
            <div class="result-text"><?= $result['result'] ?></div>
            <div class="result-pct"><?= number_format($pct, 2) ?>%</div>
            <div style="font-size:10px;color:#777">Required: <?= $result['passing_percentage'] ?>%</div>
        </div>
    </div>

    <!-- Student + Exam Info -->
    <div class="student-info-section">
        <div style="flex-shrink:0">
            <?php if ($result['profile_picture']): ?>
                <img src="serve_file.php?type=profile&id=<?= $result['student_db_id'] ?>" class="student-photo">
            <?php else: ?>
                <div class="photo-placeholder"><?= strtoupper(substr($result['full_name'],0,1)) ?></div>
            <?php endif; ?>
        </div>
        <div style="flex:1">
            <table class="info-table">
                <tr><th>Student Name</th><td><?= sanitize($result['full_name']) ?></td><th>Father's Name</th><td><?= sanitize($result['father_name'] ?? '—') ?></td></tr>
                <tr><th>Student ID</th><td><?= sanitize($result['stu_id']) ?></td><th>CNIC / B-Form</th><td><?= sanitize($result['cnic_number'] ?? '—') ?></td></tr>
                <tr><th>Exam Name</th><td><?= sanitize($result['exam_name']) ?></td><th>Exam Code</th><td><?= sanitize($result['exam_code']) ?></td></tr>
                <tr><th>Exam Date</th><td><?= formatDate($result['scheduled_date']) ?></td><th>Start Time</th><td><?= $result['start_time'] ? formatTime($result['start_time']) : '—' ?></td></tr>
                <tr><th>End Time</th><td><?= $result['end_time'] ? formatTime($result['end_time']) : '—' ?></td><th>Time Taken</th><td><?= $timeTaken ? secondsToTime($timeTaken) : '—' ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Score Stats -->
    <div class="section-title">Score Summary</div>
    <div class="stats-grid">
        <div class="stat-item"><div class="stat-val" style="color:#1565c0"><?= $result['total_questions'] ?></div><div class="stat-lbl">Total Questions</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#1565c0"><?= $result['attempted_questions'] ?></div><div class="stat-lbl">Attempted</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#2e7d32"><?= $result['correct_answers'] ?></div><div class="stat-lbl">Correct</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#c62828"><?= $result['incorrect_answers'] ?></div><div class="stat-lbl">Incorrect</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#78909c"><?= $result['unanswered'] ?></div><div class="stat-lbl">Unanswered</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#4527a0"><?= $result['total_marks'] ?></div><div class="stat-lbl">Total Marks</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#2e7d32"><?= number_format((float)($result['obtained_marks'] + ($result['negative_marks_total'] ?? 0)), 2) ?></div><div class="stat-lbl">Correct Marks</div></div>
        <div class="stat-item"><div class="stat-val" style="color:#c62828"><?= number_format(-(float)($result['negative_marks_total'] ?? 0), 2) ?></div><div class="stat-lbl">Wrong Marks</div></div>
        <div class="stat-item"><div class="stat-val" style="color:<?= $result['result'] === 'PASS' ? '#2e7d32' : '#c62828' ?>"><?= number_format((float)$result['obtained_marks'], 2) ?></div><div class="stat-lbl">Net Score</div></div>
        <div class="stat-item"><div class="stat-val" style="color:<?= $result['result'] === 'PASS' ? '#2e7d32' : '#c62828' ?>"><?= number_format($pct, 2) ?>%</div><div class="stat-lbl">Percentage</div></div>
    </div>

    <?php if ($result['violation_terminated']): ?>
    <div class="watermark" style="background:#ffebee;border:1px solid #e53935;margin-bottom:10px">
        ⚠️ This examination was terminated due to rule violation(s). Result: FAIL
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <div class="watermark">
            <?= APP_NAME ?> | <?= sanitize($result['exam_name']) ?> | Generated: <?= date('d M Y H:i:s') ?>
        </div>
        <p style="margin-top:6px">This is a computer-generated result. For verification contact the administration.</p>
    </div>
</div>
</body>
</html>
