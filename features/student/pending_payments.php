<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /irb-digital-system/features/auth/login.php");
    exit;
}

require_once __DIR__ . '/../../init.php';

$db = new Database();
$student_id = $_SESSION['user_id'];

// Use a LEFT JOIN to grab the sample size and exact fee IF it exists
$sql = "
    SELECT 
        a.id, 
        a.serial_number, 
        a.title, 
        a.current_stage, 
        s.calculated_size,
        s.sample_amount
    FROM applications a
    LEFT JOIN sample_sizes s ON a.id = s.application_id
    WHERE a.student_id = $student_id 
    AND a.current_stage IN ('awaiting_initial_payment', 'awaiting_sample_payment')
";

$pendingApplications = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدفوعات المستحقة | IRB System</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body {
            background: var(--bg-page);
        }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 20px 24px;
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content>* {
            width: 100%;
            max-width: 1120px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            color: var(--primary-base);
            margin-bottom: 8px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
            margin-top: 0;
        }

        .page-title i {
            color: var(--accent-base);
        }

        .page-subtitle {
            color: var(--text-muted);
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .btn-history {
            background: var(--bg-surface);
            color: var(--primary-base);
            border: 2px solid var(--border-light);
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all var(--transition-smooth);
            font-size: 0.9rem;
            box-shadow: var(--shadow-sm);
        }

        .btn-history:hover {
            background: var(--primary-light);
            border-color: var(--primary-base);
            transform: translateY(-2px);
        }

        .invoice-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 24px;
            margin-top: 10px;
        }

        .invoice-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
            transition: all var(--transition-smooth);
            position: relative;
            overflow: hidden;
        }

        .invoice-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-base);
            opacity: 0;
            transition: opacity var(--transition-smooth);
        }

        .invoice-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-base);
        }

        .invoice-card:hover::before {
            opacity: 1;
        }

        .card-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 15px;
        }

        .card-title-text {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary-base);
            margin: 0;
            line-height: 1.5;
            flex: 1;
        }

        .badge-serial {
            font-family: monospace, 'Cairo';
            background: var(--primary-light);
            color: var(--primary-base);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 800;
            white-space: nowrap;
            border: 1px solid rgba(44, 62, 80, 0.1);
        }

        .data-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .data-row .label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-row .value {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .amount-box {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px dashed var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .amount-box .label {
            font-size: 0.95rem;
            font-weight: 800;
            color: var(--primary-base);
        }

        .amount-display {
            font-size: 1.6rem;
            color: var(--accent-dark);
            font-weight: 800;
        }

        .btn-pay {
            background: var(--primary-base);
            color: white;
            border: none;
            padding: 14px;
            border-radius: var(--radius-md);
            font-size: 1rem;
            font-weight: 800;
            transition: all var(--transition-smooth);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 25px;
            width: 100%;
            box-shadow: var(--shadow-sm);
        }

        .btn-pay:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        .empty-state-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            margin-top: 20px;
        }

        .empty-state-card i {
            font-size: 4rem;
            color: var(--success-base);
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .empty-state-card h3 {
            color: var(--primary-base);
            font-weight: 800;
            margin-bottom: 10px;
        }

        @media(max-width:992px) {
            .content {
                margin-right: 0;
                padding: 24px 14px;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <div class="page-header">
            <div>
                <h2 class="page-title"><i class="fa-solid fa-file-invoice"></i> المدفوعات المستحقة</h2>
                <p class="page-subtitle">إدارة الفواتير ورسوم تقديم الأبحاث المطلوبة لاستكمال المراجعة</p>
            </div>
            <a href="payment_history.php" class="btn-history">
                <i class="fa-solid fa-clock-rotate-left"></i> سجل المدفوعات السابقة
            </a>
        </div>

        <?php if (empty($pendingApplications)): ?>
            <div class="empty-state-card">
                <i class="fa-solid fa-circle-check"></i>
                <h3>لا توجد فواتير مستحقة الدفع</h3>
                <p style="font-size: 1.05rem; font-weight: 600;">جميع أبحاثك في المراحل التالية أو لم تتطلب دفعاً بعد.</p>
            </div>
        <?php else: ?>
            <div class="invoice-grid">
                <?php foreach ($pendingApplications as $app):
                    $isInitial = $app['current_stage'] === 'awaiting_initial_payment';

                    // Setup specific UI text based on the phase
                    if ($isInitial) {
                        $paymentType = 'رسوم التقديم المبدئية';
                        $icon = 'fa-file-signature';
                        $amount = 500.00;
                    } else {
                        $paymentType = 'رسوم مراجعة حجم العينة';
                        $icon = 'fa-users-viewfinder';
                        $amount = $app['sample_amount'] ?? 0;
                    }
                    ?>
                    <div class="invoice-card">
                        <div class="card-header-flex">
                            <h3 class="card-title-text"><?= htmlspecialchars($app['title']) ?></h3>
                            <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                        </div>

                        <div style="flex-grow: 1;">
                            <div class="data-row">
                                <span class="label"><i class="fa-solid <?= $icon ?>"></i> نوع الرسوم</span>
                                <span class="value"><?= $paymentType ?></span>
                            </div>

                            <?php if (!$isInitial && $app['calculated_size']): ?>
                                <div class="data-row">
                                    <span class="label"><i class="fa-solid fa-chart-pie"></i> حجم العينة المُسجل</span>
                                    <span class="value"><?= $app['calculated_size'] ?> مشارك</span>
                                </div>
                            <?php endif; ?>

                            <div class="amount-box">
                                <span class="label">المبلغ المطلوب:</span>
                                <span class="amount-display"><?= number_format($amount, 2) ?> ج.م</span>
                            </div>
                        </div>

                        <a href="../payment/checkout.php?app_id=<?= $app['id'] ?>" class="btn-pay">
                            <i class="fa-regular fa-credit-card"></i> دفع عبر Paymob
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>