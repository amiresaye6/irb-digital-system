<?php
session_start();

$errors   = $_SESSION['errors']   ?? [];
$old_data = $_SESSION['old_data'] ?? [];

unset($_SESSION['errors']);
unset($_SESSION['old_data']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد | IRB</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #ecf0f1;
            color: #1e293b;
            padding: 32px 16px;
            min-height: 100vh;
            direction: rtl;
        }

        .steps-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            gap: 0;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .step-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
            border: 2px solid #e2e8f0;
            background: #ffffff;
            color: #64748b;
        }

        .step.active .step-circle {
            border-color: #1abc9c;
            background: #1abc9c;
            color: white;
        }

        .step.done .step-circle {
            border-color: #1abc9c;
            background: #e1f5ee;
            color: #0f6e56;
        }

        .step-label {
            font-size: 12px;
            color: #64748b;
            white-space: nowrap;
        }

        .step.active .step-label {
            color: #0f6e56;
            font-weight: 500;
        }

        .step-line {
            width: 80px;
            height: 2px;
            background: #e2e8f0;
            margin-bottom: 20px;
        }

        .step-line.done { background: #1abc9c; }

        .card {
            background: #ffffff;
            border-radius: 16px;
            border: 0.5px solid #e2e8f0;
            padding: 32px;
            max-width: 680px;
            margin: 0 auto;
        }

        .card-title {
            font-size: 16px;
            font-weight: 500;
            color: #0f172a;
            margin-bottom: 6px;
            text-align: right;
        }

        .card-sub {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 28px;
            text-align: right;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 13px;
            font-weight: 500;
            color: #1e293b;
            text-align: right;
        }

        .field input {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            background: #ffffff;
            color: #1e293b;
            font-family: 'Cairo', sans-serif;
            text-align: right;
            outline: none;
            transition: border 0.2s;
            width: 100%;
        }

        .field input:focus {
            border-color: #1abc9c;
            box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.1);
        }

        .field input::placeholder {
            color: #94a3b8;
            font-size: 13px;
        }

        .field input.error {
            border-color: #e24b4a;
            box-shadow: 0 0 0 3px rgba(226, 75, 74, 0.1);
        }

        .field input[type="file"] {
            padding: 8px 14px;
            cursor: pointer;
            background: #f8fafc;
        }

        .section-divider {
            border: none;
            border-top: 0.5px solid #e2e8f0;
            margin: 20px 0;
        }

        .section-label {
            font-size: 12px;
            font-weight: 500;
            color: #64748b;
            text-align: right;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .error-box {
            background: #fadbd8;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #991b1b;
            text-align: right;
        }

        .error-box p { margin: 4px 0; }

        .notice {
            background: #e1f5ee;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 12px;
            color: #0f6e56;
            margin-top: 16px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            text-align: right;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
        }

        .btn-cancel {
            border: 1px solid #e2e8f0;
            background: transparent;
            color: #64748b;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .btn-next {
            background: #1abc9c;
            border: none;
            color: white;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Cairo', sans-serif;
            transition: background 0.2s;
        }

        .btn-next:hover { background: #16a085; }

        .footer-note {
            text-align: center;
            font-size: 12px;
            color: #64748b;
            margin-top: 20px;
        }

        .footer-note a {
            color: #1abc9c;
            text-decoration: none;
            font-weight: 500;
        }

        @media (max-width: 520px) {
            .row { grid-template-columns: 1fr; }
            .card { padding: 20px 16px; }
            .step-line { width: 40px; }
        }
    </style>
</head>
<body>

<div class="steps-bar">
    <div class="step done">
        <div class="step-circle">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M13.5 3.5L6 11 2.5 7.5l-1 1L6 13l8.5-8.5z"/>
            </svg>
        </div>
        <span class="step-label">تأكيد</span>
    </div>
    <div class="step-line done"></div>
    <div class="step done">
        <div class="step-circle">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                <path d="M13.5 3.5L6 11 2.5 7.5l-1 1L6 13l8.5-8.5z"/>
            </svg>
        </div>
        <span class="step-label">رفع المستندات</span>
    </div>
    <div class="step-line done"></div>
    <div class="step active">
        <div class="step-circle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <span class="step-label">البيانات الأساسية</span>
    </div>
</div>

<div class="card">
    <p class="card-title">إنشاء حساب طالب جديد</p>
    <p class="card-sub">يرجى إدخال بياناتك الشخصية بدقة لضمان سرعة مراجعة طلبك.</p>

    <?php if(!empty($errors)): ?>
        <div class="error-box">
            <?php foreach($errors as $error): ?>
                <p>• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST" enctype="multipart/form-data">

        <div class="row">
            <div class="field">
                <label>الاسم الثلاثي بالكامل (كما في البطاقة)</label>
                <input type="text" name="full_name"
                       placeholder="أدخل اسمك الكامل"
                       value="<?php echo htmlspecialchars($old_data['full_name'] ?? ''); ?>"
                       required>
            </div>
             <div class="field">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email"
                       placeholder="student@university.edu.eg"
                       value="<?php echo htmlspecialchars($old_data['email'] ?? ''); ?>"
                       class="<?php echo (!empty($errors) && !filter_var($old_data['email'] ?? '', FILTER_VALIDATE_EMAIL)) ? 'error' : ''; ?>"
                       required>
            </div>
        </div>

        <div class="row">
            <div class="field">
                <label>رقم الهاتف الجوال</label>
                <input type="text" name="phone_number"
                       placeholder="01X XXXX XXXX"
                       value="<?php echo htmlspecialchars($old_data['phone_number'] ?? ''); ?>">
            </div>
            <div class="field">
                <label>رقم البطاقة الشخصية (الرقم القومي)</label>
                <input type="text" name="national_id"
                       placeholder="14 رقم" maxlength="14"
                       value="<?php echo htmlspecialchars($old_data['national_id'] ?? ''); ?>"
                       required>
            </div>
        </div>

        <div class="row">
        
            <div class="field">
                <label>الكلية</label>
                <input type="text" name="faculty"
                       placeholder="اختر الكلية"
                       value="<?php echo htmlspecialchars($old_data['faculty'] ?? ''); ?>">
            </div>
            <div class="field">
                <label>القسم العلمي</label>
                <input type="text" name="department"
                       placeholder="مثال: قسم الكيمياء الحيوية"
                       value="<?php echo htmlspecialchars($old_data['department'] ?? ''); ?>">
            </div>
        </div>

        <div class="row">
             <div class="field">
                <label>كلمة المرور</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="field">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required>
            </div>
           
        </div>

        <hr class="section-divider">
        <p class="section-label">وثائق الهوية</p>

        <div class="row">
            <div class="field">
                <label>صورة البطاقة (ظهر)</label>
                <input type="file" name="id_back" accept=".jpg,.jpeg,.png" required>
            </div>
            <div class="field">
                <label>صورة البطاقة (وجه)</label>
                <input type="file" name="id_front" accept=".jpg,.jpeg,.png" required>
            </div>
        </div>

        <div class="notice">
            <span style="font-size:14px; flex-shrink:0">ℹ</span>
            <span>بإنشائك لهذا الحساب، أنت توافق على سياسات الخصوصية وشروط الاستخدام الخاصة بنظام المراجعة الأخلاقية للأبحاث.</span>
        </div>

        <div class="actions">
            <button type="submit" class="btn-next">
                إنشاء الحساب
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/>
                </svg>
            </button>
            <a href="login.php" class="btn-cancel">إلغاء</a>
        </div>

    </form>
</div>

<p class="footer-note">
    لديك حساب بالفعل؟ <a href="login.php">تسجيل دخول</a>
</p>

</body>
</html>