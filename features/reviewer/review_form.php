<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('reviewer');
if (!isset($_GET['application_id']) || empty($_GET['application_id'])) {
    header("Location: assigned_reserches.php"); exit;
}

require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$reviewsObj = new Reviews();
$application_id = intval($_GET['application_id']);
$reviewer_id = $_SESSION['user_id'];

$app = $reviewsObj->getApplicationDetails($application_id);
if (!$app) { die("البحث غير موجود."); }

$review = $reviewsObj->getReview($application_id, $reviewer_id);
if (!$review) { die("ليس لديك صلاحية مراجعة هذا البحث."); }

$documents = $reviewsObj->getApplicationDocuments($application_id);
$myComments = $reviewsObj->getReviewComments($review['id']);

$coInvestigators = [];
if (!empty($app['co_investigators'])) {
    $decoded = json_decode($app['co_investigators'], true);
    if (is_array($decoded)) $coInvestigators = $decoded;
}

$isBlinded = !empty($app['is_blinded']);
$isFinalApproved = ($review['current_stage'] === 'approved');

$docLabels = [
    'protocol' => ['بروتوكول البحث', 'fa-file-medical', '#2c3e50'],
    'conflict_of_interest' => ['إقرار تعارض المصالح', 'fa-handshake-angle', '#e67e22'],
    'irb_checklist' => ['قائمة فحص IRB', 'fa-list-check', '#1abc9c'],
    'pi_consent' => ['موافقة الباحث الرئيسي', 'fa-user-pen', '#3498db'],
    'patient_consent' => ['نموذج موافقة المرضى', 'fa-clipboard-user', '#9b59b6'],
];

