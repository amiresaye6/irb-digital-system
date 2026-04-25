<?php
session_start();
require_once "../../init.php";
Auth::checkRole(['admin']);

$user_id = $_GET['id'] ?? null;

if($user_id) {
    $dbobj = new Database();
    $dbobj->deleteById("users", $user_id);

    $dbobj->insert("logs", [
        "user_id" => $_SESSION['user_id'],
        "action"  => "رفض وحذف حساب رقم " . $user_id
    ]);
}

header("Location: dashboard.php");
exit();
?>