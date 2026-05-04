<?php
require_once __DIR__ . "/../../init.php";
Auth::checkRole(['manager', 'super_admin']);

require_once __DIR__ . '/../../classes/Reviews.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$reviewsObj = new Reviews();
$allReviews = $reviewsObj->getAllSystemReviews();

function getAssignmentBadge($status, $date)
{
    $dateHtml = '';
    if ($date) {
        $formattedDate = irb_format_arabic_date($date);
        $dateHtml = "<div style='font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;'><i class='fa-regular fa-calendar'></i> {$formattedDate}</div>";
    }

    switch ($status) {
        case 'awaiting_acceptance':
            return '<span class="status-badge awaiting_acceptance"><i class="fa-solid fa-clock"></i> بانتظار القبول</span>' . $dateHtml;
        case 'accepted':
            return '<span class="status-badge accepted"><i class="fa-solid fa-check"></i> تم القبول</span>' . $dateHtml;
        case 'refused':
            return '<span class="status-badge" style="background:#f8d7da;color:#721c24;"><i class="fa-solid fa-xmark"></i> اعتذر</span>' . $dateHtml;
        case 'timed_out':
            return '<span class="status-badge pending"><i class="fa-solid fa-hourglass-end"></i> انتهت المهلة</span>' . $dateHtml;
        default:
            return '<span class="status-badge pending">غير محدد</span>';
    }
}