$success_msg = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل البحث ومراجعته</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right:260px; min-height:100vh; padding:30px 24px; background:var(--bg-page); display:flex; flex-direction:column; align-items:center; }
        .content > * { width:100%; max-width:1000px; }

        .page-header { margin-bottom:24px; }
        .page-title { color:var(--primary-base); font-size:1.6rem; font-weight:800; display:flex; align-items:center; gap:12px; margin-bottom:6px; }
        .page-title i { color:var(--accent-base); }
        .page-subtitle { color:var(--text-muted); font-size:0.9rem; font-weight:500; line-height:1.5; }

        .card { background:var(--bg-surface); padding:24px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border-light); margin-bottom:20px; }
        .card-header { display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid var(--border-light); }
        .card-header h3 { color:var(--primary-base); font-size:1.1rem; font-weight:800; margin:0; }
        .card-header i { color:var(--accent-base); font-size:1.1rem; }

        .summary-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
        .info-group { background:linear-gradient(180deg,rgba(44,62,80,0.03) 0%,#fff 100%); border:1px solid rgba(189,195,199,0.55); border-radius:var(--radius-md); padding:14px 16px; }
        .info-label { font-weight:800; color:var(--primary-base); display:block; margin-bottom:8px; font-size:0.82rem; text-transform:uppercase; letter-spacing:0.5px; }
        .info-value { font-size:1rem; color:var(--text-main); font-weight:700; display:flex; align-items:center; gap:10px; line-height:1.4; word-wrap:break-word; }
        .wide-group { grid-column:1/-1; }
        .badge-serial { font-weight:800; color:white; background:var(--primary-base); padding:6px 12px; border-radius:var(--radius-sm); font-size:0.9rem; }

        .details-list { list-style:none; padding:0; margin:0; display:flex; flex-wrap:wrap; gap:8px; }
        .details-list li { background:#fff; border:1px solid rgba(189,195,199,0.65); border-radius:999px; padding:8px 12px; font-size:0.88rem; font-weight:700; display:flex; align-items:center; gap:6px; }
        .details-empty { color:var(--text-muted); font-size:0.9rem; font-weight:600; }

        .redacted-name { color:#e74c3c; font-weight:800; display:flex; align-items:center; gap:8px; }

        /* Documents Grid */
        .docs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
        .doc-card { background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%); border:1.5px solid var(--border-light); border-radius:var(--radius-md); padding:18px; display:flex; align-items:center; gap:14px; transition:all var(--transition-smooth); }
        .doc-card:hover { border-color:var(--accent-base); transform:translateY(-2px); box-shadow:var(--shadow-md); }
        .doc-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .doc-icon i { font-size:1.3rem; color:white; }
        .doc-info { flex:1; min-width:0; }
        .doc-name { font-weight:700; color:var(--text-main); font-size:0.92rem; margin-bottom:4px; }
        .doc-actions { display:flex; gap:8px; }
        .doc-btn { padding:6px 12px; border-radius:8px; font-size:0.8rem; font-weight:700; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:all var(--transition-smooth); border:1.5px solid var(--border-light); background:#fff; color:var(--primary-base); }
        .doc-btn:hover { background:var(--primary-base); color:white; border-color:var(--primary-base); }
        .no-docs { text-align:center; padding:30px; color:var(--text-muted); }
        .no-docs i { font-size:2.5rem; margin-bottom:10px; opacity:0.4; display:block; }

        /* Reviews Timeline */
        .reviews-timeline { position:relative; padding-right:30px; }
        .reviews-timeline::before { content:''; position:absolute; right:10px; top:0; bottom:0; width:3px; background:linear-gradient(to bottom,var(--accent-base),var(--primary-base)); border-radius:4px; }
        .review-item ,.review-item2 { position:relative; margin-bottom:20px; padding:16px 20px; background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%); border:1px solid var(--border-light); border-radius:var(--radius-md); }
        .review-item::before { content:''; position:absolute; right:-26px; top:20px; width:14px; height:14px; border-radius:50%; border:3px solid var(--accent-base); background:white; }
        .review-item.decision-approved::before { border-color:#27ae60; background:#27ae60; }
        .review-item.decision-rejected::before { border-color:#e74c3c; background:#e74c3c; }
        .review-item.decision-needs_modification::before { border-color:#f39c12; background:#f39c12; }
        .review-item.decision-pending::before { border-color:#95a5a6; background:#95a5a6; }
        .review-meta { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-bottom:10px; }
        .reviewer-name { font-weight:800; color:var(--primary-base); font-size:0.95rem; }
        .review-date { font-size:0.82rem; color:var(--text-muted); font-weight:600; }
        .review-decision-badge { display:inline-flex; align-items:center; gap:6px; padding:5px 12px; border-radius:999px; font-size:0.8rem; font-weight:700; }
        .review-decision-badge.approved { background:var(--status-approved-bg); color:var(--status-approved-text); }
        .review-decision-badge.rejected { background:var(--status-rejected-bg); color:var(--status-rejected-text); }
        .review-decision-badge.needs_modification { background:#fdf2e9; color:#b9770e; }
        .review-decision-badge.pending { background:var(--status-pending-bg); color:var(--status-pending-text); }
        .review-comment { font-size:0.9rem; color:var(--text-main); line-height:1.6; padding:10px 14px; background:var(--bg-page); border-radius:8px; border-right:3px solid var(--accent-base); }
        .no-comment { font-style:italic; color:var(--text-muted); font-size:0.85rem; }

        /* Action Area */
        .action-area { display:flex; gap:12px; justify-content:flex-end; flex-wrap:wrap; }
        .btn-primary { background:var(--accent-base); color:white; border:none; padding:12px 24px; border-radius:var(--radius-md); cursor:pointer; font-family:inherit; font-weight:800; font-size:0.95rem; transition:all var(--transition-smooth); box-shadow:var(--shadow-md); display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-primary:hover { background:var(--accent-dark); transform:translateY(-2px); box-shadow:var(--shadow-lg); }
        .btn-secondary { background:var(--primary-light); color:var(--primary-base); border:2px solid var(--primary-base); padding:12px 24px; border-radius:var(--radius-md); font-family:inherit; font-weight:800; font-size:0.95rem; transition:all var(--transition-smooth); display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-secondary:hover { background:var(--primary-base); color:white; transform:translateY(-2px); }

        /* Final Approved Banner */
        .final-banner { background:linear-gradient(135deg,#27ae60 0%,#2ecc71 100%); color:white; padding:18px 24px; border-radius:var(--radius-md); display:flex; align-items:center; gap:14px; font-weight:700; font-size:1rem; box-shadow:0 4px 15px rgba(39,174,96,0.3); }
        .final-banner i { font-size:1.8rem; }

        /* Success Alert */
        .alert-success { background:linear-gradient(135deg,#d5f5e3 0%,#eafaf1 100%); color:#1e8449; padding:14px 20px; border-radius:var(--radius-md); border:1px solid #a9dfbf; display:flex; align-items:center; gap:10px; font-weight:700; margin-bottom:20px; animation:slideDown 0.4s ease; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-10px)} to{opacity:1;transform:translateY(0)} }

        @media(max-width:992px) { .content{margin-right:0;padding:24px 14px;} .summary-grid{grid-template-columns:1fr;} .docs-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-file-circle-check"></i> تفاصيل البحث ومراجعته</h2>
            <p class="page-subtitle">عرض جميع بيانات البحث والمستندات المرفقة مع إمكانية تقديم قرار المراجعة</p>
        </div>

        <?php if ($success_msg === '1'): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> تم حفظ قرارك بنجاح!</div>
        <?php endif; ?>

        <?php if ($isFinalApproved): ?>
            <div class="final-banner" style="margin-bottom:20px;">
                <i class="fa-solid fa-shield-check"></i>
                <div>
                    <div style="font-size:1.1rem;">تم الاعتماد النهائي من الإدارة</div>
                    <div style="font-size:0.85rem;opacity:0.9;font-weight:500;">هذا البحث حاصل على الموافقة النهائية ولا يمكن تعديل القرار.</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Application Details -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-clipboard-list"></i><h3>بيانات البحث</h3></div>
            <div class="summary-grid">
                <div class="info-group">
                    <span class="info-label">رقم الملف</span>
                    <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                </div>
                <div class="info-group">
                    <span class="info-label">تاريخ التقديم</span>
                    <div class="info-value"><i class="fa-regular fa-calendar" style="color:var(--accent-base)"></i> <?= htmlspecialchars(irb_format_arabic_date($app['created_at'])) ?></div>
                </div>
                <div class="info-group wide-group">
                    <span class="info-label">عنوان البحث</span>
                    <div class="info-value"><i class="fa-solid fa-book" style="color:var(--accent-base)"></i> <?= htmlspecialchars($app['title']) ?></div>
                </div>
                <div class="info-group">
                    <span class="info-label">الباحث الرئيسي</span>
                    <?php if ($isBlinded): ?>
                        <div class="redacted-name"><i class="fa-solid fa-user-secret"></i> معلومات محجوبة</div>
                    <?php else: ?>
                        <div class="info-value"><i class="fa-solid fa-user-doctor" style="color:var(--primary-base)"></i> <?= htmlspecialchars($app['principal_investigator']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="info-group">
                    <span class="info-label">الكلية / القسم</span>
                        <div class="info-value"><i class="fa-solid fa-building-columns" style="color:var(--primary-base)"></i> <?= !empty($app['faculty']) ? htmlspecialchars($app['faculty']) : 'غير متوفر' ?> — <?= !empty($app['department']) ? htmlspecialchars($app['department']) : '' ?></div>
                
                </div>
                <?php if (!$isBlinded): ?>
                <div class="info-group wide-group">
                    <span class="info-label">الباحثون المشاركون</span>
                    <?php if (!empty($coInvestigators)): ?>
                        <ul class="details-list">
                            <?php foreach ($coInvestigators as $ci): ?>
                                <li><i class="fa-solid fa-user"></i> <?= htmlspecialchars($ci) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="details-empty">لا يوجد باحثون مشاركون</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Documents -->
        <div class="card">
            <div class="card-header"><i class="fa-solid fa-folder-open"></i><h3>المستندات المرفقة (<?= count($documents) ?>)</h3></div>
            <?php if (empty($documents)): ?>
                <div class="no-docs"><i class="fa-solid fa-file-circle-xmark"></i><p>لا توجد مستندات مرفقة لهذا البحث</p></div>
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

        <!-- My Review & Comments -->
        <div class="card ">
            <div class="card-header"><i class="fa-solid fa-clipboard-check"></i><h3>قراري وتعليقاتي</h3></div>

            <?php
                $myDecClass = $review['decision'];
                $myDecLabel = match($review['decision']) {
                    'approved' => ['مقبول', 'fa-check-double'],
                    'rejected' => ['مرفوض', 'fa-xmark'],
                    'needs_modification' => ['يحتاج تعديل', 'fa-pen'],
                    default => ['قيد المراجعة', 'fa-hourglass-half'],
                };
            ?>
            <div class="review-item2" style="margin-bottom:20px;">
                <div class="review-meta">
                    <span class="reviewer-name"><i class="fa-solid fa-user-shield"></i> قراري الحالي</span>
                    <span class="review-decision-badge <?= $myDecClass ?>"><i class="fa-solid <?= $myDecLabel[1] ?>"></i> <?= $myDecLabel[0] ?></span>
                </div>
                <?php if (!empty($review['reviewed_at'])): ?>
                    <div class="review-date" style="margin-bottom:8px"><i class="fa-regular fa-clock"></i> آخر تحديث: <?= htmlspecialchars(irb_format_arabic_date($review['reviewed_at'])) ?></div>
                <?php endif; ?>
            </div>

            <div style="border-top:2px solid var(--border-light);padding-top:16px;">
                <div style="font-weight:800;color:var(--primary-base);font-size:0.9rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
                    <i class="fa-solid fa-comments" style="color:var(--accent-base)"></i>
                    تعليقاتي (<?= count($myComments) ?>)
                </div>
                <?php if (!empty($myComments)): ?>
                    <div class="reviews-timeline">
                        <?php foreach ($myComments as $cmt): ?>
                            <div class="review-item decision-<?= $myDecClass ?>">
                                <div class="review-comment">
                                    <?= nl2br(htmlspecialchars($cmt['comment'])) ?>
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:8px;display:flex;align-items:center;gap:6px;">
                                    <i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_date($cmt['created_at'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-docs"><i class="fa-solid fa-comment-slash"></i><p>لم تقم بإضافة أي تعليقات بعد</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="action-area">
                <a href="assigned_reserches.php" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> العودة للقائمة</a>
                <?php if (!$isFinalApproved): ?>
                    <a href="submit_decision.php?application_id=<?= $application_id ?>" class="btn-primary">
                        <i class="fa-solid fa-gavel"></i>
                        <?= ($review['decision'] === 'pending') ? 'إضافة قرار المراجعة' : 'تعديل قرار المراجعة' ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
