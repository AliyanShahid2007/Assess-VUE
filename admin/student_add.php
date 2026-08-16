<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();
define('PAGE_TITLE', 'Add Student');

$errors = [];
$data = [
    'full_name' => '', 'father_name' => '', 'cnic_number' => '',
    'username' => '', 'email' => '', 'phone' => '', 'address' => '', 'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $data = [
            'full_name'   => trim($_POST['full_name'] ?? ''),
            'father_name' => trim($_POST['father_name'] ?? ''),
            'cnic_number' => trim($_POST['cnic_number'] ?? ''),
            'username'    => trim($_POST['username'] ?? ''),
            'password'    => $_POST['password'] ?? '',
            'email'       => trim($_POST['email'] ?? ''),
            'phone'       => trim($_POST['phone'] ?? ''),
            'address'     => trim($_POST['address'] ?? ''),
            'notes'       => trim($_POST['notes'] ?? ''),
        ];

        if (empty($data['full_name'])) $errors[] = 'Full name is required.';
        if (empty($data['username'])) $errors[] = 'Username is required.';
        if (empty($data['password'])) $errors[] = 'Password is required.';
        if (!validatePassword($data['password'])) $errors[] = 'Password must be at least 6 characters.';

        // Check username uniqueness
        if (!empty($data['username'])) {
            $existing = db()->fetchOne("SELECT id FROM students WHERE username = ?", [$data['username']]);
            if ($existing) $errors[] = 'Username already taken.';
        }

        if (empty($errors)) {
            $student_id = generateStudentId();
            $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $sid = db()->insert(
                "INSERT INTO students (student_id, full_name, father_name, cnic_number, username, password_hash, email, phone, address, notes, created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                [
                    $student_id, $data['full_name'], $data['father_name'], $data['cnic_number'],
                    $data['username'], $hash, $data['email'], $data['phone'], $data['address'],
                    $data['notes'], $_SESSION['admin_id']
                ]
            );

            // Handle profile picture
            if (!empty($_FILES['profile_picture']['name'])) {
                $imgVal = validateImageUpload($_FILES['profile_picture']);
                if ($imgVal['success']) {
                    $saved = saveUploadedFile($_FILES['profile_picture'], UPLOAD_PROFILES);
                    if ($saved['success']) {
                        db()->execute("UPDATE students SET profile_picture=? WHERE id=?", [$saved['filename'], $sid]);
                        // Also save in documents table
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$sid, 'profile_picture', $saved['filename'], $_FILES['profile_picture']['name'], $_FILES['profile_picture']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                } else {
                    $errors[] = 'Profile picture: ' . $imgVal['message'];
                }
            }

            // Handle CNIC Front
            if (!empty($_FILES['cnic_front']['name'])) {
                $imgVal = validateImageUpload($_FILES['cnic_front']);
                if ($imgVal['success']) {
                    $saved = saveUploadedFile($_FILES['cnic_front'], UPLOAD_CNIC_FRONT);
                    if ($saved['success']) {
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$sid, 'cnic_front', $saved['filename'], $_FILES['cnic_front']['name'], $_FILES['cnic_front']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                }
            }

            // Handle CNIC Back
            if (!empty($_FILES['cnic_back']['name'])) {
                $imgVal = validateImageUpload($_FILES['cnic_back']);
                if ($imgVal['success']) {
                    $saved = saveUploadedFile($_FILES['cnic_back'], UPLOAD_CNIC_BACK);
                    if ($saved['success']) {
                        db()->execute("INSERT INTO student_documents (student_id, doc_type, file_path, original_name, file_size, mime_type, uploaded_by) VALUES (?,?,?,?,?,?,?)",
                            [$sid, 'cnic_back', $saved['filename'], $_FILES['cnic_back']['name'], $_FILES['cnic_back']['size'], $imgVal['mime'], $_SESSION['admin_id']]);
                    }
                }
            }

            if (empty($errors)) {
                logActivity('admin', $_SESSION['admin_id'], 'create_student', "Created student: $student_id");
                setFlash('success', "Student $student_id ({$data['full_name']}) created successfully!");
                redirect('students.php');
            }
        }
    }
}
include 'includes/header.php';
?>

