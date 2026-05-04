<?php
session_start();
require_once "../../init.php";
Auth::checkRole(['manager']);
$user_id = $_GET['id'] ?? null;

if($user_id) {
    $dbobj = new Database();

    $dbobj->updateById("users", $user_id, ["is_active" => 1]);

    $existing_app = $dbobj->selectWhere("applications", "student_id", $user_id);
/*
    if(!$existing_app) {
        $year   = date("Y");
        $serial = "IRB-" . $year . "-" . str_pad($user_id, 3, "0", STR_PAD_LEFT);

        $dbobj->insert("applications", [
            "student_id"    => $user_id,
            "serial_number" => $serial,
            "current_stage" => "awaiting_initial_payment"
        ]);

        
    }
*/
    $dbobj->insert("logs", [
            "user_id" => $_SESSION['user_id'],
           // "action"  => "تفعيل حساب رقم " . $user_id . " وإنشاء رقم تسلسلي: " . $serial,
            "action"  => "تفعيل حساب رقم " . $user_id,
            "type"    => "registration"
        ]);
}

header("Location: dashboard.php"); 
exit();
?>