<?php
require_once __DIR__ . "/../../classes/Auth.php";
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../classes/Applications.php';
Auth::checkRole('student'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function user() {
        return [
            'id'        => $_SESSION['user_id']   ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role']       ?? null,
            'email'     => $_SESSION['email']      ?? null,
        ];
    }
$userData = user();
$appObj = new Applications();
$student_id = $_SESSION['user_id'];
$applications = $appObj->getStudentApplications($student_id);// returns many applications with(id, serial_number, title, principal_investigator, current_stage, created_at, updated_at)

$stageLabels = [
    'pending_admin' => ['مراجعة أولية', 'fa-hourglass-half', 'pending'],
    'awaiting_initial_payment' => ['بانتظار الدفع', 'fa-credit-card', 'pending'],
    'awaiting_sample_calc' => ['حساب العينة', 'fa-calculator', 'pending'],
    'awaiting_sample_payment' => ['دفع العينة', 'fa-credit-card', 'pending'],
    'under_review' => ['قيد المراجعة', 'fa-microscope', 'pending'],
    'approved_by_reviewer' => ['مقبول من المراجع', 'fa-user-check', 'approved'],
    'approved' => ['معتمد نهائياً', 'fa-circle-check', 'approved'],
    'rejected' => ['مرفوض', 'fa-circle-xmark', 'rejected'],
];


?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم</title>
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <style>
        body {
            background-color: var(--bg-page);
            font-family: 'Cairo', sans-serif;
            margin: 0;
            display: flex;
        }

        .main-content {
            flex: 1;
            margin-right: 280px;
            padding: 40px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 30px;
            text-align: right;
        }

        .page-title-container {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
        }

        .page-title-container h1 {
            color: var(--primary-base);
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
        }

        .page-title-container i {
            color: var(--accent-base); 
            font-size: 1.8rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-top: 8px;
            font-size: 1.1rem;
        }
        /* --------------------------------------------- */

        .user-profile-section {
            background: var(--bg-surface);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border-right: 6px solid var(--accent-base);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            background: var(--primary-light);
            color: var(--primary-base);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 30px;
            border: 2px solid var(--accent-light);
        }

        .user-details h2 {
            margin: 0;
            color: var(--primary-base);
            font-size: 1.4rem;
        }

        .user-details span {
            color: var(--text-muted);
            font-size: 0.9rem;
            display: block;
            margin-top: 5px;
        }

        .role-badge {
            background: var(--accent-light);
            color: var(--accent-dark);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .table-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: var(--primary-base);
            color: white;
        }

        th {
            padding: 18px 20px;
            text-align: right;
            font-weight: 600;
        }

        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f1f1;
            color: var(--text-main);
        }

        tbody tr:hover {
            background-color: var(--primary-light) !important;
            transition: 0.2s;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .status-pending { background: var(--status-pending-bg); color: var(--status-pending-text); }
        .status-approved { background: var(--status-approved-bg); color: var(--status-approved-text); }
        .status-rejected { background: var(--status-rejected-bg); color: var(--status-rejected-text); }

        .btn-add {
            background: var(--accent-base);
            color: white;
            padding: 12px 25px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition-smooth);
        }

        .btn-add:hover { background: var(--accent-dark); transform: translateY(-2px); }

        #space{
            height:100px;
        }

        @media (max-width: 1000px){
            .main-content {
            margin-right: 0px;
            }
        }

    </style>
</head>
<body>

    <div class="main-content">
        
        <div class="page-header">
            <div class="page-title-container">
                <h1>لوحة التحكم</h1>
                <i class="fa-solid fa-gauge-high"></i>
            </div>
            <p class="page-subtitle">نظرة عامة على نشاطك ومتابعة سريعة لأحدث طلبات البحث</p>
        </div>

        <div class="user-profile-section">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <div class="user-details">
                    <h2><?= htmlspecialchars($userData['full_name']) ?></h2>
                    <h2><?= htmlspecialchars($userData['email']) ?></h2>
                    <h2><?= htmlspecialchars($userData['role']) ?></h2>
                </div>
            </div>
            <div style="text-align: left;">
                                <span class="role-badge">بوابة الطلاب</span>

                <div style="margin-top: 10px;padding: 40px;">
                    <a href="apply.php" class="btn-add">
                        <i class="fa fa-plus"></i> تقديم بحث جديد
                    </a>
                </div>
            </div>
        </div>

        <h3 style="color: var(--primary-base); margin-bottom: 20px;">
            <i class="fa-solid fa-list-check" style="margin-left: 10px;"></i> طلباتي البحثية الأخيرة
        </h3>

        <div class="table-card">
            <?php if (!empty($applications)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>عنوان البحث</th>
                            <th>الباحث الرئيسي</th>
                            <th>المرحلة الحالية</th>
                            <th>تاريخ التقديم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): 
                            $stageKey = $app['current_stage'];
                            $labelInfo = $stageLabels[$stageKey] ?? ['غير معروف', 'fa-question', 'pending'];
                            $statusClass = 'status-' . $labelInfo[2];
                        ?>
                            <tr>
                                <td><b style="color: var(--primary-base);"><?= $app['serial_number'] ?></b></td>
                                <td style="max-width: 350px; font-weight: 600;"><?= htmlspecialchars($app['title']) ?></td>
                                <td><?= htmlspecialchars($app['principal_investigator']) ?></td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <i class="fa <?= $labelInfo[1] ?>"></i>
                                        <?= $labelInfo[0] ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d', strtotime($app['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 15px;"></i>
                    <p>لا توجد أي طلبات مقدمة حالياً.</p>
                </div>
            <?php endif; ?>
        </div>
        <div id="space"></div>
    </div>

</body>
</html>