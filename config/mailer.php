<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

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

/**
 * Send an email using Brevo SMTP credentials from .env
 * 
 * @param string $toEmail  Recipient email address
 * @param string $toName   Recipient name
 * @param string $subject  Email subject
 * @param string $htmlBody HTML content of the email
 * @return array ['success' => bool, 'error' => string]
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    // Load .env values
    $host       = $_ENV['MAIL_HOST']         ?? 'smtp-relay.brevo.com';
    $port       = intval($_ENV['MAIL_PORT']  ?? 587);
    $username   = $_ENV['MAIL_USERNAME']      ?? '';
    $password   = $_ENV['MAIL_PASSWORD']      ?? '';
    $fromAddr   = $_ENV['MAIL_FROM_ADDRESS']  ?? $username;
    $fromName   = $_ENV['MAIL_FROM_NAME']     ?? 'AttendEase';

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;

        // Recipients
        $mail->setFrom($fromAddr, $fromName);
        $mail->addAddress($toEmail, $toName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return ['success' => true, 'error' => ''];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

/**
 * Build a welcome email HTML for a newly registered student
 */
function buildWelcomeEmail(string $fullName, string $studentId, string $course, int $yearLevel): string {
    $baseUrl = rtrim($_ENV['APP_BASE_URL'] ?? 'http://10.33.207.129/AttendanceSystem', '/');
    $loginUrl = $baseUrl . '/student_login.php';
    return "
    <div style='font-family: Arial, sans-serif; max-width: 560px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden;'>
        <div style='background: linear-gradient(135deg, #1b5e20, #2e7d32); padding: 28px 32px; text-align: center;'>
            <h1 style='color: white; margin: 0; font-size: 1.5rem;'>🎓 Welcome to AttendEase!</h1>
        </div>
        <div style='padding: 28px 32px;'>
            <p style='font-size: 1rem; color: #333;'>Hi <strong>" . htmlspecialchars($fullName) . "</strong>,</p>
            <p style='color: #555;'>Your student account has been successfully created. Here are your details:</p>
            <div style='background: #f1f8e9; border: 1px solid #a5d6a7; border-radius: 8px; padding: 16px; margin: 18px 0;'>
                <table style='width: 100%; font-size: 0.92rem; color: #333;'>
                    <tr><td style='padding: 5px 0; color: #777; width: 40%;'>Student ID</td><td><strong>" . htmlspecialchars($studentId) . "</strong></td></tr>
                    <tr><td style='padding: 5px 0; color: #777;'>Full Name</td><td><strong>" . htmlspecialchars($fullName) . "</strong></td></tr>
                    <tr><td style='padding: 5px 0; color: #777;'>Course</td><td><strong>" . htmlspecialchars($course) . " — Year {$yearLevel}</strong></td></tr>
                </table>
            </div>
            <p style='color: #555; font-size: 0.9rem;'>You can now log in using your <strong>Student ID</strong> and password at:</p>
            <div style='text-align: center; margin: 18px 0;'>
                <a href='" . htmlspecialchars($loginUrl) . "'
                   style='background: #2e7d32; color: white; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: bold; display: inline-block;'>
                    🔐 Go to Login
                </a>
            </div>
            <p style='color: #aaa; font-size: 0.8rem; margin-top: 24px; border-top: 1px solid #eee; padding-top: 14px;'>
                This is an automated message from AttendEase. Please do not reply to this email.
            </p>
        </div>
    </div>
    ";
}
