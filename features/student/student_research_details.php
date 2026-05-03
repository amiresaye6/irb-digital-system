<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('student'); 
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_researches.php"); exit;
}
require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$student_id = $_SESSION['user_id'];
$app_id = intval($_GET['id']);
$app = $appObj->getApplicationFullDetails($app_id, $student_id);
if (!$app) { die("البحث غير موجود أو لا تملك صلاحية عرضه."); }

$documents = $appObj->getApplicationDocuments($app_id);
$feedback = $appObj->getReviewerFeedback($app_id);
$sample = $appObj->getSampleSize($app_id);
$needsMod = $appObj->hasNeedsModification($app_id);
$keywords = $appObj->getApplicationKeywords($app_id);

$coInvestigators = [];
if (!empty($app['co_investigators'])) {
    $decoded = json_decode($app['co_investigators'], true);
    if (is_array($decoded)) $coInvestigators = $decoded;
}

$stageOrder = ['pending_admin','awaiting_initial_payment','awaiting_sample_calc','awaiting_sample_payment','under_review','approved_by_reviewer','approved'];
$stageNames = ['تقديم الطلب','المراجعة الأولية','حساب العينة','دفع العينة','المراجعة السرية','موافقة المراجع','الاعتماد النهائي'];
$stageIcons = ['fa-file-circle-plus','fa-magnifying-glass','fa-calculator','fa-credit-card','fa-microscope','fa-user-check','fa-certificate'];

$currentIdx = array_search($app['current_stage'], $stageOrder);
if ($currentIdx === false) $currentIdx = ($app['current_stage'] === 'rejected') ? -1 : 0;
$isRejected = ($app['current_stage'] === 'rejected');

