<?php
session_start();
require_once "../../init.php";
Auth::checkRole('admin');

$user_id = $_POST['user_id'] ?? null;

if($user_id) {
    $dbobj = new Database();

    $user = $dbobj->selectById("users", $user_id);

    if(!empty($user['id_front_url'])) {
        $front_path = __DIR__ . "/../../" . $user['id_front_url'];
        if(file_exists($front_path)) unlink($front_path);
    }

    if(!empty($user['id_back_url'])) {
        $back_path = __DIR__ . "/../../" . $user['id_back_url'];
        if(file_exists($back_path)) unlink($back_path);
    }

  
    $dbobj->insert("logs", [
        "user_id" => $_SESSION['user_id'],
        "action"  => "رفض وحذف حساب المستخدم رقم " . $user_id . " - " . $user['full_name']
    ]);

    $dbobj->deleteById("users", $user_id);
}

header("Location: dashboard.php");
exit();
?>