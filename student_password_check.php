<?php
// ─────────────────────────────────────────────────────
//  Password check — Step 2 after Google OAuth
//  Session must have login_student_id set by google_callback.php
// ─────────────────────────────────────────────────────
session_start();

// Must have a pending Google-verified student
if (!isset($_SESSION['login_student_id']) || empty($_SESSION['login_via_google'])) {
    header('Location: student_login.php');
    exit;
}

require_once 'config/database.php';
$conn = getConnection();

$sid_db  = intval($_SESSION['login_student_id']);
$res     = $conn->query("SELECT * FROM students WHERE id=$sid_db AND is_active=1");
$student = ($res && $res->num_rows === 1) ? $res->fetch_assoc() : null;

if (!$student) {
    session_destroy();
    header('Location: student_login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (!$student['password']) {
        $error = "Your account has no password. Please sign up first.";
    } elseif (!password_verify($password, $student['password'])) {
        $error = "Incorrect password. Please try again.";
    } else {
        // Password verified → proceed to face challenge
        header('Location: student_face_verify.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Password — AttendEase</title>
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #0a0e2e 0%, #1565c0 60%, #0d47a1 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px; position: relative; overflow: hidden;
        }
        body::before {
            content: ''; position: absolute; inset: 0;
            background-image:
                linear-gradient(rgba(0,180,216,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,180,216,0.05) 1px, transparent 1px);
            background-size: 50px 50px; pointer-events: none;
        }
        .wrap { width:100%; max-width:400px; position:relative; z-index:1; }

        /* Brand */
        .brand { text-align:center; margin-bottom:20px; color:white; }
        .brand .logo {
            width:48px; height:48px;
            background:linear-gradient(135deg,#00b4d8,#1565c0);
            border-radius:13px;
            display:inline-flex; align-items:center; justify-content:center;
            font-size:1.5rem; margin-bottom:8px;
        }
        .brand h1 { font-size:1.2rem; font-weight:900; }
        .brand h1 span { color:#00b4d8; }

        /* Card */
        .card {
            background:white; border-radius:22px;
            padding:34px 30px;
            box-shadow:0 24px 70px rgba(0,0,0,0.3);
        }

        /* Steps */
        .steps {
            display:flex; justify-content:center; align-items:center;
            margin-bottom:22px;
        }
        .step { display:flex; flex-direction:column; align-items:center; gap:4px; font-size:0.65rem; color:#ccc; font-weight:700; min-width:52px; text-align:center; }
        .step-num { width:26px; height:26px; border-radius:50%; background:#eee; color:#bbb; display:flex; align-items:center; justify-content:center; font-size:0.74rem; font-weight:800; }
        .step.done   .step-num { background:#00a87a; color:white; }
        .step.active .step-num { background:#1565c0; color:white; box-shadow:0 0 0 3px #bbdefb; }
        .step.done   { color:#00a87a; }
        .step.active { color:#1565c0; }
        .s-line { flex:1; height:3px; background:#eee; margin-bottom:14px; max-width:36px; }
        .s-line.done { background:#00a87a; }

        /* Google verified badge */
        .google-verified {
            display:flex; align-items:center; gap:10px;
            background:#f8f9ff; border:1px solid #e8eaf6;
            border-radius:12px; padding:12px 14px;
            margin-bottom:20px;
        }
        .gv-icon { font-size:2rem; flex-shrink:0; }
        .gv-info { flex:1; }
        .gv-label { font-size:0.7rem; color:#888; font-weight:600; margin-bottom:2px; }
        .gv-email { font-size:0.88rem; font-weight:700; color:#1565c0; word-break:break-all; }
        .gv-check { color:#00a87a; font-size:1.1rem; flex-shrink:0; }

        /* Form */
        label { display:block; font-size:0.84rem; font-weight:700; color:#333; margin-bottom:6px; }
        input[type=password] {
            width:100%; padding:12px 14px;
            border:2px solid #e0e0e0; border-radius:10px;
            font-size:0.95rem; outline:none;
            transition:border-color 0.2s, box-shadow 0.2s;
            font-family:inherit;
        }
        input[type=password]:focus {
            border-color:#1565c0;
            box-shadow:0 0 0 3px rgba(21,101,192,0.12);
        }

        .btn-submit {
            width:100%; padding:13px;
            background:linear-gradient(135deg,#00b4d8,#1565c0);
            color:white; border:none; border-radius:10px;
            font-size:1rem; font-weight:700; cursor:pointer;
            margin-top:14px; transition:opacity 0.2s, transform 0.2s;
        }
        .btn-submit:hover { opacity:0.9; transform:translateY(-1px); }

        .err {
            background:#ffebee; border:1px solid #ffcdd2;
            border-radius:10px; padding:12px 14px;
            font-size:0.83rem; color:#e53935; margin-bottom:14px;
        }

        .back-link {
            text-align:center; margin-top:16px;
            font-size:0.8rem; color:#aaa;
        }
        .back-link a { color:#1565c0; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="brand">
        <div class="logo"><img src="assets/img/logo.png" style="width:100%;height:100%;object-fit:cover;border-radius:8px;" alt="Logo"></div>
        <h1>Attend<span>Ease</span></h1>
    </div>

    <div class="card">

        <!-- Steps -->
        <div class="steps">
            <div class="step done"><div class="step-num">✓</div>Gmail</div>
            <div class="s-line done"></div>
            <div class="step active"><div class="step-num">2</div>Password</div>
            <div class="s-line"></div>
            <div class="step"><div class="step-num">3</div>Face</div>
            <div class="s-line"></div>
            <div class="step"><div class="step-num">4</div>Done</div>
        </div>

        <!-- Google verified badge -->
        <div class="google-verified">
            <div class="gv-icon">🔵</div>
            <div class="gv-info">
                <div class="gv-label">GMAIL VERIFIED</div>
                <div class="gv-email"><?= htmlspecialchars($student['google_email'] ?? $student['email'] ?? '') ?></div>
            </div>
            <div class="gv-check">✅</div>
        </div>

        <?php if ($error): ?>
        <div class="err">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <label>Enter Your Password</label>
            <input type="password" name="password" required autofocus
                   placeholder="Enter your account password">
            <button type="submit" class="btn-submit">Continue to Face Verify →</button>
        </form>

        <div class="back-link">
            <a href="student_login.php?cancel=1">← Use a different account</a>
        </div>
    </div>
</div>
<script src="assets/js/toggle-password.js"></script>
</body>
</html>
