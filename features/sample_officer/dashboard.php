<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['sample_officer']);

require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$db = new Database();
$conn = $db->getconn();
$user = Auth::user();
$officer_id = $user['id'];

// 1. Get Statistics
$statPending = 0;
$statCompleted = 0;

// Count Pending Applications
$resPending = $conn->query("SELECT COUNT(*) as cnt FROM applications WHERE current_stage = 'awaiting_sample_calc'");
if ($resPending) {
    $statPending = $resPending->fetch_assoc()['cnt'];
}

// Count Applications completed by THIS specific officer
$stmtComp = $conn->prepare("SELECT COUNT(*) as cnt FROM sample_sizes WHERE sampler_id = ?");
$stmtComp->bind_param("i", $officer_id);
$stmtComp->execute();
$resComp = $stmtComp->get_result();
if ($resComp) {
    $statCompleted = $resComp->fetch_assoc()['cnt'];
}
$stmtComp->close();

// 2. Fetch Active Queue
$query = "SELECT id, serial_number, title, created_at, current_stage 
          FROM applications 
          WHERE current_stage = 'awaiting_sample_calc' 
          ORDER BY created_at ASC";
$result = $conn->query($query);
$applications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إحصائيات وطابور العينات</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">
    <style>
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

        .content>* {
            width: 100%;
            max-width: 1120px;
        }

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
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        /* Stats Cards CSS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition-smooth);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-base);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }

        .stat-icon.pending {
            background: var(--status-pending-bg);
            color: var(--status-pending-text);
        }

        .stat-icon.completed {
            background: var(--status-approved-bg);
            color: var(--status-approved-text);
        }

        .stat-icon.total {
            background: var(--primary-light);
            color: var(--primary-base);
        }

        .stat-info h4 {
            margin: 0 0 5px;
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 700;
        }

        .stat-info span {
            display: block;
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
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
            padding: 9px 38px 9px 12px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent-base);
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.12);
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
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
        }

        .results-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 0 10px;
            color: var(--text-muted);
            font-size: 0.85rem;
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
            width: 100%;
            max-width: 1120px;
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: var(--radius-lg);
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
        }

        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
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
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-action:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-weight: 700;
        }

        .date-cell small {
            color: var(--text-muted);
            font-weight: 600;
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

        @media(max-width:992px) {
            .content {
                margin-right: 0;
            }

            .toolbar-card {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-chart-bar"></i> إحصائيات وطابور العينات</h2>
        <p class="page-subtitle">نظرة عامة على أدائك والملفات بانتظار التقييم الفني</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div
                style="width:100%; background:var(--status-approved-bg); color:var(--status-approved-text); padding:12px; border-radius:var(--radius-md); margin-bottom:20px; font-weight:700;">
                <i class="fa-solid fa-check-circle"></i> <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <h4>ملفات بانتظار الحساب</h4>
                    <span><?= number_format($statPending) ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed"><i class="fa-solid fa-check-double"></i></div>
                <div class="stat-info">
                    <h4>عينات قمت بحسابها بنجاح</h4>
                    <span><?= number_format($statCompleted) ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total"><i class="fa-solid fa-file-invoice"></i></div>
                <div class="stat-info">
                    <h4>إجمالي العينات المنجزة للجنة</h4>
                    <span><?= number_format($statPending + $statCompleted) ?></span>
                </div>
            </div>
        </div>

        <div class="toolbar-card">
            <div class="toolbar-meta" style="grid-column:1/-1;">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث في الطابور الحالي</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة
                    ضبط</button>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="searchApplications"><i class="fa-solid fa-magnifying-glass"></i> ابحث
                    برقم الملف أو العنوان</label>
                <input type="text" id="searchApplications" class="search-input" placeholder="اكتب للبحث...">
            </div>
            <select id="statusFilter" style="display:none;">
                <option value="all">الكل</option>
            </select>
        </div>

        <div class="results-bar">
            <div class="results-chip"><i class="fa-solid fa-list"></i> <span>عدد الملفات المعلقة: <strong
                        id="resultsCount"><?= count($applications) ?></strong></span></div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="15%">رقم الملف</th>
                            <th width="40%">عنوان البحث</th>
                            <th width="20%">تاريخ وتوقيت التقديم</th>
                            <th width="10%" style="display:none;" id="dateSortHeader"><span id="dateSortIcon"></span>
                            </th>
                            <th width="15%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsTableBody">
                        <?php if (empty($applications)): ?>
                            <tr>
                                <td colspan="4">
                                    <div style="text-align:center; padding:40px; color:var(--text-muted);"><i
                                            class="fa-solid fa-check-double"
                                            style="font-size:3rem; opacity:0.5; margin-bottom:10px;"></i>
                                        <p style="font-weight:700;font-size:1.1rem;">أنت بطل! جميع العينات تم حسابها بنجاح.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applications as $app):
                                $searchBlob = strtolower($app['serial_number'] . ' ' . $app['title']);
                                ?>
                                <tr data-search="<?= htmlspecialchars($searchBlob) ?>" data-status="all"
                                    data-date="<?= htmlspecialchars($app['created_at']) ?>">
                                    <td><span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span></td>
                                    <td style="font-weight:700;"><?= htmlspecialchars($app['title']) ?></td>
                                    <td>
                                        <div class="date-cell">
                                            <span><?= htmlspecialchars(irb_format_arabic_date($app['created_at'])) ?></span>
                                            <small><i class="fa-regular fa-clock"></i>
                                                <?= htmlspecialchars(irb_format_arabic_time($app['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td><a href="process_application.php?id=<?= $app['id'] ?>" class="btn-action"><i
                                                class="fa-solid fa-calculator"></i> معالجة الملف</a></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results">
                                <td colspan="4"><i class="fa-solid fa-filter-circle-xmark" style="font-size:2rem;"></i>
                                    <p style="font-weight:800;">لا توجد نتائج مطابقة</p>
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
                searchInputId: 'searchApplications', statusFilterId: 'statusFilter', resetButtonId: 'resetFilters',
                sortHeaderId: 'dateSortHeader', sortIconId: 'dateSortIcon',
                resultsCountId: 'resultsCount', tableBodyId: 'applicationsTableBody', noResultsRowId: 'noResultsRow',
                paginationContainerId: 'applicationsPagination', pageSize: 10, defaultSort: 'asc',
            });
        })();
    </script>
</body>

</html>