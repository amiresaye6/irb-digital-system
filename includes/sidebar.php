<?php
$currentSidebarPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function irb_sidebar_is_active($paths)
{
    global $currentSidebarPath;
    foreach ((array) $paths as $path) {
        if ($currentSidebarPath === $path) {
            return true;
        }
    }
    return false;
}
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fa-solid fa-microscope"></i>
            <span>IRB</span>
        </div>
        <p class="sidebar-subtitle">منظومة الموافقات البحثية</p>
    </div>

    <ul class="sidebar-menu">
        <!-- Universal Links -->
        <li class="menu-item">
            <a href="/irb-digital-system/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/dashboard.php']) ? ' is-active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>لوحة التحكم</span>
            </a>
        </li>

        <!-- Student Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة الطالب</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/submit_application.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/submit_application.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>تقديم بحث جديد</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/my_applications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/my_applications.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>أبحاثي</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Admin Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة الإدارة</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/pending_applications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/pending_applications.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-hourglass-end"></i>
                    <span>الطلبات قيد المراجعة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/reviewer/assign_reviewers.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/reviewer/assign_reviewers.php', '/irb-digital-system/features/reviewer/assign_form.php', '/irb-digital-system/features/reviewer/submit_assignment.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-user-check"></i>
                    <span>تعيين المراجعين</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/admin_dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/admin_dashboard.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-gauge"></i>
                    <span>لوحة معلومات الإدارة</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Sample Officer Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'sample_officer'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة ضابط العينات</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/sample_requests.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/sample_requests.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-calculator"></i>
                    <span>حساب حجم العينة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/sample_statistics.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/sample_statistics.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-chart-bar"></i>
                    <span>إحصائيات العينات</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Reviewer Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'reviewer'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة المراجع</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/assigned_reviews.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/assigned_reviews.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-microscope"></i>
                    <span>الأبحاث المسندة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/my_reviews.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/my_reviews.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>مراجعاتي</span>
                </a>
            </li>
        <?php endif; ?>

        <!-- Manager Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة المدير</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/final_approvals.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/final_approvals.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-stamp"></i>
                    <span>الاعتمادات النهائية</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/system_reports.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/system_reports.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>التقارير والإحصائيات</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fa-solid fa-user-circle"></i>
            <span class="user-name"><?= isset($_SESSION['full_name']) ? htmlspecialchars(substr($_SESSION['full_name'], 0, 20)) : 'المستخدم' ?></span>
        </div>
        <a href="/irb-digital-system/logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
    </div>
</aside>

<style>
    .sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: 260px;
        height: 100vh;
        background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-base) 100%);
        border-right: 3px solid var(--accent-base);
        display: flex;
        flex-direction: column;
        z-index: 100;
        box-shadow: 2px 0 12px rgba(44, 62, 80, 0.15);
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 25px 18px;
        border-bottom: 2px solid rgba(26, 188, 156, 0.3);
        background: linear-gradient(135deg, rgba(26, 188, 156, 0.1) 0%, transparent 100%);
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .sidebar-logo i {
        font-size: 1.6rem;
        color: var(--accent-base);
    }

    .sidebar-logo span {
        font-size: 1.3rem;
        font-weight: 800;
        color: #ffffff;
    }

    .sidebar-subtitle {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 600;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
        overflow-y: auto;
    }

    .menu-category {
        padding: 20px 18px 10px 18px;
        margin-top: 10px;
    }

    .category-label {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: block;
    }

    .sidebar .sidebar-menu .menu-item {
        margin-bottom: 3px;
        padding: 0 8px;
    }

    .sidebar .sidebar-menu li .menu-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 11px 13px;
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.89rem;
        transition: all var(--transition-smooth);
        border-radius: var(--radius-md) 0 0 var(--radius-md);
        border-left: 4px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .sidebar .sidebar-menu li .menu-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, rgba(26, 188, 156, 0) 0%, rgba(26, 188, 156, 0.15) 100%);
        opacity: 0;
        transition: opacity var(--transition-smooth);
    }

    .sidebar .sidebar-menu li .menu-link i {
        font-size: 1.02rem;
        width: 18px;
        text-align: center;
        color: rgba(255, 255, 255, 0.8);
        transition: all var(--transition-smooth);
        position: relative;
        z-index: 1;
    }

    .sidebar .sidebar-menu li .menu-link span {
        position: relative;
        z-index: 1;
    }

    .sidebar .sidebar-menu li .menu-link:hover {
        background-color: rgba(26, 188, 156, 0.2);
        color: rgba(255, 255, 255, 0.96);
        border-left: 4px solid var(--accent-base);
        transform: translateX(4px);
    }

    .sidebar .sidebar-menu li .menu-link.is-active {
        background-color: rgba(26, 188, 156, 0.2);
        color: rgba(255, 255, 255, 0.96);
        border-left: 4px solid var(--accent-base);
        transform: translateX(4px);
    }

    .sidebar .sidebar-menu li .menu-link:hover::before {
        opacity: 1;
    }

    .sidebar .sidebar-menu li .menu-link.is-active::before {
        opacity: 1;
    }

    .sidebar .sidebar-menu li .menu-link:hover span,
    .sidebar .sidebar-menu li .menu-link.is-active span {
        color: rgba(255, 255, 255, 0.96);
    }

    .sidebar .sidebar-menu li .menu-link:hover i {
        color: var(--accent-base);
        transform: scale(1.15);
    }

    .sidebar .sidebar-menu li .menu-link.is-active i {
        color: var(--accent-base);
        transform: scale(1.1);
    }

    .sidebar-footer {
        padding: 20px 18px;
        border-top: 2px solid rgba(26, 188, 156, 0.3);
        background: linear-gradient(180deg, transparent 0%, rgba(26, 188, 156, 0.05) 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }

    .user-info i {
        font-size: 1.6rem;
        color: var(--accent-base);
        flex-shrink: 0;
    }

    .user-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: #ffffff;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .logout-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        background: rgba(26, 188, 156, 0.2);
        color: var(--accent-base);
        text-decoration: none;
        transition: all var(--transition-smooth);
        flex-shrink: 0;
    }

    .logout-btn:hover {
        background: var(--accent-base);
        color: white;
        transform: scale(1.1);
    }

    .logout-btn i {
        font-size: 1.1rem;
    }

    /* Scrollbar styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(26, 188, 156, 0.3);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: var(--accent-base);
    }
</style>
