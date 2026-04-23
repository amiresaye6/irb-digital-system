<?php
//require_once __DIR__ . '/../../includes/header.php';
//require_once __DIR__ . '/../../includes/sidebar.php';
session_start();
$errors = $_SESSION['form_errors']??[];
$data = $_SESSION['form_data']??[];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User Form</title>
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css" />
</head>

<body>

<div class="container">
    <form action="saveData.php" method="POST" enctype="multipart/form-data">
        <div>
            <?php
                    if(is_array($errors) && count($errors) > 0){
                        foreach($errors as $error){
                            echo "<h5>$error</h5>";
                        }
                    }
      
            ?>
        </div>
        <div class="row">
            <div class="field-group">
                <label>عنوان البحث</label>
                <input type="text" name="title" value="<?= htmlspecialchars($data['title'] ?? '') ?>" minlength="2" maxlength="80" required placeholder="اكتب هنا عنوان البحث">
            </div>
            <div class="field-group">
                <label>اسم الباحث الرئيسي</label>
                <input type="text" name="principal_investigator" value="<?= htmlspecialchars($data['principal_investigator'] ?? '') ?>" minlength="2" maxlength="80" required placeholder="اكتب اسم الباحث الرئيسى هنا">
            </div>
            <div class="field-group">
                <label>المشاركون فى البحث</label>
                <input type="text" name="co_investigators" value="<?= htmlspecialchars($data['co_investigators'] ?? '') ?>" minlength="2" required placeholder="اكتب اسماء المشاركون فى البحث هنا مفصول بينهم بعلامة فاصلة ( , )">
            </div>
            <div class="file-input-wrapper">
                <label>البحث</label>
                <input type="file" required name="research">
            </div>
            <div class="file-input-wrapper">
                <label>نموذج البرونوكول</label>
                <input type="file" required name="protocol">
            </div>
            <div class="file-input-wrapper">
                <label>اقرار تضارب المصالح</label>
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
        <button type="submit" class="btn-add">تقديم الطلب</button>
    </form>
</div>

</body>
</html>
<?php
require_once __DIR__ .'/../../includes/footer.php'; 
?>