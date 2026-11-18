<?php
session_start();
require_once '../config/database.php';
$conn = getConnection();
require_once 'layout.php';

$sid_db = intval($_SESSION['student_id']);

// Filters
$filter_month   = $_GET['month']   ?? date('Y-m');
$filter_subject = $_GET['subject'] ?? '';
$filter_status  = $_GET['status']  ?? '';

$where = ["student_id = $sid_db"];
if ($filter_month)   $where[] = "DATE_FORMAT(date,'%Y-%m') = '" . $conn->real_escape_string($filter_month) . "'";
if ($filter_subject) $where[] = "subject LIKE '%" . $conn->real_escape_string($filter_subject) . "%'";
if ($filter_status)  $where[] = "status = '" . $conn->real_escape_string($filter_status) . "'";

$where_sql = implode(' AND ', $where);

$records = $conn->query("
    SELECT a.date, COALESCE(a.subject, sub.subject_name, '—') as subject, 
           a.time_in, a.status, a.location_verified
    FROM attendance a
    LEFT JOIN subjects sub ON a.subject_id = sub.id
    WHERE $where_sql
    ORDER BY a.date DESC, a.time_in DESC
");

// Summary for filtered period
$summary = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(status='present') as present,
        SUM(status='late')    as late,
        SUM(status='absent')  as absent
    FROM attendance
    WHERE $where_sql
")->fetch_assoc();
?>

<div class="s-page-title">📋 My Attendance Records</div>

<!-- Filter Bar -->
<div class="s-card">
    <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
        <div class="s-form-group" style="margin:0; flex:1; min-width:140px;">
            <label>Month</label>
            <input type="month" name="month" class="s-input"
                   value="<?= htmlspecialchars($filter_month) ?>">
        </div>
        <div class="s-form-group" style="margin:0; flex:1; min-width:140px;">
            <label>Subject</label>
            <input type="text" name="subject" class="s-input"
                   placeholder="All subjects"
                   value="<?= htmlspecialchars($filter_subject) ?>">
        </div>
        <div class="s-form-group" style="margin:0; min-width:120px;">
            <label>Status</label>
            <select name="status" class="s-input">
                <option value="">All</option>
                <option value="present" <?= $filter_status==='present'?'selected':'' ?>>Present</option>
                <option value="late"    <?= $filter_status==='late'   ?'selected':'' ?>>Late</option>
                <option value="absent"  <?= $filter_status==='absent' ?'selected':'' ?>>Absent</option>
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="s-btn s-btn-primary">🔍 Filter</button>
            <a href="my_records.php" class="s-btn" style="background:#eee; color:#555;">↺</a>
        </div>
    </form>
</div>

<!-- Summary pills -->
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
    <div style="background:white; border-radius:12px; padding:12px 18px; text-align:center; border:1px solid #eef0f7; flex:1; min-width:90px;">
        <div style="font-size:1.5rem; font-weight:900; color:#1a237e;"><?= $summary['total'] ?? 0 ?></div>
        <div style="font-size:0.72rem; color:var(--muted); font-weight:600;">Total</div>
    </div>
    <div style="background:white; border-radius:12px; padding:12px 18px; text-align:center; border:1px solid #eef0f7; flex:1; min-width:90px;">
        <div style="font-size:1.5rem; font-weight:900; color:#00a87a;"><?= $summary['present'] ?? 0 ?></div>
        <div style="font-size:0.72rem; color:var(--muted); font-weight:600;">Present</div>
    </div>
    <div style="background:white; border-radius:12px; padding:12px 18px; text-align:center; border:1px solid #eef0f7; flex:1; min-width:90px;">
        <div style="font-size:1.5rem; font-weight:900; color:#e65100;"><?= $summary['late'] ?? 0 ?></div>
        <div style="font-size:0.72rem; color:var(--muted); font-weight:600;">Late</div>
    </div>
    <div style="background:white; border-radius:12px; padding:12px 18px; text-align:center; border:1px solid #eef0f7; flex:1; min-width:90px;">
        <div style="font-size:1.5rem; font-weight:900; color:#e53935;"><?= $summary['absent'] ?? 0 ?></div>
        <div style="font-size:0.72rem; color:var(--muted); font-weight:600;">Absent</div>
    </div>
</div>

<!-- Records Table -->
<div class="s-card">
    <div class="s-card-title">
        📄 Records — <?= date('F Y', strtotime($filter_month . '-01')) ?>
        <?php if ($filter_subject): ?>&nbsp;·&nbsp; <?= htmlspecialchars($filter_subject) ?><?php endif; ?>
    </div>

    <?php if ($records && $records->num_rows > 0): ?>
    <div style="overflow-x:auto;">
        <table class="s-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Subject</th>
                    <th>Time In</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
                    <td style="color:var(--muted);"><?= date('D', strtotime($r['date'])) ?></td>
                    <td><?= htmlspecialchars($r['subject'] ?? '—') ?></td>
                    <td><?= $r['time_in'] ?></td>
                    <td>
                        <?php if ($r['location_verified']): ?>
                            <span style="color:#00a87a; font-size:0.8rem;">✅ Within Campus</span>
                        <?php else: ?>
                            <span style="color:#888; font-size:0.8rem;">� Recorded</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="s-badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="s-empty">
            <div class="ei">📭</div>
            <p>No records found for the selected filters.</p>
        </div>
    <?php endif; ?>
</div>

</main>
</body>
</html>
