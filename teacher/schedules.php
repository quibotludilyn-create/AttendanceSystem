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

$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacher_id AND is_active=1 ORDER BY subject_name");
$schedules = $conn->query("
    SELECT sch.*, sub.subject_name, sub.section, sub.course, sub.year_level
    FROM schedules sch JOIN subjects sub ON sch.subject_id=sub.id
    WHERE sub.teacher_id=$teacher_id
    ORDER BY FIELD(sch.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), sch.start_time
");
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$sched_by_day = [];
while ($s = $schedules->fetch_assoc()) $sched_by_day[$s['day_of_week']][] = $s;
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
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:6px;">
                <?php 
                $day_presets = [
                    'MWF' => ['Monday','Wednesday','Friday'],
                    'TTH' => ['Tuesday','Thursday'],
                    'Daily' => ['Monday','Tuesday','Wednesday','Thursday','Friday'],
                    'Sat' => ['Saturday'],
                ];
                foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                <label style="display:flex;align-items:center;gap:6px;font-size:0.85rem;cursor:pointer;background:#f4f6fb;padding:6px 10px;border-radius:8px;border:1.5px solid #eee;transition:all 0.2s;" 
                       onmouseover="this.style.borderColor='#2e7d32'" onmouseout="this.style.borderColor='#eee'">
                    <input type="checkbox" name="days_of_week[]" value="<?= $d ?>" 
                           style="accent-color:#2e7d32;width:15px;height:15px;">
                    <?= substr($d, 0, 3) ?>
                </label>
                <?php endforeach; ?>
            </div>
            <!-- Quick select presets -->
            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                <span style="font-size:0.75rem;color:#888;align-self:center;">Quick:</span>
                <button type="button" onclick="setDays(['Monday','Wednesday','Friday'])" class="btn btn-sm btn-info">M-W-F</button>
                <button type="button" onclick="setDays(['Tuesday','Thursday'])" class="btn btn-sm btn-info">T-TH</button>
                <button type="button" onclick="setDays(['Monday','Tuesday','Wednesday','Thursday','Friday'])" class="btn btn-sm btn-info">Mon–Fri</button>
                <button type="button" onclick="setDays(['Saturday'])" class="btn btn-sm btn-info">Sat</button>
                <button type="button" onclick="setDays([])" class="btn btn-sm btn-secondary">Clear</button>
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
    <?php if (empty($sched_by_day)): ?>
    <div class="empty-state"><div class="empty-icon">🗓️</div><p>No schedules yet.</p></div>
    <?php else: ?>
    <?php foreach($days as $day): ?>
    <?php if (!isset($sched_by_day[$day])) continue; ?>
    <div style="margin-bottom:16px;">
        <div style="font-weight:700;font-size:0.82rem;color:#1b5e20;background:#e8f5e9;padding:7px 12px;border-radius:8px;margin-bottom:8px;">
            📅 <?= $day ?>
        </div>
        <?php foreach($sched_by_day[$day] as $sch): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:#f9fafb;border-radius:8px;margin-bottom:6px;border-left:3px solid #2e7d32;">
            <div>
                <div style="font-weight:700;font-size:0.88rem;"><?= htmlspecialchars($sch['subject_name']) ?></div>
                <div style="font-size:0.76rem;color:#666;">
                    Section <?= htmlspecialchars($sch['section']) ?> &nbsp;·&nbsp;
                    <?= date('g:i A', strtotime($sch['start_time'])) ?> – <?= date('g:i A', strtotime($sch['end_time'])) ?>
                    <?php if ($sch['room']): ?>&nbsp;·&nbsp; 🏫 <?= htmlspecialchars($sch['room']) ?><?php endif; ?>
                </div>
            </div>
            <a href="?delete=<?= $sch['id'] ?>" onclick="return confirm('Delete this schedule?')" class="btn btn-danger btn-sm">🗑️</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</div>
<script>
function setDays(days) {
    document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => {
        cb.checked = days.includes(cb.value);
        cb.closest('label').style.borderColor = cb.checked ? '#2e7d32' : '#eee';
        cb.closest('label').style.background  = cb.checked ? '#e8f5e9' : '#f4f6fb';
    });
}
document.querySelectorAll('input[name="days_of_week[]"]').forEach(cb => {
    cb.addEventListener('change', function() {
        this.closest('label').style.borderColor = this.checked ? '#2e7d32' : '#eee';
        this.closest('label').style.background  = this.checked ? '#e8f5e9' : '#f4f6fb';
    });
});
</script>

</main>
</div>
</body>
</html>
