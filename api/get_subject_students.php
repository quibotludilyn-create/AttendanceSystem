<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$subject_id = intval($_GET['subject_id'] ?? 0);
$teacher_id = intval($_SESSION['teacher_id']);
$today      = date('Y-m-d');

if (!$subject_id) {
    echo json_encode(['success' => false, 'message' => 'No subject selected']);
    exit;
}

// Verify subject belongs to teacher
$sub = $conn->query("SELECT * FROM subjects WHERE id=$subject_id AND teacher_id=$teacher_id AND is_active=1");
if (!$sub || $sub->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Subject not found']);
    exit;
}
$subject = $sub->fetch_assoc();

// Get enrolled students with face descriptors
$res = $conn->query("
    SELECT s.id, s.student_id, s.full_name, s.face_descriptor
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.subject_id = $subject_id
      AND s.face_descriptor IS NOT NULL
      AND s.face_descriptor != ''
    ORDER BY s.full_name
");

$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = [
        'id'          => intval($row['id']),
        'student_id'  => $row['student_id'],
        'full_name'   => $row['full_name'],
        'descriptor'  => json_decode($row['face_descriptor'], true)
    ];
}

// Get already recorded students today
$recorded = [];
$rec = $conn->query("
    SELECT student_id, status, time_in FROM attendance
    WHERE subject_id=$subject_id AND date='$today'
");
while ($r = $rec->fetch_assoc()) {
    $recorded[intval($r['student_id'])] = $r;
}

echo json_encode([
    'success'      => true,
    'subject_name' => $subject['subject_name'],
    'subject_id'   => $subject_id,
    'students'     => $students,
    'recorded'     => $recorded
]);
