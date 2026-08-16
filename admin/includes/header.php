<?php
// Admin Header / Layout Top - included in every admin page
if (!defined('PAGE_TITLE')) define('PAGE_TITLE', APP_NAME);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= PAGE_TITLE ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-wrapper">
<!-- SIDEBAR -->
<nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="fas fa-graduation-cap me-2"></i>
        <span><?= APP_NAME ?></span>
    </div>
    <div class="sidebar-menu">
        <div class="menu-label">MAIN</div>
        <a href="dashboard.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
        </a>

        <div class="menu-label">STUDENTS</div>
        <a href="students.php" class="menu-item <?= in_array(basename($_SERVER['PHP_SELF']), ['students.php','student_add.php','student_edit.php','student_view.php']) ? 'active' : '' ?>">
            <i class="fas fa-users"></i><span>Students</span>
        </a>

        <div class="menu-label">QUESTIONS</div>
        <a href="pdf_import.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'pdf_import.php' ? 'active' : '' ?>">
            <i class="fas fa-file-pdf"></i><span>PDF Import</span>
        </a>
        <a href="questions.php" class="menu-item <?= in_array(basename($_SERVER['PHP_SELF']), ['questions.php','question_add.php','question_edit.php']) ? 'active' : '' ?>">
            <i class="fas fa-database"></i><span>Question Bank</span>
        </a>

        <div class="menu-label">EXAMINATIONS</div>
        <a href="exams.php" class="menu-item <?= in_array(basename($_SERVER['PHP_SELF']), ['exams.php','exam_add.php','exam_edit.php','exam_questions.php']) ? 'active' : '' ?>">
            <i class="fas fa-clipboard-list"></i><span>Exams</span>
        </a>
        <a href="schedules.php" class="menu-item <?= in_array(basename($_SERVER['PHP_SELF']), ['schedules.php','schedule_add.php','schedule_edit.php']) ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i><span>Schedules</span>
        </a>

        <div class="menu-label">RESULTS</div>
        <a href="results.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'results.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i><span>All Results</span>
        </a>
        <a href="reports.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i><span>Reports</span>
        </a>

        <div class="menu-label">SETTINGS</div>
        <a href="subjects.php" class="menu-item <?= basename($_SERVER['PHP_SELF']) === 'subjects.php' ? 'active' : '' ?>">
            <i class="fas fa-book"></i><span>Subjects</span>
        </a>
        <a href="logout.php" class="menu-item text-danger-item">
            <i class="fas fa-sign-out-alt"></i><span>Logout</span>
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-content">
    <!-- TOPBAR -->
    <div class="topbar">
        <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title"><?= PAGE_TITLE ?></div>
        <div class="topbar-user">
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle topbar-avatar" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle me-1"></i>
                    <?= sanitize($_SESSION['admin_name'] ?? 'Admin') ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="content-area">
