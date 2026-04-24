<?php
require_once __DIR__ . '/../../init.php';

// Only admins and managers should see the financial overview
Auth::checkRole(['admin', 'manager', 'student']); // to-do remove studetn form here :__:

$db = new Database();

// Join payments with applications and users to get the full picture
$sql = "
    SELECT 
        p.*, 
        a.serial_number, 
        a.title,
        u.full_name AS student_name
    FROM payments p
    JOIN applications a ON p.application_id = a.id
    JOIN users u ON a.student_id = u.id
    ORDER BY p.created_at DESC
";

$allPayments = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);

// Calculate Quick Stats for the Dashboard
$totalRevenue = 0;
$completedCount = 0;
$pendingCount = 0;
$failedCount = 0;

foreach ($allPayments as $pay) {
    if ($pay['status'] === 'completed') {
        $totalRevenue += $pay['amount'];
        $completedCount++;
    } elseif ($pay['status'] === 'pending') {
        $pendingCount++;
    } elseif ($pay['status'] === 'failed') {
        $failedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة المدفوعات (Admin Payments)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body { font-family: 'Cairo', sans-serif; background-color: var(--bg-page); padding: 40px; color: var(--text-main); }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { color: var(--primary-base); border-bottom: 4px solid var(--accent-base); padding-bottom: 10px; margin: 0; font-weight: 800; }
        
        /* Stats Dashboard */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .icon-revenue { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .icon-pending { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .icon-failed { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .stat-info h4 { margin: 0; font-size: 0.9rem; color: var(--text-muted); }
        .stat-info .value { margin: 5px 0 0 0; font-size: 1.4rem; font-weight: 800; color: var(--primary-dark); }

        /* Table Styles */
        .table-container { background: var(--bg-surface); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); overflow-x: auto; border: 1px solid rgba(44, 62, 80, 0.05); }
        table { width: 100%; border-collapse: collapse; text-align: right; }
        th, td { padding: 16px 20px; border-bottom: 1px solid var(--border-light); }
        th { background-color: #f8fafc; color: var(--primary-base); font-weight: 700; white-space: nowrap; }
        tr:hover { background-color: #f9fbfc; }
        
        .student-name { font-weight: 700; color: var(--primary-dark); }
        .serial-badge { font-family: monospace, 'Cairo'; background: var(--accent-light); color: var(--accent-dark); padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 700; white-space: nowrap; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
        .status-completed { background-color: var(--status-approved-bg); color: var(--status-approved-text); }
        .status-pending { background-color: var(--status-pending-bg); color: var(--status-pending-text); }
        .status-failed { background-color: var(--status-rejected-bg); color: var(--status-rejected-text); }
        
        .phase-text { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); }
        .amount-text { font-weight: 800; color: var(--primary-base); white-space: nowrap; }
        .date-text { font-size: 0.85rem; color: var(--text-muted); white-space: nowrap; }
        .transaction-id { font-family: monospace; font-size: 0.85rem; color: var(--text-muted); background: #eee; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="page-header">
        <h1 class="page-title"><i class="fa-solid fa-file-invoice-dollar" style="margin-left: 10px;"></i> إدارة المدفوعات</h1>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-revenue"><i class="fa-solid fa-vault"></i></div>
            <div class="stat-info">
                <h4>إجمالي الإيرادات المكتملة</h4>
                <div class="value"><?= number_format($totalRevenue, 2) ?> ج.م</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-pending"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info">
                <h4>عمليات قيد الانتظار</h4>
                <div class="value"><?= $pendingCount ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-failed"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-info">
                <h4>عمليات فاشلة / ملغاة</h4>
                <div class="value"><?= $failedCount ?></div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>الباحث (Student)</th>
                    <th>رقم البحث (Serial)</th>
                    <th>نوع الرسوم</th>
                    <th>المبلغ</th>
                    <th>حالة الدفع</th>
                    <th>المرجع (Paymob ID)</th>
                    <th>تاريخ العملية</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allPayments)): ?>
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">لا توجد أي سجلات دفع في النظام حالياً.</td></tr>
                <?php else: ?>
                    <?php foreach ($allPayments as $row): 
                        $phaseName = ($row['phase'] === 'initial') ? 'رسوم مبدئية' : 'مراجعة عينة';
                        $dateDisplay = $row['paid_at'] ? date('Y/m/d h:i A', strtotime($row['paid_at'])) : date('Y/m/d h:i A', strtotime($row['created_at']));
                    ?>
                        <tr>
                            <td>
                                <div class="student-name"><?= htmlspecialchars($row['student_name']) ?></div>
                            </td>
                            <td>
                                <span class="serial-badge"><?= htmlspecialchars($row['serial_number']) ?></span>
                            </td>
                            <td><span class="phase-text"><?= $phaseName ?></span></td>
                            <td class="amount-text"><?= number_format($row['amount'], 2) ?> ج.م</td>
                            <td>
                                <?php if($row['status'] === 'completed'): ?>
                                    <span class="badge status-completed"><i class="fa-solid fa-check"></i> مكتمل</span>
                                <?php elseif($row['status'] === 'pending'): ?>
                                    <span class="badge status-pending"><i class="fa-solid fa-clock"></i> انتظار</span>
                                <?php elseif($row['status'] === 'failed'): ?>
                                    <span class="badge status-failed"><i class="fa-solid fa-xmark"></i> فشل</span>
                                <?php endif; ?>
                            </td>
                            <td dir="ltr">
                                <?php if($row['gateway_transaction_id']): ?>
                                    <span class="transaction-id"><?= htmlspecialchars($row['gateway_transaction_id']) ?></span>
                                <?php else: ?>
                                    <span style="color: var(--border-dark);">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="date-text" dir="ltr"><?= $dateDisplay ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>