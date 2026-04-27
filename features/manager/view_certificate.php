<?php
require_once '../../classes/Database.php';
session_start();

require_once __DIR__ . "/../../classes/Auth.php";

Auth::checkRole(['manager','student']);

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
    if (!$cert) { die("الشهادة غير موجودة أو لم يتم اعتمادها بعد."); }
}


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة اعتماد IRB - <?= $cert['serial_number'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;  
        }
        
        body { 
            background-color: #f0f0f0; 
            font-family: 'Cairo', sans-serif; 
            margin: 0;
            padding: 0;
        }

        .certificate-container {
            width: 210mm;
            height: 297mm; 
            padding: 15mm;
            margin: 0 auto;
            background: white;
            box-sizing: border-box;
            border: 12px double #16a085;
            position: relative;
            overflow: hidden; 
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: middle;
            text-align: center;
        }
        .logo-img {
            max-width: 80px;
            height: auto;
        }

        .cert-title {
            color: #2c3e50;
            font-size: 26px;
            margin: 15px 0;
            font-weight: bold;
            text-align: center;
        }

        .content {
            text-align: right;
            line-height: 1.6;
            font-size: 17px;
            color: #333;
            margin-top: 20px;
        }

        .data-row { margin: 12px 0; }
        .label { 
            font-weight: bold; 
            color: #16a085; 
            min-width: 140px; 
            display: inline-block; 
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            text-align: center;
        }

        .sig-box {
            width: 200px;
        }
        .sig-space {
            height: 80px;
            margin-bottom: 10px;
            border-bottom: 1px dashed #ccc; 
        }

        .stamp-box {
            width: 150px;
            height: 150px;
            border: 2px dashed #16a085;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a085;
            font-size: 12px;
            opacity: 0.5;
            margin: 0 auto;
        }

        .no-print { text-align: center; margin: 20px; }
        .btn-download { 
            background: #27ae60; color: white; padding: 12px 25px; 
            border: none; border-radius: 5px; cursor: pointer; font-size: 16px; 
        }

        @media print {
            body { background: none; }
            .no-print, .sidebar { display: none !important; }
            .certificate-container { 
                margin: 0; 
                box-shadow: none;
                width: 210mm;
                height: 297mm;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-download" onclick="window.print()">
        <i class="fa-solid fa-print"></i> طباعة الشهادة (PDF)
    </button>
</div>

<div class="certificate-container" id="certificate">
    <table class="header-table">
        <tr>
            <td style="width: 20%;">
                <img src="../../assets/images/university-logo.png" alt="جامعة الزقازيق" class="logo-img">
            </td>
            <td style="width: 60%;">
                <h2 style="margin: 0; color: #2c3e50;">جامعة الزقازيق - كلية الطب</h2>
                <h3 style="margin: 5px 0; color: #16a085;">لجنة أخلاقيات البحث العلمي (IRB)</h3>
            </td>
            <td style="width: 20%;">
                <img src="../../assets/images/faculty-logo2.png" alt="كلية الطب" class="logo-img">
            </td>
        </tr>
    </table>

    <div class="cert-title">شهادة اعتماد أخلاقيات البحث العلمي</div>

    <div class="content">
        <p>تشهد لجنة أخلاقيات البحث العلمي بأن البحث المقدم من السيد الدكتور/ <strong><?= $cert['full_name'] ?></strong></p>
        <p>المقيد بكلية: <strong><?= $cert['faculty'] ?></strong> - قسم: <strong><?= $cert['department'] ?></strong></p>
        
        <div class="data-row">
            <span class="label">عنوان البحث:</span>
            <span><?= $cert['title'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم التسجيل:</span>
            <span style="font-family: monospace; font-weight: bold;"><?= $cert['serial_number'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم الشهادة:</span>
            <span><?= $cert['certificate_number'] ?></span>
        </div>

        <div class="data-row">
            <span class="label">تاريخ الاعتماد:</span>
            <span><?= date('Y-m-d', strtotime($cert['issued_at'])) ?></span>
        </div>

        <p style="margin-top: 25px; text-indent: 30px;">
            وقد تمت مراجعة بروتوكول الدراسة والموافقة عليه من قبل اللجنة، مع التأكيد على ضرورة الالتزام بالمعايير الأخلاقية العالمية والمتبعة في الجامعة طوال فترة تنفيذ البحث.
        </p>
    </div>

    <div class="signature-section">
        
        <div class="sig-box">
            <p style="margin-bottom: 5px; font-weight: bold;">مدير وحدة IRB</p>
            <div class="sig-space">
                <div style="margin-top: 40px; border-top: 1px dashed #555; width: 80%; margin-left: auto; margin-right: auto;"></div>
                <small style="color: #999; font-size: 10px;">(توقيع السيد الأستاذ الدكتور مدير الوحدة)</small>
            </div>
            <p><strong>أ.د. طارق الحديدي</strong></p>
        </div>

        <div class="sig-box">
            <div class="stamp-box">
                <i class="fa-solid fa-stamp" style="display: block; margin-bottom: 5px; font-size: 24px;"></i>
                محل ختم الاعتماد
            </div>
            <p style="margin-top: 10px; font-weight: bold; color: #16a085;">ختم اللجنة الرسمي</p>
        </div>

        <div class="sig-box">
            <p style="margin-bottom: 5px; font-weight: bold;">عميد الكلية</p>
            <div class="sig-space">
                <div style="margin-top: 40px; border-top: 1px dashed #555; width: 80%; margin-left: auto; margin-right: auto;"></div>
                <small style="color: #999; font-size: 10px;">(توقيع السيد الأستاذ الدكتور العميد)</small>
            </div>
            <p><strong>أ.د. محمود مصطفي طه</strong></p>
        </div>
        
    </div>

    <div style="position: absolute; bottom: 15mm; width: 85%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px;">
        هذه الشهادة صدرت إلكترونياً من نظام IRB الرقمي بجامعة الزقازيق
    </div>
</div>

</body>
</html>