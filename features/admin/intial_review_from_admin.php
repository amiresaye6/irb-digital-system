<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
 header("Location: /irb-digital-system/login.php"); exit;
}
 require_once __DIR__ . '/../../includes/sidebar.php';
require_once "../../init.php";
require_once __DIR__ . '/../../classes/Applications.php';

$case = $_GET['case'];
$app_id = intval($_GET['id']);
$app_student_id = intval($_GET['student_id']);
$serial_number = $_GET['serial_number'];
$appObj = new Applications();
$app = $appObj->getApplicationFullDetails($app_id, $app_student_id);
if (!$app) { die("البحث غير موجود أو لا تملك صلاحية عرضه."); }

$database = new Database();
if($case == 'accept' && $app['current_stage']=='pending_admin'){
    //change stage
    $sql ="UPDATE applications SET current_stage='awaiting_initial_payment' WHERE id=$app_id";
    $stmt = $database->conn->prepare($sql);
    $stmt->execute();
    $stmt->close();

    //add log
    $logs = [
        "application_id" => $app_id,
        "user_id" => $_SESSION['user_id'] ,
        "action" => "تمت الموافقة المبدئية من الادمن"
    ];
    $database->insert("logs",$logs);

    //push notification
    $message = "تم قبول مبدئي لبحثك $serial_number بإنتظار سداد رسومك الأولية";
    $channel = "system";
    $sql = "INSERT INTO notifications 
    (user_id, application_id, message, channel)
    VALUES (?, ?, ?, ?)";
    $stmt = $database->conn->prepare($sql);
    $stmt->bind_param("iiss", $app_student_id, $app_id, $message, $channel);
    $stmt->execute();
    $stmt->close();

}elseif($case == 'reject' && $app['current_stage']=='pending_admin'){
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

    //push notification
    $reasons = $_SESSION['refusal_reason'];
    $message = "تم رفض بحث $serial_number للاسباب : $reasons" ; 
    $channel = "system";
    $sql = "INSERT INTO notifications 
    (user_id, application_id, message, channel)
    VALUES (?, ?, ?, ?)";
    $stmt = $database->conn->prepare($sql);
    $stmt->bind_param("iiss", $app_student_id, $app_id, $message, $channel);
    $stmt->execute();
    $stmt->close();
    $_SESSION['refusal_reason'] = "";
}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معالجة الطلب</title>
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-page);
            font-family: 'Cairo', sans-serif;
            margin: 0;
            padding: 40px;
            margin-right: 260px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .response-card {
            background: var(--bg-surface);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 500px;
            width: 100%;
            text-align: center;
            border-top: 5px solid <?php echo ($case === 'accept') ? 'var(--accent-base)' : '#e74c3c'; ?>;
        }

        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: <?php echo ($case === 'accept') ? 'var(--accent-base)' : '#e74c3c'; ?>;
        }

        .status-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-base);
            margin-bottom: 10px;
        }

        .status-msg {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn-return {
            display: inline-block;
            background-color: var(--primary-base);
            color: white;
            padding: 12px 30px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-smooth);
        }

        .btn-return:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @media (max-width: 1000px) {
            body {
                margin-right: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="response-card">
        <div class="status-icon">
            <?php if($case === 'accept'): ?>
                <i class="fa-solid fa-circle-check"></i>
            <?php else: ?>
                <i class="fa-solid fa-circle-xmark"></i>
            <?php endif; ?>
        </div>

        <h1 class="status-title">
            <?php echo ($case === 'accept') ? 'تمت الموافقة' : 'تم الرفض'; ?>
        </h1>

        <p class="status-msg">
            تمت معالجة البحث ذو الرقم التسلسلي <strong><?php echo htmlspecialchars($serial_number); ?></strong> بنجاح وتحديث حالته في النظام وإرسال الإشعارات اللازمة للباحث.
        </p>

        <a href="/irb-digital-system/features/admin/pending_applications.php" class="btn-return">
            <i class="fa-solid fa-arrow-right-long"></i> العودة للطلبات قيد المراجعة
        </a>
    </div>

</body>
</html>