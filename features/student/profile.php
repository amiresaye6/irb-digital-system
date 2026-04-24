<?php
require_once "../../init.php";
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('student'); 

$id = $_SESSION['user_id'];
$dbobj = new Database();
$user = $dbobj->selectById("users", $id);
$success = $_SESSION['success'] ?? '';
$errors  = $_SESSION['errors']  ?? [];
unset($_SESSION['success']);
unset($_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي | IRB System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-deep:  #2C3E50;
            --accent-base: #1ABC9C;
            --bg-page: #f8fafc;
            --sidebar-width: 280px;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            margin: 0;
            padding-right: var(--sidebar-width);
            color: #334155;
        }

        .main-wrapper {
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Header Section */
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        /* Modern Button */
        .btn-modern {
            background: var(--primary-deep);
            color: white;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .btn-modern:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            color: white;
        }

        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 25px;
            align-items: start;
        }

        .glass-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            padding: 30px;
        }

        /* Data Fields Styling */
        .info-display-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .data-field {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 12px;
        }

        .data-field label {
            display: block;
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .data-field span {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary-deep);
        }

        /* Sidebar Cards */
        .docs-sidebar-card {
            position: sticky;
            top: 20px;
        }

        .img-preview {
            width: 100%;
            border-radius: 12px;
            margin-top: 12px;
            border: 1px solid #eee;
            transition: 0.3s;
            object-fit: cover;
            height: 180px;
        }

        /* Modal Overlay */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .modal-box {
            background: white;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-overlay.open .modal-box {
            transform: scale(1);
        }

        .modal-field { margin-bottom: 15px; }
        .modal-field label { display: block; font-size: 13px; margin-bottom: 6px; color: #64748b; font-weight: 600;}
        .modal-field input {
            width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px;
        }

        .btn-save { background: var(--accent-base); color: white; border: none; padding: 12px; border-radius: 10px; width: 100%; font-weight: 700; margin-top: 15px;}
        .btn-cancel { background: #f1f5f9; color: #64748b; border: none; padding: 12px; border-radius: 10px; width: 100%; margin-top: 8px; font-weight: 600;}

        @media (max-width: 1200px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            body { padding-right: 0; }
        }
    </style>
</head>
<body>

<?php require_once "../../includes/header.php"; ?>

<div class="main-wrapper">
    <header class="header-section">
        <div>
            <h2 style="font-weight: 700; color: var(--primary-deep); margin:0;">الملف الشخصي</h2>
            
        </div>
        <button class="btn-modern" onclick="toggleModal(true)">
            <i class="fas fa-user-edit me-2"></i> تحديث البيانات
        </button>
    </header>

    <div class="dashboard-grid">
        <div class="glass-card">
            <h5 style="border-right: 4px solid var(--accent-base); padding-right: 15px; margin-bottom: 30px; font-weight: 700;">المعلومات الأكاديمية والشخصية</h5>
            
            <div class="info-display-grid">
                <div class="data-field">
                    <label>الاسم الكامل</label>
                    <span><?php echo $user['full_name']; ?></span>
                </div>
                <div class="data-field">
                    <label>البريد الإلكتروني</label>
                    <span><?php echo $user['email']; ?></span>
                </div>
                <div class="data-field">
                    <label>رقم الهاتف</label>
                    <span><?php echo $user['phone_number'] ?: 'غير مسجل'; ?></span>
                </div>
                <div class="data-field">
                    <label>الرقم القومي</label>
                    <span style="letter-spacing: 1px;"><?php echo $user['national_id']; ?></span>
                </div>
                <div class="data-field">
                    <label>الكلية</label>
                    <span><?php echo $user['faculty'] ?: '—'; ?></span>
                </div>
                <div class="data-field">
                    <label>القسم</label>
                    <span><?php echo $user['department'] ?: '—'; ?></span>
                </div>
            </div>

            <div style="margin-top: 40px; padding: 18px; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-shield-alt" style="color: var(--accent-base); font-size: 20px;"></i>
                <p style="font-size: 13px; color: #64748b; margin: 0; line-height: 1.6;">
                    بياناتك محمية بموجب سياسة الخصوصية، ولا يمكن تعديل الرقم القومي إلا من خلال مراسلة الدعم الفني.
                </p>
            </div>
        </div>

        <div class="docs-sidebar-card">
            <div class="glass-card" style="margin-bottom: 20px;">
                <h6 style="font-weight: 700; margin-bottom: 20px;"><i class="fas fa-id-card-alt me-2"></i> وثائق الهوية</h6>
                
                <label style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">وجه البطاقة</label>
                <img src="../../<?php echo $user['id_front_url']; ?>" class="img-preview" alt="ID Front">
                
                <div style="margin: 20px 0; border-top: 1px solid #f1f5f9;"></div>
                
                <label style="font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">ظهر البطاقة</label>
                <img src="../../<?php echo $user['id_back_url']; ?>" class="img-preview" alt="ID Back">
            </div>

            <div class="glass-card" style="background: var(--primary-deep); color: white; border: none;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; opacity: 0.8;">حالة الحساب</span>
                    <i class="fas fa-check-circle" style="color: var(--accent-base);"></i>
                </div>
                <div style="margin-top: 10px; font-size: 18px; font-weight: 700;">
                    <?php echo $user['is_active'] ? 'نشط بالكامل' : 'قيد التدقيق'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <h4 class="text-center mb-4" style="font-weight: 700; color: var(--primary-deep);">تحديث البيانات</h4>
        
        <form action="update_profile.php" method="POST">
            <div class="modal-field">
                <label>رقم الهاتف</label>
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
            </div>

            <div class="modal-field">
                <label>الكلية</label>
                <input type="text" name="faculty" value="<?php echo htmlspecialchars($user['faculty'] ?? ''); ?>">
            </div>

            <div class="modal-field">
                <label>القسم العلمي</label>
                <input type="text" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
            </div>

            <div class="alert alert-light border-0" style="font-size: 12px; color: #64748b; background: #f8fafc;">
                <i class="fas fa-info-circle me-1"></i> لا يمكن تعديل الاسم أو الرقم القومي من هنا.
            </div>

            <button type="submit" class="btn-save">حفظ التغييرات</button>
            <button type="button" class="btn-cancel" onclick="toggleModal(false)">إلغاء</button>
        </form>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>

<script>
    function toggleModal(show) {
        const modal = document.getElementById('editModal');
        if (show) {
            modal.classList.add('open');
        } else {
            modal.classList.remove('open');
        }
    }

    // إغلاق عند الضغط خارج الصندوق
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            toggleModal(false);
        }
    }
</script>
</body>
</html>