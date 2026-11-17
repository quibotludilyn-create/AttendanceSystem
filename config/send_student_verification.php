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

function sendStudentVerificationEmail(mysqli $conn, int $studentId, string $email): void
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid recipient email.');
    }

    $token     = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires   = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $conn->prepare(
        'UPDATE students
         SET email_verification_token_hash = ?,
             email_verification_expires_at = ?,
             email_verified_at = NULL
         WHERE id = ?'
    );
    if (!$stmt) throw new RuntimeException($conn->error);
    $stmt->bind_param('ssi', $tokenHash, $expires, $studentId);
    if (!$stmt->execute()) throw new RuntimeException($stmt->error);
    $stmt->close();

    $verifyUrl = rtrim($_ENV['APP_BASE_URL'] ?? '', '/')
        . '/verify_email.php?token=' . urlencode($token);

    $fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? '';
    $fromName  = $_ENV['MAIL_FROM_NAME']    ?? 'AttendEase';
    $apiKey    = $_ENV['BREVO_API_KEY']     ?? $_ENV['MAIL_PASSWORD'] ?? '';

    $payload = json_encode([
        'sender'      => ['name' => $fromName, 'email' => $fromEmail],
        'to'          => [['email' => $email]],
        'subject'     => 'Verify your AttendEase email',
        'htmlContent' => '<p>Click to verify your email address:</p>
                          <p><a href="' . htmlspecialchars($verifyUrl, ENT_QUOTES, 'UTF-8') . '">Verify email</a></p>',
        'textContent' => "Verify your email: $verifyUrl",
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
        throw new RuntimeException('API request failed: ' . $curlErr);
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        $data = json_decode($response, true);
        throw new RuntimeException('Brevo API error: ' . ($data['message'] ?? $response));
    }
}
