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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Mobile Menu Button -->
<div class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fa-solid fa-bars"></i>
</div>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fa-solid fa-microscope"></i>
            <span>IRB</span>
        </div>
        <p class="sidebar-subtitle">منظومة الموافقات البحثية</p>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                <a href="/irb-digital-system/features/student/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/dashboard.php']) ? ' is-active' : '' ?>">
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'sample_officer'):?>
                <a href="/irb-digital-system/features/sample_officer/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/sample_officer/dashboard.php']) ? ' is-active' : '' ?>">
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'):?>
                <a href="/irb-digital-system/features/admin/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/dashboard.php']) ? ' is-active' : '' ?>">
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'manager'):?>
                <a href="/irb-digital-system/features/manager/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/dashboard.php']) ? ' is-active' : '' ?>">
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'reviewer'):?>
                <a href="/irb-digital-system/features/reviewer/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/reviewer/dashboard.php']) ? ' is-active' : '' ?>">
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'):?>
                <a href="/irb-digital-system/features/admin/dashboard.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/dashboard.php']) ? ' is-active' : '' ?>">
            <?php endif; ?>
            <i class="fa-solid fa-chart-line"></i>
                <span>لوحة التحكم</span>
            </a>
        </li>

        <!-- Student Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <?php
                require_once __DIR__ . '/../classes/Applications.php';
                $sidebarAppObj = new Applications();
                $sidebarUnread = $sidebarAppObj->getUnreadNotificationCount($_SESSION['user_id']);
            ?>
            <li class="menu-category">
                <span class="category-label">منطقة الطالب</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/apply.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/apply.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    <span>تقديم بحث جديد</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/student_researches.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/student_researches.php', '/irb-digital-system/features/student/student_research_details.php', '/irb-digital-system/features/student/update_application.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>أبحاثي</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/get_certificates.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/get_certificates.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>شهاداتى</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/pending_payments.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/pending_payments.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-hourglass-half"></i>
                    <span>المدفوعات المعلقة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/payment_history.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/payment_history.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-receipt"></i>
                    <span>سجل المدفوعات</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/student/student_notifications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/student/student_notifications.php', '/irb-digital-system/features/student/notification_details.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>الإشعارات</span>
                    <?php if ($sidebarUnread > 0): ?>
                        <span style="background:var(--accent-base);color:white;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:800;margin-right:auto;"><?= $sidebarUnread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة الإدارة</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/manage_users.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/manage_users.php', '/irb-digital-system/features/admin/add_user.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>إدارة المستخدمين</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/pending_applications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/pending_applications.php']) ? ' is-active' : '' ?>">
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
                <a href="/irb-digital-system/features/admin/payments.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/payments.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-money-check-dollar"></i>
                    <span>إدارة المدفوعات</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/system_logs.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/system_logs.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>سجل النظام</span>
                </a>
            </li>
        <?php endif; ?>
        <!-- Super Admin Role Links -->
          <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة المشرف العام</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/manage_users.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/manage_users.php', '/irb-digital-system/features/admin/add_user.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>إدارة المستخدمين</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/pending_applications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/pending_applications.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-hourglass-end"></i>
                    <span>الطلبات قيد المراجعة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/payments.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/payments.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-money-check-dollar"></i>
                    <span>إدارة المدفوعات</span>
                </a>
            </li>
             <li class="menu-item">
                <a href="/irb-digital-system/features/admin/application_details.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/application_details.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>تفاصيل الطلب</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/admin/system_logs.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/admin/system_logs.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-list-check"></i>
                    <span>سجل النظام</span>
                </a>
            </li>
            
        <?php endif; ?>

        <!-- Sample Officer Role Links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'sample_officer'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة ضابط العينات</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/sample_officer/requests_history.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/sample_officer/requests_history.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>سجل العينات المنجزة</span>
                </a>
            </li>
            <?php
                if (!isset($sidebarAppObj)) {
                    require_once __DIR__ . '/../classes/Applications.php';
                    $sidebarAppObj = new Applications();
                }
                $samplerUnread = $sidebarAppObj->getUnreadNotificationCount($_SESSION['user_id']);
            ?>
            <li class="menu-item">
                <a href="/irb-digital-system/features/sample_officer/notifications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/sample_officer/notifications.php', '/irb-digital-system/features/sample_officer/notification_details.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>الإشعارات</span>
                    <?php if ($samplerUnread > 0): ?>
                        <span style="background:var(--accent-base);color:white;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:800;margin-right:auto;"><?= $samplerUnread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'reviewer'): ?>
            <?php
                if (!isset($sidebarAppObj)) {
                    require_once __DIR__ . '/../classes/Applications.php';
                    $sidebarAppObj = new Applications();
                }
                $reviewerUnread = $sidebarAppObj->getUnreadNotificationCount($_SESSION['user_id']);
            ?>
            <li class="menu-category">
                <span class="category-label">منطقة المراجع</span>
            </li>
            <li class="menu-item">
                <?php
                $sidebarPendingCount = 0;
                if (isset($_SESSION['user_id'])) {
                    if (!class_exists('Reviews')) require_once __DIR__ . '/../classes/Reviews.php';
                    $sidebarReviews = new Reviews();
                    $sidebarPendingAssignments = $sidebarReviews->getPendingAssignments($_SESSION['user_id']);
                    $sidebarPendingCount = count($sidebarPendingAssignments);
                }
                ?>
                <a href="/irb-digital-system/features/reviewer/pending_assignments.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/reviewer/pending_assignments.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-inbox"></i>
                    <span>الأبحاث المعلقة</span>
                    <?php if ($sidebarPendingCount > 0): ?>
                        <span style="background:#f59e0b;color:white;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:800;margin-right:auto;"><?= $sidebarPendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/reviewer/assigned_reserches.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/reviewer/assigned_reserches.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-microscope"></i>
                    <span>الأبحاث المسندة</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/reviewer/notifications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/reviewer/notifications.php', '/irb-digital-system/features/reviewer/notification_details.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>الإشعارات</span>
                    <?php if ($reviewerUnread > 0): ?>
                        <span style="background:var(--accent-base);color:white;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:800;margin-right:auto;"><?= $reviewerUnread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'manager'): ?>
            <li class="menu-category">
                <span class="category-label">منطقة المدير</span>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/manager/upload_signature.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/upload_signature.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>رفع التوقيع</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/manager/dashboard2.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/dashboard2.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-stamp"></i>
                    <span>الأبحاث الجاهزة للأعتماد النهائي</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/manager/final_approvals.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/final_approvals.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>الاعتمادات النهائية</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="/irb-digital-system/features/manager/system_reportsAndStatistics.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/system_reportsAndStatistics.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>التقارير والإحصائيات</span>
                </a>
            </li>
            <?php
                if (!isset($sidebarAppObj)) {
                    require_once __DIR__ . '/../classes/Applications.php';
                    $sidebarAppObj = new Applications();
                }
                $managerUnread = $sidebarAppObj->getUnreadNotificationCount($_SESSION['user_id']);
            ?>
            <li class="menu-item">
                <a href="/irb-digital-system/features/manager/notifications.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/features/manager/notifications.php', '/irb-digital-system/features/manager/notification_details.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span>الإشعارات</span>
                    <?php if ($managerUnread > 0): ?>
                        <span style="background:var(--accent-base);color:white;padding:2px 8px;border-radius:999px;font-size:0.75rem;font-weight:800;margin-right:auto;"><?= $managerUnread ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

         <?php if(isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): ?>
            <li class="menu-item">
                <a href="/irb-digital-system/includes/profile.php" class="menu-link<?= irb_sidebar_is_active(['/irb-digital-system/includes/profile.php']) ? ' is-active' : '' ?>">
                    <i class="fa-solid fa-user"></i>
                    <span>الملف الشخصي</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- Updated Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <i class="fa-solid fa-user-circle"></i>
            <div class="user-details">
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] !== 'admin'): ?>
                    <a href="/irb-digital-system/includes/profile.php" class="user-name" style="text-decoration: none;" title="الملف الشخصي"><?= isset($_SESSION['full_name']) ? htmlspecialchars(mb_substr($_SESSION['full_name'], 0, 20, 'UTF-8')) : 'المستخدم' ?></a>
                <?php else:?>
                    <span class="user-name"><?= isset($_SESSION['full_name']) ? htmlspecialchars(mb_substr($_SESSION['full_name'], 0, 20, 'UTF-8'))  : 'المستخدم' ?></span>
                <?php endif; ?>
                
                <!-- Descriptive Logout Button -->
                <a href="javascript:void(0)" class="logout-link" id="logoutTrigger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>تسجيل الخروج</span>
                </a>
            </div>
        </div>
    </div>
