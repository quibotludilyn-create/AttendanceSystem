<?php
session_start();
unset($_SESSION['teacher_id'],$_SESSION['teacher_name'],$_SESSION['teacher_emp'],$_SESSION['teacher_dept']);
session_destroy();
header('Location: teacher_login.php');
exit;
