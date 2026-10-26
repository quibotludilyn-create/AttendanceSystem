<?php
require_once 'layout.php';
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_subject'])) {
    $code = $conn->real_escape_string(trim($_POST['subject_code']));
    $name = $conn->real_escape_string(trim($_POST['subject_name']));
    $sec  = $conn->real_escape_string(trim($_POST['section']));
    $crs  = $conn->real_escape_string(trim($_POST['course']));
    $yr   = intval($_POST['year_level']);
    $room = $conn->real_escape_string(trim($_POST['room']));
    $sy   = $conn->real_escape_string(trim($_POST['school_year']));
    $sem  = $conn->real_escape_string($_POST['semester']);
    $conn->query("INSERT INTO subjects (subject_code,subject_name,teacher_id,section,course,year_level,room,school_year,semester)
                  VALUES ('$code','$name',$teacher_id,'$sec','$crs',$yr,'$room','$sy','$sem')");
    $msg = "Subject '$name' added successfully.";
}

if (isset($_GET['delete_subject'])) {
    $sid = intval($_GET['delete_subject']);
    $conn->query("DELETE FROM subjects WHERE id=$sid AND teacher_id=$teacher_id");
    $msg = "Subject deleted.";
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['enroll_student'])) {
    $sub_id = intval($_POST['subject_id']);
    $stu_id = intval($_POST['student_id']);
    $chk = $conn->query("SELECT id FROM enrollments WHERE subject_id=$sub_id AND student_id=$stu_id");
    if ($chk->num_rows > 0) { $err = "Student already enrolled."; }
    else {
        $conn->query("INSERT INTO enrollments (subject_id,student_id) VALUES ($sub_id,$stu_id)");
        $msg = "Student enrolled successfully.";
    }
}

if (isset($_GET['unenroll'])) {
    $eid = intval($_GET['unenroll']);
    $conn->query("DELETE FROM enrollments WHERE id=$eid");
    $msg = "Student removed from class.";
}

$view_subject = isset($_GET['view']) ? intval($_GET['view']) : 0;
$view_data    = null;
if ($view_subject) {
    $view_data = $conn->query("SELECT * FROM subjects WHERE id=$view_subject AND teacher_id=$teacher_id")->fetch_assoc();
}

$subjects     = $conn->query("SELECT s.*, COUNT(e.id) as enrolled FROM subjects s LEFT JOIN enrollments e ON e.subject_id=s.id WHERE s.teacher_id=$teacher_id AND s.is_active=1 GROUP BY s.id ORDER BY s.subject_name");
$all_students = $conn->query("SELECT id, student_id, full_name, course, year_level FROM students WHERE is_active=1 ORDER BY full_name");
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid-2">
<!-- Add Subject -->
<div class="card">
    <div class="card-title">➕ Add New Subject</div>
    <form method="POST">
        <div class="form-group">
            <label>Subject Code *</label>
            <input type="text" name="subject_code" class="form-control" placeholder="e.g. CS101" required>
        </div>
        <div class="form-group">
            <label>Subject Name *</label>
            <input type="text" name="subject_name" class="form-control" placeholder="e.g. Introduction to Programming" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Section *</label>
                <input type="text" name="section" class="form-control" placeholder="e.g. A" required>
            </div>
            <div class="form-group">
                <label>Course</label>
                <input type="text" name="course" class="form-control" placeholder="BSIT">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Year Level</label>
                <select name="year_level" class="form-control">
                    <option>1</option><option>2</option><option>3</option><option>4</option>
                </select>
            </div>
            <div class="form-group">
                <label>Room</label>
                <input type="text" name="room" class="form-control" placeholder="Room 201">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>School Year</label>
                <input type="text" name="school_year" class="form-control" value="<?= date('Y').'-'.(date('Y')+1) ?>">
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" class="form-control">
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>
        </div>
        <button type="submit" name="add_subject" class="btn btn-primary btn-block">➕ Add Subject</button>
    </form>
