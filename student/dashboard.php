<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireStudent();

$studentId = $_SESSION['student_id'];
$student   = db()->fetchOne("SELECT * FROM students WHERE id=?", [$studentId]);
if (!$student || !$student['is_active']) {
    session_destroy();
    redirect('login.php');
}

// Current PKT time
$now    = date('Y-m-d H:i:s');
$nowTs  = time();

// ── All assigned schedules ────────────────────────────────────
$schedules = db()->fetchAll("
    SELECT es.*, e.exam_name, e.exam_code, e.duration_minutes, e.total_questions,
           e.passing_percentage, e.total_marks,
           er.result, er.percentage, er.id as result_id,
           ea.id as attempt_id, ea.status as attempt_status
    FROM exam_schedules es
    JOIN exams e ON e.id = es.exam_id
    LEFT JOIN exam_attempts ea ON ea.schedule_id = es.id AND ea.student_id = ?
    LEFT JOIN exam_results  er ON er.attempt_id  = ea.id
    WHERE es.student_id = ?
    ORDER BY es.scheduled_date ASC, es.start_time ASC",
    [$studentId, $studentId]
);

// Determine status + countdown for each schedule
$liveExams     = [];
$upcomingExams = [];
$pastExams     = [];

foreach ($schedules as &$s) {
    $schedDT = $s['scheduled_date'] . ' ' . $s['start_time'];
    $endDT   = $s['scheduled_date'] . ' ' . (
        $s['end_time'] ?? date('H:i:s', strtotime($s['start_time']) + $s['duration_minutes'] * 60)
    );
    $startTs = strtotime($schedDT);
    $endTs   = strtotime($endDT);

    // Friendly date pieces (PKT)
    $s['fmt_date']     = date('d M Y', $startTs);          // 25 Aug 2025
    $s['fmt_day']      = date('l', $startTs);              // Monday
    $s['fmt_time']     = date('h:i A', $startTs);          // 02:30 PM
    $s['fmt_datetime'] = date('D, d M Y • h:i A', $startTs); // Mon, 25 Aug 2025 • 02:30 PM

    if ($s['attempt_status'] === 'completed' || $s['result_id']) {
        $s['avail_status'] = 'completed';
        $pastExams[] = &$s;
    } elseif ($s['attempt_status'] === 'in_progress') {
        $s['avail_status']        = 'in_progress';
        $s['countdown_seconds']   = max(0, $endTs - $nowTs);
        $liveExams[] = &$s;
    } elseif ($s['status'] === 'cancelled') {
        $s['avail_status'] = 'cancelled';
        $pastExams[] = &$s;
    } elseif ($nowTs < $startTs) {
        $s['avail_status']      = 'upcoming';
        $s['countdown_seconds'] = $startTs - $nowTs;
        $upcomingExams[] = &$s;
    } elseif ($nowTs >= $startTs && $nowTs <= $endTs + 300 && $s['attempt_allowed']) {
        $s['avail_status'] = 'available';
        $liveExams[] = &$s;
    } else {
        $s['avail_status'] = 'expired';
        if ($s['status'] === 'scheduled') {
            db()->execute("UPDATE exam_schedules SET status='missed' WHERE id=?", [$s['id']]);
        }
        $pastExams[] = &$s;
    }
}
unset($s);

// ── Recent results (last 10) ──────────────────────────────────
$results = db()->fetchAll("
    SELECT er.*, e.exam_name, ea.start_time, ea.end_time, es.scheduled_date, es.start_time as sched_time
    FROM exam_results er
    JOIN exams         e  ON e.id  = er.exam_id
    JOIN exam_attempts ea ON ea.id = er.attempt_id
    JOIN exam_schedules es ON es.id = er.schedule_id
    WHERE er.student_id = ?
    ORDER BY er.calculated_at DESC
    LIMIT 10",
    [$studentId]
);

