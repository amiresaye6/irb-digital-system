<?php
require_once '../../classes/Database.php';
session_start();

require_once __DIR__ . "/../../classes/Auth.php";

Auth::checkRole(['manager', 'student']);

if (isset($_GET['app_id'])) {
    $app_id = $_GET['app_id'];
    $db = new Database();
    $conn = $db->getconn();

    $sql = "SELECT c.*, a.title, a.serial_number, 
                    u_student.full_name, u_student.faculty, u_student.department,
                    u_manager.full_name AS db_manager_name, 
                    sig.signature_url
            FROM certificates c
            JOIN applications a ON c.application_id = a.id
            JOIN users u_student ON a.student_id = u_student.id
            JOIN users u_manager ON c.manager_id = u_manager.id 
            LEFT JOIN signatures sig ON u_manager.id = sig.userId 
            WHERE c.application_id = $app_id";

    $result = $conn->query($sql);
    $cert = $result->fetch_assoc();

    if (!$cert) { 
        die("الشهادة غير موجودة أو لم يتم اعتمادها بعد."); 
    }
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
            padding: 20mm;
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
            margin-bottom: 30px;
        }
        .header-table td {
            vertical-align: middle;
            text-align: center;
        }
        .logo-img {
            max-width: 90px;
            height: auto;
        }

        .cert-title {
            color: #2c3e50;
            font-size: 28px;
            margin: 20px 0;
            font-weight: bold;
            text-align: center;
        }

        .content {
            text-align: right;
            line-height: 1.8;
            font-size: 18px;
            color: #333;
            margin-top: 30px;
        }

        .data-row { margin: 15px 0; }
        .label { 
            font-weight: bold; 
            color: #16a085; 
            min-width: 150px; 
            display: inline-block; 
        }

        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-around; 
            align-items: center;
            text-align: center;
        }

        .sig-box {
            width: 250px;
        }
        .sig-space {
            height: 100px;
            margin-bottom: 10px;
            position: relative;
        }

        .stamp-box {
            width: 160px;
            height: 160px;
            border: 2px dashed #16a085;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #16a085;
            font-size: 13px;
            opacity: 0.6;
        }

        .no-print { text-align: center; margin: 20px; }
        .btn-download { 
            background: #27ae60; color: white; padding: 12px 25px; 
            border: none; border-radius: 5px; cursor: pointer; font-size: 16px; 
            transition: 0.3s;
        }
        .btn-download:hover { background: #219150; }

        @media print {
            body { background: none; }
            .no-print { display: none !important; }
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
            <td style="width: 25%;">
                <img src="../../assets/images/university-logo.png" alt="جامعة الزقازيق" class="logo-img">
            </td>
            <td style="width: 50%;">
                <h2 style="margin: 0; color: #2c3e50;">جامعة الزقازيق - كلية الطب</h2>
                <h3 style="margin: 5px 0; color: #16a085;">لجنة أخلاقيات البحث العلمي (IRB)</h3>
            </td>
            <td style="width: 25%;">
                <img src="../../assets/images/faculty-logo2.png" alt="كلية الطب" class="logo-img">
            </td>
        </tr>
    </table>

    <div class="cert-title">شهادة اعتماد أخلاقيات البحث العلمي</div>

    <div class="content">
        <p>تشهد لجنة أخلاقيات البحث العلمي بأن البحث المقدم من السيد الدكتور/ <strong><?= htmlspecialchars($cert['full_name']) ?></strong></p>
        <p>المقيد بكلية: <strong><?= htmlspecialchars($cert['faculty']) ?></strong> - قسم: <strong><?= htmlspecialchars($cert['department']) ?></strong></p>
        
        <div class="data-row">
            <span class="label">عنوان البحث:</span>
            <span><?= htmlspecialchars($cert['title']) ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم التسجيل:</span>
            <span style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($cert['serial_number']) ?></span>
        </div>

        <div class="data-row">
            <span class="label">رقم الشهادة:</span>
            <span style=" font-family: monospace; color: #070707;font-weight: bold;"><?= htmlspecialchars($cert['certificate_number']) ?></span>
        </div>

        <div class="data-row">
            <span class="label" >تاريخ الاعتماد:</span>
            <span style="font-family: monospace;font-weight: bold;"><?= date('Y-m-d', strtotime($cert['issued_at'])) ?></span>
        </div>

        <p style="margin-top: 30px; text-indent: 40px; text-align: justify;">
            وقد تمت مراجعة بروتوكول الدراسة والموافقة عليه من قبل اللجنة، مع التأكيد على ضرورة الالتزام بالمعايير الأخلاقية العالمية والمتبعة في الجامعة طوال فترة تنفيذ البحث، ويسري هذا الاعتماد من تاريخ صدوره.
        </p>
    </div>

    <div class="signature-section">

        <div class="sig-box">
            <p style="font-weight: bold; margin-bottom: 5px;">مدير وحدة IRB</p>
            <div class="sig-space">
                <?php if (!empty($cert['signature_url'])): ?>
                    <img src="<?= htmlspecialchars($cert['signature_url']) ?>" 
                        alt="توقيع المدير" 
                        style="max-width: 180px; max-height: 90px; position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%);">
                <?php else: ?>
                    <div style="height: 60px; border-bottom: 1px dashed #ccc; margin-top: 20px;"></div>
                <?php endif; ?>
            </div>
            <p><strong> <?= htmlspecialchars($cert['db_manager_name']) ?></strong></p>
            <small style="color: #7f8c8d; font-size: 11px; margin-bottom: 5px;">(توقيع إلكتروني معتمد)</small>
        </div>
    </div>

    <div style="position: absolute; bottom: 10mm; left: 0; right: 0; text-align: center; font-size: 11px; color: #bdc3c7; border-top: 1px solid #ecf0f1; padding-top: 10px; margin: 0 20mm;">
        هذه الشهادة صدرت إلكترونياً من نظام IRB الرقمي - كلية الطب - جامعة الزقازيق
    </div>
</div>

</body>
</html>