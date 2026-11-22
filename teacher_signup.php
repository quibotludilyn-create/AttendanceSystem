<?php
session_start();
if (isset($_SESSION['teacher_id'])) { header('Location: teacher/dashboard.php'); exit; }
require_once 'config/database.php';
$conn = getConnection();
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp    = $conn->real_escape_string(trim($_POST['employee_id']));
    $name   = $conn->real_escape_string(trim($_POST['full_name']));
    $email  = $conn->real_escape_string(strtolower(trim($_POST['email'])));
    $uname  = $conn->real_escape_string(trim($_POST['username']));
    $dept   = $conn->real_escape_string(trim($_POST['department']));
    $phone  = $conn->real_escape_string(trim($_POST['phone']));
    $pw     = $_POST['password'];
    $pw2    = $_POST['confirm_password'];

    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($pw) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($pw !== $pw2) {
        $error = "Passwords do not match.";
    } else {
        // Check duplicates
        $chk = $conn->query("SELECT id FROM teachers WHERE employee_id='$emp' OR email='$email' OR username='$uname'");
        if ($chk->num_rows > 0) {
            $error = "Employee ID, email, or username already exists.";
        } else {
            $hash = $conn->real_escape_string(password_hash($pw, PASSWORD_DEFAULT));
            $conn->query("INSERT INTO teachers (employee_id, full_name, email, username, password, department, phone, is_active, is_approved)
                          VALUES ('$emp','$name','$email','$uname','$hash','$dept','$phone',1,0)");

            // Notify admin
            $tid = $conn->insert_id;
            $conn->query("INSERT INTO notifications (recipient_type, recipient_id, title, message, type)
                          VALUES ('admin', 1, 'New Teacher Registration',
                          'Teacher $name ($emp) has registered and awaits approval.', 'system')");

            $success = "Registration submitted! Wait for admin approval before you can login.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Sign Up — AttendEase</title>
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#1b5e20,#2e7d32);min-height:100vh;display:flex;align-items:flex-start;justify-content:center;padding:24px 16px 40px;position:relative;}
        body::before{content:'';position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.04) 1px,transparent 1px);background-size:50px 50px;pointer-events:none;}
        .wrap{width:100%;max-width:520px;position:relative;z-index:1;}
        .brand{text-align:center;margin-bottom:20px;color:white;}
        .brand .logo{width:48px;height:48px;background:rgba(255,255,255,0.2);border-radius:13px;display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;margin-bottom:8px;}
        .brand h1{font-size:1.2rem;font-weight:900;}
        .brand h1 span{color:#a5d6a7;}
        .card{background:white;border-radius:20px;padding:32px 28px;box-shadow:0 24px 60px rgba(0,0,0,0.25);}
        .card-head{text-align:center;margin-bottom:24px;}
        .badge{display:inline-block;background:#e8f5e9;color:#2e7d32;padding:4px 14px;border-radius:20px;font-size:0.7rem;font-weight:800;letter-spacing:1px;margin-bottom:10px;}
        .card-head h2{font-size:1.15rem;font-weight:800;color:#1b5e20;margin-bottom:4px;}
        .card-head p{font-size:0.82rem;color:#888;}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .fg{margin-bottom:14px;}
        .fg label{display:block;font-size:0.82rem;font-weight:700;color:#444;margin-bottom:5px;}
        .fg input,.fg select{width:100%;padding:10px 13px;border:1.5px solid #ddd;border-radius:9px;font-size:0.92rem;outline:none;transition:border-color 0.2s;font-family:inherit;}
        .fg input:focus,.fg select:focus{border-color:#2e7d32;box-shadow:0 0 0 3px rgba(46,125,50,0.1);}
        .str-bar{height:4px;background:#eee;border-radius:2px;margin-top:5px;overflow:hidden;}
        .str-fill{height:100%;width:0%;border-radius:2px;transition:all 0.3s;}
        .str-lbl{font-size:0.72rem;color:#aaa;margin-top:2px;}
        .match-msg{font-size:0.75rem;margin-top:3px;}
        .btn{width:100%;padding:13px;background:linear-gradient(135deg,#2e7d32,#43a047);color:white;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;margin-top:6px;transition:opacity 0.2s;}
        .btn:hover{opacity:0.9;}
        .err{background:#ffebee;border:1px solid #ffcdd2;border-radius:10px;padding:12px 14px;font-size:0.83rem;color:#e53935;margin-bottom:16px;}
        .suc{background:#e8f5e9;border:1px solid #c8e6c9;border-radius:10px;padding:14px;font-size:0.88rem;color:#2e7d32;margin-bottom:16px;text-align:center;}
        .links{text-align:center;margin-top:18px;font-size:0.8rem;color:#aaa;}
        .links a{color:#2e7d32;text-decoration:none;font-weight:600;}
        .note{background:#f1f8e9;border-radius:10px;padding:12px 14px;font-size:0.78rem;color:#558b2f;line-height:1.6;margin-bottom:16px;}
        @media(max-width:500px){.grid2{grid-template-columns:1fr;}}
        body{background-color:#0b1420;background-image:radial-gradient(ellipse at 10% 12%,rgba(105,147,189,0.12),transparent 38%),radial-gradient(ellipse at 90% 86%,rgba(95,135,174,0.1),transparent 42%),linear-gradient(135deg,#0b1420,#14253a 60%,#1a3045);}
        body::before{background-image:linear-gradient(rgba(147,175,207,0.035) 1px,transparent 1px),linear-gradient(90deg,rgba(147,175,207,0.035) 1px,transparent 1px);}
        .brand .logo{background:rgba(20,37,56,0.78);border:1px solid rgba(147,175,207,0.18);box-shadow:0 0 24px rgba(105,147,189,0.1);}
        .brand h1 span{color:#a8c2df;}
        .card{background:linear-gradient(155deg,rgba(23,38,53,0.98),rgba(17,30,44,0.98));border:1px solid rgba(147,175,207,0.16);box-shadow:0 24px 64px rgba(0,0,0,0.32),0 0 28px rgba(105,147,189,0.05);backdrop-filter:blur(16px);}
        .badge{background:rgba(147,175,207,0.08);border:1px solid rgba(147,175,207,0.18);color:#a8c2df;}
        .card-head h2{color:#e3eaeb;}
        .card-head p{color:#9aadb3;}
        .fg label{color:#c2d0d4;}
        .fg input,.fg select{border:1px solid #374858;color:#e3eaf0;background:#142231;}
        .fg input::placeholder{color:#879aa1;}
        .fg input:focus,.fg select:focus{border-color:#91abc2;box-shadow:0 0 0 3px rgba(145,171,194,0.08);background:#17283a;}
        .str-bar{background:#293b43;}
        .str-lbl{color:#a0b0b4;}
        .match-msg{color:#a0b0b4;}
        .btn{background:linear-gradient(110deg,#4d7193,#527f9a);box-shadow:0 8px 26px rgba(81,119,153,0.16);}
        .btn:hover{opacity:1;filter:brightness(1.06);box-shadow:0 10px 28px rgba(81,119,153,0.24);}
        .err{background:rgba(177,83,88,0.1);border-color:rgba(210,135,137,0.22);color:#dfa0a2;}
        .suc{background:rgba(105,147,189,0.1);border-color:rgba(147,175,207,0.18);color:#b6cbe0;}
        .links{color:#a0b0b4;}
        .links a{color:#a8c2df;}
        .note{background:rgba(147,175,207,0.06);border:1px solid rgba(147,175,207,0.12);color:#b1c2d0;}
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="logo"><img src="assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Logo"></div>
        <h1>Attend<span>Ease</span></h1>
    </div>
    <div class="card">
        <div class="card-head">
            <div class="badge">👨‍🏫 TEACHER REGISTRATION</div>
            <h2>Create Teacher Account</h2>
            <p>Your account will be reviewed by the admin before activation</p>
        </div>

        <div class="note">
            ⓘ After registration, an admin must <strong>approve</strong> your account before you can login.
        </div>

        <?php if ($error): ?><div class="err">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="suc">✅ <?= htmlspecialchars($success) ?></div>
            <div class="links"><a href="teacher_login.php">← Go to Login</a></div>
        <?php else: ?>

        <form method="POST" autocomplete="off" onsubmit="return validateForm()">
            <div class="grid2">
                <div class="fg">
                    <label>Employee ID *</label>
                    <input type="text" name="employee_id" required placeholder="e.g. EMP-001" value="<?= htmlspecialchars($_POST['employee_id'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required placeholder="Juan Dela Cruz" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            <div class="grid2">
                <div class="fg">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="teacher@school.edu" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Username *</label>
                    <input type="text" name="username" required placeholder="e.g. jdelacruz" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>
            <div class="grid2">
                <div class="fg">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. BSIT Dept." value="<?= htmlspecialchars($_POST['department'] ?? '') ?>">
                </div>
                <div class="fg">
                    <label>Phone Number</label>
                    <input type="text" name="phone" placeholder="09XX-XXX-XXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="fg">
                <label>Password * <span style="color:#aaa;font-weight:400;font-size:0.76rem">(min. 8 characters)</span></label>
                <input type="password" name="password" id="pw1" required minlength="8" placeholder="Enter password" oninput="checkStr(this.value)">
                <div class="str-bar"><div class="str-fill" id="str-fill"></div></div>
                <div class="str-lbl" id="str-lbl">Enter a password</div>
            </div>
            <div class="fg">
                <label>Confirm Password *</label>
                <input type="password" name="confirm_password" id="pw2" required placeholder="Re-enter password" oninput="checkMatch()">
                <div class="match-msg" id="match-msg"></div>
            </div>
            <button type="submit" class="btn">📝 Submit Registration</button>
        </form>
        <?php endif; ?>

        <div class="links">
            Already have an account? <a href="teacher_login.php">Login here →</a>
        </div>
    </div>
</div>
<script>
function checkStr(pw){
    const f=document.getElementById('str-fill'),l=document.getElementById('str-lbl');
    let s=0;
    if(pw.length>=8)s++;if(pw.length>=12)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[^A-Za-z0-9]/.test(pw))s++;
    const lvl=[{p:'0%',c:'#a0b0b4',t:'Enter a password'},{p:'25%',c:'#d48789',t:'Weak'},{p:'50%',c:'#d8b27b',t:'Fair'},{p:'75%',c:'#a0b7cc',t:'Good'},{p:'100%',c:'#a8c2df',t:'Strong 💪'}][Math.min(s,4)];
    f.style.width=lvl.p;f.style.background=lvl.c;l.textContent=lvl.t;l.style.color=lvl.c;
}
function checkMatch(){
    const v1=document.getElementById('pw1').value,v2=document.getElementById('pw2').value,el=document.getElementById('match-msg');
    if(!v2)return;
    el.textContent=v1===v2?'✅ Passwords match':'❌ Passwords do not match';
    el.style.color=v1===v2?'#a8c2df':'#d48789';
}
function validateForm(){
    const v1=document.getElementById('pw1').value,v2=document.getElementById('pw2').value;
    if(v1.length<8){alert('Password must be at least 8 characters.');return false;}
    if(v1!==v2){alert('Passwords do not match.');return false;}
    return true;
}
</script>
<script src="assets/js/toggle-password.js"></script>
</body>
</html>
