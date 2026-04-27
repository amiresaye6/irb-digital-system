<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Determine where the "Home" button should take them
$dashboard_url = '/irb-digital-system/index.php'; // Default fallback to landing page

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch($_SESSION['role']) {
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
            $dashboard_url = '/irb-digital-system/features/reviewer/assigned_reserches.php';
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
    <title>الصفحة غير موجودة - IRB Digital System</title>
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
            background-image: radial-gradient(var(--border-light) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .error-card { 
            background: var(--bg-surface); 
            padding: 50px 40px; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-lg); 
            text-align: center; 
            max-width: 500px; 
            width: 90%; 
            border-top: 5px solid var(--primary-base);
            position: relative;
            overflow: hidden;
        }
        .error-card::before {
            content: '404';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 10rem;
            font-weight: 800;
            color: var(--primary-light);
            opacity: 0.3;
            z-index: 0;
        }
        .error-content {
            position: relative;
            z-index: 1;
        }
        .error-icon { 
            font-size: 4.5rem; 
            color: var(--accent-base); 
            margin-bottom: 20px; 
            animation: float 3s ease-in-out infinite;
        }
        .error-title { 
            color: var(--primary-base); 
            font-weight: 800; 
            font-size: 2rem; 
            margin-bottom: 15px; 
            margin-top: 0;
        }
        .error-desc { 
            color: var(--text-muted); 
            font-weight: 600; 
            font-size: 1.05rem; 
            margin-bottom: 35px; 
            line-height: 1.7;
        }
        .btn-home { 
            background: var(--primary-base); 
            color: white; 
            text-decoration: none; 
            padding: 14px 28px; 
            border-radius: var(--radius-md); 
            font-weight: 800; 
            font-size: 1.05rem;
            display: inline-flex; 
            align-items: center; 
            gap: 10px; 
            transition: var(--transition-smooth); 
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
        }
        .btn-home:hover { 
            background: var(--primary-dark); 
            transform: translateY(-3px); 
            box-shadow: 0 8px 25px rgba(44, 62, 80, 0.3); 
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-content">
            <i class="fa-solid fa-map-location-dot error-icon"></i>
            <h1 class="error-title">الصفحة غير موجودة!</h1>
            <p class="error-desc">عذراً، يبدو أنك ضللت الطريق. الصفحة التي تبحث عنها قد تم نقلها، أو حذفها، أو ربما لم تكن موجودة من الأساس.</p>
            
            <a href="<?= htmlspecialchars($dashboard_url) ?>" class="btn-home">
                <i class="fa-solid <?= isset($_SESSION['user_id']) ? 'fa-gauge' : 'fa-house' ?>"></i> 
                <?= isset($_SESSION['user_id']) ? 'العودة للوحة التحكم' : 'العودة للرئيسية' ?>
            </a>
        </div>
    </div>
</body>
</html>