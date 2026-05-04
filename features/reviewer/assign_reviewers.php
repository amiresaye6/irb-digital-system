<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('manager');

require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$reviewsObj = new Reviews();

// The class now returns applications ordered by created_at ASC (Oldest First)
$applications = $reviewsObj->getApplicationsUnderReview();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين المراجعين</title>
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

        .toolbar-hint {
            color: var(--text-muted);
            font-size: 0.8rem;
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

        .toolbar-actions {
            display: flex;
            align-items: stretch;
            gap: 10px;
            flex-wrap: wrap;
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

        .alert-success {
            background: var(--status-approved-bg);
            color: var(--status-approved-text);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
            border-right: 4px solid var(--success-base);
            font-size: 0.95rem;
        }

        .alert-danger {
            background: var(--status-rejected-bg);
            color: var(--status-rejected-text);
            padding: 12px 16px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-md);
            border-right: 4px solid var(--alert-base);
            font-size: 0.95rem;
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
            color: var(--text-muted);
            background: #f0f2f5;
        }

        .status-badge.awaiting_acceptance {
            color: #856404;
            background: #fff3cd;
        }

        .status-badge.accepted {
            color: var(--status-approved-text);
            background: var(--status-approved-bg);
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        .review-count {
            background: white;
            color: var(--status-approved-text);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 0.8rem;
            min-width: 20px;
            text-align: center;
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
            الأبحاث الجاهزة للتحكيم
        </h2>
        <p class="page-subtitle">
            من هنا يمكنك تعيين مراجع متخصص لكل بحث ومتابعة حالة قبول الإسناد. الأبحاث مرتبة حسب الأقدمية لضمان العدالة في سرعة الرد.
        </p>

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
                <input type="text" id="searchApplications" class="search-input" placeholder="ابحث برقم الملف أو عنوان البحث أو اسم الباحث أو القسم...">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="statusFilter">
                    <i class="fa-solid fa-filter"></i>
                    حالة الإسناد
                </label>
                <select id="statusFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="pending">لم يتم التعيين</option>
                    <option value="awaiting_acceptance">بانتظار الموافقة</option>
                    <option value="accepted">تم القبول</option>
                </select>
            </div>

        </div>

        <div class="results-bar">
            <div class="results-chip">
                <i class="fa-solid fa-list"></i>
                <span>عدد النتائج: <strong id="resultsCount"><?= count($applications) ?></strong></span>
            </div>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>تم تعيين المراجع بنجاح وبانتظار قبوله!</span>
            </div>
        <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
            <div class="alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>خطأ: يوجد إسناد سارٍ لهذا البحث أو حدثت مشكلة تقنية.</span>
            </div>
        <?php endif; ?>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="12%">رقم الملف</th>
                            <th width="37%">بيانات البحث</th>
                            <th width="15%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button" aria-label="ترتيب حسب تاريخ التقديم">
                                    <span>تاريخ التقديم</span>
                                    <span class="sort-direction" id="dateSortIcon"><i class="fa-solid fa-arrow-up-wide-short"></i></span>
                                </button>
                            </th>
                            <th width="16%">حالة الإسناد</th>
                            <th width="20%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsTableBody">
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <p style="font-weight: 700; font-size: 1.05rem;">لا توجد أبحاث قيد المراجعة</p>
                                        <p style="font-size: 0.9rem;">جميع الأبحاث إما قيد الانتظار أو تم الانتهاء من مراجعتها</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app):
                                // Fetch the single active assignment logic
                                $active = $reviewsObj->getActiveAssignment($app['id']);
                                $statusKey = $active ? $active['assignment_status'] : 'pending';
                                
                                $searchBlob = strtolower(trim($app['serial_number'] . ' ' . $app['title'] . ' ' . $app['principal_investigator'] . ' ' . ($app['department'] ?? '') . ' ' . irb_format_arabic_date($app['created_at'])));
                                $applicationDate = irb_format_arabic_date($app['created_at']);
                                $applicationTime = irb_format_arabic_time($app['created_at']);
                            ?>
                                <tr
                                    data-search="<?= htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8') ?>"
                                    data-status="<?= $statusKey ?>"
                                    data-date="<?= htmlspecialchars($app['created_at']) ?>"
                                >
                                    <td>
                                        <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="app-title">
                                            <?= htmlspecialchars($app['title']) ?>
                                        </div>
                                        <div class="app-investigator">
                                            <i class="fa-solid fa-user-doctor"></i>
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
                                        </div>
                                    </td>
                                    <td>
                                        <?php if(!$active): ?>
                                            <span class="status-badge pending">
                                                <i class="fa-solid fa-hourglass-end"></i>
                                                لم يتم التعيين
                                            </span>
                                        <?php elseif($active['assignment_status'] == 'awaiting_acceptance'): ?>
                                            <span class="status-badge awaiting_acceptance">
                                                <i class="fa-solid fa-clock"></i>
                                                بانتظار: <?= htmlspecialchars($active['full_name']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge accepted">
                                                <i class="fa-solid fa-check-circle"></i>
                                                مقبول: <?= htmlspecialchars($active['full_name']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="assign_form.php?application_id=<?= $app['id'] ?>" class="btn-action">
                                            <i class="fa-solid <?= $active ? 'fa-eye' : 'fa-user-plus' ?>"></i>
                                            <?= $active ? 'متابعة' : 'إسناد' ?>
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
                defaultSort: 'asc',
            });
        })();
    </script>
</body>
</html>
