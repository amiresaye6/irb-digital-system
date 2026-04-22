<?php
session_start();

$errors= $_SESSION['errors'] ?? [];
$old_data = $_SESSION['old_data'] ?? [];

unset($_SESSION['errors']);
unset($_SESSION['old_data']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
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
            padding: 40px 0; 
        }

        .register-card {
            background: var(--bg-surface);
            padding: 2.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 600px; 
            margin: auto;
            border: 1px solid var(--border-light);
        }

        .register-card h2 {
            font-weight: 700;
            color: var(--primary-deep);
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-light);
            padding: 0.6rem 0.75rem;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--primary-base);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1);
        }

        
        input[type="file"] {
            background-color: var(--bg-page);
            cursor: pointer;
        }

        .btn-submit {
            background-color: var(--primary-base);
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .error-box {
            background-color: var(--status-rejected-bg);
            color: var(--status-rejected-text);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .error-box p { margin: 0; }

        .login-link {
            color: var(--primary-base);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center">
    <div class="register-card">
        <h2>تسجيل حساب جديد</h2>

        <?php if(!empty($errors)): ?>
            <div class="error-box">
                <?php foreach($errors as $error): ?>
                    <p><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="save.php" method="POST" enctype="multipart/form-data">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">الاسم بالكامل</label> 
                    <input type="text" class="form-control" name="full_name" value="<?php echo $_POST['full_name'] ?? ''; ?>" placeholder="أدخل اسمك الثلاثي" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">البريد الإلكتروني</label> 
                    <input type="email" class="form-control" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" placeholder="name@example.com" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">كلمة المرور</label> 
                    <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">رقم البطاقة (الرقم القومي)</label> 
                    <input type="text" class="form-control" name="national_id" value="<?php echo $_POST['national_id'] ?? ''; ?>" maxlength="14" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">رقم الهاتف</label> 
                <input type="text" class="form-control" name="phone_number" value="<?php echo $_POST['phone_number'] ?? ''; ?>" placeholder="01xxxxxxxxx">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">الكلية</label> 
                    <input type="text" class="form-control" name="faculty" value="<?php echo $_POST['faculty'] ?? ''; ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">القسم</label> 
                    <input type="text" class="form-control" name="department" value="<?php echo $_POST['department'] ?? ''; ?>">
                </div>
            </div>

            <div class="row border-top pt-3 mt-2">
                <div class="col-md-6 mb-3">
                    <label class="form-label">صورة البطاقة (وجه)</label> 
                    <input type="file" class="form-control" name="id_front" accept=".jpg,.jpeg,.png" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">صورة البطاقة (ظهر)</label> 
                    <input type="file" class="form-control" name="id_back" accept=".jpg,.jpeg,.png" required>
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100">إنشاء الحساب</button>

        </form>

        <p class="text-center mt-4 mb-0" style="color: var(--text-muted); font-size: 0.9rem;">
            لديك حساب بالفعل؟ <a href="login.php" class="login-link">تسجيل دخول</a>
        </p>
    </div>
</div>

</body>
</html>