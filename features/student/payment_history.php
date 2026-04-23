<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['student']);

$db = new Database();
$student_id = Auth::user()['id'];

// Join payments with applications to get the titles and serials
$sql = "
    SELECT p.*, a.title, a.serial_number 
    FROM payments p
    JOIN applications a ON p.application_id = a.id
    WHERE a.student_id = $student_id
    ORDER BY p.created_at DESC
";

$history = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>سجل المدفوعات (Payment History)</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/global.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            padding: 40px;
            color: var(--text-main);
        }

        .page-title {
            color: var(--primary-base);
            border-bottom: 3px solid var(--accent-base);
            padding-bottom: 10px;
            display: inline-block;
            margin-bottom: 30px;
        }

        .table-container {
            background: var(--bg-surface);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }

        th,
        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-light);
        }

        th {
            background-color: var(--primary-light);
            color: var(--primary-base);
            font-weight: 700;
        }

        tr:hover {
            background-color: #f9fbfc;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-block;
        }

        .status-completed {
            background-color: var(--status-approved-bg);
            color: var(--status-approved-text);
        }

        .status-pending {
            background-color: var(--status-pending-bg);
            color: var(--status-pending-text);
        }

        .status-failed {
            background-color: var(--status-rejected-bg);
            color: var(--status-rejected-text);
        }

        .phase-badge {
            background: var(--bg-page);
            border: 1px solid var(--border-dark);
            color: var(--text-muted);
            font-size: 0.8rem;
            padding: 3px 8px;
            border-radius: 4px;
        }

        .amount {
            font-weight: 800;
            color: var(--primary-base);
        }

        .date {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
    </style>
</head>

<body>
    <h1 class="page-title">سجل المدفوعات</h1>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>رقم البحث</th>
                    <th>نوع الرسوم</th>
                    <th>المبلغ</th>
                    <th>رقم العملية (المرجع)</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px;">لم تقم بأي عمليات دفع حتى الآن.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($history as $row):
                        $phaseName = ($row['phase'] === 'initial') ? 'تقديم مبدئي' : 'حجم العينة';
                        $dateDisplay = $row['paid_at'] ? date('Y/m/d h:i A', strtotime($row['paid_at'])) : date('Y/m/d h:i A', strtotime($row['created_at']));
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['serial_number']) ?></strong><br>
                                <small
                                    style="color: var(--text-muted);"><?= mb_strimwidth(htmlspecialchars($row['title']), 0, 40, "...") ?></small>
                            </td>
                            <td><span class="phase-badge"><?= $phaseName ?></span></td>
                            <td class="amount"><?= number_format($row['amount'], 2) ?> ج.م</td>
                            <td style="font-family: monospace; font-size: 0.9rem;">
                                <?= $row['gateway_transaction_id'] ? htmlspecialchars($row['gateway_transaction_id']) : '—' ?>
                            </td>
                            <td class="date" dir="ltr"><?= $dateDisplay ?></td>
                            <td>
                                <span class="badge status-<?= $row['status'] ?>">
                                    <?php
                                    if ($row['status'] === 'completed')
                                        echo 'مكتمل';
                                    elseif ($row['status'] === 'pending')
                                        echo 'قيد الانتظار';
                                    elseif ($row['status'] === 'failed')
                                        echo 'فشلت';
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>