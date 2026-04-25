<?php
require_once '../../classes/Database.php';
session_start();

if (isset($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $db = new Database();
    $conn = $db->getconn();

    $sql = "SELECT c.*, a.title, a.serial_number, u.full_name, u.faculty, u.department 
            FROM certificates c
            JOIN applications a ON c.application_id = a.id
            JOIN users u ON a.student_id = u.id
            WHERE c.application_id = $app_id";

    $result = $conn->query($sql);
    $cert = $result->fetch_assoc();
}

if (!$cert) { die("الشهادة غير موجودة أو لم يتم اعتمادها بعد."); }
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة اعتماد IRB - <?= $cert['serial_number'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f0f0f0; font-family: 'Arial', sans-serif; }

        .certificate-container {
            width: 210mm; min-height: 297mm;
            padding: 20mm; margin: 10mm auto;
            background: white; position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border: 15px double #16a085; 
        }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #eee; padding-bottom: 20px; }
        .logo { width: 80px; margin-bottom: 10px; }
        .cert-title { color: #2c3e50; font-size: 28px; margin: 20px 0; font-weight: bold; }
        .content { text-align: right; line-height: 1.8; font-size: 18px; color: #333; }
        .data-row { margin: 15px 0; }
        .label { font-weight: bold; color: #16a085; min-width: 150px; display: inline-block; }
        .footer-sig { margin-top: 50px; display: flex; justify-content: space-between; text-align: center; }
        .stamp { width: 120px; opacity: 0.6; }
        
        .no-print { text-align: center; margin: 20px; }
        .btn-download { 
            background: #27ae60; color: white; padding: 12px 25px; 
            border: none; border-radius: 5px; cursor: pointer; font-size: 16px; 
        }
        @media print { .no-print { display: none; } .certificate-container { margin: 0; box-shadow: none; border: 10px double #16a085; } }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-download" onclick="window.print()">
        <i class="fa-solid fa-file-pdf"></i> تحميل الشهادة (PDF / طباعة)
    </button>
</div>

<div class="certificate-container" id="certificate">
    <div class="header">
        <i class="fa-solid fa-university fa-3x" style="color: #16a085;"></i>
        <h2>جامعة الزقازيق - كلية الطب</h2>
        <h3>لجنة أخلاقيات البحث العلمي (IRB)</h3>
    </div>

    <div class="cert-title" style="text-align: center;">شهادة اعتماد أخلاقيات البحث العلمي</div>

    <div class="content">
        <p>تشهد لجنة أخلاقيات البحث العلمي بأن البحث المقدم من السيد الدكتور/ <strong><?= $cert['full_name'] ?></strong></p>
        <p>المقيد بكلية: <strong><?= $cert['faculty'] ?></strong> - قسم: <strong><?= $cert['department'] ?></strong></p>
        
        <div class="data-row">
            <span class="label">عنوان البحث:</span>
            <span><?= $cert['title'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم التسجيل (Serial):</span>
            <span style="font-family: monospace; letter-spacing: 1px;"><?= $cert['serial_number'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم الشهادة:</span>
            <span><?= $cert['certificate_number'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">تاريخ الاعتماد:</span>
            <span><?= date('Y-m-d', strtotime($cert['issued_at'])) ?></span>
        </div>

        <p style="margin-top: 30px;">وقد تمت مراجعة بروتوكول الدراسة والموافقة عليه من قبل اللجنة، مع الالتزام بالمعايير الأخلاقية المتبعة.</p>
    </div>

    <div class="footer-sig">
        <div>
            <p>يعتمد،،</p>
            <p><strong>مدير لجنة IRB</strong></p>
            <br>
            <p>أ.د. طارق الحديدي</p>
        </div>
        <div>
            <i class="fa-solid fa-stamp fa-5x" style="color: rgba(22, 160, 133, 0.2);"></i>
            <p style="font-size: 10px; color: #ccc;">ختم اللجنة الإلكتروني</p>
        </div>
    </div>
</div>

</body>
</html>