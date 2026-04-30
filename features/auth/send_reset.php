<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../../init.php";
require_once __DIR__ . "/../../classes/EmailService.php";
$email = trim($_POST['email'] ?? '');

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


$token      = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', time() + 3600); 


$dbobj->getconn()->query("DELETE FROM password_resets WHERE email = '" . $dbobj->getconn()->real_escape_string($email) . "'");


$dbobj->insert("password_resets", [
    "email"      => $email,
    "token"      => $token,
    "expires_at" => $expires_at
]);


$reset_link =  BASE_URL . "/features/auth/reset_password.php?token=" . $token;


$subject = 'استعادة كلمة المرور - نظام IRB الرقمي';
$messageBody = "لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بحسابك.\n\n";
$messageBody .= "يرجى الضغط على الزر أدناه لإتمام العملية:\n\n";
$messageBody .= "<a href='{$reset_link}' style='display:inline-block; background:#1abc9c; color:white; padding:12px 25px; text-decoration:none; border-radius:8px; font-weight:bold;'>إعادة تعيين كلمة المرور</a>\n\n";
$messageBody .= "إذا لم تطلب هذا التغيير، يمكنك تجاهل هذا البريد.";


$sent = EmailService::send($email, $user['full_name'], $subject, $messageBody);

if ($sent) {
   
    $dbobj->insert("logs", [
        "user_id" => $user['id'],
        "action"  => "طلب استعادة كلمة المرور"
    ]);
    $_SESSION['reset_success'] = "إذا كان البريد مسجلاً، ستصلك رسالة خلال دقائق";
} else {
    $_SESSION['reset_error'] = "حدث خطأ في إرسال البريد، حاول مرة أخرى لاحقاً";
}

header("Location: forgot_password.php");
exit();
?>