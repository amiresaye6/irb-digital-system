<?php
session_start();
require_once "../../init.php";
if(isset($_SESSION['user_id'])) {
    $dbobj = new Database();
    $dbobj->insert("logs", [
        "user_id" => $_SESSION['user_id'],
        "action"  => "تسجيل خروج",
        "type"    => "login"
    ]);
}
Auth::logout();
header("Location: login.php");
exit();
?>
