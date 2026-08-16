<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$editId = sanitizeInt($_GET['id'] ?? 0);
$isEdit = $editId > 0;
$sch = $isEdit ? db()->fetchOne("SELECT * FROM exam_schedules WHERE id=?", [$editId]) : null;
if ($isEdit && !$sch) { setFlash('error', 'Schedule not found.'); redirect('schedules.php'); }

define('PAGE_TITLE', $isEdit ? 'Edit Schedule' : 'Schedule Exam');

$students = db()->fetchAll("SELECT * FROM students WHERE is_active=1 ORDER BY full_name");
$exams    = db()->fetchAll("SELECT * FROM exams WHERE status='active' ORDER BY exam_name");
$errors   = [];

$data = $sch ?: [
    'student_id' => sanitizeInt($_GET['student_id'] ?? 0),
    'exam_id' => 0, 'scheduled_date' => date('Y-m-d'),
    'start_time' => '09:00', 'duration_minutes' => 60, 'notes' => '',
    'attempt_allowed' => 1, 'status' => 'scheduled'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $data = [
            'student_id'       => sanitizeInt($_POST['student_id'] ?? 0),
            'exam_id'          => sanitizeInt($_POST['exam_id'] ?? 0),
            'scheduled_date'   => $_POST['scheduled_date'] ?? '',
            'start_time'       => $_POST['start_time'] ?? '09:00',
            'duration_minutes' => sanitizeInt($_POST['duration_minutes'] ?? 60),
            'notes'            => trim($_POST['notes'] ?? ''),
            'attempt_allowed'  => (int)isset($_POST['attempt_allowed']),
            'status'           => $isEdit ? ($_POST['status'] ?? 'scheduled') : 'scheduled',
        ];

        if (!$data['student_id']) $errors[] = 'Select a student.';
        if (!$data['exam_id'])    $errors[] = 'Select an exam.';
        if (empty($data['scheduled_date'])) $errors[] = 'Exam date is required.';
        if (empty($data['start_time'])) $errors[] = 'Start time is required.';
        if ($data['duration_minutes'] < 5) $errors[] = 'Duration must be at least 5 minutes.';

        if (empty($errors)) {
            // Calculate end time
            $startTs = strtotime($data['scheduled_date'] . ' ' . $data['start_time']);
            $endTs   = $startTs + ($data['duration_minutes'] * 60);
            $endTime = date('H:i:s', $endTs);

            if ($isEdit) {
                db()->execute("UPDATE exam_schedules SET student_id=?,exam_id=?,scheduled_date=?,
                    start_time=?,end_time=?,duration_minutes=?,notes=?,attempt_allowed=?,status=?
                    WHERE id=?",
                    [$data['student_id'], $data['exam_id'], $data['scheduled_date'],
                     $data['start_time'], $endTime, $data['duration_minutes'],
                     $data['notes'], $data['attempt_allowed'], $data['status'], $editId]);
                setFlash('success', 'Schedule updated!');
            } else {
                db()->insert("INSERT INTO exam_schedules (student_id,exam_id,scheduled_date,start_time,end_time,duration_minutes,notes,attempt_allowed,created_by)
                    VALUES (?,?,?,?,?,?,?,?,?)",
                    [$data['student_id'], $data['exam_id'], $data['scheduled_date'],
                     $data['start_time'], $endTime, $data['duration_minutes'],
                     $data['notes'], $data['attempt_allowed'], $_SESSION['admin_id']]);
                setFlash('success', 'Exam scheduled successfully!');
            }
            redirect('schedules.php');
        }
    }
}

include 'includes/header.php';
?>
<?= renderFlash() ?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="schedules.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-calendar-plus me-2 text-primary"></i><?= PAGE_TITLE ?></h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card" style="max-width:700px">
    <div class="card-body">
        <form method="POST">
            <?= csrfField() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Student <span class="text-danger">*</span></label>
                    <select name="student_id" class="form-select" required onchange="fillDuration()">
                        <option value="0">— Select Student —</option>
                        <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $data['student_id'] == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['full_name']) ?> (<?= sanitize($s['student_id']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Examination <span class="text-danger">*</span></label>
                    <select name="exam_id" class="form-select" required onchange="fillDuration()">
                        <option value="0">— Select Exam —</option>
                        <?php foreach ($exams as $e): ?>
                        <option value="<?= $e['id'] ?>" data-duration="<?= $e['duration_minutes'] ?>"
                                <?= $data['exam_id'] == $e['id'] ? 'selected' : '' ?>>
                            <?= sanitize($e['exam_name']) ?> (<?= sanitize($e['exam_code']) ?> | <?= $e['duration_minutes'] ?> min)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam Date <span class="text-danger">*</span></label>
                    <input type="date" name="scheduled_date" class="form-control" required
                           value="<?= sanitize($data['scheduled_date']) ?>"
                           min="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Start Time <span class="text-danger">*</span></label>
                    <input type="time" name="start_time" class="form-control" required
                           value="<?= sanitize($data['start_time']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Duration (Minutes)</label>
                    <input type="number" name="duration_minutes" id="durationField" class="form-control" min="5"
                           value="<?= $data['duration_minutes'] ?>">
                </div>
                <?php if ($isEdit): ?>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['scheduled','completed','cancelled','missed'] as $st): ?>
                        <option value="<?= $st ?>" <?= $data['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="attempt_allowed" id="aa"
                               <?= $data['attempt_allowed'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="aa">Allow student to attempt this exam</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2"><?= sanitize($data['notes']) ?></textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save me-2"></i><?= $isEdit ? 'Update Schedule' : 'Schedule Exam' ?>
                    </button>
                    <a href="schedules.php" class="btn btn-outline-secondary ms-2 px-4">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function fillDuration() {
    const sel = document.querySelector('[name=exam_id]');
    const opt = sel.options[sel.selectedIndex];
    const dur = opt.dataset.duration;
    if (dur) document.getElementById('durationField').value = dur;
}
</script>
<?php include 'includes/footer.php'; ?>
