<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Admin only.");
}

require_once __DIR__ . '/../../classes/Reviews.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_reviewer'])) {
    $application_id = intval($_POST['application_id']);
    $reviewer_id = intval($_POST['reviewer_id']);
    $admin_id = intval($_SESSION['user_id']);
    
    if (empty($application_id) || empty($reviewer_id)) {
        header("Location: assign_reviewers.php?status=error");
        exit;
    }
    
    $reviewsObj = new Reviews();
    
    if ($reviewsObj->assignReviewer($application_id, $reviewer_id, $admin_id)) {
        header("Location: assign_reviewers.php?status=success");
        exit;
    } else {
        header("Location: assign_reviewers.php?status=error");
        exit;
    }
} else {
    header("Location: assign_reviewers.php");
    exit;
}
?>
