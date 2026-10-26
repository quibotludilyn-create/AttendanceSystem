<?php
require_once 'layout.php';
$msg = ''; $err = '';

$t = $conn->query("SELECT * FROM teachers WHERE id=$teacher_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])) {
    $dept  = $conn->real_escape_string(trim($_POST['department']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $email = $conn->real_escape_string(strtolower(trim($_POST['email'])));
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email format.";
    } else {
        $conn->query("UPDATE teachers SET department='$dept',phone='$phone',email='$email' WHERE id=$teacher_id");
        $msg = "Profile updated.";
        $t = $conn->query("SELECT * FROM teachers WHERE id=$teacher_id")->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'];
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];
    if (!password_verify($cur, $t['password'])) {
        $err = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $err = "New password must be at least 8 characters.";
    } elseif ($new !== $conf) {
        $err = "Passwords do not match.";
    } else {
        $hash = $conn->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
        $conn->query("UPDATE teachers SET password='$hash' WHERE id=$teacher_id");
        $msg = "Password changed successfully.";
    }
}

$total_classes  = $conn->query("SELECT COUNT(*) as c FROM subjects WHERE teacher_id=$teacher_id AND is_active=1")->fetch_assoc()['c'];
$total_students = $conn->query("SELECT COUNT(DISTINCT e.student_id) as c FROM enrollments e JOIN subjects s ON e.subject_id=s.id WHERE s.teacher_id=$teacher_id")->fetch_assoc()['c'];
$total_att      = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE teacher_id=$teacher_id")->fetch_assoc()['c'];
$manual_att     = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE teacher_id=$teacher_id AND is_manual=1")->fetch_assoc()['c'];
?>

<?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Profile Header -->
<div class="card" style="background:linear-gradient(135deg,#1b5e20,#2e7d32);color:white;border:none;margin-bottom:20px;">
    <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;font-size:2.2rem;flex-shrink:0;">
            👨‍🏫
        </div>
        <div style="flex:1;">
            <h2 style="font-size:1.2rem;font-weight:800;margin-bottom:4px;"><?= htmlspecialchars($t['full_name']) ?></h2>
            <p style="font-size:0.84rem;opacity:0.75;"><?= htmlspecialchars($t['employee_id']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($t['department'] ?: 'No department') ?></p>
        </div>
        <span class="badge badge-present" style="background:rgba(0,200,150,0.2);color:#a5d6a7;border:1px solid rgba(0,200,150,0.4);">
            ✅ Active <?= $t['is_approved'] ? '· Approved' : '· Pending' ?>
        </span>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
    <div class="stat-card green"> <div class="stat-icon" style="background:#e8f5e9;">📚</div><div class="stat-info"><h3><?= $total_classes ?></h3><p>Classes</p></div></div>
    <div class="stat-card blue">  <div class="stat-icon" style="background:#e3f2fd;">👥</div><div class="stat-info"><h3><?= $total_students ?></h3><p>Students</p></div></div>
    <div class="stat-card green"> <div class="stat-icon" style="background:#e8f5e9;">📋</div><div class="stat-info"><h3><?= $total_att ?></h3><p>Attendance Records</p></div></div>
    <div class="stat-card orange"><div class="stat-icon" style="background:#fff3e0;">✏️</div><div class="stat-info"><h3><?= $manual_att ?></h3><p>Manual Records</p></div></div>
</div>

<div class="grid-2">
<!-- Edit Profile -->
<div class="card">
    <div class="card-title">📋 Personal Information</div>
    <form method="POST">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($t['full_name']) ?>" disabled style="background:#f5f5f5;">
        </div>
        <div class="form-group">
            <label>Employee ID</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($t['employee_id']) ?>" disabled style="background:#f5f5f5;">
        </div>
        <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($t['username']) ?>" disabled style="background:#f5f5f5;">
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($t['email']) ?>" required>
        </div>
        <div class="form-group">
            <label>Department</label>
            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($t['department'] ?? '') ?>" placeholder="e.g. Computer Science">
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($t['phone'] ?? '') ?>" placeholder="09XX-XXX-XXXX">
        </div>
        <button type="submit" name="update_profile" class="btn btn-primary btn-block">💾 Update Profile</button>
    </form>
</div>

<!-- Change Password -->
<div class="card">
    <div class="card-title">🔑 Change Password</div>
    <form method="POST" onsubmit="return validatePw()">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" id="np1" class="form-control" placeholder="Min. 8 characters" required minlength="8" oninput="pwStr(this.value)">
            <div style="height:4px;background:#eee;border-radius:2px;margin-top:6px;overflow:hidden;"><div id="sf" style="height:100%;width:0%;border-radius:2px;transition:all 0.3s;"></div></div>
            <div id="sl" style="font-size:0.72rem;color:#aaa;margin-top:2px;">Enter password</div>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" id="np2" class="form-control" placeholder="Re-enter password" required oninput="chkMatch()">
            <div id="mm" style="font-size:0.76rem;margin-top:3px;"></div>
        </div>
        <button type="submit" name="change_password" class="btn btn-primary btn-block">🔐 Change Password</button>
    </form>

    <div style="margin-top:20px;border-top:1px solid #eee;padding-top:16px;">
        <a href="../teacher_logout.php" class="btn btn-danger btn-block">🚪 Logout</a>
    </div>
</div>
</div>

<script>
function pwStr(pw){
    const f=document.getElementById('sf'),l=document.getElementById('sl');
    let s=0;if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
    const lv=[{p:'0%',c:'#eee',t:'Enter password'},{p:'25%',c:'#ef5350',t:'Weak'},{p:'50%',c:'#ffa726',t:'Fair'},{p:'75%',c:'#66bb6a',t:'Good'},{p:'100%',c:'#2e7d32',t:'Strong 💪'}][Math.min(s,4)];
    f.style.width=lv.p;f.style.background=lv.c;l.textContent=lv.t;l.style.color=lv.c;
}
function chkMatch(){
    const v1=document.getElementById('np1').value,v2=document.getElementById('np2').value,el=document.getElementById('mm');
    if(!v2)return;el.textContent=v1===v2?'✅ Match':'❌ No match';el.style.color=v1===v2?'#2e7d32':'#e53935';
}
function validatePw(){
    const v1=document.getElementById('np1').value,v2=document.getElementById('np2').value;
    if(v1.length<8){alert('Min 8 characters.');return false;}
    if(v1!==v2){alert('Passwords do not match.');return false;}
    return true;
}
</script>

</main>
</div>
<script src="../assets/js/toggle-password.js"></script>
</body>
</html>
