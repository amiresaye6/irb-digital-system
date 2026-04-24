<?php
 require_once __DIR__ . '/../../includes/sidebar.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$errors = $_SESSION['form_errors'] ?? [];
$data = $_SESSION['form_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقديم طلب بحث جديد</title>
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: var(--bg-page);
            font-family: 'Cairo', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 50px;
            margin-right: 100px;
        }

        .page-header {
            max-width: 900px;
            margin: 0 auto 10px auto;
            text-align: right;
        }

        .page-title-container {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 12px;
        }

        .page-title-container h1 {
            color: var(--primary-base);
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
        }

        .page-title-container i {
            color: var(--accent-base); 
            font-size: 1.8rem;
        }

        .page-subtitle {
            color: var(--text-muted);
            margin-top: 8px;
            font-size: 1.1rem;
        }
        /* --------------------------- */

        .form-card {
            background: var(--bg-surface);
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            border-top: 5px solid var(--accent-base);
        }

        .form-header {
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 15px;
        }

        .form-header h2 {
            color: var(--primary-base);
            margin: 0;
        }

        .error-box {
            background-color: var(--alert-light);
            color: var(--alert-base);
            padding: 15px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            border-right: 5px solid var(--alert-base);
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
        }

        label {
            font-weight: 700;
            color: var(--primary-base);
            font-size: 0.95rem;
        }

        input[type="text"], 
        input[type="file"] {
            padding: 12px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            transition: var(--transition-smooth);
            background: var(--primary-light);
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--accent-base);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .file-input-wrapper {
            background: #fcfcfc;
            padding: 15px;
            border: 1px dashed var(--border-dark);
            border-radius: var(--radius-md);
        }

        .btn-submit {
            background-color: var(--accent-base);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition-smooth);
            width: 100%;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background-color: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 1000px) {
            body {
                margin-right: 0; /* إلغاء المساحة اللي كانت محجوزة للـ sidebar */
                padding: 20px;   /* تقليل الحواف عشان الشاشات الصغيرة */
            }

            .form-card {
                padding: 20px;   /* تصغير الحواف الداخلية للكارت */
                margin: 20px auto;
            }

            .grid-container {
                grid-template-columns: 1fr; /* جعل الحقول تحت بعضها تماماً */
            }
        }
    </style>
</head>

<body>

<div class="page-header">
    <div class="page-title-container">
        <h1>تقديم بحث جديد</h1>
        <i class="fa-solid fa-file-circle-plus"></i>
    </div>
    <p class="page-subtitle">يرجى تعبئة بيانات المقترح البحثي ورفع المرفقات اللازمة لبدء المراجعة</p>
</div>

<div class="form-card">
    <div class="form-header">
        <h2>نموذج تقديم مقترح بحثي</h2>
        <p style="color: var(--text-muted);">يرجى ملء كافة البيانات ورفع الملفات المطلوبة بعناية.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul style="margin:0; padding-right: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="saveData.php" method="POST" enctype="multipart/form-data">
        <div class="grid-container">
            <div class="field-group full-width">
                <label>عنوان البحث</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>" minlength="3" required placeholder="اكتب هنا عنوان البحث الكامل">
            </div>

            <div class="field-group">
                <label>اسم الباحث الرئيسي</label>
                <input type="text" name="principal_investigator" value="<?= htmlspecialchars($data['principal_investigator'] ?? '') ?>" minlength="3" required>
            </div>

            <div class="field-group">
                <label>المشاركون في البحث (مفصولين بفاصلة)</label>
                <input type="text" name="co_investigators" placeholder="مثال : محمد رمضان , احمد العوضى" value="<?= htmlspecialchars($data['co_investigators'] ?? '') ?>" minlength="3" required>
            </div>

            <div class="file-input-wrapper">
                <label>ملف البحث (Research)</label>
                <input type="file" required name="research">
            </div>

            <div class="file-input-wrapper">
                <label>نموذج البروتوكول</label>
                <input type="file" required name="protocol">
            </div>

            <div class="file-input-wrapper">
                <label>إقرار تضارب المصالح</label>
                <input type="file" required name="conflict_of_interest">
            </div>

            <div class="file-input-wrapper">
                <label>قائمة مراجعة IRB</label>
                <input type="file" required name="irb_checklist">
            </div>

            <div class="file-input-wrapper">
                <label>موافقة الباحث الرئيسي</label>
                <input type="file" required name="pi_consent">
            </div>

            <div class="file-input-wrapper">
                <label>موافقة المريض</label>
                <input type="file" required name="patient_consent">
            </div>

            <div class="file-input-wrapper">
                <label>موافقة الصور والخزعات</label>
                <input type="file" required name="photos_biopsies_consent">
            </div>

            <div class="file-input-wrapper">
                <label>طلب مراجعة البروتوكول</label>
                <input type="file" required name="protocol_review_app">
            </div>
        </div>

        <button type="submit" class="btn-submit">إرسال الطلب للمراجعة</button>
    </form>
</div>
<script>
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.files[0]) {
            const fileSize = this.files[0].size; 
            const maxSize = 4 * 1024 * 1024; 

            if (fileSize > maxSize) {
                alert("خطأ: حجم ملف (" + this.previousElementSibling.innerText + ") كبير جداً. الحد الأقصى المسموح به هو 4 ميجابايت فقط.");
                this.value = ""; 
            }
        }
    });
});
</script>
</body>
</html>