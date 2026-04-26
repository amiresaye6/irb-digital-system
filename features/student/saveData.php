<?php
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . "/../../classes/Auth.php";
Auth::checkRole('student'); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Validation
    if (empty($_POST['title'])) {
        array_push($errors, "عنوان البحث فارغ");
    } elseif (strlen($_POST['title']) < 3) {
        array_push($errors, "عنوان البحث لا يمكن أن يكون أقل من 3 حروف");
    } elseif (strlen($_POST['title']) > 80) {
        array_push($errors, "عنوان البحث لا يمكن أن يكون أكثر من 80 حرف");
    }

    if (empty($_POST['principal_investigator'])) {
        array_push($errors, "حقل اسم الباحث الرئيسي فارغ");
    } elseif (strlen($_POST['principal_investigator']) < 3) {
        array_push($errors, "اسم الباحث الرئيسي لا يمكن أن يكون أقل من 3 حروف");
    } elseif (strlen($_POST['principal_investigator']) > 80) {
        array_push($errors, "اسم الباحث الرئيسي لا يمكن أن يكون أكثر من 80 حرف");
    }

    if (empty($_POST['co_investigators'])) {
        array_push($errors, "حقل المشاركين في البحث فارغ");
    }

    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $max_size = 4 * 1024 * 1024; 

    $file_fields = [
        'research'               => 'ملف البحث',
        'protocol'               => 'نموذج البروتوكول',
        'conflict_of_interest'   => 'إقرار تضارب المصالح',
        'irb_checklist'          => 'قائمة مراجعة IRB',
        'pi_consent'             => 'موافقة الباحث الرئيسي',
        'patient_consent'        => 'موافقة المريض',
        'photos_biopsies_consent'=> 'موافقة الصور والخزعات',
        'protocol_review_app'    => 'طلب مراجعة البروتوكول',
    ];

    foreach ($file_fields as $field_name => $field_label) {
        if (empty($_FILES[$field_name]["name"])) {
            array_push($errors, "$field_label: الملف مطلوب");
        } elseif (!in_array($_FILES[$field_name]["type"], $allowed_types)) {
            array_push($errors, "$field_label: يُسمح فقط بملفات PDF أو Word");
        } elseif ($_FILES[$field_name]["size"] > $max_size) {
            array_push($errors, "$field_label: حجم الملف يجب أن يكون أقل من 10 ميجابايت");
        }
    }

    //  Failed Validation 
    if (count($errors) > 0) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data']   = [
            'title'                  => $_POST['title']                  ?? '',
            'principal_investigator' => $_POST['principal_investigator'] ?? '',
            'co_investigators'       => $_POST['co_investigators']       ?? '',
        ];
        header("Location: apply.php");
        exit;
    }

    // Success data 
    $title                  = $_POST['title'];
    $principal_investigator = $_POST['principal_investigator'];
    $co_investigators_array = array_values(array_map('trim', explode(',', $_POST['co_investigators'])));
    $co_investigators_json  = json_encode($co_investigators_array, JSON_UNESCAPED_UNICODE);

    $student_id = $_SESSION['user_id'] ;
    $database = new Database();
    $sql  = "INSERT INTO applications (title, principal_investigator, co_investigators, student_id) VALUES (?, ?, ?, ?)";
    $stmt = $database->conn->prepare($sql);
    $stmt->bind_param("sssi", $title, $principal_investigator, $co_investigators_json, $student_id);
    $stmt->execute();
    $stmt->close();

    $last_id       = $database->conn->insert_id;
    $year          = date("Y");
    $serial_number = "IRB-$year-" . str_pad($last_id, 3, "0", STR_PAD_LEFT);

    $updateSql  = "UPDATE applications SET serial_number = ? WHERE id = ?";
    $updateStmt = $database->conn->prepare($updateSql);
    $updateStmt->bind_param("si", $serial_number, $last_id);
    $updateStmt->execute();
    $updateStmt->close();




    //Success insert files
    $research_dir  = __DIR__ . "/../../uploads/researches/";
    $documents_dir = __DIR__ . "/../../uploads/documents/$last_id/";
    $research_dir_db = "uploads/researches/";
    $documents_dir_db = "uploads/documents/$last_id/";

    if (!is_dir($research_dir)) mkdir($research_dir, 0755, true);
    if (!is_dir($documents_dir)) mkdir($documents_dir, 0755, true);

    $docSql  = "INSERT INTO documents (application_id, document_type, file_path) VALUES (?, ?, ?)";
    $docStmt = $database->conn->prepare($docSql);

    foreach ($file_fields as $field_name => $field_label) {
        $file_name = basename($_FILES[$field_name]["name"]);

        if ($field_name === 'research') {
            $abs_destination = $research_dir    . $last_id . "_" . $field_name . "_" . $file_name;
            $db_path         = $research_dir_db . $last_id . "_" . $field_name . "_" . $file_name;
        } else {
            $abs_destination = $documents_dir    . $last_id . "_" . $field_name . "_" . $file_name;
            $db_path         = $documents_dir_db . $last_id . "_" . $field_name . "_" . $file_name;
        }

        move_uploaded_file($_FILES[$field_name]["tmp_name"], $abs_destination); // الرفع بالمسار الكامل
        $docStmt->bind_param("iss", $last_id, $field_name, $db_path);           // الداتابيز بالمسار القصير
        $docStmt->execute();
    };




    $logs = [
        "application_id" => $last_id,
        "user_id" => $student_id,
        "action" => "تم تقديم البحث بنجاح ورفع المستندات",
        "type" => "submission"
    ];
    $database->insert("logs",$logs);

    $docStmt->close();
        $_SESSION['form_errors'] = [];
        $_SESSION['form_data']   = [];

    
    require_once __DIR__ . '/../../includes/sidebar.php';

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تم تقديم الطلب بنجاح</title>
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css" />
    <style>
        body {
            background-color: var(--bg-page);
            font-family: 'Cairo', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .success-card {
            background: var(--bg-surface);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            text-align: center;
            max-width: 500px;
            width: 90%;
            border-top: 8px solid var(--success-base);
            animation: fadeIn 0.5s ease-out;
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--success-light);
            color: var(--success-base);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }

        h1 {
            color: var(--primary-base);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .serial-badge {
            display: inline-block;
            background: var(--primary-light);
            color: var(--primary-base);
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-weight: 800;
            font-size: 1.2rem;
            border: 1px dashed var(--primary-base);
            margin: 15px 0;
        }

        p {
            color: var(--text-muted);
            line-height: 1.6;
        }

        .btn-back {
            display: inline-block;
            margin-top: 25px;
            padding: 12px 30px;
            background-color: var(--accent-base);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            transition: var(--transition-smooth);
        }

        .btn-back:hover {
            background-color: var(--accent-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="success-card">
    <div class="icon-box">✓</div>
    <h1>تم استلام طلبك بنجاح!</h1>
    <p>لقد تم تسجيل طلب البحث الخاص بك في النظام. يرجى الاحتفاظ برقم المرجع الموضح أدناه للمتابعة:</p>
    
    <div class="serial-badge">
        <?= $serial_number ?>
    </div>

    <p>سيتم مراجعة الملفات المرفقة من قبل لجنة الأخلاقيات (IRB) وإشعارك بالنتيجة قريباً.</p>
    
    <a href="apply.php" class="btn-back">تقديم طلب آخر</a>
</div>

</body>
</html>

<?php
} 
?>