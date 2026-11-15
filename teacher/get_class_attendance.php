<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['error' => 'Unauthorized', 'students' => []]);
    exit;
}

require_once '../config/database.php';
$conn = getConnection();

$teacher_id = intval($_SESSION['teacher_id']);
$subject_id = intval($_GET['subject_id'] ?? 0);

if ($subject_id <= 0) {
    echo json_encode(['students' => []]);
    exit;
}

// Verify this subject belongs to the logged-in teacher
$sub = $conn->query("
    SELECT subject_name FROM subjects
    WHERE id = $subject_id AND teacher_id = $teacher_id AND is_active = 1
");
if (!$sub || $sub->num_rows === 0) {
    echo json_encode(['students' => []]);
    exit;
}
$subject_name  = $sub->fetch_assoc()['subject_name'];
$subj_name_esc = $conn->real_escape_string($subject_name);

// Fetch enrolled students with attendance counts
// Handles both new records (subject_id set) and old records (subject_id NULL, matched by name)
$res = $conn->query("
    SELECT
        e.id          AS eid,
        st.student_id,
        st.full_name,
        st.course,
        st.year_level,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.student_id = st.id
           AND a.status = 'present'
           AND (a.subject_id = $subject_id
                OR (a.subject_id IS NULL AND LOWER(a.subject) = LOWER('$subj_name_esc')))
        ) AS present_count,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.student_id = st.id
           AND a.status = 'late'
           AND (a.subject_id = $subject_id
                OR (a.subject_id IS NULL AND LOWER(a.subject) = LOWER('$subj_name_esc')))
        ) AS late_count,
        (SELECT COUNT(*) FROM attendance a
         WHERE a.student_id = st.id
           AND a.status = 'absent'
           AND (a.subject_id = $subject_id
                OR (a.subject_id IS NULL AND LOWER(a.subject) = LOWER('$subj_name_esc')))
        ) AS absent_count
    FROM enrollments e
    JOIN students st ON e.student_id = st.id
    WHERE e.subject_id = $subject_id
    ORDER BY st.full_name
");

$students = [];
while ($row = $res->fetch_assoc()) {
    $students[] = [
        'eid'           => intval($row['eid']),
        'student_id'    => htmlspecialchars($row['student_id']),
        'full_name'     => htmlspecialchars($row['full_name']),
        'course'        => htmlspecialchars($row['course'] ?? ''),
        'year_level'    => intval($row['year_level']),
        'present_count' => intval($row['present_count']),
        'late_count'    => intval($row['late_count']),
        'absent_count'  => intval($row['absent_count']),
    ];
}

echo json_encode(['students' => $students]);
