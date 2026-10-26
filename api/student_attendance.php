<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once '../config/database.php';
$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit;
}

$student_db_id = intval($_SESSION['student_id']);
$subject_id    = intval($input['subject_id'] ?? 0);
$time_in       = $conn->real_escape_string($input['time_in'] ?? date('H:i:s'));
$status        = in_array($input['status'], ['present','late','absent']) ? $input['status'] : 'present';
$latitude      = isset($input['latitude'])  ? floatval($input['latitude'])  : null;
$longitude     = isset($input['longitude']) ? floatval($input['longitude']) : null;
$date          = date('Y-m-d');

if ($subject_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please select a subject.']);
    exit;
}

// Get subject + teacher info
$sub_res = $conn->query("
    SELECT sub.subject_name, sub.section, sub.subject_code,
           sub.teacher_id, t.full_name as teacher_name
    FROM subjects sub
    JOIN teachers t ON sub.teacher_id = t.id
    WHERE sub.id = $subject_id AND sub.is_active = 1
");

if (!$sub_res || $sub_res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Subject not found.']);
    exit;
}

$subject = $sub_res->fetch_assoc();
$teacher_id   = intval($subject['teacher_id']);
$subject_name = $subject['subject_name'];
$section      = $subject['section'];

// Check enrollment
$enrolled = $conn->query("
    SELECT id FROM enrollments
    WHERE student_id=$student_db_id AND subject_id=$subject_id
");
if (!$enrolled || $enrolled->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this subject.']);
    exit;
}

// Check already recorded today
$check = $conn->query("
    SELECT id FROM attendance
    WHERE student_id=$student_db_id AND date='$date' AND subject_id=$subject_id
");
if ($check && $check->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => "Attendance for '$subject_name' already recorded today."
    ]);
    exit;
}

// Insert attendance
$lat_val = $latitude  !== null ? $latitude  : 'NULL';
$lon_val = $longitude !== null ? $longitude : 'NULL';
$subj_esc = $conn->real_escape_string($subject_name);

$sql = "INSERT INTO attendance
        (student_id, date, time_in, status, subject, subject_id, teacher_id, latitude, longitude, location_verified)
        VALUES
        ($student_db_id, '$date', '$time_in', '$status', '$subj_esc', $subject_id, $teacher_id,
         $lat_val, $lon_val, 1)";

if (!$conn->query($sql)) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}

$student_name = $_SESSION['student_name'] ?? '';
$formatted_date = date('F d, Y');
$formatted_time = date('h:i A', strtotime($time_in));
$status_label   = ucfirst($status);

// ── Notify TEACHER ──────────────────────────────────────
$notif_title   = $conn->real_escape_string("Attendance Recorded");
$notif_msg_t   = $conn->real_escape_string(
    "$student_name has been marked $status_label in $subject_name ($section) at $formatted_time on $formatted_date."
);
$conn->query("
    INSERT INTO notifications (recipient_type, recipient_id, title, message, type, is_read)
    VALUES ('teacher', $teacher_id, '$notif_title', '$notif_msg_t', 'attendance', 0)
");

// ── Notify STUDENT ──────────────────────────────────────
$notif_msg_s = $conn->real_escape_string(
    "Your attendance for $subject_name ($section) has been recorded as $status_label at $formatted_time on $formatted_date."
);
$conn->query("
    INSERT INTO notifications (recipient_type, recipient_id, title, message, type, is_read)
    VALUES ('student', $student_db_id, '$notif_title', '$notif_msg_s', 'attendance', 0)
");

// ── Notify ADMIN (all admins) ────────────────────────────
$admins = $conn->query("SELECT id FROM users WHERE role='admin'");
if ($admins) {
    while ($admin = $admins->fetch_assoc()) {
        $notif_msg_a = $conn->real_escape_string(
            "$student_name marked $status_label in $subject_name ($section) — Teacher: {$subject['teacher_name']} at $formatted_time."
        );
        $conn->query("
            INSERT INTO notifications (recipient_type, recipient_id, title, message, type, is_read)
            VALUES ('admin', {$admin['id']}, '$notif_title', '$notif_msg_a', 'attendance', 0)
        ");
    }
}

echo json_encode([
    'success'      => true,
    'student_name' => $student_name,
    'subject_name' => $subject_name,
    'section'      => $section,
    'status'       => $status,
    'time_in'      => $time_in,
    'date'         => $date
]);
