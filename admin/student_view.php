<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$id = sanitizeInt($_GET['id'] ?? 0);
$student = db()->fetchOne("SELECT * FROM students WHERE id = ?", [$id]);
if (!$student) { setFlash('error', 'Student not found.'); redirect('students.php'); }

define('PAGE_TITLE', 'Student Profile');

// Exam history
$history = db()->fetchAll("
    SELECT er.*, e.exam_name, e.exam_code, ea.start_time, ea.end_time,
           ea.id as attempt_id, es.scheduled_date
    FROM exam_results er
    JOIN exams e ON e.id = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = ea.schedule_id
    WHERE er.student_id = ?
    ORDER BY ea.start_time DESC",
    [$id]
);

// Documents
$docs = db()->fetchAll("SELECT * FROM student_documents WHERE student_id = ?", [$id]);
$docMap = [];
foreach ($docs as $d) {
    $docMap[$d['doc_type']] = $d;
}

include 'includes/header.php';
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="students.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>Student Profile</h2>
    <div class="ms-auto d-flex gap-2">
        <a href="student_edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="schedule_add.php?student_id=<?= $id ?>" class="btn btn-success btn-sm">
            <i class="fas fa-calendar-plus me-1"></i>Schedule Exam
        </a>
    </div>
</div>

<?= renderFlash() ?>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-12 col-md-4">
        <div class="card text-center">
            <div class="card-body pb-3">
                <?php if ($student['profile_picture']): ?>
                    <img src="serve_file.php?type=profile&id=<?= $id ?>"
                         class="profile-avatar mb-3" style="width:110px;height:110px">
                <?php else: ?>
                    <div class="avatar-placeholder mx-auto mb-3" style="width:110px;height:110px;font-size:2.5rem;border-radius:50%">
                        <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <h5 class="mb-0"><?= sanitize($student['full_name']) ?></h5>
                <p class="text-muted mb-2"><?= sanitize($student['father_name'] ?? '') ?></p>
                <span class="badge bg-primary px-3 py-2 fs-6"><?= sanitize($student['student_id']) ?></span>
                <div class="mt-2">
                    <span class="badge-status <?= $student['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card mt-3">
            <div class="card-header"><i class="fas fa-info-circle me-2"></i>Details</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr><th class="text-muted" style="width:40%">Username</th><td><?= sanitize($student['username']) ?></td></tr>
                    <tr><th class="text-muted">CNIC/B-Form</th><td><?= sanitize($student['cnic_number'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Phone</th><td><?= sanitize($student['phone'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Email</th><td><?= sanitize($student['email'] ?? '—') ?></td></tr>
                    <tr><th class="text-muted">Last Login</th><td><?= $student['last_login'] ? formatDateTime($student['last_login']) : '—' ?></td></tr>
                    <tr><th class="text-muted">Registered</th><td><?= formatDate($student['created_at']) ?></td></tr>
                </table>
                <?php if ($student['notes']): ?>
                <hr class="my-2">
                <p class="text-muted small mb-0"><strong>Notes:</strong> <?= sanitize($student['notes']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Documents + History -->
    <div class="col-12 col-md-8">
        <!-- Documents -->
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-id-card me-2"></i>Identity Documents</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4 text-center">
                        <div class="doc-card">
                            <p class="fw-semibold mb-2 small">Profile Picture</p>
                            <?php if (isset($docMap['profile_picture'])): ?>
                                <img src="serve_file.php?type=profile&id=<?= $id ?>"
                                     class="doc-preview" style="height:120px;object-fit:cover">
                            <?php else: ?>
                                <div class="text-muted py-3"><i class="fas fa-user fa-3x"></i><br><small>No photo</small></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="doc-card">
                            <p class="fw-semibold mb-2 small">CNIC / B-Form Front</p>
                            <?php if (isset($docMap['cnic_front'])): ?>
                                <img src="serve_file.php?type=cnic_front&id=<?= $id ?>"
                                     class="doc-preview" style="height:120px;object-fit:cover"
                                     onclick="openDocModal('serve_file.php?type=cnic_front&id=<?= $id ?>', 'CNIC Front')" style="cursor:pointer">
                            <?php else: ?>
                                <div class="text-muted py-3"><i class="fas fa-id-card fa-3x"></i><br><small>Not uploaded</small></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="doc-card">
                            <p class="fw-semibold mb-2 small">CNIC / B-Form Back</p>
                            <?php if (isset($docMap['cnic_back'])): ?>
                                <img src="serve_file.php?type=cnic_back&id=<?= $id ?>"
                                     class="doc-preview" style="height:120px;object-fit:cover"
                                     onclick="openDocModal('serve_file.php?type=cnic_back&id=<?= $id ?>', 'CNIC Back')" style="cursor:pointer">
                            <?php else: ?>
                                <div class="text-muted py-3"><i class="fas fa-id-card-alt fa-3x"></i><br><small>Not uploaded</small></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam History -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="fas fa-history me-2"></i>Examination History (<?= count($history) ?>)</div>
            </div>
            <div class="card-body p-0">
                <?php if ($history): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Exam</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Result</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $i => $h): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= sanitize($h['exam_name']) ?></div>
                                <small class="text-muted"><?= sanitize($h['exam_code']) ?></small>
                            </td>
                            <td>
                                <?= formatDate($h['scheduled_date']) ?>
                                <div class="small text-muted"><?= $h['start_time'] ? formatTime($h['start_time']) : '' ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold">Percentage: <?= number_format((float)$h['percentage'], 2) ?>%</span>
                                <div class="small text-muted">Correct: <?= number_format((float)($h['obtained_marks'] + ($h['negative_marks_total'] ?? 0)), 2) ?> | Wrong: <?= number_format(-(float)($h['negative_marks_total'] ?? 0), 2) ?></div>
                                <div class="small text-muted">Net Score: <?= number_format((float)$h['obtained_marks'], 2) ?>/<?= number_format((float)$h['total_marks'], 2) ?></div>
                            </td>
                            <td>
                                <span class="badge-status <?= $h['result'] === 'PASS' ? 'badge-pass' : 'badge-fail' ?>">
                                    <?= $h['result'] ?>
                                    <?php if ($h['violation_terminated']): ?>
                                        <span class="ms-1">⚠</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <a href="attempt_view.php?id=<?= $h['attempt_id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-clipboard fa-2x mb-2 d-block"></i>
                    No examination history yet
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Document Modal -->
<div class="modal fade" id="docModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="docModalTitle">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="docModalImg" src="" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
function openDocModal(src, title) {
    document.getElementById('docModalImg').src = src;
    document.getElementById('docModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('docModal')).show();
}
</script>

<?php include 'includes/footer.php'; ?>
