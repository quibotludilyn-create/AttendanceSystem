<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['subjects' => [], 'error' => 'Not logged in.']);
    exit;
}

require_once '../config/database.php';
$conn = getConnection();

$student_id = intval($_SESSION['student_id']);
$today      = date('Y-m-d');
$day_name   = date('l'); // e.g. Monday, Tuesday

// Get enrolled subjects with teacher info and today's schedule
$res = $conn->query("
    SELECT
        sub.id            AS subject_id,
        sub.subject_code,
        sub.subject_name,
        sub.section,
        sub.course,
        sub.year_level,
        sub.room,
        t.full_name       AS teacher_name,
        t.id              AS teacher_id,
        sch.start_time,
        sch.end_time,
        sch.day_of_week,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.student_id = $student_id
           AND a.subject_id = sub.id
           AND a.date = '$today') AS already_recorded
    FROM enrollments e
    JOIN subjects sub ON e.subject_id = sub.id
    JOIN teachers  t   ON sub.teacher_id = t.id
    LEFT JOIN schedules sch ON sch.subject_id = sub.id
        AND sch.day_of_week = '$day_name'
    WHERE e.student_id = $student_id
      AND sub.is_active = 1
    ORDER BY sch.start_time ASC, sub.subject_name ASC
");

$subjects = [];
while ($row = $res->fetch_assoc()) {
    $subjects[] = $row;
}

echo json_encode(['subjects' => $subjects, 'today' => $today, 'day' => $day_name]);
