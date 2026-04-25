<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once "../../init.php";
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('admin');

$dbobj   = new Database();
$success = $_SESSION['success'] ?? '';
$errors  = $_SESSION['errors']  ?? [];
unset($_SESSION['success'], $_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مستخدم جديد | IRB System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    
    <style>
        body { background: var(--bg-page); }
        
        /* ضبط المساحة للمحتوى بجانب الـ Sidebar */
        .content-wrapper {
            margin-right: 260px;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            align-items: center; /* سنترة الفورم */
        }

        .page-header {
            width: 100%;
            max-width: 800px;
            margin-bottom: 25px;
            text-align: right;
        }

        .page-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--primary-base);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-card {
            background: #fff;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-md);
            padding: 35px;
            width: 100%;
            max-width: 800px;
        }

        .section-label {
            font-size: 0.9rem;
            font-weight: 800;
            color: var(--accent-base);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border-light));
        }

        /* تنسيق شبكة الأدوار */
        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .role-card {
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            background: var(--bg-surface);
        }

        .role-card:hover { border-color: var(--accent-base); transform: translateY(-3px); }

        .role-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none; 
}

        .role-card.selected {
            border-color: var(--accent-base);
            background: rgba(26, 188, 156, 0.05);
            box-shadow: 0 4px 12px rgba(26, 188, 156, 0.1);
        }

        .role-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* ألوان الأيقونات */
        .role-icon.admin { background: #eef2ff; color: #4f46e5; }
        .role-icon.reviewer { background: #fff7ed; color: #ea580c; }
        .role-icon.manager { background: #f0fdf4; color: #16a34a; }
        .role-icon.sample_officer { background: #faf5ff; color: #9333ea; }

        .role-name { font-weight: 700; font-size: 0.95rem; color: var(--primary-base); }
        .role-desc { font-size: 0.75rem; color: var(--text-muted); }

        /* الحقول */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .field { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .field.full { grid-column: 1 / -1; }

        .field label { font-size: 0.85rem; font-weight: 700; color: var(--text-main); }

        .field input {
            border: 1.5px solid var(--border-light);
            border-radius: 10px;
            padding: 12px;
            font-family: inherit;
            transition: 0.2s;
            background: #fafafa;
        }

        .field input:focus {
            border-color: var(--accent-base);
            background: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 188, 156, 0.1);
        }

        .btn-submit {
            background: var(--accent-base);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .btn-submit:hover { background: var(--accent-dark); transform: scale(1.02); }

        .btn-cancel {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .content-wrapper { margin-right: 0; padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="page-header">
            <h2 class="page-title"><i class="fa-solid fa-user-plus"></i> إضافة مستخدم جديد</h2>
            <p style="color:var(--text-muted); font-size:0.85rem; margin-top:5px;">قم بملء البيانات لإنشاء حساب وظيفي جديد على النظام</p>
        </div>

        <div class="form-card">
            <?php if($success): ?>
                <div style="background:#dcfce7; color:#166534; padding:15px; border-radius:10px; margin-bottom:20px; font-weight:600;">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="save_user.php" method="POST">
                <p class="section-label">أولاً: تحديد الدور الوظيفي</p>
                
                <div class="role-grid">
                    <label class="role-card" id="card-admin">
                        <input type="radio" name="role" value="admin" onchange="selectRole(this)" required>
                        <div class="role-icon admin"><i class="fas fa-shield-halved"></i></div>
                        <span class="role-name">موظف إداري</span>
                        <span class="role-desc">إدارة النظام</span>
                    </label>

                    <label class="role-card" id="card-reviewer">
                        <input type="radio" name="role" value="reviewer" onchange="selectRole(this)">
                        <div class="role-icon reviewer"><i class="fas fa-microscope"></i></div>
                        <span class="role-name">مُراجع</span>
                        <span class="role-desc">تقييم الأبحاث</span>
                    </label>

                    <label class="role-card" id="card-manager">
                        <input type="radio" name="role" value="manager" onchange="selectRole(this)">
                        <div class="role-icon manager"><i class="fas fa-stamp"></i></div>
                        <span class="role-name">مدير اللجنة</span>
                        <span class="role-desc">الاعتماد النهائي</span>
                    </label>

                    <label class="role-card" id="card-sample_officer">
                        <input type="radio" name="role" value="sample_officer" onchange="selectRole(this)">
                        <div class="role-icon sample_officer"><i class="fas fa-calculator"></i></div>
                        <span class="role-name">ضابط عينات</span>
                        <span class="role-desc">حساب العينة</span>
                    </label>
                </div>

                <p class="section-label">ثانياً: البيانات الأساسية</p>
                <div class="form-grid">
                    <div class="field full">
                        <label>الاسم ثلاثي</label>
                        <input type="text" name="full_name" placeholder="أدخل الاسم بالكامل كما هو في البطاقة" required>
                    </div>
                    <div class="field">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" placeholder="example@domain.com" required>
                    </div>
                    <div class="field">
                        <label>الرقم القومي</label>
                        <input type="text" name="national_id" placeholder="14 رقم" maxlength="14" required>
                    </div>
                    <div class="field">
                        <label>رقم الهاتف</label>
                        <input type="text" name="phone_number" placeholder="01xxxxxxxxx">
                    </div>
                    <div class="field">
                        <label>الكلية / الجهة</label>
                        <input type="text" name="faculty" placeholder="مثال: كلية الطب">
                    </div>
                </div>

                <p class="section-label" style="margin-top:20px;">ثالثاً: أمان الحساب</p>
                <div class="form-grid">
                    <div class="field">
                        <label>كلمة المرور</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <div class="field">
                        <label>تأكيد كلمة المرور</label>
                        <input type="password" name="confirm_password" placeholder="••••••••" required>
                    </div>
                </div>

                <div style="background:#fffbeb; border:1px solid #fef3c7; padding:15px; border-radius:10px; margin-top:20px; font-size:0.8rem; color:#92400e;">
                    <i class="fas fa-info-circle"></i> سيتم تفعيل هذا الحساب فور إنشائه ليتمكن المستخدم من الدخول مباشرة.
                </div>

                <div class="form-actions" style="margin-top:30px; display:flex; justify-content:space-between; align-items:center;">
                    <a href="dashboard.php" class="btn-cancel">إلغاء والعودة</a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> حفظ البيانات وإنشاء الحساب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function selectRole(radio) {
        document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
        document.getElementById('card-' + radio.value).classList.add('selected');
    }
    </script>
</body>
</html>