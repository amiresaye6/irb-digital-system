<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('reviewer');
require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$reviewsObj  = new Reviews();
$reviewer_id = $_SESSION['user_id'];
$assignments = $reviewsObj->getPendingAssignments($reviewer_id);

$success = $_SESSION['assignment_success'] ?? '';
$error   = $_SESSION['assignment_error']   ?? '';
unset($_SESSION['assignment_success'], $_SESSION['assignment_error']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات الإسناد المعلقة | IRB</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 20px 24px; background: var(--bg-page); display: flex; flex-direction: column; align-items: center; }
        .content > * { width: 100%; max-width: 1120px; }
        .page-title { color: var(--primary-base); margin-bottom: 8px; font-weight: 800; display: flex; align-items: center; gap: 12px; font-size: 1.6rem; }
        .page-title i { color: #f59e0b; }
        .page-subtitle { color: var(--text-muted); margin-bottom: 12px; font-weight: 500; font-size: 0.9rem; line-height: 1.5; }
        .flash { padding: 14px 20px; border-radius: var(--radius-md); font-weight: 700; font-size: 0.9rem; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .flash-success { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
        .flash-error   { background: #fee2e2; color: #991b1b; border: 1.5px solid #fca5a5; }

        /* Toolbar */
        .toolbar-card { background: linear-gradient(180deg, rgba(44,62,80,0.04) 0%, rgba(255,255,255,0.92) 100%); border: 1px solid rgba(189,195,199,0.6); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 10px 14px; margin-bottom: 10px; display: grid; grid-template-columns: minmax(0,1.7fr) minmax(190px,0.6fr); gap: 10px 12px; align-items: end; }
        .toolbar-meta { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px; flex-wrap: wrap; grid-column: 1 / -1; }
        .toolbar-title { display: flex; align-items: center; gap: 10px; color: var(--primary-base); font-weight: 800; font-size: 0.95rem; margin: 0; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-label { font-size: 0.78rem; font-weight: 800; color: var(--primary-base); display: flex; align-items: center; gap: 6px; }
        .search-input { width: 100%; border: 1.5px solid rgba(189,195,199,0.9); border-radius: 10px; background: #fff; color: var(--text-main); font-family: inherit; font-size: 0.9rem; font-weight: 600; padding: 9px 38px 9px 12px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; transition: all var(--transition-smooth); }
        .search-input:focus { outline: none; border-color: var(--accent-base); box-shadow: 0 0 0 3px rgba(26,188,156,0.12); }
        .btn-reset { border: 1.5px solid var(--border-light); background: #fff; color: var(--primary-base); border-radius: 10px; padding: 9px 14px; font-family: inherit; font-weight: 700; cursor: pointer; transition: all var(--transition-smooth); display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; font-size: 0.88rem; }
        .btn-reset:hover { background: var(--primary-light); border-color: var(--primary-base); }
        .results-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 10px; color: var(--text-muted); font-size: 0.85rem; flex-wrap: wrap; }
        .results-chip { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: var(--primary-light); color: var(--primary-base); font-weight: 700; font-size: 0.85rem; }

        /* Table */
        .data-card { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border-light); margin-top: 10px; }
        .table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }
        .data-table { width: 100%; border-collapse: collapse; text-align: right; font-size: 0.95rem; }
        .data-table th { padding: 14px 12px; font-weight: 800; border-bottom: 2px solid var(--primary-base); color: white; background: var(--primary-base); font-size: 0.9rem; white-space: nowrap; }
        .data-table td { padding: 14px 12px; border-bottom: 1px solid var(--border-light); vertical-align: middle; }
        .data-table tbody tr:hover { background: var(--primary-light); }
        .sortable-header { cursor: pointer; user-select: none; }
        .sortable-button { background: none; border: none; color: white; font-family: inherit; font-weight: 800; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 6px; padding: 0; }
        .sort-direction { font-size: 0.8rem; }
        .row-hidden { display: none; }
        .no-results { display: none; text-align: center; padding: 34px 20px; color: var(--text-muted); }
        .no-results i { font-size: 2.4rem; color: var(--border-dark); margin-bottom: 10px; opacity: 0.6; }

        /* Badges */
        .badge-serial { display: inline-block; background: var(--primary-light); color: var(--primary-base); font-weight: 800; font-size: 0.82rem; padding: 5px 12px; border-radius: var(--radius-sm); white-space: nowrap; }
        .app-title { font-weight: 800; color: var(--text-main); font-size: 0.95rem; margin-bottom: 4px; line-height: 1.4; }
        .app-meta { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 3px; }
        .app-meta i { color: var(--accent-base); }
        .redacted-badge { display: inline-flex; align-items: center; gap: 5px; background: #1e293b; color: #94a3b8; font-size: 0.78rem; font-weight: 700; padding: 3px 10px; border-radius: 6px; }
        .new-badge { display: inline-flex; align-items: center; gap: 4px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a; font-size: 0.72rem; font-weight: 800; padding: 2px 8px; border-radius: 999px; margin-top: 4px; }
        .date-cell { display: flex; flex-direction: column; gap: 3px; }
        .date-main { font-weight: 700; color: var(--text-main); font-size: 0.9rem; }
        .date-time { font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }
        .status-awaiting { display: inline-flex; align-items: center; gap: 5px; background: #fef3c7; color: #d97706; border: 1px solid #fde68a; font-size: 0.8rem; font-weight: 800; padding: 5px 12px; border-radius: 999px; }

        /* Action buttons */
        .btn-accept { background: linear-gradient(135deg,#10b981,#059669); color: white; border: none; border-radius: 10px; padding: 8px 14px; font-family: inherit; font-weight: 800; font-size: 0.83rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-accept:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .btn-refuse { background: #fff; color: #dc2626; border: 1.5px solid #fca5a5; border-radius: 10px; padding: 7px 14px; font-family: inherit; font-weight: 800; font-size: 0.83rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-refuse:hover { background: #fee2e2; }
        .actions-wrap { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,0.55); z-index: 9000; align-items: center; justify-content: center; backdrop-filter: blur(3px); }
        .modal-overlay.open { display: flex; }
        .modal-box { background: #fff; border-radius: 20px; padding: 32px; max-width: 480px; width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,0.25); animation: modalIn 0.25s ease; position: relative; }
        @keyframes modalIn { from { opacity:0; transform:scale(0.94) translateY(-10px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .modal-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.6rem; }
        .modal-icon.confirm { background: #d1fae5; color: #059669; }
        .modal-icon.refuse  { background: #fee2e2; color: #dc2626; }
        .modal-title { font-size: 1.15rem; font-weight: 800; color: var(--text-main); text-align: center; margin-bottom: 6px; }
        .modal-sub { font-size: 0.88rem; color: var(--text-muted); text-align: center; margin-bottom: 20px; }
        .modal-label { font-size: 0.82rem; font-weight: 800; color: #dc2626; margin-bottom: 6px; display: block; }
        .modal-textarea { width: 100%; border: 1.5px solid #fca5a5; border-radius: 10px; padding: 10px 14px; font-family: inherit; font-size: 0.88rem; font-weight: 600; color: var(--text-main); resize: vertical; min-height: 90px; outline: none; background: #fff9f9; transition: border-color 0.2s; box-sizing: border-box; }
        .modal-textarea:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1); }
        .modal-actions { display: flex; gap: 10px; margin-top: 18px; }
        .modal-btn-confirm { flex: 1; background: linear-gradient(135deg,#10b981,#059669); color: white; border: none; border-radius: 10px; padding: 11px; font-family: inherit; font-weight: 800; font-size: 0.9rem; cursor: pointer; }
        .modal-btn-refuse  { flex: 1; background: #dc2626; color: white; border: none; border-radius: 10px; padding: 11px; font-family: inherit; font-weight: 800; font-size: 0.9rem; cursor: pointer; }
        .modal-btn-cancel  { flex: 0 0 auto; background: #f1f5f9; color: var(--text-main); border: none; border-radius: 10px; padding: 11px 18px; font-family: inherit; font-weight: 700; font-size: 0.88rem; cursor: pointer; }

        /* Empty state */
        .empty-state { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 16px; display: block; }
        .empty-state h3 { font-weight: 800; font-size: 1.1rem; color: var(--text-main); margin-bottom: 8px; }

        @media (max-width: 992px) { .content { margin-right: 0; padding: 16px 14px; } .toolbar-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Accept Modal -->
    <div class="modal-overlay" id="acceptModal">
        <div class="modal-box">
            <div class="modal-icon confirm"><i class="fa-solid fa-check"></i></div>
            <div class="modal-title">تأكيد قبول الإسناد</div>
            <div class="modal-sub">هل تؤكد قبول مراجعة هذا البحث؟ سيضاف إلى قائمة أعمالك النشطة.</div>
            <form method="POST" action="handle_assignment.php">
                <input type="hidden" name="review_id" id="acceptReviewId">
                <input type="hidden" name="action" value="accept">
                <div class="modal-actions">
                    <button type="button" class="modal-btn-cancel" onclick="closeModal('acceptModal')">إلغاء</button>
                    <button type="submit" class="modal-btn-confirm"><i class="fa-solid fa-check"></i> نعم، أقبل الإسناد</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Refuse Modal -->
    <div class="modal-overlay" id="refuseModal">
        <div class="modal-box">
            <div class="modal-icon refuse"><i class="fa-solid fa-xmark"></i></div>
            <div class="modal-title">الاعتذار عن المراجعة</div>
            <div class="modal-sub">يرجى ذكر سبب اعتذارك. سيُحفظ هذا للسجل الإداري.</div>
            <form method="POST" action="handle_assignment.php">
                <input type="hidden" name="review_id" id="refuseReviewId">
                <input type="hidden" name="action" value="refuse">
                <label class="modal-label" for="refusalReasonInput"><i class="fa-solid fa-comment-dots"></i> سبب الاعتذار (مطلوب)</label>
                <textarea class="modal-textarea" id="refusalReasonInput" name="refusal_reason" placeholder="تضارب مصالح، عبء عمل زائد، خارج التخصص..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="modal-btn-cancel" onclick="closeModal('refuseModal')">إلغاء</button>
                    <button type="submit" class="modal-btn-refuse"><i class="fa-solid fa-paper-plane"></i> إرسال الاعتذار</button>
                </div>
            </form>
        </div>
    </div>

    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-inbox"></i>
            طلبات الإسناد المعلقة
        </h2>
        <p class="page-subtitle">
            هذه الطلبات تنتظر ردك — اقبل لإضافة البحث لقائمة أعمالك، أو اعتذر مع ذكر السبب.
        </p>

        <?php if ($success): ?>
        <div class="flash flash-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="flash flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="toolbar-card">
            <div class="toolbar-meta">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث والتصفية</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة ضبط</button>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="searchInput"><i class="fa-solid fa-magnifying-glass"></i> البحث السريع</label>
                <input type="text" id="searchInput" class="search-input" placeholder="ابحث برقم الملف أو عنوان البحث أو القسم...">
            </div>
        </div>


        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="12%">رقم الملف</th>
                            <th width="35%">بيانات البحث</th>
                            <th width="13%">الحالة</th>
                            <th width="16%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button" aria-label="ترتيب حسب تاريخ الإسناد">
                                    <span>تاريخ الإسناد</span>
                                    <span class="sort-direction" id="dateSortIcon"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
                                </button>
                            </th>
                            <th width="24%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="assignmentsTableBody">
                        <?php if (empty($assignments)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <h3>لا توجد طلبات معلقة</h3>
                                    <p>جميع طلبات الإسناد الموجهة إليك تمت معالجتها.</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($assignments as $idx => $asgn):
                            $isRedacted   = ($asgn['principal_investigator'] === "معلومات محجوبة");
                            $assignedDate = irb_format_arabic_date($asgn['assigned_at']);
                            $assignedTime = irb_format_arabic_time($asgn['assigned_at']);
                            $searchBlob   = strtolower($asgn['serial_number'] . ' ' . $asgn['title'] . ' ' . ($asgn['department'] ?? '') . ' ' . $assignedDate);
                        ?>
                        <tr data-search="<?= htmlspecialchars($searchBlob) ?>" data-date="<?= htmlspecialchars($asgn['assigned_at']) ?>">
                            <td>
                                <span class="badge-serial"><?= htmlspecialchars($asgn['serial_number']) ?></span>
                                <?php if ($idx === 0): ?><div><span class="new-badge"><i class="fa-solid fa-star"></i> جديد</span></div><?php endif; ?>
                            </td>
                            <td>
                                <div class="app-title"><?= htmlspecialchars($asgn['title']) ?></div>
                                <div class="app-meta">
                                    <?php if ($isRedacted): ?>
                                        <span class="redacted-badge"><i class="fa-solid fa-user-secret"></i> هوية محجوبة</span>
                                    <?php else: ?>
                                        <i class="fa-solid fa-user-doctor"></i> <?= htmlspecialchars($asgn['principal_investigator']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($asgn['department'])): ?>
                                        <span>|</span><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($asgn['department']) ?>
                                    <?php endif; ?>
                                </div>
                                
                            </td>
                            <td><span class="status-awaiting"><i class="fa-solid fa-clock"></i> بانتظار الرد</span></td>
                            <td>
                                <div class="date-cell">
                                    <span class="date-main"><?= htmlspecialchars($assignedDate) ?></span>
                                    <span class="date-time"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($assignedTime) ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="actions-wrap">
                                    <button type="button" class="btn-accept" onclick="openAccept(<?= $asgn['review_id'] ?>)">
                                        <i class="fa-solid fa-check"></i> قبول
                                    </button>
                                    <button type="button" class="btn-refuse" onclick="openRefuse(<?= $asgn['review_id'] ?>)">
                                        <i class="fa-solid fa-xmark"></i> اعتذار
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr id="noResultsRow" class="no-results">
                            <td colspan="5"><i class="fa-solid fa-filter-circle-xmark"></i><p style="font-weight:800;margin:6px 0 0;">لا توجد نتائج مطابقة</p></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('assignmentsPagination'); ?>
        </div>
    </div>

    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
    // Init table tools (search + sort + pagination)
    (function () {
        window.IRBTableTools.init({
            searchInputId:        'searchInput',
            resetButtonId:        'resetFilters',
            sortHeaderId:         'dateSortHeader',
            sortIconId:           'dateSortIcon',
            resultsCountId:       'resultsCount',
            tableBodyId:          'assignmentsTableBody',
            noResultsRowId:       'noResultsRow',
            paginationContainerId:'assignmentsPagination',
            pageSize: 10,
            defaultSort: 'desc',
        });
    })();

    // Modal helpers
    function openAccept(reviewId) {
        document.getElementById('acceptReviewId').value = reviewId;
        document.getElementById('acceptModal').classList.add('open');
    }
    function openRefuse(reviewId) {
        document.getElementById('refuseReviewId').value = reviewId;
        document.getElementById('refusalReasonInput').value = '';
        document.getElementById('refuseModal').classList.add('open');
        setTimeout(() => document.getElementById('refusalReasonInput').focus(), 100);
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('open');
    }
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
    });
    // Close on Escape
    document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open')); });
    </script>
</body>
</html>
