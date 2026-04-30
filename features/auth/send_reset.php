<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../../init.php";
require_once __DIR__ . "/../../classes/EmailService.php";
$env = require __DIR__ . '/../../includes/env.php';



$email = trim($_POST['email'] ?? '');
$BASE_URL = $env['APP_URL'];

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_error'] = "البريد الإلكتروني غير صالح";
    header("Location: forgot_password.php");
    exit();
}

$dbobj = new Database();
$user  = $dbobj->selectWhere("users", "email", $email);

if(!$user) {
    $_SESSION['reset_success'] = "إذا كان البريد مسجلاً، ستصلك رسالة خلال دقائق";
    header("Location: forgot_password.php");
    exit();
}


$token = bin2hex(random_bytes(32));

// Delete old tokens
$dbobj->getconn()->query("DELETE FROM password_resets WHERE email = '" . $dbobj->getconn()->real_escape_string($email) . "'");

// Insert new token with 5-minute expiration using DB time
$email_safe = $dbobj->getconn()->real_escape_string($email);
$token_safe = $dbobj->getconn()->real_escape_string($token);
$query = "INSERT INTO password_resets (email, token, expires_at) VALUES ('$email_safe', '$token_safe', DATE_ADD(NOW(), INTERVAL 5 MINUTE))";
$dbobj->getconn()->query($query);


$reset_link =  $BASE_URL . "/features/auth/reset_password.php?token=" . $token;


$subject = 'استعادة كلمة المرور - نظام IRB الرقمي';
$messageBody = "لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.\n\n";
$messageBody .= "يرجى الضغط على الزر أدناه لإتمام العملية:\n\n";
$messageBody .= "<a href='{$reset_link}' style='display:inline-block; background:#1abc9c; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>إعادة تعيين كلمة المرور</a>\n\n";
$messageBody .= "إذا لم تطلب هذا التغيير، يمكنك تجاهل هذا البريد.";


 EmailService::sendAsync($email, $user['full_name'], $subject, $messageBody);


    $dbobj->insert("logs", [
        "user_id" => $user['id'],
        "action"  => "طلب استعادة كلمة المرور"
    ]);
    $_SESSION['reset_success'] = "إذا كان البريد مسجلاً، ستصلك رسالة خلال دقائق";


header("Location: forgot_password.php");
exit();
?>