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

// Delete single
if (isset($_GET['delete'])) {
    $nid = intval($_GET['delete']);
    $conn->query("DELETE FROM notifications WHERE id=$nid AND recipient_type='teacher' AND recipient_id=$teacher_id");
    header('Location: notifications.php');
    exit;
}

// Delete all
if (isset($_GET['delete_all'])) {
    $conn->query("DELETE FROM notifications WHERE recipient_type='teacher' AND recipient_id=$teacher_id");
    header('Location: notifications.php');
    exit;
}

// Bulk delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = array_map('intval', $_POST['selected'] ?? []);
    if (!empty($ids)) {
        $ids_str = implode(',', $ids);
        $conn->query("DELETE FROM notifications WHERE id IN ($ids_str) AND recipient_type='teacher' AND recipient_id=$teacher_id");
    }
    header('Location: notifications.php');
    exit;
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

<div class="teacher-notification-filters" style="display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;">
    <a href="?filter=all"    class="btn btn-primary" style="<?= $filter==='all'   ?'':'opacity:0.5;' ?>">All</a>
    <a href="?filter=unread" class="btn btn-warning" style="<?= $filter==='unread'?'':'opacity:0.6;' ?>">
        Unread <?php if($unread>0): ?><span style="background:#e53935;color:white;border-radius:10px;padding:1px 7px;font-size:0.65rem;margin-left:4px;"><?= $unread ?></span><?php endif; ?>
    </a>
    <?php if ($unread > 0): ?>
    <a href="?mark_all=1" class="btn btn-success">✓ Mark all read</a>
    <?php endif; ?>
    <?php if ($notifs->num_rows > 0): ?>
    <a href="?delete_all=1" onclick="return confirm('Delete all notifications?')" class="btn btn-danger" style="margin-left:auto;">🗑️ Delete All</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">🔔 Notifications
        <?php if ($unread > 0): ?>
        <span class="badge badge-absent" style="margin-left:8px;"><?= $unread ?> unread</span>
        <?php endif; ?>
    </div>

    <?php if ($notifs->num_rows > 0): ?>
    <form method="POST" id="bulk-form">
        <div class="teacher-notification-bulk-controls" style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eee;">
            <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;font-weight:600;color:#555;cursor:pointer;">
                <input type="checkbox" id="select-all" style="width:16px;height:16px;cursor:pointer;">
                Select All
            </label>
            <button type="submit" name="bulk_delete" onclick="return confirmBulk()"
                style="padding:7px 16px;border-radius:8px;background:#ffebee;color:#e53935;
                       border:1px solid #ffcdd2;font-size:0.82rem;font-weight:700;cursor:pointer;
                       display:none;" id="delete-selected-btn">
                🗑️ Delete Selected
            </button>
        </div>
        <?php while($n = $notifs->fetch_assoc()): ?>
        <div class="teacher-notification-item" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="checkbox" name="selected[]" value="<?= $n['id'] ?>" class="notif-cb"
                   style="width:17px;height:17px;cursor:pointer;flex-shrink:0;accent-color:#2e7d32;">
            <a class="teacher-notification-link" href="?read=<?= $n['id'] ?>" style="display:flex;gap:14px;padding:14px;border-radius:12px;flex:1;text-decoration:none;color:inherit;background:<?= $n['is_read']?'#fafafa':'#f1f8e9' ?>;border:1px solid <?= $n['is_read']?'#eee':'#c8e6c9' ?>;transition:background 0.2s;">
                <div style="font-size:1.6rem;flex-shrink:0;margin-top:2px;"><?= $type_icons[$n['type']] ?? '🔔' ?></div>
                <div style="flex:1;">
                    <div style="font-size:0.88rem;font-weight:<?= $n['is_read']?'500':'700' ?>;color:#1b5e20;"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="teacher-notification-message" style="font-size:0.81rem;color:#555;margin-top:3px;line-height:1.5;"><?= htmlspecialchars($n['message']) ?></div>
                    <div style="font-size:0.72rem;color:#aaa;margin-top:4px;"><?= date('F d, Y · h:i A', strtotime($n['created_at'])) ?></div>
                </div>
                <?php if (!$n['is_read']): ?>
                <div style="width:8px;height:8px;border-radius:50%;background:#2e7d32;flex-shrink:0;margin-top:6px;"></div>
                <?php endif; ?>
            </a>
            <a class="teacher-notification-delete" href="?delete=<?= $n['id'] ?>" onclick="return confirm('Delete this notification?')"
               style="padding:10px 12px;border-radius:10px;background:#ffebee;color:#e53935;
                      text-decoration:none;font-size:0.9rem;border:1px solid #ffcdd2;flex-shrink:0;">
                🗑️
            </a>
        </div>
        <?php endwhile; ?>
    </form>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">🔔</div><p>No notifications yet.</p></div>
    <?php endif; ?>
</div>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.notif-cb').forEach(cb => cb.checked = this.checked);
    toggleDeleteBtn();
});
document.querySelectorAll('.notif-cb').forEach(cb => cb.addEventListener('change', toggleDeleteBtn));
function toggleDeleteBtn() {
    const anyChecked = [...document.querySelectorAll('.notif-cb')].some(cb => cb.checked);
    const btn = document.getElementById('delete-selected-btn');
    if (btn) btn.style.display = anyChecked ? 'inline-block' : 'none';
}
function confirmBulk() {
    const count = [...document.querySelectorAll('.notif-cb:checked')].length;
    return confirm(`Delete ${count} selected notification(s)?`);
}
</script>

</main>
</div>
</body>
</html>
