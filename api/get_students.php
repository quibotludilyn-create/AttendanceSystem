<?php
header('Content-Type: application/json');
require_once '../config/database.php';
$conn = getConnection();

if (isset($_GET['id'])) {
    $id  = intval($_GET['id']);
    $res = $conn->query("
        SELECT id, student_id, full_name, course, year_level, section
        FROM students WHERE id = $id
    ");
    echo json_encode($res ? $res->fetch_assoc() : null);
} else {
    $res  = $conn->query("
        SELECT id, student_id, full_name, course, year_level, section, face_descriptor
        FROM students
    ");
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) $rows[] = $row;
    }
    echo json_encode($rows);
}
