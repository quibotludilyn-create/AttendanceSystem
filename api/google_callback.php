<?php
session_start();
require_once '../config/google.php';
require_once '../config/database.php';

// ── Helper: redirect with error ──
function fail($msg) {
    $_SESSION['google_error'] = $msg;
    header('Location: ../student_login.php');
    exit;
}

// ── 1. Check state (CSRF protection) ──
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['google_state'] ?? '')) {
    fail('Invalid state. Please try again.');
}
unset($_SESSION['google_state']);

// ── 2. Check for error from Google ──
if (isset($_GET['error'])) {
    fail('Google login was cancelled or denied.');
}

if (empty($_GET['code'])) {
    fail('No authorization code received.');
}

// ── 3. Exchange code for access token ──
$postData = http_build_query([
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

$ctx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postData,
        'timeout' => 15,
    ]
]);

$tokenResponse = @file_get_contents(GOOGLE_TOKEN_URL, false, $ctx);
if (!$tokenResponse) {
    fail('Failed to get access token from Google.');
}

$tokenData = json_decode($tokenResponse, true);
if (empty($tokenData['access_token'])) {
    fail('Invalid token response from Google.');
}

// ── 4. Get user info from Google ──
$ctx2 = stream_context_create([
    'http' => [
        'method'  => 'GET',
        'header'  => "Authorization: Bearer " . $tokenData['access_token'] . "\r\n",
        'timeout' => 15,
    ]
]);

$userResponse = @file_get_contents(GOOGLE_USERINFO_URL, false, $ctx2);
if (!$userResponse) {
    fail('Failed to get user info from Google.');
}

$googleUser = json_decode($userResponse, true);
if (empty($googleUser['email'])) {
    fail('Could not retrieve email from Google.');
}

$googleEmail = strtolower(trim($googleUser['email']));
$googleId    = $googleUser['sub'] ?? '';
$googleName  = $googleUser['name'] ?? '';

// ── 5. Find student by Google email or Google ID ──
$conn = getConnection();
$safeEmail    = $conn->real_escape_string($googleEmail);
$safeGoogleId = $conn->real_escape_string($googleId);

$res = $conn->query("
    SELECT * FROM students
    WHERE (google_email = '$safeEmail' OR google_id = '$safeGoogleId')
      AND is_active = 1
    LIMIT 1
");

// If not found by google_id, try matching by stored email field
if (!$res || $res->num_rows === 0) {
    $res = $conn->query("
        SELECT * FROM students
        WHERE email = '$safeEmail' AND is_active = 1
        LIMIT 1
    ");
}

if (!$res || $res->num_rows === 0) {
    // No matching student — store google info in session and ask to link
    $_SESSION['google_pending'] = [
        'email'     => $googleEmail,
        'google_id' => $googleId,
        'name'      => $googleName,
    ];
    header('Location: ../student_link_google.php');
    exit;
}

$student = $res->fetch_assoc();

// Save google_email & google_id if not set
if (empty($student['google_email']) || empty($student['google_id'])) {
    $conn->query("UPDATE students SET google_email='$safeEmail', google_id='$safeGoogleId' WHERE id=" . $student['id']);
}

// Check face is registered
if (!$student['face_descriptor']) {
    $_SESSION['google_error'] = "Your face is not registered yet. Please contact your admin.";
    header('Location: ../student_login.php');
    exit;
}

// ── 6. Set pending session → go to password check ──
$_SESSION['login_student_id']       = $student['id'];
$_SESSION['login_student_name']     = $student['full_name'];
$_SESSION['login_via_google']       = true;
$_SESSION['login_google_email']     = $googleEmail;

header('Location: ../student_password_check.php');
exit;
