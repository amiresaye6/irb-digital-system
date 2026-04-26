<?php
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('student'); 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$student_id = $_SESSION['user_id'];
$certificates = $appObj->getCertificates($student_id);

?>