// Stats
$totalAttempts  = count($results);
$passCount      = count(array_filter($results, fn($r) => $r['result'] === 'PASS'));
$avgPct         = $totalAttempts ? round(array_sum(array_column($results, 'percentage')) / $totalAttempts, 1) : 0;
$bestPct        = $totalAttempts ? max(array_column($results, 'percentage')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
    <style>
    /* ── Modern Dashboard Overrides ─────────────────────── */
    :root {
        --primary:    #1a237e;
        --accent:     #283593;
        --success:    #2e7d32;
        --warning:    #f57f17;
        --danger:     #c62828;
        --grad-main:  linear-gradient(135deg, #1a237e 0%, #283593 50%, #1565c0 100%);
        --grad-live:  linear-gradient(135deg, #1b5e20 0%, #388e3c 100%);
        --grad-up:    linear-gradient(135deg, #e65100 0%, #f57c00 100%);
        --card-shadow: 0 4px 24px rgba(0,0,0,.10);
    }

    body { background: #f0f2f8; }

    /* ── Navbar ────────────────────────────────────────── */
    .av-navbar {
        background: var(--grad-main);
        padding: 0 1.5rem;
        height: 64px;
        display: flex;
        align-items: center;
        box-shadow: 0 3px 16px rgba(26,35,126,.4);
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .av-brand {
        display: flex;
        align-items: center;
        gap: .75rem;
        text-decoration: none;
    }
    .av-brand-icon {
        width: 40px; height: 40px;
        background: rgba(255,255,255,.15);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem; color: #fff;
    }
    .av-brand-text { font-size: 1.25rem; font-weight: 800; color: #fff; letter-spacing: -.3px; }
    .av-brand-text span { color: #90caf9; }

    .av-nav-right { margin-left: auto; display: flex; align-items: center; gap: 1rem; }
    .av-datetime { font-size: .8rem; color: rgba(255,255,255,.75); text-align: right; line-height: 1.3; }
    .av-datetime strong { display: block; font-size: .9rem; color: #fff; }

    .av-user-chip {
        display: flex; align-items: center; gap: .6rem;
        background: rgba(255,255,255,.12);
        border-radius: 50px;
        padding: .35rem .75rem .35rem .4rem;
        cursor: default;
    }
    .av-user-chip img, .av-user-avatar {
        width: 34px; height: 34px; border-radius: 50%; object-fit: cover;
        border: 2px solid rgba(255,255,255,.4);
    }
    .av-user-avatar {
        background: rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; color: #fff; font-size: .9rem;
    }
    .av-user-name { font-size: .85rem; font-weight: 600; color: #fff; }
    .av-user-id   { font-size: .72rem; color: rgba(255,255,255,.65); }
    .av-logout-btn {
        width: 36px; height: 36px; border-radius: 50%;
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
        color: #fff; display: flex; align-items: center; justify-content: center;
        text-decoration: none; transition: background .2s;
    }
    .av-logout-btn:hover { background: rgba(255,255,255,.25); color: #fff; }

    /* ── Hero welcome strip ─────────────────────────────── */
    .av-hero {
        background: var(--grad-main);
        padding: 2rem 1.5rem 3.5rem;
        margin-bottom: -2rem;
    }
    .av-hero h2 { font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: .25rem; }
    .av-hero p  { color: rgba(255,255,255,.75); margin: 0; font-size: .9rem; }

    /* ── Stats strip ────────────────────────────────────── */
    .av-stats-row { margin-bottom: 1.75rem; }
    .av-stat-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        border: none;
    }
    .av-stat-icon {
        width: 52px; height: 52px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .av-stat-val  { font-size: 1.75rem; font-weight: 800; line-height: 1; color: #1a237e; }
    .av-stat-lbl  { font-size: .78rem; color: #78909c; font-weight: 500; margin-top: .2rem; }

    /* ── Section headers ─────────────────────────────────── */
    .av-section-header {
        display: flex; align-items: center; gap: .6rem;
        font-size: 1rem; font-weight: 700; color: #1a237e;
        margin-bottom: 1rem;
    }
    .av-section-header .av-section-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: .95rem; flex-shrink: 0;
    }
    .av-badge-count {
        margin-left: auto;
        background: #e8eaf6; color: #3949ab;
        font-size: .75rem; font-weight: 700;
        padding: .2em .7em; border-radius: 20px;
    }

    /* ── Live exam card ──────────────────────────────────── */
    .av-live-card {
        background: var(--grad-live);
        border-radius: 20px;
        padding: 1.5rem;
        color: #fff;
        box-shadow: 0 6px 24px rgba(27,94,32,.35);
        position: relative;
        overflow: hidden;
        transition: transform .2s;
    }
    .av-live-card:hover { transform: translateY(-2px); }
    .av-live-card::before {
        content: ''; position: absolute; top: -30px; right: -30px;
        width: 130px; height: 130px; border-radius: 50%;
        background: rgba(255,255,255,.08);
    }
    .av-live-pulse {
        display: inline-flex; align-items: center; gap: .4rem;
        background: rgba(255,255,255,.2); border-radius: 20px;
        padding: .25rem .75rem; font-size: .75rem; font-weight: 700;
        margin-bottom: .75rem;
    }
    .av-live-dot {
        width: 8px; height: 8px; border-radius: 50%; background: #a5d6a7;
        animation: livePulse 1.2s ease-in-out infinite;
    }
    @keyframes livePulse {
        0%,100% { opacity:1; transform:scale(1);   }
        50%      { opacity:.4; transform:scale(1.5); }
    }
    .av-live-card h5 { font-size: 1.15rem; font-weight: 700; margin-bottom: .35rem; }
    .av-live-meta   { font-size: .82rem; opacity: .85; margin-bottom: 1rem; }
    .av-live-card .btn-start {
        background: #fff; color: #1b5e20;
        font-weight: 700; border-radius: 10px;
        padding: .55rem 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,.15);
        text-decoration: none; display: inline-block;
        transition: transform .15s, box-shadow .15s;
    }
    .av-live-card .btn-start:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 16px rgba(0,0,0,.2);
    }
    .av-live-card .btn-continue {
        background: rgba(255,255,255,.2);
        border: 2px solid rgba(255,255,255,.4);
        color: #fff; font-weight: 700; border-radius: 10px;
        padding: .55rem 1.5rem; text-decoration: none; display: inline-block;
        transition: background .15s;
    }
    .av-live-card .btn-continue:hover { background: rgba(255,255,255,.35); color: #fff; }

    /* ── Upcoming exam card ──────────────────────────────── */
    .av-upcoming-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: var(--card-shadow);
        border-left: 5px solid #f57c00;
        padding: 1.25rem 1.4rem;
        transition: transform .2s;
        height: 100%;
    }
    .av-upcoming-card:hover { transform: translateY(-2px); }
    .av-upcoming-card h6 { font-size: .95rem; font-weight: 700; color: #263238; margin-bottom: .5rem; }
    .av-up-datetime {
        display: flex; align-items: center; gap: .4rem;
        font-size: .82rem; color: #e65100; font-weight: 600;
        background: #fff8e1; padding: .4rem .75rem;
        border-radius: 8px; margin-bottom: .75rem;
    }
    .av-up-meta { font-size: .79rem; color: #607d8b; display: flex; flex-wrap: wrap; gap: .5rem; }
    .av-up-meta span {
        background: #f5f5f5; padding: .2rem .6rem;
        border-radius: 6px; display: flex; align-items: center; gap: .3rem;
    }
    .av-countdown-box {
        margin-top: .85rem; background: #fff3e0;
        border: 1px solid #ffe082; border-radius: 10px;
        padding: .5rem .8rem; font-size: .82rem;
        color: #bf360c; display: flex; align-items: center; justify-content: space-between;
    }
    .av-countdown-timer { font-weight: 800; font-family: monospace; font-size: .95rem; }

    /* ── Empty state ─────────────────────────────────────── */
    .av-empty {
        text-align: center; padding: 2.5rem 1rem;
        background: #fff; border-radius: 16px;
        box-shadow: var(--card-shadow);
    }
    .av-empty-icon { font-size: 2.5rem; color: #b0bec5; margin-bottom: .75rem; }
    .av-empty p    { color: #90a4ae; font-size: .9rem; margin: 0; }

    /* ── Recent Results table ─────────────────────────────── */
    .av-results-card {
        background: #fff; border-radius: 16px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
    }
    .av-results-card .card-header {
        background: #fff; border-bottom: 1px solid #eff0f6;
        padding: 1.1rem 1.5rem;
    }
    .av-results-table th {
        background: #f8f9fe; font-size: .78rem; font-weight: 700;
        color: #78909c; text-transform: uppercase; letter-spacing: .5px;
        padding: .75rem 1rem; border-bottom: 2px solid #eff0f6;
    }
    .av-results-table td {
        padding: .85rem 1rem; font-size: .875rem;
        border-bottom: 1px solid #f5f5f5; vertical-align: middle;
    }
    .av-results-table tbody tr:hover { background: #fafbff; }
    .av-results-table tbody tr:last-child td { border-bottom: none; }

    .av-exam-name { font-weight: 600; color: #263238; }
    .av-exam-date {
        font-size: .8rem; color: #607d8b; margin-top: .15rem;
        display: flex; align-items: center; gap: .3rem;
    }

    .av-result-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        font-size: .8rem; font-weight: 700; padding: .3rem .75rem; border-radius: 20px;
    }
    .av-pass { background: #e8f5e9; color: #1b5e20; }
    .av-fail { background: #ffebee; color: #b71c1c; }

    .av-score-bar-wrap { display: flex; align-items: center; gap: .6rem; }
    .av-score-bar-bg {
        flex: 1; height: 6px; background: #eceff1;
        border-radius: 3px; overflow: hidden; min-width: 60px;
    }
    .av-score-bar-fill { height: 100%; border-radius: 3px; transition: width .4s; }
    .av-score-text { font-size: .82rem; font-weight: 700; white-space: nowrap; }

    /* ── Profile sidebar ─────────────────────────────────── */
    .av-profile-card {
        background: #fff; border-radius: 16px;
        box-shadow: var(--card-shadow); padding: 1.5rem;
        text-align: center;
    }
    .av-profile-pic {
        width: 80px; height: 80px; border-radius: 50%;
        object-fit: cover; border: 3px solid #3949ab;
        margin-bottom: .75rem;
    }
    .av-profile-avatar {
        width: 80px; height: 80px; border-radius: 50%;
        background: linear-gradient(135deg, #3949ab, #7986cb);
        color: #fff; font-size: 2rem; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto .75rem;
    }
    .av-profile-name { font-size: 1rem; font-weight: 700; color: #263238; margin-bottom: .15rem; }
    .av-profile-id   {
        display: inline-block; background: #e8eaf6; color: #3949ab;
        font-size: .78rem; font-weight: 700; padding: .25rem .75rem;
        border-radius: 20px; margin-bottom: 1rem;
    }
    .av-profile-stat {
        display: flex; justify-content: space-between; align-items: center;
        font-size: .82rem; color: #607d8b; padding: .4rem 0;
        border-bottom: 1px solid #f5f5f5;
    }
    .av-profile-stat:last-child { border-bottom: none; }
    .av-profile-stat strong { color: #263238; font-weight: 700; }

    /* ── Responsive ──────────────────────────────────────── */
    @media (max-width: 576px) {
        .av-hero   { padding: 1.25rem 1rem 3rem; }
        .av-hero h2 { font-size: 1.2rem; }
        .av-datetime { display: none; }
        .av-results-table th, .av-results-table td { padding: .6rem .75rem; }
    }
    </style>
</head>
<body>

<!-- ═══════════════════════ NAVBAR ═══════════════════════ -->
<nav class="av-navbar">
    <a class="av-brand" href="dashboard.php">
        <div class="av-brand-icon"><i class="fas fa-graduation-cap"></i></div>
        <div class="av-brand-text">Assess<span>VUE</span></div>
    </a>
    <div class="av-nav-right">
        <div class="av-datetime">
            <strong id="navClock">--:-- --</strong>
            <span id="navDate"><?= date('D, d M Y') ?></span> <small>PKT</small>
        </div>
        <div class="av-user-chip">
            <?php if ($student['profile_picture']): ?>
                <img src="../admin/serve_file.php?type=profile&id=<?= $studentId ?>"
                     alt="<?= sanitize($student['full_name']) ?>">
            <?php else: ?>
                <div class="av-user-avatar"><?= strtoupper(substr($student['full_name'],0,1)) ?></div>
            <?php endif; ?>
            <div>
                <div class="av-user-name"><?= sanitize($student['full_name']) ?></div>
                <div class="av-user-id"><?= sanitize($student['student_id']) ?></div>
            </div>
        </div>
        <a href="logout.php" class="av-logout-btn" title="Sign Out">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<!-- ═══════════════════════ HERO ═══════════════════════ -->
<div class="av-hero">
    <div class="container-fluid px-3">
        <?= renderFlash() ?>
        <h2>Welcome back, <?= sanitize(explode(' ', $student['full_name'])[0]) ?>! 👋</h2>
        <p>
            <i class="fas fa-map-marker-alt me-1"></i>Pakistani Standard Time (PKT) &nbsp;|&nbsp;
            <i class="fas fa-calendar-alt me-1"></i><?= date('l, d F Y') ?>
        </p>
    </div>
</div>

<!-- ═══════════════════════ MAIN CONTENT ═══════════════════════ -->
<div class="container-fluid px-3 pb-5">

    <!-- Stats Strip -->
    <div class="row g-3 av-stats-row">
        <div class="col-6 col-md-3">
            <div class="av-stat-card">
                <div class="av-stat-icon" style="background:#e8eaf6;">
                    <i class="fas fa-clipboard-list" style="color:#3949ab;"></i>
                </div>
                <div>
                    <div class="av-stat-val"><?= count($schedules) ?></div>
                    <div class="av-stat-lbl">Total Assigned</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="av-stat-card">
                <div class="av-stat-icon" style="background:#e8f5e9;">
                    <i class="fas fa-check-circle" style="color:#2e7d32;"></i>
                </div>
                <div>
                    <div class="av-stat-val" style="color:#2e7d32;"><?= $passCount ?></div>
                    <div class="av-stat-lbl">Passed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="av-stat-card">
                <div class="av-stat-icon" style="background:#fff8e1;">
                    <i class="fas fa-chart-line" style="color:#f57f17;"></i>
                </div>
                <div>
                    <div class="av-stat-val" style="color:#f57f17;"><?= $avgPct ?>%</div>
                    <div class="av-stat-lbl">Avg Score</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="av-stat-card">
                <div class="av-stat-icon" style="background:#fce4ec;">
                    <i class="fas fa-trophy" style="color:#c2185b;"></i>
                </div>
                <div>
                    <div class="av-stat-val" style="color:#c2185b;"><?= number_format($bestPct, 1) ?>%</div>
                    <div class="av-stat-lbl">Best Score</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ── LEFT COLUMN ─────────────────────────────────── -->
        <div class="col-lg-8">

            <!-- ▶ LIVE / AVAILABLE EXAMS -->
            <?php if ($liveExams): ?>
            <div class="mb-4">
                <div class="av-section-header">
                    <div class="av-section-icon" style="background:#e8f5e9;">
                        <i class="fas fa-play-circle" style="color:#2e7d32;"></i>
                    </div>
                    Live &amp; Available Exams
                    <span class="av-badge-count"><?= count($liveExams) ?></span>
                </div>
                <div class="row g-3">
                <?php foreach ($liveExams as $s): ?>
                <div class="col-12 col-sm-6">
                    <div class="av-live-card">
                        <div class="av-live-pulse">
                            <div class="av-live-dot"></div>
                            <?= $s['avail_status'] === 'in_progress' ? 'IN PROGRESS' : 'AVAILABLE NOW' ?>
                        </div>
                        <h5><?= sanitize($s['exam_name']) ?></h5>
                        <div class="av-live-meta">
                            <i class="fas fa-calendar-day me-1"></i><?= $s['fmt_day'] ?>, <?= $s['fmt_date'] ?>
                            &nbsp;·&nbsp;
                            <i class="fas fa-clock me-1"></i><?= $s['fmt_time'] ?> (PKT)
                        </div>
                        <div class="d-flex gap-2 flex-wrap" style="font-size:.8rem; margin-bottom:1rem; opacity:.85;">
                            <span><i class="fas fa-question-circle me-1"></i><?= $s['total_questions'] ?> Questions</span>
                            <span>·</span>
                            <span><i class="fas fa-hourglass me-1"></i><?= $s['duration_minutes'] ?> min</span>
                            <span>·</span>
                            <span>Pass: <?= $s['passing_percentage'] ?>%</span>
                        </div>
                        <?php if ($s['avail_status'] === 'in_progress'): ?>
                        <a href="exam_start.php?schedule_id=<?= $s['id'] ?>" class="btn-continue">
                            <i class="fas fa-arrow-right me-1"></i>Continue Exam
                        </a>
                        <?php else: ?>
                        <a href="exam_start.php?schedule_id=<?= $s['id'] ?>" class="btn-start">
                            <i class="fas fa-play me-1"></i>Start Examination
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ⏰ UPCOMING EXAMS -->
            <div class="mb-4">
                <div class="av-section-header">
                    <div class="av-section-icon" style="background:#fff3e0;">
                        <i class="fas fa-calendar-alt" style="color:#e65100;"></i>
                    </div>
                    Upcoming Exams
                    <span class="av-badge-count"><?= count($upcomingExams) ?></span>
                </div>
                <?php if ($upcomingExams): ?>
                <div class="row g-3">
                <?php foreach ($upcomingExams as $s): ?>
                <div class="col-12 col-sm-6 col-xl-4">
                    <div class="av-upcoming-card">
                        <h6><?= sanitize($s['exam_name']) ?></h6>
                        <div class="av-up-datetime">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <div><?= $s['fmt_day'] ?>, <?= $s['fmt_date'] ?></div>
                                <div style="font-size:.75rem;"><?= $s['fmt_time'] ?> (PKT)</div>
                            </div>
                        </div>
                        <div class="av-up-meta">
                            <span><i class="fas fa-question-circle"></i><?= $s['total_questions'] ?> Qs</span>
                            <span><i class="fas fa-hourglass"></i><?= $s['duration_minutes'] ?> min</span>
                            <span><i class="fas fa-percent"></i>Pass <?= $s['passing_percentage'] ?>%</span>
                        </div>
                        <div class="av-countdown-box" data-seconds="<?= $s['countdown_seconds'] ?? 0 ?>">
                            <span><i class="fas fa-hourglass-start me-1"></i>Starts in:</span>
                            <span class="av-countdown-timer">--:--:--</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="av-empty">
                    <div class="av-empty-icon"><i class="fas fa-calendar-times"></i></div>
                    <p>No upcoming exams scheduled.<br>Your administrator will assign examinations.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 📋 RECENT EXAM HISTORY -->
            <div class="mb-4">
                <div class="av-section-header">
                    <div class="av-section-icon" style="background:#e3f2fd;">
                        <i class="fas fa-history" style="color:#1565c0;"></i>
                    </div>
                    Recent Exam History
                    <?php if ($results): ?>
                    <span class="av-badge-count"><?= count($results) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($results): ?>
                <div class="av-results-card">
                    <div class="table-responsive">
                        <table class="table av-results-table mb-0">
                            <thead>
                                <tr>
                                    <th>Examination</th>
                                    <th>Scheduled</th>
                                    <th>Score</th>
                                    <th>Result</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($results as $r):
                                $schedTs  = strtotime($r['scheduled_date'] . ' ' . $r['sched_time']);
                                $fmtDay   = date('l',       $schedTs);   // Monday
                                $fmtDate  = date('d M Y',   $schedTs);   // 25 Aug 2025
                                $fmtTime  = date('h:i A',   $schedTs);   // 02:30 PM
                                $pctVal   = (float)$r['percentage'];
                                $barColor = $r['result'] === 'PASS' ? '#43a047' : '#e53935';
                            ?>
                            <tr>
                                <td>
                                    <div class="av-exam-name"><?= sanitize($r['exam_name']) ?></div>
                                    <div class="av-exam-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?= $fmtDay ?>, <?= $fmtDate ?> at <?= $fmtTime ?> PKT
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size:.82rem; font-weight:600; color:#37474f;"><?= $fmtDate ?></div>
                                    <div style="font-size:.75rem; color:#90a4ae;"><?= $fmtDay ?></div>
                                </td>
                                <td>
                                    <div class="av-score-bar-wrap">
                                        <div class="av-score-bar-bg">
                                            <div class="av-score-bar-fill"
                                                 style="width:<?= min(100,$pctVal) ?>%;background:<?= $barColor ?>;"></div>
                                        </div>
                                        <span class="av-score-text" style="color:<?= $barColor ?>;">
                                            <?= number_format($pctVal, 1) ?>%
                                        </span>
                                    </div>
                                    <div style="font-size:.75rem; color:#90a4ae; margin-top:.15rem;">
                                        <?= $r['obtained_marks'] ?> / <?= $r['total_marks'] ?> marks
                                    </div>
                                </td>
                                <td>
                                    <span class="av-result-badge <?= $r['result'] === 'PASS' ? 'av-pass' : 'av-fail' ?>">
                                        <i class="fas fa-<?= $r['result'] === 'PASS' ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $r['result'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="result_view.php?attempt_id=<?= $r['attempt_id'] ?? '' ?>"
                                       class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                                        <i class="fas fa-eye me-1"></i>Report
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="av-empty">
                    <div class="av-empty-icon"><i class="fas fa-file-alt"></i></div>
                    <p>No exam results yet.<br>Complete an examination to see your history here.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 📂 PAST / OTHER EXAMS -->
            <?php
            $otherPast = array_filter($pastExams, fn($s) => $s['avail_status'] !== 'completed');
            if ($otherPast):
            ?>
            <div class="mb-4">
                <div class="av-section-header">
                    <div class="av-section-icon" style="background:#f5f5f5;">
                        <i class="fas fa-archive" style="color:#78909c;"></i>
                    </div>
                    Missed / Cancelled
                    <span class="av-badge-count"><?= count($otherPast) ?></span>
                </div>
                <div class="row g-2">
                <?php foreach ($otherPast as $s): ?>
                <div class="col-12 col-sm-6">
                    <div style="background:#fff; border-radius:12px; padding:1rem 1.25rem;
                                box-shadow:0 2px 10px rgba(0,0,0,.06); display:flex;
                                align-items:center; gap:.85rem; border-left:4px solid
                                <?= $s['avail_status'] === 'cancelled' ? '#ef9a9a' : '#90a4ae' ?>;">
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:.9rem; color:#37474f;"><?= sanitize($s['exam_name']) ?></div>
                            <div style="font-size:.78rem; color:#90a4ae; margin-top:.15rem;">
                                <i class="fas fa-calendar me-1"></i><?= $s['fmt_day'] ?>, <?= $s['fmt_date'] ?>
                                &nbsp;·&nbsp;
                                <i class="fas fa-clock me-1"></i><?= $s['fmt_time'] ?> PKT
                            </div>
                        </div>
                        <span class="badge <?= $s['avail_status'] === 'cancelled' ? 'bg-danger' : 'bg-secondary' ?> bg-opacity-75" style="font-size:.72rem;">
                            <?= ucfirst($s['avail_status']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /col-lg-8 -->

        <!-- ── RIGHT SIDEBAR ───────────────────────────────── -->
        <div class="col-lg-4">

            <!-- Profile Card -->
            <div class="av-profile-card mb-4">
                <?php if ($student['profile_picture']): ?>
                    <img src="../admin/serve_file.php?type=profile&id=<?= $studentId ?>"
                         class="av-profile-pic" alt="">
                <?php else: ?>
                    <div class="av-profile-avatar"><?= strtoupper(substr($student['full_name'],0,1)) ?></div>
                <?php endif; ?>
                <div class="av-profile-name"><?= sanitize($student['full_name']) ?></div>
                <div class="av-profile-id"><?= sanitize($student['student_id']) ?></div>

                <div>
                    <?php if ($student['father_name']): ?>
                    <div class="av-profile-stat">
                        <span><i class="fas fa-user me-1 text-muted"></i>Father</span>
                        <strong><?= sanitize($student['father_name']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if ($student['phone']): ?>
                    <div class="av-profile-stat">
                        <span><i class="fas fa-phone me-1 text-muted"></i>Phone</span>
                        <strong><?= sanitize($student['phone']) ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="av-profile-stat">
                        <span><i class="fas fa-tasks me-1 text-muted"></i>Assigned</span>
                        <strong><?= count($schedules) ?> Exams</strong>
                    </div>
                    <div class="av-profile-stat">
                        <span><i class="fas fa-check me-1 text-muted"></i>Completed</span>
                        <strong><?= $totalAttempts ?></strong>
                    </div>
                    <div class="av-profile-stat">
                        <span><i class="fas fa-trophy me-1 text-muted"></i>Passed</span>
                        <strong class="text-success"><?= $passCount ?></strong>
                    </div>
                </div>
            </div>

            <!-- Quick Info Card -->
            <div style="background:#fff; border-radius:16px; box-shadow:var(--card-shadow); padding:1.25rem; margin-bottom:1.25rem;">
                <div style="font-size:.85rem; font-weight:700; color:#1a237e; margin-bottom:.85rem;">
                    <i class="fas fa-info-circle me-1"></i>Exam Guidelines
                </div>
                <div style="font-size:.8rem; color:#546e7a; line-height:1.7;">
                    <div class="mb-1"><i class="fas fa-circle me-2" style="font-size:.4rem; color:#3949ab; vertical-align:middle;"></i>Login before your scheduled time</div>
                    <div class="mb-1"><i class="fas fa-circle me-2" style="font-size:.4rem; color:#3949ab; vertical-align:middle;"></i>Stable internet connection required</div>
                    <div class="mb-1"><i class="fas fa-circle me-2" style="font-size:.4rem; color:#3949ab; vertical-align:middle;"></i>Do not switch tabs during exam</div>
                    <div class="mb-1"><i class="fas fa-circle me-2" style="font-size:.4rem; color:#3949ab; vertical-align:middle;"></i>Answers auto-save as you proceed</div>
                    <div><i class="fas fa-circle me-2" style="font-size:.4rem; color:#3949ab; vertical-align:middle;"></i>All times are in Pakistan Standard Time (PKT)</div>
                </div>
            </div>

            <!-- Current PKT Time Card -->
            <div style="background:var(--grad-main); border-radius:16px; padding:1.25rem; text-align:center; color:#fff;">
                <div style="font-size:.75rem; opacity:.7; margin-bottom:.25rem; text-transform:uppercase; letter-spacing:.5px;">
                    Current Time (PKT)
                </div>
                <div id="sidebarClock" style="font-size:1.6rem; font-weight:800; font-family:monospace; letter-spacing:2px;">
                    <?= date('h:i:s A') ?>
                </div>
                <div style="font-size:.8rem; opacity:.8; margin-top:.2rem;">
                    <?= date('l, d F Y') ?>
                </div>
                <div style="font-size:.72rem; opacity:.6; margin-top:.15rem;">UTC+05:00 Pakistan</div>
            </div>

        </div><!-- /col-lg-4 -->
    </div><!-- /row -->
</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Live clock (PKT display) ──────────────────────────────────
function updateClocks() {
    const now = new Date();
    // Force PKT display using toLocaleTimeString with offset isn't reliable
    // We just show local clock — server already set timezone to PKT
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const s = String(now.getSeconds()).padStart(2,'0');
    const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
    const h12  = String(now.getHours() % 12 || 12).padStart(2,'0');
    const timeStr = `${h12}:${m}:${s} ${ampm}`;

    const nav  = document.getElementById('navClock');
    const side = document.getElementById('sidebarClock');
    if (nav)  nav.textContent  = `${h12}:${m} ${ampm}`;
    if (side) side.textContent = timeStr;
}
updateClocks();
setInterval(updateClocks, 1000);

// ── Countdown timers ──────────────────────────────────────────
document.querySelectorAll('.av-countdown-box[data-seconds]').forEach(box => {
    let secs = parseInt(box.dataset.seconds) || 0;
    const el = box.querySelector('.av-countdown-timer');
    if (!el) return;
    const tick = () => {
        if (secs <= 0) {
            el.textContent = 'Starting soon…';
            setTimeout(() => location.reload(), 3000);
            return;
        }
        const h = Math.floor(secs / 3600);
        const mi = Math.floor((secs % 3600) / 60);
        const sc = secs % 60;
        el.textContent = `${String(h).padStart(2,'0')}:${String(mi).padStart(2,'0')}:${String(sc).padStart(2,'0')}`;
        secs--;
        setTimeout(tick, 1000);
    };
    tick();
});
</script>
</body>
</html>
