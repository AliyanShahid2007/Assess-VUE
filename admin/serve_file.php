<?php
// Serve protected files (profile pics, CNIC docs) to admin only
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAdmin();

$type = $_GET['type'] ?? '';
$id   = sanitizeInt($_GET['id'] ?? 0);
$doc_id = sanitizeInt($_GET['doc_id'] ?? 0);

if (!$id) { http_response_code(400); exit('Invalid request.'); }

switch ($type) {
    case 'profile':
        $student = db()->fetchOne("SELECT profile_picture FROM students WHERE id=?", [$id]);
        if (!$student || empty($student['profile_picture'])) {
            http_response_code(404); exit('Not found.');
        }
        $path = UPLOAD_PROFILES . $student['profile_picture'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        serveProtectedFile($path, $mimeMap[$ext] ?? 'image/jpeg');
        break;

    case 'cnic_front':
        $doc = db()->fetchOne("SELECT file_path, mime_type FROM student_documents WHERE student_id=? AND doc_type='cnic_front' ORDER BY uploaded_at DESC LIMIT 1", [$id]);
        if (!$doc) { http_response_code(404); exit('Not found.'); }
        serveProtectedFile(UPLOAD_CNIC_FRONT . $doc['file_path'], $doc['mime_type'] ?? 'image/jpeg');
        break;

    case 'cnic_back':
        $doc = db()->fetchOne("SELECT file_path, mime_type FROM student_documents WHERE student_id=? AND doc_type='cnic_back' ORDER BY uploaded_at DESC LIMIT 1", [$id]);
        if (!$doc) { http_response_code(404); exit('Not found.'); }
        serveProtectedFile(UPLOAD_CNIC_BACK . $doc['file_path'], $doc['mime_type'] ?? 'image/jpeg');
        break;

    default:
        http_response_code(400);
        exit('Invalid file type.');
}
