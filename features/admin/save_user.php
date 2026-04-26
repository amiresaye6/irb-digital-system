<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../../init.php";
require_once __DIR__ . "/../../classes/Auth.php"; 

Auth::checkRole(['admin']);

$dbobj = new Database();

$full_name    = trim($_POST['full_name']    ?? '');
$email         = trim($_POST['email']           ?? '');
$password      = $_POST['password']         ?? '';
$confirm_pass  = $_POST['confirm_password'] ?? '';
$national_id   = trim($_POST['national_id']     ?? '');
$phone_number  = trim($_POST['phone_number']    ?? '');
$faculty       = trim($_POST['faculty']         ?? '');
$role          = $_POST['role']             ?? '';

$errors = [];

$allowed_roles = ['admin', 'reviewer', 'manager', 'sample_officer'];
if(!in_array($role, $allowed_roles)) {
    $errors[] = "يرجى اختيار الدور الوظيفي بشكل صحيح.";
}
if(empty($full_name) || strlen($full_name) < 3) {
    $errors[] = "الاسم يجب أن يكون ثلاثياً على الأقل.";
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "البريد الإلكتروني المدخل غير صحيح.";
}
if(strlen($password) < 8) {
    $errors[] = "كلمة المرور ضعيفة، يجب أن لا تقل عن 8 رموز.";
}
if($password !== $confirm_pass) {
    $errors[] = "كلمتا المرور غير متطابقتين.";
}
if(!preg_match("/^[0-9]{14}$/", $national_id)) {
    $errors[] = "الرقم القومي يجب أن يتكون من 14 رقم.";
}

$existing_email = $dbobj->selectWhere("users", "email", $email);
if(!empty($existing_email)) {
    $errors[] = "هذا البريد الإلكتروني مسجل لمستخدم آخر فعلياً.";
}

$existing_national = $dbobj->selectWhere("users", "national_id", $national_id);
if(!empty($existing_national)) {
    $errors[] = "هذا الرقم القومي مسجل لمستخدم آخر فعلياً.";
}

if(!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['old_data'] = $_POST; 
    header("Location: add_user.php");
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$data = [
    "role"          => $role,
    "full_name"     => $full_name,
    "email"         => $email,
    "password_hash" => $hashed_password,
    "national_id"   => $national_id,
    "phone_number"  => $phone_number,
    "faculty"       => $faculty,
    "is_active"     => 1
];

$result = $dbobj->insert("users", $data);
if($result) {
    $dbobj->insert("logs", [
        "user_id" => $_SESSION['user_id'],
        "action"  => "إضافة مستخدم جديد: " . $full_name . " بصلاحية: " . $role,
        "type"    => "registration"
    ]);

    $_SESSION['success'] = "تم إنشاء حساب (" . $full_name . ") بنجاح.";
} else {
    $_SESSION['errors'] = ["فشل حفظ البيانات في قاعدة البيانات."];
}
header("Location: add_user.php");
exit();