<?php
session_start();

if (isset($_SESSION['student_id'])) {
    header('Location: student/home.php');
    exit;
}

if (isset($_GET['cancel'])) {
    unset($_SESSION['login_student_id'], $_SESSION['login_student_name'],
          $_SESSION['login_via_google'], $_SESSION['login_google_email']);
}

$error = '';

if (!empty($_SESSION['google_error'])) {
    $error = $_SESSION['google_error'];
    unset($_SESSION['google_error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $conn  = getConnection();
    $gmail = $conn->real_escape_string(strtolower(trim($_POST['gmail'] ?? '')));
    $pass  = $_POST['password'] ?? '';

    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid Gmail address.";
    } else {
        $res = $conn->query("SELECT * FROM students WHERE (google_email='$gmail' OR email='$gmail') AND is_active=1 LIMIT 1");
        if (!$res || $res->num_rows === 0) {
            $error = "Gmail not found. Please sign up first or contact your admin.";
        } else {
            $student = $res->fetch_assoc();

            if (empty($student['email_verified_at'])) {
                $error = 'Please verify your email using the link we sent you.';
            }



            elseif (!$student['password'])                           { $error = "Account has no password. Please sign up first."; }
            elseif (!password_verify($pass,$student['password'])){ $error = "Incorrect Gmail or password."; }
            elseif (!$student['face_descriptor'])                { $error = "Face not registered. Please complete sign up."; }
            else {
                $_SESSION['login_student_id']   = $student['id'];
                $_SESSION['login_student_name'] = $student['full_name'];
                $_SESSION['login_via_google']   = false;
                $_SESSION['login_google_email'] = $student['google_email'] ?? $student['email'] ?? '';
                header('Location: student_face_verify.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — Attendance System</title>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0a0e2e;
            overflow: hidden;
        }

        /* ── Left panel ── */
        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, #050818 0%, #0d1b6e 50%, #1a237e 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 48px;
            position: relative;
            overflow: hidden;
        }

        /* Animated circles background */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,180,216,0.12), transparent 70%);
            top: -100px; right: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(0,200,150,0.1), transparent 70%);
            bottom: -80px; left: -60px;
            animation: float 11s ease-in-out infinite reverse;
        }
        @keyframes float { 0%,100%{transform:translate(0,0);} 50%{transform:translate(20px,-20px);} }

        /* Grid overlay */
        .grid-bg {
            position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(0,180,216,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,180,216,0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
        }

        .left-content { position: relative; z-index: 1; text-align: center; max-width: 400px; }

        /* Logo */
        .logo-wrap {
            margin-bottom: 28px;
        }
        .logo-wrap img {
            width: 160px;
            height: 160px;
            object-fit: contain;
            border-radius: 20px;
            filter: drop-shadow(0 8px 32px rgba(0,180,216,0.5));
            animation: glow 3s ease-in-out infinite;
        }
        @keyframes glow {
            0%,100% { filter: drop-shadow(0 8px 32px rgba(0,180,216,0.4)); }
            50%      { filter: drop-shadow(0 8px 48px rgba(0,200,150,0.6)); }
        }

        .left-content h1 {
            font-size: 1.8rem;
            font-weight: 900;
            color: white;
            line-height: 1.2;
            margin-bottom: 12px;
            letter-spacing: -0.5px;
        }
        .left-content h1 span {
            background: linear-gradient(90deg, #00b4d8, #00c896);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .left-content p {
            color: rgba(255,255,255,0.55);
            font-size: 0.9rem;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        /* Features list */
        .features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-align: left;
        }
        .feat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
            backdrop-filter: blur(4px);
        }
        .feat-icon {
            width: 36px; height: 36px;
            border-radius: 9px;
            background: rgba(0,180,216,0.15);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .feat-icon img { width: 24px; height: 24px; object-fit: contain; }
        .feat-text strong { color: white; display: block; font-size: 0.84rem; margin-bottom: 1px; }
        .feat-text span   { font-size: 0.76rem; opacity: 0.6; }

        /* ── Right panel (form) ── */
        .right-panel {
            width: 480px;
            min-width: 480px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 44px;
            position: relative;
            overflow-y: auto;
        }

        /* Decorative top accent */
        .right-panel::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #00b4d8, #1565c0, #00c896);
        }

        .form-header { margin-bottom: 32px; }

        .portal-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }
        .portal-badge img { width: 20px; height: 20px; object-fit: contain; }

        .form-header h2 {
            font-size: 1.6rem;
            font-weight: 900;
            color: #0a0e2e;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }
        .form-header p { color: #888; font-size: 0.88rem; }

        /* Step indicators */
        .step-flow {
            display: flex;
            align-items: center;
            gap: 0;
            background: #f8f9ff;
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 28px;
        }
        .sf-step { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 4px; }
        .sf-icon  { font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }
        .sf-icon img { width: 24px; height: 24px; object-fit: contain; }
        .sf-lbl   { font-size: 0.6rem; font-weight: 700; color: #666; text-align: center; line-height: 1.3; }
        .sf-arrow { color: #ddd; font-size: 0.9rem; margin-bottom: 14px; flex-shrink: 0; }

        /* Error */
        .error-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff1f1;
            border: 1px solid #ffc9c9;
            border-left: 4px solid #e53935;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 0.84rem;
            color: #c62828;
            margin-bottom: 20px;
        }

        /* Form groups */
        .fg { margin-bottom: 20px; }
        .fg label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 7px;
            letter-spacing: 0.3px;
        }
        .fg label span { color: #aaa; font-weight: 400; }

        .input-wrap { position: relative; }
        .input-prefix {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: 1rem; pointer-events: none;
            opacity: 0.6;
        }
        .fg input {
            width: 100%;
            padding: 13px 14px 13px 40px;
            border: 2px solid #e8e8e8;
            border-radius: 11px;
            font-size: 0.95rem;
            outline: none;
            font-family: inherit;
            color: #222;
            background: #fafbff;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .fg input:focus {
            border-color: #1565c0;
            background: white;
            box-shadow: 0 0 0 4px rgba(21,101,192,0.1);
        }
        .fg input::placeholder { color: #bbb; }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: white;
            border: none;
            border-radius: 11px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            letter-spacing: 0.3px;
            transition: all 0.25s;
            box-shadow: 0 6px 20px rgba(21,101,192,0.35);
            margin-bottom: 20px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(21,101,192,0.45);
        }
        .btn-submit:active { transform: translateY(0); }

        /* Divider */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 18px; color: #ccc; font-size: 0.78rem;
        }
        .divider::before, .divider::after { content:''; flex:1; border-top:1px solid #eee; }

        /* Signup button */
        .btn-signup {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px;
            border: 2px solid #e0e4f0;
            border-radius: 11px;
            background: transparent;
            font-size: 0.92rem;
            font-weight: 700;
            color: #1a237e;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 24px;
        }
        .btn-signup:hover { background: #f0f4ff; border-color: #1a237e; }

        /* Bottom links */
        .bottom-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }
        .bottom-links a {
            color: #aaa; font-size: 0.78rem;
            text-decoration: none; transition: color 0.2s;
        }
        .bottom-links img { width: 24px; height: 24px; object-fit: contain; vertical-align: middle; }
        .bottom-links a:hover { color: #1565c0; }
        .bottom-links .sep { color: #e0e0e0; }

        /* Mobile responsive */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; min-width: 0; padding: 40px 28px; }
        }
        @media (max-width: 480px) {
            .right-panel { padding: 32px 22px; }
            .form-header h2 { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

<!-- ── Left Panel ── -->
<div class="left-panel">
    <div class="grid-bg"></div>
    <div class="left-content">
        <div class="logo-wrap">
            <img src="assets/img/logo.png" alt="Attendance System Logo">
        </div>
        <h1>
            Face Recognition-Based<br>
            <span>Student Attendance</span><br>
            Monitoring System
        </h1>
        <p>
            Smart, secure, and contactless attendance recording
            powered by AI face recognition and GPS verification.
        </p>
        <div class="features">
            <div class="feat-item">
                <div class="feat-icon"><img src="assets/img/AI_Face.png" alt=""></div>
                <div class="feat-text">
                    <strong>AI Face Recognition</strong>
                    <span>Liveness detection — no photos can bypass</span>
                </div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><img src="assets/img/location_verified.png" alt=""></div>
                <div class="feat-text">
                    <strong>GPS Location Verified</strong>
                    <span>Must be within campus to record attendance</span>
                </div>
            </div>
            <div class="feat-item">
                <div class="feat-icon"><img src="assets/img/fast_notification.png" alt=""></div>
                <div class="feat-text">
                    <strong>Instant Notifications</strong>
                    <span>Teacher & admin notified automatically</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Right Panel (Form) ── -->
<div class="right-panel">

    <div class="form-header">
        <div class="portal-badge"><img src="assets/img/students.png" alt=""> STUDENT PORTAL</div>
        <h2>Welcome Back</h2>
        <p>Sign in to record your attendance</p>
    </div>

    <!-- Step flow preview -->
    <div class="step-flow">
        <div class="sf-step">
            <div class="sf-icon"><img src="assets/img/signuponce.png" alt=""></div>
            <div class="sf-lbl">Enter<br>Credentials</div>
        </div>
        <div class="sf-arrow">›</div>
        <div class="sf-step">
            <div class="sf-icon"><img src="assets/img/face_V.png" alt=""></div>
            <div class="sf-lbl">Face<br>Challenge</div>
        </div>
        <div class="sf-arrow">›</div>
        <div class="sf-step">
            <div class="sf-icon"><img src="assets/img/location.png" alt=""></div>
            <div class="sf-lbl">Location<br>Check</div>
        </div>
        <div class="sf-arrow">›</div>
        <div class="sf-step">
            <div class="sf-icon"><img src="assets/img/check_verified.png" alt=""></div>
            <div class="sf-lbl">Attendance<br>Recorded</div>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
        <span>❌</span>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="fg">
            <label>Gmail Address <span>(registered Gmail)</span></label>
            <div class="input-wrap">
                <span class="input-prefix">📧</span>
                <input type="email" name="gmail" required autofocus
                       placeholder="juandelacruz@gmail.com"
                       value="<?= htmlspecialchars($_POST['gmail'] ?? '') ?>">
            </div>
        </div>
        <div class="fg">
            <label>Password</label>
            <div class="input-wrap">
                <span class="input-prefix">🔒</span>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
        </div>
        <button type="submit" class="btn-submit">Sign In → Face Verification</button>
    </form>

    <div class="divider">Don't have an account?</div>

    <a href="student_signup.php" class="btn-signup">
        📝 Create Student Account
    </a>

    <div class="bottom-links">
        <a href="index.php">← Home</a>
        <span class="sep">|</span>
        <a href="teacher_login.php"><img src="assets/img/teacher.png" alt=""> Teacher Login</a>
        <span class="sep">|</span>
        <a href="admin/" style="color:#ddd;">Admin</a>
    </div>
</div>

<script src="assets/js/toggle-password.js"></script>
</body>
</html>
