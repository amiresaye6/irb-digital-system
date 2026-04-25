<?php
require_once __DIR__ . '/../../init.php';
Auth::checkRole(['sample_officer']);

require_once __DIR__ . '/../../includes/irb_helpers.php';
require_once __DIR__ . '/../../includes/pagination.php';

$db = new Database();
$conn = $db->getconn();
$user = Auth::user();
$officer_id = $user['id'];

// Fetch the history of applications calculated by this specific officer
$query = "SELECT a.id, a.serial_number, a.title, a.principal_investigator, a.current_stage, 
                 s.calculated_size, s.sample_amount, s.notes, s.created_at as calculation_date
          FROM applications a 
          JOIN sample_sizes s ON a.id = s.application_id 
          WHERE s.sampler_id = ? 
          ORDER BY s.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $officer_id);
$stmt->execute();
$result = $stmt->get_result();
$history = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$stageLabels = [
    'awaiting_sample_payment' => ['بانتظار دفع العينة', 'fa-credit-card', 'pending'],
    'under_review' => ['قيد المراجعة العلمية', 'fa-microscope', 'pending'],
    'approved_by_reviewer' => ['مقبول من المراجع', 'fa-user-check', 'approved'],
    'approved' => ['معتمد نهائياً', 'fa-circle-check', 'approved'],
    'rejected' => ['مرفوض', 'fa-circle-xmark', 'rejected'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل العينات المنجزة</title>
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
            transition: var(--transition-smooth);
        }

        .btn-reset:hover {
            background: var(--primary-light);
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
            white-space: nowrap;
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

        .actions-cell {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit {
            background: var(--bg-page);
            color: var(--warning-base);
            border: 1px solid var(--warning-base);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .btn-edit:hover {
            background: var(--warning-base);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-view {
            background: var(--primary-light);
            color: var(--primary-base);
            border: 1px solid var(--primary-base);
            text-decoration: none;
            padding: 6px 12px;
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
            font-size: 0.8rem;
            transition: all 0.3s;
            cursor: pointer;
        }

        .btn-view:hover {
            background: var(--primary-base);
            color: white;
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

        /* Modal Styles */
        .irb-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(44, 62, 80, 0.5);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .irb-modal-overlay.is-open {
            display: flex;
            opacity: 1;
        }

        .irb-modal {
            background: #fff;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            transform: translateY(-30px);
            transition: transform 0.3s ease;
            overflow: hidden;
            border: 1px solid var(--border-light);
        }

        .irb-modal-overlay.is-open .irb-modal {
            transform: translateY(0);
        }

        .irb-modal-header {
            background: var(--primary-light);
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .irb-modal-title {
            margin: 0;
            color: var(--primary-base);
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .irb-modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
            padding: 0;
            line-height: 1;
        }

        .irb-modal-close:hover {
            color: var(--alert-base);
        }

        .irb-modal-body {
            padding: 24px;
        }

        .modal-divider {
            height: 1px;
            background: var(--border-light);
            margin: 20px 0;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .modal-item label {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .modal-item div {
            font-weight: 800;
            color: var(--text-main);
            font-size: 1rem;
            background: var(--bg-page);
            padding: 10px 14px;
            border-radius: var(--radius-md);
            border: 1px solid rgba(189, 195, 199, 0.5);
            line-height: 1.4;
        }

        @media(max-width:992px) {
            .content {
                margin-right: 0;
            }

            .toolbar-card {
                grid-template-columns: 1fr;
            }

            .modal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-clock-rotate-left"></i> سجل العينات المنجزة</h2>
        <p class="page-subtitle">قائمة بالأبحاث التي قمت بحساب حجم عيناتها. يمكنك مراجعة الإدخالات أو تعديلها (قبل
            الدفع).</p>

        <?php if (isset($_SESSION['success'])): ?>
            <div
                style="width:100%; background:var(--status-approved-bg); color:var(--status-approved-text); padding:12px; border-radius:var(--radius-md); margin-bottom:15px; font-weight:700;">
                <i class="fa-solid fa-check-circle"></i> <?= $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="toolbar-card">
            <div class="toolbar-meta" style="grid-column:1/-1;">
                <h3 class="toolbar-title"><i class="fa-solid fa-sliders"></i> البحث في السجل</h3>
                <button type="button" id="resetFilters" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> إعادة
                    ضبط</button>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="searchApplications"><i class="fa-solid fa-magnifying-glass"></i> ابحث
                    برقم الملف أو الباحث</label>
                <input type="text" id="searchApplications" class="search-input" placeholder="اكتب للبحث...">
            </div>
            <div class="filter-group">
                <label class="filter-label" for="statusFilter"><i class="fa-solid fa-filter"></i> حالة الملف
                    الحالية</label>
                <select id="statusFilter" class="filter-select irb-select irb-select--compact">
                    <option value="all">الكل</option>
                    <option value="pending">قيد استكمال الإجراءات</option>
                    <option value="approved">مقبول / معتمد</option>
                    <option value="rejected">مرفوض</option>
                </select>
            </div>
        </div>

        <div class="results-bar">
            <div class="results-chip"><i class="fa-solid fa-check-double"></i> <span>إجمالي ما أنجزته: <strong
                        id="resultsCount"><?= count($history) ?></strong></span></div>
        </div>

        <div class="data-card">
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="12%">رقم الملف</th>
                            <th width="28%">عنوان البحث والباحث</th>
                            <th width="15%">بيانات العينة</th>
                            <th width="15%">تاريخ الإنجاز</th>
                            <th width="10%" style="display:none;" id="dateSortHeader"><span id="dateSortIcon"></span>
                            </th>
                            <th width="15%">الحالة الحالية</th>
                            <th width="15%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="applicationsTableBody">
                        <?php if (empty($history)): ?>
                            <tr>
                                <td colspan="6">
                                    <div style="text-align:center; padding:40px; color:var(--text-muted);"><i
                                            class="fa-solid fa-folder-open"
                                            style="font-size:3rem; opacity:0.5; margin-bottom:10px;"></i>
                                        <p style="font-weight:700;font-size:1.1rem;">لم تقم بحساب أي عينات حتى الآن.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($history as $app):
                                $stage = $stageLabels[$app['current_stage']] ?? ['غير معروف', 'fa-question', 'pending'];
                                $searchBlob = strtolower($app['serial_number'] . ' ' . $app['title'] . ' ' . $app['principal_investigator']);
                                $canEdit = ($app['current_stage'] === 'awaiting_sample_payment');
                                ?>
                                <tr data-search="<?= htmlspecialchars($searchBlob) ?>" data-status="<?= $stage[2] ?>"
                                    data-date="<?= htmlspecialchars($app['calculation_date']) ?>">
                                    <td><span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span></td>
                                    <td>
                                        <div style="font-weight:800; color:var(--text-main); margin-bottom:4px;">
                                            <?= htmlspecialchars($app['title']) ?></div>
                                        <div style="font-size:0.85rem; color:var(--text-muted); font-weight:700;"><i
                                                class="fa-solid fa-user-tie"></i>
                                            <?= htmlspecialchars($app['principal_investigator']) ?></div>
                                    </td>
                                    <td>
                                        <div style="font-weight:800; color:var(--accent-dark);"><i
                                                class="fa-solid fa-users"></i> <?= htmlspecialchars($app['calculated_size']) ?>
                                        </div>
                                        <div style="font-size:0.85rem; color:var(--text-muted); font-weight:700;"><i
                                                class="fa-solid fa-money-bill-wave"></i>
                                            <?= htmlspecialchars($app['sample_amount']) ?> ج.م</div>
                                    </td>
                                    <td>
                                        <div class="date-cell">
                                            <span><?= htmlspecialchars(irb_format_arabic_date($app['calculation_date'])) ?></span>
                                            <small><i class="fa-regular fa-clock"></i>
                                                <?= htmlspecialchars(irb_format_arabic_time($app['calculation_date'])) ?></small>
                                        </div>
                                    </td>
                                    <td><span class="status-badge <?= $stage[2] ?>"><i class="fa-solid <?= $stage[1] ?>"></i>
                                            <?= $stage[0] ?></span></td>
                                    <td>
                                        <div class="actions-cell">
                                            <button type="button" class="btn-view"
                                                data-serial="<?= htmlspecialchars($app['serial_number']) ?>"
                                                data-title="<?= htmlspecialchars($app['title']) ?>"
                                                data-pi="<?= htmlspecialchars($app['principal_investigator']) ?>"
                                                data-size="<?= htmlspecialchars($app['calculated_size']) ?>"
                                                data-amount="<?= htmlspecialchars($app['sample_amount']) ?>"
                                                data-notes="<?= htmlspecialchars($app['notes']) ?>"
                                                data-date="<?= htmlspecialchars(irb_format_arabic_date($app['calculation_date']) . ' - ' . irb_format_arabic_time($app['calculation_date'])) ?>">
                                                <i class="fa-solid fa-eye"></i> استعراض
                                            </button>

                                            <?php if ($canEdit): ?>
                                                <a href="process_application.php?id=<?= $app['id'] ?>" class="btn-edit"><i
                                                        class="fa-solid fa-pen-to-square"></i> تعديل</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="noResultsRow" class="no-results">
                                <td colspan="6"><i class="fa-solid fa-filter-circle-xmark" style="font-size:2rem;"></i>
                                    <p style="font-weight:800;">لا توجد نتائج مطابقة لبحثك</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php irb_render_table_pagination('applicationsPagination'); ?>
        </div>
    </div>

    <div id="viewModal" class="irb-modal-overlay">
        <div class="irb-modal">
            <div class="irb-modal-header">
                <h3 class="irb-modal-title"><i class="fa-solid fa-microscope"></i> تفاصيل العينة المحسوبة</h3>
                <button type="button" class="irb-modal-close" id="closeModalBtn" aria-label="إغلاق النافذة"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="irb-modal-body">
                <div class="modal-grid">
                    <div class="modal-item">
                        <label>رقم الملف</label>
                        <div id="modalSerial" style="color:var(--primary-base);"></div>
                    </div>
                    <div class="modal-item">
                        <label>الباحث الرئيسي</label>
                        <div id="modalPI"></div>
                    </div>
                    <div class="modal-item" style="grid-column:1/-1;">
                        <label>عنوان البحث</label>
                        <div id="modalTitle"></div>
                    </div>
                </div>

                <div class="modal-divider"></div>

                <div class="modal-grid">
                    <div class="modal-item">
                        <label>حجم العينة المُدخل</label>
                        <div style="color:var(--accent-dark);"><i class="fa-solid fa-users"></i> <span
                                id="modalSize"></span></div>
                    </div>
                    <div class="modal-item">
                        <label>التكلفة المالية</label>
                        <div style="color:var(--success-base);"><i class="fa-solid fa-money-bill-wave"></i> <span
                                id="modalAmount"></span></div>
                    </div>
                    <div class="modal-item" style="grid-column:1/-1;">
                        <label>الملاحظات المُرفقة</label>
                        <div id="modalNotes" style="font-weight:600;"></div>
                    </div>
                    <div class="modal-item" style="grid-column:1/-1;">
                        <label>تاريخ الإدخال</label>
                        <div id="modalDate" style="color:var(--text-muted); font-size:0.9rem;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/irb-digital-system/assets/js/irb-table-tools.js"></script>
    <script>
        (function () {
            // Init Search/Filter
            window.IRBTableTools.init({
                searchInputId: 'searchApplications', statusFilterId: 'statusFilter', resetButtonId: 'resetFilters',
                sortHeaderId: 'dateSortHeader', sortIconId: 'dateSortIcon',
                resultsCountId: 'resultsCount', tableBodyId: 'applicationsTableBody', noResultsRowId: 'noResultsRow',
                paginationContainerId: 'applicationsPagination', pageSize: 10, defaultSort: 'desc',
            });

            // Modal Logic
            const modalOverlay = document.getElementById('viewModal');
            const closeBtn = document.getElementById('closeModalBtn');
            const viewButtons = document.querySelectorAll('.btn-view');

            const modalSerial = document.getElementById('modalSerial');
            const modalPI = document.getElementById('modalPI');
            const modalTitle = document.getElementById('modalTitle');
            const modalSize = document.getElementById('modalSize');
            const modalAmount = document.getElementById('modalAmount');
            const modalNotes = document.getElementById('modalNotes');
            const modalDate = document.getElementById('modalDate');

            viewButtons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();

                    // Populate data
                    modalSerial.textContent = btn.dataset.serial;
                    modalPI.textContent = btn.dataset.pi;
                    modalTitle.textContent = btn.dataset.title;
                    modalSize.textContent = btn.dataset.size;
                    modalAmount.textContent = btn.dataset.amount + ' ج.م';

                    const notes = btn.dataset.notes.trim();
                    modalNotes.textContent = notes ? notes : 'لا توجد ملاحظات مسجلة.';

                    modalDate.textContent = btn.dataset.date;

                    // Open Modal
                    modalOverlay.classList.add('is-open');
                });
            });

            const closeModal = () => {
                modalOverlay.classList.remove('is-open');
            };

            closeBtn.addEventListener('click', closeModal);
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) closeModal();
            });

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modalOverlay.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();
    </script>
</body>

</html>