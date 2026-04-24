<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /irb-digital-system/login.php"); exit;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_notifications.php"); exit;
}
require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$student_id = $_SESSION['user_id'];
$notif_id = intval($_GET['id']);
$notif = $appObj->getNotificationById($notif_id, $student_id);
if (!$notif) { die("الإشعار غير موجود."); }

$appObj->markNotificationRead($notif_id, $student_id);

$feedback = [];
$needsMod = false;
if (!empty($notif['app_id'])) {
    $feedback = $appObj->getReviewerFeedback($notif['app_id']);
    $needsMod = $appObj->hasNeedsModification($notif['app_id']);
}

$msg = $notif['message'];
if (strpos($msg, 'تعديلات') !== false) { $iconType = 'type-warning'; $icon = 'fa-triangle-exclamation'; $typeLabel = 'طلب تعديل'; }
elseif (strpos($msg, 'رفض') !== false) { $iconType = 'type-danger'; $icon = 'fa-circle-xmark'; $typeLabel = 'إشعار رفض'; }
elseif (strpos($msg, 'اعتماد') !== false || strpos($msg, 'تهانينا') !== false) { $iconType = 'type-success'; $icon = 'fa-circle-check'; $typeLabel = 'إشعار اعتماد'; }
else { $iconType = 'type-info'; $icon = 'fa-info-circle'; $typeLabel = 'إشعار عام'; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الإشعار</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body{background:var(--bg-page)}
        .content{margin-right:260px;min-height:100vh;padding:30px 24px;display:flex;flex-direction:column;align-items:center}
        .content>*{width:100%;max-width:800px}
        .page-title{color:var(--primary-base);font-size:1.5rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:6px}
        .page-title i{color:var(--accent-base)}
        .page-subtitle{color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:22px}
        .card{background:var(--bg-surface);padding:24px;border-radius:var(--radius-lg);box-shadow:var(--shadow-md);border:1px solid var(--border-light);margin-bottom:20px}
        .card-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid var(--border-light)}
        .card-header h3{color:var(--primary-base);font-size:1.1rem;font-weight:800;margin:0}
        .card-header i{color:var(--accent-base);font-size:1.1rem}

        .notif-detail-header{display:flex;align-items:center;gap:16px;margin-bottom:20px}
        .notif-detail-icon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:white;flex-shrink:0}
        .notif-detail-icon.type-warning{background:linear-gradient(135deg,#f39c12,#f1c40f)}
        .notif-detail-icon.type-success{background:linear-gradient(135deg,#27ae60,#2ecc71)}
        .notif-detail-icon.type-danger{background:linear-gradient(135deg,#e74c3c,#ec7063)}
        .notif-detail-icon.type-info{background:linear-gradient(135deg,var(--primary-base),var(--accent-base))}
        .notif-detail-info h4{color:var(--primary-base);font-size:1.1rem;font-weight:800;margin:0 0 4px}
        .notif-detail-info .notif-type{font-size:0.85rem;color:var(--text-muted);font-weight:600}
        .notif-msg-box{font-size:1rem;color:var(--text-main);line-height:1.7;font-weight:600;padding:18px;background:var(--bg-page);border-radius:var(--radius-md);border-right:4px solid var(--accent-base)}
        .notif-meta-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-top:14px;font-size:0.85rem;color:var(--text-muted);font-weight:600}
        .notif-meta-bar i{color:var(--accent-base)}

        .app-link{display:flex;align-items:center;gap:14px;padding:16px;background:linear-gradient(135deg,rgba(44,62,80,0.03) 0%,#fff 100%);border:1px solid var(--border-light);border-radius:var(--radius-md);text-decoration:none;color:inherit;transition:all var(--transition-smooth)}
        .app-link:hover{border-color:var(--accent-base);transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .app-link .badge-serial{font-weight:800;color:white;background:var(--primary-base);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.85rem;flex-shrink:0}
        .app-link-info{flex:1}
        .app-link-title{font-weight:700;color:var(--text-main);font-size:0.95rem}
        .app-link-arrow{color:var(--accent-base);font-size:1.2rem}

        .feedback-item{padding:14px 18px;background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%);border:1px solid var(--border-light);border-radius:var(--radius-md);margin-bottom:12px;border-right:4px solid var(--accent-base)}
        .feedback-item.dec-needs_modification{border-right-color:#f39c12}
        .feedback-item.dec-rejected{border-right-color:#e74c3c}
        .feedback-item.dec-approved{border-right-color:#27ae60}
        .feedback-reviewer{font-weight:800;color:var(--primary-base);font-size:0.9rem;display:flex;align-items:center;gap:6px;margin-bottom:8px}
        .feedback-text{font-size:0.9rem;color:var(--text-main);line-height:1.6;padding:10px 14px;background:var(--bg-page);border-radius:8px}
        .feedback-date{font-size:0.78rem;color:var(--text-muted);margin-top:6px;display:flex;align-items:center;gap:5px}

        .action-area{display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap}
        .btn-primary{background:var(--accent-base);color:white;padding:12px 24px;border-radius:var(--radius-md);font-family:inherit;font-weight:800;font-size:0.95rem;transition:all var(--transition-smooth);box-shadow:var(--shadow-md);display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:none}
        .btn-primary:hover{background:var(--accent-dark);transform:translateY(-2px)}
        .btn-secondary{background:var(--primary-light);color:var(--primary-base);border:2px solid var(--primary-base);padding:12px 24px;border-radius:var(--radius-md);font-family:inherit;font-weight:800;font-size:0.95rem;transition:all var(--transition-smooth);display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-secondary:hover{background:var(--primary-base);color:white;transform:translateY(-2px)}

        @media(max-width:992px){.content{margin-right:0;padding:24px 14px}}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-bell"></i> تفاصيل الإشعار</h2>
        <p class="page-subtitle">عرض تفاصيل الإشعار والإجراءات المتاحة</p>

        <div class="card">
            <div class="notif-detail-header">
                <div class="notif-detail-icon <?= $iconType ?>"><i class="fa-solid <?= $icon ?>"></i></div>
                <div class="notif-detail-info">
                    <h4><?= htmlspecialchars($typeLabel) ?></h4>
                    <span class="notif-type"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($notif['created_at'])) ?></span>
                </div>
            </div>
            <div class="notif-msg-box"><?= nl2br(htmlspecialchars($notif['message'])) ?></div>
            <?php if (!empty($notif['serial_number'])): ?>
                <div class="notif-meta-bar"><i class="fa-solid fa-file-lines"></i> رقم الملف: <strong><?= htmlspecialchars($notif['serial_number']) ?></strong></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($notif['app_id'])): ?>
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-link"></i><h3>البحث المرتبط</h3></div>
                <a href="student_research_details.php?id=<?= $notif['app_id'] ?>" class="app-link">
                    <span class="badge-serial"><?= htmlspecialchars($notif['serial_number']) ?></span>
                    <div class="app-link-info"><div class="app-link-title"><?= htmlspecialchars($notif['app_title']) ?></div></div>
                    <i class="fa-solid fa-chevron-left app-link-arrow"></i>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($feedback)): ?>
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-comments"></i><h3>ملاحظات المراجعين</h3></div>
                <?php foreach ($feedback as $fb):
                    if (empty($fb['comment'])) continue;
                ?>
                    <div class="feedback-item dec-<?= $fb['decision'] ?>">
                        <span class="feedback-reviewer"><i class="fa-solid fa-user-secret"></i> <?= htmlspecialchars($fb['reviewer_label']) ?></span>
                        <div class="feedback-text"><?= nl2br(htmlspecialchars($fb['comment'])) ?></div>
                        <?php if (!empty($fb['comment_date'])): ?>
                            <div class="feedback-date"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($fb['comment_date'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="action-area">
                <a href="student_notifications.php" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> العودة للإشعارات</a>
                <?php if ($needsMod && !empty($notif['app_id']) && $notif['current_stage'] !== 'approved' && $notif['current_stage'] !== 'rejected'): ?>
                    <a href="update_application.php?id=<?= $notif['app_id'] ?>" class="btn-primary"><i class="fa-solid fa-pen-to-square"></i> تحديث المستندات</a>
                <?php elseif (!empty($notif['app_id'])): ?>
                    <a href="student_research_details.php?id=<?= $notif['app_id'] ?>" class="btn-primary"><i class="fa-solid fa-eye"></i> عرض تفاصيل البحث</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
