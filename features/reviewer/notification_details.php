<?php
require_once '../../init.php';

// Ensure user is reviewer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'reviewer') {
    header('Location: /irb-digital-system/features/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$reviewsObj = new Reviews();
$reviewer_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: notifications.php");
    exit;
}

$notif_id = intval($_GET['id']);
$notif = $appObj->getNotificationById($notif_id, $reviewer_id);

if (!$notif) {
    header("Location: notifications.php");
    exit;
}

// Mark as read if unread
if (!$notif['is_read']) {
    $appObj->markNotificationRead($notif_id, $reviewer_id);
    $notif['is_read'] = 1;
}

// Fetch application details to know if blinded
$application_id = $notif['app_id'];
$appDetails = null;
if ($application_id) {
    $appDetails = $reviewsObj->getApplicationDetails($application_id);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الإشعار | نظام إدارة الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 40px 24px; display: flex; flex-direction: column; align-items: center; }
        .content > * { width: 100%; max-width: 800px; }

        .page-title { color: var(--primary-base); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; margin-bottom: 25px; }
        .page-title i { color: var(--accent-base); }

        .notif-card { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-sm); margin-bottom: 25px; }
        .notif-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid var(--primary-light); flex-wrap: wrap; gap: 15px; }
        
        .sender-info { display: flex; align-items: center; gap: 12px; }
        .sender-icon { width: 45px; height: 45px; border-radius: 12px; background: rgba(26,188,156,0.1); color: var(--primary-base); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .sender-name { font-weight: 800; color: var(--primary-dark); font-size: 1.05rem; }
        .sender-system { font-size: 0.85rem; color: var(--text-muted); font-weight: 600; }
        
        .notif-time { background: var(--bg-page); padding: 6px 12px; border-radius: 999px; font-size: 0.85rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 6px; }

        .notif-body { color: var(--text-main); font-size: 1.05rem; line-height: 1.7; font-weight: 600; margin-bottom: 30px; }

        .app-context { background: rgba(26,188,156,0.03); border: 1px solid rgba(26,188,156,0.15); border-radius: var(--radius-md); padding: 20px; margin-top: 20px; }
        .app-context-title { font-weight: 800; color: var(--primary-base); margin-bottom: 15px; font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        
        .app-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .app-detail-item { display: flex; flex-direction: column; gap: 4px; }
        .app-detail-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; }
        .app-detail-val { font-size: 0.95rem; color: var(--text-main); font-weight: 700; }
        .app-detail-val.redacted { color: #e74c3c; font-style: italic; }

        .actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-light); }
        .btn-primary { background: var(--primary-base); color: white; padding: 10px 20px; border-radius: var(--radius-md); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .btn-secondary { background: var(--bg-page); color: var(--text-main); border: 1px solid var(--border-dark); padding: 10px 20px; border-radius: var(--radius-md); font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
        .btn-secondary:hover { background: #e2e8f0; }

        @media(max-width: 992px) { .content { margin-right: 0; } .app-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2 class="page-title"><i class="fa-regular fa-bell"></i> تفاصيل الإشعار</h2>

        <div class="notif-card">
            <div class="notif-header">
                <div class="sender-info">
                    <div class="sender-icon"><i class="fa-solid fa-desktop"></i></div>
                    <div>
                        <div class="sender-name">نظام إدارة الموافقات البحثية</div>
                        <div class="sender-system">إشعار تلقائي</div>
                    </div>
                </div>
                <div class="notif-time">
                    <i class="fa-regular fa-clock"></i>
                    <?= htmlspecialchars(irb_format_arabic_date($notif['created_at'])) ?>
                </div>
            </div>

            <div class="notif-body">
                <?= nl2br(htmlspecialchars($notif['message'])) ?>
            </div>

            <?php if ($appDetails): ?>
                <div class="app-context">
                    <div class="app-context-title"><i class="fa-solid fa-file-lines"></i> معلومات البحث المرتبط</div>
                    <div class="app-grid">
                        <div class="app-detail-item">
                            <span class="app-detail-label">رقم الملف</span>
                            <span class="app-detail-val"><?= htmlspecialchars($appDetails['serial_number']) ?></span>
                        </div>
                        <div class="app-detail-item">
                            <span class="app-detail-label">عنوان البحث</span>
                            <span class="app-detail-val"><?= htmlspecialchars($appDetails['title']) ?></span>
                        </div>
                        <div class="app-detail-item">
                            <span class="app-detail-label">الباحث الرئيسي</span>
                            <?php if ($appDetails['is_blinded'] == 1): ?>
                                <span class="app-detail-val redacted"><i class="fa-solid fa-user-secret"></i> معلومات محجوبة</span>
                            <?php else: ?>
                                <span class="app-detail-val"><?= htmlspecialchars($appDetails['principal_investigator']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="app-detail-item">
                            <span class="app-detail-label">تاريخ التقديم</span>
                            <span class="app-detail-val"><?= htmlspecialchars(irb_format_arabic_date($appDetails['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="actions">
                <a href="notifications.php" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> العودة للإشعارات</a>
                <?php if ($application_id): ?>
                    <a href="review_form.php?application_id=<?= $application_id ?>" class="btn-primary">الذهاب لنموذج المراجعة <i class="fa-solid fa-microscope"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