<?= renderFlash() ?>
<div class="d-flex align-items-center mb-4 gap-3">
    <a href="students.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Back</a>
    <h2 class="mb-0"><i class="fas fa-user-plus me-2 text-primary"></i>Add New Student</h2>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</strong>
    <ul class="mb-0 mt-1">
        <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?= csrfField() ?>
    <div class="row g-4">
        <!-- Personal Info -->
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header"><i class="fas fa-user me-2"></i>Personal Information</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= sanitize($data['full_name']) ?>" placeholder="Enter full name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father's Name</label>
                            <input type="text" name="father_name" class="form-control"
                                   value="<?= sanitize($data['father_name']) ?>" placeholder="Enter father's name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CNIC / B-Form Number</label>
                            <input type="text" name="cnic_number" class="form-control"
                                   value="<?= sanitize($data['cnic_number']) ?>" placeholder="e.g. 35202-1234567-9">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?= sanitize($data['phone']) ?>" placeholder="+92 300 0000000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= sanitize($data['email']) ?>" placeholder="student@email.com">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Enter address"><?= sanitize($data['address']) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes (Admin Only)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"><?= sanitize($data['notes']) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credentials -->
            <div class="card mt-4">
                <div class="card-header"><i class="fas fa-key me-2"></i>Login Credentials</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?= sanitize($data['username']) ?>" placeholder="e.g. student001" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="pass" class="form-control" required
                                       placeholder="Min. 6 characters" autocomplete="new-password">
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                                    <i class="fas fa-eye" id="passEye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2">
                        <small><i class="fas fa-info-circle me-1"></i> Student ID will be auto-generated. Share username and password securely with the student.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="col-12 col-lg-4">
            <div class="card">
                <div class="card-header"><i class="fas fa-id-card me-2"></i>Profile &amp; Documents</div>
                <div class="card-body">
                    <!-- Profile Picture -->
                    <div class="mb-4 text-center">
                        <label class="form-label d-block">Profile Picture</label>
                        <div class="mb-2">
                            <img id="profilePreview" src="../assets/img/no-avatar.png" class="profile-avatar" style="display:block;margin:0 auto 10px;">
                        </div>
                        <input type="file" name="profile_picture" id="profilePic" class="form-control"
                               accept=".jpg,.jpeg,.png,.webp" onchange="previewImage(this,'profilePreview')">
                        <small class="text-muted">JPG, JPEG, PNG, WEBP — Max 5MB</small>
                    </div>
                    <hr>
                    <!-- CNIC Front -->
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i>CNIC / B-Form Front</label>
                        <img id="cnicFrontPreview" src="" class="doc-preview mb-2" style="display:none">
                        <input type="file" name="cnic_front" class="form-control"
                               accept=".jpg,.jpeg,.png,.webp" onchange="previewDoc(this,'cnicFrontPreview')">
                        <small class="text-muted">Max 5MB — Stored Securely</small>
                    </div>
                    <!-- CNIC Back -->
                    <div class="mb-0">
                        <label class="form-label"><i class="fas fa-id-card-alt me-1"></i>CNIC / B-Form Back</label>
                        <img id="cnicBackPreview" src="" class="doc-preview mb-2" style="display:none">
                        <input type="file" name="cnic_back" class="form-control"
                               accept=".jpg,.jpeg,.png,.webp" onchange="previewDoc(this,'cnicBackPreview')">
                        <small class="text-muted">Max 5MB — Stored Securely</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="fas fa-save me-2"></i>Create Student
        </button>
        <a href="students.php" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewDoc(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(previewId);
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function togglePass() {
    const f = document.getElementById('pass');
    const e = document.getElementById('passEye');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
<?php include 'includes/footer.php'; ?>