</div>

<!-- Subject List -->
<div class="card">
    <div class="card-title">📚 My Subjects (<?= $subjects->num_rows ?>)</div>
    <?php if ($subjects->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Code</th><th>Subject</th><th>Section</th><th>Year</th><th>Enrolled</th><th>Actions</th></tr></thead>
            <tbody>
            <?php while($s = $subjects->fetch_assoc()): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['subject_code']) ?></strong></td>
                <td><?= htmlspecialchars($s['subject_name']) ?></td>
                <td><?= htmlspecialchars($s['section']) ?></td>
                <td>Year <?= $s['year_level'] ?></td>
                <td><span class="badge badge-present"><?= $s['enrolled'] ?> students</span></td>
                <td style="white-space:nowrap;">
                    <a href="?view=<?= $s['id'] ?>" class="btn btn-primary btn-sm">👥 Students</a>
                    <a href="?delete_subject=<?= $s['id'] ?>" onclick="return confirm('Delete <?= htmlspecialchars($s['subject_name'], ENT_QUOTES) ?>?')" class="btn btn-danger btn-sm">🗑️</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><div class="empty-icon">📚</div><p>No subjects yet. Add one using the form.</p></div>
    <?php endif; ?>
</div>
</div>

<?php if ($view_data): ?>
<div class="card">
    <div class="card-title">
        👥 Students in: <?= htmlspecialchars($view_data['subject_name']) ?> — Section <?= htmlspecialchars($view_data['section']) ?>
        <a href="my_classes.php" style="margin-left:auto;font-size:0.78rem;color:#aaa;font-weight:400;">✕ Close</a>
    </div>

    <form method="POST" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
        <input type="hidden" name="subject_id" value="<?= $view_subject ?>">
        <select name="student_id" class="form-control" style="flex:1;min-width:200px;" required>
            <option value="">— Select student to enroll —</option>
            <?php
            $enrolled_ids = [];
            $er = $conn->query("SELECT student_id FROM enrollments WHERE subject_id=$view_subject");
            while($e = $er->fetch_assoc()) $enrolled_ids[] = $e['student_id'];
            $all_students->data_seek(0);
            while($stu = $all_students->fetch_assoc()):
                if (in_array($stu['id'], $enrolled_ids)) continue;
            ?>
            <option value="<?= $stu['id'] ?>"><?= htmlspecialchars($stu['full_name']) ?> (<?= htmlspecialchars($stu['student_id']) ?>)</option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="enroll_student" class="btn btn-success">➕ Enroll</button>
    </form>

    <?php
    $enrolled = $conn->query("
        SELECT e.id as eid, st.student_id, st.full_name, st.course, st.year_level,
               (SELECT COUNT(*) FROM attendance a WHERE a.student_id=st.id AND a.subject_id=$view_subject AND a.status='present') as present_count
        FROM enrollments e JOIN students st ON e.student_id=st.id
        WHERE e.subject_id=$view_subject ORDER BY st.full_name
    ");
    ?>
    <?php if ($enrolled->num_rows > 0): ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>#</th><th>Student ID</th><th>Name</th><th>Course</th><th>Attendance</th><th>Action</th></tr></thead>
            <tbody>
            <?php $i=1; while($e = $enrolled->fetch_assoc()): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($e['student_id']) ?></td>
                <td><?= htmlspecialchars($e['full_name']) ?></td>
                <td><?= htmlspecialchars($e['course']) ?> Yr<?= $e['year_level'] ?></td>
                <td><span class="badge badge-present"><?= $e['present_count'] ?> present</span></td>
                <td>
                    <a href="?view=<?= $view_subject ?>&unenroll=<?= $e['eid'] ?>" onclick="return confirm('Remove student?')" class="btn btn-danger btn-sm">Remove</a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:20px 0;"><div class="empty-icon">👥</div><p>No students enrolled yet.</p></div>
    <?php endif; ?>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>
