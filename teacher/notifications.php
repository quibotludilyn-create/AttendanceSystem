<?php
// All redirects MUST happen before layout.php outputs HTML
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header('Location: ../teacher_login.php');
    exit;
}
require_once '../config/database.php';
$conn       = getConnection();
$teacher_id = intval($_SESSION['teacher_id']);

if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE recipient_type='teacher' AND recipient_id=$teacher_id");
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['read'])) {
    $nid = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$nid AND recipient_type='teacher' AND recipient_id=$teacher_id");
}

// Now include layout (outputs HTML)
require_once 'layout.php';

$filter = $_GET['filter'] ?? 'all';
$w      = "WHERE recipient_type='teacher' AND recipient_id=$teacher_id";
if ($filter === 'unread') $w .= " AND is_read=0";

$notifs = $conn->query("SELECT * FROM notifications $w ORDER BY created_at DESC");
$unread = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE recipient_type='teacher' AND recipient_id=$teacher_id AND is_read=0")->fetch_assoc()['c'];

$type_icons = ['attendance'=>'📋','schedule'=>'🗓️','system'=>'⚙️','manual'=>'✏️'];
?>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="?filter=all"    class="btn <?= $filter==='all'   ?'btn-primary':'btn-primary' ?>" style="<?= $filter==='all'   ?'':'opacity:0.5;' ?>">All</a>
    <a href="?filter=unread" class="btn btn-warning" style="<?= $filter==='unread'?'':'opacity:0.6;' ?>">
        Unread <?php if($unread>0): ?><span style="background:#e53935;color:white;border-radius:10px;padding:1px 7px;font-size:0.65rem;margin-left:4px;"><?= $unread ?></span><?php endif; ?>
    </a>
    <?php if ($unread > 0): ?>
    <a href="?mark_all=1" class="btn btn-success" style="margin-left:auto;">✓ Mark all read</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">🔔 Notifications
        <?php if ($unread > 0): ?>
        <span class="badge badge-absent" style="margin-left:8px;"><?= $unread ?> unread</span>
        <?php endif; ?>
    </div>

    <?php if ($notifs->num_rows > 0): ?>
    <?php while($n = $notifs->fetch_assoc()): ?>
    <a href="?read=<?= $n['id'] ?>" style="display:flex;gap:14px;padding:14px;border-radius:12px;margin-bottom:8px;text-decoration:none;color:inherit;background:<?= $n['is_read']?'#fafafa':'#f1f8e9' ?>;border:1px solid <?= $n['is_read']?'#eee':'#c8e6c9' ?>;transition:background 0.2s;">
        <div style="font-size:1.6rem;flex-shrink:0;margin-top:2px;"><?= $type_icons[$n['type']] ?? '🔔' ?></div>
        <div style="flex:1;">
            <div style="font-size:0.88rem;font-weight:<?= $n['is_read']?'500':'700' ?>;color:#1b5e20;"><?= htmlspecialchars($n['title']) ?></div>
            <div style="font-size:0.81rem;color:#555;margin-top:3px;line-height:1.5;"><?= htmlspecialchars($n['message']) ?></div>
            <div style="font-size:0.72rem;color:#aaa;margin-top:4px;"><?= date('F d, Y · h:i A', strtotime($n['created_at'])) ?></div>
        </div>
        <?php if (!$n['is_read']): ?>
        <div style="width:8px;height:8px;border-radius:50%;background:#2e7d32;flex-shrink:0;margin-top:6px;"></div>
        <?php endif; ?>
    </a>
    <?php endwhile; ?>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">🔔</div><p>No notifications yet.</p></div>
    <?php endif; ?>
</div>

</main>
</div>
</body>
</html>
