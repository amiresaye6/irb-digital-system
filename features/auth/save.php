<?php
session_start(); 
require_once "../../init.php";
require_once "../../classes/EmailService.php";

$full_name= trim($_POST['full_name']    ?? '');
$email= trim($_POST['email']        ?? '');
$password=$_POST['password']     ?? '';
$national_id= trim($_POST['national_id']  ?? '');
$phone_number= trim($_POST['phone_number'] ?? '');
$faculty= trim($_POST['faculty']      ?? '');
$department= trim($_POST['department']   ?? '');

$errors = [];
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];

if(strlen($full_name) < 10) {
    $errors[] = "الاسم بالكامل يجب أن يكون أكثر من 10 أحرف";
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "البريد الإلكتروني غير صالح";
}
if(strlen($password) < 8) {
    $errors[] = "كلمة المرور يجب أن لا تقل عن 8 رموز";
}
if(!preg_match("/^[0-9]{14}$/", $national_id)) {
    $errors[] = "رقم البطاقة يجب أن يتكون من 14 رقم";
}
$dbobj= new Database();
$existing_email = $dbobj->selectWhere("users", "email", $email);
if($existing_email) {
    $errors[] = "البريد الإلكتروني مسجل من قبل";
}

$existing_national = $dbobj->selectWhere("users", "national_id", $national_id);
if($existing_national) {
    $errors[] = "رقم البطاقة مسجل من قبل";
}

$real_dir   = __DIR__ . "/../../uploads/id_cards/";
$upload_dir = "uploads/id_cards/";

$id_front_url = "";
$id_back_url  = "";

if(!is_dir($real_dir)) mkdir($real_dir, 0777, true);

if(isset($_FILES['id_front']) && $_FILES['id_front']['error'] == 0) {
    if(!in_array($_FILES['id_front']['type'], $allowed_types)) {
        $errors[] = "صورة وجه البطاقة يجب أن تكون JPG أو PNG فقط";
    } else {
        $front_name   = str_replace(' ', '_', basename($_FILES['id_front']['name']));
        $file_name    = time() . "_front_" . $front_name;
        $id_front_url = $upload_dir . $file_name;
        move_uploaded_file($_FILES['id_front']['tmp_name'], $real_dir . $file_name);
    }
    
} else {
    $errors[] = "صورة وجه البطاقة مطلوبة";
}

if(isset($_FILES['id_back']) && $_FILES['id_back']['error'] == 0) {
    if(!in_array($_FILES['id_back']['type'], $allowed_types)) {
        $errors[] = "صورة ظهر البطاقة يجب أن تكون JPG أو PNG فقط";
    } else {
        $back_name   = str_replace(' ', '_', basename($_FILES['id_back']['name']));
        $file_name   = time() . "_back_" . $back_name;
        $id_back_url = $upload_dir . $file_name;
        move_uploaded_file($_FILES['id_back']['tmp_name'], $real_dir . $file_name);
    }
} else {
    $errors[] = "صورة ظهر البطاقة مطلوبة";
}

if(!empty($errors)) {
    $_SESSION['errors']= $errors;
    $_SESSION['old_data']= $_POST;
    header("Location: register.php");
    exit();
}

$hashed_password= password_hash($password, PASSWORD_DEFAULT);


$data = [
    "role"          => "student",
    "full_name"     => $full_name,
    "email"         => $email,
    "password_hash" => $hashed_password,
    "national_id"   => $national_id,
    "phone_number"  => $phone_number,
    "faculty"       => $faculty,
    "department"    => $department,
    "id_front_url"  => $id_front_url,
    "id_back_url"   => $id_back_url,
    "is_active"     => 0
];

if($dbobj->insert("users", $data)) {
    $_SESSION['success'] = "تم التسجيل بنجاح! انتظر تفعيل حسابك من الإدارة.";
    EmailService::sendAsync($email, $full_name, "طلب تسجيل حساب جديد", "مرحبا {$full_name}, 
لقد تم تسجيل طلب إنشاء حساب جديد. سيتم مراجعة طلبك من قبل الإدارة وتفعيله قريباً.

تفاصيل الحساب:
الاسم: {$full_name}
البريد الإلكتروني: {$email}
الرقم القومي: {$national_id}
رقم الهاتف: {$phone_number}
الكلية: {$faculty}
القسم: {$department}", "");
    header("Location: login.php");
    exit();
} else {
    $_SESSION['errors']   = ["حدث خطأ في قاعدة البيانات، حاول مرة أخرى"];
    $_SESSION['old_data'] = $_POST;
    header("Location: register.php");
    exit();
}
?>