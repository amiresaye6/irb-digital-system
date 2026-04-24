<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /irb-digital-system/features/auth/login.php");
    exit;
}

require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$db = new Database();
$student_id = $_SESSION['user_id'];

// Join payments with applications to get the titles and serials
$sql = "
    SELECT p.*, a.title, a.serial_number 
    FROM payments p
    JOIN applications a ON p.application_id = a.id
    WHERE a.student_id = $student_id
    ORDER BY p.created_at DESC
";

$history = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل المدفوعات | IRB System</title>

    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-select.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/irb-pagination.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        body {
            background: var(--bg-page);
            position: relative;
            z-index: 0;
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
            text-align: right;
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--border-light);
            vertical-align: middle;
            word-wrap: break-word;
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

        .app-title-cell {
            color: var(--text-main);
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
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

        .phase-badge {
            background: var(--bg-page);
            border: 1px solid var(--border-dark);
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .amount-text {
            font-weight: 800;
            color: var(--accent-dark);
            font-size: 1rem;
            white-space: nowrap;
        }

        .transaction-id {
            font-family: monospace, 'Cairo';
            font-size: 0.85rem;
            color: var(--text-muted);
            background: #eee;
            padding: 3px 8px;
            border-radius: 4px;
            font-weight: 700;
        }

        /* Compact Action Buttons */
        .action-buttons-flex {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-icon-sm {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 1rem;
            transition: all var(--transition-smooth);
            text-decoration: none;
            border: 1.5px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
        }

        .btn-view {
            background: #ffffff;
            color: var(--primary-base);
        }

        .btn-view:hover {
            background: var(--primary-light);
            border-color: var(--primary-base);
            transform: translateY(-2px);
        }

        .btn-pdf {
            background: #ffffff;
            color: #e74c3c;
        }

        .btn-pdf:hover {
            background: #fadbd8;
            border-color: #e74c3c;
            transform: translateY(-2px);
        }

        .btn-receipt.disabled {
            padding: 4px 8px;
            font-size: 0.8rem;
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8fafc;
            color: var(--text-muted);
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            pointer-events: none;
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

        @media(max-width:992px) {
            .content {
                margin-right: 0;
                padding: 24px 14px;
            }

            .toolbar-card {
                grid-template-columns: 1fr;
            }
        }

        /* Template stored safely out of the way */
        #hidden-pdf-template {
            display: none;
        }

        .pdf-receipt-box {
            background: #ffffff;
            padding: 40px;
            text-align: center;
            font-family: 'Cairo', sans-serif;
            color: #2c3e50;
            border: 2px solid #ecf0f1;
            border-radius: 16px;
            width: 700px;
            margin: 0 auto;
            direction: rtl;
            /* Safe inside LTR wrapper */
        }

        .pdf-success-badge {
            display: inline-block;
            background: #d5f4e6;
            color: #27ae60;
            padding: 12px 24px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .pdf-receipt-box h2 {
            margin: 0;
            font-size: 2rem;
            color: #1a252f;
            font-weight: 800;
        }

        .pdf-receipt-box .pdf-amount {
            font-size: 3rem;
            font-weight: 800;
            color: #1abc9c;
            margin: 15px 0 30px;
        }

        .pdf-receipt-box .pdf-details {
            text-align: right;
            border-top: 3px dashed #bdc3c7;
            padding-top: 30px;
        }

        .pdf-receipt-box .pdf-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .pdf-receipt-box .pdf-row .lbl {
            color: #7f8c8d;
            font-weight: 700;
        }

        .pdf-receipt-box .pdf-row .val {
            color: #2c3e50;
            font-weight: 800;
            font-family: monospace, 'Cairo';
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-file-invoice-dollar"></i> سجل المدفوعات</h2>
        <p class="page-subtitle">متابعة كافة الفواتير وعمليات الدفع السابقة لأبحاثك واستخراج الإيصالات</p>

        <div class="toolbar-card">
            <div class="toolbar-meta" style="grid-column:1/-1;">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث والتصفية</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة
                    ضبط</button>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="searchPayments"><i class="fa-solid fa-magnifying-glass"></i> البحث
                    السريع</label>
                <input type="text" id="searchPayments" class="search-input"
                    placeholder="ابحث برقم الملف أو رقم المعاملة...">
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
            <div class="results-chip"><i class="fa-solid fa-list"></i> <span>إجمالي العمليات: <strong
                        id="resultsCount"><?= count($history) ?></strong></span></div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="15%">رقم الملف</th>
                            <th width="12%">نوع الرسوم</th>
                            <th width="15%">المبلغ</th>
                            <th width="15%">المرجع (ID)</th>
                            <th width="18%" class="sortable-header">
                                <button type="button" id="dateSortHeader" class="sortable-button"
                                    aria-label="ترتيب حسب تاريخ الدفع">
                                    <span>تاريخ العملية</span>
                                    <span class="sort-direction" id="dateSortIcon"><i
                                            class="fa-solid fa-arrow-down-wide-short"></i></span>
                                </button>
                            </th>
                            <th width="12%">الحالة</th>
                            <th width="13%" style="text-align: center;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state"><i class="fa-solid fa-receipt"></i>
                                        <p style="font-weight:700;font-size:1.05rem;">لا توجد أي مدفوعات</p>
                                        <p style="font-size:0.9rem;">لم تقم بأي عمليات دفع حتى الآن.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $row):
                                $phaseName = ($row['phase'] === 'initial') ? 'رسوم التقديم' : 'مراجعة العينة';
                                $dateVal = $row['paid_at'] ? $row['paid_at'] : $row['created_at'];

                                if ($row['status'] === 'completed') {
                                    $uiStatus = 'approved';
                                    $icon = 'fa-circle-check';
                                    $label = 'مكتمل';
                                } elseif ($row['status'] === 'pending') {
                                    $uiStatus = 'pending';
                                    $icon = 'fa-hourglass-half';
                                    $label = 'انتظار';
                                } else {
                                    $uiStatus = 'rejected';
                                    $icon = 'fa-circle-xmark';
                                    $label = 'فشل';
                                }

                                $searchBlob = strtolower($row['serial_number'] . ' ' . $row['title'] . ' ' . $row['gateway_transaction_id']);
                                $receiptUrl = "../payment/receipt.php?payment_id={$row['id']}";

                                // Data for JS PDF
                                $amtFormatted = number_format($row['amount'], 2) . ' ج.م';
                                $serialSafe = htmlspecialchars($row['serial_number'], ENT_QUOTES);
                                $refSafe = htmlspecialchars($row['transaction_reference'], ENT_QUOTES);
                                $paymobSafe = htmlspecialchars($row['gateway_transaction_id'] ?? '—', ENT_QUOTES);
                                $dateSafe = htmlspecialchars(date('Y-m-d h:i A', strtotime($dateVal)), ENT_QUOTES);
                                ?>
                                <tr data-search="<?= htmlspecialchars($searchBlob) ?>" data-status="<?= $uiStatus ?>"
                                    data-date="<?= htmlspecialchars($dateVal) ?>">
                                    <td>
                                        <span class="badge-serial"
                                            style="margin-bottom: 6px;"><?= htmlspecialchars($row['serial_number']) ?></span><br>
                                        <div class="app-title-cell">
                                            <?= mb_strimwidth(htmlspecialchars($row['title']), 0, 30, "...") ?>
                                        </div>
                                    </td>
                                    <td><span class="phase-badge"><?= $phaseName ?></span></td>
                                    <td class="amount-text"><?= number_format($row['amount'], 2) ?> ج.م</td>
                                    <td dir="ltr">
                                        <?php if ($row['gateway_transaction_id']): ?>
                                            <span
                                                class="transaction-id"><?= htmlspecialchars($row['gateway_transaction_id']) ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--border-dark);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span><?= htmlspecialchars(irb_format_arabic_date($dateVal)) ?></span>
                                            <small><i class="fa-regular fa-clock"></i>
                                                <?= htmlspecialchars(irb_format_arabic_time($dateVal)) ?></small>
                                        </div>
                                    </td>
                                    <td><span class="status-badge <?= $uiStatus ?>"><i class="fa-solid <?= $icon ?>"></i>
                                            <?= $label ?></span></td>

                                    <td style="text-align: center; vertical-align: middle;">
                                        <?php if ($row['status'] === 'completed'): ?>
                                            <div class="action-buttons-flex">
                                                <a href="<?= $receiptUrl ?>" class="btn-icon-sm btn-view" target="_blank"
                                                    title="عرض الإيصال">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn-icon-sm btn-pdf" title="تحميل كملف PDF"
                                                    onclick="downloadSilentPDF('<?= $amtFormatted ?>', '<?= $serialSafe ?>', '<?= $refSafe ?>', '<?= $paymobSafe ?>', '<?= $dateSafe ?>', this)">
                                                    <i class="fa-solid fa-file-pdf"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="btn-receipt disabled">
                                                <i class="fa-solid fa-ban"></i> غير متاح
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results">
                                <td colspan="7"><i class="fa-solid fa-filter-circle-xmark"></i>
                                    <p style="font-weight:800;font-size:1.05rem;margin:0 0 6px;">لا توجد مدفوعات مطابقة</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('paymentsPagination'); ?>
        </div>
    </div>

    <div id="hidden-pdf-template">
        <div id="pdf-receipt-content" class="pdf-receipt-box">
            <div class="pdf-success-badge"><i class="fa-solid fa-circle-check"></i> عملية دفع ناجحة</div>
            <h2>إيصال إلكتروني</h2>
            <div class="pdf-amount" id="pdf-amount">0.00 ج.م</div>
            <div class="pdf-details">
                <div class="pdf-row"><span class="lbl">رقم التسلسل (Serial):</span><span class="val"
                        id="pdf-serial">IRB-000</span></div>
                <div class="pdf-row"><span class="lbl">المرجع المالي:</span><span class="val"
                        id="pdf-ref">REF-000</span></div>
                <div class="pdf-row"><span class="lbl">رقم أمر الدفع (Paymob):</span><span class="val"
                        id="pdf-paymob">000000</span></div>
                <div class="pdf-row"><span class="lbl">التاريخ والوقت:</span><span class="val" id="pdf-date"
                        dir="ltr">2026-01-01</span></div>
            </div>
        </div>
    </div>


    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
        (function () {
            window.IRBTableTools.init({
                searchInputId: 'searchPayments', statusFilterId: 'statusFilter', resetButtonId: 'resetFilters',
                sortHeaderId: 'dateSortHeader', sortIconId: 'dateSortIcon',
                resultsCountId: 'resultsCount', tableBodyId: 'paymentsTableBody', noResultsRowId: 'noResultsRow',
                paginationContainerId: 'paymentsPagination', pageSize: 10, defaultSort: 'desc',
            });
        })();
        function downloadSilentPDF(amount, serial, ref, paymobId, date, btnElement) {
            const originalIcon = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            btnElement.style.pointerEvents = 'none';

            // 1. Build the HTML string. 
            // 🔥 THE TRICK: We wrap the RTL receipt inside a fixed-width LTR container.
            // This completely destroys the html2canvas RTL offset bug.
            const receiptHTML = `
                <div style="width: 800px; direction: ltr; background: white; padding: 20px;">
                    <div style="direction: rtl; font-family: 'Cairo', sans-serif; background: #ffffff; padding: 40px; text-align: center; color: #2c3e50; border: 2px solid #ecf0f1; border-radius: 16px; width: 650px; margin: 0 auto;">
                        <div style="display: inline-block; background: #d5f4e6; color: #27ae60; padding: 12px 24px; border-radius: 30px; font-size: 1.2rem; font-weight: 700; margin-bottom: 25px;">
                            عملية دفع ناجحة ✓
                        </div>
                        <h2 style="margin: 0; font-size: 2rem; color: #1a252f; font-weight: 800;">إيصال إلكتروني</h2>
                        <div style="font-size: 3rem; font-weight: 800; color: #1abc9c; margin: 15px 0 30px;">${amount}</div>
                        
                        <div style="text-align: right; border-top: 3px dashed #bdc3c7; padding-top: 30px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2rem;">
                                <span style="color: #7f8c8d; font-weight: 700;">رقم التسلسل (Serial):</span>
                                <span style="color: #2c3e50; font-weight: 800; font-family: monospace;">${serial}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2rem;">
                                <span style="color: #7f8c8d; font-weight: 700;">المرجع المالي:</span>
                                <span style="color: #2c3e50; font-weight: 800; font-family: monospace;">${ref}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2rem;">
                                <span style="color: #7f8c8d; font-weight: 700;">رقم أمر الدفع (Paymob):</span>
                                <span style="color: #2c3e50; font-weight: 800; font-family: monospace;">${paymobId}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 1.2rem;">
                                <span style="color: #7f8c8d; font-weight: 700;">التاريخ والوقت:</span>
                                <span style="color: #2c3e50; font-weight: 800; font-family: monospace; direction: ltr;">${date}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const opt = {
                margin: 10,
                filename: 'IRB_Receipt_' + ref + '.pdf',
                image: { type: 'jpeg', quality: 1 },
                html2canvas: {
                    scale: 2,
                    useCORS: true,
                    windowWidth: 800, // The size of the "virtual screen"
                    x: 0,  // <-- CHANGE THIS: Negative moves content Right, Positive moves content Left
                    y: 600   // <-- CHANGE THIS: Negative moves content Down, Positive moves content Up
                }, jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // 2. 🔥 THE FIX: Wait for the browser to finish loading the "Cairo" font completely
            document.fonts.ready.then(() => {
                // 3. Pass the RAW STRING directly! No DOM appending, no tempDiv, no z-index bugs.
                html2pdf().set(opt).from(receiptHTML).save().then(() => {
                    btnElement.innerHTML = originalIcon;
                    btnElement.style.pointerEvents = 'auto';
                }).catch(err => {
                    console.error("PDF Error:", err);
                    btnElement.innerHTML = originalIcon;
                    btnElement.style.pointerEvents = 'auto';
                });
            });
        }

    </script>
</body>

</html>