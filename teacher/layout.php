<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['teacher_id'])) {
    header('Location: ../teacher_login.php');
    exit;
}
require_once '../config/database.php';
$conn = getConnection();

$teacher_id   = intval($_SESSION['teacher_id']);
$teacher_name = $_SESSION['teacher_name'] ?? '';
$teacher_emp  = $_SESSION['teacher_emp']  ?? '';
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Unread notification count
$notif_count = $conn->query("
    SELECT COUNT(*) as c FROM notifications
    WHERE recipient_type='teacher' AND recipient_id=$teacher_id AND is_read=0
")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AttendEase — Teacher</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <style>
        /* ── Teacher sidebar green theme override ── */
        .sidebar {
            background: linear-gradient(180deg, #1b5e20, #2e7d32) !important;
        }
        .sidebar-header {
            background: rgba(0,0,0,0.15) !important;
        }
        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            border-left-color: #a5d6a7 !important;
            background: rgba(255,255,255,0.12) !important;
        }
        .topbar h4 { color: #1b5e20; }

        /* Notification badge */
        .notif-badge {
            display: inline-flex; align-items: center; justify-content: center;
            background: #e53935; color: white;
            border-radius: 10px; padding: 1px 7px;
            font-size: 0.65rem; font-weight: 800;
            margin-left: 4px; vertical-align: middle;
        }

        /* Teacher-specific card title color */
        .card-title { color: #1b5e20; border-bottom-color: #e8f5e9; }

        /* Stat card teacher colors */
        .stat-card.teacher { border-left-color: #2e7d32; }
        .stat-info h3 { color: #1b5e20; }

        /* Table header green */
        thead th { background: #2e7d32; }

        /* Topbar teacher badge */
        .topbar-badge {
            background: #e8f5e9; color: #2e7d32;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 800;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- ── SIDEBAR ── -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <span class="sidebar-icon"><img src="../assets/img/logo.png" style="width:44px;height:44px;object-fit:cover;border-radius:8px;" alt="Logo"></span>
            <h3>Face Recognition<br>Attendance System<br><small style="opacity:0.7;font-size:0.75rem;">Teacher Portal</small></h3>
        </div>

        <nav>
            <ul>
                <li>
                    <a href="dashboard.php" class="<?= $current_page==='dashboard' ?'active':'' ?>">
                        <span class="nav-icon">📊</span> Dashboard
                    </a>
                </li>
                <li>
                    <a href="my_classes.php" class="<?= $current_page==='my_classes' ?'active':'' ?>">
                        <span class="nav-icon">📚</span> My Classes
                    </a>
                </li>
                <li>
                    <a href="attendance.php" class="<?= $current_page==='attendance' ?'active':'' ?>">
                        <span class="nav-icon">📷</span> Attendance
                    </a>
                </li>
                <li>
                    <a href="schedules.php" class="<?= $current_page==='schedules' ?'active':'' ?>">
                        <span class="nav-icon">🗓️</span> Schedules
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?= $current_page==='reports' ?'active':'' ?>">
                        <span class="nav-icon">📋</span> Reports
                    </a>
                </li>
                <li>
                    <a href="location.php" class="<?= $current_page==='location' ?'active':'' ?>">
                        <span class="nav-icon">📍</span> Location
                    </a>
                </li>
                <li>
                    <a href="notifications.php" class="<?= $current_page==='notifications' ?'active':'' ?>">
                        <span class="nav-icon">🔔</span> Notifications
                        <?php if ($notif_count > 0): ?>
                        <span class="notif-badge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="profile.php" class="<?= $current_page==='profile' ?'active':'' ?>">
                        <span class="nav-icon">👤</span> My Profile
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../teacher_logout.php" class="btn btn-danger btn-block btn-sm">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- ── MAIN CONTENT ── -->
    <main class="main-content">
        <div class="topbar">
            <h4>
                <?php
                $titles = [
                    'dashboard'     => '📊 Dashboard',
                    'my_classes'    => '📚 My Classes',
                    'attendance'    => '📷 Attendance',
                    'schedules'     => '🗓️ Schedules',
                    'reports'       => '📋 Reports',
                    'location'      => '📍 Location Settings',
                    'notifications' => '🔔 Notifications',
                    'profile'       => '👤 My Profile',
                ];
                echo $titles[$current_page] ?? ucfirst($current_page);
                ?>
            </h4>
            <div class="topbar-user">
                <span class="topbar-badge">👨‍🏫 TEACHER</span>
                👤 <?= htmlspecialchars($teacher_name) ?>
                &nbsp;|&nbsp;
                <?= date('F d, Y') ?>
            </div>
        </div>
<script src="../assets/js/notifications.js"></script>
