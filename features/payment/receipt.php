<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['student']);

$db = new Database();
$student_id = Auth::user()['id'];

$paymentData = null;
// SCENARIO 1: Coming from the Payment History page (Internal)
if (isset($_GET['payment_id'])) {
    $payment_id = (int) $_GET['payment_id'];
    $sql = "
        SELECT p.*, a.serial_number 
        FROM payments p
        JOIN applications a ON p.application_id = a.id
        WHERE p.id = $payment_id AND a.student_id = $student_id
    ";
    $paymentData = $db->getconn()->query($sql)->fetch_assoc();
}
// SCENARIO 2: Coming directly from Paymob Redirection (External)
elseif (isset($_GET['merchant_order_id'])) {
    $merchantRef = $db->getconn()->real_escape_string($_GET['merchant_order_id']);
    $sql = "
        SELECT p.*, a.serial_number 
        FROM payments p
        JOIN applications a ON p.application_id = a.id
        WHERE p.transaction_reference = '$merchantRef' AND a.student_id = $student_id
    ";
    $paymentData = $db->getconn()->query($sql)->fetch_assoc();
}

// Security Check: Does this payment exist and belong to the student?
if (!$paymentData) {
    die("<div style='text-align:center; margin-top:50px; font-family: Cairo, sans-serif; direction: rtl;'>
            <h2 style='color: #e74c3c;'>عذراً، الإيصال غير موجود</h2>
            <p>لا تملك صلاحية الوصول إلى هذا الإيصال أو أن المعاملة غير صحيحة.</p>
            <a href='../student/payment_history.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#2c3e50; color:white; text-decoration:none; border-radius:8px;'>العودة للسجل</a>
         </div>");
}

/* * RACE CONDITION FIX:
 * Paymob might redirect the user here before the Webhook updates the database status to 'completed'.
 * So, we consider it a success if the DB says completed OR if Paymob's URL says success=true.
 */
$isSuccessFromDB = ($paymentData['status'] === 'completed');
$isSuccessFromPaymob = (isset($_GET['success']) && $_GET['success'] === 'true');
$isSuccess = $isSuccessFromDB || $isSuccessFromPaymob;

// Extract variables for the UI
$amountEGP = $paymentData['amount'];
$serialNumber = $paymentData['serial_number'];
$merchantRef = $paymentData['transaction_reference'];
// If DB hasn't saved the Paymob Order ID yet, grab it from the URL
$orderId = $paymentData['gateway_transaction_id'] ?: ($_GET['order'] ?? '—');
// If DB hasn't saved the paid_at timestamp yet, use the current time
$paymentDate = $paymentData['paid_at'] ? date('Y-m-d h:i A', strtotime($paymentData['paid_at'])) : date('Y-m-d h:i A');

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>إيصال الدفع (Payment Receipt)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: var(--text-main);
        }

        .receipt-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 30px;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .badge.success {
            background-color: var(--status-approved-bg);
            color: var(--status-approved-text);
        }

        .badge.fail {
            background-color: var(--status-rejected-bg);
            color: var(--status-rejected-text);
        }

        .amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-base);
            margin: 5px 0;
        }

        .details {
            text-align: right;
            margin-top: 25px;
            border-top: 2px dashed var(--border-light);
            padding-top: 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .row span:first-child {
            color: var(--text-muted);
            font-weight: 600;
        }

        .row span:last-child {
            font-weight: 800;
            color: var(--text-main);
            font-family: monospace, 'Cairo';
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--primary-base);
            color: white;
            text-decoration: none;
            padding: 12px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--primary-dark);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-base);
            color: var(--primary-base);
        }

        .btn-outline:hover {
            background: var(--primary-base);
            color: white;
        }

        @media print {
            body {
                background: white;
            }

            .receipt-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .btn-group {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <?php if ($isSuccess): ?>
            <div class="badge success"><i class="fa-solid fa-circle-check"></i> عملية دفع ناجحة</div>
            <h2 style="margin: 0; color: var(--primary-dark);">إيصال إلكتروني</h2>
            <div class="amount"><?= number_format($amountEGP, 2) ?> ج.م</div>

            <div class="details">
                <div class="row"><span>رقم التسلسل (Serial):</span><span><?= htmlspecialchars($serialNumber) ?></span></div>
                <div class="row"><span>المرجع المالي:</span><span><?= htmlspecialchars($merchantRef) ?></span></div>
                <div class="row"><span>رقم أمر الدفع (Paymob):</span><span><?= htmlspecialchars($orderId) ?></span></div>
                <div class="row"><span>التاريخ والوقت:</span><span dir="ltr"><?= htmlspecialchars($paymentDate) ?></span>
                </div>
            </div>

            <div class="btn-group">
                <button onclick="window.print()" class="btn btn-outline"><i class="fa-solid fa-print"></i> طباعة /
                    PDF</button>
                <a href="../student/payment_history.php" class="btn"><i class="fa-solid fa-home"></i> العودة للسجل</a>
            </div>

        <?php else: ?>
            <div class="badge fail"><i class="fa-solid fa-circle-xmark"></i> فشل عملية الدفع</div>
            <h2 style="margin: 0; color: var(--primary-dark);">لم تكتمل المعاملة</h2>
            <p style="color: var(--text-muted); margin-top: 15px;">عذراً، لم نتمكن من معالجة عملية الدفع الخاصة بك.</p>

            <div class="btn-group">
                <a href="../student/payment_history.php" class="btn"><i class="fa-solid fa-rotate-left"></i> العودة
                    للسجل</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>