<?php
session_start();
// Only clear student session keys
unset($_SESSION['student_id']);
unset($_SESSION['student_sid']);
unset($_SESSION['student_name']);
unset($_SESSION['student_course']);
unset($_SESSION['student_year']);
unset($_SESSION['student_section']);
session_destroy();
header('Location: student_login.php');
exit;
