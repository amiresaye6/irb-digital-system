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
require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../classes/Database.php';

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
    $database = new Database();
    $db = $database->conn;

    // Get student info for notification
    $app_sql = "SELECT student_id, serial_number FROM applications WHERE id = ?";
    $app_stmt = $db->prepare($app_sql);
    $app_stmt->bind_param("i", $application_id);
    $app_stmt->execute();
    $app_row = $app_stmt->get_result()->fetch_assoc();

    if ($app_row) {
        // Build notification message based on decision
        $decisionLabels = [
            'approved' => 'تمت الموافقة على بحثك من قبل المراجع',
            'needs_modification' => 'بحثك يحتاج إلى تعديلات بناءً على ملاحظات المراجع الفنية. يرجى مراجعة التعليقات وتحديث المستندات',
            'rejected' => 'تم رفض بحثك من قبل المراجع. يرجى مراجعة أسباب الرفض في تفاصيل البحث',
        ];
        $msg = ($decisionLabels[$decision] ?? 'تم تحديث حالة بحثك') . " ({$app_row['serial_number']}).";

        // Send notification to student
        Applications::createNotification($db, $app_row['student_id'], $application_id, $msg);

        // Log the reviewer decision
        $logLabels = [
            'approved' => 'موافقة على البحث من قبل المراجع',
            'needs_modification' => 'طلب تعديلات على البحث من قبل المراجع',
            'rejected' => 'رفض البحث من قبل المراجع',
        ];
        $logAction = ($logLabels[$decision] ?? $decision) . " - بواسطة المراجع";
        Applications::createLog($db, $application_id, $reviewer_id, $logAction);
    }

    header("Location: review_form.php?application_id=$application_id&success=1");
} else {
    header("Location: submit_decision.php?application_id=$application_id&error=" . urlencode($result['message']));
}
exit;
