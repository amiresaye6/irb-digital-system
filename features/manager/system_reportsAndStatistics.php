<?php
require_once '../../classes/Database.php';

$db = new Database();
$conn = $db->getconn();

$approved_count = $conn->query("SELECT COUNT(*) as total FROM applications WHERE current_stage = 'approved'")->fetch_assoc()['total'];

$rejected_count = $conn->query("SELECT COUNT(*) as total FROM applications WHERE current_stage = 'rejected'")->fetch_assoc()['total'];

$cert_count = $conn->query("SELECT COUNT(*) as total FROM certificates")->fetch_assoc()['total'];

$pending_count = $conn->query("SELECT COUNT(*) as total FROM applications WHERE current_stage IN ('under_review', 'approved_by_reviewer')")->fetch_assoc()['total'];

$total_apps = $approved_count + $rejected_count + $pending_count;

$performance_rate = ($total_apps > 0) ? round((($approved_count + $rejected_count) / $total_apps) * 100) : 0;

$acceptance_rate = ($total_apps > 0) ? round(($approved_count / $total_apps) * 100) : 0;

include '../../includes/header.php';
?>
<link rel="stylesheet" href="/irb-digital-system/assets/css/reportsAndStatistics.css">

<div class="page-header" style="margin-right: 230px; margin-top:35px">
    <h2 class="page-title"><i class="fa-solid fa-chart-line"></i> التقارير والإحصائيات</h2>
</div>

<div class="stats-container" style="margin-right: 230px;">
    <div class="stat-card">
        <div class="stat-icon bg-light-green text-green">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-content">
            <h6>الموافقات النهائية</h6>
            <h3><?= $approved_count ?></h3>
            <small><i class="fa-regular fa-clock"></i> تم التحديث مؤخراً</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-light-blue text-blue">
            <i class="fa-solid fa-certificate"></i>
        </div>
        <div class="stat-content">
            <h6>شهادات صادرة</h6>
            <h3><?= $cert_count ?></h3>
            <small><i class="fa-regular fa-clock"></i> تم التحديث مؤخراً</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-light-red text-red">
            <i class="fa-solid fa-gavel"></i>
        </div>
        <div class="stat-content">
            <h6>طلبات مرفوضة</h6>
            <h3><?= $rejected_count ?></h3>
            <small><i class="fa-regular fa-clock"></i> تم التحديث مؤخراً</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon bg-light-cyan text-cyan">
            <i class="fa-solid fa-chart-simple"></i>
        </div>
        <div class="stat-content">
            <h6>الأداء السنوي</h6>
            <h3><?= $performance_rate ?>%</h3>
            <div class="stat-progress">
                <div class="progress-bar" style="width: <?= $performance_rate ?>%"></div>
            </div>
        </div>
    </div>
</div>

<div class="chart-container" style="margin-right: 420px;">
    <div class="chart-card">
        <div class="chart-header">
            <h5>معدل القبول السنوي</h5>
            <span class="acceptance-badge"><?= $acceptance_rate ?>%</span>
        </div>
        <div class="chart-wrapper">
            <div class="chart-donut">
                <canvas id="acceptanceChart"></canvas>
                <div class="donut-center">
                    <span class="donut-value"><?= $acceptance_rate ?>%</span>
                    <span class="donut-label">موافقة</span>
                </div>
            </div>
            <ul class="chart-legend" >
                <li>
                    <span class="legend-dot bg-green"></span>
                    <span class="legend-label">قبول نهائي</span>
                    <span class="legend-value"><?= $approved_count ?></span>
                </li>
                <li>
                    <span class="legend-dot bg-red"></span>
                    <span class="legend-label">رفض نهائي</span>
                    <span class="legend-value"><?= $rejected_count ?></span>
                </li>
                <li>
                    <span class="legend-dot bg-gray"></span>
                    <span class="legend-label">قيد المراجعة</span>
                    <span class="legend-value"><?= $pending_count ?></span>
                </li>
            </ul>
        </div>
    </div>
</div>

</main>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('acceptanceChart');
    if (!ctx) return;
    
    const chartCtx = ctx.getContext('2d');
    new Chart(chartCtx, {
        type: 'doughnut',
        data: {
            labels: ['قبول نهائي', 'رفض نهائي', 'قيد المراجعة'],
            datasets: [{
                data: [<?= $approved_count ?>, <?= $rejected_count ?>, <?= $pending_count ?>],
                backgroundColor: ['#27ae60', '#e74c3c', '#95a5a6'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    rtl: true,
                    backgroundColor: 'rgba(44, 62, 80, 0.9)',
                    padding: 12,
                    borderRadius: 8,
                    titleFont: { family: "'Cairo', sans-serif", size: 14, weight: 'bold' },
                    bodyFont: { family: "'Cairo', sans-serif", size: 13 }
                }
            }
        }
    });
});
</script>