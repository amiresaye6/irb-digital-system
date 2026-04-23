<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['student']);

$success = $_GET['success'] ?? 'false';
$orderId = $_GET['order'] ?? '';
$merchantRef = $_GET['merchant_order_id'] ?? '';
$amountCents = $_GET['amount_cents'] ?? 0;

$isSuccess = ($success === 'true');
$amountEGP = $amountCents / 100;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Transaction Result</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .receipt-container {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-weight: 700;
            margin-bottom: 20px;
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
            margin: 10px 0;
        }

        .details {
            text-align: left;
            margin-top: 20px;
            border-top: 1px dashed var(--border-light);
            padding-top: 20px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--text-muted);
        }

        .row span:last-child {
            font-weight: 600;
            color: var(--text-main);
        }

        .btn {
            display: block;
            background: var(--primary-base);
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: var(--radius-md);
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <?php if ($isSuccess): ?>
            <div class="badge success">✓ Payment Successful</div>
            <h2>Digital Receipt</h2>
            <div class="amount">EGP <?= number_format($amountEGP, 2) ?></div>

            <div class="details">
                <div class="row"><span>Reference</span><span><?= htmlspecialchars($merchantRef) ?></span></div>
                <div class="row"><span>Paymob Order</span><span><?= htmlspecialchars($orderId) ?></span></div>
                <div class="row"><span>Date</span><span><?= date('Y-m-d H:i') ?></span></div>
            </div>

            <a href="../student/payments.php" class="btn">Return to Dashboard</a>

        <?php else: ?>
            <div class="badge fail">✗ Payment Failed</div>
            <h2>Transaction Incomplete</h2>
            <p>Your payment could not be processed at this time. Please check your payment details or try another method.
            </p>
            <a href="../student/payments_history.php" class="btn">Try Again</a>
        <?php endif; ?>
    </div>
</body>

</html>