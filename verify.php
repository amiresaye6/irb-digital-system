<?php
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/classes/Database.php';

$db = new Database();
$conn = $db->getconn();

$certificate = null;
$error = null;
$searchQuery = $_GET['code'] ?? '';

if (!empty($searchQuery)) {
    $cleanSearch = trim($searchQuery);

    $sql = "SELECT c.*, 
                   u.full_name as student_name, 
                   a.title as research_title, 
                   a.serial_number
            FROM certificates c
            LEFT JOIN users u ON c.student_id = u.id
            LEFT JOIN applications a ON c.application_id = a.id
            WHERE c.certificate_number = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cleanSearch);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $certificate = $result->fetch_assoc();
    } else {
        $error = "الكود ($cleanSearch) غير موجود في قاعدة البيانات.";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الشهادات | IRB Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #1abc9c;
            --bg: #f4f7f6;
        }

        * {
            font-family: 'Cairo', sans-serif !important;
            box-sizing: border-box;
        }

        body {
            background: var(--bg);
            background-image: radial-gradient(circle at 20% 30%, rgba(26, 188, 156, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(44, 62, 80, 0.05) 0%, transparent 40%);
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* --- SVG FIXES --- */
        .svg-icon {
            width: 1.2em;
            height: 1.2em;
            vertical-align: middle;
            display: inline-block;
            margin-left: 8px;
            /* Margin for RTL spacing */
        }

        .header-icon .svg-icon {
            width: 45px;
            height: 45px;
            margin-left: 0;
        }

        .badge-success .svg-icon {
            width: 1.4em;
            height: 1.4em;
        }

        /* ----------------- */

        .container {
            width: 100%;
            max-width: 800px;
            perspective: 1000px;
        }

        .main-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 50px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: var(--accent);
            color: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            box-shadow: 0 10px 20px rgba(26, 188, 156, 0.3);
        }

        h1 {
            color: var(--primary);
            font-weight: 900;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        p.subtitle {
            color: #7f8c8d;
            font-weight: 600;
            margin-bottom: 40px;
        }

        .search-group {
            display: flex;
            gap: 10px;
            background: white;
            padding: 8px;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.02);
            border: 2px solid #edf2f7;
            transition: 0.3s;
        }

        .search-group:focus-within {
            border-color: var(--accent);
            box-shadow: 0 10px 25px rgba(26, 188, 156, 0.1);
        }

        .search-group input {
            flex: 1;
            border: none;
            padding: 15px 20px;
            font-size: 1.1rem;
            font-weight: 700;
            outline: none;
            background: transparent;
        }

        .btn-verify {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-verify:hover {
            background: var(--accent);
            transform: scale(1.02);
        }

        .cert-result {
            margin-top: 50px;
            text-align: right;
            border-top: 2px dashed #eee;
            padding-top: 40px;
            animation: fadeIn 0.5s ease;
        }

        .cert-visual-card {
            background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .cert-visual-card::after {
            content: "\f0a3";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            position: absolute;
            top: -20px;
            left: -20px;
            font-size: 12rem;
            color: rgba(26, 188, 156, 0.05);
            pointer-events: none;
        }

        .data-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .data-item label {
            display: block;
            color: #94a3b8;
            font-size: 0.85rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .data-item div {
            color: var(--primary);
            font-weight: 800;
            font-size: 1.1rem;
        }

        .badge-success {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ecfdf5;
            color: #059669;
            padding: 8px 20px;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .error-box {
            margin-top: 30px;
            background: #fff5f5;
            color: #c53030;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #feb2b2;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 600px) {
            .search-group {
                flex-direction: column;
            }

            .data-row {
                grid-template-columns: 1fr;
            }

            .main-card {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="main-card">
            <div class="header-icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="svg-icon">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
            </div>
            <h1>مركز التحقق الرقمي</h1>
            <p class="subtitle">قم بإدخال كود الشهادة للتحقق من سلامة وصحة المستند الصادر.</p>

            <form action="" method="GET">
                <div class="search-group">
                    <input type="text" name="code" placeholder="رقم الشهادة (CERT-2026-XXXX)"
                        value="<?= htmlspecialchars($searchQuery) ?>" required>
                    <button type="submit" class="btn-verify">تحقق الآن</button>
                </div>
            </form>

            <?php if ($certificate): ?>
                <div class="cert-result">
                    <div style="text-align: center;">
                        <div class="badge-success">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="svg-icon">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                            </svg>
                            شهادة رسمية معتمدة
                        </div>
                    </div>

                    <div class="cert-visual-card">
                        <div class="data-row">
                            <div class="data-item">
                                <label>اسم صاحب الشهادة</label>
                                <div><?= htmlspecialchars($certificate['student_name']) ?></div>
                            </div>
                            <div class="data-item">
                                <label>تاريخ الإصدار</label>
                                <div><?= date('d M, Y', strtotime($certificate['issued_at'])) ?></div>
                            </div>
                        </div>

                        <div class="data-item" style="margin-bottom: 20px;">
                            <label>موضوع البحث / المشروع</label>
                            <div style="line-height: 1.5;"><?= htmlspecialchars($certificate['research_title']) ?></div>
                        </div>

                        <div class="data-row">
                            <div class="data-item">
                                <label>الجهة المصدرة</label>
                                <div>كلية الطب - لجنة IRB</div>
                            </div>
                            <div class="data-item">
                                <label>الرقم المرجعي</label>
                                <div style="color: var(--accent);">
                                    <?= htmlspecialchars($certificate['certificate_number']) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 30px; text-align: center;">
                        <a href="index.php"
                            style="color: var(--primary); text-decoration: none; font-weight: 800; font-size: 0.9rem; display: inline-flex; align-items: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="svg-icon">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                            </svg>
                            العودة للرئيسية
                        </a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="error-box">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                        stroke="currentColor" class="svg-icon">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <?= $error ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>