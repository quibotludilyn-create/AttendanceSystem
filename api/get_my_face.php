<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

// Support: active student, pending login (Google OAuth flow or old flow)
if (isset($_SESSION['student_id'])) {
    $id = intval($_SESSION['student_id']);
} elseif (isset($_SESSION['login_student_id'])) {
    $id = intval($_SESSION['login_student_id']);
} else {
    echo json_encode(['face_descriptor' => null, 'error' => 'Not logged in.']);
    exit;
}

$res = $conn->query("SELECT face_descriptor FROM students WHERE id=$id");

if ($res && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
    echo json_encode(['face_descriptor' => $row['face_descriptor']]);
} else {
    echo json_encode(['face_descriptor' => null]);
}
