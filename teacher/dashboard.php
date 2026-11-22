<?php
require_once 'layout.php';
$today     = date('Y-m-d');
$dayOfWeek = date('l');

// ── Stats ──────────────────────────────────────────
$total_subjects = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE teacher_id=$teacher_id AND is_active=1")->fetch_assoc()['c'];

$total_students = $conn->query("
    SELECT COUNT(DISTINCT e.student_id) as c
    FROM enrollments e
    JOIN subjects s ON s.id = e.subject_id
    WHERE s.teacher_id = $teacher_id AND s.is_active = 1
")->fetch_assoc()['c'];

$today_stats = $conn->query("
    SELECT
        SUM(a.status='present') as present,
        SUM(a.status='late')    as late,
        SUM(a.status='absent')  as absent
    FROM attendance a
    JOIN subjects s ON a.subject = s.subject_name AND s.teacher_id = $teacher_id
    WHERE a.date = '$today'
")->fetch_assoc();

$present_today = intval($today_stats['present'] ?? 0);
$late_today    = intval($today_stats['late']    ?? 0);
$absent_today  = intval($today_stats['absent']  ?? 0);
$attended      = $present_today + $late_today;
$total_today   = $attended + $absent_today;
$attend_rate   = $total_today > 0 ? round($attended / $total_today * 100) : 0;

// ── My Subjects ────────────────────────────────────
$subjects = $conn->query("
    SELECT s.id, s.subject_name, s.section, s.subject_code,
           COUNT(DISTINCT e.student_id) as student_count
    FROM subjects s
    LEFT JOIN enrollments e ON e.subject_id = s.id
    WHERE s.teacher_id = $teacher_id AND s.is_active = 1
    GROUP BY s.id
    LIMIT 6
");

// ── Today's Attendance ─────────────────────────────
$recent = $conn->query("
    SELECT st.student_id as sid, st.full_name, a.subject, a.time_in, a.status
    FROM attendance a
    JOIN students st ON a.student_id = st.id
    JOIN subjects sub ON a.subject = sub.subject_name AND sub.teacher_id = $teacher_id
    WHERE a.date = '$today'
    ORDER BY a.created_at DESC
    LIMIT 15
");

// ── Notifications ──────────────────────────────────
$notifs = $conn->query("
    SELECT * FROM notifications
    WHERE recipient_type='teacher' AND recipient_id=$teacher_id
    ORDER BY created_at DESC LIMIT 5
");

// ── All-time for donut ─────────────────────────────
$all_stats = $conn->query("
    SELECT SUM(a.status='present') as present,
           SUM(a.status='late')    as late,
           SUM(a.status='absent')  as absent
    FROM attendance a
    JOIN subjects s ON a.subject = s.subject_name AND s.teacher_id = $teacher_id
")->fetch_assoc();
$p_all = intval($all_stats['present'] ?? 0);
$l_all = intval($all_stats['late']    ?? 0);
$ab_all= intval($all_stats['absent']  ?? 0);
?>

<!-- ── TOP STATS BAR ──────────────────────────────── -->
<div class="teacher-stats-grid">
    <div class="stat-card green" style="flex-direction:row;gap:10px;align-items:center;">
        <div class="stat-icon" style="background:#e8f5e9;font-size:1.3rem;">📚</div>
        <div class="stat-info"><h3><?= $total_subjects ?></h3><p>My Subjects</p></div>
    </div>
    <div class="stat-card blue" style="flex-direction:row;gap:10px;align-items:center;">
        <div class="stat-icon" style="background:#e3f2fd;font-size:1.3rem;">👥</div>
        <div class="stat-info"><h3><?= $total_students ?></h3><p>Total Students</p></div>
    </div>
    <div class="stat-card green" style="flex-direction:row;gap:10px;align-items:center;">
        <div class="stat-icon" style="background:#e8f5e9;font-size:1.3rem;">✅</div>
        <div class="stat-info"><h3><?= $present_today ?></h3><p>Present Today</p></div>
    </div>
    <div class="stat-card orange" style="flex-direction:row;gap:10px;align-items:center;">
        <div class="stat-icon" style="background:#fff3e0;font-size:1.3rem;">⏰</div>
        <div class="stat-info"><h3><?= $late_today ?></h3><p>Late Today</p></div>
    </div>
    <div class="stat-card red" style="flex-direction:row;gap:10px;align-items:center;">
        <div class="stat-icon" style="background:#ffebee;font-size:1.3rem;">❌</div>
        <div class="stat-info"><h3><?= $absent_today ?></h3><p>Absent Today</p></div>
    </div>
    <div class="stat-card blue" style="flex-direction:row;gap:10px;align-items:center;border:2px solid #c8e6c9;">
        <div style="position:relative;width:52px;height:52px;flex-shrink:0;">
            <canvas id="miniDonut" width="52" height="52"></canvas>
            <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:900;color:#1b5e20;"><?= $attend_rate ?>%</div>
        </div>
        <div class="stat-info"><h3><?= $attend_rate ?>%</h3><p>Attendance Rate</p></div>
    </div>
</div>

<!-- ── GREETING BANNER ────────────────────────────── -->
<div class="card" style="background:linear-gradient(135deg,#1b5e20 0%,#2e7d32 60%,#388e3c 100%);color:white;border:none;margin-bottom:20px;overflow:hidden;position:relative;">
    <div style="position:absolute;right:20px;top:50%;transform:translateY(-50%);font-size:5rem;opacity:0.15;">📅</div>
    <div>
        <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:4px;">
            Good <?= date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') ?>,
            <?= htmlspecialchars(explode(' ', $teacher_name)[0]) ?>! 👋
        </h2>
        <p style="font-size:0.84rem;opacity:0.8;">Here's your attendance overview for today.</p>
    </div>
</div>

<!-- ── MY SUBJECTS ────────────────────────────────── -->
<?php if ($subjects && $subjects->num_rows > 0): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            🎓 My Subjects
            <div style="font-size:0.76rem;color:#888;font-weight:400;margin-top:2px;">Your assigned subjects and sections</div>
        </div>
        <span style="background:#e8f5e9;color:#2e7d32;padding:4px 12px;border-radius:20px;font-size:0.76rem;font-weight:700;"><?= $total_subjects ?> Subject<?= $total_subjects != 1 ? 's' : '' ?></span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
        <?php
        $subj_icons = ['💻','🗄️','🌐','📊','🔬','📐','📝','🎨','🧮'];
        $si = 0;
        while($s = $subjects->fetch_assoc()):
            $icon = $subj_icons[$si % count($subj_icons)]; $si++;
        ?>
        <div style="border:1px solid #e8f5e9;border-radius:12px;padding:14px;display:flex;align-items:center;gap:12px;background:#fafffe;">
            <div style="width:40px;height:40px;border-radius:10px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;"><?= $icon ?></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:0.88rem;font-weight:700;color:#1b5e20;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($s['subject_name']) ?></div>
                <div style="font-size:0.74rem;color:#888;">Section <?= htmlspecialchars($s['section']) ?> · <?= $s['student_count'] ?> Students</div>
            </div>
            <a href="attendance.php?subject=<?= $s['id'] ?>" style="background:#e8f5e9;color:#2e7d32;padding:5px 10px;border-radius:8px;text-decoration:none;font-size:0.76rem;font-weight:700;white-space:nowrap;">View →</a>
        </div>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── TODAY'S ATTENDANCE + DONUT ─────────────────── -->
<div class="teacher-attendance-panels">

    <!-- Today's Attendance Table -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
            <div>📅 Today's Attendance — <?= date('F d, Y') ?></div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <input type="text" id="searchStudent" placeholder="🔍 Search Student..."
                    style="padding:6px 12px;border:1px solid #ddd;border-radius:8px;font-size:0.82rem;outline:none;"
                    oninput="filterTable()">
            </div>
        </div>
        <?php if ($recent && $recent->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="attendTable">
                <thead>
                    <tr><th>Student ID</th><th>Name</th><th>Subject</th><th>Time In</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php while($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['sid']) ?></td>
                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                    <td><?= htmlspecialchars($r['subject']) ?></td>
                    <td><?= date('h:i A', strtotime($r['time_in'])) ?></td>
                    <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">
            <a href="reports.php" class="btn btn-primary">📋 View All Records →</a>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No attendance recorded today.</p>
            <a href="attendance.php" class="btn btn-primary" style="margin-top:12px;">📷 Take Attendance Now</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Attendance Analytics -->
    <div class="card" style="margin-bottom:0;">
        <div class="card-title">📊 Attendance Analytics</div>
        <div style="font-size:0.76rem;color:#888;margin-bottom:12px;">Overall attendance for today</div>
        <div style="position:relative;height:160px;display:flex;align-items:center;justify-content:center;">
            <canvas id="donutChart"></canvas>
            <div style="position:absolute;text-align:center;">
                <div style="font-size:1.4rem;font-weight:900;color:#1b5e20;"><?= $attend_rate ?>%</div>
                <div style="font-size:0.68rem;color:#888;">Overall</div>
            </div>
        </div>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px;font-size:0.78rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span><span style="color:#43a047;">●</span> Present</span>
                <strong style="color:#43a047;"><?= $present_today ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span><span style="color:#ffa726;">●</span> Late</span>
                <strong style="color:#ffa726;"><?= $late_today ?></strong>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span><span style="color:#e53935;">●</span> Absent</span>
                <strong style="color:#e53935;"><?= $absent_today ?></strong>
            </div>
        </div>
        <?php if ($attend_rate > 0): ?>
        <div style="margin-top:12px;background:#e8f5e9;border-radius:8px;padding:8px 12px;font-size:0.76rem;color:#2e7d32;font-weight:600;">
            📈 <?= $attend_rate ?>% attendance rate today
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── RECENT NOTIFICATIONS ───────────────────────── -->
<div class="card">
    <div class="card-title" style="display:flex;justify-content:space-between;align-items:center;">
        🔔 Recent Notifications
        <a href="notifications.php" class="btn btn-info btn-sm">View All Notifications →</a>
    </div>
    <div style="font-size:0.76rem;color:#888;margin-bottom:12px;">Latest updates and announcements</div>
    <?php if ($notifs && $notifs->num_rows > 0):
        $type_icons = ['attendance'=>'📋','schedule'=>'🗓️','system'=>'📢','manual'=>'✏️'];
        while($n = $notifs->fetch_assoc()): ?>
    <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5;align-items:flex-start;">
        <div style="width:36px;height:36px;border-radius:10px;background:#e8f5e9;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
            <?= $type_icons[$n['type']] ?? '🔔' ?>
        </div>
        <div style="flex:1;">
            <div style="font-size:0.87rem;font-weight:<?= $n['is_read'] ? '400' : '600' ?>;color:#1b5e20;"><?= htmlspecialchars($n['title']) ?></div>
            <div style="font-size:0.78rem;color:#888;margin-top:2px;"><?= htmlspecialchars($n['message']) ?></div>
            <div style="font-size:0.72rem;color:#bbb;margin-top:2px;"><?= date('M d, Y · h:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
        <div style="width:8px;height:8px;border-radius:50%;background:#2e7d32;flex-shrink:0;margin-top:4px;"></div>
        <?php endif; ?>
    </div>
    <?php endwhile;
    else: ?>
    <div class="empty-state" style="padding:20px 0;"><div class="empty-icon">🔔</div><p>No notifications yet.</p></div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Main Donut
new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present','Late','Absent'],
        datasets: [{
            data: [<?= $present_today ?>,<?= $late_today ?>,<?= $absent_today ?>],
            backgroundColor: ['#43a047','#ffa726','#e53935'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        cutout: '70%',
        plugins: { legend: { display: false } }
    }
});

// Mini Donut in stats
new Chart(document.getElementById('miniDonut'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?= $attended ?>, <?= $absent_today ?>],
            backgroundColor: ['#43a047','#e0e0e0'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: false,
        cutout: '70%',
        plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
});

// Search filter
function filterTable() {
    const q = document.getElementById('searchStudent').value.toLowerCase();
    document.querySelectorAll('#attendTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
