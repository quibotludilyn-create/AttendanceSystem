<?php

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
 * Send an email using Brevo HTTP API (no SMTP port needed)
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    $apiKey   = $_ENV['BREVO_API_KEY']       ?? $_ENV['MAIL_PASSWORD'] ?? '';
    $fromAddr = $_ENV['MAIL_FROM_ADDRESS']    ?? '';
    $fromName = $_ENV['MAIL_FROM_NAME']       ?? 'AttendEase';

    $payload = json_encode([
        'sender'      => ['name' => $fromName, 'email' => $fromAddr],
        'to'          => [['email' => $toEmail, 'name' => $toName]],
        'subject'     => $subject,
        'htmlContent' => $htmlBody,
        'textContent' => strip_tags($htmlBody),
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'cURL error: ' . $curlErr];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $data = json_decode($response, true);
        return ['success' => false, 'error' => $data['message'] ?? $response];
    }

    return ['success' => true, 'error' => ''];
}

/**
 * Build a welcome email HTML for a newly registered student
 */
function buildWelcomeEmail(string $fullName, string $studentId, string $course, int $yearLevel): string {
    $baseUrl  = rtrim($_ENV['APP_BASE_URL'] ?? '', '/');
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
