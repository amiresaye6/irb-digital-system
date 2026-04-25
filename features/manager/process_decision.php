<?php
session_start();
require_once '../../classes/Database.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $review_id = $_GET['id'];
    $action = $_GET['action'];
    $db = new Database();
    $conn = $db->getconn();

    $get_app = $conn->query("SELECT application_id FROM reviews WHERE id = $review_id");
    $app_data = $get_app->fetch_assoc();
    $application_id = $app_data['application_id'];

    if ($action == 'approve') {
        $sql = "UPDATE reviews SET decision = 'final_approved' WHERE id = $review_id";
        $sql_app = "UPDATE applications SET current_stage = 'final_approved' WHERE id = $application_id";
    } 
    elseif ($action == 'return') {
        $sql = "UPDATE reviews SET decision = 'returned_to_reviewer' WHERE id = $review_id";
        $sql_app = "UPDATE applications SET current_stage = 'returned_to_reviewer' WHERE id = $application_id";
    }

    if ($conn->query($sql) && $conn->query($sql_app)) {
        header('Content-Type: application/json'); 
        echo json_encode(['status' => 'success']);
        exit(); 
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
        exit();
    }
}