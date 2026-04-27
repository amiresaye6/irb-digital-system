<?php
// Include initialization which loads env, Database, Auth, and starts session
require_once __DIR__ . '/../../init.php';

// Protect route using Auth class
Auth::checkRole(['sample_officer']);

$db = new Database();
$conn = $db->getconn();

$user = Auth::user();
$officer_id = $user['id'];
$app_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch application and ensure it's in the correct stage (Allowing both new and edit stages)
$stmt = $conn->prepare("SELECT a.*, u.full_name as student_name FROM applications a JOIN users u ON a.student_id = u.id WHERE a.id = ? AND a.current_stage IN ('awaiting_sample_calc', 'awaiting_sample_payment')");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application) {
    die("<div style='padding:40px;text-align:center;font-family:Cairo;font-weight:800;font-size:1.2rem;'>الملف غير موجود، أو تجاوز مرحلة التعديل المسموح بها.</div>");
}

// Determine Mode (New vs Edit)
$is_edit = ($application['current_stage'] === 'awaiting_sample_payment');
$sample_data = ['calculated_size' => '', 'sample_amount' => '', 'notes' => ''];

if ($is_edit) {
    $sampStmt = $conn->prepare("SELECT * FROM sample_sizes WHERE application_id = ? AND sampler_id = ? LIMIT 1");
    $sampStmt->bind_param("ii", $app_id, $officer_id);
    $sampStmt->execute();
    $sampRes = $sampStmt->get_result();
    if ($sampRow = $sampRes->fetch_assoc()) {
        $sample_data = $sampRow;
    }
    $sampStmt->close();
}

// Fetch ALL attached documents
$docStmt = $conn->prepare("SELECT document_type, file_path FROM documents WHERE application_id = ?");
$docStmt->bind_param("i", $app_id);
$docStmt->execute();
$documentsResult = $docStmt->get_result();
$documents = $documentsResult->fetch_all(MYSQLI_ASSOC);
$docStmt->close();

