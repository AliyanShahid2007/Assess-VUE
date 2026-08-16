<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
if (isStudentLoggedIn()) {
    logActivity('student', $_SESSION['student_id'], 'logout', 'Student logged out');
    session_unset();
    session_destroy();
}
header('Location: login.php');
exit;