function getDecisionBadge($assignment_status, $decision)
{
    // If they haven't accepted the review, there is no academic decision yet
    if ($assignment_status !== 'accepted') {
        return '<span style="color:var(--text-muted); font-size:0.85rem; font-weight:700;">-</span>';
    }

    switch ($decision) {
        case 'approved':
            return '<span class="status-badge accepted"><i class="fa-solid fa-check-circle"></i> موافقة</span>';
        case 'needs_modification':
            return '<span class="status-badge" style="background:#fff3cd;color:#856404;"><i class="fa-solid fa-pen-to-square"></i> يحتاج تعديل</span>';
        case 'rejected':
            return '<span class="status-badge" style="background:#f8d7da;color:#721c24;"><i class="fa-solid fa-ban"></i> مرفوض</span>';
        default:
            return '<span class="status-badge pending"><i class="fa-solid fa-spinner"></i> قيد المراجعة</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل المراجعات والتحكيم</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">

    <style>
        /* Shared Styles */
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

        /* Updated Toolbar Grid for 3 Elements */
        .toolbar-card {
            background: linear-gradient(180deg, rgba(44, 62, 80, 0.04) 0%, rgba(255, 255, 255, 0.92) 100%);
            border: 1px solid rgba(189, 195, 199, 0.6);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            padding: 16px;
            margin-bottom: 10px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 15px;
            align-items: end;
        }

        .toolbar-meta {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
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
            gap: 6px;
        }

        .filter-label {
            font-size: 0.8rem;
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
            padding: 9px 12px;
            padding-right: 38px;
            transition: all var(--transition-smooth);
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
            padding: 7px 14px;
            font-family: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--transition-smooth);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
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
            margin-top: 15px;
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
            white-space: nowrap;
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
            white-space: nowrap;
        }

        .app-title {
            color: var(--text-main);
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .app-investigator {
            font-size: 0.85rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            line-height: 1.4;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: var(--radius-sm);
            font-size: 0.78rem;
            font-weight: 800;
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
        }

        .btn-action {
            background: var(--accent-base);
            color: white;
            text-decoration: none;
            padding: 8px 14px;
            border: none;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-weight: 700;
            transition: all var(--transition-smooth);
            box-shadow: var(--shadow-sm);
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            font-family: inherit;
        }

        .btn-action:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        body {
            background: var(--bg-page);
        }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content>* {
            width: 100%;
            max-width: 1120px;
        }

        @media (max-width: 992px) {
            .content {
                margin-right: 0;
                padding: 24px 14px;
            }

            .toolbar-card {
                grid-template-columns: 1fr;
            }
        }

        /* Review Details Modal */
        .review-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            backdrop-filter: blur(4px);
        }

        .review-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .review-modal-content {
            background: var(--bg-surface, #fff);
            border-radius: var(--radius-lg, 12px);
            width: 95%;
            max-width: 800px !important;
            max-height: 90vh;
            overflow-y: auto;
            padding: 0;
            transform: translateY(20px);
            transition: transform 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .review-modal-overlay.active .review-modal-content {
            transform: translateY(0);
        }

        .review-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light, #eee);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            background: var(--primary-light, #f8f9fa);
            border-radius: var(--radius-lg, 12px) var(--radius-lg, 12px) 0 0;
        }

        .review-modal-header h3 {
            margin: 0;
            color: var(--primary-base);
            font-size: 1.2rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        #m_serial {
            margin-right: 8px;
            color: var(--text-main);
            direction: ltr;
            display: inline-block;
            white-space: nowrap;
            unicode-bidi: embed;
            font-family: sans-serif;
            font-size: 1.1rem;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
            padding: 0;
            line-height: 1;
        }

        .btn-close:hover {
            color: var(--alert-base);
        }

        .review-modal-body {
            padding: 24px;
        }

        .detail-section {
            margin-bottom: 24px;
        }

        .detail-section h4 {
            border-bottom: 2px solid var(--border-light);
            padding-bottom: 8px;
            margin-bottom: 16px;
            color: var(--text-main);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-light);
        }

        .detail-item label {
            display: block;
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 5px;
        }

        .detail-item p {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1.5;
        }

        .comments-box {
            background: #fff;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-top: 12px;
        }

        .comment-item {
            padding-bottom: 12px;
            border-bottom: 1px dashed var(--border-light);
            margin-bottom: 12px;
        }

        .comment-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .comment-date {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
        }

        /* Inline Custom Pagination Styles */
        .custom-pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .custom-pagination button {
            background: #fff;
            border: 1px solid var(--border-light);
            color: var(--text-main);
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s;
        }

        .custom-pagination button:hover {
            background: var(--primary-light);
            color: var(--primary-base);
            border-color: var(--primary-base);
        }

        .custom-pagination button.active {
            background: var(--primary-base);
            color: #fff;
            border-color: var(--primary-base);
        }

        .custom-pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <h2 class="page-title">
            <i class="fa-solid fa-list-check"></i>
            سجل المراجعات الكامل
        </h2>
        <p class="page-subtitle">
            استعرض جميع المراجعات السابقة والحالية، مع إمكانية البحث والاطلاع على قرارات المراجعين والتعليقات الخاصة بكل
            بحث.
        </p>

        <div class="toolbar-card">
            <div class="toolbar-meta">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث والتصفية</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة
                    ضبط</button>
            </div>

            <!-- Search -->
            <div class="filter-group">
                <label class="filter-label" for="searchReviews"><i class="fa-solid fa-magnifying-glass"></i> البحث
                    السريع</label>
                <input type="text" id="searchReviews" class="search-input"
                    placeholder="ابحث برقم الملف، اسم المراجع، الخ...">
            </div>

            <!-- Filter 1: Assignment Status -->
            <div class="filter-group">
                <label class="filter-label" for="assignmentFilter"><i class="fa-solid fa-handshake"></i> حالة
                    الإسناد</label>
                <select id="assignmentFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">جميع الحالات</option>
                    <option value="awaiting_acceptance">بانتظار القبول</option>
                    <option value="accepted">تم القبول</option>
                    <option value="refused">اعتذر عن المراجعة</option>
                    <option value="timed_out">انتهت المهلة</option>
                </select>
            </div>

            <!-- Filter 2: Academic Decision -->
            <div class="filter-group">
                <label class="filter-label" for="decisionFilter"><i class="fa-solid fa-gavel"></i> القرار
                    الأكاديمي</label>
                <select id="decisionFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">جميع القرارات</option>
                    <option value="pending">قيد المراجعة</option>
                    <option value="approved">موافقة</option>
                    <option value="needs_modification">يحتاج تعديل</option>
                    <option value="rejected">مرفوض</option>
                </select>
            </div>
        </div>

        <div class="results-bar">
            <div class="results-chip">
                <i class="fa-solid fa-list"></i>
                <span>إجمالي النتائج: <strong id="resultsCount"><?= count($allReviews) ?></strong></span>
            </div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="10%">رقم الملف</th>
                            <th width="28%">بيانات البحث</th>
                            <th width="16%">المراجع</th>
                            <th width="16%">حالة الإسناد</th>
                            <th width="16%">القرار الأكاديمي</th>
                            <th width="14%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="reviewsTableBody">
                        <?php if (empty($allReviews)): ?>
                            <tr id="emptyStateRow">
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <i class="fa-solid fa-inbox"
                                        style="font-size: 3rem; color: var(--border-dark); opacity: 0.5; margin-bottom: 10px;"></i>
                                    <p style="font-weight: 700;">لا توجد مراجعات مسجلة في النظام.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($allReviews as $review):
                                $searchBlob = strtolower(trim($review['serial_number'] . ' ' . $review['title'] . ' ' . $review['student_name'] . ' ' . $review['reviewer_name']));
                                $assignStatus = $review['assignment_status'];
                                $decisionStatus = $review['decision'] ? $review['decision'] : 'pending';
                                $reviewJson = htmlspecialchars(json_encode($review, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="review-row" data-search="<?= $searchBlob ?>" data-assignment="<?= $assignStatus ?>"
                                    data-decision="<?= $decisionStatus ?>">
                                    <td>
                                        <span class="badge-serial"><?= htmlspecialchars($review['serial_number']) ?></span>
                                    </td>
                                    <td>
                                        <div class="app-title"><?= htmlspecialchars($review['title']) ?></div>
                                        <div class="app-investigator">
                                            <i class="fa-solid fa-user-graduate"></i>
                                            <strong>الطالب:</strong> <?= htmlspecialchars($review['student_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary-base); margin-bottom: 4px;">
                                            <?= htmlspecialchars($review['reviewer_name']) ?>
                                        </div>
                                        <?php if (!empty($review['reviewer_department'])): ?>
                                            <div style="font-size: 0.8rem; color: var(--text-muted);">
                                                قسم: <?= htmlspecialchars($review['reviewer_department']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= getAssignmentBadge($review['assignment_status'], $review['assigned_at']) ?>
                                    </td>
                                    <td>
                                        <?= getDecisionBadge($review['assignment_status'], $review['decision']) ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn-action view-details-btn"
                                            data-review="<?= $reviewJson ?>">
                                            <i class="fa-solid fa-eye"></i> التفاصيل
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" style="display:none; text-align:center; padding:34px;">
                                <td colspan="6">
                                    <i class="fa-solid fa-filter-circle-xmark"
                                        style="font-size: 2.4rem; color: var(--border-dark); opacity: 0.6;"></i>
                                    <p style="font-weight: 800; margin: 10px 0 6px;">لا توجد نتائج مطابقة</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Custom Pagination Container -->
            <div id="customPagination" class="custom-pagination"></div>

        </div>
    </div>

    <!-- Review Details Modal -->
    <div id="reviewModal" class="review-modal-overlay">
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h3><i class="fa-solid fa-file-signature"></i> تفاصيل المراجعة <span id="m_serial"></span></h3>
                <button type="button" class="btn-close" id="closeModalBtn"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="review-modal-body">
                <div class="detail-section">
                    <h4>معلومات البحث</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>عنوان البحث</label>
                            <p id="m_title"></p>
                        </div>
                        <div class="detail-item"><label>الباحث (الطالب)</label>
                            <p id="m_student"></p>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <h4>بيانات المراجع والإسناد</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><label>اسم المراجع</label>
                            <p id="m_reviewer"></p>
                        </div>
                        <div class="detail-item"><label>تاريخ الإسناد</label>
                            <p id="m_assigned_at"></p>
                        </div>
                        <div class="detail-item"><label>تاريخ إتمام المراجعة</label>
                            <p id="m_reviewed_at"></p>
                        </div>
                        <div class="detail-item"><label>القرار النهائي</label>
                            <div id="m_decision_badge" style="margin-top: 5px;"></div>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <h4>ملاحظات وتعليقات المراجع</h4>
                    <div id="m_refusal_reason"
                        style="display:none; background:#f8d7da; padding:12px; border-radius:6px; color:#721c24; margin-bottom:10px;">
                        <strong>سبب الاعتذار: </strong> <span id="m_refusal_text"></span>
                    </div>
                    <div id="m_comments_container" class="comments-box"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Logic For Multi-Filtering and Pagination -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- 1. Dual-Filter & Search Logic ---
            const searchInput = document.getElementById('searchReviews');
            const assignFilter = document.getElementById('assignmentFilter');
            const decisionFilter = document.getElementById('decisionFilter');
            const resetButton = document.getElementById('resetFilters');
            const rows = Array.from(document.querySelectorAll('.review-row'));
            const noResultsRow = document.getElementById('noResultsRow');
            const resultsCount = document.getElementById('resultsCount');
            const paginationContainer = document.getElementById('customPagination');

            const pageSize = 10;
            let currentPage = 1;
            let filteredRows = [...rows];

            function applyFilters() {
                const term = searchInput.value.toLowerCase();
                const assignVal = assignFilter.value;
                const decisionVal = decisionFilter.value;

                filteredRows = rows.filter(row => {
                    const searchData = row.getAttribute('data-search');
                    const assignData = row.getAttribute('data-assignment');
                    const decisionData = row.getAttribute('data-decision');

                    const matchesSearch = searchData.includes(term);
                    const matchesAssign = assignVal === 'all' || assignData === assignVal;
                    const matchesDecision = decisionVal === 'all' || decisionData === decisionVal;

                    return matchesSearch && matchesAssign && matchesDecision;
                });

                currentPage = 1; // Reset to page 1 after filter changes
                renderTable();
            }

            function renderTable() {
                // Hide all rows initially
                rows.forEach(r => r.style.display = 'none');

                // Show only the slice for current page
                const start = (currentPage - 1) * pageSize;
                const end = start + pageSize;
                filteredRows.slice(start, end).forEach(r => r.style.display = '');

                // Update UI metadata
                resultsCount.textContent = filteredRows.length;
                if (noResultsRow) noResultsRow.style.display = filteredRows.length === 0 ? '' : 'none';

                renderPagination();
            }

            function renderPagination() {
                paginationContainer.innerHTML = '';
                const totalPages = Math.ceil(filteredRows.length / pageSize);
                if (totalPages <= 1) return;

                // Prev Button
                const prevBtn = document.createElement('button');
                prevBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
                prevBtn.disabled = currentPage === 1;
                prevBtn.addEventListener('click', () => { currentPage--; renderTable(); });
                paginationContainer.appendChild(prevBtn);

                // Page Numbers
                for (let i = 1; i <= totalPages; i++) {
                    const pageBtn = document.createElement('button');
                    pageBtn.textContent = i;
                    if (i === currentPage) pageBtn.classList.add('active');
                    pageBtn.addEventListener('click', () => { currentPage = i; renderTable(); });
                    paginationContainer.appendChild(pageBtn);
                }

                // Next Button
                const nextBtn = document.createElement('button');
                nextBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
                nextBtn.disabled = currentPage === totalPages;
                nextBtn.addEventListener('click', () => { currentPage++; renderTable(); });
                paginationContainer.appendChild(nextBtn);
            }

            // Bind Events
            searchInput.addEventListener('input', applyFilters);
            assignFilter.addEventListener('change', applyFilters);
            decisionFilter.addEventListener('change', applyFilters);

            resetButton.addEventListener('click', () => {
                searchInput.value = '';
                assignFilter.value = 'all';
                decisionFilter.value = 'all';
                applyFilters();
            });

            // Initialize
            if (rows.length > 0) renderTable();

            // --- 2. Modal Logic ---
            const modal = document.getElementById('reviewModal');
            const closeBtn = document.getElementById('closeModalBtn');

            function closeModal() { modal.classList.remove('active'); }
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

            // Attach to the table body (Event Delegation) so it works on paginated rows
            document.getElementById('reviewsTableBody').addEventListener('click', function (e) {
                const btn = e.target.closest('.view-details-btn');
                if (!btn) return;

                const data = JSON.parse(btn.getAttribute('data-review'));

                document.getElementById('m_serial').textContent = `(${data.serial_number})`;
                document.getElementById('m_title').textContent = data.title;
                document.getElementById('m_student').textContent = data.student_name;
                document.getElementById('m_reviewer').textContent = data.reviewer_name;

                document.getElementById('m_assigned_at').textContent = data.assigned_at ? new Date(data.assigned_at).toLocaleString('ar-EG') : 'غير متوفر';
                document.getElementById('m_reviewed_at').textContent = data.reviewed_at ? new Date(data.reviewed_at).toLocaleString('ar-EG') : 'لم تتم المراجعة بعد';

                const refusalBox = document.getElementById('m_refusal_reason');
                if (data.assignment_status === 'refused') {
                    refusalBox.style.display = 'block';
                    document.getElementById('m_refusal_text').textContent = data.refusal_reason || 'غير محدد';
                    document.getElementById('m_decision_badge').innerHTML = '<span class="status-badge" style="background:#f8d7da;color:#721c24;">اعتذر عن المراجعة</span>';
                } else if (data.assignment_status === 'awaiting_acceptance') {
                    refusalBox.style.display = 'none';
                    document.getElementById('m_decision_badge').innerHTML = '<span class="status-badge awaiting_acceptance">بانتظار القبول</span>';
                } else if (data.assignment_status === 'timed_out') {
                    refusalBox.style.display = 'none';
                    document.getElementById('m_decision_badge').innerHTML = '<span class="status-badge pending">انتهت المهلة</span>';
                } else {
                    refusalBox.style.display = 'none';
                    let badgeHtml = '';
                    if (data.decision === 'approved') badgeHtml = '<span class="status-badge accepted"><i class="fa-solid fa-check-circle"></i> موافقة</span>';
                    else if (data.decision === 'needs_modification') badgeHtml = '<span class="status-badge" style="background:#fff3cd;color:#856404;"><i class="fa-solid fa-pen-to-square"></i> يحتاج تعديل</span>';
                    else if (data.decision === 'rejected') badgeHtml = '<span class="status-badge" style="background:#f8d7da;color:#721c24;"><i class="fa-solid fa-ban"></i> مرفوض</span>';
                    else badgeHtml = '<span class="status-badge pending"><i class="fa-solid fa-spinner"></i> قيد الانتظار</span>';
                    document.getElementById('m_decision_badge').innerHTML = badgeHtml;
                }

                const commentsContainer = document.getElementById('m_comments_container');
                commentsContainer.innerHTML = '';
                if (data.comments && data.comments.length > 0) {
                    data.comments.forEach(c => {
                        const d = new Date(c.created_at).toLocaleString('ar-EG');
                        commentsContainer.innerHTML += `
                            <div class="comment-item">
                                <span class="comment-date"><i class="fa-regular fa-clock"></i> ${d}</span>
                                <p style="margin:0; font-size: 0.95rem; line-height:1.6; color:var(--text-main);">${c.comment}</p>
                            </div>
                        `;
                    });
                } else {
                    commentsContainer.innerHTML = '<p style="color:var(--text-muted); margin:0; text-align:center;">لا توجد تعليقات مسجلة.</p>';
                }

                modal.classList.add('active');
            });
        });
    </script>
</body>

</html>