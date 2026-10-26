<?php
session_start();
require_once '../config/database.php';
$conn = getConnection();
require_once 'layout.php';

$sid_db = intval($_SESSION['student_id']);
$today  = date('Y-m-d');
$month  = date('Y-m');

// Today's records
$today_recs = $conn->query("
    SELECT subject, time_in, status FROM attendance
    WHERE student_id=$sid_db AND date='$today'
    ORDER BY time_in ASC
");

// This month summary
$month_stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(status='present') as present,
        SUM(status='late')    as late,
        SUM(status='absent')  as absent
    FROM attendance
    WHERE student_id=$sid_db AND DATE_FORMAT(date,'%Y-%m')='$month'
")->fetch_assoc();

// Last 5 records
$recent = $conn->query("
    SELECT date, subject, time_in, status FROM attendance
    WHERE student_id=$sid_db
    ORDER BY date DESC, time_in DESC
    LIMIT 5
");
?>

<div class="s-page-title">🏠 Dashboard</div>

<!-- Greeting card -->
<div class="s-card" style="background: linear-gradient(135deg, #0a0e2e, #1565c0); color: white; border: none;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
        <div>
            <p style="font-size:0.8rem; opacity:0.7; margin-bottom:4px;">
                <?= date('l, F d, Y') ?>
            </p>
            <h2 style="font-size:1.4rem; font-weight:800; margin-bottom:6px;">
                Good <?= (date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening')) ?>,
                <?= explode(' ', $student_name)[0] ?>! 👋
            </h2>
            <p style="font-size:0.84rem; opacity:0.75;">
                <?= htmlspecialchars($student_course) ?> &nbsp;·&nbsp;
                Year <?= $student_year ?> - <?= htmlspecialchars($student_section) ?>
            </p>
        </div>
        <a href="scan.php" style="
            background: rgba(0,200,150,0.2);
            border: 1.5px solid rgba(0,200,150,0.5);
            color: #00c896;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s;
            white-space: nowrap;
        " onmouseover="this.style.background='rgba(0,200,150,0.35)'"
           onmouseout="this.style.background='rgba(0,200,150,0.2)'">
            📷 Take Attendance
        </a>
    </div>
</div>

<!-- Monthly stats -->
<div style="display:grid; grid-template-columns: repeat(3,1fr); gap:12px; margin-bottom:18px;">
    <div class="s-card" style="text-align:center; padding:16px; margin-bottom:0;">
        <div style="font-size:1.8rem; font-weight:900; color:#00a87a; line-height:1;">
            <?= $month_stats['present'] ?? 0 ?>
        </div>
        <div style="font-size:0.74rem; color:var(--muted); margin-top:4px; font-weight:600;">
            Present<br><?= date('M Y') ?>
        </div>
    </div>
    <div class="s-card" style="text-align:center; padding:16px; margin-bottom:0;">
        <div style="font-size:1.8rem; font-weight:900; color:#e65100; line-height:1;">
            <?= $month_stats['late'] ?? 0 ?>
        </div>
        <div style="font-size:0.74rem; color:var(--muted); margin-top:4px; font-weight:600;">
            Late<br><?= date('M Y') ?>
        </div>
    </div>
    <div class="s-card" style="text-align:center; padding:16px; margin-bottom:0;">
        <div style="font-size:1.8rem; font-weight:900; color:#e53935; line-height:1;">
            <?= $month_stats['total'] ?? 0 ?>
        </div>
        <div style="font-size:0.74rem; color:var(--muted); margin-top:4px; font-weight:600;">
            Total<br><?= date('M Y') ?>
        </div>
    </div>
</div>

<!-- Today's attendance -->
<div class="s-card">
    <div class="s-card-title">📅 Today's Attendance</div>
    <?php if ($today_recs && $today_recs->num_rows > 0): ?>
        <table class="s-table">
            <thead><tr><th>Subject</th><th>Time In</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r = $today_recs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['subject']) ?></td>
                    <td><?= $r['time_in'] ?></td>
                    <td><span class="s-badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <div style="margin-top:14px;">
            <a href="scan.php" class="s-btn s-btn-primary">📷 Record Another Subject</a>
        </div>
    <?php else: ?>
        <div class="s-empty">
            <div class="ei">📭</div>
            <p>No attendance recorded today yet.</p>
        </div>
        <a href="scan.php" class="s-btn s-btn-green s-btn-block" style="margin-top:10px;">
            📷 Record My Attendance Now
        </a>
    <?php endif; ?>
</div>

<!-- Recent records -->
<div class="s-card">
    <div class="s-card-title">🕐 Recent Records</div>
    <?php if ($recent && $recent->num_rows > 0): ?>
        <table class="s-table">
            <thead><tr><th>Date</th><th>Subject</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d', strtotime($r['date'])) ?></td>
                    <td><?= htmlspecialchars($r['subject']) ?></td>
                    <td><?= $r['time_in'] ?></td>
                    <td><span class="s-badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <div style="margin-top:14px;">
            <a href="my_records.php" class="s-btn s-btn-primary">View All Records →</a>
        </div>
    <?php else: ?>
        <div class="s-empty">
            <div class="ei">📂</div>
            <p>No attendance records yet.</p>
        </div>
    <?php endif; ?>
</div>

</main>
</body>
</html>
