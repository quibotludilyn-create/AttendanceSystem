<?php
session_start();
require_once '../config/database.php';
$conn = getConnection();
require_once 'layout.php';

$sid_db = intval($_SESSION['student_id']);
$today  = date('Y-m-d');
$month  = date('Y-m');
$dayOfWeek = date('l'); // e.g. Monday

// Today's records
$today_recs = $conn->query("
    SELECT COALESCE(a.subject, sub.subject_name, '—') as subject, a.time_in, a.status 
    FROM attendance a
    LEFT JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.student_id=$sid_db AND a.date='$today'
    ORDER BY a.time_in ASC
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

// All-time stats for percentage
$all_stats = $conn->query("
    SELECT COUNT(*) as total,
        SUM(status='present') as present,
        SUM(status='late') as late,
        SUM(status='absent') as absent
    FROM attendance WHERE student_id=$sid_db
")->fetch_assoc();

$total_all   = intval($all_stats['total'] ?? 0);
$present_all = intval($all_stats['present'] ?? 0);
$late_all    = intval($all_stats['late'] ?? 0);
$absent_all  = intval($all_stats['absent'] ?? 0);
$attend_pct  = $total_all > 0 ? round(($present_all + $late_all) / $total_all * 100) : 0;

// Last 7 days trend
$trend = $conn->query("
    SELECT date, SUM(status='present') as present, SUM(status='late') as late, SUM(status='absent') as absent
    FROM attendance
    WHERE student_id=$sid_db AND date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY date ORDER BY date ASC
");
$trend_labels = $trend_present = $trend_late = $trend_absent = [];
while($r = $trend->fetch_assoc()) {
    $trend_labels[]  = date('M d', strtotime($r['date']));
    $trend_present[] = intval($r['present']);
    $trend_late[]    = intval($r['late']);
    $trend_absent[]  = intval($r['absent']);
}

// Upcoming classes today
$upcoming = $conn->query("
    SELECT sub.subject_name, s.start_time, s.end_time, s.room, u.full_name as teacher_name
    FROM schedules s
    JOIN subjects sub ON sub.id = s.subject_id
    JOIN enrollments e ON e.subject_id = s.subject_id
    JOIN users u ON u.id = sub.teacher_id
    WHERE e.student_id=$sid_db AND s.day_of_week='$dayOfWeek'
    AND s.start_time > CURTIME()
    ORDER BY s.start_time ASC
    LIMIT 3
");

// Last 5 records
$recent = $conn->query("
    SELECT a.date, COALESCE(a.subject, sub.subject_name, '—') as subject, a.time_in, a.status 
    FROM attendance a
    LEFT JOIN subjects sub ON a.subject_id = sub.id
    WHERE a.student_id=$sid_db
    ORDER BY a.date DESC, a.time_in DESC
    LIMIT 5
");
?>

<div class="s-page-title">🏠 Dashboard</div>

<?php
// Auto-mark absents for ended classes today
$absResult = $conn->query("
    SELECT s.id, s.subject_id, s.end_time, sub.subject_name, sub.teacher_id
    FROM schedules s
    JOIN subjects sub ON sub.id = s.subject_id
    JOIN enrollments e ON e.subject_id = s.subject_id
    WHERE s.day_of_week = '" . date('l') . "'
      AND s.end_time <= '" . date('H:i:s') . "'
      AND e.student_id = $sid_db
");
if ($absResult) {
    while ($sched = $absResult->fetch_assoc()) {
        $subName = $conn->real_escape_string($sched['subject_name']);
        $exists = $conn->query("SELECT id FROM attendance WHERE student_id=$sid_db AND date='$today' AND subject='$subName' LIMIT 1");
        if ($exists && $exists->num_rows === 0) {
            $conn->query("INSERT INTO attendance (student_id, date, time_in, status, subject, recorded_by)
                VALUES ($sid_db, '$today', '" . date('H:i:s') . "', 'absent', '$subName', " . intval($sched['teacher_id']) . ")");
        }
    }
}
?>

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

<!-- Attendance Overview Chart + Percentage -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:18px;">
    <!-- Donut Chart -->
    <div class="s-card" style="margin-bottom:0;">
        <div class="s-card-title">📊 Attendance Overview</div>
        <div style="position:relative;height:160px;display:flex;align-items:center;justify-content:center;">
            <canvas id="donutChart"></canvas>
            <div style="position:absolute;text-align:center;">
                <div style="font-size:1.6rem;font-weight:900;color:var(--navy);"><?= $attend_pct ?>%</div>
                <div style="font-size:0.68rem;color:var(--muted);font-weight:600;">Attendance</div>
            </div>
        </div>
        <div style="display:flex;justify-content:center;gap:12px;margin-top:8px;font-size:0.72rem;font-weight:600;">
            <span style="color:#00a87a;">● Present (<?= $present_all ?>)</span>
            <span style="color:#e65100;">● Late (<?= $late_all ?>)</span>
            <span style="color:#e53935;">● Absent (<?= $absent_all ?>)</span>
        </div>
    </div>

    <!-- Absent Summary -->
    <div class="s-card" style="margin-bottom:0;">
        <div class="s-card-title">📋 Absent Summary</div>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
            <div style="background:#ffebee;border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:0.8rem;font-weight:700;color:#c62828;">❌ Absent</div>
                    <div style="font-size:0.72rem;color:#888;margin-top:2px;">All time</div>
                </div>
                <div style="font-size:1.6rem;font-weight:900;color:#e53935;"><?= $absent_all ?></div>
            </div>
            <div style="background:#fff3e0;border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:0.8rem;font-weight:700;color:#e65100;">⏰ Late</div>
                    <div style="font-size:0.72rem;color:#888;margin-top:2px;">All time</div>
                </div>
                <div style="font-size:1.6rem;font-weight:900;color:#e65100;"><?= $late_all ?></div>
            </div>
            <div style="background:#e8f5e9;border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <div style="font-size:0.8rem;font-weight:700;color:#2e7d32;">✅ Present</div>
                    <div style="font-size:0.72rem;color:#888;margin-top:2px;">All time</div>
                </div>
                <div style="font-size:1.6rem;font-weight:900;color:#00a87a;"><?= $present_all ?></div>
            </div>
        </div>
    </div>
</div>

<!-- 7-Day Trend Chart -->
<div class="s-card">
    <div class="s-card-title">📈 7-Day Attendance Trend</div>
    <div style="position:relative;height:160px;">
        <canvas id="trendChart"></canvas>
    </div>
</div>

<!-- Upcoming Classes -->
<?php if ($upcoming && $upcoming->num_rows > 0): ?>
<div class="s-card">
    <div class="s-card-title">🗓️ Upcoming Classes Today</div>
    <?php while($uc = $upcoming->fetch_assoc()): ?>
    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f4f4f4;">
        <div style="background:#e8f4fd;border-radius:10px;padding:8px 12px;text-align:center;min-width:52px;">
            <div style="font-size:0.78rem;font-weight:800;color:var(--mid);"><?= date('h:i', strtotime($uc['start_time'])) ?></div>
            <div style="font-size:0.65rem;color:#aaa;"><?= date('A', strtotime($uc['start_time'])) ?></div>
        </div>
        <div style="flex:1;">
            <div style="font-size:0.88rem;font-weight:700;color:var(--navy);"><?= htmlspecialchars($uc['subject_name']) ?></div>
            <div style="font-size:0.76rem;color:var(--muted);">
                👨‍🏫 <?= htmlspecialchars($uc['teacher_name']) ?>
                <?= $uc['room'] ? ' · Room '.$uc['room'] : '' ?>
                · <?= date('h:i A', strtotime($uc['start_time'])) ?>–<?= date('h:i A', strtotime($uc['end_time'])) ?>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Donut Chart
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Late', 'Absent'],
        datasets: [{
            data: [<?= $present_all ?>, <?= $late_all ?>, <?= $absent_all ?>],
            backgroundColor: ['#00a87a','#e65100','#e53935'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});

// Line Chart
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [
            {
                label: 'Present',
                data: <?= json_encode($trend_present) ?>,
                borderColor: '#00a87a', backgroundColor: 'rgba(0,168,122,0.1)',
                tension: 0.4, fill: true, pointRadius: 4
            },
            {
                label: 'Late',
                data: <?= json_encode($trend_late) ?>,
                borderColor: '#e65100', backgroundColor: 'rgba(230,81,0,0.1)',
                tension: 0.4, fill: true, pointRadius: 4
            },
            {
                label: 'Absent',
                data: <?= json_encode($trend_absent) ?>,
                borderColor: '#e53935', backgroundColor: 'rgba(229,57,53,0.1)',
                tension: 0.4, fill: true, pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<!-- Today's attendance -->
<div class="s-card">
    <div class="s-card-title">📅 Today's Attendance</div>
    <?php if ($today_recs && $today_recs->num_rows > 0): ?>
        <table class="s-table">
            <thead><tr><th>Subject</th><th>Time In</th><th>Status</th></tr></thead>
            <tbody>
            <?php while($r = $today_recs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['subject'] ?? '—') ?></td>
                    <td><?= $r['time_in'] ?></td>
                    <td><span class="s-badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
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
                    <td><?= htmlspecialchars($r['subject'] ?? '—') ?></td>
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
