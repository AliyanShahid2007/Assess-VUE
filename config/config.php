<?php
// ============================================================
//  Database Configuration
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'examuser');
define('DB_PASS', 'ExamPass2024!');
define('DB_NAME', 'exam_system');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'ExamPortal Pro');
define('APP_VERSION', '1.0.0');
define('APP_URL', ''); // Leave empty for relative URLs
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_PDF_SIZE', 20 * 1024 * 1024); // 20MB

// Session settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_DAYS', 30);

// Secure upload directories - NOT web accessible
define('UPLOAD_PROFILES', UPLOAD_PATH . 'profiles/');
define('UPLOAD_CNIC_FRONT', UPLOAD_PATH . 'cnic_front/');
define('UPLOAD_CNIC_BACK', UPLOAD_PATH . 'cnic_back/');
define('UPLOAD_PDFS', UPLOAD_PATH . 'pdfs/');
define('UPLOAD_TEMP', UPLOAD_PATH . 'temp/');

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTS', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_PDF_TYPES', ['application/pdf']);
define('ALLOWED_PDF_EXTS', ['pdf']);
