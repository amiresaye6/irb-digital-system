<?php
require_once '../../init.php';

// Ensure user is manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header('Location: /irb-digital-system/features/auth/login.php');
    exit;
}

$dbobj = new Database();
$conn = $dbobj->getconn();

require_once '../../classes/ManagerDashboard.php';
$dashboard = new ManagerDashboard($conn);

// Get All KPIs (No filters applied)
$kpis = $dashboard->getKPIs();
$totalResearches = $kpis['totalResearches'];
$avgApproval = $kpis['avgApproval'];
$pendingResearches = $kpis['pendingResearches'];
$approvedResearches = $kpis['approvedResearches'];
$approvalRate = $kpis['approvalRate'];
$rejectedResearches = $kpis['rejectedResearches'];
$returnedResearches = $kpis['returnedResearches'];
$totalReviewers = $kpis['totalReviewers'];

// Get Chart Data
$deptDist = $dashboard->getDepartmentDistribution(null, null);
$deptLabels = $deptDist['labels'];
$deptData = $deptDist['data'];

$workloadData = $dashboard->getReviewerWorkload();
$reviewerLabels = $workloadData['labels'];
$reviewerData = $workloadData['data'];

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدير | نظام إدارة الموافقات البحثية</title>
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
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
        .kpi-card.time::before { background: var(--accent-base); }
        .kpi-card.pending::before { background: var(--warning-base); }
        .kpi-card.rate::before { background: var(--success-base); }
        .kpi-card.approved::before { background: #27ae60; }
        .kpi-card.rejected::before { background: var(--alert-base); }
        .kpi-card.returned::before { background: #f39c12; }
        .kpi-card.reviewers::before { background: #9b59b6; }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .kpi-title {
            font-size: 1rem;
            color: var(--text-muted);
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        .kpi-icon {
            font-size: 1.8rem;
            opacity: 0.8;
            margin-right: 10px;
        }

        .kpi-card.total .kpi-icon { color: var(--primary-base); }
        .kpi-card.time .kpi-icon { color: var(--accent-base); }
        .kpi-card.pending .kpi-icon { color: var(--warning-base); }
        .kpi-card.rate .kpi-icon { color: var(--success-base); }
        .kpi-card.approved .kpi-icon { color: #27ae60; }
        .kpi-card.rejected .kpi-icon { color: var(--alert-base); }
        .kpi-card.returned .kpi-icon { color: #f39c12; }
        .kpi-card.reviewers .kpi-icon { color: #9b59b6; }

        .kpi-value {
            font-size: 2.8rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            line-height: 1;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }

        .kpi-value small {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Charts Area */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
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
        }

        .chart-title {
            margin: 0 0 30px 0;
            font-size: 1.3rem;
            color: var(--primary-dark);
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chart-title i {
            color: var(--accent-base);
            font-size: 1.5rem;
        }

        .chart-container {
            position: relative;
            height: 380px;
            width: 100%;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content">
        <div class="content-wrapper">
            
            <div class="page-header">
                <div class="page-header-text">
                    <h2><i class="fa-solid fa-chart-line"></i> لوحة الإحصائيات الشاملة</h2>
                    <p>مراقبة وتحليل أداء لجنة أخلاقيات البحث العلمي والمراجعين.</p>
                </div>
            </div>

            <!-- KPIs Section (Smaller, more cards) -->
            <div class="kpi-grid">
                <div class="kpi-card total">
                    <div class="kpi-header">
                        <h3 class="kpi-title">إجمالي الأبحاث المقدمة</h3>
                        <i class="fa-solid fa-file-contract kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($totalResearches) ?></p>
                </div>
                
                <div class="kpi-card pending">
                    <div class="kpi-header">
                        <h3 class="kpi-title">أبحاث قيد المراجعة</h3>
                        <i class="fa-solid fa-hourglass-half kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($pendingResearches) ?></p>
                </div>

                <div class="kpi-card returned">
                    <div class="kpi-header">
                        <h3 class="kpi-title">بانتظار تعديل الباحث</h3>
                        <i class="fa-solid fa-rotate-left kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($returnedResearches) ?></p>
                </div>

                <div class="kpi-card rejected">
                    <div class="kpi-header">
                        <h3 class="kpi-title">أبحاث مرفوضة</h3>
                        <i class="fa-solid fa-ban kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($rejectedResearches) ?></p>
                </div>

                <div class="kpi-card approved">
                    <div class="kpi-header">
                        <h3 class="kpi-title">الأبحاث المعتمدة</h3>
                        <i class="fa-solid fa-check-circle kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($approvedResearches) ?></p>
                </div>

                <div class="kpi-card rate">
                    <div class="kpi-header">
                        <h3 class="kpi-title">نسبة الاعتماد النهائي</h3>
                        <i class="fa-solid fa-percent kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= $approvalRate ?> <small>%</small></p>
                </div>

                <div class="kpi-card time">
                    <div class="kpi-header">
                        <h3 class="kpi-title">متوسط وقت الاعتماد</h3>
                        <i class="fa-solid fa-stopwatch kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= $avgApproval ?> <small>يوم</small></p>
                </div>

                <div class="kpi-card reviewers">
                    <div class="kpi-header">
                        <h3 class="kpi-title">المراجعين النشطين</h3>
                        <i class="fa-solid fa-user-check kpi-icon"></i>
                    </div>
                    <p class="kpi-value"><?= number_format($totalReviewers) ?></p>
                </div>
            </div>

            <!-- Charts Area -->
            <div class="charts-grid">
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fa-solid fa-chart-pie"></i>
                        التوزيع حسب القسم الأكاديمي
                    </h3>
                    <div class="chart-container">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-card">
                    <h3 class="chart-title">
                        <i class="fa-solid fa-users-viewfinder"></i>
                        عبء العمل للمراجعين
                    </h3>
                    <div class="chart-container">
                        <canvas id="workloadChart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart.js and Initialization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    Chart.defaults.font.family = "'Cairo', sans-serif";
    Chart.defaults.color = '#7f8c8d';

    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(26, 37, 47, 0.95)',
                titleFont: { size: 14, family: "'Cairo', sans-serif", weight: 'bold' },
                bodyFont: { size: 15, family: "'Cairo', sans-serif" },
                padding: 15,
                cornerRadius: 10,
                displayColors: true,
                boxPadding: 8,
                callbacks: {
                    label: function(context) {
                        return ` العدد: ${context.parsed.y || context.parsed}`;
                    }
                }
            }
        },
        animation: {
            y: { duration: 2000, easing: 'easeOutElastic' }
        }
    };

    const themeColors = [
        '#1abc9c', '#2c3e50', '#27ae60', '#f39c12', '#e74c3c', '#16a085', '#bdc3c7', '#34495e'
    ];

    // 1. Department Distribution Chart
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx) {
        new Chart(deptCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [{
                    data: <?= json_encode($deptData) ?>,
                    backgroundColor: themeColors,
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 15
                }]
            },
            options: {
                ...commonOptions,
                plugins: {
                    ...commonOptions.plugins,
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            padding: 25,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 13, family: "'Cairo', sans-serif" }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Reviewer Workload Chart
    const workloadCtxElement = document.getElementById('workloadChart');
    if (workloadCtxElement) {
        const workloadCtx = workloadCtxElement.getContext('2d');
        const gradientBar = workloadCtx.createLinearGradient(0, 0, 0, 400);
        gradientBar.addColorStop(0, '#2c3e50'); 
        gradientBar.addColorStop(1, '#ecf5f7'); 

        const gradientHover = workloadCtx.createLinearGradient(0, 0, 0, 400);
        gradientHover.addColorStop(0, '#1abc9c'); 
        gradientHover.addColorStop(1, '#d5f4f1'); 

        new Chart(workloadCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($reviewerLabels) ?>,
                datasets: [{
                    label: 'الأبحاث المسندة',
                    data: <?= json_encode($reviewerData) ?>,
                    backgroundColor: gradientBar,
                    hoverBackgroundColor: gradientHover,
                    borderRadius: 10,
                    barPercentage: 0.5
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(189, 195, 199, 0.2)',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 1,
                            font: { size: 14 }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 13, weight: '600' }
                        }
                    }
                }
            }
        });
    }
    </script>
</body>
</html>
