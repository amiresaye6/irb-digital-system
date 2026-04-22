<?php
session_start();
$error= $_GET['error'] ?? '';
$success= $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
    <style>
        :root {
            /* 1. Primary Palette (Midnight Blue) */
            --primary-light: #ecf5f7;
            --primary-base: #2c3e50;
            --primary-dark: #1a252f;
            --primary-deep: #0f172a;

            /* 2. Accent Palette (Teal) */
            --accent-light: #d5f4f1;
            --accent-base: #1abc9c;
            --accent-dark: #16a085;

            /* 3. Status Colors */
            --status-approved-bg: #d5f4e6;
            --status-approved-text: #27ae60;
            --status-pending-bg: #fdebd0;
            --status-pending-text: #d68910;
            --status-rejected-bg: #fadbd8;
            --status-rejected-text: #991b1b;

            /* 4. Neutrals & UI */
            --bg-page: #ecf0f1;
            --bg-surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
            --border-dark: #cbd5e1;

            /* 5. Depth & Radius */
            --radius-md: 16px;
            --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
            --transition-smooth: 0.3s ease;
        }
           
        body {
            font-family: 'Cairo', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-card {
            background: var(--bg-surface);
            padding: 2.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 450px;
            border: 1px solid var(--border-light);
        }

        .login-card h1 {
            font-weight: 700;
            color: var(--primary-deep);
            font-size: 1.75rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-light);
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: var(--primary-base);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        .btn-primary {
            background-color: var(--primary-base);
            border: none;
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            border: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .register-link {
            color: var(--primary-base);
            text-decoration: none;
            font-weight: 600;
        }

        .register-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
    <h1>تسجيل دخول</h1>

    <?php if($error == 'invalid'): ?>
        <div class="alert alert-danger" style="background-color: #fee2e2; color: #991b1b;">
            البريد الإلكتروني أو كلمة المرور غير صحيحة
        </div>
    <?php elseif($error == 'inactive'): ?>
        <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e;">
            حسابك قيد المراجعة، يرجى انتظار موافقة الإدارة.
        </div>
    <?php elseif($error == 'must_login'): ?>
        <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e;">
            يرجى تسجيل الدخول للوصول لهذه الصفحة.
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success" style="background-color: #dcfce7; color: #166534;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="submitlogin.php" method="POST">
        <div class="mb-3">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">كلمة المرور</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">دخول للمنصة</button>
    </form>

    <p class="text-center mt-3 mb-0" style="color: var(--text-muted);">
        ليس لديك حساب؟ <a href="register.php" class="register-link">إنشاء حساب جديد</a>
    </p>
</div>
</body>
</html>