<?php
session_start();

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('admin');

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
        require_once __DIR__ . '/../../classes/Applications.php';
        $db = (new Database())->conn;
        

        $appDetails = $reviewsObj->getApplicationDetails($application_id);
        $serial = $appDetails ? $appDetails['serial_number'] : '';
        
        $message = "تم تعيين بحث جديد لك لمراجعته. (رقم الملف: " . $serial . ")";
        Applications::createNotification($db, $reviewer_id, $application_id, $message);

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
