<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$id = sanitizeInt($_GET['id'] ?? 0);
$student = db()->fetchOne("SELECT * FROM students WHERE id = ?", [$id]);
if (!$student) { setFlash('error', 'Student not found.'); redirect('students.php'); }

define('PAGE_TITLE', 'Edit Student');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $full_name   = trim($_POST['full_name'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $cnic_number = trim($_POST['cnic_number'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $address     = trim($_POST['address'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');
        $is_active   = (int)($_POST['is_active'] ?? 1);
        $new_password = $_POST['new_password'] ?? '';

        if (empty($full_name)) $errors[] = 'Full name is required.';
        if (empty($username)) $errors[] = 'Username is required.';

        // Username unique check
        $existing = db()->fetchOne("SELECT id FROM students WHERE username = ? AND id != ?", [$username, $id]);
        if ($existing) $errors[] = 'Username already taken by another student.';

        if (!empty($new_password) && !validatePassword($new_password)) {
            $errors[] = 'New password must be at least 6 characters.';
        }

        if (empty($errors)) {
            $update = "UPDATE students SET full_name=?, father_name=?, cnic_number=?, username=?,
                       email=?, phone=?, address=?, notes=?, is_active=?";
            $params = [$full_name, $father_name, $cnic_number, $username, $email, $phone, $address, $notes, $is_active];

            if (!empty($new_password)) {
                $update .= ", password_hash=?";
                $params[] = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            }

            $update .= " WHERE id=?";
            $params[] = $id;
            db()->execute($update, $params);

            // Profile picture update
            if (!empty($_FILES['profile_picture']['name'])) {
                $imgVal = validateImageUpload($_FILES['profile_picture']);
                if ($imgVal['success']) {
                    // Delete old file
                    if ($student['profile_picture']) {
                        @unlink(UPLOAD_PROFILES . $student['profile_picture']);
                    }
                    $saved = saveUploadedFile($_FILES['profile_picture'], UPLOAD_PROFILES);
                    if ($saved['success']) {
                        db()->execute("UPDATE students SET profile_picture=? WHERE id=?", [$saved['filename'], $id]);
                        db()->execute("DELETE FROM student_documents WHERE student_id=? AND doc_type='profile_picture'", [$id]);
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$id, 'profile_picture', $saved['filename'], $_FILES['profile_picture']['name'], $_FILES['profile_picture']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                }
            }

            // CNIC Front
            if (!empty($_FILES['cnic_front']['name'])) {
                $imgVal = validateImageUpload($_FILES['cnic_front']);
                if ($imgVal['success']) {
                    $old = db()->fetchOne("SELECT file_path FROM student_documents WHERE student_id=? AND doc_type='cnic_front'", [$id]);
                    if ($old) @unlink(UPLOAD_CNIC_FRONT . $old['file_path']);
                    $saved = saveUploadedFile($_FILES['cnic_front'], UPLOAD_CNIC_FRONT);
                    if ($saved['success']) {
                        db()->execute("DELETE FROM student_documents WHERE student_id=? AND doc_type='cnic_front'", [$id]);
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$id, 'cnic_front', $saved['filename'], $_FILES['cnic_front']['name'], $_FILES['cnic_front']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                }
            }

            // CNIC Back
            if (!empty($_FILES['cnic_back']['name'])) {
                $imgVal = validateImageUpload($_FILES['cnic_back']);
                if ($imgVal['success']) {
                    $old = db()->fetchOne("SELECT file_path FROM student_documents WHERE student_id=? AND doc_type='cnic_back'", [$id]);
                    if ($old) @unlink(UPLOAD_CNIC_BACK . $old['file_path']);
                    $saved = saveUploadedFile($_FILES['cnic_back'], UPLOAD_CNIC_BACK);
                    if ($saved['success']) {
                        db()->execute("DELETE FROM student_documents WHERE student_id=? AND doc_type='cnic_back'", [$id]);
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$id, 'cnic_back', $saved['filename'], $_FILES['cnic_back']['name'], $_FILES['cnic_back']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                }
            }

            logActivity('admin', $_SESSION['admin_id'], 'edit_student', "Edited student ID: $id");
            setFlash('success', 'Student updated successfully!');
            redirect('student_view.php?id=' . $id);
        }
    }
}

$docs = db()->fetchAll("SELECT * FROM student_documents WHERE student_id = ?", [$id]);
$docMap = [];
foreach ($docs as $d) $docMap[$d['doc_type']] = $d;

include 'includes/header.php';
?>

<?= renderFlash() ?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="student_view.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-user-edit me-2 text-primary"></i>Edit Student</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fas fa-user me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required value="<?= sanitize($student['full_name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control" value="<?= sanitize($student['father_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CNIC / B-Form Number</label>
                            <input type="text" name="cnic_number" class="form-control" value="<?= sanitize($student['cnic_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= sanitize($student['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($student['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1" <?= $student['is_active'] ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= !$student['is_active'] ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"><?= sanitize($student['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"><?= sanitize($student['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header"><i class="fas fa-key me-2"></i>Credentials</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student ID</label>
                            <input type="text" class="form-control" value="<?= sanitize($student['student_id']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required value="<?= sanitize($student['username']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label>
                            <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-id-card me-2"></i>Documents</div>
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Profile Picture</label>
                        <?php if ($student['profile_picture']): ?>
                            <img src="serve_file.php?type=profile&id=<?= $id ?>" class="profile-avatar mb-2">
                        <?php else: ?>
                            <div class="avatar-placeholder mx-auto mb-2" style="width:80px;height:80px;font-size:2rem;border-radius:50%">
                                <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="profile_picture" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">CNIC Front</label>
                        <?php if (isset($docMap['cnic_front'])): ?>
                            <img src="serve_file.php?type=cnic_front&id=<?= $id ?>" class="doc-preview mb-1" style="height:80px;object-fit:cover">
                        <?php endif; ?>
                        <input type="file" name="cnic_front" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <div>
                        <label class="form-label">CNIC Back</label>
                        <?php if (isset($docMap['cnic_back'])): ?>
                            <img src="serve_file.php?type=cnic_back&id=<?= $id ?>" class="doc-preview mb-1" style="height:80px;object-fit:cover">
                        <?php endif; ?>
                        <input type="file" name="cnic_back" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Save Changes</button>
        <a href="student_view.php?id=<?= $id ?>" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>

<?php include 'includes/footer.php'; ?>
