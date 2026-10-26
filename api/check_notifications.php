<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
$conn = getConnection();

$recip_type = '';
$recip_id   = 0;

if (isset($_SESSION['user_id'])) {
    $recip_type = 'admin';
    $recip_id   = intval($_SESSION['user_id']);
} elseif (isset($_SESSION['teacher_id'])) {
    $recip_type = 'teacher';
    $recip_id   = intval($_SESSION['teacher_id']);
} elseif (isset($_SESSION['student_id'])) {
    $recip_type = 'student';
    $recip_id   = intval($_SESSION['student_id']);
}

if (!$recip_id) {
    echo json_encode(array('count' => 0, 'notifications' => array()));
    exit;
}

$res = $conn->query("
    SELECT id, title, message, type, created_at
    FROM notifications
    WHERE recipient_type='$recip_type' AND recipient_id=$recip_id AND is_read=0
    ORDER BY created_at DESC
    LIMIT 5
");

$notifs = array();
if ($res) {
    while ($n = $res->fetch_assoc()) {
        $notifs[] = $n;
    }
}

echo json_encode(array(
    'count'         => count($notifs),
    'notifications' => $notifs
));
