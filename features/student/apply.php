<?php
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('student'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../includes/sidebar.php';
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
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .upload-status {
            display: none;
            color: var(--success-base);
            font-size: 0.85rem;
            margin-top: 2px;
            font-weight: 700;
            align-items: center;
            gap: 5px;
        }

        .upload-status.active {
            display: flex;
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


        select.investigator-title {
            padding: 12px;
            min-width: 170px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--primary-light);
            color: var(--text-main);
            font-family: 'Cairo', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition-smooth);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: linear-gradient(45deg, transparent 50%, var(--accent-base) 50%),
                              linear-gradient(135deg, var(--accent-base) 50%, transparent 50%);
            background-position: calc(100% - 18px) calc(50% - 3px),
                                 calc(100% - 12px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
            padding-left: 35px;
        }
        
        select.investigator-title:focus {
            outline: none;
            border-color: var(--accent-base);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        @media (max-width: 1000px) {
            body {
                margin-right: 0; 
                padding: 20px; 
            }

            .form-card {
                padding: 20px; 
                margin: 20px auto;
            }

            .grid-container {
                grid-template-columns: 1fr; 
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
        <h4>يرجى ملء كافة البيانات ورفع الملفات المطلوبة بعناية.</h4>
        <h4>يجب ان تكون كل الملفات المرفوعة فى صيغة pdf او word</h4>
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
        <input type="hidden" name="form_source" value="apply_research_form">
        <div class="grid-container">
            <div class="field-group full-width">
                <label>عنوان البحث</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>" minlength="3" maxlength="80" required placeholder="اكتب هنا عنوان البحث الكامل">
            </div>

            <!--div class="field-group full-width">
                <label>الكلمات المفتاحية مفصولين بفاصلة (حد أدنى 5 كلمات) </label>
                <input type="text" name="keywords" placeholder="مثال : انزلاق غضروفى , قطنية , عرق النسا , اسفل الظهر , سقوط القدم" value="<?= htmlspecialchars($data['keywords'] ?? '') ?>" minlength="3" required>
            </div-->
            <div class="field-group full-width">
                <label>الكلمات المفتاحية (الحد الأدنى 5 كلمات)</label>

                <div id="keywordsWrapper">
                    <div class="keyword-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية 1" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                        <div style="width:45px;"></div>
                    </div>
                    <div class="keyword-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية 2" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                        <div style="width:45px;"></div>
                    </div>
                    <div class="keyword-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية 3" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                        <div style="width:45px;"></div>
                    </div>
                    <div class="keyword-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية 4" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                        <div style="width:45px;"></div>
                    </div>
                    <div class="keyword-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية 5" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                        <button type="button" onclick="addKeywordRow()" style="width:45px; height:45px; border:none; border-radius:50%; background:var(--accent-base); color:#fff; font-size:22px; cursor:pointer;">
                            +
                        </button>
                        
                    </div>
                </div>

                <input type="hidden" name="keywords" id="keywords_hidden" value="<?= htmlspecialchars($data['keywords'] ?? '') ?>">
            </div>

            <div class="field-group full-width">
                <label>اسم الباحث الرئيسي</label>
                <input type="text" name="principal_investigator" value="<?= htmlspecialchars($data['principal_investigator'] ?? '') ?>" minlength="3" maxlength="80" required>
            </div>

            <!--div class="field-group">
                <label>المشاركون في البحث (مفصولين بفاصلة)</label>
                <input type="text" name="co_investigators" placeholder="مثال : محمد ابراهيم , احمد اسامة  " value="<?= htmlspecialchars($data['co_investigators'] ?? '') ?>" minlength="3" required>
            </div-->



            <div class="field-group full-width">
                <label>المشاركون في البحث</label>

                <div id="coInvestigatorsWrapper">

                    <div class="co-investigator-row" style="display:flex; gap:10px; margin-bottom:10px; align-items:center;">
                        <select class="investigator-title" style="padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
                            <option value="عضو هيئة تدريس">عضو هيئة تدريس</option>
                            <option value="باحث">باحث</option>
                            <option value="طالب">طالب</option>
                        </select>

                        <input type="text"
                               class="investigator-name"
                               placeholder="الاسم والانتماء (الجامعة - الكلية - القسم) مثال : مصطفى السيد شحاته اخصائى باطنة مستشفى جامعة بورسعيد"
                               style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">

                        <button type="button"
                                onclick="addInvestigatorRow()"
                                style="width:45px; height:45px; border:none; border-radius:50%; background:var(--accent-base); color:#fff; font-size:22px; cursor:pointer;">
                            +
                        </button>
                    </div>

                </div>

                <input type="hidden" name="co_investigators" id="co_investigators_hidden"
                       value="<?= htmlspecialchars($data['co_investigators'] ?? '') ?>">
            </div>
            
            <!--div class="file-input-wrapper">
                <label>ملف البحث (Research)</label>
                <input type="file" required name="research">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div-->

            <div class="file-input-wrapper">
                <label>نموذج البروتوكول</label>
                <input type="file" required name="protocol">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>إقرار تضارب المصالح</label>
                <input type="file" required name="conflict_of_interest">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>قائمة مراجعة IRB</label>
                <input type="file" required name="irb_checklist">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>موافقة الباحث الرئيسي</label>
                <input type="file" required name="pi_consent">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>موافقة المريض</label>
                <input type="file" required name="patient_consent">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>موافقة الصور والخزعات</label>
                <input type="file" required name="photos_biopsies_consent">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>

            <div class="file-input-wrapper">
                <label>طلب مراجعة البروتوكول</label>
                <input type="file" required name="protocol_review_app">
                <div class="upload-status"><i class="fa-solid fa-circle-check"></i> تم اختيار الملف</div>
            </div>
        </div>

        <button type="submit" class="btn-submit">إرسال الطلب للمراجعة</button>
    </form>
</div>
<script>
function addInvestigatorRow() {
    const wrapper = document.getElementById("coInvestigatorsWrapper");

    const row = document.createElement("div");
    row.className = "co-investigator-row";
    row.style.cssText = "display:flex; gap:10px; margin-bottom:10px; align-items:center;";

    row.innerHTML = `
        <select class="investigator-title" style="padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
            <option value="عضو هيئة تدريس">عضو هيئة تدريس</option>
            <option value="باحث">باحث</option>
            <option value="طالب">طالب</option>
        </select>

        <input type="text"
               class="investigator-name"
               placeholder="الاسم والانتماء (الجامعة - الكلية - القسم)"
               style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">

        <button type="button"
                onclick="removeInvestigatorRow(this)"
                style="width:45px; height:45px; border:none; border-radius:50%; background:#dc3545; color:#fff; font-size:20px; cursor:pointer;">
            ×
        </button>
    `;

    wrapper.appendChild(row);
}

function removeInvestigatorRow(btn) {
    btn.parentElement.remove();
    buildCoInvestigators();
}

function buildCoInvestigators() {
    const rows = document.querySelectorAll(".co-investigator-row");
    let result = [];

    rows.forEach(row => {
        const title = row.querySelector(".investigator-title").value.trim();
        const name  = row.querySelector(".investigator-name").value.trim();

        if (name !== "") {
            result.push(title + " " + name);
        }
    });

    document.getElementById("co_investigators_hidden").value = result.join(",");
}

function addKeywordRow() {
    const wrapper = document.getElementById("keywordsWrapper");

    const row = document.createElement("div");
    row.className = "keyword-row";
    row.style.cssText = "display:flex; gap:10px; margin-bottom:10px; align-items:center;";

    row.innerHTML = `
        <input type="text" class="keyword-input" placeholder="كلمة مفتاحية إضافية" required style="flex:1; padding:12px; border:1px solid var(--border-light); border-radius:var(--radius-md);">
        <button type="button" onclick="removeKeywordRow(this)" style="width:45px; height:45px; border:none; border-radius:50%; background:#dc3545; color:#fff; font-size:20px; cursor:pointer;">
            ×
        </button>
    `;

    wrapper.appendChild(row);
}

function removeKeywordRow(btn) {
    btn.parentElement.remove();
    buildKeywords();
}

function buildKeywords() {
    const inputs = document.querySelectorAll(".keyword-input");
    let result = [];

    inputs.forEach(input => {
        const val = input.value.trim();
        if (val !== "") {
            result.push(val);
        }
    });

    document.getElementById("keywords_hidden").value = result.join(",");
}

document.addEventListener("input", function(e){
    if(e.target.classList.contains("keyword-input")){
        buildKeywords();
    }
});

document.querySelector("form").addEventListener("submit", function () {
    buildCoInvestigators();
    buildKeywords();
});

document.addEventListener("input", function(e){
    if(e.target.classList.contains("investigator-name")){
        buildCoInvestigators();
    }
});

document.addEventListener("change", function(e){
    if(e.target.classList.contains("investigator-title")){
        buildCoInvestigators();
    }
});
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const statusIndicator = this.parentElement.querySelector('.upload-status');
        
        if (this.files[0]) {
            const fileSize = this.files[0].size; 
            const maxSize = 4 * 1024 * 1024; 

            if (fileSize > maxSize) {
                alert("خطأ: حجم ملف (" + this.previousElementSibling.innerText + ") كبير جداً. الحد الأقصى المسموح به هو 4 ميجابايت فقط.");
                this.value = ""; 
                statusIndicator.classList.remove('active'); 
            } else {
                statusIndicator.classList.add('active'); 
            }
        } else {
            statusIndicator.classList.remove('active'); 
        }
    });
});
</script>
</body>
</html>