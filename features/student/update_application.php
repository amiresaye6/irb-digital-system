<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /irb-digital-system/login.php"); exit;
}
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: student_researches.php"); exit;
}
require_once __DIR__ . '/../../classes/Applications.php';
require_once __DIR__ . '/../../includes/irb_helpers.php';

$appObj = new Applications();
$student_id = $_SESSION['user_id'];
$app_id = intval($_GET['id']);
$app = $appObj->getApplicationFullDetails($app_id, $student_id);
if (!$app) { die("البحث غير موجود أو لا تملك صلاحية تعديله."); }
if ($app['current_stage'] === 'approved' || $app['current_stage'] === 'rejected') {
    header("Location: student_research_details.php?id=$app_id"); exit;
}
if ($app['current_stage'] !== 'under_review' && !$appObj->hasNeedsModification($app_id)) {
    header("Location: student_research_details.php?id=$app_id"); exit;
}

$documents = $appObj->getApplicationDocuments($app_id);
$feedback = $appObj->getReviewerFeedback($app_id);

$coInvestigators = [];
if (!empty($app['co_investigators'])) {
    $decoded = json_decode($app['co_investigators'], true);
    if (is_array($decoded)) $coInvestigators = $decoded;
}

$docTypes = [
    'research' => ['ملف البحث', 'fa-file-lines', '#2c3e50'],
    'protocol' => ['بروتوكول البحث', 'fa-file-medical', '#2c3e50'],
    'conflict_of_interest' => ['إقرار تعارض المصالح', 'fa-handshake-angle', '#e67e22'],
    'irb_checklist' => ['قائمة فحص IRB', 'fa-list-check', '#1abc9c'],
    'pi_consent' => ['موافقة الباحث الرئيسي', 'fa-user-pen', '#3498db'],
    'patient_consent' => ['نموذج موافقة المرضى', 'fa-clipboard-user', '#9b59b6'],
    'photos_biopsies_consent' => ['موافقة الصور والعينات', 'fa-camera', '#e74c3c'],
    'protocol_review_app' => ['نموذج مراجعة البروتوكول', 'fa-file-shield', '#16a085'],
];

$existingTypes = [];
foreach ($documents as $doc) {
    $existingTypes[$doc['document_type']] = $doc;
}

