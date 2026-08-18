<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

// Prevent browser from caching the login page (avoids stale CSRF token on back-button)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (isStudentLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login)) $errors[] = 'Username or Student ID is required.';
        if (empty($password)) $errors[] = 'Password is required.';

        if (empty($errors)) {
            $student = db()->fetchOne(
                "SELECT * FROM students WHERE (username = ? OR student_id = ?) AND is_active = 1",
                [$login, $login]
            );

            if ($student && password_verify($password, $student['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_username'] = $student['username'];
                $_SESSION['student_name'] = $student['full_name'];
                $_SESSION['student_db_id'] = $student['id'];

                db()->execute("UPDATE students SET last_login = NOW() WHERE id = ?", [$student['id']]);
                logActivity('student', $student['id'], 'login', 'Student logged in');
                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid credentials or account inactive.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 50%, #388e3c 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.35); max-width: 440px; width: 100%; overflow: hidden; }
        .login-header { background: linear-gradient(135deg, #1b5e20, #388e3c); color: #fff; padding: 2.5rem 2rem; text-align: center; }
        .login-header .logo-icon { font-size: 3rem; margin-bottom: .5rem; }
        .login-header h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
        .login-body { padding: 2rem; }
        .form-control { border-radius: 8px; border: 2px solid #e0e0e0; }
        .form-control:focus { border-color: #388e3c; box-shadow: 0 0 0 .2rem rgba(56,142,60,.2); }
        .btn-login { background: linear-gradient(135deg, #1b5e20, #388e3c); border: none; border-radius: 8px; padding: .8rem; font-weight: 600; color: #fff; width: 100%; }
        .portal-badge { background: #e8f5e9; color: #2e7d32; font-size: .75rem; font-weight: 700; padding: .25rem .75rem; border-radius: 20px; }
        .admin-link { color: rgba(255,255,255,.7); font-size: .85rem; }
        .admin-link:hover { color: #fff; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="logo-icon"><i class="fas fa-user-graduate"></i></div>
        <h1><?= APP_NAME ?></h1>
        <p>Student Examination Portal</p>
        <span class="portal-badge mt-2 d-inline-block">STUDENT LOGIN</span>
    </div>
    <div class="login-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($errors[0]) ?>
        </div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label fw-semibold"><i class="fas fa-id-badge me-1"></i> Username or Student ID</label>
                <input type="text" name="login" class="form-control" placeholder="Enter username or Student ID"
                       value="<?= sanitize($_POST['login'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold"><i class="fas fa-lock me-1"></i> Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passField" class="form-control" placeholder="Enter password" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                        <i class="fas fa-eye" id="passEye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In to Examination Portal
            </button>
        </form>
        <div class="text-center mt-3">
            <small class="text-muted">
                Don't have credentials? Contact your administrator.
            </small>
        </div>
        <div class="text-center mt-2">
            <a href="../admin/login.php" class="text-decoration-none small text-muted">
                <i class="fas fa-cog me-1"></i> Admin Panel
            </a>
        </div>
    </div>
</div>
<script>
function togglePass() {
    const f = document.getElementById('passField');
    const e = document.getElementById('passEye');
    f.type = f.type === 'password' ? 'text' : 'password';
    e.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
