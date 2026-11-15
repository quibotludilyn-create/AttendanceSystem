<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load .env if not already loaded
if (empty($_ENV['MAIL_HOST'])) {
    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
            [$key, $val] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($val);
        }
    }
}

function sendStudentVerificationEmail(mysqli $conn, int $studentId, string $email): void
{
    $required = [
        'APP_BASE_URL',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    foreach ($required as $key) {
        if (empty($_ENV[$key])) {
            throw new RuntimeException("Missing .env setting: $key");
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid recipient email.');
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $conn->prepare(
        'UPDATE students
         SET email_verification_token_hash = ?,
             email_verification_expires_at = ?,
             email_verified_at = NULL
         WHERE id = ?'
    );

    if (!$stmt) {
        throw new RuntimeException($conn->error);
    }

    $stmt->bind_param('ssi', $tokenHash, $expires, $studentId);

    if (!$stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }

    $stmt->close();

    $verifyUrl = rtrim($_ENV['APP_BASE_URL'], '/')
        . '/verify_email.php?token=' . urlencode($token);

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $_ENV['MAIL_HOST'];
    $mail->SMTPAuth = true;
    $mail->Username = $_ENV['MAIL_USERNAME'];
    $mail->Password = str_replace(' ', '', $_ENV['MAIL_PASSWORD']);
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $_ENV['MAIL_PORT'];

    $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Verify your AttendEase email';
    $mail->Body = '<p>Click to verify your email address:</p><p><a href="'
        . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8')
        . '">Verify email</a></p>';
    $mail->AltBody = "Verify your email: $verifyUrl";

    try {
        $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        throw new RuntimeException('SMTP send failed: ' . $e->getMessage(), 0, $e);
    }
}