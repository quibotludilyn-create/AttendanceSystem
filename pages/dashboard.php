<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$today = date('Y-m-d');

$present_today = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE date='$today' AND status='present'")->fetch_assoc()['c'];
$late_today    = $conn->query("SELECT COUNT(DISTINCT student_id) as c FROM attendance WHERE date='$today' AND status='late'")->fetch_assoc()['c'];
$absent_today  = $total_students - $present_today - $late_today;
if ($absent_today < 0) $absent_today = 0;

// Last 7 days attendance trend
$trend = $conn->query("
    SELECT date, 
        SUM(status='present') as present,
        SUM(status='late')    as late,
        SUM(status='absent')  as absent
    FROM attendance
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY date ORDER BY date ASC
");
$trend_labels = $trend_present = $trend_late = [];
while($r = $trend->fetch_assoc()) {
    $trend_labels[]  = date('M d', strtotime($r['date']));
    $trend_present[] = intval($r['present']);
    $trend_late[]    = intval($r['late']);
}

$recent = $conn->query("
    SELECT s.full_name, s.student_id, s.course, a.time_in, a.status, a.subject
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE a.date = '$today' 
    ORDER BY a.created_at DESC 
    LIMIT 15
");
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon" style="background:#e8eaf6">👥</div>
        <div class="stat-info"><h3><?= $total_students ?></h3><p>Total Students</p></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9">✅</div>
        <div class="stat-info"><h3><?= $present_today ?></h3><p>Present Today</p></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon" style="background:#fff3e0">⏰</div>
        <div class="stat-info"><h3><?= $late_today ?></h3><p>Late Today</p></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon" style="background:#ffebee">❌</div>
        <div class="stat-info"><h3><?= $absent_today ?></h3><p>Absent Today</p></div>
    </div>
</div>

<!-- Charts Row -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;">
    <!-- Donut Chart: Today's Attendance -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-title">🍩 Today's Overview</div>
        <div style="position:relative;height:220px;display:flex;align-items:center;justify-content:center;">
            <canvas id="donutChart"></canvas>
        </div>
        <div style="display:flex;justify-content:center;gap:16px;margin-top:12px;font-size:0.78rem;font-weight:600;">
            <span style="color:#43a047;">● Present (<?= $present_today ?>)</span>
            <span style="color:#ffa726;">● Late (<?= $late_today ?>)</span>
            <span style="color:#e53935;">● Absent (<?= $absent_today ?>)</span>
        </div>
    </div>

    <!-- Line Chart: 7-Day Trend -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-title">📈 7-Day Attendance Trend</div>
        <div style="position:relative;height:220px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Donut Chart
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Late', 'Absent'],
        datasets: [{
            data: [<?= $present_today ?>, <?= $late_today ?>, <?= $absent_today ?>],
            backgroundColor: ['#43a047','#ffa726','#e53935'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
        }
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
                borderColor: '#43a047',
                backgroundColor: 'rgba(67,160,71,0.1)',
                tension: 0.4, fill: true, pointRadius: 4,
            },
            {
                label: 'Late',
                data: <?= json_encode($trend_late) ?>,
                borderColor: '#ffa726',
                backgroundColor: 'rgba(255,167,38,0.1)',
                tension: 0.4, fill: true, pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: { ticks: { font: { size: 11 } } }
        }
    }
});
</script>

<div class="card">
    <div class="card-title">🕐 Today's Attendance — <?= date('F d, Y') ?></div>
    <?php if ($recent && $recent->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['course'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['subject'] ?? '—') ?></td>
                    <td><?= $row['time_in'] ?></td>
                    <td>
                        <span class="badge badge-<?= $row['status'] ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No attendance recorded today yet.</p>
            <a href="attendance.php" class="btn btn-primary" style="margin-top:12px">
                📷 Take Attendance Now
            </a>
        </div>
    <?php endif; ?>
</div>

    </main>
</div>
</body>
</html>