</aside>

<!-- Logout Confirmation Modal -->
<div class="logout-modal" id="logoutModal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fa-solid fa-circle-question"></i>
        </div>
        <h3>تأكيد تسجيل الخروج</h3>
        <p>هل أنت متأكد أنك تريد مغادرة النظام؟</p>
        <div class="modal-actions">
            <button class="btn-cancel" id="cancelLogout">إلغاء</button>
            <a href="/irb-digital-system/features/auth/logout.php" class="btn-confirm">تسجيل الخروج</a>
        </div>
    </div>
</div>

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

    .sidebar .sidebar-menu li .menu-link i {
        font-size: 1.02rem;
        width: 18px;
        text-align: center;
        color: rgba(255, 255, 255, 0.8);
        transition: all var(--transition-smooth);
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

    /* Footer Updates */
    .sidebar-footer {
        padding: 20px 18px;
        border-top: 2px solid rgba(26, 188, 156, 0.3);
        background: linear-gradient(180deg, transparent 0%, rgba(26, 188, 156, 0.05) 100%);
    }

    .user-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .user-info i.fa-user-circle {
        font-size: 1.8rem;
        color: var(--accent-base);
        flex-shrink: 0;
    }

    .user-details {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }

    .user-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: #ffffff;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .logout-link {
        display: flex;
        align-items: center;
        gap: 6px;
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .logout-link:hover {
        color: #ff7675;
    }

    .logout-link i {
        font-size: 0.85rem;
    }

    /* Logout Modal Styling */
    .logout-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .logout-modal.is-visible {
        display: flex;
    }

    .modal-content {
        background: #ffffff;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 380px;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        animation: modalScaleUp 0.25s ease-out;
    }

    @keyframes modalScaleUp {
        from { transform: scale(0.85); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    .modal-icon {
        font-size: 3rem;
        color: #ff7675;
        margin-bottom: 15px;
    }

    .modal-content h3 {
        color: #2d3436;
        margin-bottom: 10px;
        font-size: 1.25rem;
    }

    .modal-content p {
        color: #636e72;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
    }

    .btn-confirm, .btn-cancel {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        border: none;
        flex: 1;
    }

    .btn-confirm {
        background: #ff7675;
        color: white;
    }

    .btn-confirm:hover {
        background: #d63031;
    }

    .btn-cancel {
        background: #f1f2f6;
        color: #2d3436;
    }

    .btn-cancel:hover {
        background: #dfe4ea;
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

    /* Responsive */
    .mobile-menu-btn {
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: var(--primary-base, #1abc9c);
        color: white;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    @media (max-width: 1000px) {
        .mobile-menu-btn {
            display: block;
        }
        
        .sidebar {
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
            box-shadow: -5px 0 15px rgba(0,0,0,0.2);
        }

        .sidebar.sidebar-open {
            transform: translateX(0);
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle
        var menuBtn = document.getElementById('mobileMenuBtn');
        var sidebar = document.querySelector('.sidebar');

        if (menuBtn && sidebar) {
            menuBtn.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-open');
            });
        }

        // Logout Modal Logic
        const logoutTrigger = document.getElementById('logoutTrigger');
        const logoutModal = document.getElementById('logoutModal');
        const cancelLogout = document.getElementById('cancelLogout');

        if (logoutTrigger && logoutModal) {
            logoutTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                logoutModal.classList.add('is-visible');
            });

            cancelLogout.addEventListener('click', function() {
                logoutModal.classList.remove('is-visible');
            });

            // Close modal when clicking background
            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.remove('is-visible');
                }
            });
        }
    });
</script>