<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['latitude']) || !isset($input['longitude'])) {
    echo json_encode(['allowed' => false, 'message' => 'Invalid location data.']);
    exit;
}

$user_lat = floatval($input['latitude']);
$user_lon = floatval($input['longitude']);

// ── Haversine distance formula ──────────────────────────
function haversine($lat1, $lon1, $lat2, $lon2) {
    $R  = 6371000; // meters
    $dL = deg2rad($lat2 - $lat1);
    $dl = deg2rad($lon2 - $lon1);
    $a  = sin($dL/2)*sin($dL/2) +
          cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
          sin($dl/2) * sin($dl/2);
    return round($R * 2 * atan2(sqrt($a), sqrt(1-$a)), 1);
}

$location = null;
$source   = '';

// ── Priority 1: Teacher's location (main source) ─────────
// Get teacher location for this student's enrolled subjects
if (isset($_SESSION['student_id'])) {
    $sid = intval($_SESSION['student_id']);

    // Find teacher location for any active subject this student is enrolled in
    // Prioritize subject-specific location over default teacher location
    $res = $conn->query("
        SELECT tl.*, t.full_name as teacher_name, sub.subject_name, sub.section
        FROM teacher_locations tl
        JOIN subjects sub ON tl.teacher_id = sub.teacher_id
        JOIN enrollments e ON e.subject_id = sub.id
        JOIN teachers t ON tl.teacher_id = t.id
        WHERE e.student_id = $sid
          AND tl.is_active = 1
          AND sub.is_active = 1
        ORDER BY tl.subject_id DESC, tl.updated_at DESC
        LIMIT 1
    ");

    if ($res && $res->num_rows > 0) {
        $location = $res->fetch_assoc();
        $source   = 'teacher';
    }

    // Fallback: teacher's default location (no subject_id)
    if (!$location) {
        $res = $conn->query("
            SELECT tl.*, t.full_name as teacher_name
            FROM teacher_locations tl
            JOIN subjects sub ON tl.teacher_id = sub.teacher_id
            JOIN enrollments e ON e.subject_id = sub.id
            JOIN teachers t ON tl.teacher_id = t.id
            WHERE e.student_id = $sid
              AND tl.is_active = 1
              AND tl.subject_id IS NULL
            ORDER BY tl.updated_at DESC
            LIMIT 1
        ");
        if ($res && $res->num_rows > 0) {
            $location = $res->fetch_assoc();
            $source   = 'teacher_default';
        }
    }
}

// ── Priority 2: Admin global location ────────────────────
if (!$location) {
    $res = $conn->query("SELECT * FROM location_settings WHERE is_active = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $location = $res->fetch_assoc();
        $source   = 'admin';
    }
}

// ── No location set — allow (warn) ───────────────────────
if (!$location) {
    echo json_encode([
        'allowed'         => true,
        'distance_meters' => 0,
        'allowed_radius'  => 0,
        'message'         => 'No location restriction set. Attendance allowed from anywhere.',
        'warning'         => true,
        'source'          => 'none'
    ]);
    exit;
}

$school_lat     = floatval($location['latitude']);
$school_lon     = floatval($location['longitude']);
$allowed_radius = intval($location['radius_meters']);
$loc_name       = $location['subject_name'] ?? $location['name'] ?? 'Campus';
$teacher_name   = $location['teacher_name'] ?? null;

$distance = haversine($school_lat, $school_lon, $user_lat, $user_lon);
$allowed  = ($distance <= $allowed_radius);

$message = $allowed
    ? "✅ Within allowed area ({$distance}m from {$loc_name})."
    : "❌ You are {$distance}m away from {$loc_name}. Must be within {$allowed_radius}m.";

if (!$allowed && $teacher_name) {
    $message .= " Location set by: {$teacher_name}.";
}

echo json_encode([
    'allowed'         => $allowed,
    'distance_meters' => $distance,
    'allowed_radius'  => $allowed_radius,
    'location_name'   => $loc_name,
    'teacher_name'    => $teacher_name,
    'source'          => $source,
    'message'         => $message
]);
