<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

$msg = '';

// Toggle visibility
if (isset($_GET['toggle'])) {
    $fid = intval($_GET['toggle']);
    $conn->query("UPDATE feedbacks SET is_visible = NOT is_visible WHERE id=$fid");
    header('Location: feedbacks.php');
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $fid = intval($_GET['delete']);
    $conn->query("DELETE FROM feedbacks WHERE id=$fid");
    header('Location: feedbacks.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$where  = $filter === 'visible' ? "WHERE is_visible=1" : ($filter === 'hidden' ? "WHERE is_visible=0" : "");
$feedbacks = $conn->query("SELECT * FROM feedbacks $where ORDER BY created_at DESC");

$total   = $conn->query("SELECT COUNT(*) as c FROM feedbacks")->fetch_assoc()['c'];
$visible = $conn->query("SELECT COUNT(*) as c FROM feedbacks WHERE is_visible=1")->fetch_assoc()['c'];
$avg_rat = $conn->query("SELECT ROUND(AVG(rating),1) as r FROM feedbacks")->fetch_assoc()['r'];

$role_icons = ['student'=>'👨‍🎓','teacher'=>'👨‍🏫','admin'=>'🛡️','other'=>'👤'];
?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px;">
    <div class="stat-card blue">
        <div class="stat-icon" style="background:#e3f2fd;">💬</div>
        <div class="stat-info"><h3><?= $total ?></h3><p>Total Feedbacks</p></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9;">✅</div>
        <div class="stat-info"><h3><?= $visible ?></h3><p>Visible on Landing</p></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon" style="background:#fff3e0;">⭐</div>
        <div class="stat-info"><h3><?= $avg_rat ?? '—' ?></h3><p>Average Rating</p></div>
    </div>
</div>

<div class="card">
    <div class="card-title">
        💬 User Feedbacks
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="?filter=all"     class="btn btn-sm <?= $filter==='all'    ?'btn-primary':'btn-info' ?>">All (<?= $total ?>)</a>
            <a href="?filter=visible" class="btn btn-sm <?= $filter==='visible'?'btn-primary':'btn-info' ?>">Visible (<?= $visible ?>)</a>
            <a href="?filter=hidden"  class="btn btn-sm <?= $filter==='hidden' ?'btn-primary':'btn-info' ?>">Hidden (<?= $total-$visible ?>)</a>
        </div>
    </div>

    <?php if ($feedbacks && $feedbacks->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr><th>Name</th><th>Role</th><th>Rating</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($f = $feedbacks->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($f['name']) ?></td>
                <td>
                    <span style="font-size:1.2rem;"><?= $role_icons[$f['role']] ?? '👤' ?></span>
                    <span style="font-size:0.82rem;color:#888;margin-left:4px;"><?= ucfirst($f['role']) ?></span>
                </td>
                <td>
                    <span style="color:#ffa726;font-size:1rem;letter-spacing:1px;">
                        <?= str_repeat('★', $f['rating']) ?><?= str_repeat('☆', 5-$f['rating']) ?>
                    </span>
                    <span style="font-size:0.78rem;color:#aaa;margin-left:4px;">(<?= $f['rating'] ?>/5)</span>
                </td>
                <td style="max-width:300px;">
                    <div style="font-size:0.86rem;color:#444;line-height:1.5;max-height:60px;overflow:hidden;">
                        <?= htmlspecialchars($f['message']) ?>
                    </div>
                </td>
                <td style="font-size:0.8rem;color:#888;white-space:nowrap;"><?= date('M d, Y', strtotime($f['created_at'])) ?></td>
                <td>
                    <?php if ($f['is_visible']): ?>
                    <span class="badge badge-present">✅ Visible</span>
                    <?php else: ?>
                    <span class="badge badge-absent">🚫 Hidden</span>
                    <?php endif; ?>
                </td>
                <td style="white-space:nowrap;">
                    <a href="?toggle=<?= $f['id'] ?>" class="btn btn-sm <?= $f['is_visible']?'btn-warning':'btn-success' ?>">
                        <?= $f['is_visible'] ? '🚫 Hide' : '✅ Show' ?>
                    </a>
                    <a href="?delete=<?= $f['id'] ?>" onclick="return confirm('Delete this feedback?')" class="btn btn-danger btn-sm">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <p style="font-size:0.78rem;color:#aaa;margin-top:12px;">
        ℹ️ Only <strong>Visible</strong> feedbacks appear on the landing page as testimonials.
        Toggle to show/hide individual feedbacks.
    </p>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">💬</div><p>No feedbacks yet.</p></div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>
