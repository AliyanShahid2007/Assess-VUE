<?php
/**
 * student/serve_file.php
 * Serves the logged-in student's own profile picture.
 * Requires active student session — never exposes other students' files.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = (int)$_SESSION['student_id'];
$type      = $_GET['type'] ?? '';

if ($type === 'profile') {
    $student = db()->fetchOne("SELECT profile_picture FROM students WHERE id=?", [$studentId]);
    if (!$student || empty($student['profile_picture'])) {
        http_response_code(404); exit('Not found.');
    }
    $path = UPLOAD_PROFILES . $student['profile_picture'];
    if (!file_exists($path)) {
        http_response_code(404); exit('File missing.');
    }
    $ext     = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $mime    = $mimeMap[$ext] ?? 'image/jpeg';

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

http_response_code(400);
exit('Invalid request.');
