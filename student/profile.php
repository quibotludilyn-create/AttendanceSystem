<?php
session_start();
require_once '../config/database.php';
$conn = getConnection();
require_once 'layout.php';

$sid_db  = intval($_SESSION['student_id']);
$success = '';
$error   = '';

// Fetch full student record
$res     = $conn->query("SELECT * FROM students WHERE id=$sid_db");
$student = $res->fetch_assoc();

// ── Change Password ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $cur  = $_POST['current_password'];
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];

    if (!password_verify($cur, $student['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters.";
    } elseif ($new !== $conf) {
        $error = "New passwords do not match.";
    } else {
        $hashed = $conn->real_escape_string(password_hash($new, PASSWORD_DEFAULT));
        $conn->query("UPDATE students SET password='$hashed' WHERE id=$sid_db");
        $success = "Password changed successfully!";
        // Re-fetch
        $res = $conn->query("SELECT * FROM students WHERE id=$sid_db");
        $student = $res->fetch_assoc();
    }
}

// ── Attendance summary (all time) ──
$stats = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(status='present') as present,
        SUM(status='late')    as late,
        SUM(status='absent')  as absent,
        MIN(date) as first_date,
        MAX(date) as last_date
    FROM attendance
    WHERE student_id=$sid_db
")->fetch_assoc();
?>

<div class="s-page-title">👤 My Profile</div>

<!-- Profile card -->
<div class="s-card">
    <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
        <div style="
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00b4d8, #1565c0);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: white; flex-shrink:0;
            box-shadow: 0 6px 20px rgba(0,180,216,0.3);
        ">
            <?= mb_substr($student['full_name'], 0, 1) ?>
        </div>
        <div style="flex:1;">
            <h2 style="font-size:1.2rem; font-weight:800; color:var(--navy); margin-bottom:4px;">
                <?= htmlspecialchars($student['full_name']) ?>
            </h2>
            <p style="color:var(--muted); font-size:0.85rem;">
                <?= htmlspecialchars($student['student_id']) ?> &nbsp;·&nbsp;
                <?= htmlspecialchars($student['course']) ?> Year <?= $student['year_level'] ?>
                <?= $student['section'] ? ' - '.htmlspecialchars($student['section']) : '' ?>
            </p>
        </div>
        <div>
            <span style="
                background: #e8fdf5; color: #00a87a;
                padding: 5px 14px; border-radius: 20px;
                font-size: 0.76rem; font-weight: 700;
            ">
                ✅ Active Account
            </span>
        </div>
    </div>
</div>

<!-- Info grid -->
<div class="s-card">
    <div class="s-card-title">📋 Student Information</div>
    <table class="s-table">
        <tr><td style="color:var(--muted); width:40%;">Student ID</td>     <td><strong><?= htmlspecialchars($student['student_id']) ?></strong></td></tr>
        <tr><td style="color:var(--muted);">Full Name</td>    <td><?= htmlspecialchars($student['full_name']) ?></td></tr>
        <tr><td style="color:var(--muted);">Course</td>       <td><?= htmlspecialchars($student['course']) ?></td></tr>
        <tr><td style="color:var(--muted);">Year Level</td>   <td>Year <?= $student['year_level'] ?></td></tr>
        <tr><td style="color:var(--muted);">Section</td>      <td><?= htmlspecialchars($student['section'] ?: '—') ?></td></tr>
        <tr><td style="color:var(--muted);">Password</td>     <td>••••••••</td></tr>
        <tr>
            <td style="color:var(--muted);">Face Registration</td>
            <td>
                <?php if ($student['face_descriptor']): ?>
                    <span style="color:#00a87a; font-weight:600;">✅ Registered</span>
                <?php else: ?>
                    <span style="color:#e53935; font-weight:600;">❌ Not registered</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><td style="color:var(--muted);">Member Since</td> <td><?= date('F d, Y', strtotime($student['created_at'])) ?></td></tr>
    </table>
</div>

<!-- Attendance overview -->
<div class="s-card">
    <div class="s-card-title">📊 My Attendance Overview</div>
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; text-align:center;">
        <div style="padding:14px; background:var(--gray); border-radius:12px;">
            <div style="font-size:1.6rem; font-weight:900; color:var(--navy);"><?= $stats['total'] ?? 0 ?></div>
            <div style="font-size:0.72rem; color:var(--muted); font-weight:600; margin-top:3px;">Total</div>
        </div>
        <div style="padding:14px; background:#e8fdf5; border-radius:12px;">
            <div style="font-size:1.6rem; font-weight:900; color:#00a87a;"><?= $stats['present'] ?? 0 ?></div>
            <div style="font-size:0.72rem; color:#00a87a; font-weight:600; margin-top:3px;">Present</div>
        </div>
        <div style="padding:14px; background:#fff3e0; border-radius:12px;">
            <div style="font-size:1.6rem; font-weight:900; color:#e65100;"><?= $stats['late'] ?? 0 ?></div>
            <div style="font-size:0.72rem; color:#e65100; font-weight:600; margin-top:3px;">Late</div>
        </div>
        <div style="padding:14px; background:#ffebee; border-radius:12px;">
            <div style="font-size:1.6rem; font-weight:900; color:var(--red);"><?= $stats['absent'] ?? 0 ?></div>
            <div style="font-size:0.72rem; color:var(--red); font-weight:600; margin-top:3px;">Absent</div>
        </div>
    </div>
    <?php if ($stats['first_date']): ?>
    <p style="font-size:0.78rem; color:var(--muted); margin-top:12px; text-align:center;">
        First record: <?= date('F d, Y', strtotime($stats['first_date'])) ?>
        &nbsp;·&nbsp;
        Last record: <?= date('F d, Y', strtotime($stats['last_date'])) ?>
    </p>
    <?php endif; ?>
</div>

<!-- Change Password -->
<div class="s-card">
    <div class="s-card-title">🔑 Change Password</div>

    <?php if ($success): ?>
        <div class="s-alert s-alert-ok">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="s-alert s-alert-err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" onsubmit="return validatePwChange()">
        <div class="s-form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="s-input"
                   placeholder="Enter current password" required>
        </div>
        <div class="s-form-group">
            <label>New Password</label>
            <input type="password" name="new_password" id="np1" class="s-input"
                   placeholder="Min. 6 characters" required minlength="6"
                   oninput="pwStrength(this.value)">
            <!-- Strength bar -->
            <div style="height:4px; background:#eee; border-radius:2px; margin-top:6px; overflow:hidden;">
                <div id="str-fill" style="height:100%; width:0%; border-radius:2px; transition:all 0.3s;"></div>
            </div>
            <div id="str-lbl" style="font-size:0.72rem; color:#aaa; margin-top:2px;">Enter new password</div>
        </div>
        <div class="s-form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" id="np2" class="s-input"
                   placeholder="Re-enter new password" required
                   oninput="checkPwMatch()">
            <div id="match-msg" style="font-size:0.76rem; margin-top:3px;"></div>
        </div>
        <button type="submit" name="change_password" class="s-btn s-btn-primary s-btn-block">
            🔐 Update Password
        </button>
    </form>
</div>

<!-- Danger zone -->
<div class="s-card" style="border-color:#ffcdd2;">
    <div class="s-card-title" style="color:var(--red);">⚠️ Account</div>
    <p style="font-size:0.84rem; color:var(--muted); margin-bottom:14px;">
        If you need to re-register your face or have account issues, contact your administrator.
    </p>
    <a href="../student_logout.php" class="s-btn s-btn-danger">
        🚪 Logout of My Account
    </a>
</div>

<script>
function pwStrength(pw) {
    const fill = document.getElementById('str-fill');
    const lbl  = document.getElementById('str-lbl');
    let s = 0;
    if (pw.length >= 6)           s++;
    if (pw.length >= 10)          s++;
    if (/[A-Z]/.test(pw))         s++;
    if (/[0-9]/.test(pw))         s++;
    if (/[^A-Za-z0-9]/.test(pw))  s++;
    const lvl = [
        {p:'0%',   c:'#eee',    t:'Enter new password'},
        {p:'25%',  c:'#ef5350', t:'Weak'},
        {p:'50%',  c:'#ffa726', t:'Fair'},
        {p:'75%',  c:'#66bb6a', t:'Good'},
        {p:'100%', c:'#00a87a', t:'Strong 💪'},
    ][Math.min(s, 4)];
    fill.style.width      = lvl.p;
    fill.style.background = lvl.c;
    lbl.textContent       = lvl.t;
    lbl.style.color       = lvl.c;
}

function checkPwMatch() {
    const v1 = document.getElementById('np1').value;
    const v2 = document.getElementById('np2').value;
    const el = document.getElementById('match-msg');
    if (!v2) { el.textContent = ''; return; }
    el.textContent = v1 === v2 ? '✅ Passwords match' : '❌ Passwords do not match';
    el.style.color = v1 === v2 ? '#00a87a' : '#e53935';
}

function validatePwChange() {
    const v1 = document.getElementById('np1').value;
    const v2 = document.getElementById('np2').value;
    if (v1.length < 6) { alert('New password must be at least 6 characters.'); return false; }
    if (v1 !== v2)     { alert('Passwords do not match.');                     return false; }
    return true;
}
</script>

</main>
<script src="../assets/js/toggle-password.js"></script>
</body>
</html>
