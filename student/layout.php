<?php
// Student shared layout — include at top of every student page
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['student_id'])) {
    header('Location: ../student_login.php');
    exit;
}

// Initialize DB connection for layout use (notification count etc.)
if (!isset($conn)) {
    require_once '../config/database.php';
    $conn = getConnection();
}

$student_name    = $_SESSION['student_name']    ?? '';
$student_sid     = $_SESSION['student_sid']     ?? '';
$student_course  = $_SESSION['student_course']  ?? '';
$student_year    = $_SESSION['student_year']    ?? '';
$student_section = $_SESSION['student_section'] ?? '';
$cur = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Required for face-api.js (TensorFlow.js uses eval internally) -->
    <title>AttendEase — <?= ucfirst($cur) ?></title>
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --navy:   #0a0e2e;
            --blue:   #1a237e;
            --mid:    #1565c0;
            --accent: #00b4d8;
            --green:  #00c896;
            --red:    #e53935;
            --white:  #fff;
            --gray:   #f4f6fb;
            --text:   #2c3e50;
            --muted:  #7f8c9a;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: var(--gray);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOP NAV ── */
        .s-nav {
            background: var(--navy);
            padding: 0 24px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 16px rgba(0,0,0,0.25);
        }

        .s-nav-brand {
            display: flex; align-items: center; gap: 10px;
            text-decoration: none; color: white;
        }

        .s-nav-brand .logo {
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--accent), var(--mid));
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .s-nav-brand span { font-size: 1rem; font-weight: 800; }
        .s-nav-brand span em { color: var(--accent); font-style: normal; }

        .s-nav-right {
            display: flex; align-items: center; gap: 8px;
        }

        .s-nav-user {
            color: rgba(255,255,255,0.75);
            font-size: 0.83rem;
            padding: 6px 12px;
            background: rgba(255,255,255,0.08);
            border-radius: 8px;
        }

        .s-nav-user strong { color: white; }

        .s-logout {
            background: rgba(229,57,53,0.15);
            border: 1px solid rgba(229,57,53,0.35);
            color: #ff7070;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }

        .s-logout:hover { background: rgba(229,57,53,0.3); color: white; }

        /* ── BOTTOM NAV (mobile-first tab bar) ── */
        .s-tabs {
            background: white;
            border-bottom: 1px solid #e8eaf6;
            display: flex;
            padding: 0 16px;
            gap: 4px;
            overflow-x: auto;
            scrollbar-width: none;
        }

        .s-tabs::-webkit-scrollbar { display: none; }

        .s-tab {
            display: flex; flex-direction: column; align-items: center;
            gap: 2px;
            padding: 10px 16px;
            text-decoration: none;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 600;
            border-bottom: 3px solid transparent;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .s-tab .tab-icon { font-size: 1.25rem; }

        .s-tab:hover    { color: var(--blue); }
        .s-tab.active   { color: var(--blue); border-bottom-color: var(--blue); }

        /* ── MAIN CONTENT ── */
        .s-main {
            flex: 1;
            padding: 20px 20px 40px;
            max-width: 780px;
            width: 100%;
            margin: 0 auto;
        }

        /* ── PAGE TITLE ── */
        .s-page-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--navy);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── CARDS ── */
        .s-card {
            background: white;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border: 1px solid #eef0f7;
            margin-bottom: 18px;
        }

        .s-card-title {
            font-size: 0.92rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2fa;
            display: flex; align-items: center; gap: 7px;
        }

        /* ── BADGE ── */
        .s-badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            display: inline-block;
        }

        .badge-present { background: #e8f5e9; color: #2e7d32; }
        .badge-late    { background: #fff3e0; color: #e65100; }
        .badge-absent  { background: #ffebee; color: #c62828; }

        /* ── TABLE ── */
        .s-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
        .s-table th {
            background: #f0f2fa; color: var(--navy);
            padding: 10px 12px; text-align: left;
            font-size: 0.76rem; font-weight: 700; letter-spacing: 0.3px;
        }
        .s-table td { padding: 10px 12px; border-bottom: 1px solid #f4f4f4; }
        .s-table tr:last-child td { border-bottom: none; }
        .s-table tr:hover td      { background: #f8f9ff; }

        /* ── EMPTY STATE ── */
        .s-empty {
            text-align: center; padding: 36px 20px; color: var(--muted);
        }
        .s-empty .ei { font-size: 3rem; margin-bottom: 8px; }
        .s-empty p   { font-size: 0.88rem; }

        /* ── BTN ── */
        .s-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 22px; border: none; border-radius: 10px;
            font-size: 0.9rem; font-weight: 700;
            cursor: pointer; text-decoration: none;
            transition: all 0.2s;
        }
        .s-btn-primary  { background: linear-gradient(135deg, var(--accent), var(--mid)); color: white; box-shadow: 0 4px 14px rgba(0,180,216,0.3); }
        .s-btn-primary:hover  { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,180,216,0.45); }
        .s-btn-green    { background: linear-gradient(135deg, #00c896, #00a87a); color: white; box-shadow: 0 4px 14px rgba(0,200,150,0.3); }
        .s-btn-green:hover    { transform: translateY(-2px); }
        .s-btn-danger   { background: rgba(229,57,53,0.1); color: var(--red); border: 1px solid rgba(229,57,53,0.3); }
        .s-btn-danger:hover   { background: var(--red); color: white; }
        .s-btn-block    { width: 100%; justify-content: center; }

        /* ── FORM ── */
        .s-form-group { margin-bottom: 14px; }
        .s-form-group label {
            display: block; font-size: 0.83rem;
            font-weight: 600; color: #555; margin-bottom: 5px;
        }
        .s-input {
            width: 100%; padding: 10px 14px;
            border: 1.5px solid #dde0ec;
            border-radius: 9px; font-size: 0.92rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafbff;
        }
        .s-input:focus {
            outline: none; border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0,180,216,0.15);
        }

        /* ── ALERT ── */
        .s-alert { padding: 12px 16px; border-radius: 10px; font-size: 0.87rem; margin-bottom: 14px; }
        .s-alert-ok   { background: #e8fdf5; color: #00a87a; border: 1px solid #b2dfdb; }
        .s-alert-err  { background: #ffebee; color: var(--red); border: 1px solid #ffcdd2; }

        @media (max-width: 600px) {
            .s-main { padding: 14px 12px 36px; }
            .s-nav-user { display: none; }
        }
    </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="s-nav">
    <a href="home.php" class="s-nav-brand">
        <div class="logo"><img src="../assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:7px;" alt="Logo"></div>
        <span>Attend<em>Ease</em></span>
    </a>
    <div class="s-nav-right">
        <div class="s-nav-user">
            👤 <strong><?= htmlspecialchars($student_name) ?></strong>
            &nbsp;·&nbsp; <?= htmlspecialchars($student_sid) ?>
        </div>
        <a href="../student_logout.php" class="s-logout">🚪 Logout</a>
    </div>
</nav>

<!-- TAB BAR -->
<nav class="s-tabs">
    <a href="home.php"       class="s-tab <?= $cur==='home'       ?'active':'' ?>"><span class="tab-icon">🏠</span>Home</a>
    <a href="scan.php"       class="s-tab <?= $cur==='scan'       ?'active':'' ?>"><span class="tab-icon">📷</span>Take Attendance</a>
    <a href="my_records.php" class="s-tab <?= $cur==='my_records' ?'active':'' ?>"><span class="tab-icon">📋</span>My Records</a>
    <a href="profile.php"    class="s-tab <?= $cur==='profile'    ?'active':'' ?>"><span class="tab-icon">👤</span>Profile</a>
    <a href="notifications.php" class="s-tab <?= $cur==='notifications' ?'active':'' ?>">
        <span class="tab-icon">🔔</span>Alerts
        <?php
        $nc = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE recipient_type='student' AND recipient_id={$_SESSION['student_id']} AND is_read=0");
        $nc_count = $nc ? $nc->fetch_assoc()['c'] : 0;
        if ($nc_count > 0): ?>
        <span style="background:#e53935;color:white;border-radius:10px;padding:1px 5px;font-size:0.6rem;margin-left:2px;"><?= $nc_count ?></span>
        <?php endif; ?>
    </a>
</nav>

<main class="s-main">
<script src="../assets/js/notifications.js"></script>
