<?php
require_once "../init.php";
require_once __DIR__ . "/../classes/Auth.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    header('Location: /irb-digital-system/features/auth/login.php');
    exit;
}

$id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$dbobj = new Database();
$user = $dbobj->selectById("users", $id);
$success = $_SESSION['success'] ?? '';
$errors  = $_SESSION['errors']  ?? [];
unset($_SESSION['success']);
unset($_SESSION['errors']);

$isStudent = ($role === 'student');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي | نظام إدارة الموافقات البحثية</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 40px 50px; display: flex; flex-direction: column; align-items: center; }
        .content-wrapper { width: 100%; max-width: 1400px; }

        .page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 35px; border-bottom: 2px solid var(--primary-light); padding-bottom: 20px; }
        .page-header-text h2 { color: var(--primary-base); font-weight: 800; font-size: 2rem; margin: 0 0 8px 0; display: flex; align-items: center; gap: 15px; }
        .page-header-text h2 i { color: var(--accent-base); background: rgba(26, 188, 156, 0.1); padding: 12px; border-radius: var(--radius-md); }
        .page-header-text p { color: var(--text-muted); font-size: 1.05rem; margin: 0; font-weight: 600; }

        .btn-modern { background: var(--primary-base); color: white; border: none; padding: 10px 22px; border-radius: var(--radius-md); font-size: 0.95rem; font-weight: 700; transition: all 0.3s ease; cursor: pointer; display: flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-modern:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(26, 188, 156, 0.2); color: white; }

        /* Grid Layout */
        .dashboard-grid { display: grid; grid-template-columns: <?= $isStudent ? '1fr 350px' : '1fr' ?>; gap: 30px; align-items: start; }
        @media (max-width: 1200px) { .dashboard-grid { grid-template-columns: 1fr; } }

        .glass-card { background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-light); box-shadow: var(--shadow-sm); padding: 35px; }
        
        .card-title-header { border-right: 4px solid var(--accent-base); padding-right: 15px; margin-bottom: 30px; font-weight: 800; color: var(--primary-dark); font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }

        /* Data Fields Styling */
        .info-display-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 30px; }
        .data-field { border-bottom: 1px solid var(--border-light); padding-bottom: 15px; }
        .data-field label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px; font-weight: 700; text-transform: uppercase; }
        .data-field span { font-size: 1.05rem; font-weight: 700; color: var(--text-main); }

        .alert-info-box { margin-top: 40px; padding: 18px; background: rgba(26, 188, 156, 0.05); border-radius: var(--radius-md); border: 1px dashed rgba(26, 188, 156, 0.3); display: flex; align-items: center; gap: 15px; }
        .alert-info-box i { color: var(--accent-base); font-size: 1.5rem; }
        .alert-info-box p { font-size: 0.95rem; color: var(--text-muted); margin: 0; line-height: 1.6; font-weight: 600; }

        /* Sidebar Cards for Students */
        .docs-sidebar-card { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 20px; }
        .img-preview { width: 100%; border-radius: var(--radius-md); margin-top: 12px; border: 1px solid var(--border-light); object-fit: cover; height: 180px; box-shadow: var(--shadow-sm); }
        .doc-label { font-size: 0.8rem; color: var(--primary-dark); font-weight: 800; display: block; margin-top: 20px; }

        .status-card { background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-base) 100%); color: white; border: none; padding: 25px; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .status-header { display: flex; justify-content: space-between; align-items: center; font-size: 0.95rem; font-weight: 700; opacity: 0.9; }
        .status-value { margin-top: 15px; font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px; }

        /* Modal Overlay */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .modal-overlay.open { opacity: 1; visibility: visible; }
        .modal-box { background: white; width: 90%; max-width: 500px; padding: 40px; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: scale(0.9); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .modal-overlay.open .modal-box { transform: scale(1); }
        .modal-box h4 { font-weight: 800; color: var(--primary-dark); margin-bottom: 25px; font-size: 1.4rem; text-align: center; }

        .modal-field { margin-bottom: 20px; }
        .modal-field label { display: block; font-size: 0.9rem; margin-bottom: 8px; color: var(--text-muted); font-weight: 700; }
        .modal-field input { width: 100%; padding: 12px 15px; border: 1.5px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-weight: 600; color: var(--text-main); transition: all 0.2s; }
        .modal-field input:focus { outline: none; border-color: var(--accent-base); box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1); }

        .btn-save { background: var(--primary-base); color: white; border: none; padding: 14px; border-radius: var(--radius-md); width: 100%; font-weight: 800; margin-top: 15px; cursor: pointer; transition: all 0.2s; font-size: 1rem; }
        .btn-save:hover { background: var(--primary-dark); }
        .btn-cancel { background: var(--bg-page); color: var(--text-muted); border: 1.5px solid var(--border-light); padding: 14px; border-radius: var(--radius-md); width: 100%; margin-top: 10px; font-weight: 700; cursor: pointer; transition: all 0.2s; font-size: 1rem; }
        .btn-cancel:hover { background: #e2e8f0; color: var(--text-main); }

        /* Alerts */
        .toast { padding: 15px 25px; border-radius: var(--radius-md); font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow-sm); }
        .toast-success { background: rgba(46, 204, 113, 0.1); color: #27ae60; border: 1px solid rgba(46, 204, 113, 0.2); }
        .toast-error { background: rgba(231, 76, 60, 0.1); color: #c0392b; border: 1px solid rgba(231, 76, 60, 0.2); }
    </style>
</head>
<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="content">
        <div class="content-wrapper">
            
            <div class="page-header">
                <div class="page-header-text">
                    <h2><i class="fa-solid fa-id-badge"></i> الملف الشخصي</h2>
                    <p>استعرض وقم بتحديث بياناتك الأكاديمية والشخصية</p>
                </div>
                <button class="btn-modern" onclick="toggleModal(true)">
                    <i class="fas fa-user-edit"></i> تحديث البيانات
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="toast toast-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <?php foreach($errors as $error): ?>
                    <div class="toast toast-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="dashboard-grid">
                <!-- Main Info Card -->
                <div class="glass-card">
                    <h5 class="card-title-header"><i class="fa-solid fa-user-tie"></i> المعلومات الأكاديمية والشخصية</h5>
                    
                    <div class="info-display-grid">
                        <div class="data-field">
                            <label>الاسم الكامل</label>
                            <span><?= htmlspecialchars($user['full_name']) ?></span>
                        </div>
                        <div class="data-field">
                            <label>البريد الإلكتروني</label>
                            <span><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="data-field">
                            <label>الدور والصلاحية</label>
                            <span>
                                <?php 
                                    $rolesAr = [
                                        'student' => 'طالب / باحث',
                                        'reviewer' => 'مراجع',
                                        'sample_officer' => 'ضابط عينات',
                                        'manager' => 'مدير النظام'
                                    ];
                                    echo $rolesAr[$user['role']] ?? $user['role'];
                                ?>
                            </span>
                        </div>
                        <div class="data-field">
                            <label>رقم الهاتف</label>
                            <span><?= htmlspecialchars($user['phone_number'] ?: 'غير مسجل') ?></span>
                        </div>
                        <div class="data-field">
                            <label>الرقم القومي</label>
                            <span style="letter-spacing: 1px;"><?= htmlspecialchars($user['national_id']) ?></span>
                        </div>
                        <div class="data-field">
                            <label>الكلية</label>
                            <span><?= htmlspecialchars($user['faculty'] ?: '—') ?></span>
                        </div>
                        <div class="data-field">
                            <label>القسم</label>
                            <span><?= htmlspecialchars($user['department'] ?: '—') ?></span>
                        </div>
                    </div>

                    <div class="alert-info-box">
                        <i class="fas fa-shield-alt"></i>
                        <p>بياناتك محمية بموجب سياسة الخصوصية. لا يمكن تعديل الرقم القومي أو البريد الإلكتروني إلا من خلال مراسلة الدعم الفني للحفاظ على الأمان.</p>
                    </div>
                </div>

                <!-- Sidebar Docs for Students Only -->
                <?php if ($isStudent): ?>
                    <div class="docs-sidebar-card">
                        
                        <div class="status-card">
                            <div class="status-header">
                                <span>حالة الحساب</span>
                                <i class="fas fa-circle-check"></i>
                            </div>
                            <div class="status-value">
                                <?php if($user['is_active']): ?>
                                    <i class="fa-solid fa-shield-check"></i> نشط وموثق
                                <?php else: ?>
                                    <i class="fa-solid fa-clock-rotate-left"></i> قيد التدقيق
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="glass-card" style="padding: 25px;">
                            <h6 style="font-weight: 800; color: var(--primary-dark); margin-bottom: 15px; border-bottom: 2px solid var(--border-light); padding-bottom: 15px;">
                                <i class="fas fa-id-card-alt me-2" style="color: var(--accent-base);"></i> وثائق الهوية
                            </h6>
                            
                            <span class="doc-label">وجه البطاقة</span>
                            <?php if(!empty($user['id_front_url'])): ?>
                                <img src="/irb-digital-system/<?= htmlspecialchars($user['id_front_url']) ?>" class="img-preview" alt="ID Front">
                            <?php else: ?>
                                <div style="height: 100px; display:flex; align-items:center; justify-content:center; background: var(--bg-page); border-radius: var(--radius-md); border: 1px dashed var(--border-dark); color: var(--text-muted); margin-top: 12px; font-weight: 600;">لا توجد صورة</div>
                            <?php endif; ?>
                            
                            <span class="doc-label">ظهر البطاقة</span>
                            <?php if(!empty($user['id_back_url'])): ?>
                                <img src="/irb-digital-system/<?= htmlspecialchars($user['id_back_url']) ?>" class="img-preview" alt="ID Back">
                            <?php else: ?>
                                <div style="height: 100px; display:flex; align-items:center; justify-content:center; background: var(--bg-page); border-radius: var(--radius-md); border: 1px dashed var(--border-dark); color: var(--text-muted); margin-top: 12px; font-weight: 600;">لا توجد صورة</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal-overlay" id="editModal">
        <div class="modal-box">
            <h4><i class="fa-solid fa-pen-to-square" style="color: var(--accent-base); margin-left: 8px;"></i> تحديث البيانات</h4>
            
            <form action="update_profile.php" method="POST">
                <div class="modal-field">
                    <label>الاسم الكامل</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                </div>

                <div class="modal-field">
                    <label>رقم الهاتف</label>
                    <input type="text" name="phone_number" value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>" required>
                </div>

                <div class="modal-field">
                    <label>الكلية</label>
                    <input type="text" name="faculty" value="<?= htmlspecialchars($user['faculty'] ?? '') ?>">
                </div>

                <div class="modal-field">
                    <label>القسم العلمي</label>
                    <input type="text" name="department" value="<?= htmlspecialchars($user['department'] ?? '') ?>">
                </div>

                <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> حفظ التغييرات</button>
                <button type="button" class="btn-cancel" onclick="toggleModal(false)">إلغاء</button>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(show) {
            const modal = document.getElementById('editModal');
            if (show) {
                modal.classList.add('open');
            } else {
                modal.classList.remove('open');
            }
        }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                toggleModal(false);
            }
        }
    </script>
</body>
</html>
