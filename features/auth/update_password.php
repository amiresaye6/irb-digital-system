<?php
session_start();
require_once "../../init.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbobj    = new Database();
    $conn     = $dbobj->getconn();
    
    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    
    if ($password !== $confirm) {
        $_SESSION['reset_error'] = "كلمات المرور غير متطابقة";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }

    
    $safe_token = $conn->real_escape_string($token);
    $check_sql  = "SELECT email FROM password_resets WHERE token = '$safe_token' AND expires_at > NOW() LIMIT 1";
    $result     = $conn->query($check_sql);
    $reset_data = $result->fetch_assoc();

    if (!$reset_data) {
        $_SESSION['reset_error'] = "عذراً، الرابط انتهت صلاحيته.";
        header("Location: forgot_password.php");
        exit();
    }

    $email = $reset_data['email'];

   
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  

    $update_user = $conn->query("UPDATE users SET password_hash = '$hashed_password' WHERE email = '$email'");

    if ($update_user) {
      
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");

       
        $user_row = $dbobj->selectWhere("users", "email", $email);
        $dbobj->insert("logs", [
            "user_id" => $user_row ? $user_row['id'] : null,
            "action" => "تغيير كلمة المرور بنجاح (استعادة) للإيميل: $email",
            "type" => "profile"
        ]);

        $_SESSION['login_success'] = "تم تغيير كلمة المرور بنجاح، يمكنك تسجيل الدخول الآن.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['reset_error'] = "حدث خطأ أثناء التحديث، حاول مرة أخرى.";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }
}