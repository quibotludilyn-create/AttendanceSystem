<?php
require_once 'layout.php';
$msg = ''; $err = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['manual_attendance'])) {
    $stu_id  = intval($_POST['student_id']);
    $sub_id  = intval($_POST['subject_id']);
    $status  = in_array($_POST['status'],['present','late','absent']) ? $_POST['status'] : 'present';
    $reason  = $conn->real_escape_string(trim($_POST['reason'] ?? ''));
    $time_in = date('H:i:s');
    $date    = $conn->real_escape_string($_POST['date'] ?? $today);

    $chk = $conn->query("SELECT id FROM attendance WHERE student_id=$stu_id AND date='$date' AND subject_id=$sub_id");
    if ($chk->num_rows > 0) {
        $existing = $chk->fetch_assoc();
        $conn->query("UPDATE attendance SET status='$status',is_manual=1,manual_by=$teacher_id,manual_reason='$reason',time_in='$time_in' WHERE id={$existing['id']}");
        $msg = "Attendance updated manually.";
    } else {
        $conn->query("INSERT INTO attendance (student_id,date,time_in,status,subject_id,teacher_id,is_manual,manual_by,manual_reason,location_verified)
                      VALUES ($stu_id,'$date','$time_in','$status',$sub_id,$teacher_id,1,$teacher_id,'$reason',0)");
        $msg = "Manual attendance recorded.";
    }
    $stu = $conn->query("SELECT full_name FROM students WHERE id=$stu_id")->fetch_assoc();
    $conn->query("INSERT INTO notifications (recipient_type,recipient_id,title,message,type)
                  VALUES ('student',$stu_id,'Attendance Updated',
                  'Your attendance was manually marked as $status by your teacher for ".date('F d, Y', strtotime($date))."','manual')");
}

$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY subject_name");
$selected_subject = isset($_GET['subject']) ? intval($_GET['subject']) : 0;
$selected_date    = $_GET['date'] ?? $today;

$enrolled_students = null;
$attendance_records = [];
if ($selected_subject) {
    $enrolled_students = $conn->query("
        SELECT s.id, s.student_id, s.full_name, s.course, s.year_level
        FROM enrollments e JOIN students s ON e.student_id=s.id
        WHERE e.subject_id=$selected_subject ORDER BY s.full_name
    ");
    $att_res = $conn->query("
        SELECT a.*, s.full_name FROM attendance a JOIN students s ON a.student_id=s.id
        WHERE a.subject_id=$selected_subject AND a.date='$selected_date' AND a.teacher_id=$teacher_id
    ");
    while ($ar = $att_res->fetch_assoc()) $attendance_records[$ar['student_id']] = $ar;
}
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Filter -->
<div class="card">
    <div class="card-title">🔍 Select Class</div>
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
        <div class="form-group" style="margin:0;flex:2;min-width:200px;">
            <label>Subject / Class</label>
            <select name="subject" class="form-control" onchange="this.form.submit()">
                <option value="">— Choose a subject —</option>
                <?php $subjects->data_seek(0); while($s = $subjects->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>" <?= $selected_subject==$s['id']?'selected':'' ?>>
                    <?= htmlspecialchars($s['subject_name']) ?> — <?= htmlspecialchars($s['section']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;min-width:150px;">
            <label>Date</label>
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>">
        </div>
        <div><button type="submit" class="btn btn-primary">🔍 Load</button></div>
    </form>
</div>

<?php if ($selected_subject && $enrolled_students): ?>
<div class="grid-2">

<!-- Attendance List -->
<div class="card">
    <div class="card-title">
        👥 Student Attendance — <?= date('F d, Y', strtotime($selected_date)) ?>
        <span style="margin-left:auto;font-size:0.78rem;font-weight:400;color:#888;"><?= $enrolled_students->num_rows ?> enrolled</span>
    </div>
    <?php if ($enrolled_students->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Name</th><th>Status</th><th>Time</th><th>Type</th></tr></thead>
            <tbody>
            <?php $enrolled_students->data_seek(0); while($st = $enrolled_students->fetch_assoc()): ?>
            <?php $att = $attendance_records[$st['id']] ?? null; ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($st['full_name']) ?></div>
                    <div style="font-size:0.74rem;color:#aaa;"><?= htmlspecialchars($st['student_id']) ?></div>
                </td>
                <td>
                    <?php if ($att): ?>
                    <span class="badge badge-<?= $att['status'] ?>"><?= ucfirst($att['status']) ?></span>
                    <?php else: ?>
                    <span class="badge badge-absent">Absent</span>
                    <?php endif; ?>
                </td>
                <td><?= $att ? $att['time_in'] : '—' ?></td>
                <td><?php if ($att): ?><span class="badge" style="background:<?= $att['is_manual']?'#f5f5f5':'#e8f5e9' ?>;color:<?= $att['is_manual']?'#777':'#2e7d32' ?>;"><?= $att['is_manual']?'Manual':'Face' ?></span><?php endif; ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">👥</div><p>No students enrolled.</p></div>
    <?php endif; ?>
</div>

<!-- Manual Override -->
<div class="card">
    <div class="card-title">✏️ Manual Attendance Override
        <span style="margin-left:8px;background:#fff3e0;color:#e65100;padding:3px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;">For face recognition failures</span>
    </div>
    <form method="POST">
        <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
        <div class="form-group">
            <label>Student *</label>
            <select name="student_id" class="form-control" required>
                <option value="">— Select student —</option>
                <?php $enrolled_students->data_seek(0); while($st = $enrolled_students->fetch_assoc()): ?>
                <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['full_name']) ?> (<?= htmlspecialchars($st['student_id']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" required>
                    <option value="present">✅ Present</option>
                    <option value="late">⏰ Late</option>
                    <option value="absent">❌ Absent</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Reason *</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Face recognition failed due to poor lighting" required>
        </div>
        <button type="submit" name="manual_attendance" class="btn btn-warning btn-block">✏️ Save Manual Attendance</button>
    </form>
</div>
</div>
<?php else: ?>
<div class="card"><div class="empty-state"><div class="empty-icon">📷</div><p>Select a subject and date above to manage attendance.</p></div></div>
<?php endif; ?>

</main>
</div>
</body>
</html>
