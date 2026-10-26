<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();
$msg = ''; $err = '';

// Approve teacher
if (isset($_GET['approve'])) {
    $tid = intval($_GET['approve']);
    $conn->query("UPDATE teachers SET is_approved=1 WHERE id=$tid");
    // Notify teacher
    $t = $conn->query("SELECT * FROM teachers WHERE id=$tid")->fetch_assoc();
    $conn->query("INSERT INTO notifications (recipient_type,recipient_id,title,message,type)
                  VALUES ('teacher',$tid,'Account Approved',
                  'Your teacher account has been approved. You can now login to AttendEase.','system')");
    $msg = "Teacher approved.";
}

// Reject/Delete teacher
if (isset($_GET['delete'])) {
    $tid = intval($_GET['delete']);
    $conn->query("DELETE FROM teachers WHERE id=$tid");
    $msg = "Teacher deleted.";
}

// Toggle active
if (isset($_GET['toggle'])) {
    $tid = intval($_GET['toggle']);
    $conn->query("UPDATE teachers SET is_active = NOT is_active WHERE id=$tid");
    $msg = "Teacher status updated.";
}

$filter = $_GET['filter'] ?? 'all';
$w = $filter === 'pending' ? "WHERE is_approved=0" : ($filter === 'active' ? "WHERE is_approved=1 AND is_active=1" : "");
$teachers = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM subjects s WHERE s.teacher_id=t.id) as class_count FROM teachers t $w ORDER BY t.created_at DESC");

$pending = $conn->query("SELECT COUNT(*) as c FROM teachers WHERE is_approved=0")->fetch_assoc()['c'];
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="stat-card blue"><div class="stat-icon" style="background:#e8eaf6">👨‍🏫</div><div class="stat-info"><h3><?= $conn->query("SELECT COUNT(*) as c FROM teachers")->fetch_assoc()['c'] ?></h3><p>Total Teachers</p></div></div>
    <div class="stat-card green"><div class="stat-icon" style="background:#e8f5e9">✅</div><div class="stat-info"><h3><?= $conn->query("SELECT COUNT(*) as c FROM teachers WHERE is_approved=1 AND is_active=1")->fetch_assoc()['c'] ?></h3><p>Active Teachers</p></div></div>
    <div class="stat-card orange"><div class="stat-icon" style="background:#fff3e0">⏳</div><div class="stat-info"><h3><?= $pending ?></h3><p>Pending Approval</p></div></div>
</div>

<div class="card">
    <div class="card-title">👨‍🏫 Teacher Management
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="?filter=all"     class="btn btn-sm <?= $filter==='all'    ?'btn-primary':'btn-info' ?>">All</a>
            <a href="?filter=pending" class="btn btn-sm <?= $filter==='pending'?'btn-primary':'btn-info' ?>">Pending <?php if($pending>0): ?><span style="background:#e53935;color:white;border-radius:10px;padding:1px 6px;font-size:0.65rem;margin-left:3px;"><?= $pending ?></span><?php endif; ?></a>
            <a href="?filter=active"  class="btn btn-sm <?= $filter==='active' ?'btn-primary':'btn-info' ?>">Active</a>
        </div>
    </div>

    <?php if ($teachers->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Emp ID</th><th>Name</th><th>Username</th><th>Department</th><th>Classes</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while($t = $teachers->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($t['employee_id']) ?></td>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($t['full_name']) ?></div>
                    <div style="font-size:0.76rem;color:#aaa;"><?= htmlspecialchars($t['email']) ?></div>
                </td>
                <td><?= htmlspecialchars($t['username']) ?></td>
                <td><?= htmlspecialchars($t['department'] ?: '—') ?></td>
                <td><span class="badge badge-present"><?= $t['class_count'] ?> classes</span></td>
                <td>
                    <?php if (!$t['is_approved']): ?>
                    <span class="badge" style="background:#fff3e0;color:#e65100;">⏳ Pending</span>
                    <?php elseif ($t['is_active']): ?>
                    <span class="badge badge-present">✅ Active</span>
                    <?php else: ?>
                    <span class="badge badge-absent">❌ Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <?php if (!$t['is_approved']): ?>
                    <a href="?approve=<?= $t['id'] ?>" class="btn btn-success btn-sm">✅ Approve</a>
                    <?php else: ?>
                    <a href="?toggle=<?= $t['id'] ?>" class="btn btn-warning btn-sm"><?= $t['is_active']?'Deactivate':'Activate' ?></a>
                    <?php endif; ?>
                    <a href="?delete=<?= $t['id'] ?>" onclick="return confirm('Delete <?= htmlspecialchars($t['full_name'], ENT_QUOTES) ?>?')" class="btn btn-danger btn-sm">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">👨‍🏫</div><p>No teachers found.</p></div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>
