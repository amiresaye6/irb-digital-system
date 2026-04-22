<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../classes/Reviews.php';
$reviewsObj = new Reviews();

$applications = $reviewsObj->getApplicationsUnderReview();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين المراجعين</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        .page-title {
            color: var(--primary-base);
            margin-bottom: 8px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
        }

        .page-title i {
            color: var(--accent-base);
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-bottom: 25px;
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .alert-success {
            background: var(--status-approved-bg);
            color: var(--status-approved-text);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
            border-right: 4px solid var(--success-base);
            font-size: 0.95rem;
        }

        .alert-danger {
            background: var(--status-rejected-bg);
            color: var(--status-rejected-text);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
            border-right: 4px solid var(--alert-base);
            font-size: 0.95rem;
        }

        .data-card {
            background: var(--bg-surface);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-top: 25px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 0.95rem;
        }

        .data-table th {
            padding: 14px 12px;
            font-weight: 800;
            border-bottom: 2px solid var(--primary-base);
            color: white;
            background: var(--primary-base);
            font-size: 0.9rem;
            text-align: right;
        }

        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .data-table tr:hover {
            background-color: var(--primary-light);
        }

        .badge-serial {
            font-weight: 800;
            color: white;
            background: var(--primary-base);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            display: inline-block;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .app-title {
            color: var(--text-main);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .app-investigator {
            font-size: 0.88rem;
            color: var(--text-muted);
            display: flex;
            align-items: flex-start;
            gap: 6px;
            line-height: 1.4;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge.pending {
            color: var(--status-pending-text);
            background: var(--status-pending-bg);
        }

        .status-badge.assigned {
            color: var(--status-approved-text);
            background: var(--status-approved-bg);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-action {
            background: var(--accent-base);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            transition: all var(--transition-smooth);
            box-shadow: var(--shadow-sm);
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .btn-action:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-dark);
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 6px 0;
            line-height: 1.5;
        }

        .review-count {
            background: white;
            color: var(--status-approved-text);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 0.8rem;
            min-width: 20px;
            text-align: center;
        }

        body {
            background: var(--bg-page);
        }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 40px 24px;
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content > * {
            width: 100%;
            max-width: 1120px;
        }

        .data-card {
            width: 100%;
            max-width: 1120px;
        }

        @media (max-width: 992px) {
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
        <h2 class="page-title">
            <i class="fa-solid fa-list-check"></i>
            الأبحاث الجاهزة للتحكيم
        </h2>
        <p class="page-subtitle">
            من هنا يمكنك الإطلاع على الأبحاث المؤهلة للمراجعة واختيار بحث لتعيين المراجعين المتخصصين له.
        </p>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>تم تعيين المراجع بنجاح!</span>
            </div>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>خطأ: تم تعيين هذا المراجع مسبقاً أو حدثت مشكلة في التعيين.</span>
            </div>
        <?php endif; ?>

        <div class="data-card">
            <div style="overflow-x: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="12%">رقم الملف</th>
                            <th width="45%">بيانات البحث</th>
                            <th width="20%">حالة المراجعين</th>
                            <th width="23%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <p style="font-weight: 700; font-size: 1.05rem;">لا توجد أبحاث قيد المراجعة</p>
                                        <p style="font-size: 0.9rem;">جميع الأبحاث إما قيد الانتظار أو تم الانتهاء من مراجعتها</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app):
                                $assigned = $reviewsObj->getAssignedReviewers($app['id']);
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="app-title">
                                            <?= htmlspecialchars($app['title']) ?>
                                        </div>
                                        <div class="app-investigator">
                                            <i class="fa-solid fa-user-doctor"></i>
                                            <strong>الباحث:</strong>
                                            <?= htmlspecialchars($app['principal_investigator']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if(empty($assigned)): ?>
                                            <span class="status-badge pending">
                                                <i class="fa-solid fa-hourglass-end"></i>
                                                لم يتم التعيين
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge assigned">
                                                <i class="fa-solid fa-check-circle"></i>
                                                <span class="review-count"><?= count($assigned) ?></span>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="assign_form.php?application_id=<?= $app['id'] ?>" class="btn-action">
                                            <i class="fa-solid fa-user-plus"></i>
                                            إسناد
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
