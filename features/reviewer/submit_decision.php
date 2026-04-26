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

$review = $reviewsObj->getReview($application_id, $reviewer_id);
if (!$review) { die("ليس لديك صلاحية مراجعة هذا البحث."); }
if ($review['current_stage'] === 'approved') {
    header("Location: review_form.php?application_id=$application_id"); exit;
}

$app = $reviewsObj->getApplicationDetails($application_id);
$currentDecision = $review['decision'];
$isUpdate = ($currentDecision !== 'pending');

$error_msg = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isUpdate ? 'تعديل' : 'إضافة' ?> قرار المراجعة</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: var(--bg-page); }
        .content { margin-right:260px; min-height:100vh; padding:30px 24px; background:var(--bg-page); display:flex; flex-direction:column; align-items:center; }
        .content > * { width:100%; max-width:800px; }

        .page-title { color:var(--primary-base); font-size:1.6rem; font-weight:800; display:flex; align-items:center; gap:12px; margin-bottom:6px; }
        .page-title i { color:var(--accent-base); }
        .page-subtitle { color:var(--text-muted); font-size:0.9rem; font-weight:500; line-height:1.5; margin-bottom:24px; }

        .card { background:var(--bg-surface); padding:24px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border-light); margin-bottom:20px; }
        .card-header { display:flex; align-items:center; gap:10px; margin-bottom:18px; padding-bottom:14px; border-bottom:2px solid var(--border-light); }
        .card-header h3 { color:var(--primary-base); font-size:1.1rem; font-weight:800; margin:0; }
        .card-header i { color:var(--accent-base); font-size:1.1rem; }

        .app-brief { display:flex; align-items:center; gap:14px; padding:14px; background:var(--bg-page); border-radius:var(--radius-md); border:1px solid var(--border-light); margin-bottom:20px; }
        .app-brief .badge-serial { font-weight:800; color:white; background:var(--primary-base); padding:6px 12px; border-radius:var(--radius-sm); font-size:0.85rem; flex-shrink:0; }
        .app-brief-title { font-weight:700; color:var(--text-main); font-size:0.95rem; line-height:1.4; }

        /* Decision Cards */
        .decisions-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
        .decision-card { position:relative; cursor:pointer; padding:22px 16px; border-radius:var(--radius-md); border:2.5px solid var(--border-light); background:#fff; text-align:center; transition:all 0.3s cubic-bezier(.4,0,.2,1); }
        .decision-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .decision-card input[type="radio"] { position:absolute; opacity:0; pointer-events:none; }
        .decision-card.selected { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.12); }
        .decision-card.card-approved.selected { border-color:#27ae60; background:linear-gradient(135deg,#eafaf1 0%,#fff 100%); }
        .decision-card.card-needs_modification.selected { border-color:#f39c12; background:linear-gradient(135deg,#fef9e7 0%,#fff 100%); }
        .decision-card.card-rejected.selected { border-color:#e74c3c; background:linear-gradient(135deg,#fdedec 0%,#fff 100%); }
        .decision-icon { width:56px; height:56px; border-radius:50%; margin:0 auto 12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:white; }
        .card-approved .decision-icon { background:linear-gradient(135deg,#27ae60,#2ecc71); }
        .card-needs_modification .decision-icon { background:linear-gradient(135deg,#f39c12,#f1c40f); }
        .card-rejected .decision-icon { background:linear-gradient(135deg,#e74c3c,#ec7063); }
        .decision-label { font-weight:800; font-size:0.95rem; color:var(--text-main); margin-bottom:4px; }
        .decision-desc { font-size:0.8rem; color:var(--text-muted); font-weight:500; line-height:1.4; }
        .check-indicator { position:absolute; top:10px; left:10px; width:24px; height:24px; border-radius:50%; border:2px solid var(--border-light); display:flex; align-items:center; justify-content:center; transition:all 0.3s; font-size:0.7rem; color:white; }
        .selected .check-indicator { background:var(--accent-base); border-color:var(--accent-base); }

        /* Comments */
        .form-group { margin-bottom:18px; }
        .form-label { font-weight:800; color:var(--primary-base); display:block; margin-bottom:8px; font-size:0.85rem; }
        .form-label .required { color:#e74c3c; }
        .form-textarea { width:100%; min-height:140px; border:1.5px solid var(--border-light); border-radius:var(--radius-md); padding:14px; font-family:inherit; font-size:0.95rem; font-weight:600; color:var(--text-main); resize:vertical; transition:all var(--transition-smooth); background:#fff; box-sizing:border-box; }
        .form-textarea:focus { outline:none; border-color:var(--accent-base); box-shadow:0 0 0 3px rgba(26,188,156,0.12); }
        .form-hint { font-size:0.8rem; color:var(--text-muted); margin-top:6px; padding:8px 12px; background:var(--primary-light); border-right:3px solid var(--accent-base); border-radius:4px; font-weight:500; }

        .action-area { display:flex; gap:12px; justify-content:flex-end; flex-wrap:wrap; }
        .btn-primary { background:var(--accent-base); color:white; border:none; padding:14px 28px; border-radius:var(--radius-md); cursor:pointer; font-family:inherit; font-weight:800; font-size:1rem; transition:all var(--transition-smooth); box-shadow:var(--shadow-md); display:inline-flex; align-items:center; gap:8px; }
        .btn-primary:hover { background:var(--accent-dark); transform:translateY(-2px); box-shadow:var(--shadow-lg); }
        .btn-primary:disabled { opacity:0.5; cursor:not-allowed; transform:none; }
        .btn-secondary { background:var(--primary-light); color:var(--primary-base); border:2px solid var(--primary-base); padding:14px 28px; border-radius:var(--radius-md); font-family:inherit; font-weight:800; font-size:1rem; transition:all var(--transition-smooth); display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-secondary:hover { background:var(--primary-base); color:white; transform:translateY(-2px); }

        .alert-error { background:linear-gradient(135deg,#fdedec 0%,#fef5f4 100%); color:#922b21; padding:14px 20px; border-radius:var(--radius-md); border:1px solid #f5b7b1; display:flex; align-items:center; gap:10px; font-weight:700; margin-bottom:20px; }

        @media(max-width:992px) { .content{margin-right:0;padding:24px 14px;} .decisions-grid{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-gavel"></i>
            <?= $isUpdate ? 'تعديل قرار المراجعة' : 'إضافة قرار المراجعة' ?>
        </h2>
        <p class="page-subtitle">اختر قرارك بشأن هذا البحث وأضف تعليقاتك الفنية</p>

        <?php if ($error_msg): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars(urldecode($error_msg)) ?></div>
        <?php endif; ?>

        <!-- Brief App Info -->
        <div class="app-brief">
            <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
            <span class="app-brief-title"><?= htmlspecialchars($app['title']) ?></span>
        </div>

        <form id="decisionForm" action="process_decision.php" method="POST">
            <input type="hidden" name="application_id" value="<?= $application_id ?>">

            <!-- Decision Selection -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-scale-balanced"></i><h3>اختر قرارك</h3></div>
                <div class="decisions-grid">
                    <label class="decision-card card-approved <?= $currentDecision === 'approved' ? 'selected' : '' ?>">
                        <input type="radio" name="decision" value="approved" <?= $currentDecision === 'approved' ? 'checked' : '' ?> required>
                        <span class="check-indicator"><i class="fa-solid fa-check"></i></span>
                        <div class="decision-icon"><i class="fa-solid fa-check-double"></i></div>
                        <div class="decision-label">موافق</div>
                        <div class="decision-desc">البحث يستوفي جميع الشروط الأخلاقية والعلمية</div>
                    </label>
                    <label class="decision-card card-needs_modification <?= $currentDecision === 'needs_modification' ? 'selected' : '' ?>">
                        <input type="radio" name="decision" value="needs_modification" <?= $currentDecision === 'needs_modification' ? 'checked' : '' ?>>
                        <span class="check-indicator"><i class="fa-solid fa-check"></i></span>
                        <div class="decision-icon"><i class="fa-solid fa-pen-ruler"></i></div>
                        <div class="decision-label">يحتاج تعديل</div>
                        <div class="decision-desc">يتطلب تعديلات أو إضافات قبل الموافقة</div>
                    </label>
                    <label class="decision-card card-rejected <?= $currentDecision === 'rejected' ? 'selected' : '' ?>">
                        <input type="radio" name="decision" value="rejected" <?= $currentDecision === 'rejected' ? 'checked' : '' ?>>
                        <span class="check-indicator"><i class="fa-solid fa-check"></i></span>
                        <div class="decision-icon"><i class="fa-solid fa-xmark"></i></div>
                        <div class="decision-label">مرفوض</div>
                        <div class="decision-desc">البحث لا يستوفي الشروط المطلوبة</div>
                    </label>
                </div>
            </div>

            <!-- Comments -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-comment-dots"></i><h3>التعليقات والملاحظات الفنية</h3></div>
                <div class="form-group">
                    <label class="form-label" for="comments">تعليقاتك <span class="required" id="requiredStar" style="display:none;">*</span></label>
                    <textarea name="comments" id="comments" class="form-textarea" placeholder="أضف ملاحظاتك وتعليقاتك الفنية هنا..."></textarea>
                    <div class="form-hint" id="commentsHint">التعليقات إلزامية في حالة الرفض أو طلب التعديل</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="action-area">
                    <a href="review_form.php?application_id=<?= $application_id ?>" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> تراجع</a>
                    <button type="button" id="submitBtn" class="btn-primary" disabled><i class="fa-solid fa-paper-plane"></i> إرسال القرار</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    (function() {
        const cards = document.querySelectorAll('.decision-card');
        const radios = document.querySelectorAll('input[name="decision"]');
        const submitBtn = document.getElementById('submitBtn');
        const commentsField = document.getElementById('comments');
        const requiredStar = document.getElementById('requiredStar');
        const form = document.getElementById('decisionForm');

        function updateUI() {
            const selected = document.querySelector('input[name="decision"]:checked');
            cards.forEach(c => c.classList.remove('selected'));
            if (selected) {
                selected.closest('.decision-card').classList.add('selected');
                submitBtn.disabled = false;
                requiredStar.style.display = (selected.value !== 'approved') ? 'inline' : 'none';
            } else {
                submitBtn.disabled = true;
            }
        }

        cards.forEach(card => {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                updateUI();
            });
        });

        updateUI();

        const decisionLabels = {
            'approved': 'الموافقة على',
            'needs_modification': 'طلب تعديل',
            'rejected': 'رفض'
        };
        const decisionColors = {
            'approved': '#27ae60',
            'needs_modification': '#f39c12',
            'rejected': '#e74c3c'
        };
        const decisionIcons = {
            'approved': 'success',
            'needs_modification': 'warning',
            'rejected': 'error'
        };

        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = document.querySelector('input[name="decision"]:checked');
            if (!selected) return;

            const decision = selected.value;
            const comments = commentsField.value.trim();

            if (decision !== 'approved' && comments === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'تعليقات مطلوبة',
                    text: 'يجب إضافة تعليقات وملاحظات فنية عند الرفض أو طلب التعديل',
                    confirmButtonText: 'حسناً',
                    confirmButtonColor: 'var(--primary-base)',
                });
                return;
            }

            Swal.fire({
                title: 'تأكيد القرار',
                html: `<p style="font-size:1.05rem;font-weight:600;">هل أنت متأكد من <strong style="color:${decisionColors[decision]}">${decisionLabels[decision]}</strong> هذا البحث؟</p>`,
                icon: decisionIcons[decision],
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-check"></i> نعم، تأكيد',
                cancelButtonText: '<i class="fa-solid fa-xmark"></i> إلغاء',
                confirmButtonColor: decisionColors[decision],
                cancelButtonColor: '#95a5a6',
                reverseButtons: true,
                customClass: { popup: 'swal-rtl' }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    })();
    </script>
    <style>.swal-rtl { direction:rtl; font-family:'Cairo',sans-serif; }</style>
</body>
</html>
