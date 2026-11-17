<?php
require_once 'layout.php';
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_schedule'])) {
    $sub_id = intval($_POST['subject_id']);
    $days_selected = $_POST['days_of_week'] ?? [];
    $start  = $conn->real_escape_string($_POST['start_time']);
    $end    = $conn->real_escape_string($_POST['end_time']);
    $room   = $conn->real_escape_string(trim($_POST['room'] ?? ''));

    if (empty($days_selected)) {
        $err = "Please select at least one day.";
    } elseif ($start >= $end) {
        $err = "End time must be after start time.";
    } else {
        $added = 0;
        foreach ($days_selected as $day) {
            $day = $conn->real_escape_string($day);
            $conflict = $conn->query("
                SELECT sch.*, sub.subject_name, sub.section FROM schedules sch
                JOIN subjects sub ON sch.subject_id=sub.id
                WHERE sub.teacher_id=$teacher_id AND sch.day_of_week='$day'
                  AND (('$start'>=sch.start_time AND '$start'<sch.end_time) OR
                       ('$end'>sch.start_time AND '$end'<=sch.end_time) OR
                       ('$start'<=sch.start_time AND '$end'>=sch.end_time))
            ");
            if ($conflict && $conflict->num_rows > 0) {
                $c = $conflict->fetch_assoc();
                $err .= "Conflict on $day: {$c['subject_name']} ({$c['section']}) at {$c['start_time']}–{$c['end_time']}. ";
                continue;
            }
            $conn->query("INSERT INTO schedules (subject_id,day_of_week,start_time,end_time,room) VALUES ($sub_id,'$day','$start','$end','$room')");
            $added++;
        }
        if ($added > 0) {
            if ($room) $conn->query("UPDATE subjects SET room='$room' WHERE id=$sub_id AND teacher_id=$teacher_id");
            $msg = "$added schedule(s) added successfully.";
        }
    }
}

if (isset($_GET['delete'])) {
    $did = intval($_GET['delete']);
    $conn->query("DELETE FROM schedules WHERE id=$did");
    $msg = "Schedule deleted.";
}

// Delete group of schedules (same subject+time+room, multiple days)
if (isset($_GET['delete_group'])) {
    $ids = array_map('intval', explode(',', $_GET['delete_group']));
    $ids = array_filter($ids);
    if (!empty($ids)) {
        $ids_str = implode(',', $ids);
        $conn->query("DELETE FROM schedules WHERE id IN ($ids_str)");
        $msg = "Schedule group deleted.";
    }
    header('Location: schedules.php');
    exit;
}