$error_msg = $_GET['error'] ?? null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_app'])) {
    $hasChanges = false;

    // 1. Update application details
    $newTitle = trim($_POST['title'] ?? '');
    $newPI = trim($_POST['principal_investigator'] ?? '');
    $coNames = $_POST['co_investigators'] ?? [];
    $newCo = array_values(array_filter(array_map('trim', $coNames)));

    if (!empty($newTitle) && !empty($newPI)) {
        $appObj->updateApplicationDetails($app_id, $student_id, $newTitle, $newPI, $newCo);
        $hasChanges = true;
    }

    // 2. Upload / replace documents
    $uploadDir = __DIR__ . '/../../uploads/documents/' . $app_id . '/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    require_once __DIR__ . '/../../classes/Database.php';
    $database = new Database();
    $db = $database->conn;

    $uploadedCount = 0;
    foreach ($docTypes as $type => $label) {
        if (isset($_FILES['doc_' . $type]) && $_FILES['doc_' . $type]['error'] === UPLOAD_ERR_OK) {
            $fileName = time() . '_' . $type . '_' . basename($_FILES['doc_' . $type]['name']);
            $filePath = 'uploads/documents/' . $app_id . '/' . $fileName;
            $fullPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['doc_' . $type]['tmp_name'], $fullPath)) {
                // If a document of this type already exists, UPDATE its path
                if (isset($existingTypes[$type])) {
                    $sql = "UPDATE documents SET file_path = ?, uploaded_at = NOW() WHERE id = ?";
                    $stmt = $db->prepare($sql);
                    $docId = $existingTypes[$type]['id'];
                    $stmt->bind_param("si", $filePath, $docId);
                    $stmt->execute();
                } else {
                    // No existing document of this type — insert new
                    $sql = "INSERT INTO documents (application_id, document_type, file_path) VALUES (?, ?, ?)";
                    $stmt = $db->prepare($sql);
                    $stmt->bind_param("iss", $app_id, $type, $filePath);
                    $stmt->execute();
                }
                $uploadedCount++;
            }
        }
    }
    if ($uploadedCount > 0) $hasChanges = true;

    // Log the changes
    if ($hasChanges) {
        $logParts = [];
        if (!empty($newTitle) && !empty($newPI)) $logParts[] = 'تحديث بيانات البحث (العنوان، الباحث الرئيسي، المشاركون)';
        if ($uploadedCount > 0) $logParts[] = "تحديث $uploadedCount مستند(ات)";
        $logAction = implode(' + ', $logParts);
        Applications::createLog($db, $app_id, $student_id, $logAction);
    }

    if ($hasChanges) {
        header("Location: student_research_details.php?id=$app_id&success=1");
    } else {
        header("Location: update_application.php?id=$app_id&error=" . urlencode('لم يتم إجراء أي تغييرات'));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث البحث</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/includes/style.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body{background:var(--bg-page)}
        .content{margin-right:260px;min-height:100vh;padding:30px 24px;display:flex;flex-direction:column;align-items:center}
        .content>*{width:100%;max-width:950px}
        .page-title{color:var(--primary-base);font-size:1.5rem;font-weight:800;display:flex;align-items:center;gap:12px;margin-bottom:6px}
        .page-title i{color:var(--accent-base)}
        .page-subtitle{color:var(--text-muted);font-size:0.9rem;font-weight:500;margin-bottom:22px}
        .card{background:var(--bg-surface);padding:24px;border-radius:var(--radius-lg);box-shadow:var(--shadow-md);border:1px solid var(--border-light);margin-bottom:20px}
        .card-header{display:flex;align-items:center;gap:10px;margin-bottom:18px;padding-bottom:14px;border-bottom:2px solid var(--border-light)}
        .card-header h3{color:var(--primary-base);font-size:1.1rem;font-weight:800;margin:0}
        .card-header i{color:var(--accent-base);font-size:1.1rem}

        .app-brief{display:flex;align-items:center;gap:14px;padding:14px;background:var(--bg-page);border-radius:var(--radius-md);border:1px solid var(--border-light);margin-bottom:20px}
        .badge-serial{font-weight:800;color:white;background:var(--primary-base);padding:6px 12px;border-radius:var(--radius-sm);font-size:0.85rem;flex-shrink:0}
        .app-brief-title{font-weight:700;color:var(--text-main);font-size:0.95rem}

        .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px}
        .form-group{display:flex;flex-direction:column;gap:6px}
        .form-group.wide{grid-column:1/-1}
        .form-label{font-size:0.85rem;font-weight:800;color:var(--primary-base);display:flex;align-items:center;gap:6px}
        .form-label i{color:var(--accent-base);font-size:0.85rem}
        .form-input{width:100%;border:1.5px solid rgba(189,195,199,0.9);border-radius:var(--radius-md);background:#fff;color:var(--text-main);font-family:inherit;font-size:0.92rem;font-weight:600;padding:12px 14px;transition:all var(--transition-smooth);box-sizing:border-box}
        .form-input:focus{outline:none;border-color:var(--accent-base);box-shadow:0 0 0 3px rgba(26,188,156,0.12)}
        .form-hint{font-size:0.78rem;color:var(--text-muted);font-weight:500}

        /* Co-investigators dynamic list */
        .co-inv-list{display:flex;flex-direction:column;gap:10px}
        .co-inv-item{display:flex;align-items:center;gap:10px;animation:fadeInItem 0.3s ease}
        @keyframes fadeInItem{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
        .co-inv-item .co-inv-num{width:32px;height:32px;border-radius:50%;background:var(--primary-base);color:white;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:800;flex-shrink:0}
        .co-inv-item input{flex:1;border:1.5px solid rgba(189,195,199,0.9);border-radius:var(--radius-md);background:#fff;color:var(--text-main);font-family:inherit;font-size:0.9rem;font-weight:600;padding:10px 14px;transition:all var(--transition-smooth);box-sizing:border-box}
        .co-inv-item input:focus{outline:none;border-color:var(--accent-base);box-shadow:0 0 0 3px rgba(26,188,156,0.12)}
        .co-inv-remove{width:34px;height:34px;border-radius:50%;border:1.5px solid #e74c3c;background:white;color:#e74c3c;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--transition-smooth);flex-shrink:0;font-size:0.85rem}
        .co-inv-remove:hover{background:#e74c3c;color:white;transform:scale(1.1)}
        .co-inv-add{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:var(--radius-md);border:1.5px dashed var(--accent-base);background:rgba(26,188,156,0.04);color:var(--accent-base);font-family:inherit;font-weight:700;font-size:0.88rem;cursor:pointer;transition:all var(--transition-smooth);margin-top:6px;align-self:flex-start}
        .co-inv-add:hover{background:var(--accent-base);color:white;border-style:solid;transform:translateY(-2px)}
        .co-inv-empty{text-align:center;padding:16px;color:var(--text-muted);font-size:0.88rem;font-weight:600;background:var(--bg-page);border-radius:var(--radius-md);border:1.5px dashed var(--border-light)}

        .feedback-compact{padding:12px 16px;background:#fef9e7;border:1px solid #f9e79f;border-right:4px solid #f39c12;border-radius:var(--radius-md);margin-bottom:10px;font-size:0.9rem;color:var(--text-main);line-height:1.5}
        .feedback-compact .reviewer{font-weight:800;color:var(--primary-base);font-size:0.85rem;margin-bottom:4px;display:flex;align-items:center;gap:5px}

        .upload-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px}
        .upload-card{background:linear-gradient(135deg,rgba(44,62,80,0.02) 0%,#fff 100%);border:1.5px solid var(--border-light);border-radius:var(--radius-md);padding:18px;transition:all var(--transition-smooth)}
        .upload-card:hover{border-color:var(--accent-base)}
        .upload-card-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
        .upload-card-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .upload-card-icon i{font-size:1rem;color:white}
        .upload-card-name{font-weight:700;color:var(--text-main);font-size:0.9rem}
        .upload-card-status{font-size:0.78rem;font-weight:600;margin-top:2px}
        .upload-card-status.has-file{color:#27ae60}
        .upload-card-status.no-file{color:var(--text-muted)}
        .upload-card-status.replace-note{color:#e67e22;font-weight:700}
        .file-input-wrap input[type="file"]{width:100%;border:1.5px dashed var(--border-light);border-radius:var(--radius-md);padding:12px;font-family:inherit;font-size:0.85rem;color:var(--text-main);background:#fff;cursor:pointer;transition:all var(--transition-smooth)}
        .file-input-wrap input[type="file"]:hover{border-color:var(--accent-base);background:var(--primary-light)}

        .action-area{display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap}
        .btn-primary{background:var(--accent-base);color:white;border:none;padding:14px 28px;border-radius:var(--radius-md);cursor:pointer;font-family:inherit;font-weight:800;font-size:1rem;transition:all var(--transition-smooth);box-shadow:var(--shadow-md);display:inline-flex;align-items:center;gap:8px}
        .btn-primary:hover{background:var(--accent-dark);transform:translateY(-2px)}
        .btn-secondary{background:var(--primary-light);color:var(--primary-base);border:2px solid var(--primary-base);padding:14px 28px;border-radius:var(--radius-md);font-family:inherit;font-weight:800;font-size:1rem;transition:all var(--transition-smooth);display:inline-flex;align-items:center;gap:8px;text-decoration:none}
        .btn-secondary:hover{background:var(--primary-base);color:white;transform:translateY(-2px)}
        .info-hint{font-size:0.8rem;color:var(--text-muted);margin-top:14px;padding:10px 14px;background:var(--primary-light);border-right:3px solid var(--accent-base);border-radius:4px;font-weight:500;line-height:1.5}
        .alert-error{background:linear-gradient(135deg,#fdedec 0%,#fef5f4 100%);color:#922b21;padding:14px 20px;border-radius:var(--radius-md);border:1px solid #f5b7b1;display:flex;align-items:center;gap:10px;font-weight:700;margin-bottom:20px}

        @media(max-width:992px){.content{margin-right:0;padding:24px 14px}.upload-grid,.form-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="content">
        <h2 class="page-title"><i class="fa-solid fa-pen-to-square"></i> تحديث البحث</h2>
        <p class="page-subtitle">تعديل بيانات البحث ورفع نسخ محدّثة من المستندات</p>

        <?php if ($error_msg): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars(urldecode($error_msg)) ?></div>
        <?php endif; ?>

        <div class="app-brief">
            <span class="badge-serial"><?= htmlspecialchars($app['serial_number']) ?></span>
            <span class="app-brief-title"><?= htmlspecialchars($app['title']) ?></span>
        </div>

        <?php if (!empty($feedback)): ?>
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-comments"></i><h3>ملاحظات المراجعين</h3></div>
                <?php foreach ($feedback as $fb):
                    if (empty($fb['comment'])) continue;
                ?>
                    <div class="feedback-compact">
                        <div class="reviewer"><i class="fa-solid fa-user-secret"></i> <?= htmlspecialchars($fb['reviewer_label']) ?></div>
                        <?= nl2br(htmlspecialchars($fb['comment'])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="updateForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_app" value="1">

            <!-- Application Details -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-clipboard-list"></i><h3>بيانات البحث</h3></div>
                <div class="form-grid">
                    <div class="form-group wide">
                        <label class="form-label" for="title"><i class="fa-solid fa-book"></i> عنوان البحث</label>
                        <input type="text" id="title" name="title" class="form-input" value="<?= htmlspecialchars($app['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pi"><i class="fa-solid fa-user-doctor"></i> الباحث الرئيسي</label>
                        <input type="text" id="pi" name="principal_investigator" class="form-input" value="<?= htmlspecialchars($app['principal_investigator']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-hashtag"></i> رقم الملف</label>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($app['serial_number']) ?>" disabled style="opacity:0.55;cursor:not-allowed">
                        <span class="form-hint">لا يمكن تعديل رقم الملف</span>
                    </div>
                </div>
            </div>

            <!-- Co-Investigators Dynamic List -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-users"></i><h3>الباحثون المشاركون</h3></div>
                <div class="co-inv-list" id="coInvList">
                    <?php if (empty($coInvestigators)): ?>
                        <div class="co-inv-empty" id="coInvEmpty"><i class="fa-solid fa-user-plus" style="margin-left:6px"></i> لم يتم إضافة باحثين مشاركين بعد</div>
                    <?php else: ?>
                        <?php foreach ($coInvestigators as $idx => $ci): ?>
                            <div class="co-inv-item">
                                <span class="co-inv-num"><?= $idx + 1 ?></span>
                                <input type="text" name="co_investigators[]" value="<?= htmlspecialchars($ci) ?>" placeholder="اسم الباحث المشارك">
                                <button type="button" class="co-inv-remove" title="حذف"><i class="fa-solid fa-trash-can"></i></button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="co-inv-add" id="addCoInvBtn"><i class="fa-solid fa-circle-plus"></i> إضافة باحث مشارك</button>
            </div>

            <!-- Documents -->
            <div class="card">
                <div class="card-header"><i class="fa-solid fa-cloud-arrow-up"></i><h3>تحديث المستندات</h3></div>
                <div class="upload-grid">
                    <?php foreach ($docTypes as $type => $label):
                        $hasExisting = isset($existingTypes[$type]);
                    ?>
                        <div class="upload-card">
                            <div class="upload-card-header">
                                <div class="upload-card-icon" style="background:<?= $label[2] ?>"><i class="fa-solid <?= $label[1] ?>"></i></div>
                                <div>
                                    <div class="upload-card-name"><?= htmlspecialchars($label[0]) ?></div>
                                    <div class="upload-card-status <?= $hasExisting ? 'has-file' : 'no-file' ?>">
                                        <?php if ($hasExisting): ?>
                                            <i class="fa-solid fa-check-circle"></i> يوجد ملف سابق
                                        <?php else: ?>
                                            <i class="fa-solid fa-minus-circle"></i> لا يوجد ملف
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($hasExisting): ?>
                                        <div class="upload-card-status replace-note"><i class="fa-solid fa-rotate"></i> سيتم استبدال الملف الحالي عند رفع ملف جديد</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="file-input-wrap">
                                <input type="file" name="doc_<?= $type ?>" accept=".pdf,.doc,.docx,.jpg,.png">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="info-hint"><i class="fa-solid fa-info-circle"></i> اختر الملفات التي تريد تحديثها فقط. عند رفع ملف جديد لنوع موجود، سيتم استبدال الملف السابق تلقائياً.</div>
            </div>

            <!-- Actions -->
            <div class="card">
                <div class="action-area">
                    <a href="student_research_details.php?id=<?= $app_id ?>" class="btn-secondary"><i class="fa-solid fa-arrow-right"></i> تراجع</a>
                    <button type="button" id="submitBtn" class="btn-primary"><i class="fa-solid fa-paper-plane"></i> حفظ التحديثات</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    // ==================== Co-Investigators Dynamic List ====================
    const coInvList = document.getElementById('coInvList');
    const addBtn = document.getElementById('addCoInvBtn');

    function renumberItems() {
        const items = coInvList.querySelectorAll('.co-inv-item');
        const emptyMsg = document.getElementById('coInvEmpty');
        items.forEach((item, i) => {
            item.querySelector('.co-inv-num').textContent = i + 1;
        });
        if (emptyMsg) {
            emptyMsg.style.display = items.length === 0 ? '' : 'none';
        }
    }

    function createCoInvItem(value = '') {
        const count = coInvList.querySelectorAll('.co-inv-item').length + 1;
        const div = document.createElement('div');
        div.className = 'co-inv-item';
        div.innerHTML = `
            <span class="co-inv-num">${count}</span>
            <input type="text" name="co_investigators[]" value="${value}" placeholder="اسم الباحث المشارك">
            <button type="button" class="co-inv-remove" title="حذف"><i class="fa-solid fa-trash-can"></i></button>
        `;
        div.querySelector('.co-inv-remove').addEventListener('click', function() {
            div.style.animation = 'fadeInItem 0.2s ease reverse';
            setTimeout(() => { div.remove(); renumberItems(); }, 200);
        });
        return div;
    }

    addBtn.addEventListener('click', function() {
        // Hide empty message
        const emptyMsg = document.getElementById('coInvEmpty');
        if (emptyMsg) emptyMsg.style.display = 'none';
        const item = createCoInvItem('');
        coInvList.appendChild(item);
        item.querySelector('input').focus();
    });

    // Attach remove handlers to existing items
    coInvList.querySelectorAll('.co-inv-remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.co-inv-item');
            item.style.animation = 'fadeInItem 0.2s ease reverse';
            setTimeout(() => { item.remove(); renumberItems(); }, 200);
        });
    });

    // ==================== Form Submission ====================
    document.getElementById('submitBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.getElementById('updateForm');
        const title = form.querySelector('#title').value.trim();
        const pi = form.querySelector('#pi').value.trim();

        if (!title || !pi) {
            Swal.fire({ icon:'warning', title:'بيانات مطلوبة', text:'يرجى تعبئة عنوان البحث واسم الباحث الرئيسي', confirmButtonText:'حسناً', confirmButtonColor:'#2c3e50', customClass:{popup:'swal-rtl'} });
            return;
        }

        Swal.fire({
            title:'تأكيد حفظ التحديثات',
            html:'<p style="font-weight:600">هل أنت متأكد من حفظ جميع التعديلات؟</p>',
            icon:'question', showCancelButton:true,
            confirmButtonText:'<i class="fa-solid fa-check"></i> نعم، احفظ',
            cancelButtonText:'<i class="fa-solid fa-xmark"></i> إلغاء',
            confirmButtonColor:'#1abc9c', cancelButtonColor:'#95a5a6',
            reverseButtons:true, customClass:{popup:'swal-rtl'}
        }).then(r => { if (r.isConfirmed) form.submit(); });
    });
    </script>
    <style>.swal-rtl{direction:rtl;font-family:'Cairo',sans-serif}</style>
</body>
</html>
