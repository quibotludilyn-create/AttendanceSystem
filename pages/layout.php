<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Location: ../admin/index.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Monitoring System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<div class="wrapper">
    <aside class="sidebar admin-sidebar">
        <div class="sidebar-header">
            <span class="sidebar-icon"><img src="../assets/img/logo.png" style="width:44px;height:44px;object-fit:cover;border-radius:8px;" alt="Logo"></span>
            <h3>Face Recognition<br>Attendance Monitoring<br>System</h3>
        </div>
        <nav id="admin-sidebar-menu">
            <ul>
                <li>
                    <a href="dashboard.php" class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                        <span class="nav-icon">📊</span> Dashboard
                    </a>
                </li>
                <li>
                    <?php /* Take Attendance hidden from admin sidebar
                    <a href="attendance.php" class="<?= $current_page === 'attendance' ? 'active' : '' ?>">
                        <span class="nav-icon">📷</span> Take Attendance
                    </a>
                    */ ?>
                </li>
                <li>
                    <a href="students.php" class="<?= $current_page === 'students' ? 'active' : '' ?>">
                        <span class="nav-icon">👥</span> Students
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                        <span class="nav-icon">📋</span> Reports
                    </a>
                </li>
                <?php /* Location Settings hidden from admin sidebar
                <li>
                    <a href="location_settings.php" class="<?= $current_page === 'location_settings' ? 'active' : '' ?>">
                        <span class="nav-icon">📍</span> Location Settings
                    </a>
                </li>
                */ ?>
                <li>
                    <a href="teachers.php" class="<?= $current_page === 'teachers' ? 'active' : '' ?>">
                        <span class="nav-icon">👨‍🏫</span> Teachers
                        <?php
                        require_once '../config/database.php';
                        $tc = getConnection()->query("SELECT COUNT(*) as c FROM teachers WHERE is_approved=0")->fetch_assoc()['c'];
                        if ($tc > 0): ?>
                        <span style="background:#e53935;color:white;border-radius:10px;padding:1px 7px;font-size:0.65rem;margin-left:4px;"><?= $tc ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="notifications.php" class="<?= $current_page === 'notifications' ? 'active' : '' ?>">
                        <span class="nav-icon">🔔</span> Notifications
                        <?php
                        $nc = getConnection()->query("SELECT COUNT(*) as c FROM notifications WHERE recipient_type='admin' AND recipient_id={$_SESSION['user_id']} AND is_read=0");
                        $nc_count = $nc ? $nc->fetch_assoc()['c'] : 0;
                        if ($nc_count > 0): ?>
                        <span style="background:#e53935;color:white;border-radius:10px;padding:1px 7px;font-size:0.65rem;margin-left:4px;"><?= $nc_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="feedbacks.php" class="<?= $current_page === 'feedbacks' ? 'active' : '' ?>">
                        <span class="nav-icon">💬</span> Feedbacks
                        <?php
                        $fbc = getConnection()->query("SELECT COUNT(*) as c FROM feedbacks WHERE DATE(created_at) = CURDATE()");
                        $fbc_count = $fbc ? $fbc->fetch_assoc()['c'] : 0;
                        if ($fbc_count > 0): ?>
                        <span style="background:#1565c0;color:white;border-radius:10px;padding:1px 7px;font-size:0.65rem;margin-left:4px;"><?= $fbc_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn btn-danger btn-block btn-sm">
                🚪 Logout
            </a>
        </div>
    </aside>
    <div class="admin-sidebar-overlay" id="admin-sidebar-overlay" onclick="closeAdminSidebar()" aria-hidden="true"></div>

    <main class="main-content">
        <div class="topbar">
            <button class="admin-hamburger" type="button" onclick="toggleAdminSidebar()"
                    aria-expanded="false" aria-controls="admin-sidebar-menu" aria-label="Open navigation menu">☰</button>
            <h4>
                <?php
                $titles = [
                    'dashboard'          => '📊 Dashboard',
                    'attendance'         => '📷 Take Attendance',
                    'students'           => '👥 Students',
                    'reports'            => '📋 Reports',
                    'location_settings'  => '📍 Location Settings',
                    'teachers'           => '👨‍🏫 Teachers',
                    'notifications'      => '🔔 Notifications',
                    'feedbacks'          => '💬 Feedbacks',
                ];
                echo $titles[$current_page] ?? ucfirst($current_page);
                ?>
            </h4>
            <div class="topbar-user">
                👤 <?= htmlspecialchars($_SESSION['full_name']) ?>
                &nbsp;|&nbsp;
                <?= date('F d, Y') ?>
            </div>
        </div>
<script>
function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const isOpen = sidebar.classList.toggle('is-open');
    document.getElementById('admin-sidebar-overlay').classList.toggle('show', isOpen);
    setAdminSidebarButton(isOpen);
}
function setAdminSidebarButton(isOpen) {
    const button = document.querySelector('.admin-hamburger');
    button.textContent = isOpen ? '✕' : '☰';
    button.setAttribute('aria-expanded', String(isOpen));
    button.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
}
function closeAdminSidebar() {
    document.querySelector('.admin-sidebar').classList.remove('is-open');
    document.getElementById('admin-sidebar-overlay').classList.remove('show');
    setAdminSidebarButton(false);
}
document.querySelectorAll('#admin-sidebar-menu a').forEach(link => {
    link.addEventListener('click', closeAdminSidebar);
});
</script>
<!-- Notifications script loaded at body level so DOM is ready -->
<script src="../assets/js/notifications.js"></script>
