<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['descriptor'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

// Determine student ID — from signup session OR admin panel
if (isset($_SESSION['signup_student_id'])) {
    // Student registering their OWN face during sign up
    $student_id = intval($_SESSION['signup_student_id']);
} elseif (isset($_SESSION['user_id']) && isset($input['student_id'])) {
    // Admin registering a student's face
    $student_id = intval($input['student_id']);
} else {
    echo json_encode(['success' => false, 'message' => 'Not authorized.']);
    exit;
}

// Verify student exists
$check = $conn->query("SELECT id FROM students WHERE id = $student_id");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

$descriptor = $conn->real_escape_string(json_encode($input['descriptor']));
$sql = "UPDATE students SET face_descriptor = '$descriptor' WHERE id = $student_id";

if ($conn->query($sql)) {
    // If coming from signup, mark session for done page
    if (isset($_SESSION['signup_student_id'])) {
        $_SESSION['signup_done_student_id'] = $student_id;
        // keep signup_student_id so done page can clean up
    }
    echo json_encode(['success' => true, 'message' => 'Face registered successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
