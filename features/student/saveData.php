<?php
require_once __DIR__ . '/../../init.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Validation: Basic Fields
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

    //  Validation Files 
    $allowed_types = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $max_size = 10 * 1024 * 1024; // 10MB

    // research + 7 documents = 8 files total
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

    $sql  = "INSERT INTO applications (title, principal_investigator, co_investigators) VALUES (?, ?, ?)";
    $stmt = $database->conn->prepare($sql);
    $stmt->bind_param("sss", $title, $principal_investigator, $co_investigators_json);
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
    $research_dir = __DIR__ . "/../../uploads/researches/";
    $documents_dir = __DIR__ . "/../../uploads/documents/";

    if (!is_dir($research_dir)) mkdir($research_dir, 0755, true);
    if (!is_dir($documents_dir)) mkdir($documents_dir, 0755, true);

    $docSql  = "INSERT INTO documents (application_id, document_type, file_path) VALUES (?, ?, ?)";
    $docStmt = $database->conn->prepare($docSql);

    foreach ($file_fields as $field_name => $field_label) {
        $file_name   = basename($_FILES[$field_name]["name"]);
        if ($field_name === 'research') {
            $destination = $research_dir . $last_id . "_" . $field_name . "_" . $file_name;
        } else {
            $destination = $documents_dir . $last_id . "_" . $field_name . "_" . $file_name;
        }
        move_uploaded_file($_FILES[$field_name]["tmp_name"], $destination);
        $docStmt->bind_param("iss", $last_id, $field_name, $destination);
        $docStmt->execute();
    }

    $docStmt->close();
        $_SESSION['form_errors'] = [];
        $_SESSION['form_data']   = [];

        echo "<h1>تم تقديم الطلب بنجاح — رقم الطلب: $serial_number</h1>";
    }
?>