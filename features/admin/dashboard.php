<?php
require_once '../../init.php';

require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('admin');

$dbobj = new Database();
$conn = $dbobj->getconn();

require_once '../../classes/AdminDashboard.php';
require_once '../../includes/irb_helpers.php';
$dashboard = new AdminDashboard($conn);

// Get All Data
$kpis = $dashboard->getTopStats();
$statusDist = $dashboard->getStatusDistribution();
$monthlySubs = $dashboard->getMonthlySubmissions();
$paymentAnalytics = $dashboard->getPaymentAnalytics();
$deptDist = $dashboard->getDepartmentDistribution();
$reviewers = $dashboard->getReviewerPerformance();
$recentLogs = $dashboard->getRecentActivity();
$requiresAction = $dashboard->getRequiresAction();
$certStats = $dashboard->getCertificateStats();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة معلومات الإدارة | نظام إدارة الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: var(--bg-page); }

        .content {
            margin-right: 260px;
            min-height: 100vh;
            padding: 40px 30px;
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .kpi-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 25px 20px;
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
        .kpi-card.total .kpi-icon { color: var(--primary-base); }
        .kpi-card.pending::before { background: #f39c12; }
        .kpi-card.pending .kpi-icon { color: #f39c12; }
        .kpi-card.active::before { background: #3498db; }
        .kpi-card.active .kpi-icon { color: #3498db; }
        .kpi-card.payment::before { background: #e67e22; }
        .kpi-card.payment .kpi-icon { color: #e67e22; }
        .kpi-card.assign::before { background: #9b59b6; }
        .kpi-card.assign .kpi-icon { color: #9b59b6; }
        .kpi-card.review::before { background: #8e44ad; }
        .kpi-card.review .kpi-icon { color: #8e44ad; }
        .kpi-card.mod::before { background: #c0392b; }
        .kpi-card.mod .kpi-icon { color: #c0392b; }
        .kpi-card.approved::before { background: #27ae60; }
        .kpi-card.approved .kpi-icon { color: #27ae60; }
        .kpi-card.rejected::before { background: #e74c3c; }
        .kpi-card.rejected .kpi-icon { color: #e74c3c; }
        .kpi-card.students::before { background: #16a085; }
        .kpi-card.students .kpi-icon { color: #16a085; }
        .kpi-card.revs::before { background: #2980b9; }
        .kpi-card.revs .kpi-icon { color: #2980b9; }
        .kpi-card.money::before { background: #f1c40f; }
        .kpi-card.money .kpi-icon { color: #f1c40f; }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .kpi-title {
            font-size: 0.95rem;
            color: var(--text-muted);
            font-weight: 700;
            margin: 0;
            line-height: 1.4;
        }

        .kpi-icon {
            font-size: 1.8rem;
            opacity: 0.8;
            margin-right: 10px;
        }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            line-height: 1;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .kpi-value small {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Grid Layouts */
        .charts-row-1 {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .charts-row-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .charts-row-3 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .charts-row-4 {
            display: grid;
            grid-template-columns: 2fr 1.5fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Cards */
        .chart-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-light);
            display: flex;
            flex-direction: column;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--accent-base);
        }

        .chart-body {
            flex: 1;
            position: relative;
            min-height: 300px;
        }
        
        .chart-body.small-chart {
            min-height: 200px;
        }

        /* Payment Summary Card */
        .payment-summary {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .ps-item {
            background: #f8fafc;
            border-radius: var(--radius-md);
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 4px solid var(--accent-base);
        }
        .ps-item.primary { border-color: var(--primary-base); }
        .ps-item.warning { border-color: var(--warning-base); }
        .ps-label { font-size: 0.95rem; font-weight: 700; color: var(--text-muted); }
        .ps-value { font-size: 1.25rem; font-weight: 800; color: var(--text-main); }
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
        }
        .data-table th {
            padding: 12px 15px;
            background: var(--primary-base);
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-light);
            font-size: 0.95rem;
            color: var(--text-main);
            vertical-align: middle;
        }
        .data-table tr:hover td {
            background: #f8fafc;
        }

        /* Feed / List Styles */
        .feed-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .feed-item {
            display: flex;
            gap: 15px;
            align-items: flex-start;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-light);
        }
        .feed-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .feed-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary-base);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.1rem;
        }
        .feed-content { flex: 1; }
        .feed-text { font-size: 0.95rem; font-weight: 600; color: var(--text-main); margin-bottom: 4px; }
        .feed-time { font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }

        /* Action Alert Box */
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            border-radius: var(--radius-md);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert-box.danger { background: #f8d7da; border-color: #f5c2c7; }
        .alert-box.info { background: #cce5ff; border-color: #b8daff; }
        
        .alert-info h4 { margin: 0 0 5px 0; font-size: 1rem; color: #664d03; }
        .alert-box.danger .alert-info h4 { color: #842029; }
        .alert-box.info .alert-info h4 { color: #004085; }
        
        .alert-info p { margin: 0; font-size: 0.85rem; color: var(--text-muted); }
        .btn-action { background: var(--accent-base); color: white; padding: 8px 15px; border-radius: var(--radius-sm); text-decoration: none; font-size: 0.85rem; font-weight: 700; transition: 0.2s; white-space: nowrap; }
        .btn-action:hover { background: var(--primary-base); transform: translateY(-2px); }

        @media(max-width: 1200px) {
            .charts-row-1, .charts-row-2, .charts-row-4 { grid-template-columns: 1fr; }
        }
        @media(max-width: 992px) {
            .content { margin-right: 0; padding: 20px; }
            .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <div class="content-wrapper">
            <div class="page-header">
                <div class="page-header-text">
                    <h2><i class="fa-solid fa-gauge-high"></i> لوحة معلومات الإدارة</h2>
                    <p>نظرة عامة وشاملة على أداء نظام الموافقات البحثية</p>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card total">
                    <div class="kpi-header"><h3 class="kpi-title">إجمالي الأبحاث</h3><i class="fa-solid fa-folder-open kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['totalResearches'] ?></div>
                </div>
                <div class="kpi-card pending">
                    <div class="kpi-header"><h3 class="kpi-title">بانتظار التفعيل</h3><i class="fa-solid fa-hourglass-start kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['pendingAdmin'] ?></div>
                </div>
                <div class="kpi-card active">
                    <div class="kpi-header"><h3 class="kpi-title">أبحاث نشطة</h3><i class="fa-solid fa-spinner fa-spin kpi-icon" style="animation-duration: 3s;"></i></div>
                    <div class="kpi-value"><?= $kpis['activeResearches'] ?></div>
                </div>
                <div class="kpi-card payment">
                    <div class="kpi-header"><h3 class="kpi-title">بانتظار الدفع</h3><i class="fa-solid fa-credit-card kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['pendingPayment'] ?></div>
                </div>
            
                <div class="kpi-card review">
                    <div class="kpi-header"><h3 class="kpi-title">قيد المراجعة</h3><i class="fa-solid fa-microscope kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['underReview'] ?></div>
                </div>
                <div class="kpi-card students">
                    <div class="kpi-header"><h3 class="kpi-title">الطلاب المسجلين</h3><i class="fa-solid fa-user-graduate kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['totalStudents'] ?></div>
                </div>
                <div class="kpi-card revs">
                    <div class="kpi-header"><h3 class="kpi-title">إجمالي المراجعين</h3><i class="fa-solid fa-user-tie kpi-icon"></i></div>
                    <div class="kpi-value"><?= $kpis['totalReviewers'] ?></div>
                </div>
                <div class="kpi-card money">
                    <div class="kpi-header"><h3 class="kpi-title">إجمالي الإيرادات</h3><i class="fa-solid fa-coins kpi-icon"></i></div>
                    <div class="kpi-value" style="font-size:2rem;"><?= number_format($kpis['totalRevenue'], 2) ?> <small>EGP</small></div>
                </div>
            </div>

            <!-- Urgent Actions Row -->
            <?php if (!empty($requiresAction)): ?>
            <div class="charts-row-3">
                <div class="chart-card">
                    <div class="chart-header" style="border-bottom-color: #f5c2c7;">
                        <h3 class="chart-title" style="color: #842029;"><i class="fa-solid fa-triangle-exclamation" style="color:#e74c3c;"></i> أبحاث تتطلب إجراء فوري</h3>
                    </div>
                    <div class="chart-body" style="min-height: auto;">
                        <?php foreach($requiresAction as $action): 
                            $bgClass = $action['type'] == 'stale_payment' ? 'danger' : ($action['type'] == 'activation' ? 'info' : '');
                        ?>
                            <div class="alert-box <?= $bgClass ?>">
                                <div class="alert-info">
                                    <h4><?= htmlspecialchars($action['title']) ?> - رقم الطلب: <?= htmlspecialchars($action['serial']) ?></h4>
                                    <p>تاريخ التحديث الأخير: <?= date('Y/m/d H:i', strtotime($action['date'])) ?></p>
                                </div>
                                <a href="<?= htmlspecialchars($action['link']) ?>" class="btn-action">اتخاذ إجراء</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Row 1 -->
            <div class="charts-row-1">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-chart-pie"></i> توزيع حالات الأبحاث</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-chart-line"></i> معدل تقديم الأبحاث الشهري</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="monthlySubsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Row 2 -->
            <div class="charts-row-2">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-money-bill-trend-up"></i> تحليلات المدفوعات الشهرية</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="paymentsChart"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-wallet"></i> ملخص الإيرادات</h3>
                    </div>
                    <div class="chart-body" style="min-height: auto; display: flex; flex-direction: column; justify-content: center;">
                        <div class="payment-summary">
                            <div class="ps-item primary">
                                <span class="ps-label">الدفعة الأولى (تقديم)</span>
                                <span class="ps-value"><?= number_format($paymentAnalytics['summary']['totalFirstAmount']) ?> ج.م <small style="font-size:0.8rem; color:#94a3b8;">(<?= $paymentAnalytics['summary']['totalFirstCount'] ?>)</small></span>
                            </div>
                            <div class="ps-item">
                                <span class="ps-label">الدفعة الثانية (مراجعة)</span>
                                <span class="ps-value"><?= number_format($paymentAnalytics['summary']['totalSecondAmount']) ?> ج.م <small style="font-size:0.8rem; color:#94a3b8;">(<?= $paymentAnalytics['summary']['totalSecondCount'] ?>)</small></span>
                            </div>
                            <div class="ps-item primary" style="background:#f0fdfa; border-color:#0d9488;">
                                <span class="ps-label" style="color:#0f766e;">إجمالي المحصل</span>
                                <span class="ps-value" style="color:#0f766e;"><?= number_format($paymentAnalytics['summary']['totalRevenue']) ?> ج.م</span>
                            </div>
                            <div class="ps-item warning">
                                <span class="ps-label">مدفوعات قيد الانتظار</span>
                                <span class="ps-value"><?= $paymentAnalytics['summary']['pendingCount'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Depts & Certs -->
            <div class="charts-row-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-building-columns"></i> الأبحاث حسب الكلية والقسم</h3>
                    </div>
                    <div class="chart-body">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-certificate"></i> إحصائيات الشهادات (<?= $certStats['total'] ?> إجمالي)</h3>
                    </div>
                    <div class="chart-body small-chart" style="margin-bottom: 20px;">
                        <canvas id="certChart"></canvas>
                    </div>
                    <div class="feed-list" style="border-top: 1px solid var(--border-light); padding-top: 15px;">
                        <h4 style="font-size:0.9rem; color:var(--text-muted); margin-bottom:10px;">أحدث الشهادات المصدرة</h4>
                        <?php if (empty($certStats['recent'])): ?>
                            <p style="text-align:center; color:var(--text-muted); font-size:0.9rem;">لا توجد شهادات مصدرة بعد</p>
                        <?php else: ?>
                            <?php foreach($certStats['recent'] as $cert): ?>
                                <div class="feed-item">
                                    <div class="feed-icon"><i class="fa-solid fa-award"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-text">شهادة للبحث <?= htmlspecialchars($cert['serial_number']) ?></div>
                                        <div class="feed-time"><i class="fa-regular fa-clock"></i> <?= date('Y/m/d', strtotime($cert['issued_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Row 5: Table and Activity -->
            <div class="charts-row-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title"><i class="fa-solid fa-ranking-star"></i> أداء المراجعين وعبء العمل</h3>
                    </div>
                    <div class="chart-body" style="min-height: auto;">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>المراجع</th>
                                        <th>إجمالي المسند</th>
                                        <th>العبء الحالي</th>
                                        <th>مقبول</th>
                                        <th>مرفوض</th>
                                        <th>تعديلات</th>
                                        <th>متوسط وقت الرد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reviewers as $rev): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($rev['full_name']) ?></strong></td>
                                            <td><?= $rev['total_assigned'] ?></td>
                                            <td><span style="background:var(--accent-base); color:white; padding:2px 8px; border-radius:10px; font-weight:bold;"><?= $rev['workload'] ?></span></td>
                                            <td style="color:#27ae60; font-weight:bold;"><?= $rev['approved'] ?></td>
                                            <td style="color:#e74c3c; font-weight:bold;"><?= $rev['rejected'] ?></td>
                                            <td style="color:#f39c12; font-weight:bold;"><?= $rev['modifications'] ?></td>
                                            <td><?= $rev['avg_days'] ? $rev['avg_days'] . ' يوم' : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="chart-card">
                    <div class="chart-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="chart-title" style="margin:0;"><i class="fa-solid fa-bolt"></i> سجل النشاطات الحديثة</h3>
                        <a href="/irb-digital-system/features/admin/system_logs.php" style="font-size:0.85rem; font-weight:700; color:var(--primary-base); text-decoration:none; display:flex; align-items:center; gap:5px;">
                            عرض الكل <i class="fa-solid fa-arrow-left"></i>
                        </a>
                    </div>
                    <div class="chart-body" style="min-height: auto;">
                        <ul class="feed-list">
                            <?php 
                            $displayedLogs = array_slice($recentLogs, 0, 5);
                            foreach ($displayedLogs as $log): 
                            ?>
                                <li class="feed-item">
                                    <div class="feed-icon"><i class="fa-solid fa-info"></i></div>
                                    <div class="feed-content">
                                        <div class="feed-text"><?= htmlspecialchars($log['action']) ?></div>
                                        <div class="feed-time">
                                            <i class="fa-solid fa-user" style="font-size:0.7rem;"></i> <?= htmlspecialchars($log['full_name'] ?? 'النظام') ?>
                                            &bull; <i class="fa-regular fa-clock"></i> <?= date('Y/m/d H:i', strtotime($log['created_at'])) ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Common Chart Defaults
        Chart.defaults.font.family = "'Cairo', sans-serif";
        Chart.defaults.color = '#64748b';
        Chart.defaults.scale.grid.color = '#f1f5f9';

        const chartColors = [
            '#1abc9c', '#3498db', '#9b59b6', '#34495e', '#f1c40f', 
            '#e67e22', '#e74c3c', '#2ecc71', '#16a085', '#2980b9'
        ];

        // 1. Status Distribution (Donut)
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($statusDist['labels']) ?>,
                datasets: [{
                    data: <?= json_encode($statusDist['data']) ?>,
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', labels: { font: { size: 13, weight: '600' } } }
                },
                cutout: '65%'
            }
        });

        // 2. Monthly Submissions (Line)
        new Chart(document.getElementById('monthlySubsChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlySubs['labels']) ?>,
                datasets: [{
                    label: 'عدد الأبحاث المقدمة',
                    data: <?= json_encode($monthlySubs['data']) ?>,
                    borderColor: '#1abc9c',
                    backgroundColor: 'rgba(26, 188, 156, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#1abc9c',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // 3. Payment Analytics (Bar)
        new Chart(document.getElementById('paymentsChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($paymentAnalytics['chart']['labels']) ?>,
                datasets: [
                    {
                        label: 'الدفعة الأولى',
                        data: <?= json_encode($paymentAnalytics['chart']['initialData']) ?>,
                        backgroundColor: '#3498db',
                        borderRadius: 4
                    },
                    {
                        label: 'الدفعة الثانية',
                        data: <?= json_encode($paymentAnalytics['chart']['secondData']) ?>,
                        backgroundColor: '#2ecc71',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { family: "'Cairo', sans-serif" } } } },
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true }
                }
            }
        });

        // 4. Department Distribution (Horizontal Bar)
        new Chart(document.getElementById('deptChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($deptDist['labels']) ?>,
                datasets: [{
                    label: 'عدد الأبحاث',
                    data: <?= json_encode($deptDist['data']) ?>,
                    backgroundColor: '#9b59b6',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });

        // 5. Monthly Certificates (Line/Bar)
        new Chart(document.getElementById('certChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthlySubs['labels']) ?>,
                datasets: [{
                    label: 'الشهادات المصدرة',
                    data: <?= json_encode($certStats['monthlyData']) ?>,
                    backgroundColor: '#f39c12',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>
