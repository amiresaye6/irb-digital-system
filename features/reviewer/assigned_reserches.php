<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('reviewer');

require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$reviewsObj = new Reviews();

$reviewer_id = $_SESSION['user_id']; 

$assignments = $reviewsObj->getReviewerAssignments($reviewer_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأبحاث المسندة للتحكيم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">
    <style>
        .page-title {
            color: var(--primary-base);
            margin-bottom: 8px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
        }

        .page-title i {
            color: var(--accent-base);
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-bottom: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .toolbar-card {
            background: linear-gradient(180deg, rgba(44, 62, 80, 0.04) 0%, rgba(255, 255, 255, 0.92) 100%);
            border: 1px solid rgba(189, 195, 199, 0.6);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 10px 14px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: minmax(0, 1.7fr) minmax(190px, 0.6fr);
            gap: 10px 12px;
            align-items: end;
        }

        .toolbar-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-base);
            font-weight: 800;
            font-size: 0.95rem;
            margin: 0;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-label {
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--primary-base);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .search-input {
            width: 100%;
            border: 1.5px solid rgba(189, 195, 199, 0.9);
            border-radius: 10px;
            background: #fff;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all var(--transition-smooth);
            padding: 9px 12px;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-base);
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.12);
        }

        .search-input {
            padding-right: 38px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .btn-reset {
            border: 1.5px solid var(--border-light);
            background: #fff;
            color: var(--primary-base);
            border-radius: 10px;
            padding: 9px 14px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            font-size: 0.88rem;
        }

        .btn-reset:hover {
            background: var(--primary-light);
            border-color: var(--primary-base);
            transform: translateY(-1px);
        }

        .results-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 0 10px;
            color: var(--text-muted);
            font-size: 0.85rem;
            flex-wrap: wrap;
        }

        .results-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--primary-light);
            color: var(--primary-base);
            font-weight: 700;
            font-size: 0.85rem;
        }

        .data-card {
            background: var(--bg-surface);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-top: 25px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 0.95rem;
        }

        .data-table th {
            padding: 14px 12px;
            font-weight: 800;
            border-bottom: 2px solid var(--primary-base);
            color: white;
            background: var(--primary-base);
            font-size: 0.9rem;
            text-align: right;
            white-space: nowrap;
        }

        .sortable-header {
            user-select: none;
        }

        .sortable-button {
            width: 100%;
            border: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            cursor: pointer;
            padding: 0;
        }

        .sortable-button i {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .sort-direction {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.15);
        }

        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .data-table tr:hover {
            background-color: var(--primary-light);
        }

        .badge-serial {
            font-weight: 800;
            color: white;
            background: var(--primary-base);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            display: inline-block;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .app-title {
            color: var(--text-main);
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .app-investigator {
            font-size: 0.88rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            line-height: 1.4;
            flex-wrap: wrap;
        }

        .app-investigator.redacted {
            color: #e74c3c;
            font-weight: bold;
        }

        .app-department {
            color: var(--primary-base);
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-badge.pending {
            color: var(--status-pending-text);
            background: var(--status-pending-bg);
        }

        .status-badge.approved {
            color: var(--status-approved-text);
            background: var(--status-approved-bg);
        }

        .status-badge.rejected {
            color: var(--status-rejected-text);
            background: var(--status-rejected-bg);
        }

        .status-badge.needs_modification {
            color: #b9770e;
            background: #fdf2e9;
        }

        .btn-action {
            background: var(--accent-base);
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            transition: all var(--transition-smooth);
            box-shadow: var(--shadow-sm);
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .btn-action:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-dark);
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 6px 0;
            line-height: 1.5;
        }

        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: var(--text-main);
            font-weight: 700;
        }

        .date-cell small {
            color: var(--text-muted);
            font-weight: 600;
        }

        .date-main {
            display: block;
            font-size: 0.95rem;
        }

        .date-time {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .row-hidden {
            display: none;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 34px 20px;
            color: var(--text-muted);
        }

        .no-results i {
            font-size: 2.4rem;
            color: var(--border-dark);
            margin-bottom: 10px;
            opacity: 0.6;
        }

        body {
            background: var(--bg-page);
        }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 20px 24px;
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content > * {
            width: 100%;
            max-width: 1120px;
        }

        .data-card {
            width: 100%;
            max-width: 1120px;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-lg);
        }

        @media (max-width: 992px) {
            .content {
                margin-right: 0;
                padding: 24px 14px;
            }

            .toolbar-card {
                grid-template-columns: 1fr;
            }

            .toolbar-meta {
                margin-bottom: 2px;
            }

            .btn-reset {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-list-check"></i>
            الأبحاث المسندة للتحكيم
        </h2>
        <p class="page-subtitle">
            قائمة بجميع الأبحاث التي <strong>قبلت</strong> مراجعتها وهي في قائمة عملك النشطة. يتم إخفاء بيانات الباحث لضمان حيادية التحكيم.
        </p>

        <?php
        // Show pending assignments notification
        $pendingCount = count($reviewsObj->getPendingAssignments($reviewer_id));
        if ($pendingCount > 0):
        ?>
        <div style="background: linear-gradient(135deg, #fff8e1, #fffde7); border: 1.5px solid #f59e0b; border-radius: var(--radius-lg); padding: 14px 20px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 12px; background: #fef3c7; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-bell" style="color: #d97706; font-size: 1.1rem;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; color: #92400e; font-size: 0.95rem;">لديك <?= $pendingCount ?> طلب إسناد بانتظار ردك</div>
                    <div style="font-size: 0.82rem; color: #b45309; margin-top: 2px;">يرجى مراجعة الطلبات المعلقة والرد بالقبول أو الاعتذار.</div>
                </div>
            </div>
            <a href="pending_assignments.php" style="background: #f59e0b; color: white; padding: 9px 18px; border-radius: 10px; text-decoration: none; font-weight: 800; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap;">
                <i class="fa-solid fa-inbox"></i> عرض الطلبات المعلقة
            </a>
        </div>
        <?php endif; ?>

        <div class="toolbar-card">
            <div class="toolbar-meta" style="grid-column: 1 / -1;">
                <div>
                    <h3 class="toolbar-title">
                        <i class="fa-solid fa-sliders"></i>
                        البحث والتصفية
                    </h3>
                </div>
                <button type="button" id="resetFilters" class="btn-reset">
                    <i class="fa-solid fa-rotate-left"></i>
                    إعادة ضبط
                </button>
            </div>

            <div class="filter-group">
                <label class="filter-label" for="searchApplications">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    البحث السريع
                </label>
                <input type="text" id="searchApplications" class="search-input" placeholder="ابحث برقم الملف أو عنوان البحث أو القسم...">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="statusFilter">
                    <i class="fa-solid fa-filter"></i>
                    حالة المراجعة
                </label>
                <select id="statusFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="pending">قيد المراجعة</option>
                    <option value="approved">مقبول</option>
                    <option value="rejected">مرفوض</option>
                    <option value="needs_modification">يحتاج تعديل</option>
                </select>
            </div>

        </div>

        <div class="results-bar">
            <div class="results-chip">
                <i class="fa-solid fa-list"></i>
                <span>عدد النتائج: <strong id="resultsCount"><?= count($assignments) ?></strong></span>
            </div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="12%">رقم الملف</th>
                            <th width="37%">بيانات البحث</th>
                            <th width="15%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button" aria-label="ترتيب حسب تاريخ التقديم">
                                    <span>تاريخ الطلب</span>
                                    <span class="sort-direction" id="dateSortIcon"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
                                </button>
                            </th>
                            <th width="16%">حالة المراجعة</th>
                            <th width="20%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsTableBody">
                        <?php if (empty($assignments)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <p style="font-weight: 700; font-size: 1.05rem;">لا توجد أبحاث مسندة إليك</p>
                                        <p style="font-size: 0.9rem;">سيتم إشعارك عند تعيين بحث جديد لمراجعته.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assignments as $app):
                                $searchBlob = strtolower(trim($app['serial_number'] . ' ' . $app['title'] . ' ' . $app['principal_investigator'] . ' ' . ($app['department'] ?? '') . ' ' . irb_format_arabic_date($app['created_at'])));
                                $applicationDate = irb_format_arabic_date($app['created_at']);
                                $applicationTime = irb_format_arabic_time($app['created_at']);
                                $isRedacted = ($app['principal_investigator'] === "معلومات محجوبة");
                            ?>
                                <tr
                                    data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= htmlspecialchars($app['decision']) ?>"
                                    data-date="<?= htmlspecialchars($app['created_at']) ?>"
                                >
                                    <td>
                                        <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="app-title">
                                            <?= htmlspecialchars($app['title']) ?>
                                        </div>
                                        <div class="app-investigator <?= $isRedacted ? 'redacted' : '' ?>">
                                            <?php if ($isRedacted): ?>
                                                <i class="fa-solid fa-user-secret"></i>
                                            <?php else: ?>
                                                <i class="fa-solid fa-user-doctor"></i>
                                            <?php endif; ?>
                                            <strong>الباحث:</strong>
                                            <?= htmlspecialchars($app['principal_investigator']) ?>
                                            <?php if (!empty($app['department'])): ?>
                                                <span class="app-department">| القسم: <?= htmlspecialchars($app['department']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span class="date-main"><?= htmlspecialchars($applicationDate) ?></span>
                                            <small class="date-time">
                                                <i class="fa-regular fa-clock"></i>
                                                <?= htmlspecialchars($applicationTime) ?>
                                            </small>
                                            
                                            <?php if (!empty($app['reviewed_at'])): ?>
                                                <small style="margin-top: 4px; color: var(--accent-base);">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    تعديل: <?= htmlspecialchars(irb_format_arabic_date($app['reviewed_at'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($app['decision'] === 'pending'): ?>
                                            <span class="status-badge pending">
                                                <i class="fa-solid fa-hourglass-half"></i> قيد المراجعة
                                            </span>
                                        <?php elseif($app['decision'] === 'approved'): ?>
                                            <span class="status-badge approved">
                                                <i class="fa-solid fa-check-double"></i> مقبول
                                            </span>
                                        <?php elseif($app['decision'] === 'needs_modification'): ?>
                                            <span class="status-badge needs_modification">
                                                <i class="fa-solid fa-pen"></i> يحتاج تعديل
                                            </span>
                                        <?php elseif($app['decision'] === 'rejected'): ?>
                                            <span class="status-badge rejected">
                                                <i class="fa-solid fa-xmark"></i> مرفوض
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge">
                                                <?= htmlspecialchars($app['decision']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="review_form.php?application_id=<?= $app['application_id'] ?>" class="btn-action">
                                            <i class="fa-regular fa-eye"></i>
                                            عرض ومراجعة
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results">
                                <td colspan="5">
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                    <p style="font-weight: 800; font-size: 1.05rem; margin: 0 0 6px;">لا توجد نتائج مطابقة</p>
                                    <p style="margin: 0;">جرّب تغيير كلمات البحث أو إعدادات التصفية.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('applicationsPagination'); ?>
        </div>
    </div>

    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
        (function () {
            window.IRBTableTools.init({
                searchInputId: 'searchApplications',
                statusFilterId: 'statusFilter',
                resetButtonId: 'resetFilters',
                sortHeaderId: 'dateSortHeader',
                sortIconId: 'dateSortIcon',
                resultsCountId: 'resultsCount',
                tableBodyId: 'applicationsTableBody',
                noResultsRowId: 'noResultsRow',
                paginationContainerId: 'applicationsPagination',
                pageSize: 10,
                defaultSort: 'desc',
            });
        })();
    </script>
</body>
</html>
