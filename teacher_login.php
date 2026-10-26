<?php
session_start();
if (isset($_SESSION['teacher_id'])) {
    header('Location: teacher/dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $conn  = getConnection();
    $uname = $conn->real_escape_string(trim($_POST['username']));
    $pass  = $_POST['password'];
    $res   = $conn->query("SELECT * FROM teachers WHERE username='$uname' AND is_active=1");
    if ($res && $res->num_rows === 1) {
        $t = $res->fetch_assoc();
        if (!$t['is_approved'])                             { $error = "Your account is pending admin approval."; }
        elseif (password_verify($pass, $t['password']))     {
            $_SESSION['teacher_id']   = $t['id'];
            $_SESSION['teacher_name'] = $t['full_name'];
            $_SESSION['teacher_emp']  = $t['employee_id'];
            $_SESSION['teacher_dept'] = $t['department'];
            header('Location: teacher/dashboard.php');
            exit;
        } else { $error = "Invalid username or password."; }
    } else { $error = "Account not found."; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login — Attendance System</title>
    <style>
        *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Segoe UI',system-ui,sans-serif;min-height:100vh;display:flex;background:#051a05;overflow:hidden;}

        /* Left panel */
        .left-panel{flex:1;background:linear-gradient(145deg,#051a05 0%,#0a3d0a 50%,#1b5e20 100%);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px 48px;position:relative;overflow:hidden;}
        .left-panel::before{content:'';position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(46,125,50,0.2),transparent 70%);top:-100px;right:-100px;animation:float 8s ease-in-out infinite;}
        .left-panel::after{content:'';position:absolute;width:350px;height:350px;border-radius:50%;background:radial-gradient(circle,rgba(0,200,150,0.12),transparent 70%);bottom:-80px;left:-60px;animation:float 11s ease-in-out infinite reverse;}
        @keyframes float{0%,100%{transform:translate(0,0);}50%{transform:translate(20px,-20px);}}
        .grid-bg{position:absolute;inset:0;background-image:linear-gradient(rgba(46,125,50,0.07) 1px,transparent 1px),linear-gradient(90deg,rgba(46,125,50,0.07) 1px,transparent 1px);background-size:50px 50px;pointer-events:none;}
        .left-content{position:relative;z-index:1;text-align:center;max-width:400px;}
        .logo-wrap{margin-bottom:28px;}
        .logo-wrap img{width:150px;height:150px;object-fit:contain;border-radius:20px;filter:drop-shadow(0 8px 32px rgba(46,125,50,0.6));animation:glow 3s ease-in-out infinite;}
        @keyframes glow{0%,100%{filter:drop-shadow(0 8px 32px rgba(46,125,50,0.5));}50%{filter:drop-shadow(0 8px 48px rgba(0,200,150,0.7));}}
        .left-content h1{font-size:1.7rem;font-weight:900;color:white;line-height:1.2;margin-bottom:12px;letter-spacing:-0.5px;}
        .left-content h1 span{background:linear-gradient(90deg,#69f0ae,#00e676);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .left-content p{color:rgba(255,255,255,0.55);font-size:0.9rem;line-height:1.7;margin-bottom:30px;}
        .features{display:flex;flex-direction:column;gap:12px;text-align:left;}
        .feat-item{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:11px 15px;color:rgba(255,255,255,0.8);font-size:0.85rem;backdrop-filter:blur(4px);}
        .feat-icon{width:36px;height:36px;border-radius:9px;background:rgba(46,125,50,0.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
        .feat-text strong{color:white;display:block;font-size:0.83rem;margin-bottom:1px;}
        .feat-text span{font-size:0.74rem;opacity:0.6;}

        /* Right panel */
        .right-panel{width:480px;min-width:480px;background:white;display:flex;flex-direction:column;justify-content:center;padding:52px 44px;position:relative;overflow-y:auto;}
        .right-panel::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#2e7d32,#43a047,#00c896);}
        .form-header{margin-bottom:30px;}
        .portal-badge{display:inline-flex;align-items:center;gap:6px;background:#e8f5e9;color:#2e7d32;padding:5px 14px;border-radius:20px;font-size:0.72rem;font-weight:800;letter-spacing:1px;margin-bottom:14px;}
        .form-header h2{font-size:1.6rem;font-weight:900;color:#0a1a0a;margin-bottom:6px;letter-spacing:-0.5px;}
        .form-header p{color:#888;font-size:0.88rem;}
        .error-box{display:flex;align-items:flex-start;gap:10px;background:#fff1f1;border:1px solid #ffc9c9;border-left:4px solid #e53935;border-radius:10px;padding:12px 14px;font-size:0.84rem;color:#c62828;margin-bottom:20px;}
        .fg{margin-bottom:20px;}
        .fg label{display:block;font-size:0.82rem;font-weight:700;color:#333;margin-bottom:7px;letter-spacing:0.3px;}
        .fg label span{color:#aaa;font-weight:400;}
        .input-wrap{position:relative;}
        .input-prefix{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:1rem;pointer-events:none;opacity:0.6;}
        .fg input{width:100%;padding:13px 14px 13px 40px;border:2px solid #e8e8e8;border-radius:11px;font-size:0.95rem;outline:none;font-family:inherit;color:#222;background:#fafff9;transition:border-color 0.2s,box-shadow 0.2s,background 0.2s;}
        .fg input:focus{border-color:#2e7d32;background:white;box-shadow:0 0 0 4px rgba(46,125,50,0.1);}
        .fg input::placeholder{color:#bbb;}
        .btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,#1b5e20,#2e7d32,#43a047);color:white;border:none;border-radius:11px;font-size:1rem;font-weight:700;cursor:pointer;letter-spacing:0.3px;transition:all 0.25s;box-shadow:0 6px 20px rgba(46,125,50,0.35);margin-bottom:20px;}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 28px rgba(46,125,50,0.45);}
        .divider{display:flex;align-items:center;gap:12px;margin-bottom:16px;color:#ccc;font-size:0.78rem;}
        .divider::before,.divider::after{content:'';flex:1;border-top:1px solid #eee;}
        .btn-register{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border:2px solid #c8e6c9;border-radius:11px;background:transparent;font-size:0.92rem;font-weight:700;color:#2e7d32;text-decoration:none;transition:all 0.2s;margin-bottom:24px;}
        .btn-register:hover{background:#f1f8e9;border-color:#2e7d32;}
        .bottom-links{display:flex;justify-content:center;gap:16px;padding-top:16px;border-top:1px solid #f0f0f0;}
        .bottom-links a{color:#aaa;font-size:0.78rem;text-decoration:none;transition:color 0.2s;}
        .bottom-links a:hover{color:#2e7d32;}
        .bottom-links .sep{color:#e0e0e0;}
        @media(max-width:900px){.left-panel{display:none;}.right-panel{width:100%;min-width:0;padding:40px 28px;}}
    </style>
</head>
<body>

<div class="left-panel">
    <div class="grid-bg"></div>
    <div class="left-content">
        <div class="logo-wrap">
            <img src="assets/img/logo.png" alt="Attendance System Logo">
        </div>
        <h1>Teacher <span>Management</span><br>Portal</h1>
        <p>Manage your classes, take attendance, set location, and monitor your students — all in one place.</p>
        <div class="features">
            <div class="feat-item">
                <div class="feat-icon">📚</div>
                <div class="feat-text"><strong>Class Management</strong><span>Add subjects, enroll students, set schedules</span></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon">📷</div>
                <div class="feat-text"><strong>Manual Attendance</strong><span>Override attendance when face scan fails</span></div>
            </div>
            <div class="feat-item">
                <div class="feat-icon">📋</div>
                <div class="feat-text"><strong>Reports & Analytics</strong><span>Daily, weekly, monthly reports with % rates</span></div>
            </div>
        </div>
    </div>
</div>

<div class="right-panel">
    <div class="form-header">
        <div class="portal-badge">👨‍🏫 TEACHER PORTAL</div>
        <h2>Teacher Login</h2>
        <p>Sign in to manage your classes and attendance</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box"><span>❌</span><span><?= htmlspecialchars($error) ?></span></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="fg">
            <label>Username</label>
            <div class="input-wrap">
                <span class="input-prefix">👤</span>
                <input type="text" name="username" required autofocus placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>
        <div class="fg">
            <label>Password</label>
            <div class="input-wrap">
                <span class="input-prefix">🔒</span>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
        </div>
        <button type="submit" class="btn-submit">Sign In to Teacher Portal</button>
    </form>

    <div class="divider">New teacher?</div>
    <a href="teacher_signup.php" class="btn-register">📝 Register as Teacher</a>

    <div class="bottom-links">
        <a href="index.php">← Home</a>
        <span class="sep">|</span>
        <a href="student_login.php">👨‍🎓 Student Login</a>
        <span class="sep">|</span>
        <a href="admin/" style="color:#ddd;">Admin</a>
    </div>
</div>
<script src="assets/js/toggle-password.js"></script>
</body>
</html>
