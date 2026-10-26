<?php
require_once 'layout.php';
require_once '../config/database.php';
$conn = getConnection();

// Filters
$filter_date    = $_GET['date']    ?? date('Y-m-d');
$filter_subject = $_GET['subject'] ?? '';
$filter_status  = $_GET['status']  ?? '';

$where = ["a.date = '$filter_date'"];
if ($filter_subject) $where[] = "a.subject LIKE '%" . $conn->real_escape_string($filter_subject) . "%'";
if ($filter_status)  $where[] = "a.status = '" . $conn->real_escape_string($filter_status) . "'";

$where_sql = implode(' AND ', $where);

$records = $conn->query("
    SELECT s.student_id, s.full_name, s.course, s.year_level, s.section,
           a.time_in, a.status, a.subject, a.date
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE $where_sql
    ORDER BY s.full_name ASC
");

// Summary counts
$total   = $records ? $records->num_rows : 0;
$present = 0; $late = 0; $absent = 0;
$rows_data = [];
if ($records) {
    while ($r = $records->fetch_assoc()) {
        $rows_data[] = $r;
        if ($r['status'] === 'present') $present++;
        elseif ($r['status'] === 'late') $late++;
        else $absent++;
    }
}
?>

<!-- Filter Form -->
<div class="card">
    <div class="card-title">🔍 Filter Attendance</div>
    <form method="GET" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
            <label>Date</label>
            <input type="date" name="date" class="form-control"
                   value="<?= htmlspecialchars($filter_date) ?>">
        </div>
        <div class="form-group" style="margin:0; flex:1; min-width:150px;">
            <label>Subject</label>
            <input type="text" name="subject" class="form-control"
                   placeholder="All subjects" value="<?= htmlspecialchars($filter_subject) ?>">
        </div>
        <div class="form-group" style="margin:0; flex:1; min-width:130px;">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="present" <?= $filter_status === 'present' ? 'selected' : '' ?>>Present</option>
                <option value="late"    <?= $filter_status === 'late'    ? 'selected' : '' ?>>Late</option>
                <option value="absent"  <?= $filter_status === 'absent'  ? 'selected' : '' ?>>Absent</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="reports.php" class="btn btn-info">↺ Reset</a>
            <button type="button" onclick="printReport()" class="btn btn-success">🖨️ Print</button>
        </div>
    </form>
</div>

<!-- Summary Stats -->
<div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
    <div class="stat-card blue">
        <div class="stat-icon" style="background:#e8eaf6">📋</div>
        <div class="stat-info"><h3><?= $total ?></h3><p>Total Records</p></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon" style="background:#e8f5e9">✅</div>
        <div class="stat-info"><h3><?= $present ?></h3><p>Present</p></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon" style="background:#fff3e0">⏰</div>
        <div class="stat-info"><h3><?= $late ?></h3><p>Late</p></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon" style="background:#ffebee">❌</div>
        <div class="stat-info"><h3><?= $absent ?></h3><p>Absent</p></div>
    </div>
</div>

<!-- Records Table -->
<div class="card" id="print-area">
    <div class="card-title">
        📋 Attendance Records — <?= date('F d, Y', strtotime($filter_date)) ?>
        <?= $filter_subject ? " | $filter_subject" : '' ?>
    </div>

    <?php if (count($rows_data) > 0): ?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student ID</th>
                    <th>Full Name</th>
                    <th>Course</th>
                    <th>Year / Sec</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows_data as $i => $row): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['course']) ?></td>
                    <td>Year <?= $row['year_level'] ?> - <?= htmlspecialchars($row['section']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= $row['date'] ?></td>
                    <td><?= $row['time_in'] ?></td>
                    <td>
                        <span class="badge badge-<?= $row['status'] ?>">
                            <?= ucfirst($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No attendance records found for the selected filters.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function printReport() {
    const printContent = document.getElementById('print-area').innerHTML;
    const win = window.open('', '', 'width=900,height=700');
    win.document.write(`
        <html><head>
        <title>Attendance Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background: #1a237e; color: white; padding: 8px; }
            td { padding: 7px 8px; border-bottom: 1px solid #ddd; }
            .card-title { font-size: 1rem; font-weight: bold; margin-bottom: 10px; }
            .badge { padding: 2px 8px; border-radius: 10px; font-size: 11px; }
            .badge-present { background: #e8f5e9; color: #2e7d32; }
            .badge-late    { background: #fff3e0; color: #e65100; }
            .badge-absent  { background: #ffebee; color: #c62828; }
        </style>
        </head><body>
        <h2 style="text-align:center;">Face Recognition Attendance System</h2>
        <h3 style="text-align:center;">Attendance Report</h3>
        ${printContent}
        </body></html>
    `);
    win.document.close();
    win.print();
}
</script>

    </main>
</div>
</body>
</html>
