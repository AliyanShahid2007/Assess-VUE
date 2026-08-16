-- ============================================================
--  Professional Online Examination Simulator
--  Database Schema - Complete Setup
--  Engine: MariaDB / MySQL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- 1. DATABASE
-- ============================================================
CREATE DATABASE IF NOT EXISTS `exam_system`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `exam_system`;

-- ============================================================
-- 2. ADMINS
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(100)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `full_name`     VARCHAR(200)  NOT NULL,
  `email`         VARCHAR(200)  NOT NULL UNIQUE,
  `avatar`        VARCHAR(255)  DEFAULT NULL,
  `is_active`     TINYINT(1)    NOT NULL DEFAULT 1,
  `last_login`    DATETIME      DEFAULT NULL,
  `remember_token` VARCHAR(255) DEFAULT NULL,
  `token_expires` DATETIME      DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admins_username` (`username`),
  KEY `idx_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. SUBJECTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `code`        VARCHAR(50)  DEFAULT NULL UNIQUE,
  `description` TEXT         DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_subjects_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. CHAPTERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `chapters` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id`  INT UNSIGNED NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `sort_order`  INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chapters_subject` (`subject_id`),
  CONSTRAINT `fk_chapters_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. STUDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`    VARCHAR(20)  NOT NULL UNIQUE,
  `full_name`     VARCHAR(200) NOT NULL,
  `father_name`   VARCHAR(200) DEFAULT NULL,
  `cnic_number`   VARCHAR(20)  DEFAULT NULL,
  `username`      VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email`         VARCHAR(200) DEFAULT NULL,
  `phone`         VARCHAR(20)  DEFAULT NULL,
  `address`       TEXT         DEFAULT NULL,
  `profile_picture` VARCHAR(255) DEFAULT NULL,
  `is_active`     TINYINT(1)  NOT NULL DEFAULT 1,
  `last_login`    DATETIME    DEFAULT NULL,
  `notes`         TEXT        DEFAULT NULL,
  `created_by`    INT UNSIGNED DEFAULT NULL,
  `created_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_students_student_id` (`student_id`),
  KEY `idx_students_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. STUDENT DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`   INT UNSIGNED NOT NULL,
  `doc_type`     ENUM('cnic_front','cnic_back','profile_picture') NOT NULL,
  `file_path`    VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) DEFAULT NULL,
  `file_size`    INT UNSIGNED DEFAULT NULL,
  `mime_type`    VARCHAR(100) DEFAULT NULL,
  `uploaded_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by`  INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_student_docs_student` (`student_id`),
  KEY `idx_student_docs_type` (`doc_type`),
  CONSTRAINT `fk_student_docs_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. QUESTION BANK
-- ============================================================
CREATE TABLE IF NOT EXISTS `questions` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id`      INT UNSIGNED DEFAULT NULL,
  `chapter_id`      INT UNSIGNED DEFAULT NULL,
  `question_text`   TEXT         NOT NULL,
  `option_a`        TEXT         NOT NULL,
  `option_b`        TEXT         NOT NULL,
  `option_c`        TEXT         NOT NULL,
  `option_d`        TEXT         NOT NULL,
  `correct_option`  ENUM('A','B','C','D') NOT NULL,
  `explanation`     TEXT         DEFAULT NULL,
  `difficulty`      ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  `marks`           DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `source_pdf`      VARCHAR(255) DEFAULT NULL,
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_questions_subject` (`subject_id`),
  KEY `idx_questions_chapter` (`chapter_id`),
  KEY `idx_questions_difficulty` (`difficulty`),
  CONSTRAINT `fk_questions_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_questions_chapter`
    FOREIGN KEY (`chapter_id`) REFERENCES `chapters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. EXAMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `exams` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_name`           VARCHAR(300) NOT NULL,
  `exam_code`           VARCHAR(50)  NOT NULL UNIQUE,
  `description`         TEXT         DEFAULT NULL,
  `subject_id`          INT UNSIGNED DEFAULT NULL,
  `total_questions`     INT UNSIGNED NOT NULL DEFAULT 0,
  `marks_per_question`  DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `total_marks`         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `passing_percentage`  DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `duration_minutes`    INT UNSIGNED NOT NULL DEFAULT 60,
  `max_violations`      INT UNSIGNED NOT NULL DEFAULT 3,
  `shuffle_questions`   TINYINT(1)   NOT NULL DEFAULT 0,
  `shuffle_options`     TINYINT(1)   NOT NULL DEFAULT 0,
  `show_result_immediately` TINYINT(1) NOT NULL DEFAULT 1,
  `instructions`        TEXT         DEFAULT NULL,
  `status`              ENUM('draft','active','archived') NOT NULL DEFAULT 'draft',
  `created_by`          INT UNSIGNED DEFAULT NULL,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exams_code` (`exam_code`),
  KEY `idx_exams_status` (`status`),
  KEY `idx_exams_subject` (`subject_id`),
  CONSTRAINT `fk_exams_subject`
    FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. EXAM QUESTIONS (linking exams to questions)
