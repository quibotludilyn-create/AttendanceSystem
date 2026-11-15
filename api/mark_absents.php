<?php
/**
 * Auto-mark absent for students who missed a class today.
 * Called when teacher or student opens their dashboard.
 * Only marks absent for schedules whose end_time has passed today.
 */
require_once '../config/database.php';
$conn = getConnection();

$today      = date('Y-m-d');
$dayOfWeek  = date('l'); // e.g. Monday
$now        = date('H:i:s');

// Find all schedules for today that have already ended
$schedules = $conn->query("
    SELECT s.id as schedule_id, s.subject_id, s.start_time, s.end_time,
           sub.subject_name, sub.teacher_id
    FROM schedules s
    JOIN subjects sub ON sub.id = s.subject_id
    WHERE s.day_of_week = '$dayOfWeek'
      AND s.end_time <= '$now'
");

if (!$schedules || $schedules->num_rows === 0) {
    echo json_encode(['success' => true, 'marked' => 0]);
    exit;
}

$totalMarked = 0;

while ($sched = $schedules->fetch_assoc()) {
    $subject_id   = intval($sched['subject_id']);
    $subject_name = $conn->real_escape_string($sched['subject_name']);
    $teacher_id   = intval($sched['teacher_id']);

    // Get all enrolled students for this subject
    $students = $conn->query("
        SELECT student_id FROM enrollments
        WHERE subject_id = $subject_id
    ");

    if (!$students) continue;

    while ($stu = $students->fetch_assoc()) {
        $student_id = intval($stu['student_id']);

        // Check if already has attendance record for today + this subject
        $exists = $conn->query("
            SELECT id FROM attendance
            WHERE student_id = $student_id
              AND date = '$today'
              AND subject = '$subject_name'
            LIMIT 1
        ");

        if ($exists && $exists->num_rows > 0) continue; // already recorded

        // Mark as absent
        $conn->query("
            INSERT INTO attendance (student_id, date, time_in, status, subject, recorded_by)
            VALUES ($student_id, '$today', '$now', 'absent', '$subject_name', $teacher_id)
        ");
        $totalMarked++;

        // Send notification to student
        $notif_title = $conn->real_escape_string("Absent: $subject_name");
        $notif_msg   = $conn->real_escape_string("You were marked absent in $subject_name on " . date('F d, Y') . ".");
        $conn->query("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, type, is_read)
            VALUES ('student', $student_id, '$notif_title', '$notif_msg', 'attendance', 0)
        ");
    }
}

echo json_encode(['success' => true, 'marked' => $totalMarked]);
