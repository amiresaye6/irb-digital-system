<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

Auth::checkRole(['admin']);
$db = new Database();

$sql = "SELECT id, full_name, email, role, is_active, national_id, phone_number, created_at FROM users ORDER BY created_at DESC";
$allUsers = $db->getconn()->query($sql)->fetch_all(MYSQLI_ASSOC);

$pendingUsers = [];
$activeUsers = [];

$roleTranslations = [
    'admin'          => 'مدير نظام',
    'manager'        => 'مدير عام',
    'reviewer'       => 'مُراجع',
    'sample_officer' => 'مسؤول عينات',
    'student'        => 'باحث / طالب'
];

foreach ($allUsers as $u) {
    if ((int)$u['is_active'] === 0) {
        $pendingUsers[] = $u;
    } else {
        $activeUsers[] = $u;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين | IRB System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    
    <style>
        body { background:var(--bg-page); }
        .content { margin-right:260px; padding:20px 24px; display:flex; flex-direction:column; align-items:center; }
        .content > * { width:100%; max-width:1120px; }
        
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title { color:var(--primary-base); font-weight:800; font-size:1.6rem; margin:0; }

        /* ستايل السيرش المظبوط */
        .toolbar-card { 
            background: linear-gradient(180deg,rgba(44,62,80,0.04) 0%,#fff 100%); 
            border: 1px solid rgba(189,195,199,0.6); 
            border-radius: var(--radius-lg); 
            padding: 15px; margin-bottom: 20px;
            display: flex; gap: 15px; align-items: flex-end;
        }
        .search-wrapper { position: relative; flex: 1; }
        .search-input { 
            width:100%; border:1.5px solid rgba(189,195,199,0.9); border-radius:10px; 
            padding:10px 40px 10px 12px; font-family:inherit; font-weight:600;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%237f8c8d' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'/%3E%3C/svg%3E") no-repeat right 12px center;
        }

        /* جداول منفصلة */
        .section-title { font-weight: 800; color: var(--primary-base); margin: 30px 0 15px; display: flex; align-items: center; gap: 10px; }
        .data-card { background:var(--bg-surface); border-radius:var(--radius-lg); box-shadow:var(--shadow-md); border:1px solid var(--border-light); padding: 20px; margin-bottom: 20px; }
        
        .data-table { width:100%; border-collapse:collapse; text-align:right; }
        .data-table th { padding:12px; background:var(--primary-base); color:white; font-weight:700; font-size:0.85rem; }
        .data-table td { padding:12px; border-bottom:1px solid var(--border-light); font-size: 0.9rem; }

        /* أزرار الأكشن */
        .btn-action { padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: 0.2s; }
        .btn-approve { background: #27ae60; color: white !important; border: none; }
        .btn-reject { background: #e74c3c; color: white !important; border: none; margin-right: 5px; }
        .btn-action:hover { opacity: 0.8; transform: translateY(-1px); }

        .contact-info small { display: block; color: var(--text-muted); font-size: 0.75rem; }
        
        @media(max-width:992px) { .content{margin-right:0;} }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-users-gear"></i> إدارة المستخدمين</h2>
            <a href="add_user.php" class="btn-add-user" style="background:var(--accent-base); color:white; padding:10px 20px; border-radius:10px; text-decoration:none; font-weight:800;">إضافة مستخدم</a>
        </div>

        <div class="toolbar-card">
            <div class="search-wrapper">
                <label style="display:block; font-weight:800; font-size:0.8rem; margin-bottom:5px;">البحث العام</label>
                <input type="text" id="mainSearch" class="search-input" placeholder="بحث بالاسم، البريد، الرقم القومي...">
            </div>
            <div style="width: 200px;">
                <label style="display:block; font-weight:800; font-size:0.8rem; margin-bottom:5px;">نوع الحساب</label>
                <select id="roleFilter" class="search-input" style="background-image:none; padding-right:12px;">
                    <option value="all">الكل</option>
                    <option value="student">Student</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
        </div>

        <h3 class="section-title" style="color: #f39c12;"><i class="fa-solid fa-clock-rotate-left"></i> طلبات بانتظار التفعيل (<?= count($pendingUsers) ?>)</h3>
        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>بيانات التواصل</th>
                        <th>الصلاحية</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pendingUsers)): ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">لا توجد طلبات معلقة</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingUsers as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['full_name']) ?></strong><br><small><?= htmlspecialchars($u['email']) ?></small></td>
                            <td class="contact-info">
                                <small>ID: <?= $u['national_id'] ?></small>
                                <small>Tel: <?= $u['phone_number'] ?></small>
                            </td>
                            <td><span class="phase-badge"><?= $roleTranslations[$u['role']] ?? $u['role'] ?></span></td>
                            <td>
                                <a href="activate_user.php?id=<?= $u['id'] ?>" class="btn-action btn-approve"><i class="fa-solid fa-check"></i> تفعيل</a>
                                <a href="reject_user.php?id=<?= $u['id'] ?>" class="btn-action btn-reject" onclick="return confirm('هل أنت متأكد من الرفض؟')"><i class="fa-solid fa-xmark"></i> رفض</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h3 class="section-title" style="color: #27ae60;"><i class="fa-solid fa-user-check"></i> المستخدمين النشطين</h3>
        <div class="data-card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المستخدم</th>
                        <th>بيانات التواصل</th>
                        <th>الصلاحية</th>
                        <th>تاريخ الانضمام</th>
                    </tr>
                </thead>
                <tbody id="activeUsersTable">
                    <?php foreach ($activeUsers as $u): ?>
                    <tr data-search="<?= strtolower($u['full_name'] . ' ' . $u['email']) ?>" data-role="<?= $u['role'] ?>">
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong><br><small><?= htmlspecialchars($u['email']) ?></small></td>
                        <td class="contact-info">
                            <small>ID: <?= $u['national_id'] ?></small>
                            <small>Tel: <?= $u['phone_number'] ?></small>
                        </td>
                        <td><span class="phase-badge"><?= $roleTranslations[$u['role']] ?? $u['role'] ?></span></td>
                        <td><?= date('Y/m/d', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('mainSearch').addEventListener('input', function(e) {
            let term = e.target.value.toLowerCase();
            document.querySelectorAll('#activeUsersTable tr').forEach(tr => {
                tr.style.display = tr.getAttribute('data-search').includes(term) ? '' : 'none';
            });
        });
    </script>
     <?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>