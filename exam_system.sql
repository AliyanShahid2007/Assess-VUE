-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 04:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `exam_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_type`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-18 15:02:55'),
(2, 'admin', 1, 'create_student', 'Created student: STU0002', '::1', '2026-08-18 15:09:54'),
(3, 'admin', 1, 'edit_student', 'Edited student ID: 4', '::1', '2026-08-18 15:10:08'),
(4, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-18 15:36:19'),
(5, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-18 15:42:19'),
(6, 'student', 4, 'exam_start', 'Started exam schedule_id=1 attempt_id=1', '::1', '2026-08-18 15:44:39'),
(7, 'student', 4, 'exam_start', 'Started exam schedule_id=2 attempt_id=2', '::1', '2026-08-18 16:14:56'),
(8, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-18 16:47:09'),
(9, 'student', 4, 'logout', 'Student logged out', '::1', '2026-08-18 16:57:54'),
(10, 'admin', 1, 'logout', 'Admin logged out', '::1', '2026-08-18 17:01:53'),
(11, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-18 19:46:02'),
(12, 'admin', 1, 'logout', 'Admin logged out', '::1', '2026-08-18 19:46:38'),
(13, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-18 19:46:52'),
(14, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-18 19:47:37'),
(15, 'student', 4, 'exam_start', 'Started exam schedule_id=3 attempt_id=3', '::1', '2026-08-18 19:48:09'),
(16, 'student', 4, 'logout', 'Student logged out', '::1', '2026-08-18 19:51:26'),
(17, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-18 19:52:03'),
(18, 'student', 4, 'logout', 'Student logged out', '::1', '2026-08-18 19:52:34'),
(19, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-18 19:52:46'),
(20, 'student', 4, 'exam_start', 'Started exam schedule_id=4 attempt_id=4', '::1', '2026-08-18 19:53:13'),
(21, 'admin', 1, 'logout', 'Admin logged out', '::1', '2026-08-18 19:56:17'),
(22, 'student', 4, 'logout', 'Student logged out', '::1', '2026-08-18 19:56:19'),
(23, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-19 13:39:08'),
(24, 'admin', 1, 'create_student', 'Created student: STU0002', '::1', '2026-08-19 13:48:33'),
(25, 'admin', 1, 'logout', 'Admin logged out', '::1', '2026-08-19 13:49:24'),
(26, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-19 13:49:36'),
(27, 'student', 6, 'login', 'Student logged in', '::1', '2026-08-19 13:49:52'),
(28, 'student', 6, 'exam_start', 'Started exam schedule_id=6 attempt_id=5', '::1', '2026-08-19 13:52:37'),
(29, 'student', 4, 'exam_start', 'Started exam schedule_id=5 attempt_id=6', '::1', '2026-08-19 13:52:38'),
(30, 'student', 4, 'logout', 'Student logged out', '::1', '2026-08-19 13:53:42'),
(31, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-19 13:53:55'),
(32, 'admin', 1, 'logout', 'Admin logged out', '::1', '2026-08-19 13:59:33'),
(33, 'student', 4, 'login', 'Student logged in', '::1', '2026-08-19 13:59:47'),
(34, 'student', 4, 'exam_start', 'Started exam schedule_id=7 attempt_id=7', '::1', '2026-08-19 14:00:09'),
(35, 'admin', 1, 'login', 'Admin logged in', '::1', '2026-08-19 14:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `remember_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `full_name`, `email`, `avatar`, `is_active`, `last_login`, `remember_token`, `token_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$sV8Fj1ZdsoEOtJmjuH9DM.gDC8QIP6jqpqQJRvMZsarl3Zno6Tm1O', 'System Administrator', 'admin@examportal.com', NULL, 1, '2026-08-19 14:33:31', NULL, NULL, '2026-08-18 09:56:13', '2026-08-19 14:33:31');

-- --------------------------------------------------------

--
-- Table structure for table `chapters`
--

CREATE TABLE `chapters` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chapters`
--

INSERT INTO `chapters` (`id`, `subject_id`, `name`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'HTML Fundamentals', NULL, 1, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(2, 1, 'CSS Styling', NULL, 2, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(3, 1, 'JavaScript Basics', NULL, 3, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(4, 2, 'PHP Syntax', NULL, 1, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(5, 2, 'PHP Functions', NULL, 2, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(6, 3, 'SQL Queries', NULL, 1, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(7, 3, 'Database Design', NULL, 2, 1, '2026-08-18 09:56:13', '2026-08-18 09:56:13');

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(10) UNSIGNED NOT NULL,
  `exam_name` varchar(300) NOT NULL,
  `exam_code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `total_questions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `marks_per_question` decimal(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `total_marks` decimal(8,2) NOT NULL DEFAULT 0.00,
  `passing_percentage` decimal(5,2) NOT NULL DEFAULT 60.00,
  `duration_minutes` int(10) UNSIGNED NOT NULL DEFAULT 60,
  `max_violations` int(10) UNSIGNED NOT NULL DEFAULT 3,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 0,
  `show_result_immediately` tinyint(1) NOT NULL DEFAULT 1,
  `instructions` text DEFAULT NULL,
  `status` enum('draft','active','archived') NOT NULL DEFAULT 'draft',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `exam_name`, `exam_code`, `description`, `subject_id`, `total_questions`, `marks_per_question`, `negative_marks`, `total_marks`, `passing_percentage`, `duration_minutes`, `max_violations`, `shuffle_questions`, `shuffle_options`, `show_result_immediately`, `instructions`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'NTS SHC Test', 'EX-01', '', 5, 50, 2.00, 0.00, 100.00, 60.00, 90, 3, 0, 0, 1, '', 'active', 1, '2026-08-18 15:41:11', '2026-08-19 13:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `exam_attempts`
--

CREATE TABLE `exam_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `expected_end_time` datetime DEFAULT NULL,
  `time_taken_seconds` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('in_progress','completed','terminated','abandoned') NOT NULL DEFAULT 'in_progress',
  `termination_reason` varchar(255) DEFAULT NULL,
  `current_question` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `ip_address` varchar(45) DEFAULT NULL,
  `browser_info` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_attempts`
--

INSERT INTO `exam_attempts` (`id`, `schedule_id`, `student_id`, `exam_id`, `start_time`, `end_time`, `expected_end_time`, `time_taken_seconds`, `status`, `termination_reason`, `current_question`, `ip_address`, `browser_info`, `created_at`, `updated_at`) VALUES
(2, 2, 4, 2, '2026-08-18 16:14:56', '2026-08-18 16:23:57', '2026-08-18 17:44:56', 541, 'completed', NULL, 61, '::1', NULL, '2026-08-18 16:14:56', '2026-08-18 16:23:57'),
(7, 7, 4, 2, '2026-08-19 14:00:08', '2026-08-19 14:07:12', '2026-08-19 15:30:08', 424, 'completed', NULL, 61, '::1', NULL, '2026-08-19 14:00:08', '2026-08-19 14:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `exam_questions`
--

CREATE TABLE `exam_questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_questions`
--

INSERT INTO `exam_questions` (`id`, `exam_id`, `question_id`, `sort_order`, `marks`, `negative_marks`) VALUES
(1, 2, 13, 1, 2.00, 0.00),
(2, 2, 14, 2, 2.00, 0.00),
(3, 2, 15, 3, 2.00, 0.00),
(4, 2, 16, 4, 2.00, 0.00),
(5, 2, 17, 5, 2.00, 0.00),
(6, 2, 18, 6, 2.00, 0.00),
(7, 2, 19, 7, 2.00, 0.00),
(8, 2, 20, 8, 2.00, 0.00),
(9, 2, 21, 9, 2.00, 0.00),
(10, 2, 22, 10, 2.00, 0.00),
(11, 2, 23, 11, 2.00, 0.00),
(12, 2, 24, 12, 2.00, 0.00),
(13, 2, 25, 13, 2.00, 0.00),
(14, 2, 26, 14, 2.00, 0.00),
(15, 2, 27, 15, 2.00, 0.00),
(16, 2, 28, 16, 2.00, 0.00),
(17, 2, 29, 17, 2.00, 0.00),
(18, 2, 30, 18, 2.00, 0.00),
(19, 2, 31, 19, 2.00, 0.00),
(20, 2, 32, 20, 2.00, 0.00),
(21, 2, 33, 21, 2.00, 0.00),
(22, 2, 34, 22, 2.00, 0.00),
(23, 2, 35, 23, 2.00, 0.00),
(24, 2, 36, 24, 2.00, 0.00),
(25, 2, 37, 25, 2.00, 0.00),
(26, 2, 38, 26, 2.00, 0.00),
(27, 2, 39, 27, 2.00, 0.00),
(28, 2, 40, 28, 2.00, 0.00),
(29, 2, 41, 29, 2.00, 0.00),
(30, 2, 42, 30, 2.00, 0.00),
(31, 2, 43, 31, 2.00, 0.00),
(32, 2, 44, 32, 2.00, 0.00),
(33, 2, 45, 33, 2.00, 0.00),
(34, 2, 46, 34, 2.00, 0.00),
(35, 2, 47, 35, 2.00, 0.00),
(36, 2, 48, 36, 2.00, 0.00),
(37, 2, 49, 37, 2.00, 0.00),
(38, 2, 50, 38, 2.00, 0.00),
(39, 2, 51, 39, 2.00, 0.00),
(40, 2, 52, 40, 2.00, 0.00),
(41, 2, 53, 41, 2.00, 0.00),
(42, 2, 54, 42, 2.00, 0.00),
(43, 2, 55, 43, 2.00, 0.00),
(44, 2, 56, 44, 2.00, 0.00),
(45, 2, 57, 45, 2.00, 0.00),
(46, 2, 58, 46, 2.00, 0.00),
(47, 2, 59, 47, 2.00, 0.00),
(48, 2, 60, 48, 2.00, 0.00),
(49, 2, 61, 49, 2.00, 0.00),
(50, 2, 62, 50, 2.00, 0.00),
(51, 2, 63, 51, 2.00, 0.00),
(52, 2, 64, 52, 2.00, 0.00),
(53, 2, 65, 53, 2.00, 0.00),
(54, 2, 66, 54, 2.00, 0.00),
(55, 2, 67, 55, 2.00, 0.00),
(56, 2, 68, 56, 2.00, 0.00),
(57, 2, 69, 57, 2.00, 0.00),
(58, 2, 70, 58, 2.00, 0.00),
(59, 2, 71, 59, 2.00, 0.00),
(60, 2, 72, 60, 2.00, 0.00),
(61, 2, 73, 61, 2.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `total_questions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `attempted_questions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `correct_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `incorrect_answers` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unanswered` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_marks` decimal(8,2) NOT NULL DEFAULT 0.00,
  `obtained_marks` decimal(8,2) NOT NULL DEFAULT 0.00,
  `negative_marks_total` decimal(8,2) NOT NULL DEFAULT 0.00,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `passing_percentage` decimal(5,2) NOT NULL DEFAULT 60.00,
  `result` enum('PASS','FAIL') NOT NULL DEFAULT 'FAIL',
  `violation_terminated` tinyint(1) NOT NULL DEFAULT 0,
  `time_taken_seconds` int(10) UNSIGNED DEFAULT NULL,
  `calculated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `attempt_id`, `student_id`, `exam_id`, `schedule_id`, `total_questions`, `attempted_questions`, `correct_answers`, `incorrect_answers`, `unanswered`, `total_marks`, `obtained_marks`, `negative_marks_total`, `percentage`, `passing_percentage`, `result`, `violation_terminated`, `time_taken_seconds`, `calculated_at`) VALUES
(2, 2, 4, 2, 2, 61, 61, 51, 10, 0, 122.00, 102.00, 0.00, 83.61, 60.00, 'PASS', 0, 541, '2026-08-18 16:23:57'),
(6, 7, 4, 2, 7, 61, 61, 45, 16, 0, 122.00, 90.00, 0.00, 73.77, 60.00, 'PASS', 0, 424, '2026-08-19 14:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time DEFAULT NULL,
  `duration_minutes` int(10) UNSIGNED NOT NULL,
  `status` enum('scheduled','active','completed','cancelled','missed') NOT NULL DEFAULT 'scheduled',
  `attempt_allowed` tinyint(1) NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`id`, `student_id`, `exam_id`, `scheduled_date`, `start_time`, `end_time`, `duration_minutes`, `status`, `attempt_allowed`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 4, 2, '2026-08-18', '16:14:00', '17:44:00', 90, 'completed', 1, '', 1, '2026-08-18 16:14:33', '2026-08-18 16:23:57'),
(7, 4, 2, '2026-08-19', '14:00:00', '15:30:00', 90, 'completed', 1, '', 1, '2026-08-19 13:59:31', '2026-08-19 14:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `exam_violations`
--

CREATE TABLE `exam_violations` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `violation_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `violation_count` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `violated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_violations`
--

INSERT INTO `exam_violations` (`id`, `attempt_id`, `student_id`, `exam_id`, `violation_type`, `description`, `violation_count`, `violated_at`) VALUES
(4, 2, 4, 2, 'window_blur', 'Student switched to another application.', 1, '2026-08-18 16:15:31'),
(5, 2, 4, 2, 'window_blur', 'Student switched to another application.', 2, '2026-08-18 16:15:53'),
(13, 7, 4, 2, 'tab_switch', 'Student switched browser tab or minimized window.', 1, '2026-08-19 14:00:19'),
(14, 7, 4, 2, 'window_blur', 'Student switched to another application.', 2, '2026-08-19 14:00:19');

-- --------------------------------------------------------

--
-- Table structure for table `pdf_imports`
--

CREATE TABLE `pdf_imports` (
  `id` int(10) UNSIGNED NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `total_extracted` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('pending','processed','published','failed') NOT NULL DEFAULT 'pending',
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `chapter_id` int(10) UNSIGNED DEFAULT NULL,
  `imported_by` int(10) UNSIGNED DEFAULT NULL,
  `imported_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pdf_imports`
--

INSERT INTO `pdf_imports` (`id`, `original_name`, `stored_name`, `file_size`, `total_extracted`, `status`, `subject_id`, `chapter_id`, `imported_by`, `imported_at`) VALUES
(1, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'cfb517f870b16cff668b8fc57d9a34ee_1787047836.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:10:36'),
(2, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'b481b2fc37cc21cb155af3fd4b5aa284_1787047882.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:11:22'),
(3, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '16f40f7e1e269d23b4cd25a27c9b277c_1787047887.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:11:27'),
(4, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '8967775872582325892d0a3ee6d8603c_1787047896.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:11:36'),
(5, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'aa5f0687a7a0525f2b6d2e6a1058aed0_1787048168.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:16:08'),
(6, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'd1da07e0825b9bccc9a5680c35104359_1787048405.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:20:05'),
(7, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '20a908edc06a13a0dee49cb449ee44ec_1787048480.pdf', 23974, 12, 'published', NULL, NULL, 1, '2026-08-18 15:21:20'),
(8, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'd63957f32c77474dcfa0a5fb934ee181_1787048775.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:26:15'),
(9, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'a5c91e97730273ba597f3c5c0af6be41_1787048802.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:26:42'),
(10, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '59d7168abdb36145aa917fd5f1d5dcc0_1787048807.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:26:47'),
(11, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'bd5ec6b3e5b362b8db25a38f720985a9_1787048891.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:28:11'),
(12, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '7223cd24f275757a9ff19c3fe8ef7eec_1787048943.pdf', 23974, 0, 'processed', NULL, NULL, 1, '2026-08-18 15:29:03'),
(13, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', 'a7c5017dc0a0a6234317262f04285f68_1787048951.pdf', 23974, 0, 'processed', 5, NULL, 1, '2026-08-18 15:29:11'),
(14, 'Constitution_NTS_MCQs_Part_1_Questions_1_to_25.pdf', '52c5653b3fdab4791747e8a4a97976b2_1787049259.pdf', 23974, 0, 'processed', 5, NULL, 1, '2026-08-18 15:34:19'),
(15, 'Constitution_NTS_MCQs_Part1_With_Ticks.pdf', '29d4bd8c0d25cc5982b2b725c9b3ffbc_1787049392.pdf', 23878, 10, 'published', 5, NULL, 1, '2026-08-18 15:36:32'),
(16, 'Constitution_NTS_MCQs_Part3_Questions_51_to_76.pdf', 'a70b92e3a89c6dfdd89c21bf81c9720c_1787050228.pdf', 25169, 0, 'processed', 5, NULL, 1, '2026-08-18 15:50:28'),
(17, 'Constitution_NTS_MCQs_Part3_Questions_51_to_76.pdf', '8a8c72dfea98a4f8983b52a4b5d47a73_1787050333.pdf', 25169, 0, 'processed', 5, NULL, 1, '2026-08-18 15:52:13'),
(18, 'Constitution_NTS_MCQs_Part3_Questions_51_to_76.pdf', 'f315b40484a821b13ae1a0c381a09cd8_1787050389.pdf', 25169, 26, 'published', 5, NULL, 1, '2026-08-18 15:53:09'),
(19, '1 to 25.pdf', '3dc6d7973f3b1320f6fee08c6ddb7cd1_1787051172.pdf', 6525, 25, 'published', 5, NULL, 1, '2026-08-18 16:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(10) UNSIGNED NOT NULL,
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `chapter_id` int(10) UNSIGNED DEFAULT NULL,
  `question_text` text NOT NULL,
  `option_a` text NOT NULL,
  `option_b` text NOT NULL,
  `option_c` text NOT NULL,
  `option_d` text NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `explanation` text DEFAULT NULL,
  `difficulty` enum('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `source_pdf` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `subject_id`, `chapter_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `explanation`, `difficulty`, `marks`, `negative_marks`, `source_pdf`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(13, 5, NULL, 'The Constitution came into force on?', '10 April 1973', '12 April 1973', '14 August 1973', '23 March 1956', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(14, 5, NULL, 'The Constitution was adopted on?', '10 April 1973', '12 April 1973', '14 August 1973', '23 March 1956', 'A', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(15, 5, NULL, 'Objectives Resolution is in?', 'Article 1', 'Article 2', 'Article 2A', 'Article 3', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(16, 5, NULL, 'State Religion?', 'Christianity', 'Islam', 'Hinduism', 'None', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(17, 5, NULL, 'Fundamental Rights begin from?', 'Article 1', 'Article 5', 'Article 8', 'Article 29', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(18, 5, NULL, 'Head of State?', 'President', 'Prime Minister', 'Chief Justice', 'Governor', 'A', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(19, 5, NULL, 'Head of Government?', 'President', 'Prime Minister', 'Chairman Senate', 'Speaker', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(20, 5, NULL, 'President minimum age?', '35', '40', '45', '50', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(21, 5, NULL, 'Parliament Article?', '41', '45', '50', '59', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(22, 5, NULL, 'Money Bill introduced in?', 'Senate', 'National Assembly', 'Supreme Court', 'Provincial Assembly', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 15:40:10', '2026-08-18 15:40:10'),
(23, 5, NULL, 'Article 41 relates to:', 'Parliament', 'President', 'Judiciary', 'Governor', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(24, 5, NULL, 'Article 45 deals with:', 'Power of Pardon', 'Emergency', 'Budget', 'Senate', 'A', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(25, 5, NULL, 'Head of Government is:', 'President', 'Prime Minister', 'Governor', 'Chief Justice', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(26, 5, NULL, 'Parliament consists of:', 'NA & Senate', 'President, Senate & NA', 'Cabinet', 'Courts', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(27, 5, NULL, 'Upper House is:', 'NA', 'Senate', 'Cabinet', 'Provincial Assembly', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(28, 5, NULL, 'Money Bill originates in:', 'Senate', 'National Assembly', 'SC', 'President', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(29, 5, NULL, 'Article 89 relates to:', 'Governor', 'Ordinance', 'Budget', 'Election', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(30, 5, NULL, 'PM elected by:', 'President', 'National Assembly', 'Senate', 'CJP', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(31, 5, NULL, 'Article 95 is:', 'President', 'No Confidence', 'Judiciary', 'Finance', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(32, 5, NULL, 'Attorney General article:', '99', '100', '101', '102', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(33, 5, NULL, 'Governor appointed by:', 'PM', 'President', 'CM', 'CJ', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(34, 5, NULL, 'Governor minimum age:', '30', '35', '40', '45', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(35, 5, NULL, 'CM elected by:', 'President', 'Provincial Assembly', 'Senate', 'Cabinet', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(36, 5, NULL, 'Local Govt article:', '130', '135', '140A', '145', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(37, 5, NULL, 'Judiciary starts from:', '170', '175', '176', '177', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(38, 5, NULL, 'Highest Court:', 'HC', 'SC', 'FSC', 'Sessions', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(39, 5, NULL, 'Suo Motu:', '175', '184(3)', '199', '212', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(40, 5, NULL, 'Writ jurisdiction:', '192', '193', '199', '203', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(41, 5, NULL, 'Islamic injunctions:', '225', '226', '227', '228', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(42, 5, NULL, 'CII article:', '227', '228', '229', '230', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(43, 5, NULL, 'Emergency:', '231', '232', '233', '234', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(44, 5, NULL, 'Financial emergency:', '234', '235', '236', '237', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(45, 5, NULL, 'Amendment article:', '237', '238', '239', '240', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(46, 5, NULL, 'Procedure article:', '238', '239', '240', '241', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(47, 5, NULL, 'State religion:', 'None', 'Islam', 'Christianity', 'Hinduism', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(48, 5, NULL, 'Objectives Resolution:', '2', '2A', '3', '4', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:00:21', '2026-08-18 16:00:21'),
(49, 5, NULL, 'The Constitution of Pakistan came into force on?', '14 August 1973', '23 March 1956', '10 April 1973', '12 April 1973 Correct Answer: A. 14 August 1973', 'A', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(50, 5, NULL, 'The Constitution of Pakistan was adopted on?', '12 April 1973', '10 April 1973', '14 August 1973', '23 March 1973 Correct Answer: B. 10 April 1973', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(51, 5, NULL, 'The Constitution of Pakistan was authenticated on?', '10 April 1973', '14 August 1973', '12 April 1973', '23 March 1973 Correct Answer: C. 12 April 1973', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(52, 5, NULL, 'What system of government is provided by the Constitution of Pakistan?', 'Presidential', 'Federal presidential', 'Parliamentary', 'Unitary Correct Answer: C. Parliamentary', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(53, 5, NULL, 'The Constitution of Pakistan is a?', 'Written Constitution', 'Unwritten Constitution', 'Flexible Constitution only', 'Customary Constitution Correct Answer: A. Written Constitution', 'A', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(54, 5, NULL, 'What is the supreme law of Pakistan?', 'Parliament', 'Federal Government', 'Constitution', 'Supreme Court Rules Correct Answer: C. Constitution 1 0 0 1 0 0 cm BT /F1 12 Tf 14.4 TL ET q 1 0 0 1 51.35433 778.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q7. Article 1 of the Constitution deals with?) Tj T* ET Q Q q 1 0 0 1 51.35433 759.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Fundamental Rights) Tj T* ET Q Q q 1 0 0 1 51.35433 743.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Name and Territories) Tj T* ET Q Q q 1 0 0 1 51.35433 727.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Citizenship) Tj T* ET Q Q q 1 0 0 1 51.35433 711.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. State Religion) Tj T* ET Q Q q 1 0 0 1 51.35433 695.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Name and Territories) Tj T* ET Q Q q 1 0 0 1 51.35433 668.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q8. Which article declares Islam as the State religion of Pakistan?) Tj T* ET Q Q q 1 0 0 1 51.35433 649.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 1) Tj T* ET Q Q q 1 0 0 1 51.35433 633.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 2) Tj T* ET Q Q q 1 0 0 1 51.35433 617.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 2A) Tj T* ET Q Q q 1 0 0 1 51.35433 601.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 31) Tj T* ET Q Q q 1 0 0 1 51.35433 585.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Article 2) Tj T* ET Q Q q 1 0 0 1 51.35433 558.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q9. The Objectives Resolution is made a substantive part of the Constitution through?) Tj T* ET Q Q q 1 0 0 1 51.35433 539.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 2) Tj T* ET Q Q q 1 0 0 1 51.35433 523.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 2A) Tj T* ET Q Q q 1 0 0 1 51.35433 507.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 8) Tj T* ET Q Q q 1 0 0 1 51.35433 491.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 40) Tj T* ET Q Q q 1 0 0 1 51.35433 475.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Article 2A) Tj T* ET Q Q q 1 0 0 1 51.35433 448.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q10. Fundamental Rights begin from which article?) Tj T* ET Q Q q 1 0 0 1 51.35433 429.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 5) Tj T* ET Q Q q 1 0 0 1 51.35433 413.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 8) Tj T* ET Q Q q 1 0 0 1 51.35433 397.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 10) Tj T* ET Q Q q 1 0 0 1 51.35433 381.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 14) Tj T* ET Q Q q 1 0 0 1 51.35433 365.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Article 8) Tj T* ET Q Q q 1 0 0 1 51.35433 338.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q11. Fundamental Rights extend up to which article?) Tj T* ET Q Q q 1 0 0 1 51.35433 319.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 25) Tj T* ET Q Q q 1 0 0 1 51.35433 303.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 27) Tj T* ET Q Q q 1 0 0 1 51.35433 287.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 28) Tj T* ET Q Q q 1 0 0 1 51.35433 271.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 30) Tj T* ET Q Q q 1 0 0 1 51.35433 255.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: C. Article 28) Tj T* ET Q Q q 1 0 0 1 51.35433 228.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q12. Principles of Policy are contained in?) Tj T* ET Q Q q 1 0 0 1 51.35433 209.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Articles 8\\22628) Tj T* ET Q Q q 1 0 0 1 51.35433 193.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Articles 29\\22640) Tj T* ET Q Q q 1 0 0 1 51.35433 177.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Articles 41\\22650) Tj T* ET Q Q q 1 0 0 1 51.35433 161.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Articles 50\\22663) Tj T* ET Q Q q 1 0 0 1 51.35433 145.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Articles 29\\22640) Tj T* ET Q Q', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(55, 5, NULL, 'Article 1 of the Constitution deals with?', 'Fundamental Rights', 'Name and Territories', 'Citizenship', 'State Religion Correct Answer: B. Name and Territories', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(56, 5, NULL, 'Which article declares Islam as the State religion of Pakistan?', 'Article 1', 'Article 2', 'Article 2A', 'Article 31 Correct Answer: B. Article 2', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(57, 5, NULL, 'The Objectives Resolution is made a substantive part of the Constitution through?', 'Article 2', 'Article 2A', 'Article 8', 'Article 40 Correct Answer: B. Article 2A', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(58, 5, NULL, 'Fundamental Rights begin from which article?', 'Article 5', 'Article 8', 'Article 10', 'Article 14 Correct Answer: B. Article 8', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(59, 5, NULL, 'Fundamental Rights extend up to which article?', 'Article 25', 'Article 27', 'Article 28', 'Article 30 Correct Answer: C. Article 28', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(60, 5, NULL, 'Principles of Policy are contained in?', 'Articles 8 28', 'Articles 29 40', 'Articles 41 50', 'Articles 50 63 Correct Answer: B. Articles 29 40 1 0 0 1 0 0 cm BT /F1 12 Tf 14.4 TL ET q 1 0 0 1 51.35433 778.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q13. Who is the Head of State of Pakistan?) Tj T* ET Q Q q 1 0 0 1 51.35433 759.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Prime Minister) Tj T* ET Q Q q 1 0 0 1 51.35433 743.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Chief Justice) Tj T* ET Q Q q 1 0 0 1 51.35433 727.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. President) Tj T* ET Q Q q 1 0 0 1 51.35433 711.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Chairman Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 695.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: C. President) Tj T* ET Q Q q 1 0 0 1 51.35433 668.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q14. Who is the Head of Government of Pakistan?) Tj T* ET Q Q q 1 0 0 1 51.35433 649.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. President) Tj T* ET Q Q q 1 0 0 1 51.35433 633.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Prime Minister) Tj T* ET Q Q q 1 0 0 1 51.35433 617.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Speaker National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 601.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Chairman Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 585.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Prime Minister) Tj T* ET Q Q q 1 0 0 1 51.35433 558.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q15. What is the minimum age required to become President of Pakistan?) Tj T* ET Q Q q 1 0 0 1 51.35433 539.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. 35 years) Tj T* ET Q Q q 1 0 0 1 51.35433 523.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. 40 years) Tj T* ET Q Q q 1 0 0 1 51.35433 507.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. 45 years) Tj T* ET Q Q q 1 0 0 1 51.35433 491.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. 50 years) Tj T* ET Q Q q 1 0 0 1 51.35433 475.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: C. 45 years) Tj T* ET Q Q q 1 0 0 1 51.35433 448.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q16. What is the term of office of the President of Pakistan?) Tj T* ET Q Q q 1 0 0 1 51.35433 429.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. 4 years) Tj T* ET Q Q q 1 0 0 1 51.35433 413.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. 5 years) Tj T* ET Q Q q 1 0 0 1 51.35433 397.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. 6 years) Tj T* ET Q Q q 1 0 0 1 51.35433 381.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. 7 years) Tj T* ET Q Q q 1 0 0 1 51.35433 365.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. 5 years) Tj T* ET Q Q q 1 0 0 1 51.35433 338.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q17. Article 45 of the Constitution deals with?) Tj T* ET Q Q q 1 0 0 1 51.35433 319.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Election of President) Tj T* ET Q Q q 1 0 0 1 51.35433 303.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Power of Pardon) Tj T* ET Q Q q 1 0 0 1 51.35433 287.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Prime Minister\'s appointment) Tj T* ET Q Q q 1 0 0 1 51.35433 271.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Emergency powers) Tj T* ET Q Q q 1 0 0 1 51.35433 255.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Power of Pardon) Tj T* ET Q Q q 1 0 0 1 51.35433 228.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q18. Parliament of Pakistan consists of?) Tj T* ET Q Q q 1 0 0 1 51.35433 209.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Senate and National Assembly only) Tj T* ET Q Q q 1 0 0 1 51.35433 193.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. President, Senate and National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 177.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. President and Senate only) Tj T* ET Q Q q 1 0 0 1 51.35433 161.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. National Assembly and Cabinet) Tj T* ET Q Q q 1 0 0 1 51.35433 145.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. President, Senate and National Assembly) Tj T* ET Q Q', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(61, 5, NULL, 'Who is the Head of State of Pakistan?', 'Prime Minister', 'Chief Justice', 'President', 'Chairman Senate Correct Answer: C. President', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(62, 5, NULL, 'Who is the Head of Government of Pakistan?', 'President', 'Prime Minister', 'Speaker National Assembly', 'Chairman Senate Correct Answer: B. Prime Minister', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(63, 5, NULL, 'What is the minimum age required to become President of Pakistan?', '35 years', '40 years', '45 years', '50 years Correct Answer: C. 45 years', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(64, 5, NULL, 'What is the term of office of the President of Pakistan?', '4 years', '5 years', '6 years', '7 years Correct Answer: B. 5 years', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(65, 5, NULL, 'Article 45 of the Constitution deals with?', 'Election of President', 'Power of Pardon', 'Prime Minister\'s appointment', 'Emergency powers Correct Answer: B. Power of Pardon', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(66, 5, NULL, 'Parliament of Pakistan consists of?', 'Senate and National Assembly only', 'President, Senate and National Assembly', 'President and Senate only', 'National Assembly and Cabinet Correct Answer: B. President, Senate and National Assembly 1 0 0 1 0 0 cm BT /F1 12 Tf 14.4 TL ET q 1 0 0 1 51.35433 778.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q19. Parliament is constituted under which article?) Tj T* ET Q Q q 1 0 0 1 51.35433 759.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 48) Tj T* ET Q Q q 1 0 0 1 51.35433 743.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 50) Tj T* ET Q Q q 1 0 0 1 51.35433 727.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 52) Tj T* ET Q Q q 1 0 0 1 51.35433 711.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 54) Tj T* ET Q Q q 1 0 0 1 51.35433 695.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Article 50) Tj T* ET Q Q q 1 0 0 1 51.35433 668.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q20. What is the Upper House of Parliament called?) Tj T* ET Q Q q 1 0 0 1 51.35433 649.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 633.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 617.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Provincial Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 601.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Council of Common Interests) Tj T* ET Q Q q 1 0 0 1 51.35433 585.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 558.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q21. What is the Lower House of Parliament called?) Tj T* ET Q Q q 1 0 0 1 51.35433 539.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 523.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 507.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Provincial Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 491.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Majlis e Shoora) Tj T* ET Q Q q 1 0 0 1 51.35433 475.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 448.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q22. What is the term of the National Assembly?) Tj T* ET Q Q q 1 0 0 1 51.35433 429.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. 4 years) Tj T* ET Q Q q 1 0 0 1 51.35433 413.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. 5 years) Tj T* ET Q Q q 1 0 0 1 51.35433 397.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. 6 years) Tj T* ET Q Q q 1 0 0 1 51.35433 381.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. 7 years) Tj T* ET Q Q q 1 0 0 1 51.35433 365.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. 5 years) Tj T* ET Q Q q 1 0 0 1 51.35433 338.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q23. The Senate of Pakistan is known as a?) Tj T* ET Q Q q 1 0 0 1 51.35433 319.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Temporary House) Tj T* ET Q Q q 1 0 0 1 51.35433 303.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Permanent House) Tj T* ET Q Q q 1 0 0 1 51.35433 287.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Dissolved House) Tj T* ET Q Q q 1 0 0 1 51.35433 271.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Provincial House) Tj T* ET Q Q q 1 0 0 1 51.35433 255.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. Permanent House) Tj T* ET Q Q q 1 0 0 1 51.35433 228.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q24. A Money Bill can be introduced only in?) Tj T* ET Q Q q 1 0 0 1 51.35433 209.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Senate) Tj T* ET Q Q q 1 0 0 1 51.35433 193.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. National Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 177.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Supreme Court) Tj T* ET Q Q q 1 0 0 1 51.35433 161.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Provincial Assembly) Tj T* ET Q Q q 1 0 0 1 51.35433 145.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: B. National Assembly) Tj T* ET Q Q', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(67, 5, NULL, 'Parliament is constituted under which article?', 'Article 48', 'Article 50', 'Article 52', 'Article 54 Correct Answer: B. Article 50', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(68, 5, NULL, 'What is the Upper House of Parliament called?', 'National Assembly', 'Senate', 'Provincial Assembly', 'Council of Common Interests Correct Answer: B. Senate', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(69, 5, NULL, 'What is the Lower House of Parliament called?', 'Senate', 'National Assembly', 'Provincial Assembly', 'Majlis e Shoora Correct Answer: B. National Assembly', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(70, 5, NULL, 'What is the term of the National Assembly?', '4 years', '5 years', '6 years', '7 years Correct Answer: B. 5 years', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(71, 5, NULL, 'The Senate of Pakistan is known as a?', 'Temporary House', 'Permanent House', 'Dissolved House', 'Provincial House Correct Answer: B. Permanent House', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(72, 5, NULL, 'A Money Bill can be introduced only in?', 'Senate', 'National Assembly', 'Supreme Court', 'Provincial Assembly Correct Answer: B. National Assembly 1 0 0 1 0 0 cm BT /F1 12 Tf 14.4 TL ET q 1 0 0 1 51.35433 778.3701 cm q 0 0 0 rg BT 1 0 0 1 0 4 Tm /F3 11 Tf 15 TL (Q25. The President\'s power to promulgate Ordinances is provided under?) Tj T* ET Q Q q 1 0 0 1 51.35433 759.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (A. Article 45) Tj T* ET Q Q q 1 0 0 1 51.35433 743.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (B. Article 50) Tj T* ET Q Q q 1 0 0 1 51.35433 727.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (C. Article 89) Tj T* ET Q Q q 1 0 0 1 51.35433 711.3701 cm q 0 0 0 rg BT 1 0 0 1 14 4 Tm /F1 10 Tf 14 TL (D. Article 91) Tj T* ET Q Q q 1 0 0 1 51.35433 695.3701 cm q 0 .392157 0 rg BT 1 0 0 1 0 3 Tm /F2 9 Tf 12 TL (Correct Answer: C. Article 89) Tj T* ET Q Q', 'B', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59'),
(73, 5, NULL, 'The President\'s power to promulgate Ordinances is provided under?', 'Article 45', 'Article 50', 'Article 89', 'Article 91 Correct Answer: C. Article 89 opensource \\(anonymous\\ D:20260818110500+00\'00\' \\(unspecified\\ D:20260818110500+00\'00\' ReportLab PDF Library - \\(opensource\\ \\(unspecified\\ \\(anonymous\\ aMBW!$TCThE!Sq!0BZ*?`3M\\*qV1diV3 b;XGn27t8bONO/:YGku\"o3A`?c1Xs$..K2\\!J(lV[aq;BU2J>IXLROrUGh<+C,U@c]\"Y3agfuJSi\"TsILY.BCLCW7D?%HThAC7W*X>;!-_ba(2PC`D$O-RQ ane.4n1$hFj&f!06NYAKfg89E\"&s`^0[?W<u-5Eb_sEj(-\'aBT\" fPV.U~>endstream endobj 14 0 obj << /Filter [ /ASCII85Decode /FlateDecode ] /Length 800 >> stream Gau1-997OU&:j6J\'lsKf+b&Oh\"a(2\'+X,M*7cM92:#*`]*lW:OnEY j9-?@TOF;fVg#W$`lphBq0YnWZ3 ?AHF6]pcGKaDk,PH/g5^5fbXQkbidt(^DbF<Y`#aUUhc(9#@ZFNE(.3<S\'Pdo@gu\\g$7rtaIXQ%eHbX.Y#Bn-oLpB\\+!P^NiGN?s,gub]2f4sAZF+reF1&$L5nH$q9=tYjPJ]]CQB4\\?Q0krA(ab6_11pX3PZ>J0CFZ_]TElEnH5FA\\c &uV^Q;Spd\'/m*9P!V2@l#(L3f!;M6D9Z,H2_[;/; (RF1oUo\\r*rhitBkMm,>\'f3!LWD?pfc7d^A7,!b3-UQ;&sJeVN9oRXplaV0MLR4h+0&>K\\0\"3@\"+T,5f`YC8HPkL+JFXr8em9jYC_\"_P+ _BLgGW=N>B66hfk&=.8<.CteKR\"Vd8^64\\ob+D_GAnJ@RgKeVH,@3tn6<P(MWNku=&l$\"q%Z$`K@kWfD@WtZ-e_*UI8]OIs\"*i1m/_%@TD+!41G-rfK3CbA0SJ,/6Z35mGEG=W&<WgHF8gDQc8a8TE\"#TCrR%ona*m?M0FQe8W\",o>JCg:&j\"!`O#nf--9s/$Y\\IfMlge!\'~>endstream endobj 15 0 obj << /Filter [ /ASCII85Decode /FlateDecode ] /Length 814 >> stream GauI6?Z4XP\'ZJu*\'_cHo1hCZC-Y+kDASfi&9s\'5YB1\"<026j,j<q5Qi\"0L,h%25KC$!@T -P!QJU_@d(\'=-us>] cAmc`nsEH(kY_*m/HfFRO!H`[HCR,?oh>;E6`o!G[Se<P(k2]TTdT0:U803<k:N7:3= OpSNqd\\V612ma8\\\"o=X42IqmY6C-UriBN4<IcU9^ VD6Sl4!QbB_03CZEJk%8$S/Ut%?.k.gKCil&5N6U`dE#.N-fZ8oDU:^ca\'bc0Xjfah6\\;j#R9`UdU3Mb_[P_^eas.%R>>o&K/hts7:Eb-SLfM7Q3QZN0l41(5HKf0R0WUt`MFZqB6[tcl-5jKl_^NOAEq.N]ik<V+70Ffb@7,U_>[\"7Zb^LUAIPkiYfneUfHOEE^[slP]TKQcY=/L;j&06:IR\"BjNjWn!cu/c>%Vc\\P#PZZL!XPVJL<rNP>2V::=G3RXRRX+Zm2E\\\\6rRlD=tUc6V;FLEV(Xk@la<&$[R!s#+pR1$Me7f\\^4isR*Iq210-2FbDKj[(mU>*d ^(h]2e#(#%4!u0j*mc A8a(C?1`.(!!kr-m<@:Fm6TqIMEcBlkD\"$u2_e:1LV^q>?pT+N1SjT?Wo:aiO+;`XY25qfu`-m=N^!dQt(8=h#TT>-oiQgN+UO+@36os`O:+9@/lJ?0.Qjpi+4giBG@YD%-B <T,qhfYpRq;\\l bAZ>$@FCn70^i?/8]\"pW^]nqSfh$!;!G`F2aCWWrQcg V71GXE^PJ-P cb5dDgY?b]:ma[i6iH6DuEGl\\pK><ip\\coegoE17QpX`&>?Tr7bYQ4*m/!U[eKD$j7*m`5AS/rd:*?%WK\\J61u&J#g0(as.pmLqe]rXTn//\'iQkiM<iFr>S0`j#_N>8kbk#(FG@+gt?4^id2d^\'!KIKL3pdK9~>endstream endobj 17 0 obj << /Filter [ /ASCII85Decode /FlateDecode ] /Length 330 >> stream Gat=fbA+pK&4Q?mMS&1aTmH@0U2Gk;a/[JjoE2p1;jUT]AZ,M*b\'$uf#`18<6?$T*\\H>2i@o<9g\'sP\'TH6a&WI0E1h@A!$sPE1BG3YaBCU_(n!_1YQ4c:\'b\\jfh=N\"9\"Y-nL>Fa&G:dXZTDN6`F.RC?@lt8_YNo,_63cX!H!g%W2ol?ZYgeT6W*]fcWu1/l%IC8?\"!+/(N(m6hdJ1MFK8N!&h7rH+j&VtnXg:QpCT39/@9!&Fc^>>8W(\'Sj[`;6\";9=-8TuCn@4%(lk*#.&P+g@#CSUlkQnY%p5&a;,^i>a5`eV7m&7\"\'9o1_?@ opensource', 'C', NULL, 'medium', 1.00, 0.00, NULL, 1, 1, '2026-08-18 16:06:59', '2026-08-18 16:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `father_name` varchar(200) DEFAULT NULL,
  `cnic_number` varchar(20) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `full_name`, `father_name`, `cnic_number`, `username`, `password_hash`, `email`, `phone`, `address`, `profile_picture`, `is_active`, `last_login`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 'STU0001', 'Abdul Aliyan', 'Muhammad Shahid', '4240192442567', 'stu-001', '$2y$12$NV.4mZ9dxRYGjWV3jTkBN.E07N5Z1K5La4ejm3bbwBsAMFyma/H8O', 'aliyanshahid439@gmail.com', '+923202043624', 'Orangi Town sector 11/E House no 344 Karachi Pakistan', '6a210192a4ada2f194ee37401138e70e_1787047808.jpeg', 1, '2026-08-19 13:59:47', '', 1, '2026-08-18 15:07:48', '2026-08-19 13:59:47'),
(6, 'STU0002', 'Muhammad Anas', 'Muhammad Shahid', '4240182552587', 'stu-002', '$2y$12$6avzvB7/7zw15wuIN3BG4OXqlEGFJRzTY7cBmS/h9yXWaChapg2JG', 'anas@gmail.com', '03181561925', 'house no 334 sector 11/e', 'b7cf93b3a5a5a1409e02f27d4f8a84be_1787129313.png', 1, '2026-08-19 13:49:52', '', 1, '2026-08-19 13:48:33', '2026-08-19 13:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `student_answers`
--

CREATE TABLE `student_answers` (
  `id` int(10) UNSIGNED NOT NULL,
  `attempt_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `exam_id` int(10) UNSIGNED NOT NULL,
  `question_id` int(10) UNSIGNED NOT NULL,
  `selected_option` enum('A','B','C','D') DEFAULT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `is_marked` tinyint(1) NOT NULL DEFAULT 0,
  `is_answered` tinyint(1) NOT NULL DEFAULT 0,
  `marks_awarded` decimal(5,2) NOT NULL DEFAULT 0.00,
  `negative_marks` decimal(5,2) NOT NULL DEFAULT 0.00,
  `answered_at` datetime DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_answers`
--

INSERT INTO `student_answers` (`id`, `attempt_id`, `student_id`, `exam_id`, `question_id`, `selected_option`, `correct_option`, `is_correct`, `is_marked`, `is_answered`, `marks_awarded`, `negative_marks`, `answered_at`, `sort_order`) VALUES
(11, 2, 4, 2, 13, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:15:07', 1),
(12, 2, 4, 2, 14, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:15:20', 2),
(13, 2, 4, 2, 15, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:15:39', 3),
(14, 2, 4, 2, 16, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:15:45', 4),
(15, 2, 4, 2, 17, 'B', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:16:07', 5),
(16, 2, 4, 2, 18, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:16:20', 6),
(17, 2, 4, 2, 19, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:16:25', 7),
(18, 2, 4, 2, 20, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:16:35', 8),
(19, 2, 4, 2, 21, 'B', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:16:44', 9),
(20, 2, 4, 2, 22, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:16:53', 10),
(21, 2, 4, 2, 23, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:17:02', 11),
(22, 2, 4, 2, 24, 'D', 'A', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:17:09', 12),
(23, 2, 4, 2, 25, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:17:18', 13),
(24, 2, 4, 2, 26, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:17:29', 14),
(25, 2, 4, 2, 27, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:17:38', 15),
(26, 2, 4, 2, 28, 'C', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:17:47', 16),
(27, 2, 4, 2, 29, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:17:54', 17),
(28, 2, 4, 2, 30, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:18:00', 18),
(29, 2, 4, 2, 31, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:18:07', 19),
(30, 2, 4, 2, 32, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:18:16', 20),
(31, 2, 4, 2, 33, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:18:37', 21),
(32, 2, 4, 2, 34, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:18:44', 22),
(33, 2, 4, 2, 35, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:18:50', 23),
(34, 2, 4, 2, 36, 'A', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:19:14', 24),
(35, 2, 4, 2, 37, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:21', 25),
(36, 2, 4, 2, 38, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:26', 26),
(37, 2, 4, 2, 39, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:31', 27),
(38, 2, 4, 2, 40, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:37', 28),
(39, 2, 4, 2, 41, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:44', 29),
(40, 2, 4, 2, 42, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:49', 30),
(41, 2, 4, 2, 43, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:19:58', 31),
(42, 2, 4, 2, 44, 'D', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:20:04', 32),
(43, 2, 4, 2, 45, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:10', 33),
(44, 2, 4, 2, 46, 'D', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-18 16:20:19', 34),
(45, 2, 4, 2, 47, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:24', 35),
(46, 2, 4, 2, 48, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:29', 36),
(47, 2, 4, 2, 49, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:43', 37),
(48, 2, 4, 2, 50, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:50', 38),
(49, 2, 4, 2, 51, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:20:54', 39),
(50, 2, 4, 2, 52, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:00', 40),
(51, 2, 4, 2, 53, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:07', 41),
(52, 2, 4, 2, 54, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:16', 42),
(53, 2, 4, 2, 55, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:21', 43),
(54, 2, 4, 2, 56, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:25', 44),
(55, 2, 4, 2, 57, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:30', 45),
(56, 2, 4, 2, 58, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:46', 46),
(57, 2, 4, 2, 59, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:21:52', 47),
(58, 2, 4, 2, 60, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:02', 48),
(59, 2, 4, 2, 61, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:12', 49),
(60, 2, 4, 2, 62, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:18', 50),
(61, 2, 4, 2, 63, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:23', 51),
(62, 2, 4, 2, 64, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:27', 52),
(63, 2, 4, 2, 65, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:39', 53),
(64, 2, 4, 2, 66, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:52', 54),
(65, 2, 4, 2, 67, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:22:58', 55),
(66, 2, 4, 2, 68, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:03', 56),
(67, 2, 4, 2, 69, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:07', 57),
(68, 2, 4, 2, 70, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:23', 58),
(69, 2, 4, 2, 71, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:27', 59),
(70, 2, 4, 2, 72, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:38', 60),
(71, 2, 4, 2, 73, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-18 16:23:54', 61),
(316, 7, 4, 2, 13, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:00:34', 1),
(317, 7, 4, 2, 14, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:00:40', 2),
(318, 7, 4, 2, 15, 'B', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:00:47', 3),
(319, 7, 4, 2, 16, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:00:51', 4),
(320, 7, 4, 2, 17, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:00:57', 5),
(321, 7, 4, 2, 18, 'D', 'A', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:01:18', 6),
(322, 7, 4, 2, 19, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:01:22', 7),
(323, 7, 4, 2, 20, 'B', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:01:34', 8),
(324, 7, 4, 2, 21, 'A', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:01:40', 9),
(325, 7, 4, 2, 22, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:01:45', 10),
(326, 7, 4, 2, 23, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:01:51', 11),
(327, 7, 4, 2, 24, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:01:55', 12),
(328, 7, 4, 2, 25, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:00', 13),
(329, 7, 4, 2, 26, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:07', 14),
(330, 7, 4, 2, 27, 'D', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:02:12', 15),
(331, 7, 4, 2, 28, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:02:18', 16),
(332, 7, 4, 2, 29, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:24', 17),
(333, 7, 4, 2, 30, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:32', 18),
(334, 7, 4, 2, 31, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:37', 19),
(335, 7, 4, 2, 32, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:02:43', 20),
(336, 7, 4, 2, 33, 'A', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:02:50', 21),
(337, 7, 4, 2, 34, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:02:55', 22),
(338, 7, 4, 2, 35, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:02', 23),
(339, 7, 4, 2, 36, 'B', 'C', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:03:15', 24),
(340, 7, 4, 2, 37, 'C', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:03:22', 25),
(341, 7, 4, 2, 38, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:27', 26),
(342, 7, 4, 2, 39, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:32', 27),
(343, 7, 4, 2, 40, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:38', 28),
(344, 7, 4, 2, 41, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:43', 29),
(345, 7, 4, 2, 42, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:03:47', 30),
(346, 7, 4, 2, 43, 'C', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:03:52', 31),
(347, 7, 4, 2, 44, 'D', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:03:59', 32),
(348, 7, 4, 2, 45, 'C', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:04:07', 33),
(349, 7, 4, 2, 46, 'D', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:04:14', 34),
(350, 7, 4, 2, 47, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:18', 35),
(351, 7, 4, 2, 48, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:22', 36),
(352, 7, 4, 2, 49, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:31', 37),
(353, 7, 4, 2, 50, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:35', 38),
(354, 7, 4, 2, 51, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:39', 39),
(355, 7, 4, 2, 52, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:42', 40),
(356, 7, 4, 2, 53, 'A', 'A', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:04:45', 41),
(357, 7, 4, 2, 54, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:00', 42),
(358, 7, 4, 2, 55, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:09', 43),
(359, 7, 4, 2, 56, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:18', 44),
(360, 7, 4, 2, 57, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:32', 45),
(361, 7, 4, 2, 58, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:36', 46),
(362, 7, 4, 2, 59, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:41', 47),
(363, 7, 4, 2, 60, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:47', 48),
(364, 7, 4, 2, 61, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:05:57', 49),
(365, 7, 4, 2, 62, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:03', 50),
(366, 7, 4, 2, 63, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:06', 51),
(367, 7, 4, 2, 64, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:11', 52),
(368, 7, 4, 2, 65, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:17', 53),
(369, 7, 4, 2, 66, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:24', 54),
(370, 7, 4, 2, 67, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:33', 55),
(371, 7, 4, 2, 68, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:41', 56),
(372, 7, 4, 2, 69, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:46', 57),
(373, 7, 4, 2, 70, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:51', 58),
(374, 7, 4, 2, 71, 'B', 'B', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:06:55', 59),
(375, 7, 4, 2, 72, 'C', 'B', 0, 1, 1, 0.00, 0.00, '2026-08-19 14:07:03', 60),
(376, 7, 4, 2, 73, 'C', 'C', 1, 1, 1, 2.00, 0.00, '2026-08-19 14:07:09', 61);

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `doc_type` enum('cnic_front','cnic_back','profile_picture') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `doc_type`, `file_path`, `original_name`, `file_size`, `mime_type`, `uploaded_at`, `uploaded_by`) VALUES
(1, 4, 'profile_picture', '6a210192a4ada2f194ee37401138e70e_1787047808.jpeg', 'profile_6a7b7d2c5a26d_aa55af74.jpeg', 181947, 'image/jpeg', '2026-08-18 15:10:08', 1),
(2, 6, 'profile_picture', 'b7cf93b3a5a5a1409e02f27d4f8a84be_1787129313.png', 'Screenshot 2026-07-25 170526.png', 836007, 'image/png', '2026-08-19 13:48:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `code`, `description`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Web Development', 'WD101', 'HTML, CSS, JavaScript and related technologies', 1, NULL, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(2, 'PHP Programming', 'PHP201', 'Server-side PHP programming', 1, NULL, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(3, 'MySQL Database', 'DB301', 'Database design and SQL queries', 1, NULL, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(4, 'JavaScript', 'JS401', 'Frontend JavaScript programming', 1, NULL, '2026-08-18 09:56:13', '2026-08-18 09:56:13'),
(5, 'MCQS Test', NULL, '', 1, 1, '2026-08-18 15:10:26', '2026-08-18 15:10:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_user` (`user_type`,`user_id`),
  ADD KEY `idx_log_created` (`created_at`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_admins_username` (`username`),
  ADD KEY `idx_admins_email` (`email`);

--
-- Indexes for table `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_chapters_subject` (`subject_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exam_code` (`exam_code`),
  ADD KEY `idx_exams_code` (`exam_code`),
  ADD KEY `idx_exams_status` (`status`),
  ADD KEY `idx_exams_subject` (`subject_id`);

--
-- Indexes for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempts_schedule` (`schedule_id`),
  ADD KEY `idx_attempts_student` (`student_id`),
  ADD KEY `idx_attempts_exam` (`exam_id`),
  ADD KEY `idx_attempts_status` (`status`);

--
-- Indexes for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_exam_question` (`exam_id`,`question_id`),
  ADD KEY `idx_eq_exam` (`exam_id`),
  ADD KEY `idx_eq_question` (`question_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `attempt_id` (`attempt_id`),
  ADD KEY `idx_results_student` (`student_id`),
  ADD KEY `idx_results_exam` (`exam_id`),
  ADD KEY `idx_results_result` (`result`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedules_student` (`student_id`),
  ADD KEY `idx_schedules_exam` (`exam_id`),
  ADD KEY `idx_schedules_date` (`scheduled_date`),
  ADD KEY `idx_schedules_status` (`status`);

--
-- Indexes for table `exam_violations`
--
ALTER TABLE `exam_violations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_violations_attempt` (`attempt_id`),
  ADD KEY `idx_violations_student` (`student_id`);

--
-- Indexes for table `pdf_imports`
--
ALTER TABLE `pdf_imports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_questions_subject` (`subject_id`),
  ADD KEY `idx_questions_chapter` (`chapter_id`),
  ADD KEY `idx_questions_difficulty` (`difficulty`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_students_student_id` (`student_id`),
  ADD KEY `idx_students_username` (`username`);

--
-- Indexes for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attempt_question` (`attempt_id`,`question_id`),
  ADD KEY `idx_sa_attempt` (`attempt_id`),
  ADD KEY `idx_sa_student` (`student_id`),
  ADD KEY `idx_sa_question` (`question_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_docs_student` (`student_id`),
  ADD KEY `idx_student_docs_type` (`doc_type`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_subjects_code` (`code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chapters`
--
ALTER TABLE `chapters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `exam_questions`
--
ALTER TABLE `exam_questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `exam_violations`
--
ALTER TABLE `exam_violations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `pdf_imports`
--
ALTER TABLE `pdf_imports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_answers`
--
ALTER TABLE `student_answers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=377;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `fk_chapters_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `fk_exams_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `exam_attempts`
--
ALTER TABLE `exam_attempts`
  ADD CONSTRAINT `fk_attempts_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempts_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempts_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_questions`
--
ALTER TABLE `exam_questions`
  ADD CONSTRAINT `fk_eq_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eq_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `fk_results_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_results_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `fk_schedules_exam` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_schedules_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_violations`
--
ALTER TABLE `exam_violations`
  ADD CONSTRAINT `fk_violations_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_violations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `fk_questions_chapter` FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_questions_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_answers`
--
ALTER TABLE `student_answers`
  ADD CONSTRAINT `fk_sa_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `fk_student_docs_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
