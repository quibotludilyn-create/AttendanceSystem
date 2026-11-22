<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// No-cache: always serve fresh data (attendance counts, notifications, etc.)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
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

        .teacher-attendance-panels {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 18px;
            margin-bottom: 20px;
        }

        .teacher-attendance-panels > .card { min-width: 0; }

        @media (max-width: 1024px) {
            .teacher-attendance-panels { grid-template-columns: minmax(0, 1fr); }
        }

        .teacher-subject-header,
        .teacher-attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .teacher-subject-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .teacher-subject-item { min-width: 0; }

        .teacher-attendance-search { display: flex; width: min(100%, 220px); }
        .teacher-attendance-search-input { width: 100%; min-width: 0; box-sizing: border-box; }

        .teacher-classes-layout {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .teacher-classes-layout > .card { min-width: 0; }
        .teacher-class-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .teacher-subject-table { min-width: 640px; }
        .teacher-enrollment-table { min-width: 720px; }

        .teacher-page-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .teacher-page-grid > .card { min-width: 0; }
        .teacher-form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        .teacher-schedule-item { flex-wrap: wrap; gap: 8px; }
        .teacher-schedule-info { min-width: 0; flex: 1 1 200px; }

        .teacher-report-stats {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            margin-bottom: 20px;
        }
        .teacher-profile-stats {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            margin-bottom: 20px;
        }
        .teacher-report-table { min-width: 860px; }
        .teacher-student-rate-table { min-width: 680px; }

        .teacher-location-item { flex-wrap: wrap; gap: 8px; }
        .teacher-location-info { min-width: 0; flex: 1 1 180px; }
        .teacher-location-actions { flex-shrink: 0; }

        .teacher-notification-filters,
        .teacher-notification-bulk-controls { flex-wrap: wrap; }
        .teacher-notification-link { min-width: 0; }
        .teacher-notification-link > div:nth-child(2) { min-width: 0; }
        .teacher-notification-message { overflow-wrap: anywhere; }

        @media (max-width: 1024px) {
            .teacher-page-grid { grid-template-columns: minmax(0, 1fr); }
            .teacher-report-stats { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
            .teacher-profile-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
        }

        @media (max-width: 1024px) {
            .teacher-classes-layout { grid-template-columns: minmax(0, 1fr); }
        }

        @media (max-width: 480px) {
            .teacher-class-form-grid { grid-template-columns: minmax(0, 1fr); }
            .teacher-form-row { grid-template-columns: minmax(0, 1fr); }
            .teacher-location-actions { width: 100%; justify-content: flex-end; }
            .teacher-report-filters > .form-group {
                flex: 1 1 100% !important;
                width: 100%;
                min-width: 0 !important;
            }
            .teacher-report-filter-actions { flex-wrap: wrap; }
            .teacher-report-filter-actions .btn { flex: 1 1 auto; }
            .teacher-report-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .teacher-profile-stats { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
            .teacher-profile-stats .stat-card,
            .teacher-report-stats .stat-card { padding: 12px; gap: 8px; }
            .teacher-profile-stats .stat-icon,
            .teacher-report-stats .stat-icon { width: 42px; height: 42px; }
            .teacher-profile-stats .stat-info h3,
            .teacher-report-stats .stat-info h3 { font-size: 1.4rem; }
            .teacher-notification-item {
                display: grid !important;
                grid-template-columns: 20px minmax(0, 1fr) auto;
                align-items: start !important;
                gap: 8px !important;
            }
            .teacher-notification-link { padding: 10px !important; gap: 8px !important; }
        }

        @media (max-width: 1024px) {
            .teacher-subject-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        /* ── Hamburger button ── */
        .hamburger {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 8px;
        }

        @media (max-width: 768px) {
            .teacher-subject-grid { grid-template-columns: minmax(0, 1fr); }
            .teacher-attendance-header {
                align-items: stretch !important;
                flex-direction: column;
            }
            .teacher-attendance-search { width: 100%; }

            .hamburger {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                flex: 0 0 44px;
                width: 44px;
                height: 44px;
                margin: 0 !important;
                color: #1b5e20 !important;
                background: #e8f5e9;
                border: 1px solid #c8e6c9;
                border-radius: 8px;
            }

            .sidebar {
                width: 100% !important;
                height: auto !important;
                position: relative !important;
                flex-direction: column !important;
                align-items: stretch !important;
                overflow: visible !important;
            }

            .sidebar nav,
            .sidebar-footer { display: none !important; }
            .sidebar.open nav,
            .sidebar.open .sidebar-footer {
                display: block !important;
                width: 100% !important;
            }

            .sidebar nav ul {
                flex-direction: column !important;
                overflow: visible !important;
                padding: 8px 0 !important;
            }

            .sidebar nav ul li a {
                border-left: 3px solid transparent !important;
                border-bottom: none !important;
                padding: 14px 20px !important;
                white-space: nowrap !important;
            }

            .sidebar nav ul li a.active,
            .sidebar nav ul li a:hover {
                border-left-color: #a5d6a7 !important;
                background: rgba(255,255,255,0.12) !important;
            }

            .sidebar-footer {
                width: 100% !important;
                padding: 16px !important;
                position: relative !important;
                bottom: auto !important;
            }

            .main-content {
                margin-left: 0 !important;
                padding: 12px !important;
                width: 100% !important;
            }

            .topbar {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 12px !important;
                padding: 12px 16px !important;
                position: sticky !important;
                top: 0 !important;
                z-index: 100 !important;
                background: white !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            }

            .topbar h4 { flex: 1; min-width: 0; }

            .topbar-user { display: none !important; }

            .stats-grid { grid-template-columns: repeat(2,1fr) !important; }
            .grid-2     { grid-template-columns: 1fr !important; }
            .table-responsive { overflow-x: auto !important; }
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
            <button class="hamburger" type="button" onclick="toggleSidebar()" id="hamburger-btn"
                    aria-expanded="false" aria-controls="teacher-sidebar-menu" aria-label="Open navigation menu">☰</button>
        </div>

        <nav id="teacher-sidebar-menu">
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
<script>
function toggleSidebar() {
    const isOpen = document.querySelector('.sidebar').classList.toggle('open');
    setSidebarButton(isOpen);
}
function setSidebarButton(isOpen) {
    const button = document.getElementById('hamburger-btn');
    button.textContent = isOpen ? '✕' : '☰';
    button.setAttribute('aria-expanded', String(isOpen));
    button.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('open');
    setSidebarButton(false);
}
document.querySelectorAll('#teacher-sidebar-menu a').forEach(link => {
    link.addEventListener('click', closeSidebar);
});
</script>
