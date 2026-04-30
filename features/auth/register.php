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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--bg-page);
            color: var(--text-main);
            padding: 40px 16px;
            min-height: 100vh;
            direction: rtl;
        }

        /* ✅ Steps Bar */
        .steps-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            gap: 0;
             width: 100%;        /* ✅ أضيفي دي */
    max-width: 680px;
     margin-left: auto;
    margin-right: auto;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .step-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            border: 2px solid var(--border-light);
            background: var(--bg-surface);
            color: var(--text-muted);
            transition: all var(--transition-smooth);
        }

        .step.active .step-circle {
            border-color: var(--accent-base);
            background: var(--accent-base);
            color: white;
        }

        .step.done .step-circle {
            border-color: var(--accent-base);
            background: var(--accent-light);
            color: var(--accent-dark);
        }

        .step-label {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            font-weight: 600;
        }

        .step.active .step-label { color: var(--accent-dark); font-weight: 700; }
        .step.done  .step-label  { color: var(--accent-dark); }

        .step-line {
            /* width: 80px; */
            height: 2px;
            background: var(--border-light);
            margin-bottom: 20px;
             flex: 1;
        }

        .step-line.done { background: var(--accent-base); }

        /* ✅ Card */
        .card {
            background: var(--bg-surface);
            border-radius: var(--radius-lg);
            border: 0.5px solid var(--border-light);
            padding: 36px;
            max-width: 680px;
            margin: 0 auto;
            box-shadow: var(--shadow-md);
            border-top: 4px solid var(--accent-base);
        }

        .card-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary-base);
            margin-bottom: 6px;
            text-align: right;
        }

        .card-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
            text-align: right;
            line-height: 1.6;
        }

        /* ✅ Section Label */
        .section-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--accent-base);
            text-align: right;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 0.5px;
            background: var(--border-light);
        }

        .section-divider {
            border: none;
            border-top: 0.5px solid var(--border-light);
            margin: 24px 0;
        }

        /* ✅ Grid */
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        /* ✅ Fields */
        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field label {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-base);
            text-align: right;
        }

        .field input {
            border: 1.5px solid var(--border-light);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 14px;
            background: var(--primary-light);
            color: var(--text-main);
            font-family: 'Cairo', sans-serif;
            text-align: right;
            outline: none;
            transition: all var(--transition-smooth);
            width: 100%;
        }

        .field input:focus {
            border-color: var(--accent-base);
            background: var(--bg-surface);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .field input::placeholder {
            color: var(--text-muted);
            font-size: 13px;
        }

        .field input.error {
            border-color: var(--alert-base);
            box-shadow: 0 0 0 3px var(--alert-light);
        }

        /* ✅ File Input */
        .field input[type="file"] {
            padding: 8px 14px;
            cursor: pointer;
            background: var(--bg-page);
            border-style: dashed;
        }

        /* ✅ Error Box */
        .error-box {
            background: var(--alert-light);
            border-radius: var(--radius-sm);
            border-right: 4px solid var(--alert-base);
            padding: 14px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--status-rejected-text);
            text-align: right;
        }

        .error-box p { margin: 4px 0; }

        /* ✅ Notice */
        .notice {
            background: var(--accent-light);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-size: 12px;
            color: var(--accent-dark);
            margin-top: 16px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            text-align: right;
            border-right: 3px solid var(--accent-base);
        }

        /* ✅ Actions */
        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 28px;
        }

        .btn-submit {
            background: var(--accent-base);
            border: none;
            color: white;
            padding: 12px 32px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Cairo', sans-serif;
            transition: all var(--transition-smooth);
        }

        .btn-submit:hover {
            background: var(--accent-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-cancel {
            border: 1px solid var(--border-light);
            background: transparent;
            color: var(--text-muted);
            padding: 12px 28px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
            text-decoration: none;
            display: inline-block;
            transition: all var(--transition-smooth);
        }

        .btn-cancel:hover {
            background: var(--bg-page);
            color: var(--primary-base);
        }

        /* ✅ Footer */
        .footer-note {
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 20px;
        }

        .footer-note a {
            color: var(--accent-base);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover { text-decoration: underline; }

        /* ✅ Responsive */
        @media (max-width: 520px) {
            .row        { grid-template-columns: 1fr; }
            .card       { padding: 24px 16px; }
            .step-line  { width: 40px; }
            body        { padding: 24px 12px; }
        }
    </style>
</head>
<body>

<div class="steps-bar">
    <div class="step active">
        <div class="step-circle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
            </svg>
        </div>
        <span class="step-label">البيانات الأساسية</span>
    </div>
    

    <div class="step-line"></div>

    <div class="step">
        <div class="step-circle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
            </svg>
        </div>
        <span class="step-label">رفع المستندات</span>
    </div>

    <div class="step-line"></div>
<div class="step">
        <div class="step-circle">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
            </svg>
        </div>
        <span class="step-label">تأكيد</span>
    </div>
    
</div>

<!-- ✅ Form Card -->
<div class="card">
    <p class="card-title">إنشاء حساب طالب جديد</p>
    <p class="card-sub">يرجى إدخال بياناتك الشخصية بدقة لضمان سرعة مراجعة طلبك.</p>

    <!-- ✅ Errors -->
    <?php if(!empty($errors)): ?>
        <div class="error-box">
            <?php foreach($errors as $error): ?>
                <p>• <?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="save.php" method="POST" enctype="multipart/form-data">

        <!-- البيانات الشخصية -->
        <p class="section-label">البيانات الشخصية</p>

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
                       value="<?php echo htmlspecialchars($old_data['phone_number'] ?? ''); ?>"
                       required>
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
                       placeholder="اسم الكلية"
                       value="<?php echo htmlspecialchars($old_data['faculty'] ?? ''); ?>"
                       required>
            </div>
            <div class="field">
                <label>القسم العلمي</label>
                <input type="text" name="department"
                       placeholder="مثال: قسم الجراحة العامة"
                       value="<?php echo htmlspecialchars($old_data['department'] ?? ''); ?>"
                       required>
            </div>
        </div>

        <!-- كلمة المرور -->
        <hr class="section-divider">
        <p class="section-label">بيانات الدخول</p>

        <div class="row">
            <div class="field">
                <label>كلمة المرور</label>
                <input type="password" name="password"
                       placeholder="••••••••" required>
            </div>
            <div class="field">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password"
                       placeholder="••••••••" required>
            </div>
        </div>

        <!-- وثائق الهوية -->
        <hr class="section-divider">
        <p class="section-label">وثائق الهوية</p>

        <div class="row">
            <div class="field">
                <label>صورة البطاقة (وجه)</label>
                <input type="file" name="id_front"
                       accept=".jpg,.jpeg,.png" required>
            </div>
            <div class="field">
                <label>صورة البطاقة (ظهر)</label>
                <input type="file" name="id_back"
                       accept=".jpg,.jpeg,.png" required>
            </div>
        </div>

        <div class="notice">
            <span style="font-size:14px;flex-shrink:0">ℹ</span>
            <span>
                بإنشائك لهذا الحساب، أنت توافق على سياسات الخصوصية وشروط الاستخدام
                الخاصة بنظام المراجعة الأخلاقية للأبحاث.
            </span>
        </div>

        <div class="actions">
            <button type="submit" class="btn-submit">
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