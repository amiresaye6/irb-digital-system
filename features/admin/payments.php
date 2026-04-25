<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

Auth::checkRole(['admin', 'manager']);

$db = new Database();

// Join payments with applications and users to get the full picture
$sql = "
    SELECT 
        p.*, 
        a.serial_number, 
        a.title,
        u.full_name AS student_name
    FROM payments p
    JOIN applications a ON p.application_id = a.id
    JOIN users u ON a.student_id = u.id
    ORDER BY p.created_at DESC
";

$allPayments = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);

// Calculate Quick Stats for the Dashboard
$totalRevenue = 0;
$completedCount = 0;
$pendingCount = 0;
$failedCount = 0;

foreach ($allPayments as $pay) {
    if ($pay['status'] === 'completed') {
        $totalRevenue += $pay['amount'];
        $completedCount++;
    } elseif ($pay['status'] === 'pending') {
        $pendingCount++;
    } elseif ($pay['status'] === 'failed') {
        $failedCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المدفوعات | IRB System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">
    
    <style>
        body { background:var(--bg-page); }
        .content { margin-right:260px; min-height:100vh; padding:20px 24px; background:var(--bg-page); display:flex; flex-direction:column; align-items:center; }
        .content > * { width:100%; max-width:1120px; }
        .page-title { color:var(--primary-base); margin-bottom:8px; font-weight:800; display:flex; align-items:center; gap:12px; font-size:1.6rem; }
        .page-title i { color:var(--accent-base); }
        .page-subtitle { color:var(--text-muted); margin-bottom:25px; font-weight:500; font-size:0.9rem; line-height:1.5; }

        /* Modern Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); display: flex; align-items: center; gap: 18px; transition: all var(--transition-smooth); }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--accent-base); }
        .stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
        .icon-revenue { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .icon-pending { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .icon-failed { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .stat-info h4 { margin: 0 0 6px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 700; }
        .stat-info .value { margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); line-height: 1.2; }

        /* Toolbar & Table Master Styling */
        .toolbar-card { background:linear-gradient(180deg,rgba(44,62,80,0.04) 0%,rgba(255,255,255,0.92) 100%); border:1px solid rgba(189,195,199,0.6); border-radius:var(--radius-lg); box-shadow:var(--shadow-sm); padding:10px 14px; margin-bottom:10px; display:grid; grid-template-columns:minmax(0,1.7fr) minmax(190px,0.6fr); gap:10px 12px; align-items:end; }
        .toolbar-meta { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:4px; flex-wrap:wrap; }
        .toolbar-title { display:flex; align-items:center; gap:10px; color:var(--primary-base); font-weight:800; font-size:0.95rem; margin:0; }
        .filter-group { display:flex; flex-direction:column; gap:5px; }
        .filter-label { font-size:0.78rem; font-weight:800; color:var(--primary-base); display:flex; align-items:center; gap:6px; }
        .search-input { width:100%; border:1.5px solid rgba(189,195,199,0.9); border-radius:10px; background:#fff; color:var(--text-main); font-family:inherit; font-size:0.9rem; font-weight:600; padding:9px 38px 9px 12px; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; }
        .search-input:focus { outline:none; border-color:var(--accent-base); box-shadow:0 0 0 3px rgba(26,188,156,0.12); }
        .btn-reset { border:1.5px solid var(--border-light); background:#fff; color:var(--primary-base); border-radius:10px; padding:9px 14px; font-family:inherit; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:8px; font-size:0.88rem; transition:all var(--transition-smooth); }
        .btn-reset:hover { background:var(--primary-light); border-color:var(--primary-base); transform:translateY(-1px); }

        .sortable-header { user-select:none; }
        .sortable-button { width:100%;border:0;background:transparent;color:inherit;font:inherit;font-weight:800;display:inline-flex;align-items:center;justify-content:space-between;gap:10px;cursor:pointer;padding:0; }
        .sort-direction { display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:999px;background:rgba(255,255,255,0.15); }

        .results-bar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin:0 0 10px; color:var(--text-muted); font-size:0.85rem; flex-wrap:wrap; }
        .results-chip { display:inline-flex; align-items:center; gap:8px; padding:7px 11px; border-radius:999px; background:var(--primary-light); color:var(--primary-base); font-weight:700; font-size:0.85rem; }

        .data-card { background:var(--bg-surface); padding:25px; border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border-light); width:100%; max-width:1120px; }
        .table-wrap { overflow-x:auto; border-radius:var(--radius-lg); }
        .data-table { width:100%; border-collapse:collapse; text-align:right; font-size:0.95rem; }
        .data-table th { padding:14px 12px; font-weight:800; border-bottom:2px solid var(--primary-base); color:white; background:var(--primary-base); font-size:0.9rem; white-space:nowrap; }
        .data-table td { padding:14px 12px; border-bottom:1px solid var(--border-light); vertical-align:middle; }
        .data-table tr:hover { background-color:var(--primary-light); }
        
        .student-name { font-weight: 800; color: var(--primary-dark); font-size: 0.95rem; display: flex; align-items: center; gap: 8px; }
        .student-name i { color: var(--text-muted); font-size: 0.85rem; }
        .badge-serial { font-weight:800; color:white; background:var(--primary-base); padding:6px 12px; border-radius:var(--radius-sm); display:inline-block; font-size:0.85rem; }
        
        .status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--radius-sm); font-size:0.8rem; font-weight:700; }
        .status-badge.approved { color:var(--status-approved-text); background:var(--status-approved-bg); }
        .status-badge.pending { color:var(--status-pending-text); background:var(--status-pending-bg); }
        .status-badge.rejected { color:var(--status-rejected-text); background:var(--status-rejected-bg); }
        
        .phase-badge { background: var(--bg-page); border: 1px solid var(--border-dark); color: var(--text-muted); font-size: 0.8rem; font-weight: 700; padding: 4px 8px; border-radius: 6px; white-space: nowrap; }
        .amount-text { font-weight: 800; color: var(--accent-dark); white-space: nowrap; }
        .transaction-id { font-family: monospace, 'Cairo'; font-size: 0.85rem; color: var(--text-muted); background: #eee; padding: 3px 8px; border-radius: 4px; font-weight: 700;}

        .date-cell { display:flex; flex-direction:column; gap:4px; color:var(--text-main); font-weight:700; }
        .date-cell small { color:var(--text-muted); font-weight:600; }

        .row-hidden { display: none; }
        .no-results { display: none; text-align: center; padding: 34px 20px; color: var(--text-muted); }
        .no-results i { font-size:2.4rem; color:var(--border-dark); margin-bottom:10px; opacity:0.6; }

        @media(max-width:992px) { .content{margin-right:0;padding:24px 14px;} .toolbar-card{grid-template-columns:1fr;} }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-file-invoice-dollar"></i> إدارة المدفوعات</h2>
        <p class="page-subtitle">الإشراف المالي ومتابعة مدفوعات الباحثين (Admin View)</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-revenue"><i class="fa-solid fa-vault"></i></div>
                <div class="stat-info">
                    <h4>إجمالي الإيرادات المكتملة</h4>
                    <div class="value"><?= number_format($totalRevenue, 2) ?> ج.م</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-pending"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-info">
                    <h4>عمليات قيد الانتظار</h4>
                    <div class="value"><?= $pendingCount ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-failed"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="stat-info">
                    <h4>عمليات فاشلة / ملغاة</h4>
                    <div class="value"><?= $failedCount ?></div>
                </div>
            </div>
        </div>

        <div class="toolbar-card">
            <div class="toolbar-meta" style="grid-column:1/-1;">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث والتصفية السريعة</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة ضبط</button>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="searchPayments"><i class="fa-solid fa-magnifying-glass"></i> البحث العام</label>
                <input type="text" id="searchPayments" class="search-input" placeholder="اسم الباحث، رقم البحث، أو المرجع...">
            </div>
            <div class="filter-group">
                <label class="filter-label" for="statusFilter"><i class="fa-solid fa-filter"></i> حالة الدفع</label>
                <select id="statusFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="approved">مكتمل</option>
                    <option value="pending">قيد الانتظار</option>
                    <option value="rejected">فشل</option>
                </select>
            </div>
        </div>

        <div class="results-bar">
            <div class="results-chip"><i class="fa-solid fa-list"></i> <span>إجمالي السجلات: <strong id="resultsCount"><?= count($allPayments) ?></strong></span></div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="20%">الباحث (Student)</th>
                            <th width="15%">رقم البحث</th>
                            <th width="12%">نوع الرسوم</th>
                            <th width="12%">المبلغ</th>
                            <th width="15%">المرجع (Paymob)</th>
                            <th width="12%">الحالة</th>
                            <th width="14%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button" aria-label="ترتيب حسب تاريخ العملية">
                                    <span>تاريخ العملية</span>
                                    <span class="sort-direction" id="dateSortIcon"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php if (empty($allPayments)): ?>
                            <tr><td colspan="7"><div class="no-results" style="display:block;"><i class="fa-solid fa-folder-open"></i><p style="font-weight:700;font-size:1.05rem;">لا توجد أي سجلات دفع</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($allPayments as $row): 
                                $phaseName = ($row['phase'] === 'initial') ? 'رسوم التقديم' : 'مراجعة العينة';
                                $dateVal = $row['paid_at'] ? $row['paid_at'] : $row['created_at'];
                                
                                // Map DB status to UI Filter Classes
                                if ($row['status'] === 'completed') {
                                    $uiStatus = 'approved'; $icon = 'fa-check'; $label = 'مكتمل';
                                } elseif ($row['status'] === 'pending') {
                                    $uiStatus = 'pending'; $icon = 'fa-clock'; $label = 'انتظار';
                                } else {
                                    $uiStatus = 'rejected'; $icon = 'fa-xmark'; $label = 'فشل';
                                }
                                
                                $searchBlob = strtolower($row['student_name'] . ' ' . $row['serial_number'] . ' ' . $row['gateway_transaction_id']);
                            ?>
                                <tr data-search="<?= htmlspecialchars($searchBlob) ?>" data-status="<?= $uiStatus ?>" data-date="<?= htmlspecialchars($dateVal) ?>">
                                    <td>
                                        <div class="student-name"><i class="fa-solid fa-user-graduate"></i> <?= htmlspecialchars($row['student_name']) ?></div>
                                    </td>
                                    <td><span class="badge-serial"><?= htmlspecialchars($row['serial_number']) ?></span></td>
                                    <td><span class="phase-badge"><?= $phaseName ?></span></td>
                                    <td class="amount-text"><?= number_format($row['amount'], 2) ?> ج.م</td>
                                    <td dir="ltr">
                                        <?php if($row['gateway_transaction_id']): ?>
                                            <span class="transaction-id"><?= htmlspecialchars($row['gateway_transaction_id']) ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--border-dark);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="status-badge <?= $uiStatus ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></span></td>
                                    <td>
                                        <div class="date-cell">
                                            <span><?= htmlspecialchars(irb_format_arabic_date($dateVal)) ?></span>
                                            <small><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_time($dateVal)) ?></small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results"><td colspan="7"><i class="fa-solid fa-filter-circle-xmark"></i><p style="font-weight:800;font-size:1.05rem;margin:0 0 6px;">لا توجد مدفوعات مطابقة للبحث</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('paymentsPagination'); ?>
        </div>
    </div>

    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
        (function () {
            window.IRBTableTools.init({
                searchInputId: 'searchPayments', 
                statusFilterId: 'statusFilter', 
                resetButtonId: 'resetFilters',
                sortHeaderId: 'dateSortHeader', 
                sortIconId: 'dateSortIcon',
                resultsCountId: 'resultsCount', 
                tableBodyId: 'paymentsTableBody', 
                noResultsRowId: 'noResultsRow',
                paginationContainerId: 'paymentsPagination', 
                pageSize: 15,
                defaultSort: 'desc'
            });
        })();
    </script>
</body>
</html>