<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

$total_students = $conn->query("SELECT COUNT(*) as c FROM students")->fetch_assoc()['c'];
$today = date('Y-m-d');

$present_today = $conn->query("
    SELECT COUNT(DISTINCT student_id) as c 
    FROM attendance 
    WHERE date='$today' AND status='present'
")->fetch_assoc()['c'];

$late_today = $conn->query("
    SELECT COUNT(DISTINCT student_id) as c 
    FROM attendance 
    WHERE date='$today' AND status='late'
")->fetch_assoc()['c'];

$absent_today = $total_students - $present_today - $late_today;
if ($absent_today < 0) $absent_today = 0;

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
        <div class="stat-info">
            <h3><?= $total_students ?></h3>
            <p>Total Students</p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9">✅</div>
        <div class="stat-info">
            <h3><?= $present_today ?></h3>
            <p>Present Today</p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon" style="background:#fff3e0">⏰</div>
        <div class="stat-info">
            <h3><?= $late_today ?></h3>
            <p>Late Today</p>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon" style="background:#ffebee">❌</div>
        <div class="stat-info">
            <h3><?= $absent_today ?></h3>
            <p>Absent Today</p>
        </div>
    </div>
</div>

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
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['course']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
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
