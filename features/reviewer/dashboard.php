<?php
require_once '../../init.php';

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('reviewer');

$dbobj = new Database();
$conn = $dbobj->getconn();

require_once '../../classes/ReviewerDashboard.php';
$dashboard = new ReviewerDashboard($conn);

$reviewer_id = $_SESSION['user_id'];

// Get KPIs
$kpis = $dashboard->getKPIs($reviewer_id);

// Get Chart Data
$decisionsDist = $dashboard->getDecisionsDistribution($reviewer_id);
$monthlyReviews = $dashboard->getMonthlyReviews($reviewer_id);

// Get Pending Researches
$pendingResearches = $dashboard->getPendingResearches($reviewer_id);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المراجع | نظام إدارة الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 40px 50px;
            background: var(--bg-page);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .content-wrapper {
            width: 100%;
            max-width: 1400px;
        }

        /* Header Area */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 35px;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 20px;
        }

        .page-header-text h2 {
            color: var(--primary-base);
            font-weight: 800;
            font-size: 2rem;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header-text h2 i {
            color: var(--accent-base);
            background: rgba(26, 188, 156, 0.1);
            padding: 12px;
            border-radius: var(--radius-md);
        }

        .page-header-text p {
            color: var(--text-muted);
            font-size: 1.05rem;
            margin: 0;
            font-weight: 600;
        }

        /* KPIs Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 25px;
            position: relative;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-base);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.4s ease;
        }

        .kpi-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: transparent;
        }

        .kpi-card:hover::before { transform: scaleX(1); }

        /* KPI Colors */
        .kpi-card.total::before { background: var(--primary-base); }
        .kpi-card.pending::before { background: var(--warning-base); }
        .kpi-card.mod::before { background: #f39c12; }
        .kpi-card.completed::before { background: var(--success-base); }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .kpi-title {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        .kpi-icon {
            font-size: 2rem;
            opacity: 0.8;
            margin-right: 10px;
        }

        .kpi-card.total .kpi-icon { color: var(--primary-base); }
        .kpi-card.pending .kpi-icon { color: var(--warning-base); }
        .kpi-card.mod .kpi-icon { color: #f39c12; }
        .kpi-card.completed .kpi-icon { color: var(--success-base); }

        .kpi-value {
            font-size: 3.2rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            line-height: 1;
        }

        /* Charts Grid */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 1200px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        .chart-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
        }

        .chart-title {
            margin: 0 0 25px 0;
            font-size: 1.3rem;
            color: var(--primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
        }

        .chart-title i {
            color: var(--accent-base);
            font-size: 1.4rem;
        }

        .chart-container {
            position: relative;
            height: 320px;
            width: 100%;
            flex: 1;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border-dark);
            margin-bottom: 15px;
            opacity: 0.6;
        }

        /* Table Styling from assigned_reserches.php */
        .data-card {
            background: var(--bg-surface);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            margin-top: 25px;
            width: 100%;
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
            color: var(--status-pending-text);
            background: var(--status-pending-bg);
            border: 1px solid var(--status-pending-border);
        }

        .status-badge.needs_modification {
            color: var(--status-modification-text);
            background: var(--status-modification-bg);
            border: 1px solid var(--status-modification-border);
        }

        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-main {
            font-weight: 700;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .date-time {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent-base);
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: var(--transition-smooth);
            border: none;
        }

        .btn-action:hover {
            background: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(26, 188, 156, 0.3);
        }

        .table-wrap {
            overflow-x: auto;
        }

    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <div class="content-wrapper">
            
            <div class="page-header">
                <div class="page-header-text">
                    <h2><i class="fa-solid fa-user-doctor"></i> لوحة المراجع</h2>
                    <p>مرحباً بك في لوحة المراجع. هنا يمكنك متابعة المهام المسندة إليك وإحصائيات مراجعاتك.</p>
                </div>
            </div>

            <!-- KPIs Section -->
            <div class="kpi-grid">
                <div class="kpi-card total">
                    <div class="kpi-header">
                        <h3 class="kpi-title">إجمالي الأبحاث المسندة</h3>
                        <i class="fa-solid fa-list-check kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($kpis['totalAssigned']) ?></p>
                </div>
                
                <div class="kpi-card pending">
                    <div class="kpi-header">
                        <h3 class="kpi-title">أبحاث قيد الانتظار</h3>
                        <i class="fa-solid fa-clock kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($kpis['pendingAction']) ?></p>
                </div>

                <div class="kpi-card mod">
                    <div class="kpi-header">
                        <h3 class="kpi-title">بانتظار تعديل الباحث</h3>
                        <i class="fa-solid fa-rotate-left kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($kpis['needsModification']) ?></p>
                </div>

                <div class="kpi-card completed">
                    <div class="kpi-header">
                        <h3 class="kpi-title">المراجعات المنجزة</h3>
                        <i class="fa-solid fa-check-double kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($kpis['completed']) ?></p>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="charts-grid">
                
                <!-- Decisions Chart -->
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fa-solid fa-chart-pie"></i>
                        سجل قراراتك
                    </h3>
                    <div class="chart-container">
                        <?php if(empty($decisionsDist['data'])): ?>
                            <div class="empty-state" style="padding-top:80px;">
                                <i class="fa-solid fa-chart-simple"></i>
                                <p>لا توجد بيانات لعرضها بعد</p>
                            </div>
                        <?php else: ?>
                            <canvas id="decisionsChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Monthly Reviews Chart -->
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fa-solid fa-chart-bar"></i>
                        المراجعات المنجزة شهرياً
                    </h3>
                    <div class="chart-container">
                        <?php if(empty($monthlyReviews['data'])): ?>
                            <div class="empty-state" style="padding-top:80px;">
                                <i class="fa-solid fa-chart-simple"></i>
                                <p>لا توجد مراجعات منجزة لعرضها</p>
                            </div>
                        <?php else: ?>
                            <canvas id="monthlyChart"></canvas>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Pending Researches Table (Standard Style) -->
            <div class="data-card">
                <div class="panel-title" style="margin-bottom:20px;">
                    <i class="fa-solid fa-hourglass-half"></i>
                    أبحاث تتطلب إجراءً منك
                </div>
                
                <?php if (empty($pendingResearches)): ?>
                    <div class="empty-state" style="padding: 60px 20px;">
                        <i class="fa-solid fa-clipboard-check"></i>
                        <p style="font-weight: 800; font-size: 1.3rem; margin-bottom: 5px; color: var(--primary-base);">عمل رائع!</p>
                        <p style="margin:0; font-size: 1.1rem;">لا توجد أبحاث حالياً تتطلب مراجعتك.</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="12%">رقم الملف</th>
                                    <th width="37%">بيانات البحث</th>
                                    <th width="15%">تاريخ التقديم</th>
                                    <th width="16%">حالة المراجعة</th>
                                    <th width="20%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingResearches as $app): 
                                    $isRedacted = ($app['principal_investigator'] === "معلومات محجوبة");
                                    // Formatting dates similar to irb_format_arabic_date, using generic for now
                                    $dateOnly = date('Y-m-d', strtotime($app['created_at']));
                                    $timeOnly = date('h:i A', strtotime($app['created_at']));
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
                                        </td>
                                        <td>
                                            <div class="app-title"><?= htmlspecialchars($app['title']) ?></div>
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
                                                <span class="date-main"><?= htmlspecialchars($dateOnly) ?></span>
                                                <small class="date-time">
                                                    <i class="fa-regular fa-clock"></i>
                                                    <?= htmlspecialchars($timeOnly) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if($app['decision'] === 'pending'): ?>
                                                <span class="status-badge pending">
                                                    <i class="fa-solid fa-hourglass-half"></i> قيد المراجعة
                                                </span>
                                            <?php elseif($app['decision'] === 'needs_modification'): ?>
                                                <span class="status-badge needs_modification">
                                                    <i class="fa-solid fa-pen"></i> يحتاج تعديل
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="review_form.php?application_id=<?= $app['application_id'] ?>" class="btn-action">
                                                <i class="fa-regular fa-eye"></i> عرض ومراجعة
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    Chart.defaults.font.family = "'Cairo', sans-serif";
    Chart.defaults.color = '#7f8c8d';

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                backgroundColor: 'rgba(26, 37, 47, 0.95)',
                titleFont: { size: 14, family: "'Cairo', sans-serif", weight: 'bold' },
                bodyFont: { size: 15, family: "'Cairo', sans-serif" },
                padding: 15,
                cornerRadius: 10,
                callbacks: {
                    label: function(context) {
                        return ` العدد: ${context.parsed.y || context.parsed}`;
                    }
                }
            }
        }
    };

    // 1. Decisions Doughnut Chart
    <?php if(!empty($decisionsDist['data'])): ?>
        const ctxDoughnut = document.getElementById('decisionsChart');
        if (ctxDoughnut) {
            new Chart(ctxDoughnut.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($decisionsDist['labels']) ?>,
                    datasets: [{
                        data: <?= json_encode($decisionsDist['data']) ?>,
                        backgroundColor: <?= json_encode($decisionsDist['colors']) ?>,
                        borderColor: '#ffffff',
                        borderWidth: 3,
                        hoverOffset: 10
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 14, family: "'Cairo', sans-serif", weight: 'bold' }
                            }
                        }
                    },
                    cutout: '70%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1500
                    }
                }
            });
        }
    <?php endif; ?>

    // 2. Monthly Bar Chart
    <?php if(!empty($monthlyReviews['data'])): ?>
        const ctxBar = document.getElementById('monthlyChart');
        if (ctxBar) {
            const gradientBar = ctxBar.getContext('2d').createLinearGradient(0, 0, 0, 400);
            gradientBar.addColorStop(0, '#1abc9c'); 
            gradientBar.addColorStop(1, '#ecf5f7'); 

            new Chart(ctxBar.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode($monthlyReviews['labels']) ?>,
                    datasets: [{
                        label: 'عدد المراجعات',
                        data: <?= json_encode($monthlyReviews['data']) ?>,
                        backgroundColor: gradientBar,
                        borderRadius: 8,
                        barPercentage: 0.4
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(189, 195, 199, 0.2)',
                                drawBorder: false
                            },
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                    animation: {
                        y: { duration: 1500, easing: 'easeOutQuart' }
                    }
                }
            });
        }
    <?php endif; ?>
    </script>
</body>
</html>
