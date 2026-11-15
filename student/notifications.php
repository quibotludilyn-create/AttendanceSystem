<?php
session_start();
require_once '../config/database.php';
$conn = getConnection();

// ── Must handle redirects BEFORE layout.php outputs HTML ──
if (!isset($_SESSION['student_id'])) {
    header('Location: ../student_login.php');
    exit;
}

$student_id = intval($_SESSION['student_id']);

// Mark all as read — redirect before any HTML output
if (isset($_GET['mark_all'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE recipient_type='student' AND recipient_id=$student_id");
    header('Location: notifications.php');
    exit;
}

// Mark single as read — no redirect needed, just update
if (isset($_GET['read'])) {
    $nid = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id=$nid AND recipient_type='student' AND recipient_id=$student_id");
}

// Delete single notification
if (isset($_GET['delete'])) {
    $nid = intval($_GET['delete']);
    $conn->query("DELETE FROM notifications WHERE id=$nid AND recipient_type='student' AND recipient_id=$student_id");
    header('Location: notifications.php');
    exit;
}

// Delete all notifications
if (isset($_GET['delete_all'])) {
    $conn->query("DELETE FROM notifications WHERE recipient_type='student' AND recipient_id=$student_id");
    header('Location: notifications.php');
    exit;
}

// Bulk delete selected
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = $_POST['selected'] ?? [];
    if (!empty($ids)) {
        $ids = array_map('intval', $ids);
        $ids_str = implode(',', $ids);
        $conn->query("DELETE FROM notifications WHERE id IN ($ids_str) AND recipient_type='student' AND recipient_id=$student_id");
    }
    header('Location: notifications.php');
    exit;
}

// Now include layout (outputs HTML)
require_once 'layout.php';

$filter = $_GET['filter'] ?? 'all';
$where  = "WHERE recipient_type='student' AND recipient_id=$student_id";
if ($filter === 'unread') $where .= " AND is_read=0";

$notifs = $conn->query("SELECT * FROM notifications $where ORDER BY created_at DESC");
$unread = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE recipient_type='student' AND recipient_id=$student_id AND is_read=0")->fetch_assoc()['c'];

$type_icons = ['attendance' => '📋', 'schedule' => '🗓️', 'system' => '⚙️', 'manual' => '✏️'];
?>

<div class="s-page-title">
    🔔 Notifications
    <?php if ($unread > 0): ?>
    <span style="background:#e53935;color:white;border-radius:20px;padding:3px 10px;font-size:0.72rem;font-weight:800;margin-left:8px;"><?= $unread ?> unread</span>
    <?php endif; ?>
</div>

<!-- Filter bar -->
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px;">
    <a href="?filter=all"
       style="padding:8px 18px;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:700;
              background:<?= $filter==='all'?'var(--mid)':'#eef0f7' ?>;
              color:<?= $filter==='all'?'white':'#555' ?>;">
        All (<?= $notifs->num_rows ?>)
    </a>
    <a href="?filter=unread"
       style="padding:8px 18px;border-radius:8px;text-decoration:none;font-size:0.85rem;font-weight:700;
              background:<?= $filter==='unread'?'var(--mid)':'#eef0f7' ?>;
              color:<?= $filter==='unread'?'white':'#555' ?>;">
        Unread (<?= $unread ?>)
    </a>
    <?php if ($unread > 0): ?>
    <a href="?mark_all=1"
       style="padding:8px 18px;border-radius:8px;text-decoration:none;
              font-size:0.85rem;font-weight:700;background:#e8fdf5;color:#00a87a;border:1px solid #a5d6a7;">
        ✓ Mark all as read
    </a>
    <?php endif; ?>
    <?php if ($notifs->num_rows > 0): ?>
    <a href="?delete_all=1" onclick="return confirm('Delete all notifications?')"
       style="margin-left:auto;padding:8px 18px;border-radius:8px;text-decoration:none;
              font-size:0.85rem;font-weight:700;background:#ffebee;color:#e53935;border:1px solid #ffcdd2;">
        🗑️ Delete All
    </a>
    <?php endif; ?>
</div>

<div class="s-card">
    <?php if ($notifs && $notifs->num_rows > 0): ?>
    <form method="POST" id="bulk-form">
        <!-- Select All + Delete Selected bar -->
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid #eee;">
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

        <?php while ($n = $notifs->fetch_assoc()): ?>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <!-- Checkbox -->
            <input type="checkbox" name="selected[]" value="<?= $n['id'] ?>"
                   class="notif-cb"
                   style="width:17px;height:17px;cursor:pointer;flex-shrink:0;accent-color:var(--mid);">
            <!-- Notification row -->
            <a href="?read=<?= $n['id'] ?>"
               style="display:flex;gap:14px;padding:14px;border-radius:12px;flex:1;
                      text-decoration:none;color:inherit;
                      background:<?= $n['is_read'] ? '#fafafa' : '#f0f7ff' ?>;
                      border:1px solid <?= $n['is_read'] ? '#eee' : '#bbdefb' ?>;
                      transition:background 0.2s;">
                <div style="font-size:1.8rem;flex-shrink:0;margin-top:2px;"><?= $type_icons[$n['type']] ?? '🔔' ?></div>
                <div style="flex:1;">
                    <div style="font-size:0.88rem;font-weight:<?= $n['is_read'] ? '500' : '700' ?>;color:var(--navy);">
                        <?= htmlspecialchars($n['title']) ?>
                    </div>
                    <div style="font-size:0.82rem;color:#555;margin-top:4px;line-height:1.55;">
                        <?= htmlspecialchars($n['message']) ?>
                    </div>
                    <div style="font-size:0.72rem;color:#aaa;margin-top:5px;">
                        <?= date('F d, Y · h:i A', strtotime($n['created_at'])) ?>
                    </div>
                </div>
                <?php if (!$n['is_read']): ?>
                <div style="width:9px;height:9px;border-radius:50%;background:var(--mid);flex-shrink:0;margin-top:6px;"></div>
                <?php endif; ?>
            </a>
            <!-- Single delete -->
            <a href="?delete=<?= $n['id'] ?>" onclick="return confirm('Delete this notification?')"
               style="padding:10px 12px;border-radius:10px;background:#ffebee;color:#e53935;
                      text-decoration:none;font-size:0.9rem;border:1px solid #ffcdd2;
                      flex-shrink:0;align-self:center;">
                🗑️
            </a>
        </div>
        <?php endwhile; ?>
    </form>
    <?php else: ?>
    <div class="s-empty">
        <div class="ei">🔔</div>
        <p>No notifications yet.</p>
    </div>
    <?php endif; ?>
</div>

<script>
// Select all checkbox
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.notif-cb').forEach(cb => cb.checked = this.checked);
    toggleDeleteBtn();
});

// Individual checkbox change
document.querySelectorAll('.notif-cb').forEach(cb => {
    cb.addEventListener('change', toggleDeleteBtn);
});

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
</body>
</html>
