<aside class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> الرئيسية</a>
        </li>

        <?php if($_SESSION['role'] === 'student'): ?>
            <li><a href="/irb-digital-system/features/student/apply.php"><i class="fa-solid fa-file-circle-plus"></i> تقديم بحث جديد</a></li>
            <li><a href="my_applications.php"><i class="fa-solid fa-folder-open"></i> أبحاثي</a></li>
        <?php endif; ?>

        <?php if($_SESSION['role'] === 'admin'): ?>
            <li><a href="pending_applications.php"><i class="fa-solid fa-clock"></i> طلبات قيد المراجعة</a></li>
            <li><a href="assign_reviewers.php"><i class="fa-solid fa-user-check"></i> تعيين المراجعين</a></li>
        <?php endif; ?>

        <?php if($_SESSION['role'] === 'sample_officer'): ?>
            <li><a href="sample_requests.php"><i class="fa-solid fa-calculator"></i> حساب حجم العينة</a></li>
        <?php endif; ?>

        <?php if($_SESSION['role'] === 'reviewer'): ?>
            <li><a href="assigned_reviews.php"><i class="fa-solid fa-glasses"></i> الأبحاث المسندة (Blind Review)</a></li>
        <?php endif; ?>

        <?php if($_SESSION['role'] === 'manager'): ?>
            <li><a href="final_approvals.php"><i class="fa-solid fa-stamp"></i> الاعتمادات النهائية</a></li>
            <li><a href="system_reports.php"><i class="fa-solid fa-chart-line"></i> الإحصائيات والتقارير</a></li>
        <?php endif; ?>
    </ul>
</aside>