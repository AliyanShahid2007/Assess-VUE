# AssessVUE — Professional Online Examination Simulator

A complete, professional, secure, and responsive Online Examination Management System built with **PHP + MySQL + Bootstrap 5**.

---

## 🌐 Live Access

| Portal | URL | Credentials |
|---|---|---|
| **Admin Panel** | https://assess-vue.ct.ws/admin/login.php | `admin` / `Aliy@n123` |
| **Student Portal** | https://assess-vue.ct.ws/student/login.php | `stu-001` / `admin123` |

---

## ✅ Completed Features

### 🔐 Security
- PHP Session-based authentication with session regeneration
- BCrypt password hashing (cost=12) for both admin and students
- CSRF token protection on all forms
- PDO prepared statements (SQL injection prevention)
- Input sanitization and output encoding
- Secure file upload with MIME type validation
- Protected file serving (CNIC/documents not publicly accessible)
- Role-based access (Admin vs Student routes)
- Secure logout with remember-me token invalidation

### 👤 Admin Portal
- **Dashboard** — Statistics (students, exams, results, pass/fail counts) + quick actions
- **Student Management** — Full CRUD: add, edit, view, activate/deactivate, delete students
- **Student Profiles** — Profile picture, Father's name, CNIC/B-Form front & back documents
- **Secure Document Viewing** — Admin-only access to student photos and ID documents
- **PDF Import** — Upload PDF question papers, automatic text extraction, review & edit interface
- **Question Bank** — Search, filter by subject/chapter/difficulty, edit, delete
- **Question Review** — Edit every extracted question, set correct answers, set marks
- **Exam Creation** — Full configuration: name, code, marks, negative marking, duration, pass%
- **Exam Questions** — Link questions from bank to exams with ordering
- **Exam Scheduling** — Individual scheduling per student with date/time/duration
- **Schedule Management** — View/edit/cancel/delete schedules with filter tabs
- **Results Management** — Search/filter all results by student/exam/result
- **Attempt Details** — Question-by-question analysis with violation records
- **Reports** — Analytics, top performers, exam pass rates
- **Print Reports** — Professional A4 printable result certificates
- **Subjects & Chapters** — Organize question bank by subject/chapter

### 🎓 Student Portal
- **Secure Login** — Username or Student ID + password
- **Dashboard** — Profile picture, exam cards with real-time countdown timers
- **Exam Availability** — Server-side time validation (upcoming/available/expired/completed)
- **Rules & Regulations** — Must accept rules before starting
- **Exam Interface** — One question at a time, Pearson VUE-inspired design
- **Real-time Timer** — Server-controlled countdown, persists through refresh
- **Auto-Save Answers** — AJAX-based immediate answer saving to MySQL
- **Question Navigation** — Color-coded panel (answered/marked/unanswered/current)
- **Mark for Review** — Bookmark questions to revisit
- **Violation Monitoring** — Tab switch, window blur, visibility change detection
- **Auto-Termination** — Exceeded violation limit → automatic FAIL
- **Finish Confirmation** — Summary modal before submission
- **Auto-Submit** — When timer expires, exam auto-submits
- **Result Page** — Detailed results with performance analysis and chapter breakdown
- **Print Results** — Student can print their own result report

### ⚙️ Core Engine
- **Automatic Marking** — Correct marks, negative marks, zero for unanswered
- **Pass/Fail Calculation** — Configurable passing percentage
- **Timer Persistence** — Expected end time stored in DB, timer recalculated on refresh
- **Violation Tracking** — Type, count, timestamp stored per attempt
- **Performance Analytics** — Per-subject/chapter breakdown charts

---

## 📁 Project Structure

