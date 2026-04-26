<?php
session_start();
require_once "../../init.php";

$email= trim($_POST["email"]    ?? '');
$password =$_POST["password"] ?? '';

$dbobj =new Database();
$user =$dbobj->selectWhere("users", "email", $email);

if(!$user || !password_verify($password, $user['password_hash'])) {
    header("Location: login.php?error=invalid");
    exit();
}

if(!$user['is_active']) {
    header("Location: login.php?error=inactive");
    exit();
}
Auth::login($user);
$dbobj->insert("logs", [
    "user_id" => $user['id'],
    "action"  => "تسجيل دخول من IP: " . $_SERVER['REMOTE_ADDR'],
    "type"    => "login"
]);

switch($user['role']) {
    case 'student':
        header("Location: ../../features/student/dashboard.php");
        break;
    case 'admin':
        header("Location: ../../features/admin/dashboard.php");
        break;
    case 'sample_officer':
        header("Location: ../../features/sample_officer/dashboard.php");
        break;
    case 'reviewer':
        header("Location:../../features/reviewer/dashboard.php");
        break;
    case 'manager':
        header("Location: ../../features/manager/dashboard.php");
        break;
    default:
        header("Location: ../auth/login.php?error=invalid");
}
exit();
?>