<?php
require_once __DIR__ . '/config/database.php';

$token = $_GET['token'] ?? '';
$success = false;
$expired = false;

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $expired = true;
} else {
    $conn = getConnection();
    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare(
        'UPDATE students
         SET email_verified_at = NOW(),
             email_verification_token_hash = NULL,
             email_verification_expires_at = NULL
         WHERE email_verification_token_hash = ?
           AND email_verification_expires_at > NOW()
           AND email_verified_at IS NULL'
    );

    if ($stmt) {
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $success = ($stmt->affected_rows === 1);
        if (!$success) $expired = true;
    } else {
        $expired = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email — Attendance System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
            background: linear-gradient(160deg, #0d2957 0%, #1a4a8a 50%, #1565c0 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* ── Top Header Bar ── */
        .top-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: linear-gradient(90deg, #0d2957, #1565c0);
            padding: 14px 32px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.3);
            z-index: 100;
        }
        .top-bar-icon {
            width: 48px; height: 48px;
            background: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem;
        }
        .top-bar-text h1 {
            color: white;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .top-bar-text p {
            color: #90caf9;
            font-size: 0.75rem;
            letter-spacing: 2px;
        }

        /* ── Wave decoration ── */
        .wave {
            position: fixed;
            top: 70px; left: 0; right: 0;
            height: 80px;
            background: linear-gradient(135deg, #1976d2 0%, #42a5f5 100%);
            clip-path: ellipse(55% 100% at 50% 0%);
            z-index: 1;
        }

        /* ── Card ── */
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px 36px 32px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            position: relative;
            z-index: 10;
            margin-top: 30px;
        }

        .envelope-icon {
            width: 90px; height: 90px;
            margin: 0 auto 20px;
            position: relative;
        }
        .envelope-icon img {
            width: 100%;
        }
        /* Fallback emoji icon */
        .icon-emoji {
            font-size: 4.5rem;
            display: block;
            margin-bottom: 8px;
        }

        .card h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0d2957;
            margin-bottom: 6px;
        }

        .card .subtitle {
            font-size: 0.95rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .card .desc {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.6;
        }

        /* ── Code Box ── */
        .code-box {
            background: #f0f6ff;
            border: 1.5px solid #bbdefb;
            border-radius: 12px;
            padding: 18px 24px;
            margin-bottom: 24px;
        }
        .code-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #1565c0;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .code-value {
            font-size: 2.4rem;
            font-weight: 900;
            color: #0d2957;
            letter-spacing: 6px;
        }
        .code-expires {
            font-size: 0.8rem;
            color: #555;
            margin-top: 6px;
        }
        .code-expires span { color: #e53935; font-weight: 700; }

        /* ── Button ── */
        .btn-verify {
            display: inline-block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1565c0, #1976d2);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 18px;
            transition: opacity 0.2s;
        }
        .btn-verify:hover { opacity: 0.88; }

        /* ── Warning / Error box ── */
        .warn-box {
            background: #fff3e0;
            border: 1.5px solid #ffcc80;
            border-radius: 10px;
            padding: 13px 16px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            text-align: left;
            margin-bottom: 18px;
            font-size: 0.83rem;
            color: #555;
        }
        .warn-box .warn-icon { font-size: 1.3rem; flex-shrink: 0; }
        .warn-box a { color: #1565c0; font-weight: 700; text-decoration: underline; }

        /* ── Success box ── */
        .success-box {
            background: #e8f5e9;
            border: 1.5px solid #a5d6a7;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 18px;
            font-size: 0.9rem;
            color: #2e7d32;
            font-weight: 600;
        }

        .ignore-note {
            font-size: 0.78rem;
            color: #aaa;
            margin-bottom: 20px;
        }

        /* ── Footer ── */
        .card-footer {
            border-top: 1px solid #eee;
            padding-top: 16px;
            font-size: 0.75rem;
            color: #bbb;
        }
        .card-footer strong { display: block; margin-bottom: 2px; }
    </style>
</head>
<body>

    <!-- Top Header -->
    <div class="top-bar">
        <div class="top-bar-icon">🎓</div>
        <div class="top-bar-text">
            <h1>Attendance System</h1>
            <p>Track • Manage • Achieve</p>
        </div>
    </div>

    <!-- Wave -->
    <div class="wave"></div>

    <!-- Card -->
    <div class="card">

        <?php if ($success): ?>
            <!-- ✅ SUCCESS -->
            <span class="icon-emoji">🎉</span>
            <h2>Thank You!</h2>
            <p class="subtitle">Email Successfully Verified</p>
            <p class="desc">
                Your email address has been confirmed.<br>
                Your account is now fully activated and ready to use.
            </p>
            <div class="success-box">
                ✅ Account activated successfully!
            </div>
            <a href="<?= rtrim($_ENV['APP_BASE_URL'] ?? ('http://'.$_SERVER['HTTP_HOST'].'/AttendanceSystem'), '/') ?>/student_login.php" class="btn-verify">
                🔐 Go to Login Page
            </a>
            <p style="margin-top:12px; font-size:0.82rem; color:#aaa;">
                You can now close this tab and log in to your account.
            </p>

        <?php else: ?>
            <!-- ❌ EXPIRED / INVALID -->
            <span class="icon-emoji">📧</span>
            <h2>Verify Your Email Address</h2>
            <p class="subtitle">Hello, Student!</p>
            <p class="desc">
                Thank you for registering with our Attendance System.<br>
                Please verify your email address to activate your account.
            </p>

            <div class="warn-box">
                <span class="warn-icon">⚠️</span>
                <div>
                    <strong style="color:#e65100;">Link Expired or Already Used</strong>
                    If the link is expired, please request a new verification email.<br>
                    <a href="student_signup.php">Resend Verification Email</a>
                </div>
            </div>

        <?php endif; ?>

        <p class="ignore-note">
            If you did not create an account, you can safely ignore this email.
        </p>

        <div class="card-footer">
            <strong>© <?= date('Y') ?> Attendance System. All rights reserved.</strong>
            Together for a Smarter Learning Environment.
        </div>
    </div>

</body>
</html>
