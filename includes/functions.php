<?php
// ============================================================
//  Core Helper Functions
// ============================================================
require_once __DIR__ . '/../config/database.php';

// ---- Session -----------------------------------------------
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// ---- CSRF --------------------------------------------------
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

// ---- Auth Helpers ------------------------------------------
function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function isStudentLoggedIn(): bool {
    return !empty($_SESSION['student_id']);
}

function requireAdmin(): void {
    startSecureSession();
    if (!isAdminLoggedIn()) {
        header('Location: ' . adminUrl('login.php'));
        exit;
    }
}

function requireStudent(): void {
    startSecureSession();
    if (!isStudentLoggedIn()) {
        header('Location: ' . studentUrl('login.php'));
        exit;
    }
}

function adminUrl(string $path = ''): string {
    $base = rtrim(APP_URL, '/');
    return $base . '/admin/' . ltrim($path, '/');
}

function studentUrl(string $path = ''): string {
    $base = rtrim(APP_URL, '/');
    return $base . '/student/' . ltrim($path, '/');
}

// ---- Sanitization ------------------------------------------
function sanitize(mixed $input): string {
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function sanitizeInt(mixed $input): int {
    return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeFloat(mixed $input): float {
    return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Split an exam's total marks equally across its currently assigned questions.
 * Amounts are kept in cents so their sum always exactly equals total_marks.
 */
function distributeExamMarks(int $examId): void {
    $exam = db()->fetchOne('SELECT id FROM exams WHERE id=?', [$examId]);
    if (!$exam) return;

    // The system has one grading scale: every exam is out of 100 marks.
    db()->execute('UPDATE exams SET total_marks=100.00 WHERE id=?', [$examId]);
    $questions = db()->fetchAll(
        'SELECT id FROM exam_questions WHERE exam_id=? ORDER BY sort_order ASC, id ASC',
        [$examId]
    );
    $count = count($questions);
    if ($count === 0) return;

    $totalCents = 10000;
    $baseCents  = intdiv($totalCents, $count);
    $remainder  = $totalCents % $count;

    foreach ($questions as $index => $question) {
        // Give the first few questions one extra cent to preserve the exact total.
        $marks = ($baseCents + ($index < $remainder ? 1 : 0)) / 100;
        db()->execute('UPDATE exam_questions SET marks=? WHERE id=?', [$marks, $question['id']]);
    }

    db()->execute(
        'UPDATE exams SET marks_per_question=? WHERE id=?',
        [round($totalCents / $count / 100, 2), $examId]
    );
}

// ---- Flash Messages ----------------------------------------
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function getFlash(): array {
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlash(): string {
    $flash = getFlash();
    $html = '';
    foreach ($flash as $type => $msg) {
        $cls = match($type) {
            'success' => 'alert-success',
            'error'   => 'alert-danger',
            'warning' => 'alert-warning',
            default   => 'alert-info',
        };
        $html .= '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
               . htmlspecialchars($msg)
               . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
               . '</div>';
    }
    return $html;
}

// ---- File Upload -------------------------------------------
function validateImageUpload(array $file): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Maximum 5MB allowed.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTS)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, JPEG, PNG, WEBP'];
    }
    // MIME check. Use Fileinfo when available; some XAMPP installations
    // have the extension disabled, so fall back to PHP's image parser.
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
    } else {
        $imageInfo = @getimagesize($file['tmp_name']);
        $mime = $imageInfo['mime'] ?? false;
    }
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file content detected.'];
    }
    return ['success' => true, 'ext' => $ext, 'mime' => $mime];
}

function validatePdfUpload(array $file): array {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'Please select a PDF file before uploading.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error: ' . $file['error']];
    }
    if ($file['size'] > MAX_PDF_SIZE) {
        return ['success' => false, 'message' => 'PDF too large. Maximum 20MB allowed.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        return ['success' => false, 'message' => 'Only PDF files allowed.'];
    }
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }
    } else {
        // Fallback for hosts without Fileinfo: require a real PDF header.
        $handle = @fopen($file['tmp_name'], 'rb');
        $header = $handle ? fread($handle, 5) : false;
        if ($handle) {
            fclose($handle);
        }
        $mime = $header === '%PDF-' ? 'application/pdf' : false;
    }
    if ($mime !== 'application/pdf') {
        return ['success' => false, 'message' => 'Invalid file content. Must be a PDF.'];
    }
    return ['success' => true, 'ext' => 'pdf', 'mime' => $mime];
}

function saveUploadedFile(array $file, string $directory): array {
    $safe_name = bin2hex(random_bytes(16)) . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $dest = rtrim($directory, '/') . '/' . $safe_name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => false, 'message' => 'Failed to save file.'];
    }
    return ['success' => true, 'filename' => $safe_name, 'path' => $dest];
}

// ---- Secure File Serving -----------------------------------
function serveProtectedFile(string $filePath, string $mimeType): void {
    if (!file_exists($filePath)) {
        http_response_code(404);
        exit('File not found.');
    }
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

// ---- Activity Logging --------------------------------------
function logActivity(string $userType, int $userId, string $action, string $desc = ''): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    db()->execute(
        "INSERT INTO activity_log (user_type, user_id, action, description, ip_address) VALUES (?,?,?,?,?)",
        [$userType, $userId, $action, $desc, $ip]
    );
}

// ---- Date / Time Helpers -----------------------------------
function formatDateTime(string $dt): string {
    return date('d M Y, h:i A', strtotime($dt));
}

function formatDate(string $d): string {
    return date('d M Y', strtotime($d));
}

function formatTime(string $t): string {
    return date('h:i A', strtotime($t));
}

function secondsToTime(int $secs): string {
    $h = intdiv($secs, 3600);
    $m = intdiv($secs % 3600, 60);
    $s = $secs % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

// ---- Student ID Generator ----------------------------------
function generateStudentId(): string {
    $count = db()->fetchOne("SELECT COUNT(*) as c FROM students")['c'] ?? 0;
    return 'STU' . str_pad((int)$count + 1, 4, '0', STR_PAD_LEFT);
}

// ---- JSON Response -----------------------------------------
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ---- Redirect ----------------------------------------------
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ---- Password strength check --------------------------------
function validatePassword(string $pass): bool {
    return strlen($pass) >= 6;
}
