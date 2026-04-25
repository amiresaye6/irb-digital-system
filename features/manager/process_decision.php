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
        $sql = "UPDATE reviews SET decision = 'approved' WHERE id = $review_id";
        $sql_app = "UPDATE applications SET current_stage = 'approved' WHERE id = $application_id";

        $user_info = $conn->query("SELECT u.full_name FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = $application_id");
        $user_data = $user_info->fetch_assoc();
        $student_name = $user_data['full_name'];

        $cert_num = "CERT-" . date('Y') . "-" . str_pad($application_id, 5, '0', STR_PAD_LEFT);
        $manager_id = $_SESSION['user_id'] ?? 11;
        $sql_cert = "INSERT INTO certificates (application_id, manager_id, certificate_number, issued_to_name) 
                    VALUES ($application_id, $manager_id, '$cert_num', '$student_name')";
    } elseif ($action == 'return') {
        $sql = "UPDATE reviews SET decision = 'needs_modification' WHERE id = $review_id";
        $sql_app = "UPDATE applications SET current_stage = 'under_review' WHERE id = $application_id";
        $sql_cert = null;
    }

    $conn->begin_transaction();

    try {
        $conn->query($sql);
        $conn->query($sql_app);
        if ($action == 'approve' && isset($sql_cert)) {
            $conn->query($sql_cert);
        }

        $conn->commit();

        header('Content-Type: application/json');
        $response = ['status' => 'success'];
        if ($action == 'approve') {
            $response['cert_url'] = 'view_certificate.php?app_id=' . $application_id;
        }

        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'حدث خطأ: ' . $e->getMessage()
        ]);
        exit();
    }
}
