<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 header("Location: /irb-digital-system/login.php"); exit;
}
require_once "../../init.php";
require_once __DIR__ . '/../../classes/Applications.php';

$case = $_GET['case'];
$app_id = intval($_GET['id']);
$app_student_id = intval($_GET['student_id']);
echo "$case";
echo "$app_id";
echo "$app_student_id";
$database = new Database();
if($case == 'accept'){
    $sql ="UPDATE applications SET current_stage='awaiting_initial_payment' WHERE id=$app_id";
    $stmt = $database->conn->prepare($sql);
    $stmt->execute();
    $stmt->close();

    $logs = [
        "application_id" => $app_id,
        "user_id" => $_SESSION['user_id'] ,
        "action" => "تمت الموافقة المبدئية من الادمن"
    ];
    $database->insert("logs",$logs);

}elseif($case == 'reject'){
    $sql ="UPDATE applications SET current_stage='rejected' WHERE id=$app_id";
    $stmt = $database->conn->prepare($sql);
    $stmt->execute();
    $stmt->close();

    $logs = [
        "application_id" => $app_id,
        "user_id" => $_SESSION['user_id'],
        "action" => "تم رفض البحث من الادمن"
    ];
    $database->insert("logs",$logs);
}elseif($case == 'modify'){

}

?>