<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['student']);

$db = new Database();
$currentUser = Auth::user();
$student_id = $currentUser['id'];

// Get applications waiting for either payment phase
$sql = "SELECT id, serial_number, title, current_stage, sample_size 
        FROM applications 
        WHERE student_id = $student_id 
        AND current_stage IN ('awaiting_initial_payment', 'awaiting_sample_payment')";

$pendingApplications = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);

// Helper function to calculate sample fee matching your Payment class
function getSampleFee($size)
{
    if (!$size)
        return 0;
    if ($size <= 100)
        return 200;
    if ($size <= 500)
        return 500;
    return 1000;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>المدفوعات المستحقة (Pending Payments)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            padding: 40px;
            color: var(--text-main);
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-title {
            color: var(--primary-base);
            border-bottom: 4px solid var(--accent-base);
            padding-bottom: 10px;
            display: inline-block;
            margin: 0;
            font-weight: 800;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(44, 62, 80, 0.05);
            transition: transform var(--transition-smooth), box-shadow var(--transition-smooth);
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            /* Aligns items to top */
            gap: 15px;
            /* Adds breathing room between title and badge */
            margin-bottom: 20px;
        }

        .card h3 {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.5;
            color: var(--primary-dark);
            font-weight: 700;
            flex: 1;
            /* Allows title to take remaining space */
        }

        /* The fix for the squished serial number */
        .serial {
            font-family: monospace, 'Cairo';
            /* Monospace looks great for IDs */
            font-size: 0.85rem;
            font-weight: 800;
            color: var(--accent-dark);
            background: var(--accent-light);
            padding: 6px 14px;
            border-radius: 30px;
            /* Pill shape */
            white-space: nowrap;
            /* CRITICAL: Prevents it from breaking into multiple lines */
            flex-shrink: 0;
            /* CRITICAL: Prevents the title from squishing it */
            letter-spacing: 0.5px;
        }

        .payment-details {
            background: #f8fafc;
            /* Softer, cooler gray */
            padding: 20px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
            margin-bottom: 25px;
            flex-grow: 1;
            /* Pushes button to bottom if titles vary in length */
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-row span:last-child {
            color: var(--text-main);
            font-weight: 700;
        }

        .amount-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed var(--border-dark);
        }

        .amount-row .amount {
            color: var(--accent-dark);
            font-size: 1.5rem;
            font-weight: 800;
        }

        .btn-pay {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            width: 100%;
            background: var(--primary-base);
            color: white;
            padding: 14px;
            border: none;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all var(--transition-smooth);
        }

        .btn-pay:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <div class="page-header">
        <h1 class="page-title">المدفوعات المستحقة</h1>
    </div>

    <?php if (empty($pendingApplications)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-check-circle"
                style="font-size: 4rem; color: var(--success-base); margin-bottom: 20px;"></i>
            <h3 style="color: var(--text-main); margin-bottom: 10px;">لا توجد مدفوعات مستحقة حالياً</h3>
            <p>جميع أبحاثك في المراحل التالية أو لم تتطلب دفعاً بعد.</p>
        </div>
    <?php else: ?>
        <div class="cards-grid">
            <?php foreach ($pendingApplications as $app):
                $isInitial = $app['current_stage'] === 'awaiting_initial_payment';
                $paymentType = $isInitial ? 'رسوم التقديم المبدئية' : 'رسوم مراجعة حجم العينة';
                $amount = $isInitial ? 500.00 : getSampleFee($app['sample_size']);
                ?>
                <div class="card">
                    <div class="card-header">
                        <h3><?= htmlspecialchars($app['title']) ?></h3>
                        <span class="serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                    </div>

                    <div class="payment-details">
                        <div class="detail-row">
                            <span>نوع الرسوم:</span>
                            <span><?= $paymentType ?></span>
                        </div>

                        <?php if (!$isInitial && $app['sample_size']): ?>
                            <div class="detail-row">
                                <span>حجم العينة المُسجل:</span>
                                <span><?= $app['sample_size'] ?> مشارك</span>
                            </div>
                        <?php endif; ?>

                        <div class="detail-row amount-row">
                            <span>المبلغ المطلوب:</span>
                            <span class="amount"><?= number_format($amount, 2) ?> ج.م</span>
                        </div>
                    </div>

                    <a href="../payment/checkout.php?app_id=<?= $app['id'] ?>" class="btn-pay">
                        <i class="fa-regular fa-credit-card"></i>
                        دفع الآن عبر Paymob
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>

</html>