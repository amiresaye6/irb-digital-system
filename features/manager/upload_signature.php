<?php
require_once '../../classes/Database.php';
require_once '../../includes/irb_helpers.php';
require_once __DIR__ . "/../../classes/Auth.php";

Auth::checkRole(['manager']);
$db = new Database();
$conn = $db->getconn();

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['sig_image'])) {
    $user_id = $_SESSION['user_id']; 
    
    $target_dir = "../../uploads/signatures/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $file_extension = pathinfo($_FILES["sig_image"]["name"], PATHINFO_EXTENSION);
    $file_name = "sig_user_" . $user_id . ".png"; 
    $target_file = $target_dir . $file_name;

    if (move_uploaded_file($_FILES["sig_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO signatures (userId, signature_url) 
                                VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE signature_url=?");
        $stmt->bind_param("iss", $user_id, $target_file, $target_file);
        $stmt->execute();
        $message = '<div class="alert alert-success">تم تحديث توقيعك بنجاح!</div>';
    }
    else {
        $message = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i> فشل رفع الصورة، تأكد من صلاحيات المجلد.</div>';
    }
}

include '../../includes/header.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات التوقيع الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .upload-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; }
        .preview-container {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background: #fafafa;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-img { max-height: 120px; display: none; filter: drop-shadow(0 5px 10px rgba(0,0,0,0.1)); }
        .form-label { font-weight: 600; color: #495057; }
        .btn-upload { background: #16a085; border: none; padding: 12px; font-weight: bold; transition: 0.3s; }
        .btn-upload:hover { background: #12876f; transform: translateY(-2px); }
        .help-text { font-size: 0.85rem; color: #6c757d; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            
            <?= $message ?>

            <div class="card upload-card p-4">
                <div class="text-center mb-4">
                    <div class="icon-circle bg-light-primary mb-3">
                        <i class="fas fa-pen-nib fa-3x text-primary"></i>
                    </div>
                    <h3 class="fw-bold">التوقيع الإلكتروني</h3>
                    <p class="text-muted small">قم بإعداد توقيعك الذي سيظهر على الشهادات المعتمدة</p>
                </div>

                <form method="POST" enctype="multipart/form-data" id="sigForm">                    
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-image me-2"></i>صورة التوقيع</label>
                        <input type="file" name="sig_image" id="sigInput" class="form-control" 
                            accept="image/png" required onchange="previewImage(event)">
                        <div class="help-text mt-1">يُفضل استخدام صيغة **PNG** بخلفية شفافة للحصول على أفضل نتيجة.</div>
                    </div>

                    <div class="mb-4">
                        <p class="form-label text-center">معاينة التوقيع:</p>
                        <div class="preview-container">
                            <i id="placeholderIcon" class="fas fa-cloud-upload-alt fa-2x text-muted opacity-25"></i>
                            <img id="preview" class="preview-img" alt="معاينة التوقيع">
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg btn-upload">
                            حفظ التوقيع والاعتماد <i class="fas fa-save ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        const preview = document.getElementById('preview');
        const icon = document.getElementById('placeholderIcon');
        
        reader.onload = function(){
            preview.src = reader.result;
            preview.style.display = 'block';
            icon.style.display = 'none';
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</body>
</html>