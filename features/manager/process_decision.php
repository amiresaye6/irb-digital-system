<?php
ob_start(); 
session_start();
require_once '../../classes/Database.php';
require_once __DIR__ . "/../../classes/Auth.php";

Auth::checkRole(['manager']);

if (isset($_GET['id']) && isset($_GET['action'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];
    $db = new Database();
    $conn = $db->getconn();

    $query = "SELECT r.application_id, a.student_id, a.serial_number, u.full_name 
                FROM reviews r 
                JOIN applications a ON r.application_id = a.id 
                JOIN users u ON a.student_id = u.id 
                WHERE r.id = $review_id";
    
    $res = $conn->query($query);
    $data = $res->fetch_assoc();

    if (!$data) {
        die(json_encode(['status' => 'error', 'message' => 'بيانات غير صالحة']));
    }

    $application_id = $data['application_id'];
    $student_id = $data['student_id'];
    $serial_num = $data['serial_number'];
    $student_name = $data['full_name'];

    $conn->begin_transaction();

    try {
        if ($action == 'approve') {
            $conn->query("UPDATE reviews SET decision = 'approved' WHERE id = $review_id");
            $conn->query("UPDATE applications SET current_stage = 'approved' WHERE id = $application_id");

            $currentYear = date('Y');

            $sql_count = "SELECT COUNT(*) as total FROM certificates WHERE YEAR(issued_at) = ?";
            $stmt_count = $conn->prepare($sql_count);
            $stmt_count->bind_param("s", $currentYear);
            $stmt_count->execute();
            $res_count = $stmt_count->get_result();
            $row_count = $res_count->fetch_assoc();

            $nextNumber = $row_count['total'] + 1;

            $cert_num = "CERT-" . $currentYear . "-" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
            $manager_id = $_SESSION['user_id']; 

            $stmt_cert = $conn->prepare("INSERT IGNORE INTO certificates (application_id, student_id, manager_id, certificate_number, issued_to_name, issued_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt_cert->bind_param("iiiss", $application_id, $student_id, $manager_id, $cert_num, $student_name);
            $stmt_cert->execute();

            $msg = "مبروك! تم اعتماد بحثك ذو الرقم التسلسلي ($serial_num) نهائياً. وتم إصدار شهادة رقم ($cert_num) باسمك.";
            $stmt_notif = $conn->prepare("INSERT INTO notifications (user_id, application_id, message, channel) VALUES (?, ?, ?, 'system')");
            $stmt_notif->bind_param("iis", $student_id, $application_id, $msg);
            $stmt_notif->execute();
            }
            elseif ($action == 'return') {
            $conn->query("UPDATE reviews SET decision = 'needs_modification' WHERE id = $review_id");
            $conn->query("UPDATE applications SET current_stage = 'under_review' WHERE id = $application_id");

            $msg = "تمت مراجعة طلبك ($serial_num)، وتمت إعادته للمراجع لاستيفاء بعض الملاحظات.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, application_id, message, channel) VALUES (?, ?, ?, 'system')");
            $stmt->bind_param("iis", $student_id, $application_id, $msg);
            $stmt->execute();
        }

        $conn->commit();

        ob_clean(); 
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'cert_url' => ($action == 'approve') ? 'view_certificate.php?app_id=' . $application_id : null
        ]);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}