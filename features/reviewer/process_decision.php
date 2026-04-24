<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reviewer') {
    header("Location: /irb-digital-system/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: assigned_reserches.php");
    exit;
}

require_once __DIR__ . '/../../classes/Reviews.php';

$application_id = intval($_POST['application_id'] ?? 0);
$decision = $_POST['decision'] ?? '';
$comments = trim($_POST['comments'] ?? '');
$reviewer_id = $_SESSION['user_id'];

if ($application_id <= 0 || empty($decision)) {
    header("Location: submit_decision.php?application_id=$application_id&error=" . urlencode('بيانات غير مكتملة'));
    exit;
}

$reviewsObj = new Reviews();
$result = $reviewsObj->submitReviewDecision($application_id, $reviewer_id, $decision, $comments);

if ($result['success']) {
    header("Location: review_form.php?application_id=$application_id&success=1");
} else {
    header("Location: submit_decision.php?application_id=$application_id&error=" . urlencode($result['message']));
}
exit;
