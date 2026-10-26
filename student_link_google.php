<?php
session_start();

// Must come from Google OAuth flow
if (!isset($_SESSION['google_pending'])) {
    header('Location: student_login.php');
    exit;
}

require_once 'config/database.php';
$conn = getConnection();

$pending = $_SESSION['google_pending'];
$error   = '';
$success = '';

// Student links their account by entering Student ID
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid  = $conn->real_escape_string(trim($_POST['student_id']));
    $name = trim($_POST['full_name']);

    $res = $conn->query("SELECT * FROM students WHERE student_id='$sid' AND is_active=1");

    if (!$res || $res->num_rows === 0) {
        $error = "Student ID not found. Contact your admin.";
    } else {
        $student = $res->fetch_assoc();

        if (strtolower(trim($student['full_name'])) !== strtolower($name)) {
            $error = "Full name does not match our records.";
        } elseif (!$student['password']) {
            $error = "Account not fully set up. Please sign up first.";
        } else {
            // Link Google account
            $safeEmail    = $conn->real_escape_string($pending['email']);
            $safeGoogleId = $conn->real_escape_string($pending['google_id']);
            $id           = intval($student['id']);

            $conn->query("UPDATE students SET google_email='$safeEmail', google_id='$safeGoogleId' WHERE id=$id");

            if (!$student['face_descriptor']) {
                $error = "Face not registered yet. Contact your admin.";
            } else {
                unset($_SESSION['google_pending']);
                $_SESSION['login_student_id']   = $student['id'];
                $_SESSION['login_student_name'] = $student['full_name'];
                $_SESSION['login_via_google']   = true;
                header('Location: student_password_check.php');
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
    <title>Link Google Account — AttendEase</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #0a0e2e, #1565c0);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
        }
        .box {
            background: white; border-radius: 18px;
            padding: 38px 34px; max-width: 420px; width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .google-badge {
            display: flex; align-items: center; gap: 10px;
            background: #f8f9ff; border: 1px solid #e8eaf6;
            border-radius: 10px; padding: 12px 16px;
            margin-bottom: 20px;
        }
        .google-badge .gi { font-size: 1.6rem; }
        .google-badge .gt { font-size: 0.82rem; color: #555; }
        .google-badge .ge { font-weight: 700; color: #1a237e; }
        h2 { font-size: 1.2rem; color: #1a237e; margin-bottom: 6px; }
        p  { font-size: 0.86rem; color: #666; margin-bottom: 18px; line-height: 1.6; }
        label { display:block; font-size:0.85rem; font-weight:600; color:#444; margin-bottom:5px; }
        input { width:100%; padding:10px 14px; border:1.5px solid #dde0ec; border-radius:9px; font-size:0.92rem; margin-bottom:14px; }
        input:focus { outline:none; border-color:#00b4d8; box-shadow:0 0 0 3px rgba(0,180,216,0.12); }
        .btn { width:100%; padding:13px; border:none; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; }
        .btn-primary { background:linear-gradient(135deg,#00b4d8,#1565c0); color:white; }
        .err { background:#ffebee; color:#e53935; padding:10px 14px; border-radius:8px; font-size:0.85rem; margin-bottom:14px; }
        .back { text-align:center; margin-top:14px; font-size:0.82rem; color:#aaa; }
        .back a { color:#1a237e; font-weight:600; text-decoration:none; }
    </style>
</head>
<body>
<div class="box">
    <h2>🔗 Link Your Google Account</h2>

    <div class="google-badge">
        <div class="gi">🟢</div>
        <div class="gt">
            Signed in as Google:<br>
            <span class="ge"><?= htmlspecialchars($pending['email']) ?></span>
        </div>
    </div>

    <p>
        Your Google account is not linked to any student record yet.
        Enter your <strong>Student ID</strong> and <strong>Full Name</strong>
        to connect them. You only need to do this once.
    </p>

    <?php if ($error): ?>
        <div class="err">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Student ID</label>
        <input type="text" name="student_id" placeholder="e.g. 2024-0001" required autofocus>
        <label>Full Name <span style="color:#aaa;font-size:0.78rem">(as registered by admin)</span></label>
        <input type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required>
        <button type="submit" class="btn btn-primary">🔗 Link & Continue →</button>
    </form>

    <div class="back">
        <a href="student_login.php">← Back to Login</a>
    </div>
</div>
</body>
</html>
