<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['login_student_id'])) {
    echo json_encode(['success' => false, 'message' => 'No pending login session.']);
    exit;
}

require_once '../config/database.php';
$conn = getConnection();

$sid_db = intval($_SESSION['login_student_id']);
$res    = $conn->query("SELECT * FROM students WHERE id=$sid_db AND is_active=1");

if (!$res || $res->num_rows === 0) {
    unset($_SESSION['login_student_id'], $_SESSION['login_student_name'],
          $_SESSION['login_via_google'], $_SESSION['login_google_email']);
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

$student = $res->fetch_assoc();

// Clear temp login keys
unset($_SESSION['login_student_id'], $_SESSION['login_student_name'],
      $_SESSION['login_via_google'], $_SESSION['login_google_email']);

// Set full student session
$_SESSION['student_id']      = $student['id'];
$_SESSION['student_sid']     = $student['student_id'];
$_SESSION['student_name']    = $student['full_name'];
$_SESSION['student_course']  = $student['course'];
$_SESSION['student_year']    = $student['year_level'];
$_SESSION['student_section'] = $student['section'];

echo json_encode(['success' => true, 'redirect' => 'student/home.php']);