// Dictionary for translating document names to Arabic
$docTranslations = [
    'research' => 'ملف البحث الأساسي',
    'protocol' => 'بروتوكول البحث (المنهجية)',
    'conflict_of_interest' => 'إقرار تضارب المصالح',
    'irb_checklist' => 'قائمة مراجعة IRB',
    'pi_consent' => 'موافقة الباحث الرئيسي',
    'patient_consent' => 'موافقة المريض',
    'photos_biopsies_consent' => 'موافقة الصور والخزعات',
    'protocol_review_app' => 'طلب مراجعة البروتوكول'
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $calculated_size = filter_input(INPUT_POST, 'calculated_size', FILTER_VALIDATE_INT);
    $sample_amount = filter_input(INPUT_POST, 'sample_amount', FILTER_VALIDATE_FLOAT);
    $notes = trim($_POST['notes'] ?? '');

    if ($calculated_size > 0 && $sample_amount >= 0) {
        try {
            // Start transaction
            $conn->begin_transaction();

            if ($is_edit) {
                // UPDATE Existing Record
                $updSamp = $conn->prepare("UPDATE sample_sizes SET calculated_size = ?, sample_amount = ?, notes = ? WHERE application_id = ? AND sampler_id = ?");
                $updSamp->bind_param("idsii", $calculated_size, $sample_amount, $notes, $app_id, $officer_id);
                $updSamp->execute();
                $updSamp->close();

                $logAction = "قام بتعديل حجم العينة إلى [$calculated_size] بتكلفة [$sample_amount] جنيه";
                $notifyMessage = "تم تحديث بيانات وتكلفة عينة بحثك (" . $application['serial_number'] . "). يرجى المراجعة وسداد الرسوم.";
                $redirectPage = "requests_history.php";
            } else {
                // INSERT New Record
                $insStmt = $conn->prepare("INSERT INTO sample_sizes (application_id, sampler_id, calculated_size, sample_amount, notes) VALUES (?, ?, ?, ?, ?)");
                $insStmt->bind_param("iiids", $app_id, $officer_id, $calculated_size, $sample_amount, $notes);
                $insStmt->execute();
                $insStmt->close();

                // Update application stage
                $updStmt = $conn->prepare("UPDATE applications SET current_stage = 'awaiting_sample_payment' WHERE id = ?");
                $updStmt->bind_param("i", $app_id);
                $updStmt->execute();
                $updStmt->close();

                $logAction = "Sample size of [$calculated_size] calculated and updated by [" . $user['full_name'] . "]";
                $notifyMessage = "تم حساب حجم العينة لبحثك (" . $application['serial_number'] . "). يرجى المتابعة وسداد رسوم العينة المحددة.";
                $redirectPage = "dashboard.php";
            }

            // Log the action (Audit Trail)
            $logStmt = $conn->prepare("INSERT INTO logs (application_id, user_id, action) VALUES (?, ?, ?)");
            $logStmt->bind_param("iis", $app_id, $officer_id, $logAction);
            $logStmt->execute();
            $logStmt->close();

            // Notify the student
            $channel = 'system';
            $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, application_id, message, channel) VALUES (?, ?, ?, ?)");
            $notifStmt->bind_param("iiss", $application['student_id'], $app_id, $notifyMessage, $channel);
            $notifStmt->execute();
            $notifStmt->close();

            // Commit transaction
            $conn->commit();

            $_SESSION['success'] = $is_edit ? "تم تعديل بيانات العينة بنجاح." : "تم إدخال حجم العينة بنجاح، وتم إرسال إشعار للطالب للدفع.";
            header("Location: " . $redirectPage);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "حدث خطأ أثناء حفظ البيانات: " . $e->getMessage();
        }
    } else {
        $error = "يرجى إدخال أرقام صحيحة وإيجابية لحجم العينة والمبلغ.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'تعديل بيانات العينة' : 'معالجة العينة' ?> - <?= htmlspecialchars($application['serial_number']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body { background: var(--bg-page); }
        .content { margin-right: 260px; min-height: 100vh; padding: 30px; background: var(--bg-page); display: flex; flex-direction: column; align-items: center; }
        .wrapper { width: 100%; max-width: 900px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .page-title { color: var(--primary-base); font-weight: 800; font-size: 1.6rem; margin: 0; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--text-muted); text-decoration: none; font-weight: 700; transition: var(--transition-smooth); }
        .btn-back:hover { color: var(--primary-base); }
        
        .info-card { background: #fff; border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); border: 1px solid var(--border-light); margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .info-item label { display: block; color: var(--text-muted); font-size: 0.85rem; font-weight: 700; margin-bottom: 4px; }
        .info-item div { font-weight: 800; color: var(--text-main); font-size: 1.05rem; }
        
        .docs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 15px; }
        .document-box { background: var(--primary-light); border: 1px dashed var(--accent-base); border-radius: var(--radius-md); padding: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .document-box .doc-info { display: flex; align-items: center; gap: 10px; }
        .document-box .doc-info i { font-size: 1.5rem; color: var(--accent-base); }
        .document-box .doc-name { font-weight: 700; color: var(--text-main); font-size: 0.95rem; }
        
        .doc-actions { display: flex; gap: 8px; }
        .btn-download, .btn-secondary { padding: 8px 14px; border-radius: var(--radius-md); text-decoration: none; font-weight: 700; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 6px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-download { background: var(--accent-base); color: #fff; }
        .btn-download:hover { background: var(--accent-dark); transform: translateY(-2px); }
        .btn-secondary { background: var(--border-light); color: var(--primary-base); }
        .btn-secondary:hover { background: var(--border-dark); transform: translateY(-2px); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 800; color: var(--primary-base); margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px; border: 1.5px solid var(--border-light); border-radius: var(--radius-md); font-family: inherit; font-size: 1rem; transition: var(--transition-smooth); }
        .form-control:focus { border-color: var(--accent-base); outline: none; box-shadow: 0 0 0 3px rgba(26, 188, 156, 0.15); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .btn-submit { background: var(--primary-base); color: #fff; padding: 14px 24px; border: none; border-radius: var(--radius-md); font-family: inherit; font-weight: 800; font-size: 1rem; cursor: pointer; width: 100%; transition: var(--transition-smooth); margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 8px; }
        .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .alert-error { background: var(--status-rejected-bg); color: var(--status-rejected-text); padding: 12px; border-radius: var(--radius-md); font-weight: 700; margin-bottom: 15px; }

        @media(max-width: 768px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <div class="wrapper">
            <div class="page-header">
                <h2 class="page-title"><i class="fa-solid <?= $is_edit ? 'fa-pen-to-square' : 'fa-file-invoice' ?>"></i> <?= $is_edit ? 'تعديل بيانات العينة' : 'إدخال حساب العينة' ?></h2>
                <a href="<?= $is_edit ? 'requests_history.php' : 'dashboard.php' ?>" class="btn-back"><i class="fa-solid fa-arrow-right"></i> عودة للطابور</a>
            </div>

            <?php if (isset($error)): ?>
                    <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <label>رقم الملف</label>
                        <div><?= htmlspecialchars($application['serial_number']) ?></div>
                    </div>
                    <div class="info-item">
                        <label>الباحث الرئيسي</label>
                        <div><?= htmlspecialchars($application['principal_investigator']) ?></div>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>عنوان البحث</label>
                        <div><?= htmlspecialchars($application['title']) ?></div>
                    </div>
                </div>

                <h4 style="color:var(--primary-base); font-weight:800; border-bottom:1px solid var(--border-light); padding-bottom:8px; margin-bottom:15px;"><i class="fa-solid fa-folder-open"></i> المرفقات العلمية</h4>
                
                <?php if (empty($documents)): ?>
                        <div style="color:var(--status-rejected-text); font-weight:700; text-align:center;">لا توجد مستندات مرفوعة لهذا البحث.</div>
                <?php else: ?>
                        <div class="docs-grid">
                            <?php foreach ($documents as $doc):
                                $label = $docTranslations[$doc['document_type']] ?? $doc['document_type'];
                                $filePath = '/irb-digital-system/' . htmlspecialchars($doc['file_path']);
                                $physicalPath = __DIR__ . '/../../' . $doc['file_path'];
                                ?>
                                    <div class="document-box">
                                        <div class="doc-info">
                                            <i class="fa-solid fa-file-pdf"></i>
                                            <span class="doc-name"><?= htmlspecialchars($label) ?></span>
                                        </div>
                                        <?php if (file_exists($physicalPath)): ?>
                                                <div class="doc-actions">
                                                    <a href="<?= $filePath ?>" target="_blank" class="btn-download" title="استعراض في تبويب جديد"><i class="fa-solid fa-eye"></i></a>
                                                    <a href="<?= $filePath ?>" download class="btn-secondary" title="تحميل الملف لجهازك"><i class="fa-solid fa-download"></i></a>
                                                </div>
                                        <?php else: ?>
                                                <span style="color:var(--status-rejected-text); font-weight:700; font-size:0.85rem;">الملف غير متوفر حالياً</span>
                                        <?php endif; ?>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="info-card" style="<?= $is_edit ? 'border: 2px solid var(--warning-base);' : '' ?>">
                <h3 style="color:var(--primary-base); margin-top:0; margin-bottom:20px; font-weight:800; font-size:1.2rem;">
                    <i class="fa-solid <?= $is_edit ? 'fa-pen-to-square' : 'fa-keyboard' ?>"></i> <?= $is_edit ? 'تعديل نتيجة التحليل' : 'نتيجة التحليل التقني' ?>
                </h3>

                <div class="info-grid">
                    <div class="form-group">
                        <label for="calculated_size">حجم العينة المطلوب إحصائياً *</label>
                        <input type="number" id="calculated_size" name="calculated_size" class="form-control"
                            placeholder="مثال: 150" required min="1" value="<?= htmlspecialchars($sample_data['calculated_size']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="sample_amount">التكلفة المالية (جنيه مصري) *</label>
                        <input type="number" step="0.01" id="sample_amount" name="sample_amount" class="form-control"
                            placeholder="مثال: 500.00" required min="0" value="<?= htmlspecialchars($sample_data['sample_amount']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">ملاحظات إضافية (اختياري)</label>
                    <textarea id="notes" name="notes" class="form-control"
                        placeholder="أضف أي توضيحات للطالب بخصوص طريقة الحساب..."><?= htmlspecialchars($sample_data['notes']) ?></textarea>
                </div>

                <button type="submit" class="btn-submit" style="<?= $is_edit ? 'background:var(--warning-base); color:var(--primary-dark);' : '' ?>">
                    <i class="fa-solid <?= $is_edit ? 'fa-floppy-disk' : 'fa-paper-plane' ?>"></i> 
                    <?= $is_edit ? 'حفظ التعديلات' : 'حفظ وإرسال الإشعار للطالب' ?>
                </button>
            </form>
        </div>
    </div>
</body>

</html>