-- ============================================================
CREATE TABLE IF NOT EXISTS `exam_questions` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_id`        INT UNSIGNED NOT NULL,
  `question_id`    INT UNSIGNED NOT NULL,
  `sort_order`     INT          NOT NULL DEFAULT 0,
  `marks`          DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  `negative_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_exam_question` (`exam_id`, `question_id`),
  KEY `idx_eq_exam` (`exam_id`),
  KEY `idx_eq_question` (`question_id`),
  CONSTRAINT `fk_eq_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eq_question`
    FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. EXAM SCHEDULES
-- ============================================================
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id`      INT UNSIGNED NOT NULL,
  `exam_id`         INT UNSIGNED NOT NULL,
  `scheduled_date`  DATE         NOT NULL,
  `start_time`      TIME         NOT NULL,
  `end_time`        TIME         DEFAULT NULL,
  `duration_minutes` INT UNSIGNED NOT NULL,
  `status`          ENUM('scheduled','active','completed','cancelled','missed') NOT NULL DEFAULT 'scheduled',
  `attempt_allowed` TINYINT(1)   NOT NULL DEFAULT 1,
  `notes`           TEXT         DEFAULT NULL,
  `created_by`      INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_schedules_student` (`student_id`),
  KEY `idx_schedules_exam` (`exam_id`),
  KEY `idx_schedules_date` (`scheduled_date`),
  KEY `idx_schedules_status` (`status`),
  CONSTRAINT `fk_schedules_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. EXAM ATTEMPTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `exam_attempts` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id`     INT UNSIGNED NOT NULL,
  `student_id`      INT UNSIGNED NOT NULL,
  `exam_id`         INT UNSIGNED NOT NULL,
  `start_time`      DATETIME     DEFAULT NULL,
  `end_time`        DATETIME     DEFAULT NULL,
  `expected_end_time` DATETIME   DEFAULT NULL,
  `time_taken_seconds` INT UNSIGNED DEFAULT NULL,
  `status`          ENUM('in_progress','completed','terminated','abandoned') NOT NULL DEFAULT 'in_progress',
  `termination_reason` VARCHAR(255) DEFAULT NULL,
  `current_question` INT UNSIGNED NOT NULL DEFAULT 1,
  `ip_address`      VARCHAR(45)  DEFAULT NULL,
  `browser_info`    TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_schedule` (`schedule_id`),
  KEY `idx_attempts_student` (`student_id`),
  KEY `idx_attempts_exam` (`exam_id`),
  KEY `idx_attempts_status` (`status`),
  CONSTRAINT `fk_attempts_schedule`
    FOREIGN KEY (`schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. STUDENT ANSWERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `student_answers` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id`      INT UNSIGNED NOT NULL,
  `student_id`      INT UNSIGNED NOT NULL,
  `exam_id`         INT UNSIGNED NOT NULL,
  `question_id`     INT UNSIGNED NOT NULL,
  `selected_option` ENUM('A','B','C','D') DEFAULT NULL,
  `correct_option`  ENUM('A','B','C','D') NOT NULL,
  `is_correct`      TINYINT(1)   DEFAULT NULL,
  `is_marked`       TINYINT(1)   NOT NULL DEFAULT 0,
  `is_answered`     TINYINT(1)   NOT NULL DEFAULT 0,
  `marks_awarded`   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `negative_marks`  DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `answered_at`     DATETIME     DEFAULT NULL,
  `sort_order`      INT          NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attempt_question` (`attempt_id`, `question_id`),
  KEY `idx_sa_attempt` (`attempt_id`),
  KEY `idx_sa_student` (`student_id`),
  KEY `idx_sa_question` (`question_id`),
  CONSTRAINT `fk_sa_attempt`
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sa_question`
    FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. EXAM RESULTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `exam_results` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id`          INT UNSIGNED NOT NULL UNIQUE,
  `student_id`          INT UNSIGNED NOT NULL,
  `exam_id`             INT UNSIGNED NOT NULL,
  `schedule_id`         INT UNSIGNED NOT NULL,
  `total_questions`     INT UNSIGNED NOT NULL DEFAULT 0,
  `attempted_questions` INT UNSIGNED NOT NULL DEFAULT 0,
  `correct_answers`     INT UNSIGNED NOT NULL DEFAULT 0,
  `incorrect_answers`   INT UNSIGNED NOT NULL DEFAULT 0,
  `unanswered`          INT UNSIGNED NOT NULL DEFAULT 0,
  `total_marks`         DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `obtained_marks`      DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `negative_marks_total` DECIMAL(8,2) NOT NULL DEFAULT 0.00,
  `percentage`          DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `passing_percentage`  DECIMAL(5,2) NOT NULL DEFAULT 60.00,
  `result`              ENUM('PASS','FAIL') NOT NULL DEFAULT 'FAIL',
  `violation_terminated` TINYINT(1)  NOT NULL DEFAULT 0,
  `time_taken_seconds`  INT UNSIGNED DEFAULT NULL,
  `calculated_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_results_student` (`student_id`),
  KEY `idx_results_exam` (`exam_id`),
  KEY `idx_results_result` (`result`),
  CONSTRAINT `fk_results_attempt`
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_results_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_results_exam`
    FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. EXAM VIOLATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `exam_violations` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `attempt_id`      INT UNSIGNED NOT NULL,
  `student_id`      INT UNSIGNED NOT NULL,
  `exam_id`         INT UNSIGNED NOT NULL,
  `violation_type`  VARCHAR(100) NOT NULL,
  `description`     TEXT         DEFAULT NULL,
  `violation_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `violated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_violations_attempt` (`attempt_id`),
  KEY `idx_violations_student` (`student_id`),
  CONSTRAINT `fk_violations_attempt`
    FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_violations_student`
    FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. PDF IMPORTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `pdf_imports` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `original_name`   VARCHAR(255) NOT NULL,
  `stored_name`     VARCHAR(255) NOT NULL,
  `file_size`       INT UNSIGNED DEFAULT NULL,
  `total_extracted` INT UNSIGNED NOT NULL DEFAULT 0,
  `status`          ENUM('pending','processed','published','failed') NOT NULL DEFAULT 'pending',
  `subject_id`      INT UNSIGNED DEFAULT NULL,
  `chapter_id`      INT UNSIGNED DEFAULT NULL,
  `imported_by`     INT UNSIGNED DEFAULT NULL,
  `imported_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. ACTIVITY LOG
-- ============================================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_type`   ENUM('admin','student') NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `action`      VARCHAR(255) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_type`, `user_id`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin (password: Admin@12345)
INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`) VALUES
('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@examportal.com');

-- Sample subjects
INSERT INTO `subjects` (`name`, `code`, `description`) VALUES
('Web Development', 'WD101', 'HTML, CSS, JavaScript and related technologies'),
('PHP Programming', 'PHP201', 'Server-side PHP programming'),
('MySQL Database', 'DB301', 'Database design and SQL queries'),
('JavaScript', 'JS401', 'Frontend JavaScript programming');

-- Sample chapters
INSERT INTO `chapters` (`subject_id`, `name`, `sort_order`) VALUES
(1, 'HTML Fundamentals', 1),
(1, 'CSS Styling', 2),
(1, 'JavaScript Basics', 3),
(2, 'PHP Syntax', 1),
(2, 'PHP Functions', 2),
(3, 'SQL Queries', 1),
(3, 'Database Design', 2);
