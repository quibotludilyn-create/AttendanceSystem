<?php
require_once 'layout.php';

$filter_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$filter_period  = $_GET['period']  ?? 'daily';
$filter_date    = $_GET['date']    ?? date('Y-m-d');
$filter_month   = $_GET['month']   ?? date('Y-m');
$filter_status  = $_GET['status']  ?? '';

$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY subject_name");

switch ($filter_period) {
    case 'daily':
        $safe_date = $conn->real_escape_string($filter_date);
        $date_cond = "a.date = '$safe_date'";
        $period_label = date('F d, Y', strtotime($filter_date));
        break;
    case 'weekly':
        $safe_date  = $conn->real_escape_string($filter_date);
        $week_start = date('Y-m-d', strtotime('monday this week', strtotime($safe_date)));
        $week_end   = date('Y-m-d', strtotime('sunday this week', strtotime($safe_date)));
        $date_cond  = "a.date BETWEEN '$week_start' AND '$week_end'";
        $period_label = "Week of ".date('M d', strtotime($week_start))." – ".date('M d, Y', strtotime($week_end));
        break;
    case 'monthly':
        $safe_month = $conn->real_escape_string($filter_month);
        $date_cond  = "DATE_FORMAT(a.date,'%Y-%m')='$safe_month'";
        $period_label = date('F Y', strtotime($safe_month.'-01'));
        break;
    default:
        $date_cond    = "a.date='".date('Y-m-d')."'";
        $period_label = date('F d, Y');
}

$status_cond  = $filter_status ? "AND a.status='".$conn->real_escape_string($filter_status)."'" : '';
$subject_cond = $filter_subject ? "AND a.subject_id=$filter_subject" : "AND a.teacher_id=$teacher_id";

