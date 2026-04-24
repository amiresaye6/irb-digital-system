<?php
require_once "../../init.php";
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('admin');

$dbobj = new Database();

$result       = $dbobj->getconn()->query("SELECT * FROM users WHERE role = 'student' AND is_active = 0 ORDER BY created_at DESC");
$pendingUsers = $result->fetch_all(MYSQLI_ASSOC);

$allUsers     = $dbobj->selectAll("users");
$totalUsers   = count($allUsers);
$pendingCount = count($pendingUsers);
$activeCount  = $totalUsers - $pendingCount;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الأدمن | IRB</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        /* الحل الجذري لمشكلة تداخل السايد بار */
        .dashboard-content {
            margin-right: 260px !important; /* نفس عرض السايد بار الموجود في نظامك */
            padding: 30px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        /* تنسيق كروت الإحصائيات */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            border: 0.5px solid var(--border-light);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: border var(--transition-smooth);
        }

        .stat-card:hover { border-color: var(--accent-base); }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 22px;
        }

        .stat-icon.teal  { background: var(--accent-light);  color: var(--accent-dark); }
        .stat-icon.amber { background: var(--warning-light); color: var(--warning-base); }
        .stat-icon.navy  { background: var(--primary-light); color: var(--primary-base); }

        .stat-num   { font-size: 28px; font-weight: 800; color: var(--primary-base); line-height: 1; }
        .stat-label { font-size: 13px; color: var(--text-muted); margin-top: 4px; }

        /* تنسيق الجدول والطلبات */
        .section-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-base);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }

        .table-card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            border: 0.5px solid var(--border-light);
            overflow-x: auto; /* للسماح بالتمرير في الشاشات الصغيرة */
        }

        .table-head {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1.5fr 1fr 1.8fr;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border-light);
            font-size: 13px;
            font-weight: 700;
            color: var(--text-muted);
            min-width: 800px; /* يضمن عدم تداخل الأعمدة */
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1.5fr 1.5fr 1fr 1.8fr;
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
            align-items: center;
            font-size: 14px;
            transition: background 0.2s;
            min-width: 800px;
        }

        .table-row:hover { background: #fbfcfc; }

        .row-name  { font-weight: 700; color: var(--primary-base); }
        .row-email { color: var(--text-muted); font-size: 12px; }

        .nat-id {
            font-family: monospace;
            background: #f1f3f4;
            padding: 4px 8px;
            border-radius: 5px;
            color: var(--primary-base);
        }

        .id-thumb {
            width: 45px;
            height: 30px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid var(--border-light);
            cursor: pointer;
            margin-left: 5px;
        }

        /* الأزرار */
        .btn-activate {
            background: var(--accent-base);
            color: white; border: none;
            padding: 7px 15px; border-radius: 6px;
            cursor: pointer; font-family: 'Cairo';
            font-size: 12px;
        }

        .btn-reject {
            background: transparent;
            border: 1px solid var(--alert-base);
            color: var(--alert-base);
            padding: 7px 15px; border-radius: 6px;
            cursor: pointer; font-family: 'Cairo';
            font-size: 12px;
        }

        /* المودال */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.6); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; padding: 25px; border-radius: 15px; width: 90%; max-width: 600px; }
        .modal-imgs { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .modal-img-card img { width: 100%; height: 200px; object-fit: contain; background: #eee; }

        /* استجابة الموبايل */
        @media (max-width: 992px) {
            .dashboard-content { margin-right: 0 !important; padding: 15px; }
        }
    </style>
</head>
<body>

<?php require_once "../../includes/header.php"; ?>

<div class="dashboard-content">
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon navy"><i class="fas fa-users"></i></div>
            <div>
                <p class="stat-num"><?php echo $totalUsers; ?></p>
                <p class="stat-label">إجمالي المستخدمين</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon amber"><i class="fas fa-user-clock"></i></div>
            <div>
                <p class="stat-num"><?php echo $pendingCount; ?></p>
                <p class="stat-label">في انتظار التفعيل</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal"><i class="fas fa-user-check"></i></div>
            <div>
                <p class="stat-num"><?php echo $activeCount; ?></p>
                <p class="stat-label">حسابات مفعلة</p>
            </div>
        </div>
    </div>

    <p class="section-label">طلبات تفعيل الحسابات الجديدة</p>

    <div class="table-card">
        <?php if(empty($pendingUsers)): ?>
            <div style="text-align:center; padding: 50px; color: #95a5a6;">
                <i class="fas fa-check-circle" style="font-size: 40px; margin-bottom: 10px;"></i>
                <p>لا توجد طلبات معلقة حالياً</p>
            </div>
        <?php else: ?>
            <div class="table-head">
                <span>الاسم الشخصي</span>
                <span>البريد الإلكتروني</span>
                <span>رقم البطاقة</span>
                <span>صور الهوية</span>
                <span>تاريخ التسجيل</span>
                <span>الإجراءات</span>
            </div>

            <?php foreach($pendingUsers as $user): ?>
                <?php
                     $front = "/irb-digital-system/" . ltrim(str_replace('\\', '/', $user['id_front_url']), '/');
    
                    $back  = "/irb-digital-system/" . ltrim(str_replace('\\', '/', $user['id_back_url']),  '/');
                ?>
                <div class="table-row">
                    <div>
                        <p class="row-name"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    <div class="row-email"><?php echo htmlspecialchars($user['email']); ?></div>
                    <div><span class="nat-id"><?php echo htmlspecialchars($user['national_id']); ?></span></div>
                    <div class="id-thumbs">
                        <img src="<?php echo $front; ?>" class="id-thumb" onclick="openModal('<?php echo $front; ?>','<?php echo $back; ?>','<?php echo addslashes($user['full_name']); ?>')">
                        <img src="<?php echo $back; ?>" class="id-thumb" onclick="openModal('<?php echo $front; ?>','<?php echo $back; ?>','<?php echo addslashes($user['full_name']); ?>')">
                    </div>
                    <div style="font-size:12px; color:#7f8c8d;"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></div>
                    <div class="row-actions">
                        <form action="activate_user.php" method="POST" style="display:inline">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn-activate" onclick="return confirm('تفعيل حساب المستخدم؟')">تفعيل</button>
                        </form>
                        <form action="reject_user.php" method="POST" style="display:inline">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn-reject" onclick="return confirm('رفض الحساب؟')">رفض</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="idModal">
    <div class="modal-box">
        <p style="font-weight:bold; border-bottom:1px solid #eee; padding-bottom:10px;" id="modalTitle">معاينة الهوية</p>
        <div class="modal-imgs">
            <div class="modal-img-card">
                <p style="font-size:11px; color:#7f8c8d;">وجه البطاقة</p>
                <img id="modalFront" src="">
            </div>
            <div class="modal-img-card">
                <p style="font-size:11px; color:#7f8c8d;">ظهر البطاقة</p>
                <img id="modalBack" src="">
            </div>
        </div>
        <button style="margin-top:20px; width:100%; padding:10px; cursor:pointer;" onclick="closeModal()">إغلاق</button>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>

<script>
function openModal(front, back, name) {
    document.getElementById('modalFront').src = front;
    document.getElementById('modalBack').src  = back;
    document.getElementById('modalTitle').innerText = 'هوية: ' + name;
    document.getElementById('idModal').classList.add('open');
}
function closeModal() {
    document.getElementById('idModal').classList.remove('open');
}
window.onclick = function(event) {
    if (event.target == document.getElementById('idModal')) closeModal();
}
</script>
</body>
</html>