$docLabels = [
    'research' => ['ملف البحث', 'fa-file-lines', '#2c3e50'],
    'protocol' => ['بروتوكول البحث', 'fa-file-medical', '#2c3e50'],
    'conflict_of_interest' => ['إقرار تعارض المصالح', 'fa-handshake-angle', '#e67e22'],
    'irb_checklist' => ['قائمة فحص IRB', 'fa-list-check', '#1abc9c'],
    'pi_consent' => ['موافقة الباحث الرئيسي', 'fa-user-pen', '#3498db'],
    'patient_consent' => ['نموذج موافقة المرضى', 'fa-clipboard-user', '#9b59b6'],
    'photos_biopsies_consent' => ['موافقة الصور والعينات', 'fa-camera', '#e74c3c'],
    'protocol_review_app' => ['نموذج مراجعة البروتوكول', 'fa-file-shield', '#16a085'],
];
$success_msg = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل البحث - <?= htmlspecialchars($app['serial_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body{background:var(--bg-page)}
        .content{margin-right:260px;min-height:100vh;padding:30px 24px;display:flex;flex-direction:column;align-items:center}
        .content>*{width:100%;max-width:1050px}
        .page-title{color:var(--primary-base);font-size:1.5rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:6px}
        .page-title i{color:var(--accent-base)}
        .page-subtitle{color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:22px}
        .card{background:var(--bg-surface);padding:24px;border-radius:var(--radius-lg);box-shadow:var(--shadow-md);border:1px solid var(--border-light);margin-bottom:20px}
        .card-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid var(--border-light)}
        .card-header h3{color:var(--primary-base);font-size:1.1rem;font-weight:800;margin:0}
        .card-header i{color:var(--accent-base);font-size:1.1rem}

        /* Progress Timeline */
        .progress-card{padding:30px 24px 36px}
        .progress-track{display:flex;align-items:flex-start;justify-content:space-between;position:relative;padding:14px 0 0}
        .progress-track::before{content:'';position:absolute;top:32px;right:40px;left:40px;height:4px;background:var(--border-light);border-radius:4px;z-index:0}
        .progress-step{display:flex;flex-direction:column;align-items:center;position:relative;z-index:1;flex:1;padding:0 2px}
        .step-circle{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;border:3px solid var(--border-light);background:white;color:var(--text-muted);transition:all 0.4s;margin-bottom:10px;position:relative}
        .progress-step.completed .step-circle{background:var(--accent-base);border-color:var(--accent-base);color:white;box-shadow:0 4px 12px rgba(26,188,156,0.35)}
        .progress-step.active .step-circle{background:var(--accent-base);border-color:var(--accent-base);color:white;box-shadow:0 0 0 6px rgba(26,188,156,0.2);animation:pulse 2s infinite}
        .progress-step.rejected .step-circle{background:#e74c3c;border-color:#e74c3c;color:white;box-shadow:0 4px 12px rgba(231,76,60,0.35)}
        @keyframes pulse{0%,100%{box-shadow:0 0 0 6px rgba(26,188,156,0.2)}50%{box-shadow:0 0 0 12px rgba(26,188,156,0.08)}}
        .step-label{font-size:0.74rem;font-weight:700;color:var(--text-muted);text-align:center;line-height:1.3;max-width:95px}
        .progress-step.completed .step-label,.progress-step.active .step-label{color:var(--primary-base);font-weight:800}
        .progress-line-filled{position:absolute;top:32px;left:40px;height:4px;background:linear-gradient(to right,var(--accent-base),var(--primary-base));border-radius:4px;z-index:0;transition:all 0.6s}

        .summary-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px}
        .info-group{background:linear-gradient(180deg,rgba(44,62,80,0.03) 0%,#fff 100%);border:1px solid rgba(189,195,199,0.55);border-radius:var(--radius-md);padding:14px 16px}
        .info-label{font-weight:800;color:var(--primary-base);display:block;margin-bottom:8px;font-size:0.82rem;text-transform:uppercase;letter-spacing:0.5px}
        .info-value{font-size:1rem;color:var(--text-main);font-weight:700;display:flex;align-items:center;gap:10px;line-height:1.4}
        .wide-group{grid-column:1/-1}
        .badge-serial{font-weight:800;color:white;background:var(--primary-base);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.9rem}
        .details-list{list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:8px}
        .details-list li{background:#fff;border:1px solid rgba(189,195,199,0.65);border-radius:999px;padding:8px 12px;font-size:0.88rem;font-weight:700;display:flex;align-items:center;gap:6px}
        .details-empty{color:var(--text-muted);font-size:0.9rem;font-weight:600}

        .docs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
        .doc-card{background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%);border:1.5px solid var(--border-light);border-radius:var(--radius-md);padding:16px;display:flex;align-items:center;gap:12px;transition:all var(--transition-smooth)}
        .doc-card:hover{border-color:var(--accent-base);transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .doc-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .doc-icon i{font-size:1.2rem;color:white}
        .doc-info{flex:1;min-width:0}
        .doc-name{font-weight:700;color:var(--text-main);font-size:0.9rem;margin-bottom:4px}
        .doc-actions{display:flex;gap:6px}
        .doc-btn{padding:5px 10px;border-radius:8px;font-size:0.78rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:all var(--transition-smooth);border:1.5px solid var(--border-light);background:#fff;color:var(--primary-base)}
        .doc-btn:hover{background:var(--primary-base);color:white;border-color:var(--primary-base)}
        .no-docs{text-align:center;padding:30px;color:var(--text-muted)}
        .no-docs i{font-size:2.5rem;margin-bottom:10px;opacity:0.4;display:block}

        .feedback-item{padding:14px 18px;background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%);border:1px solid var(--border-light);border-radius:var(--radius-md);margin-bottom:12px;border-right:4px solid var(--accent-base)}
        .feedback-item.dec-approved{border-right-color:#27ae60}
        .feedback-item.dec-rejected{border-right-color:#e74c3c}
        .feedback-item.dec-needs_modification{border-right-color:#f39c12}
        .feedback-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px;flex-wrap:wrap}
        .feedback-reviewer{font-weight:800;color:var(--primary-base);font-size:0.9rem;display:flex;align-items:center;gap:6px}
        .feedback-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:999px;font-size:0.78rem;font-weight:700}
        .feedback-badge.approved{background:var(--status-approved-bg);color:var(--status-approved-text)}
        .feedback-badge.rejected{background:var(--status-rejected-bg);color:var(--status-rejected-text)}
        .feedback-badge.needs_modification{background:#fdf2e9;color:#b9770e}
        .feedback-text{font-size:0.9rem;color:var(--text-main);line-height:1.6;padding:10px 14px;background:var(--bg-page);border-radius:8px}
        .feedback-date{font-size:0.78rem;color:var(--text-muted);margin-top:6px;display:flex;align-items:center;gap:5px}

        .mod-banner{background:linear-gradient(135deg,#f39c12 0%,#f1c40f 100%);color:#fff;padding:16px 20px;border-radius:var(--radius-md);display:flex;align-items:center;gap:14px;font-weight:700;margin-bottom:20px;box-shadow:0 4px 15px rgba(243,156,18,0.3)}
        .mod-banner i{font-size:1.6rem}
        .mod-banner a{background:white;color:#f39c12;padding:8px 18px;border-radius:var(--radius-md);text-decoration:none;font-weight:800;font-size:0.9rem;display:inline-flex;align-items:center;gap:6px;transition:all var(--transition-smooth);margin-right:auto}
        .mod-banner a:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
        .approved-banner{background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%);color:white;padding:16px 20px;border-radius:var(--radius-md);display:flex;align-items:center;gap:14px;font-weight:700;margin-bottom:20px;box-shadow:0 4px 15px rgba(39,174,96,0.3)}
        .approved-banner i{font-size:1.6rem}
        .rejected-banner{background:linear-gradient(135deg,#c0392b 0%,#e74c3c 100%);color:white;padding:16px 20px;border-radius:var(--radius-md);display:flex;align-items:center;gap:14px;font-weight:700;margin-bottom:20px;box-shadow:0 4px 15px rgba(192,57,43,0.3)}
        .rejected-banner i{font-size:1.6rem}

        .action-area{display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap}
        .btn-primary{background:var(--accent-base);color:white;border:none;padding:12px 24px;border-radius:var(--radius-md);font-family:inherit;font-weight:800;font-size:0.95rem;transition:all var(--transition-smooth);box-shadow:var(--shadow-md);display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-primary:hover{background:var(--accent-dark);transform:translateY(-2px)}
        .btn-secondary{background:var(--primary-light);color:var(--primary-base);border:2px solid var(--primary-base);padding:12px 24px;border-radius:var(--radius-md);font-family:inherit;font-weight:800;font-size:0.95rem;transition:all var(--transition-smooth);display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-secondary:hover{background:var(--primary-base);color:white;transform:translateY(-2px)}

        .alert-success{background:linear-gradient(135deg,#d5f5e3 0%,#eafaf1 100%);color:#1e8449;padding:14px 20px;border-radius:var(--radius-md);border:1px solid #a9dfbf;display:flex;align-items:center;gap:10px;font-weight:700;margin-bottom:20px;animation:slideDown 0.4s ease}
        @keyframes slideDown{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}

        @media(max-width:992px){.content{margin-right:0;padding:24px 14px}.summary-grid,.docs-grid{grid-template-columns:1fr}.progress-track{overflow-x:auto;padding-bottom:10px}.progress-step{min-width:75px}}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-file-circle-check"></i> تفاصيل البحث</h2>
        <p class="page-subtitle">عرض بيانات البحث ومتابعة مسار التقدم</p>

        <?php if ($success_msg === '1'): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> تم تحديث المستندات بنجاح!</div>
        <?php endif; ?>

        <?php if ($app['current_stage'] === 'approved'): ?>
            <div class="approved-banner"><i class="fa-solid fa-certificate"></i><div><div style="font-size:1.05rem">تم الاعتماد النهائي</div><div style="font-size:0.85rem;opacity:0.9;font-weight:500">هذا البحث حاصل على موافقة IRB النهائية.</div></div></div>
        <?php elseif ($isRejected): ?>
            <div class="rejected-banner"><i class="fa-solid fa-ban"></i><div><div style="font-size:1.05rem">تم رفض البحث</div><div style="font-size:0.85rem;opacity:0.9;font-weight:500">يرجى مراجعة أسباب الرفض في قسم ملاحظات المراجعين.</div></div></div>
        <?php elseif ($needsMod): ?>
            <div class="mod-banner">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><div style="font-size:1.05rem">بحثك يحتاج إلى تعديلات</div><div style="font-size:0.85rem;opacity:0.9;font-weight:500">تم إرسال ملاحظات من المراجعين. يرجى تحديث المستندات.</div></div>
                <a href="update_application.php?id=<?= $app_id ?>"><i class="fa-solid fa-pen-to-square"></i> تحديث المستندات</a>
            </div>
        <?php endif; ?>

        <!-- Progress Timeline -->
        <div class="card progress-card">
            <div class="card-header"><i class="fa-solid fa-route"></i><h3>مسار العمل والتقدم</h3></div>
            <?php
                $totalSteps = count($stageOrder);
                // In RTL flex: DOM order 6,5,4,3,2,1,0 → visual order RIGHT(6) to LEFT(0)
                // Stage 0 is rightmost, stage 6 is leftmost
                // Filled line starts from LEFT (step 0 side) and grows RIGHT toward higher stages
                // Step position from LEFT = stageIndex / (totalSteps-1)
                if ($isRejected) {
                    $filledPercent = 0;
                } elseif ($currentIdx >= 0) {
                    $filledPercent = ($currentIdx / max($totalSteps - 1, 1)) * 100;
                } else {
                    $filledPercent = 0;
                }
                // Calculate pixel offset so width never exceeds the track (track = container - 80px)
                $pxOffset = round($filledPercent * 80 / 100);
            ?>
            <div class="progress-track">
                <div class="progress-line-filled" style="width:calc(<?= $filledPercent ?>% - <?= $pxOffset ?>px)"></div>
                <?php for ($i = $totalSteps - 1; $i >= 0; $i--):
                    $stepClass = '';
                    if ($isRejected) {
                        $stepClass = ($i > $currentIdx && $currentIdx >= 0) ? 'completed' : (($i === 0) ? 'rejected' : '');
                    } else {
                        if ($i < $currentIdx) $stepClass = 'completed';
                        elseif ($i === $currentIdx) $stepClass = 'active';
                    }
                ?>
                    <div class="progress-step <?= $stepClass ?>">
                        <div class="step-circle"><i class="fa-solid <?= $stageIcons[$i] ?>"></i></div>
                        <div class="step-label"><?= $stageNames[$i] ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Application Details -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clipboard-list"></i><h3>بيانات البحث</h3></div>
            <div class="summary-grid">
                <div class="info-group"><span class="info-label">رقم الملف</span><span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span></div>
                <div class="info-group"><span class="info-label">تاريخ التقديم</span><div class="info-value"><i class="fa-regular fa-calendar" style="color:var(--accent-base)"></i> <?= htmlspecialchars(irb_format_arabic_date($app['created_at'])) ?></div></div>
                <div class="info-group wide-group"><span class="info-label">عنوان البحث</span><div class="info-value"><i class="fa-solid fa-book" style="color:var(--accent-base)"></i> <?= htmlspecialchars($app['title']) ?></div></div>
                <div class="info-group"><span class="info-label">الكلمات المفتاحية</span><div class="info-value">
                <ul class="details-list">    
                <?php foreach ($keywords as $keyword): ?>
                        <li><i class="fa-solid fa-tag"> <?= htmlspecialchars($keyword) ?></i>
                <?php endforeach; ?>
                </ul>
                </div></div>
                <div class="info-group"><span class="info-label">الباحث الرئيسي</span><div class="info-value"><i class="fa-solid fa-user-doctor" style="color:var(--primary-base)"></i> <?= htmlspecialchars($app['principal_investigator']) ?></div></div>
                <div class="info-group"><span class="info-label">الكلية / القسم</span><div class="info-value"><i class="fa-solid fa-building-columns" style="color:var(--primary-base)"></i> <?= !empty($app['faculty']) ? htmlspecialchars($app['faculty']) : 'غير متوفر' ?> — <?= !empty($app['department']) ? htmlspecialchars($app['department']) : '' ?></div></div>
                <?php if ($sample): ?>
                <div class="info-group"><span class="info-label">حجم العينة</span><div class="info-value"><i class="fa-solid fa-chart-pie" style="color:var(--accent-base)"></i> <?= htmlspecialchars($sample['calculated_size']) ?></div></div>
                <?php endif; ?>
                <div class="info-group wide-group"><span class="info-label">الباحثون المشاركون</span>
                    <?php if (!empty($coInvestigators)): ?><ul class="details-list"><?php foreach ($coInvestigators as $ci): ?><li><i class="fa-solid fa-user"></i> <?= htmlspecialchars($ci) ?></li><?php endforeach; ?></ul>
                    <?php else: ?><div class="details-empty">لا يوجد باحثون مشاركون</div><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-folder-open"></i><h3>المستندات المرفقة (<?= count($documents) ?>)</h3></div>
            <?php if (empty($documents)): ?>
                <div class="no-docs"><i class="fa-solid fa-file-circle-xmark"></i><p>لا توجد مستندات مرفقة</p></div>
            <?php else: ?>
                <div class="docs-grid">
                    <?php foreach ($documents as $doc):
                        $type = $doc['document_type'];
                        $label = $docLabels[$type] ?? [$type, 'fa-file', '#7f8c8d'];
                    ?>
                        <div class="doc-card">
                            <div class="doc-icon" style="background:<?= $label[2] ?>"><i class="fa-solid <?= $label[1] ?>"></i></div>
                            <div class="doc-info">
                                <div class="doc-name"><?= htmlspecialchars($label[0]) ?></div>
                                <div class="doc-actions">
                                    <a href="/irb-digital-system/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="doc-btn"><i class="fa-solid fa-eye"></i> عرض</a>
                                    <a href="/irb-digital-system/<?= htmlspecialchars($doc['file_path']) ?>" download class="doc-btn"><i class="fa-solid fa-download"></i> تحميل</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Reviewer Feedback -->
        <?php if (!empty($feedback)): ?>
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-comments"></i><h3>ملاحظات المراجعين</h3></div>
            <?php foreach ($feedback as $fb):
                if (empty($fb['comment'])) continue;
                $decBadge = match($fb['decision']) {
                    'approved' => ['مقبول','fa-check-double','approved'],
                    'rejected' => ['مرفوض','fa-xmark','rejected'],
                    'needs_modification' => ['يحتاج تعديل','fa-pen','needs_modification'],
                    default => ['قيد المراجعة','fa-hourglass-half','pending'],
                };
            ?>
                <div class="feedback-item dec-<?= $fb['decision'] ?>">
                    <div class="feedback-meta">
                        <span class="feedback-reviewer"><i class="fa-solid fa-user-secret"></i> <?= htmlspecialchars($fb['reviewer_label']) ?></span>
                        <span class="feedback-badge <?= $decBadge[2] ?>"><i class="fa-solid <?= $decBadge[1] ?>"></i> <?= $decBadge[0] ?></span>
                    </div>
                    <div class="feedback-text"><?= nl2br(htmlspecialchars($fb['comment'])) ?></div>
                    <?php if (!empty($fb['comment_date'])): ?>
                        <div class="feedback-date"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($fb['comment_date'])) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="card">
            <div class="action-area">
                <a href="student_researches.php" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> العودة للقائمة</a>
                <?php if ($app['current_stage'] === 'under_review' || ($needsMod && $app['current_stage'] !== 'approved' && $app['current_stage'] !== 'rejected')): ?>
                    <a href="update_application.php?id=<?= $app_id ?>" class="btn-primary"><i class="fa-solid fa-pen-to-square"></i> تحديث البحث</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
