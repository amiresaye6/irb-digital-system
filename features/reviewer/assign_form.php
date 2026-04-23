<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$reviewers = $reviewsObj->getAvailableReviewers();
$assigned = $reviewsObj->getAssignedReviewers($application_id);
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

        .detail-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--primary-light);
            color: var(--primary-base);
            font-size: 0.85rem;
            font-weight: 700;
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

        .assigned-section {
            margin-top: 20px;
            border-top: 2px solid var(--border-light);
            padding-top: 18px;
        }

        .assigned-section .info-label {
            color: var(--primary-base);
        }

        .assigned-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .assigned-list li {
            background: var(--status-approved-bg);
            padding: 10px 14px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--status-approved-text);
            font-weight: 700;
            border-right: 4px solid var(--success-base);
            font-size: 0.9rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .assigned-list li i {
            color: var(--success-base);
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            font-weight: 800;
            color: var(--primary-base);
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            box-shadow: var(--shadow-md);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-back {
            background: var(--primary-light);
            color: var(--primary-base);
            border: 2px solid var(--primary-base);
            padding: 12px 24px;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-family: inherit;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            transition: all var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-back:hover {
            background: var(--primary-base);
            color: white;
            transform: translateY(-2px);
        }

        .form-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 6px;
            padding: 8px 12px;
            background: var(--primary-light);
            border-right: 3px solid var(--accent-base);
            border-radius: 4px;
            font-weight: 500;
            line-height: 1.4;
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
            max-width: 980px;
        }

        .data-card {
            max-width: 980px;
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
    <?php
    include __DIR__ . '/../../includes/sidebar.php';
    ?>

    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-user-plus"></i>
            إسناد مراجع للبحث
        </h2>
        <p class="page-subtitle">
            اختر المراجع المتخصص المناسب لتقييم هذا البحث العلمي
        </p>

        <!-- Application Details Card -->
        <div class="data-card">
            <div class="summary-grid">
                <div class="info-group">
                    <span class="info-label">رقم الملف</span>
                    <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                </div>

                <div class="info-group">
                    <span class="info-label">تاريخ التقديم</span>
                    <div class="meta-row">
                        <span class="meta-chip">
                            <i class="fa-regular fa-calendar"></i>
                            <?= htmlspecialchars(irb_format_arabic_date($app['created_at'])) ?>
                        </span>
                        <span class="meta-chip">
                            <i class="fa-regular fa-clock"></i>
                            <?= htmlspecialchars(irb_format_arabic_time($app['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <div class="info-group wide-group">
                    <span class="info-label">عنوان البحث</span>
                    <div class="info-value">
                        <i class="fa-solid fa-book" style="color: var(--accent-base);"></i>
                        <?= htmlspecialchars($app['title']) ?>
                    </div>
                </div>

                <div class="info-group">
                    <span class="info-label">الباحث الرئيسي</span>
                    <div class="info-value">
                        <i class="fa-solid fa-user-doctor" style="color: var(--primary-base);"></i>
                        <?= htmlspecialchars($app['principal_investigator']) ?>
                    </div>
                </div>

                <div class="info-group">
                    <span class="info-label">الكلية</span>
                    <div class="info-value">
                        <i class="fa-solid fa-graduation-cap" style="color: var(--accent-base);"></i>
                        <?= !empty($app['faculty']) ? htmlspecialchars($app['faculty']) : 'غير متوفر' ?>
                    </div>
                </div>

                <div class="info-group wide-group">
                    <span class="info-label">القسم</span>
                    <div class="info-value">
                        <i class="fa-solid fa-building-columns" style="color: var(--primary-base);"></i>
                        <?= !empty($app['department']) ? htmlspecialchars($app['department']) : 'غير متوفر' ?>
                    </div>
                </div>

                <div class="info-group wide-group">
                    <span class="info-label">الباحثون الآخرون</span>
                    <?php if (!empty($coInvestigators)): ?>
                        <ul class="details-list">
                            <?php foreach ($coInvestigators as $coInvestigator): ?>
                                <li>
                                    <i class="fa-solid fa-user"></i>
                                    <?= htmlspecialchars($coInvestigator) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="details-empty">لا يوجد باحثون آخرون مسجلون لهذا البحث.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assigned Reviewers List -->
            <?php if(!empty($assigned)): ?>
                <div class="assigned-section">
                    <span class="info-label">
                        <i class="fa-solid fa-check-circle" style="margin-right: 5px;"></i>
                        المراجعون المعينون (<?= count($assigned) ?>)
                    </span>
                    <ul class="assigned-list">
                        <?php foreach($assigned as $rev): ?>
                            <li>
                                <i class="fa-solid fa-user-check"></i>
                                <?= htmlspecialchars($rev['full_name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviewer Selection Card -->
        <div class="data-card">
            <h3 style="color: var(--primary-base); margin-top: 0; margin-bottom: 18px; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--accent-base);"></i>
                اختر المراجع الجديد
            </h3>

            <form action="submit_assignment.php" method="POST">
                <input type="hidden" name="application_id" value="<?= $app['id'] ?>">

                <div class="form-group">
                    <label class="form-label" for="reviewer_id">
                        المراجع المتخصص
                    </label>
                    <select name="reviewer_id" id="reviewer_id" required class="form-select irb-select">
                        <option value="">-- اختر المراجع --</option>
                        <?php foreach ($reviewers as $rev): ?>
                            <?php
                                $isAssigned = false;
                                foreach($assigned as $a) {
                                    if((int)$a['id'] === (int)$rev['id']) {
                                        $isAssigned = true; break;
                                    }
                                }
                                if(!$isAssigned):
                            ?>
                            <option value="<?= $rev['id'] ?>">
                                أ.د. <?= htmlspecialchars($rev['full_name']) ?>
                                <?= !empty($rev['department']) ? '| ' . htmlspecialchars($rev['department']) : '' ?>
                            </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-hint">
                        اختر من قائمة المراجعين المتاحين الذين لم يتم تعيينهم مسبقاً لهذا البحث
                    </div>
                </div>

                <div class="button-group">
                    <a href="assign_reviewers.php" class="btn-back">
                        <i class="fa-solid fa-arrow-right"></i>
                        تراجع
                    </a>
                    <button type="submit" name="assign_reviewer" class="btn-submit">
                        <i class="fa-solid fa-check"></i>
                        تأكيد الإسناد
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