$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY subject_name");
$schedules = $conn->query("
    SELECT sch.*, sub.subject_name, sub.section, sub.course, sub.year_level
    FROM schedules sch JOIN subjects sub ON sch.subject_id=sub.id
    WHERE sub.teacher_id=$teacher_id
    ORDER BY FIELD(sch.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), sch.start_time
");
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// Group schedules by subject+time+room for merged display
$grouped = [];
$sched_by_day = [];
while ($s = $schedules->fetch_assoc()) {
    $sched_by_day[$s['day_of_week']][] = $s;
    // Create group key: subject_id + start_time + end_time + room
    $gkey = $s['subject_id'] . '|' . $s['start_time'] . '|' . $s['end_time'] . '|' . $s['room'];
    if (!isset($grouped[$gkey])) {
        $grouped[$gkey] = $s;
        $grouped[$gkey]['days'] = [];
        $grouped[$gkey]['ids'] = [];
    }
    $grouped[$gkey]['days'][] = $s['day_of_week'];
    $grouped[$gkey]['ids'][]  = $s['id'];
}
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<div class="grid-2">
<!-- Add Schedule -->
<div class="card">
    <div class="card-title">➕ Add Schedule</div>
    <div class="alert alert-warning" style="font-size:0.82rem;">
        ⚠️ Conflict detection enabled — overlapping schedules will be blocked.
    </div>
    <form method="POST">
        <div class="form-group">
            <label>Subject *</label>
            <select name="subject_id" class="form-control" required>
                <option value="">— Select subject —</option>
                <?php $subjects->data_seek(0); while($s = $subjects->fetch_assoc()): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?> (<?= htmlspecialchars($s['section']) ?>)</option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Days *</label>
            <div style="position:relative;">
                <div id="days-toggle" onclick="toggleDaysDropdown()"
                    style="border:1.5px solid #dde0ec;border-radius:9px;padding:10px 14px;background:#fafbff;cursor:pointer;display:flex;justify-content:space-between;align-items:center;font-size:0.92rem;">
                    <span id="days-label" style="color:#aaa;">— Select days —</span>
                    <span>▾</span>
                </div>
                <div id="days-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:white;border:1.5px solid #dde0ec;border-radius:9px;z-index:100;padding:10px;box-shadow:0 4px 16px rgba(0,0,0,0.1);margin-top:4px;">
                    <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                    <label style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:7px;cursor:pointer;font-size:0.88rem;transition:background 0.15s;"
                           onmouseover="this.style.background='#f1f8e9'" onmouseout="this.style.background='transparent'">
                        <input type="checkbox" name="days_of_week[]" value="<?= $d ?>"
                               onchange="updateDaysLabel()"
                               style="accent-color:#2e7d32;width:16px;height:16px;">
                        <?= $d ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group"><label>Start Time *</label><input type="time" name="start_time" class="form-control" required></div>
            <div class="form-group"><label>End Time *</label><input type="time" name="end_time" class="form-control" required></div>
        </div>
        <div class="form-group"><label>Room</label><input type="text" name="room" class="form-control" placeholder="e.g. Room 201"></div>
        <button type="submit" name="add_schedule" class="btn btn-primary btn-block">➕ Add Schedule</button>
    </form>
</div>

<!-- Weekly Schedule -->
<div class="card">
    <div class="card-title">📅 Weekly Schedule</div>
    <?php if (empty($grouped)): ?>
    <div class="empty-state"><div class="empty-icon">🗓️</div><p>No schedules yet.</p></div>
    <?php else: ?>
    <?php 
    $dayOrder = ['Monday'=>0,'Tuesday'=>1,'Wednesday'=>2,'Thursday'=>3,'Friday'=>4,'Saturday'=>5,'Sunday'=>6];
    $dayAbbr  = ['Monday'=>'Mon','Tuesday'=>'Tue','Wednesday'=>'Wed','Thursday'=>'Thu','Friday'=>'Fri','Saturday'=>'Sat','Sunday'=>'Sun'];
    foreach ($grouped as $gkey => $sch): 
        // Sort days in week order
        usort($sch['days'], fn($a,$b) => $dayOrder[$a] - $dayOrder[$b]);
        $daysStr = implode(', ', array_map(fn($d) => $dayAbbr[$d], $sch['days']));
        $idsStr  = implode(',', $sch['ids']);
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:#f9fafb;border-radius:8px;margin-bottom:8px;border-left:3px solid #2e7d32;">
        <div>
            <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($sch['subject_name']) ?></div>
            <div style="font-size:0.76rem;color:#666;">
                Section <?= htmlspecialchars($sch['section']) ?> &nbsp;·&nbsp;
                📅 <strong><?= $daysStr ?></strong> &nbsp;·&nbsp;
                <?= date('g:i A', strtotime($sch['start_time'])) ?> – <?= date('g:i A', strtotime($sch['end_time'])) ?>
                <?php if ($sch['room']): ?>&nbsp;·&nbsp; 🏫 <?= htmlspecialchars($sch['room']) ?><?php endif; ?>
            </div>
        </div>
        <a href="?delete_group=<?= urlencode($idsStr) ?>" 
           onclick="return confirm('Delete all <?= count($sch['days']) ?> day(s) for this schedule?')" 
           class="btn btn-danger btn-sm">🗑️</a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>
<script>
function toggleDaysDropdown() {
    const dd = document.getElementById('days-dropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}

function updateDaysLabel() {
    const checked = [...document.querySelectorAll('input[name="days_of_week[]"]:checked')].map(cb => cb.value.substring(0,3));
    const label = document.getElementById('days-label');
    if (checked.length === 0) {
        label.textContent = '— Select days —';
        label.style.color = '#aaa';
    } else {
        label.textContent = checked.join(', ');
        label.style.color = '#333';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('days-toggle')?.closest('.form-group');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('days-dropdown').style.display = 'none';
    }
});
</script>

</main>
</div>
</body>
</html>
