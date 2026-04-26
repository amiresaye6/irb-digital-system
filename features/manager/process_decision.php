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

            $cert_num = "CERT-" . date('Y') . "-" . str_pad($application_id, 5, '0', STR_PAD_LEFT);
            $manager_id = $_SESSION['user_id'] ?? 11;
            $conn->query("INSERT IGNORE INTO certificates (application_id, student_id, manager_id, certificate_number, issued_to_name) 
                        VALUES ($application_id, $student_id, $manager_id, '$cert_num', '$student_name')");

            $msg = "مبروك! تم اعتماد بحثك ذو الرقم التسلسلي ($serial_num)نهائياً. وتم إصدار شهادة رقم ($cert_num) باسمك. يمكنك الآن تحميل شهادتك .";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, application_id, message, channel) VALUES (?, ?, ?, 'system')");
            $stmt->bind_param("iis", $student_id, $application_id, $msg);
            $stmt->execute();

        } elseif ($action == 'return') {
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