```
examapp/
├── index.php                     # Redirects to admin login
├── database.sql                  # Complete database schema + seed data
├── ecosystem.config.cjs          # PM2 config
├── config/
│   ├── config.php                # App configuration & constants
│   └── database.php              # PDO database class
├── includes/
│   └── functions.php             # All helper functions
├── admin/
│   ├── login.php                 # Admin login
│   ├── logout.php                # Admin logout
│   ├── dashboard.php             # Admin dashboard
│   ├── students.php              # Student list
│   ├── student_add.php           # Add student
│   ├── student_edit.php          # Edit student
│   ├── student_view.php          # Student profile + history
│   ├── serve_file.php            # Secure file serving
│   ├── exams.php                 # Exam list
│   ├── exam_add.php              # Create/edit exam
│   ├── exam_questions.php        # Link questions to exam
│   ├── questions.php             # Question bank
│   ├── question_add.php          # Add/edit question
│   ├── pdf_import.php            # PDF upload + extraction + review
│   ├── schedules.php             # Schedule list
│   ├── schedule_add.php          # Create/edit schedule
│   ├── results.php               # All results
│   ├── attempt_view.php          # Detailed attempt view
│   ├── print_report.php          # Printable A4 report
│   ├── reports.php               # Analytics
│   ├── subjects.php              # Subjects & chapters
│   └── includes/
│       ├── header.php            # Admin layout top
│       └── footer.php            # Admin layout bottom
├── student/
│   ├── login.php                 # Student login
│   ├── logout.php                # Student logout
│   ├── dashboard.php             # Student dashboard
│   ├── exam_start.php            # Rules acceptance
│   ├── exam.php                  # Live exam interface
│   └── result_view.php           # Result & print
├── assets/
│   ├── css/admin.css             # Admin styles
│   ├── css/student.css           # Student styles
│   └── js/admin.js               # Admin scripts
└── uploads/
    ├── .htaccess                 # Block direct access
    ├── profiles/                 # Student photos
    ├── cnic_front/               # CNIC front images
    ├── cnic_back/                # CNIC back images
    ├── pdfs/                     # Uploaded PDF papers
    └── temp/                     # Temp files
```

---

## 🗄️ Database Tables

| Table | Purpose |
|---|---|
| `admins` | Admin accounts |
| `students` | Student profiles |
| `student_documents` | CNIC/profile file records |
| `subjects` | Subject categories |
| `chapters` | Chapter sub-categories |
| `questions` | Question bank |
| `exams` | Exam configurations |
| `exam_questions` | Exam-question linking |
| `exam_schedules` | Per-student scheduling |
| `exam_attempts` | Live attempt tracking |
| `student_answers` | Individual answers per attempt |
| `exam_results` | Final calculated results |
| `exam_violations` | Rule violation log |
| `pdf_imports` | PDF upload tracking |
| `activity_log` | Admin/student activity |

---

## 🚀 Setup Instructions

### Prerequisites
- PHP 8.0+ (`php -S` built-in server or Apache/Nginx)
- MySQL 5.7+ or MariaDB 10.3+

### Installation Steps

1. **Clone/copy project** to your web root

2. **Create database & user:**
```sql
CREATE DATABASE exam_system;
CREATE USER 'examuser'@'localhost' IDENTIFIED BY 'ExamPass2024!';
GRANT ALL ON exam_system.* TO 'examuser'@'localhost';
```

3. **Import database schema:**
```bash
mysql -u examuser -p exam_system < database.sql
```

4. **Configure database** in `config/config.php` if needed

5. **Set upload directory permissions:**
```bash
chmod 755 uploads/
chmod 755 uploads/profiles/ uploads/cnic_front/ uploads/cnic_back/ uploads/pdfs/ uploads/temp/
```

6. **Start server** (development):
```bash
php -S 0.0.0.0:3000 -t /path/to/examapp
```
   OR with PM2:
```bash
pm2 start ecosystem.config.cjs
```

7. **Open browser:** `http://localhost:3000`

---

## 👥 Default Credentials

| Role | Username | Password |
|---|---|---|
| Admin | `admin` | `Admin@12345` |
| Sample Student | `student001` | `Student@123` |

**Change default passwords immediately in production!**

---

## 🔒 Security Notes

- All sensitive uploads are stored outside web root or behind PHP authentication
- CNIC/B-Form documents are never served directly — always through `serve_file.php`
- Prepared statements used throughout — no raw SQL string concatenation
- Session IDs regenerated on login
- CSRF tokens on all POST forms
- Password hashing with BCrypt cost=12

---

## 📊 Technology Stack

| Component | Technology |
|---|---|
| Backend | PHP 8.4 |
| Database | MariaDB / MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| UI Framework | Bootstrap 5.3 |
| Icons | Font Awesome 6.4 |
| Charts | Chart.js 4.4 |
| Server | PHP Built-in / Apache / Nginx |

---

## 📅 Status

- **Version**: 1.0.0
- **Status**: ✅ Active
- **Last Updated**: August 2026
