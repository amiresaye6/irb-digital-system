<?php
session_start();
require_once "../../init.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbobj    = new Database();
    $conn     = $dbobj->getconn();
    
    $token    = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 1. التحقق من تطابق كلمة المرور
    if ($password !== $confirm) {
        $_SESSION['reset_error'] = "كلمات المرور غير متطابقة";
        header("Location: reset_password.php?token=" . urlencode($token));
        exit();
    }

    // 2. التحقق مرة تانية من صحة الـ token وصلاحيته
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

    // 3. تحديث كلمة المرور في جدول المستخدمين (تشفير الباسورد مهم جداً)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  
// $hashed_password = $conn->real_escape_string($hashed_password);
    
    // استخدمي طريقة التحديث المتبعة في كلاس الـ Database بتاعك
    // سأفترض أنها query عادية هنا للتبسيط:
    $update_user = $conn->query("UPDATE users SET password_hash = '$hashed_password' WHERE email = '$email'");

    if ($update_user) {
        // 4. مسح الـ token عشان ميتخدمش تاني (أمان زيادة)
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");

        // 5. تسجيل العملية في الـ Log
        $dbobj->insert("logs", [
            "action" => "تغيير كلمة المرور بنجاح",
            "details" => "عن طريق رابط استعادة المرور للإيميل: $email"
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