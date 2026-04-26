<?php
session_start();
require_once "../../init.php";
Auth::checkRole('student');

$user_id      = $_SESSION['user_id'];
$full_name    = trim($_POST['full_name']    ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$faculty      = trim($_POST['faculty']      ?? '');
$department   = trim($_POST['department']   ?? '');

$errors = [];

if(empty($phone_number)) {
    $errors[] = "رقم الهاتف مطلوب";
}
if(empty($full_name)) {
    $errors[] = "الاسم الكامل مطلوب";
}

if(!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: profile.php");
    exit();
}

$dbobj= new Database();

$data = [
    "full_name"    => $full_name,
    "phone_number" => $phone_number,
    "faculty"      => $faculty,
    "department"   => $department
];

if($dbobj->updateById("users", $user_id, $data)) {
    $dbobj->insert("logs", [
        "user_id" => $user_id,
        "action"  => "تحديث البيانات الشخصية",
        "type"    => "profile"
    ]);

    $_SESSION['full_name'] = $full_name;

    $_SESSION['success'] = "تم تحديث البيانات بنجاح";
} else {
    $_SESSION['errors'] = ["حدث خطأ، حاول مرة أخرى"];
}

header("Location: profile.php");
exit();
?>