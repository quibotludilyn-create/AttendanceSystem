<?php
session_start();
require_once 'config/database.php';
$conn = getConnection();

// Must come from signup flow
if (!isset($_SESSION['signup_done_student_id']) && !isset($_SESSION['signup_student_id'])) {
    header('Location: student_signup.php');
    exit;
}

$sid_db = intval($_SESSION['signup_done_student_id'] ?? $_SESSION['signup_student_id']);
$res    = $conn->query("SELECT * FROM students WHERE id=$sid_db");
$student = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;

// Clear all signup session keys
unset($_SESSION['signup_student_id'], $_SESSION['signup_pw_done'], $_SESSION['signup_done_student_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Complete!</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .done-card {
            background: white;
            border-radius: 18px;
            padding: 44px 36px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }
        .done-icon { font-size: 5rem; animation: pop 0.5s cubic-bezier(.175,.885,.32,1.275); }
        @keyframes pop { from{ transform:scale(0); } to{ transform:scale(1); } }
        .stu-box {
            background: #f1f8e9; border: 1px solid #a5d6a7;
            border-radius: 10px; padding: 14px 16px;
            margin: 18px 0; text-align: left;
        }
        .stu-box .sn { font-weight: 700; color: #1b5e20; font-size: 0.97rem; }
        .stu-box .sd { font-size: 0.82rem; color: #555; margin-top: 3px; }
        .check-list { text-align: left; margin: 14px 0; font-size: 0.88rem; }
        .check-list li { padding: 4px 0; color: #555; list-style: none; }
        .check-list li::before { content: '✅ '; }
        .btn-green {
            display: block; width: 100%;
            background: linear-gradient(135deg, #2e7d32, #43a047);
            color: white; padding: 13px; font-size: 1rem;
            border: none; border-radius: 8px;
            cursor: pointer; font-weight: 600; text-decoration: none;
            text-align: center; margin-top: 6px;
            transition: opacity 0.2s;
        }
        .btn-green:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="done-card">
    <div class="done-icon">🎉</div>
    <h2 style="color:#1b5e20; margin: 12px 0 6px;">Registration Complete!</h2>
    <p style="color:#666; font-size:0.9rem;">
        Your account is fully set up and ready to use.
    </p>

    <?php if ($student): ?>
    <div class="stu-box">
        <div class="sn">👨‍🎓 <?= htmlspecialchars($student['full_name']) ?></div>
        <div class="sd">
            <?= htmlspecialchars($student['student_id']) ?> &nbsp;·&nbsp;
            <?= htmlspecialchars($student['course']) ?> Year <?= $student['year_level'] ?>
            <?= $student['section'] ? ' - '.htmlspecialchars($student['section']) : '' ?>
        </div>
    </div>
    <?php endif; ?>

    <ul class="check-list">
        <li>Identity verified</li>
        <li>Password set</li>
        <li>Face registered with liveness check</li>
    </ul>

    <p style="font-size:0.83rem; color:#888; margin-bottom:16px;">
        You can now login using your <strong>Student ID</strong>, <strong>password</strong>,
        and <strong>face scan</strong>.
    </p>

    <a href="student_login.php" class="btn-green">🔐 Login Now</a>
</div>
</body>
</html>
