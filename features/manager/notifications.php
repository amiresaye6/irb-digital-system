<?php
require_once '../../init.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: /irb-digital-system/features/auth/login.php');
    exit;
}

require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$manager_id = $_SESSION['user_id'];

if (isset($_POST['mark_all_read'])) {
    $appObj->markAllNotificationsRead($manager_id);
    header("Location: notifications.php"); 
    exit;
}

// Reusing the generic notifications queries
$notifications = $appObj->getStudentNotifications($manager_id);
$unreadCount = $appObj->getUnreadNotificationCount($manager_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إشعارات المدير | نظام إدارة الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 30px 24px; display: flex; flex-direction: column; align-items: center; }
        .content > * { width: 100%; max-width: 850px; }
        
        .page-title { color: var(--primary-base); font-size: 1.5rem; font-weight: 800; display: flex; align-items: center; gap: 12px; margin-bottom: 6px; }
        .page-title i { color: var(--accent-base); }
        .page-subtitle { color: var(--text-muted); font-size: 0.9rem; font-weight: 500; margin-bottom: 18px; }

        .notif-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .notif-count { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; background: var(--primary-light); color: var(--primary-base); font-weight: 700; font-size: 0.88rem; }
        .notif-count .badge { background: var(--accent-base); color: white; padding: 2px 8px; border-radius: 999px; font-size: 0.8rem; font-weight: 800; }
        
        .btn-mark-all { background: var(--primary-base); color: white; border: none; padding: 9px 18px; border-radius: var(--radius-md); font-family: inherit; font-weight: 700; font-size: 0.88rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all var(--transition-smooth); }
        .btn-mark-all:hover { background: var(--accent-base); transform: translateY(-2px); }

        .notif-list { display: flex; flex-direction: column; gap: 12px; }
        
        .notif-card { background: var(--bg-surface); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 18px 20px; display: flex; gap: 14px; transition: all var(--transition-smooth); text-decoration: none; color: inherit; cursor: pointer; }
        .notif-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .notif-card.unread { border-right: 4px solid var(--accent-base); background: linear-gradient(135deg, rgba(26,188,156,0.04) 0%, #fff 100%); }
        .notif-card.read { opacity: 0.75; }
        
        .notif-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; color: white; }
        .notif-icon.type-info { background: linear-gradient(135deg, var(--primary-base), var(--accent-base)); }
        
        .notif-body { flex: 1; min-width: 0; }
        .notif-msg { font-size: 0.92rem; color: var(--text-main); font-weight: 700; line-height: 1.5; margin-bottom: 6px; }
        
        .notif-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .notif-time { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 5px; }
        .notif-app { font-size: 0.8rem; color: var(--primary-base); font-weight: 700; display: flex; align-items: center; gap: 5px; }
        
        .notif-unread-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent-base); flex-shrink: 0; animation: dotPulse 2s infinite; }
        @keyframes dotPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.4; } }

        .empty-state { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; color: var(--border-dark); margin-bottom: 12px; opacity: 0.5; }

        @media(max-width: 992px) { .content { margin-right: 0; padding: 24px 14px; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-bell"></i> إشعارات المدير</h2>
        <p class="page-subtitle">تنبيهات الأبحاث الجاهزة للاعتماد النهائي</p>

        <div class="notif-header">
            <span class="notif-count">
                <i class="fa-solid fa-bell"></i> إجمالي: <?= count($notifications) ?> 
                <?php if ($unreadCount > 0): ?>
                    <span class="badge"><?= $unreadCount ?> جديد</span>
                <?php endif; ?>
            </span>
            
            <?php if ($unreadCount > 0): ?>
                <form method="POST" style="margin:0">
                    <button type="submit" name="mark_all_read" class="btn-mark-all">
                        <i class="fa-solid fa-check-double"></i> تعيين الكل كمقروء
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-bell-slash"></i>
                <p style="font-weight:700; font-size:1.05rem;">لا توجد إشعارات</p>
                <p style="font-size:0.9rem;">ستظهر هنا أي تنبيهات بشأن الأبحاث الجاهزة.</p>
            </div>
        <?php else: ?>
            <div class="notif-list">
                <?php foreach ($notifications as $notif):
                    $isUnread = !$notif['is_read'];
                    $msg = $notif['message'];
                    $iconType = 'type-info'; 
                    $icon = 'fa-stamp'; 
                ?>
                    <a href="notification_details.php?id=<?= $notif['id'] ?>" class="notif-card <?= $isUnread ? 'unread' : 'read' ?>">
                        <div class="notif-icon <?= $iconType ?>"><i class="fa-solid <?= $icon ?>"></i></div>
                        <div class="notif-body">
                            <div class="notif-msg"><?= htmlspecialchars($msg) ?></div>
                            <div class="notif-meta">
                                <span class="notif-time"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($notif['created_at'])) ?></span>
                                <?php if (!empty($notif['serial_number'])): ?>
                                    <span class="notif-app"><i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($notif['serial_number']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($isUnread): ?>
                            <div class="notif-unread-dot"></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
