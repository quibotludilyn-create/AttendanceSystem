<?php
require_once 'layout.php';
$today = date('Y-m-d');
$month = date('Y-m');

$stats = $conn->query("
    SELECT
        COUNT(DISTINCT a.student_id) as present,
        SUM(a.status='late')         as late,
        SUM(a.status='absent')       as absent,
        COUNT(DISTINCT e.student_id) as total_students
    FROM subjects s
    JOIN enrollments e ON e.subject_id = s.id
    LEFT JOIN attendance a ON a.student_id = e.student_id
        AND a.date = '$today' AND a.teacher_id = $teacher_id
    WHERE s.teacher_id = $teacher_id AND s.is_active = 1
")->fetch_assoc();

$total_classes = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE teacher_id=$teacher_id AND is_active=1")->fetch_assoc()['c'];

$recent = $conn->query("
    SELECT s2.student_id as sid, s2.full_name, sub.subject_name, sub.section,
           a.time_in, a.status, a.is_manual
    FROM attendance a
    JOIN students s2  ON a.student_id  = s2.id
    JOIN subjects sub ON a.subject_id  = sub.id
    WHERE a.date = '$today' AND a.teacher_id = $teacher_id
    ORDER BY a.created_at DESC LIMIT 10
");

$notifs = $conn->query("
    SELECT * FROM notifications
    WHERE recipient_type='teacher' AND recipient_id=$teacher_id
    ORDER BY created_at DESC LIMIT 5
");
?>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9;">📚</div>
        <div class="stat-info">
            <h3><?= $total_classes ?></h3>
            <p>My Classes</p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9;">✅</div>
        <div class="stat-info">
            <h3><?= $stats['present'] ?? 0 ?></h3>
            <p>Present Today</p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon" style="background:#fff3e0;">⏰</div>
        <div class="stat-info">
            <h3><?= $stats['late'] ?? 0 ?></h3>
            <p>Late Today</p>
        </div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon" style="background:#e3f2fd;">👥</div>
        <div class="stat-info">
            <h3><?= $stats['total_students'] ?? 0 ?></h3>
            <p>Total Students</p>
        </div>
    </div>
</div>

<!-- Greeting Banner -->
<div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:white;border:none;margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
            <p style="font-size:0.8rem;opacity:0.7;margin-bottom:4px;"><?= date('l, F d, Y') ?></p>
            <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:4px;">
                Good <?= date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') ?>,
                <?= htmlspecialchars(explode(' ', $teacher_name)[0]) ?>! 👋
            </h2>
            <p style="font-size:0.84rem;opacity:0.75;"><?= htmlspecialchars($_SESSION['teacher_dept'] ?? '') ?></p>
        </div>
        <a href="attendance.php" style="background:rgba(0,200,150,0.2);border:1.5px solid rgba(0,200,150,0.5);color:#a5d6a7;padding:10px 20px;border-radius:10px;text-decoration:none;font-weight:700;font-size:0.88rem;">
            📷 Take Attendance
        </a>
    </div>
</div>

<!-- Today's Attendance -->
<div class="card">
    <div class="card-title">🕐 Today's Attendance — <?= date('F d, Y') ?></div>
    <?php if ($recent && $recent->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Student ID</th><th>Name</th><th>Subject</th><th>Section</th><th>Time In</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php while($r = $recent->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($r['sid']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><?= htmlspecialchars($r['section']) ?></td>
                <td><?= $r['time_in'] ?></td>
                <td>
                    <span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
                    <?php if ($r['is_manual']): ?>
                    <span class="badge" style="background:#f5f5f5;color:#777;margin-left:4px;">Manual</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div style="margin-top:14px;">
        <a href="reports.php" class="btn btn-primary">View All Reports →</a>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p>No attendance recorded today.</p>
        <a href="attendance.php" class="btn btn-primary" style="margin-top:12px;">📷 Take Attendance Now</a>
    </div>
    <?php endif; ?>
</div>

<!-- Notifications -->
<div class="card">
    <div class="card-title">🔔 Recent Notifications</div>
    <?php if ($notifs && $notifs->num_rows > 0): ?>
    <?php $type_icons = ['attendance'=>'📋','schedule'=>'🗓️','system'=>'⚙️','manual'=>'✏️']; ?>
    <?php while($n = $notifs->fetch_assoc()): ?>
    <div style="display:flex;gap:12px;padding:10px 0;border-bottom:1px solid #f5f5f5;align-items:flex-start;">
        <div style="font-size:1.2rem;"><?= $type_icons[$n['type']] ?? '🔔' ?></div>
        <div style="flex:1;">
            <div style="font-size:0.87rem;font-weight:<?= $n['is_read'] ? '400' : '600' ?>;color:#1b5e20;"><?= htmlspecialchars($n['title']) ?></div>
            <div style="font-size:0.78rem;color:#888;margin-top:2px;"><?= htmlspecialchars($n['message']) ?></div>
            <div style="font-size:0.72rem;color:#bbb;margin-top:2px;"><?= date('M d, h:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
        <div style="width:8px;height:8px;border-radius:50%;background:#2e7d32;flex-shrink:0;margin-top:4px;"></div>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
    <div style="margin-top:14px;"><a href="notifications.php" class="btn btn-primary">View All →</a></div>
    <?php else: ?>
    <div class="empty-state" style="padding:20px 0;"><div class="empty-icon">🔔</div><p>No notifications yet.</p></div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>
