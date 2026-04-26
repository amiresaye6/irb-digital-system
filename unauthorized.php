<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dashboard_url = '/irb-digital-system/features/auth/login.php'; // Default fallback

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student':
            $dashboard_url = '/irb-digital-system/features/student/dashboard.php';
            break;
        case 'admin':
            $dashboard_url = '/irb-digital-system/features/admin/dashboard.php';
            break;
        case 'sample_officer':
            $dashboard_url = '/irb-digital-system/features/sample_officer/dashboard.php';
            break;
        case 'reviewer':
            $dashboard_url = '/irb-digital-system/features/reviewer/dashboard.php';
            break;
        case 'manager':
            $dashboard_url = '/irb-digital-system/features/manager/dashboard.php';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>غير مصرح - IRB Digital System</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/irb-digital-system/assets/css/global.css">
    <style>
        body {
            background: var(--bg-page);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: 'Cairo', sans-serif;
        }

        .error-card {
            background: var(--bg-surface);
            padding: 40px 30px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            text-align: center;
            max-width: 450px;
            width: 90%;
            border-top: 5px solid var(--alert-base);
        }

        .error-icon {
            font-size: 5rem;
            color: var(--alert-base);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }

        .error-title {
            color: var(--primary-base);
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .error-desc {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-home {
            background: var(--primary-base);
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition-smooth);
        }

        .btn-home:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="error-card">
        <i class="fa-solid fa-shield-halved error-icon"></i>
        <h1 class="error-title">الوصول مرفوض!</h1>
        <p class="error-desc">عذراً، ليس لديك الصلاحيات الكافية للوصول إلى هذه الصفحة. يرجى العودة إلى لوحة التحكم
            الخاصة بك أو تسجيل الدخول بحساب يمتلك الصلاحيات المطلوبة.</p>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn-home"><i class="fa-solid fa-gauge"></i> العودة
                للوحة التحكم</a>
        <?php else: ?>
            <a href="/irb-digital-system/features/auth/login.php" class="btn-home"><i
                    class="fa-solid fa-right-to-bracket"></i> تسجيل الدخول</a>
        <?php endif; ?>
    </div>
</body>

</html>