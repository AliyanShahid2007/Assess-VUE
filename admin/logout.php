<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

if (isAdminLoggedIn()) {
    // Clear remember me
    if (isset($_COOKIE['admin_remember'])) {
        db()->execute("UPDATE admins SET remember_token=NULL, token_expires=NULL WHERE id=?", [$_SESSION['admin_id']]);
        setcookie('admin_remember', '', time() - 3600, '/');
    }
    logActivity('admin', $_SESSION['admin_id'], 'logout', 'Admin logged out');
    session_unset();
    session_destroy();
}
header('Location: login.php');
exit;
