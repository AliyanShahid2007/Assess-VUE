<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

// Prevent browser from caching the login page (avoids stale CSRF token on back-button)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

// Already logged in
if (isAdminLoggedIn()) {
    redirect('dashboard.php');
}

// Remember me cookie check
if (!isAdminLoggedIn() && isset($_COOKIE['admin_remember'])) {
    $token = $_COOKIE['admin_remember'];
    $admin = db()->fetchOne(
        "SELECT * FROM admins WHERE remember_token = ? AND token_expires > NOW() AND is_active = 1",
        [$token]
    );
    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        redirect('dashboard.php');
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($password)) $errors[] = 'Password is required.';

        if (empty($errors)) {
            $admin = db()->fetchOne(
                "SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1",
                [$username, $username]
            );

            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];

                // Update last login
                db()->execute("UPDATE admins SET last_login = NOW() WHERE id = ?", [$admin['id']]);
                logActivity('admin', $admin['id'], 'login', 'Admin logged in');

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+' . REMEMBER_ME_DAYS . ' days'));
                    db()->execute(
                        "UPDATE admins SET remember_token = ?, token_expires = ? WHERE id = ?",
                        [$token, $expires, $admin['id']]
                    );
                    setcookie('admin_remember', $token, time() + (REMEMBER_ME_DAYS * 86400), '/', '', false, true);
                }

                redirect('dashboard.php');
            } else {
                $errors[] = 'Invalid username or password.';
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
    <title>Admin Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%); min-height: 100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.35); max-width: 440px; width: 100%; overflow: hidden; }
        .login-header { background: linear-gradient(135deg, #1a237e, #3949ab); color: #fff; padding: 2.5rem 2rem; text-align: center; }
        .login-header .logo-icon { font-size: 3rem; margin-bottom: .5rem; }
        .login-header h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
        .login-header p { opacity: .8; font-size: .9rem; margin: .3rem 0 0; }
        .login-body { padding: 2rem; }
        .form-label { font-weight: 600; color: #37474f; font-size: .9rem; }
        .form-control { border-radius: 8px; padding: .7rem 1rem; border: 2px solid #e0e0e0; }
        .form-control:focus { border-color: #3949ab; box-shadow: 0 0 0 .2rem rgba(57,73,171,.2); }
        .input-group .form-control { border-right: none; }
        .input-group-text { background: #fff; border-left: none; border: 2px solid #e0e0e0; border-left: none; border-radius: 0 8px 8px 0; cursor: pointer; }
        .btn-login { background: linear-gradient(135deg, #1a237e, #3949ab); border: none; border-radius: 8px; padding: .8rem; font-weight: 600; font-size: 1rem; letter-spacing: .5px; color: #fff; width: 100%; }
        .btn-login:hover { background: linear-gradient(135deg, #283593, #3f51b5); transform: translateY(-1px); }
        .portal-badge { background: #e8eaf6; color: #3949ab; font-size: .75rem; font-weight: 700; padding: .25rem .75rem; border-radius: 20px; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
        <h1><?= APP_NAME ?></h1>
        <p>Professional Examination Management System</p>
        <span class="portal-badge mt-2 d-inline-block">ADMINISTRATOR LOGIN</span>
    </div>
    <div class="login-body">
        <?php if ($errors): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($errors[0]) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off" novalidate>
            <?= csrfField() ?>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user me-1"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Enter admin username"
                       value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock me-1"></i> Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordField" class="form-control" placeholder="Enter password" required>
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember"
                           <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="remember">Remember me for 30 days</label>
                </div>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i> Sign In to Admin Panel
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">
                <i class="fas fa-shield-alt me-1"></i> Secured with PHP Sessions &amp; Password Hashing
            </small>
        </div>
    </div>
</div>
<script>
function togglePassword() {
    const f = document.getElementById('passwordField');
    const eye = document.getElementById('eyeIcon');
    if (f.type === 'password') { f.type = 'text'; eye.className = 'fas fa-eye-slash'; }
    else { f.type = 'password'; eye.className = 'fas fa-eye'; }
}
</script>
</body>
</html>
