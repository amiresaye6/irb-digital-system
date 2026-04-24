<?php
session_start();
require_once "../../init.php";
Auth::checkRole('student');

$user_id      = $_SESSION['user_id'];
$phone_number = trim($_POST['phone_number'] ?? '');
$faculty      = trim($_POST['faculty']      ?? '');
$department   = trim($_POST['department']   ?? '');

$errors = [];

if(empty($phone_number)) {
    $errors[] = "رقم الهاتف مطلوب";
}

if(!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: profile.php");
    exit();
}

$dbobj= new Database();

$data = [
    "phone_number" => $phone_number,
    "faculty"      => $faculty,
    "department"   => $department
];

if($dbobj->updateById("users", $user_id, $data)) {
    $dbobj->insert("logs", [
        "user_id" => $user_id,
        "action"  => "تحديث البيانات الشخصية"
    ]);

    $_SESSION['success'] = "تم تحديث البيانات بنجاح";
} else {
    $_SESSION['errors'] = ["حدث خطأ، حاول مرة أخرى"];
}

header("Location: profile.php");
exit();
?>