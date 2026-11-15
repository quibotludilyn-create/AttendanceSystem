<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

$input   = json_decode(file_get_contents('php://input'), true);
$name    = $conn->real_escape_string(trim($input['name']    ?? ''));
$role    = in_array($input['role'] ?? '', ['student','teacher','admin','other']) ? $input['role'] : 'other';
$rating  = max(1, min(5, intval($input['rating'] ?? 5)));
$message = $conn->real_escape_string(trim($input['message'] ?? ''));

// Get student_id from session if logged in as student
$student_id = isset($_SESSION['student_id']) ? intval($_SESSION['student_id']) : 'NULL';

if (!$name || !$message) {
    echo json_encode(['success' => false, 'message' => 'Gmail and message are required.']);
    exit;
}
if (!filter_var($name, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid Gmail address.']);
    exit;
}
if (strlen($message) < 10) {
    echo json_encode(['success' => false, 'message' => 'Message must be at least 10 characters.']);
    exit;
}

// Save feedback with student_id
$conn->query("INSERT INTO feedbacks (name, student_id, role, rating, message) VALUES ('$name',$student_id,'$role',$rating,'$message')");
$feedback_id = $conn->insert_id;
$feedback_id = $conn->insert_id;

// ── Notify ALL admins ───────────────────────────────
$stars        = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
$role_icons   = ['student'=>'👨‍🎓','teacher'=>'👨‍🏫','admin'=>'🛡️','other'=>'👤'];
$role_icon    = $role_icons[$role] ?? '👤';
$short_msg    = $conn->real_escape_string(strlen($message) > 80 ? substr($message, 0, 80) . '...' : $message);
$notif_title  = $conn->real_escape_string("New Feedback Received $stars");
$notif_msg    = $conn->real_escape_string("$role_icon From: $name ($role) — \"$short_msg\"");

$admins = $conn->query("SELECT id FROM users WHERE role='admin'");
if ($admins) {
    while ($admin = $admins->fetch_assoc()) {
        $aid = intval($admin['id']);
        $conn->query("INSERT INTO notifications (recipient_type, recipient_id, title, message, type, is_read)
                      VALUES ('admin', $aid, '$notif_title', '$notif_msg', 'system', 0)");
    }
}

echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
