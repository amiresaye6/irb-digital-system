<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('manager'); 

if (!isset($_GET['application_id']) || empty($_GET['application_id'])) {
    header("Location: assign_reviewers.php");
    exit;
}

require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
$reviewsObj = new Reviews();

$application_id = intval($_GET['application_id']);
$app = $reviewsObj->getApplicationDetails($application_id);

if (!$app) {
    die("البحث غير موجود.");
}

$coInvestigators = [];
if (!empty($app['co_investigators'])) {
    $decodedInvestigators = json_decode($app['co_investigators'], true);
    if (is_array($decodedInvestigators)) {
        $coInvestigators = $decodedInvestigators;
    }
}

// Logic updates for dynamic workflow
$reviewers = $reviewsObj->getAvailableReviewers();
$activeAssignment = $reviewsObj->getActiveAssignment($application_id);
$history = $reviewsObj->getAssignmentHistory($application_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إسناد مراجع</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <style>
        .page-title {
            color: var(--primary-base);
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .page-title i {
            color: var(--accent-base);
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .data-card {
            background: var(--bg-surface);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-bottom: 20px;
            max-width: 900px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .info-group {
            margin: 0;
            background: linear-gradient(180deg, rgba(44, 62, 80, 0.03) 0%, rgba(255, 255, 255, 1) 100%);
            border: 1px solid rgba(189, 195, 199, 0.55);
            border-radius: var(--radius-md);
            padding: 14px 16px;
        }

        .info-label {
            font-weight: 800;
            color: var(--primary-base);
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            color: var(--text-main);
            font-weight: 700;
            padding: 0;
            border-bottom: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .wide-group {
            grid-column: 1 / -1;
        }

        .details-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .details-list li {
            background: #fff;
            border: 1px solid rgba(189, 195, 199, 0.65);
            color: var(--text-main);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.88rem;
            font-weight: 700;
        }

        .details-empty {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .meta-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: var(--bg-page);
            color: var(--primary-base);
            border: 1px solid rgba(189, 195, 199, 0.75);
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-serial {
            font-weight: 800;
            color: white;
            background: var(--primary-base);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            display: inline-block;
            font-size: 0.9rem;
        }

        /* --- Updated Tracking/History Styles --- */
        .assigned-section {
            margin-top: 20px;
            border-top: 2px solid var(--border-light);
            padding-top: 18px;
        }

        .history-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .history-item {
            background: #fff;
            border: 1px solid var(--border-light);
            padding: 14px;
            border-radius: var(--radius-md);
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: relative;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .history-user {
            font-weight: 700;
            color: var(--primary-base);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 999px;
        }

        .status-pill.awaiting { background: #fff3cd; color: #856404; }
        .status-pill.accepted { background: #d4edda; color: #155724; }
        .status-pill.refused { background: #f8d7da; color: #721c24; }
        .status-pill.timeout { background: #e2e3e5; color: #383d41; }

        .refusal-reason {
            background: rgba(231, 76, 60, 0.05);
            border-right: 3px solid var(--alert-base);
            padding: 10px;
            font-size: 0.85rem;
            color: var(--text-main);
            border-radius: 4px;
        }

        /* --- Form Styles --- */
        .form-group { margin-bottom: 18px; }
        .form-label {
            font-weight: 800;
            color: var(--primary-base);
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            justify-content: flex-end;
        }

        .btn-submit {
            background: var(--accent-base);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-family: inherit;
            font-weight: 800;
            font-size: 0.95rem;
            transition: all var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover { background: var(--accent-dark); transform: translateY(-2px); }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; transform: none; }

        .btn-back {
            background: var(--primary-light);
            color: var(--primary-base);
            border: 2px solid var(--primary-base);
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .warning-box {
            background: #fff8f8;
            border: 1px solid #fee2e2;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: #b91c1c;
        }

        body { background: var(--bg-page); }
        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 40px 24px;
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .content > * { width: 100%; max-width: 980px; }

        @media (max-width: 992px) { .content { margin-right: 0; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-user-plus"></i>
            إسناد ومتابعة مراجع البحث
        </h2>
        <p class="page-subtitle">
            إدارة عملية التحكيم وتتبع حالة قبول المراجعين للبحث المختار.
        </p>

        <!-- Application Summary Card -->
        <div class="data-card">
            <div class="summary-grid">
                <div class="info-group">
                    <span class="info-label">رقم الملف</span>
                    <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                </div>

                <div class="info-group">
                    <span class="info-label">تاريخ التقديم</span>
                    <div class="meta-row">
                        <span class="meta-chip"><i class="fa-regular fa-calendar"></i> <?= htmlspecialchars(irb_format_arabic_date($app['created_at'])) ?></span>
                        <span class="meta-chip"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_time($app['created_at'])) ?></span>
                    </div>
                </div>

                <div class="info-group wide-group">
                    <span class="info-label">عنوان البحث</span>
                    <div class="info-value"><i class="fa-solid fa-book" style="color: var(--accent-base);"></i> <?= htmlspecialchars($app['title']) ?></div>
                </div>

                <div class="info-group">
                    <span class="info-label">الباحث الرئيسي</span>
                    <div class="info-value"><i class="fa-solid fa-user-doctor" style="color: var(--primary-base);"></i> <?= htmlspecialchars($app['principal_investigator']) ?></div>
                </div>

                <div class="info-group">
                    <span class="info-label">الكلية / القسم</span>
                    <div class="info-value"><i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars($app['faculty'] ?? 'غير متوفر') ?> | <?= htmlspecialchars($app['department'] ?? 'غير متوفر') ?></div>
                </div>
            </div>

            <!-- Tracking History Section -->
            <?php if(!empty($history)): ?>
                <div class="assigned-section">
                    <span class="info-label" style="margin-bottom:15px;">
                        <i class="fa-solid fa-clock-rotate-left" style="margin-left: 5px;"></i>
                        سجل محاولات الإسناد والمتابعة
                    </span>
                    <ul class="history-list">
                        <?php foreach($history as $h): ?>
                            <li class="history-item">
                                <div class="history-header">
                                    <div class="history-user">
                                        <i class="fa-solid fa-user-tie"></i>
                                        أ.د. <?= htmlspecialchars($h['full_name']) ?>
                                    </div>
                                    <?php 
                                        $statusClass = $h['assignment_status'];
                                        $statusText = [
                                            'awaiting_acceptance' => 'بانتظار الرد',
                                            'accepted' => 'تم القبول',
                                            'refused' => 'اعتذر',
                                            'timed_out' => 'انتهت المهلة'
                                        ][$statusClass] ?? $statusClass;
                                    ?>
                                    <span class="status-pill <?= $statusClass ?>"><?= $statusText ?></span>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    بتاريخ: <?= irb_format_arabic_date($h['assigned_at']) ?> الساعة <?= irb_format_arabic_time($h['assigned_at']) ?>
                                </div>
                                <?php if($statusClass == 'refused' && !empty($h['refusal_reason'])): ?>
                                    <div class="refusal-reason">
                                        <strong>سبب الاعتذار:</strong> <?= htmlspecialchars($h['refusal_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Assignment Form or Lockdown Message -->
        <div class="data-card">
            <?php if ($activeAssignment): ?>
                <div class="warning-box">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 2rem;"></i>
                    <div>
                        <strong style="display:block; margin-bottom:4px; font-size:1.1rem;">الإسناد مقفل حالياً</strong>
                        هذا البحث مسند بالفعل إلى <strong><?= htmlspecialchars($activeAssignment['full_name']) ?></strong>. 
                        لا يمكنك إسناد مراجع آخر حتى يقوم المراجع الحالي بالرد أو تنتهي مهلة الـ 48 ساعة تلقائياً.
                    </div>
                </div>
                <div class="button-group">
                    <a href="assign_reviewers.php" class="btn-back"><i class="fa-solid fa-arrow-right"></i> عودة للقائمة</a>
                </div>
            <?php else: ?>
                <h3 style="color: var(--primary-base); margin-top: 0; margin-bottom: 18px; font-size: 1.1rem; font-weight: 800;">
                    <i class="fa-solid fa-user-plus" style="color: var(--accent-base); margin-left:8px;"></i>
                    اختيار مراجع جديد للبحث
                </h3>

                <form action="submit_assignment.php" method="POST">
                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                    <div class="form-group">
                        <label class="form-label" for="reviewer_id">المراجع المتخصص المتاح</label>
                        <select name="reviewer_id" id="reviewer_id" required class="form-select irb-select">
                            <option value="">-- اختر مراجع من القائمة --</option>
                            <?php foreach ($reviewers as $rev): ?>
                                <option value="<?= $rev['id'] ?>">أ.د. <?= htmlspecialchars($rev['full_name']) ?> | <?= htmlspecialchars($rev['department'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 8px; background: var(--primary-light); padding: 8px; border-radius: 4px;">
                            <i class="fa-solid fa-info-circle"></i>
                            عند التأكيد، سيتم إرسال تنبيه للمراجع وسيكون أمامه 48 ساعة لقبول أو رفض الطلب.
                        </div>
                    </div>

                    <div class="button-group">
                        <a href="assign_reviewers.php" class="btn-back"><i class="fa-solid fa-arrow-right"></i> تراجع</a>
                        <button type="submit" name="assign_reviewer" class="btn-submit"><i class="fa-solid fa-check"></i> تأكيد الإسناد</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>