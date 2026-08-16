<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Students');

// Handle actions
$action = $_GET['action'] ?? '';
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = sanitizeInt($_GET['id']);
    $st = db()->fetchOne("SELECT is_active FROM students WHERE id=?", [$id]);
    if ($st) {
        $new = $st['is_active'] ? 0 : 1;
        db()->execute("UPDATE students SET is_active=? WHERE id=?", [$new, $id]);
        setFlash('success', 'Student status updated.');
    }
    redirect('students.php');
}
if ($action === 'delete' && isset($_GET['id'])) {
    $id = sanitizeInt($_GET['id']);
    db()->execute("DELETE FROM students WHERE id=?", [$id]);
    setFlash('success', 'Student deleted.');
    redirect('students.php');
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (s.full_name LIKE ? OR s.student_id LIKE ? OR s.username LIKE ? OR s.cnic_number LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($status !== '') {
    $where .= " AND s.is_active = ?";
    $params[] = (int)$status;
}

$students = db()->fetchAll(
    "SELECT s.*, COUNT(er.id) as total_exams, 
     SUM(CASE WHEN er.result='PASS' THEN 1 ELSE 0 END) as pass_count
     FROM students s
     LEFT JOIN exam_results er ON er.student_id = s.id
     $where
     GROUP BY s.id
     ORDER BY s.created_at DESC",
    $params
);

include 'includes/header.php';
?>

<?= renderFlash() ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Students</h2>
    <a href="student_add.php" class="btn btn-primary">
        <i class="fas fa-user-plus me-1"></i> Add Student
    </a>
</div>

<!-- Search / Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-sm-6 col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, ID, username, CNIC..."
                           value="<?= sanitize($search) ?>">
                </div>
            </div>
            <div class="col-sm-3 col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" type="submit"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="students.php" class="btn btn-outline-secondary ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div><i class="fas fa-list me-2"></i>All Students (<?= count($students) ?>)</div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Username</th>
                        <th>CNIC/B-Form</th>
                        <th>Exams</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th style="width:160px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($students): ?>
                <?php foreach ($students as $i => $s): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($s['profile_picture'] && file_exists(UPLOAD_PROFILES . $s['profile_picture'])): ?>
                                <img src="serve_file.php?type=profile&id=<?= $s['id'] ?>" class="student-avatar">
                            <?php else: ?>
                                <div class="avatar-placeholder"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <div>
                                <div class="fw-semibold"><?= sanitize($s['full_name']) ?></div>
                                <small class="text-muted"><?= sanitize($s['father_name'] ?? '') ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark fw-semibold"><?= sanitize($s['student_id']) ?></span></td>
                    <td><?= sanitize($s['username']) ?></td>
                    <td><?= sanitize($s['cnic_number'] ?? '—') ?></td>
                    <td>
                        <span class="badge bg-primary"><?= $s['total_exams'] ?></span>
                        <?php if ($s['total_exams'] > 0): ?>
                        <span class="badge bg-success ms-1"><?= $s['pass_count'] ?> Pass</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-status <?= $s['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                            <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><small><?= formatDate($s['created_at']) ?></small></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="student_view.php?id=<?= $s['id'] ?>" class="btn btn-outline-info" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="student_edit.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="students.php?action=toggle&id=<?= $s['id'] ?>"
                               class="btn btn-outline-<?= $s['is_active'] ? 'warning' : 'success' ?>" title="Toggle Status">
                                <i class="fas fa-<?= $s['is_active'] ? 'ban' : 'check' ?>"></i>
                            </a>
                            <a href="students.php?action=delete&id=<?= $s['id'] ?>"
                               class="btn btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this student? This will remove all their data.')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="9" class="text-center py-4 text-muted">
                    <i class="fas fa-users fa-2x mb-2 d-block"></i>
                    No students found
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
