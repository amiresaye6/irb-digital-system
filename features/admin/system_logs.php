<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . "/../../classes/Auth.php"; 

// Auth::checkRole(['admin']);
Auth::checkRole(['admin', 'super_admin']);
$is_super_admin = ($_SESSION['role'] === 'super_admin');
require_once '../../init.php';
require_once '../../includes/irb_helpers.php';
require_once '../../includes/pagination.php';
require_once '../../classes/SystemLogs.php';

$dbobj = new Database();
$systemLogs = new SystemLogs($dbobj->getconn());

$summary = $systemLogs->getLogsSummary();
$logs = $systemLogs->getAllLogs();

// Map DB roles to Arabic UI roles
$roleTranslations = [
    'admin' => 'مدير النظام',
    'manager' => 'مدير اللجنة',
    'reviewer' => 'مراجع',
    'sample_officer' => 'مسؤول عينات',
    'student' => 'باحث',
    'super_admin' => 'مدير عام'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل النظام | IRB System</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 20px 24px; display: flex; flex-direction: column; align-items: center; }
        .content > * { width: 100%; max-width: 1200px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 25px; }
        .page-title { color: var(--primary-base); font-weight: 800; display: flex; align-items: center; gap: 12px; font-size: 1.6rem; margin: 0 0 8px 0; }
        .page-title i { color: var(--accent-base); }
        .page-subtitle { color: var(--text-muted); font-weight: 500; font-size: 0.9rem; margin: 0; line-height: 1.5; }

        .btn-export { background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: var(--radius-md); font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; font-family: inherit; font-size: 0.9rem; text-decoration: none; }
        .btn-export:hover { background: #219653; transform: translateY(-2px); box-shadow: var(--shadow-sm); color: white; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .stat-card { background: var(--bg-surface); padding: 20px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); display: flex; align-items: center; gap: 18px; transition: all var(--transition-smooth); }
        .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); border-color: var(--accent-base); }
        .stat-icon { width: 54px; height: 54px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; }
        .icon-1 { background: rgba(26, 188, 156, 0.1); color: #1abc9c; }
        .icon-2 { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .icon-3 { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        .icon-4 { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        
        .stat-info h4 { margin: 0 0 6px 0; font-size: 0.85rem; color: var(--text-muted); font-weight: 700; }
        .stat-info .value { margin: 0; font-size: 1.5rem; font-weight: 800; color: var(--primary-dark); line-height: 1.2; }
        .stat-info .value small { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; display: block; margin-top: 4px; }

        /* Toolbar */
        .toolbar-card { background: linear-gradient(180deg, rgba(44, 62, 80, 0.04) 0%, rgba(255, 255, 255, 0.92) 100%); border: 1px solid rgba(189, 195, 199, 0.6); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); padding: 15px; margin-bottom: 15px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end; }
        .toolbar-meta { grid-column: 1 / -1; display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 5px; flex-wrap: wrap; }
        .toolbar-title { display: flex; align-items: center; gap: 10px; color: var(--primary-base); font-weight: 800; font-size: 0.95rem; margin: 0; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-label { font-size: 0.78rem; font-weight: 800; color: var(--primary-base); display: flex; align-items: center; gap: 6px; }
        
        .search-input { width: 100%; border: 1.5px solid rgba(189, 195, 199, 0.9); border-radius: 10px; background: #fff; color: var(--text-main); font-family: inherit; font-size: 0.9rem; font-weight: 600; padding: 9px 38px 9px 12px; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        .search-input:focus { outline: none; border-color: var(--accent-base); box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.12); }
        .date-input { width: 100%; border: 1.5px solid rgba(189, 195, 199, 0.9); border-radius: 10px; background: #fff; color: var(--text-main); font-family: inherit; font-size: 0.9rem; font-weight: 600; padding: 9px 12px; }
        .date-input:focus { outline: none; border-color: var(--accent-base); box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.12); }
        
        .btn-reset { border: 1.5px solid var(--border-light); background: #fff; color: var(--primary-base); border-radius: 10px; padding: 9px 14px; font-family: inherit; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; transition: all var(--transition-smooth); }
        .btn-reset:hover { background: var(--primary-light); border-color: var(--primary-base); transform: translateY(-1px); }

        .results-bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 10px; color: var(--text-muted); font-size: 0.85rem; flex-wrap: wrap; }
        .results-chip { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: var(--primary-light); color: var(--primary-base); font-weight: 700; font-size: 0.85rem; }

        /* Data Card & Table */
        .data-card { background: var(--bg-surface); padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); border: 1px solid var(--border-light); margin-top: 15px; }
        .table-wrap { overflow-x: auto; border-radius: var(--radius-lg); }
        .data-table { width: 100%; border-collapse: collapse; text-align: right; font-size: 0.95rem; }
        .data-table th { padding: 14px 12px; font-weight: 800; border-bottom: 2px solid var(--primary-base); color: white; background: var(--primary-base); font-size: 0.9rem; white-space: nowrap; }
        .data-table td { padding: 14px 12px; border-bottom: 1px solid var(--border-light); vertical-align: middle; cursor: pointer; }
        .data-table tbody tr.main-row:hover { background-color: var(--primary-light); }
        
        .sortable-header { user-select: none; }
        .sortable-button { width: 100%; border: 0; background: transparent; color: inherit; font: inherit; font-weight: 800; display: inline-flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; padding: 0; }
        .sort-direction { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 999px; background: rgba(255, 255, 255, 0.15); }

        .name { font-weight: 800; color: #1e293b !important; font-size: 0.95rem; margin-bottom: 4px; }
        .role-badge { display: inline-block; font-size: 0.75rem; background: #e2e8f0; color: #475569 !important; padding: 2px 8px; border-radius: 999px; font-weight: 700; }
        .badge-serial { font-weight: 800; color: var(--primary-base); background: var(--primary-light); padding: 6px 12px; border-radius: var(--radius-sm); display: inline-block; font-size: 0.85rem; text-decoration: none; }
        .badge-serial:hover { background: var(--primary-base); color: white; }

        .date-cell { display: flex; flex-direction: column; gap: 4px; color: var(--text-main); font-weight: 700; }
        .date-cell small { color: var(--text-muted); font-weight: 600; }

        /* Colored Action Badges */
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 800; white-space: nowrap; }
        .status-login { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .status-registration { background: #fce7f3; color: #db2777; border: 1px solid #fbcfe8; }
        .status-profile { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
        .status-payment { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .status-submission { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }
        .status-document { background: #fef08a; color: #a16207; border: 1px solid #fde047; }
        .status-assignment { background: #f3e8ff; color: #9333ea; border: 1px solid #e9d5ff; }
        .status-change { background: #ffedd5; color: #ea580c; border: 1px solid #fed7aa; }
        .status-decision { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .status-certificate { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .status-general { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

        /* Accordion Row */
        .expand-row { display: none; background: #f8fafc; }
        .expand-row.active { display: table-row; }
        .expand-content { padding: 20px 30px !important; border-bottom: 2px solid var(--border-light) !important; }
        
        .expand-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .expand-box { background: #ffffff; border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 15px; }
        .expand-box h5 { margin: 0 0 10px 0; color: var(--primary-base); font-size: 0.85rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
        .expand-box p { margin: 0; color: var(--text-main); font-size: 0.9rem; font-weight: 600; line-height: 1.6; }
        
        .chevron-icon { transition: transform 0.3s ease; color: var(--text-muted); }
        .main-row.active .chevron-icon { transform: rotate(180deg); color: var(--primary-base); }

        .row-hidden { display: none !important; }
        .no-results { display: none; text-align: center; padding: 34px 20px; color: var(--text-muted); }
        .no-results i { font-size: 2.4rem; color: var(--border-dark); margin-bottom: 10px; opacity: 0.6; }

        @media(max-width: 992px) { .content { margin-right: 0; } .expand-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <div>
                <h2 class="page-title"><i class="fa-solid fa-list-check"></i> سجل النظام (Audit Logs)</h2>
                <p class="page-subtitle">تتبع ومراقبة كافة العمليات والأحداث داخل النظام بدقة</p>
            </div>
            <button class="btn-export" id="exportBtn"><i class="fa-solid fa-file-excel"></i> تصدير إكسيل</button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-1"><i class="fa-solid fa-calendar-day"></i></div>
                <div class="stat-info">
                    <h4>إجمالي سجلات اليوم</h4>
                    <div class="value"><?= $summary['today'] ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-2"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="stat-info">
                    <h4>إجمالي سجلات الشهر</h4>
                    <div class="value"><?= $summary['this_month'] ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-3"><i class="fa-solid fa-fire"></i></div>
                <div class="stat-info">
                    <h4>المستخدم الأكثر نشاطاً</h4>
                    <div class="value" style="font-size:1.1rem;"><?= htmlspecialchars($summary['active_user']['name']) ?> <small><?= $summary['active_user']['count'] ?> إجراء</small></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-4"><i class="fa-solid fa-bolt"></i></div>
                <div class="stat-info">
                    <h4>أحدث إجراء مسجل</h4>
                    <div class="value" style="font-size:1rem; line-height:1.4;"><?= htmlspecialchars($summary['last_action']['action']) ?> <small><?= $summary['last_action']['time'] ? irb_format_arabic_time($summary['last_action']['time']) : '' ?></small></div>
                </div>
            </div>
        </div>

        <div class="toolbar-card">
            <div class="toolbar-meta">
                <h3 class="toolbar-title"><i class="fa-solid fa-filter"></i> التصفية المتقدمة</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة ضبط</button>
            </div>
            
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-magnifying-glass"></i> البحث العام</label>
                <input type="text" id="searchLogs" class="search-input" placeholder="اسم المستخدم، الإجراء، أو البحث...">
            </div>
            
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-user-tag"></i> دور المستخدم</label>
                <select id="roleFilter" class="irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="admin">مدير النظام</option>
                    <option value="manager">مدير اللجنة</option>
                    <option value="reviewer">مراجع</option>
                    <option value="sample_officer">مسؤول عينات</option>
                    <option value="student">باحث</option>
                    <option value="super_admin">مدير عام</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label"><i class="fa-solid fa-layer-group"></i> نوع الإجراء</label>
                <select id="typeFilter" class="irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="login">تسجيل الدخول</option>
                    <option value="registration">تسجيل جديد</option>
                    <option value="profile">تحديث الملف</option>
                    <option value="payment">عملية دفع</option>
                    <option value="submission">تقديم بحث</option>
                    <option value="document">إدارة المستندات</option>
                    <option value="assignment">إسناد مراجعين</option>
                    <option value="status_change">تغيير حالة</option>
                    <option value="decision">قرار تحكيم</option>
                    <option value="certificate">إصدار شهادة</option>
                    <option value="general">إجراء عام</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label"><i class="fa-regular fa-calendar"></i> من تاريخ</label>
                <input type="date" id="dateFrom" class="date-input">
            </div>

            <div class="filter-group">
                <label class="filter-label"><i class="fa-regular fa-calendar-check"></i> إلى تاريخ</label>
                <input type="date" id="dateTo" class="date-input">
            </div>
        </div>

        <div class="results-bar">
            <div class="results-chip"><i class="fa-solid fa-list"></i> <span>إجمالي السجلات: <strong id="resultsCount"><?= count($logs) ?></strong></span></div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table" id="logsTable">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button" aria-label="ترتيب حسب الوقت">
                                    <span>التاريخ والوقت</span>
                                    <span class="sort-direction" id="dateSortIcon"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
                                </button>
                            </th>
                            <th width="20%">المستخدم</th>
                            <th width="20%">الإجراء</th>
                            <th width="15%">البحث المرتبط</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="6"><div class="no-results" style="display:block;"><i class="fa-solid fa-folder-open"></i><p style="font-weight:700;font-size:1.05rem;">لا توجد أي سجلات</p></div></td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $index => $row): 
                                $userRole = $row['role'] ? ($roleTranslations[$row['role']] ?? $row['role']) : 'نظام';
                                $userName = $row['full_name'] ? $row['full_name'] : 'النظام الآلي';
                                
                                $searchBlob = strtolower($userName . ' ' . $row['action'] . ' ' . $row['serial_number']);
                                $rawDate = date('Y-m-d', strtotime($row['created_at']));
                            ?>
                                <tr class="main-row" 
                                    data-search="<?= htmlspecialchars($searchBlob) ?>" 
                                    data-role="<?= $row['role'] ?? 'system' ?>" 
                                    data-type="<?= $row['action_type'] ?>" 
                                    data-date="<?= htmlspecialchars($rawDate) ?>"
                                    data-sortdate="<?= $row['created_at'] ?>"
                                    onclick="toggleRow(this)">
                                    
                                    <td><strong><?= $index + 1 ?></strong></td>
                                    <td>
                                        <div class="date-cell">
                                            <span><?= htmlspecialchars(irb_format_arabic_date($row['created_at'])) ?></span>
                                            <small><i class="fa-regular fa-clock"></i> <?= htmlspecialchars(irb_format_arabic_time($row['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="name"><?= htmlspecialchars($userName) ?></div>
                                        <div class="role-badge"><?= $userRole ?></div>
                                    </td>
                                    <td>
                                        <span class="<?= $row['badge_class'] ?>">
                                            <?= htmlspecialchars($row['action_label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['serial_number']): ?>
                                            <a href="/irb-digital-system/features/admin/application_details.php?id=<?= $row['application_id'] ?>" class="badge-serial" onclick="event.stopPropagation()">
                                                <?= htmlspecialchars($row['serial_number']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-weight:800;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:left;"><i class="fa-solid fa-chevron-down chevron-icon"></i></td>
                                </tr>
                                <tr class="expand-row">
                                    <td colspan="6" class="expand-content">
                                        <div class="expand-grid">
                                            <div class="expand-box">
                                                <h5><i class="fa-solid fa-circle-info"></i> الوصف الكامل للإجراء</h5>
                                                <p><?= htmlspecialchars($row['action']) ?></p>
                                            </div>
                                            <div class="expand-box">
                                                <h5><i class="fa-solid fa-laptop-code"></i> معلومات المتصفح / القيم السابقة</h5>
                                                <p style="color:var(--text-muted); font-style:italic;"><i class="fa-solid fa-circle-exclamation"></i> غير متوفر في هذا السجل القديم</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results"><td colspan="6"><i class="fa-solid fa-filter-circle-xmark"></i><p style="font-weight:800;font-size:1.05rem;margin:0 0 6px;">لا توجد سجلات مطابقة للبحث</p></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('logsPagination'); ?>
        </div>
    </div>

    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
        function toggleRow(row) {
            const nextRow = row.nextElementSibling;
            if(nextRow && nextRow.classList.contains('expand-row')) {
                const isActive = nextRow.classList.contains('active');
                
                // Close all
                document.querySelectorAll('.expand-row').forEach(r => r.classList.remove('active'));
                document.querySelectorAll('.main-row').forEach(r => r.classList.remove('active'));

                // Open clicked if it wasn't active
                if(!isActive) {
                    nextRow.classList.add('active');
                    row.classList.add('active');
                }
            }
        }

        (function () {
            // Setup custom multi-filter logic to supplement irb-table-tools
            const searchInput = document.getElementById('searchLogs');
            const roleFilter = document.getElementById('roleFilter');
            const typeFilter = document.getElementById('typeFilter');
            const dateFrom = document.getElementById('dateFrom');
            const dateTo = document.getElementById('dateTo');
            const resetButton = document.getElementById('resetFilters');
            const mainRows = document.querySelectorAll('.main-row');
            const resultsCount = document.getElementById('resultsCount');
            const noRes = document.getElementById('noResultsRow');

            // Map rows and their expand-row counterparts ONCE to prevent DOM moving issues
            const allRowPairs = Array.from(mainRows).map(row => ({
                main: row,
                expand: row.nextElementSibling && row.nextElementSibling.classList.contains('expand-row') ? row.nextElementSibling : null
            }));

            let sortDirection = 'desc';
            let currentPage = 1;
            const pageSize = 15;
            const sortHeader = document.getElementById('dateSortHeader');
            const sortIcon = document.getElementById('dateSortIcon');
            const paginationContainer = document.getElementById('logsPagination');
            const tableBody = document.getElementById('logsTableBody');

            function toArabicDigits(value) {
                return String(value).replace(/[0-9]/g, digit => ({
                    '0': '٠', '1': '١', '2': '٢', '3': '٣', '4': '٤', '5': '٥', '6': '٦', '7': '٧', '8': '٨', '9': '٩'
                }[digit]));
            }

            function applyFilters() {
                const search = searchInput.value.toLowerCase();
                const role = roleFilter.value;
                const type = typeFilter.value;
                const from = dateFrom.value;
                const to = dateTo.value;
                
                let visiblePairs = [];

                allRowPairs.forEach(pair => {
                    const rowSearch = pair.main.getAttribute('data-search');
                    const rowRole = pair.main.getAttribute('data-role');
                    const rowType = pair.main.getAttribute('data-type');
                    const rowDate = pair.main.getAttribute('data-date');

                    let show = true;

                    if(search && !rowSearch.includes(search)) show = false;
                    if(role !== 'all' && rowRole !== role) show = false;
                    if(type !== 'all' && rowType !== type) show = false;
                    if(from && rowDate < from) show = false;
                    if(to && rowDate > to) show = false;

                    if(show) {
                        pair.main.classList.remove('row-hidden');
                        visiblePairs.push(pair);
                    } else {
                        pair.main.classList.add('row-hidden');
                        if(pair.expand) {
                            pair.expand.classList.remove('active');
                            pair.main.classList.remove('active');
                        }
                    }
                });

                resultsCount.innerText = visiblePairs.length;
                if(visiblePairs.length === 0 && allRowPairs.length > 0) {
                    noRes.style.display = 'table-row';
                } else {
                    noRes.style.display = 'none';
                }
                
                applyPaginationAndSort(visiblePairs);
            }

            function applyPaginationAndSort(visiblePairs) {
                // Sorting
                visiblePairs.sort((a, b) => {
                    const dateA = new Date(a.main.getAttribute('data-sortdate') || 0);
                    const dateB = new Date(b.main.getAttribute('data-sortdate') || 0);
                    return sortDirection === 'asc' ? dateA - dateB : dateB - dateA;
                });

                // Update DOM order
                visiblePairs.forEach(pair => {
                    tableBody.appendChild(pair.main);
                    if(pair.expand) {
                        tableBody.appendChild(pair.expand);
                    }
                });

                // Pagination
                const totalItems = visiblePairs.length;
                const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
                
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                visiblePairs.forEach((pair, index) => {
                    if (index >= (currentPage - 1) * pageSize && index < currentPage * pageSize) {
                        pair.main.style.display = '';
                        if(pair.expand) {
                            pair.expand.style.display = '';
                        }
                    } else {
                        pair.main.style.display = 'none';
                        if(pair.expand) {
                            pair.expand.style.display = 'none';
                        }
                    }
                });

                renderPaginationUI(totalItems, totalPages);
            }

            function renderPaginationUI(totalItems, totalPages) {
                if (!paginationContainer) return;

                if (totalItems === 0) {
                    paginationContainer.innerHTML = '';
                    return;
                }

                const startIndex = toArabicDigits((currentPage - 1) * pageSize + 1);
                const endIndex = toArabicDigits(Math.min(currentPage * pageSize, totalItems));
                const totalLabel = toArabicDigits(totalItems);
                const currentPageLabel = toArabicDigits(currentPage);
                const totalPageLabel = toArabicDigits(totalPages);

                paginationContainer.innerHTML = [
                    '<div class="irb-pagination__bar">',
                    '<div class="irb-pagination__meta">',
                    '<span class="irb-pagination__chip">عرض ' + startIndex + ' - ' + endIndex + ' من ' + totalLabel + '</span>',
                    '<span class="irb-pagination__chip irb-pagination__chip--soft">' + currentPageLabel + ' / ' + totalPageLabel + '</span>',
                    '</div>',
                    '<div class="irb-pagination__controls">',
                    '<button type="button" class="irb-pagination__arrow" data-page="prev" ' + (currentPage === 1 ? 'disabled' : '') + ' aria-label="الصفحة السابقة"><i class="fa-solid fa-chevron-right"></i></button>',
                    '<button type="button" class="irb-pagination__arrow" data-page="next" ' + (currentPage === totalPages ? 'disabled' : '') + ' aria-label="الصفحة التالية"><i class="fa-solid fa-chevron-left"></i></button>',
                    '</div>',
                    '</div>'
                ].join('');

                paginationContainer.querySelectorAll('[data-page]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const target = button.getAttribute('data-page');
                        if (target === 'prev') {
                            currentPage = Math.max(1, currentPage - 1);
                        } else if (target === 'next') {
                            currentPage = Math.min(totalPages, currentPage + 1);
                        }
                        applyFilters();
                    });
                });
            }

            [searchInput, roleFilter, typeFilter, dateFrom, dateTo].forEach(el => {
                if(el) el.addEventListener('input', () => { currentPage = 1; applyFilters(); });
                if(el) el.addEventListener('change', () => { currentPage = 1; applyFilters(); });
            });

            if(resetButton) {
                resetButton.addEventListener('click', () => {
                    searchInput.value = '';
                    roleFilter.value = 'all';
                    typeFilter.value = 'all';
                    dateFrom.value = '';
                    dateTo.value = '';
                    currentPage = 1;
                    applyFilters();
                });
            }

            sortHeader.addEventListener('click', () => {
                sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                sortIcon.innerHTML = sortDirection === 'asc' ? '<i class="fa-solid fa-arrow-up-wide-short"></i>' : '<i class="fa-solid fa-arrow-down-wide-short"></i>';
                currentPage = 1;
                applyFilters();
            });

            // Export to Excel (CSV)
            document.getElementById('exportBtn').addEventListener('click', function() {
                let csv = '\uFEFF'; 
                csv += "رقم,التاريخ والوقت,اسم المستخدم,دور المستخدم,نوع الإجراء,الوصف,البحث المرتبط\n";
                
                let index = 1;
                allRowPairs.forEach(pair => {
                    if(!pair.main.classList.contains('row-hidden')) {
                        const dateCell = pair.main.cells[1].innerText.replace(/\n/g, ' ');
                        const userName = pair.main.querySelector('.name').innerText;
                        const userRole = pair.main.querySelector('.role-badge').innerText;
                        const actionLabel = pair.main.cells[3].innerText;
                        const serial = pair.main.cells[4].innerText;
                        const fullDesc = pair.expand ? pair.expand.querySelector('.expand-box p').innerText : '';

                        // Escape quotes
                        const rowData = [
                            index++,
                            `"${dateCell}"`,
                            `"${userName}"`,
                            `"${userRole}"`,
                            `"${actionLabel}"`,
                            `"${fullDesc}"`,
                            `"${serial}"`
                        ];
                        csv += rowData.join(',') + "\n";
                    }
                });

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `System_Logs_${new Date().toISOString().split('T')[0]}.csv`;
                a.click();
                URL.revokeObjectURL(url);
            });

            // Initial sort & filter
            applyFilters();


        })();
    </script>
</body>
</html>
