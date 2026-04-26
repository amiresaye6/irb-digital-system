<?php
require_once '../../init.php';

require_once __DIR__ . "/../../classes/Auth.php";

Auth::checkRole(['manager']);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: notifications.php');
    exit;
}

require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$notif_id = intval($_GET['id']);
$manager_id = $_SESSION['user_id'];

$notification = $appObj->getNotificationById($notif_id, $manager_id);

if (!$notification) {
    header('Location: notifications.php');
    exit;
}

// Mark as read
if (!$notification['is_read']) {
    $appObj->markNotificationRead($notif_id, $manager_id);
}

// Fetch application basics
$db = new Database();
$app = $db->selectById('applications', $notification['application_id']);
if (!$app) {
    die("البحث المرتبط غير موجود.");
}

$actionUrl = "decision_details.php?application_id=" . $app['id'];

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
        .content { margin-right: 260px; min-height: 100vh; padding: 30px 24px; display: flex; flex-direction: column; align-items: center; }
        .content > * { width: 100%; max-width: 800px; }
        
        .page-title { color: var(--primary-base); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; margin-bottom: 25px; }
        .page-title i { color: var(--accent-base); }

        .notif-detail-card { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
        
        /* Decorative Top Border */
        .notif-detail-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, var(--primary-base), var(--accent-base)); }

        .notif-header { display: flex; align-items: flex-start; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--border-light); }
        .notif-icon-large { width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, rgba(26,188,156,0.1) 0%, rgba(44,62,80,0.05) 100%); color: var(--accent-base); font-size: 1.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 2px solid rgba(26,188,156,0.2); }
        
        .notif-meta-info { flex: 1; }
        .notif-date { color: var(--text-muted); font-size: 0.85rem; font-weight: 600; margin-bottom: 5px; display: flex; align-items: center; gap: 6px; }
        .notif-main-title { color: var(--primary-base); font-weight: 800; font-size: 1.2rem; margin: 0; line-height: 1.4; }

        .notif-message-box { background: var(--bg-page); padding: 20px; border-radius: var(--radius-md); border-right: 4px solid var(--primary-base); font-size: 1.05rem; font-weight: 600; color: var(--text-main); line-height: 1.7; margin-bottom: 30px; }

        .app-summary { background: white; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 20px; margin-bottom: 30px; }
        .app-summary-title { font-weight: 800; color: var(--primary-dark); font-size: 1rem; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .summary-item { display: flex; flex-direction: column; gap: 4px; }
        .summary-label { font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }
        .summary-value { font-size: 0.95rem; font-weight: 700; color: var(--text-main); }
        .badge-serial { display: inline-block; background: var(--primary-light); color: var(--primary-base); padding: 4px 10px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.9rem; letter-spacing: 1px; }

        .actions-area { display: flex; gap: 15px; align-items: center; margin-top: 20px; }
        .btn-action { background: var(--accent-base); color: white; padding: 12px 24px; border-radius: var(--radius-md); text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-action:hover { background: var(--primary-base); transform: translateY(-2px); color: white; }
        .btn-back { background: var(--bg-page); color: var(--text-muted); padding: 12px 24px; border-radius: var(--radius-md); text-decoration: none; font-weight: 700; font-size: 0.95rem; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-light); }
        .btn-back:hover { background: var(--border-light); color: var(--primary-dark); }

        @media(max-width: 768px) { .content { margin-right: 0; padding: 20px 15px; } .summary-grid { grid-template-columns: 1fr; } .actions-area { flex-direction: column; width: 100%; } .actions-area .btn-action, .actions-area .btn-back { width: 100%; justify-content: center; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-bell"></i> تفاصيل الإشعار</h2>

        <div class="notif-detail-card">
            <div class="notif-header">
                <div class="notif-icon-large"><i class="fa-solid fa-stamp"></i></div>
                <div class="notif-meta-info">
                    <div class="notif-date"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($notification['created_at'])) ?></div>
                    <h3 class="notif-main-title">طلب اعتماد نهائي</h3>
                </div>
            </div>

            <div class="notif-message-box">
                <?= nl2br(htmlspecialchars($notification['message'])) ?>
            </div>

            <div class="app-summary">
                <div class="app-summary-title"><i class="fa-solid fa-file-contract" style="color:var(--accent-base);"></i> بيانات البحث المختصرة</div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">رقم الطلب</span>
                        <span class="summary-value badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                    </div>
                    <div class="summary-item" style="grid-column: 1 / -1;">
                        <span class="summary-label">عنوان البحث</span>
                        <span class="summary-value"><?= htmlspecialchars($app['title']) ?></span>
                    </div>
                </div>
            </div>

            <div class="actions-area">
                <a href="<?= $actionUrl ?>" class="btn-action">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> الذهاب إلى صفحة الاعتماد
                </a>
                <a href="notifications.php" class="btn-back">
                    <i class="fa-solid fa-arrow-right"></i> العودة للإشعارات
                </a>
            </div>
        </div>
    </div>
</body>
</html>
