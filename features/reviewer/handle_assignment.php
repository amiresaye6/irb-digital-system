<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('reviewer');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pending_assignments.php");
    exit;
}

require_once __DIR__ . '/../../classes/Reviews.php';

$reviewsObj  = new Reviews();
$reviewer_id = $_SESSION['user_id'];
$review_id   = intval($_POST['review_id'] ?? 0);
$action      = $_POST['action'] ?? '';

if (!$review_id || !in_array($action, ['accept', 'refuse'])) {
    $_SESSION['assignment_error'] = "طلب غير صالح.";
    header("Location: pending_assignments.php");
    exit;
}

if ($action === 'accept') {
    $ok = $reviewsObj->acceptAssignment($review_id, $reviewer_id);
    if ($ok) {
        $_SESSION['assignment_success'] = "تم قبول الإسناد بنجاح. يمكنك الآن مراجعة البحث.";
    } else {
        $_SESSION['assignment_error'] = "تعذّر قبول الإسناد. ربما تم تحديثه مسبقاً.";
    }
} elseif ($action === 'refuse') {
    $reason = trim($_POST['refusal_reason'] ?? '');
    if (empty($reason)) {
        $_SESSION['assignment_error'] = "يجب إدخال سبب الاعتذار عن المراجعة.";
        header("Location: pending_assignments.php");
        exit;
    }
    $ok = $reviewsObj->refuseAssignment($review_id, $reviewer_id, $reason);
    if ($ok) {
        $_SESSION['assignment_success'] = "تم تسجيل اعتذارك عن هذا الإسناد.";
    } else {
        $_SESSION['assignment_error'] = "تعذّر تسجيل الاعتذار. ربما تم تحديثه مسبقاً.";
    }
}

header("Location: pending_assignments.php");
exit;
