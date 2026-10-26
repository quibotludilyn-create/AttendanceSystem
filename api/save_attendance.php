<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$student_id  = intval($input['student_id']);
$subject     = $conn->real_escape_string($input['subject'] ?? 'General');
$time_in     = $conn->real_escape_string($input['time_in'] ?? date('H:i:s'));
$status      = in_array($input['status'], ['present', 'late', 'absent'])
               ? $input['status'] : 'present';
$date        = date('Y-m-d');
$recorded_by = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'NULL';

// Check if already recorded today for this subject
$check = $conn->query("
    SELECT id FROM attendance
    WHERE student_id = $student_id
      AND date = '$date'
      AND subject = '$subject'
");

if ($check && $check->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Student already recorded for this subject today.'
    ]);
    exit;
}

$sql = "INSERT INTO attendance (student_id, date, time_in, status, subject, recorded_by)
        VALUES ($student_id, '$date', '$time_in', '$status', '$subject', $recorded_by)";

if ($conn->query($sql)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