$records = $conn->query("
    SELECT s.student_id, s.full_name, s.course, s.year_level,
           sub.subject_name, sub.section, a.date, a.time_in, a.status, a.is_manual, a.manual_reason
    FROM attendance a JOIN students s ON a.student_id=s.id JOIN subjects sub ON a.subject_id=sub.id
    WHERE $date_cond $status_cond $subject_cond
    ORDER BY a.date DESC, s.full_name ASC
");

$summary = $conn->query("
    SELECT COUNT(*) as total, SUM(a.status='present') as present,
           SUM(a.status='late') as late, SUM(a.status='absent') as absent,
           SUM(a.is_manual=1) as manual_count
    FROM attendance a WHERE $date_cond $status_cond $subject_cond
")->fetch_assoc();

$per_student = null;
if ($filter_subject) {
    $per_student = $conn->query("
        SELECT s.student_id, s.full_name, COUNT(a.id) as total_sessions,
               SUM(a.status='present') as present, SUM(a.status='late') as late,
               SUM(a.status='absent') as absent,
               ROUND((SUM(a.status='present' OR a.status='late')/NULLIF(COUNT(a.id),0))*100,1) as pct
        FROM enrollments e JOIN students s ON e.student_id=s.id
        LEFT JOIN attendance a ON a.student_id=s.id AND a.subject_id=$filter_subject AND $date_cond
        WHERE e.subject_id=$filter_subject GROUP BY s.id ORDER BY pct DESC
    ");
}
?>

<!-- Filter -->
<div class="card">
    <div class="card-title">🔍 Filter Reports</div>
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:1;min-width:150px;">
            <label>Subject</label>
            <select name="subject" class="form-control">
                <option value="">All My Subjects</option>
                <?php $subjects->data_seek(0); while($s = $subjects->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $filter_subject==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['section']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:120px;">
            <label>Period</label>
            <select name="period" class="form-control" onchange="toggleDate(this.value)">
                <option value="daily"   <?= $filter_period==='daily'  ?'selected':'' ?>>Daily</option>
                <option value="weekly"  <?= $filter_period==='weekly' ?'selected':'' ?>>Weekly</option>
                <option value="monthly" <?= $filter_period==='monthly'?'selected':'' ?>>Monthly</option>
            </select>
        </div>
        <div class="form-group" style="margin:0;" id="date-field">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <div class="form-group" style="margin:0;display:none;" id="month-field">
            <label>Month</label>
            <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filter_month) ?>">
        </div>
        <div class="form-group" style="margin:0;">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All</option>
                <option value="present" <?= $filter_status==='present'?'selected':'' ?>>Present</option>
                <option value="late"    <?= $filter_status==='late'   ?'selected':'' ?>>Late</option>
                <option value="absent"  <?= $filter_status==='absent' ?'selected':'' ?>>Absent</option>
            </select>
        </div>
        <div style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <button type="button" onclick="printReport()" class="btn btn-success">🖨️ Print</button>
        </div>
    </form>
</div>

<!-- Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="stat-card blue">  <div class="stat-icon" style="background:#e3f2fd;">📋</div><div class="stat-info"><h3><?= $summary['total']??0 ?></h3><p>Records</p></div></div>
    <div class="stat-card green"> <div class="stat-icon" style="background:#e8f5e9;">✅</div><div class="stat-info"><h3><?= $summary['present']??0 ?></h3><p>Present</p></div></div>
    <div class="stat-card orange"><div class="stat-icon" style="background:#fff3e0;">⏰</div><div class="stat-info"><h3><?= $summary['late']??0 ?></h3><p>Late</p></div></div>
    <div class="stat-card red">   <div class="stat-icon" style="background:#ffebee;">❌</div><div class="stat-info"><h3><?= $summary['absent']??0 ?></h3><p>Absent</p></div></div>
    <div class="stat-card" style="border-left-color:#9c27b0;"><div class="stat-icon" style="background:#f3e5f5;">✏️</div><div class="stat-info"><h3><?= $summary['manual_count']??0 ?></h3><p>Manual</p></div></div>
</div>

<!-- Records Table -->
<div class="card" id="print-area">
    <div class="card-title">📄 Attendance Records — <?= htmlspecialchars($period_label) ?></div>
    <?php if ($records && $records->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Subject</th><th>Section</th><th>Date</th><th>Time</th><th>Status</th><th>Type</th></tr></thead>
            <tbody>
            <?php $i=1; while($r = $records->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($r['student_id']) ?></td>
                <td><?= htmlspecialchars($r['full_name']) ?></td>
                <td><?= htmlspecialchars($r['subject_name']) ?></td>
                <td><?= htmlspecialchars($r['section']) ?></td>
                <td><?= date('M d, Y', strtotime($r['date'])) ?></td>
                <td><?= $r['time_in'] ?></td>
                <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
                <td><span class="badge" style="background:<?= $r['is_manual']?'#f5f5f5':'#e8f5e9' ?>;color:<?= $r['is_manual']?'#777':'#2e7d32' ?>;"><?= $r['is_manual']?'Manual':'Face' ?></span></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No records found.</p></div>
    <?php endif; ?>
</div>

<!-- Attendance % per student -->
<?php if ($per_student && $per_student->num_rows > 0): ?>
<div class="card">
    <div class="card-title">📊 Attendance Rate per Student</div>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Student ID</th><th>Name</th><th>Sessions</th><th>Present</th><th>Late</th><th>Absent</th><th>Rate</th></tr></thead>
            <tbody>
            <?php while($p = $per_student->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($p['student_id']) ?></td>
                <td><?= htmlspecialchars($p['full_name']) ?></td>
                <td><?= $p['total_sessions'] ?></td>
                <td><span class="badge badge-present"><?= $p['present'] ?></span></td>
                <td><span class="badge badge-late"><?= $p['late'] ?></span></td>
                <td><span class="badge badge-absent"><?= $p['absent'] ?></span></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="flex:1;background:#eee;border-radius:3px;height:6px;overflow:hidden;">
                            <div style="height:100%;width:<?= $p['pct'] ?>%;background:<?= $p['pct']>=75?'#2e7d32':($p['pct']>=50?'#ffa726':'#ef5350') ?>;border-radius:3px;"></div>
                        </div>
                        <span style="font-weight:700;font-size:0.82rem;color:<?= $p['pct']>=75?'#2e7d32':($p['pct']>=50?'#e65100':'#e53935') ?>;"><?= $p['pct'] ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function toggleDate(val) {
    document.getElementById('date-field').style.display  = val==='monthly'?'none':'block';
    document.getElementById('month-field').style.display = val==='monthly'?'block':'none';
}
toggleDate('<?= $filter_period ?>');
function printReport() {
    const content = document.getElementById('print-area').innerHTML;
    const win = window.open('','','width=900,height=700');
    win.document.write(`<html><head><title>Attendance Report</title><style>body{font-family:Arial,sans-serif;font-size:12px;}table{width:100%;border-collapse:collapse;}th{background:#1b5e20;color:white;padding:8px;}td{padding:7px;border-bottom:1px solid #ddd;}</style></head><body><h2 style="color:#1b5e20;">Attendance Report — <?= htmlspecialchars($period_label) ?></h2>${content}</body></html>`);
    win.document.close(); win.print();
}
</script>

</main>
</div>
</body>
</html>
