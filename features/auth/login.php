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
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #ecf0f1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            direction: rtl;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            border: 0.5px solid #e2e8f0;
            padding: 40px 36px;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
        }

        .card-accent {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1abc9c, #16a085);
        }

        .logo-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .logo-circle {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #e1f5ee;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-circle svg {
            width: 28px;
            height: 28px;
            fill: #0f6e56;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-align: center;
            margin-bottom: 4px;
        }

        .card-sub {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-bottom: 28px;
        }

        .alert {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 12px;
            margin-bottom: 16px;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
        }

        .alert-danger  { background: #fadbd8; color: #991b1b; }
        .alert-warning { background: #fef3c7; color: #92400e; }
        .alert-success { background: #d5f4e6; color: #0f6e56; }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .field label {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            text-align: right;
        }

        .field input {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
            background: #f8fafc;
            color: #1e293b;
            font-family: 'Cairo', sans-serif;
            text-align: right;
            outline: none;
            transition: all 0.2s;
            width: 100%;
        }

        .field input:focus {
            border-color: #1abc9c;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(26, 188, 156, 0.08);
        }

        .field input::placeholder {
            color: #94a3b8;
            font-size: 13px;
        }

        /* .field input.error {
            border-color: #e24b4a;
            box-shadow: 0 0 0 4px rgba(226, 75, 74, 0.08);
        } */

        .btn-next {
            background: #1abc9c;
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Cairo', sans-serif;
            width: 100%;
            margin-top: 8px;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }

        .btn-next:hover {
            background: #16a085;
            transform: translateY(-1px);
        }

        .btn-next:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }

        .divider-line {
            flex: 1;
            height: 0.5px;
            background: #e2e8f0;
        }

        .divider-text {
            font-size: 12px;
            color: #94a3b8;
        }

        .footer-note {
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }

        .footer-note a {
            color: #1abc9c;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-note a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .card { padding: 32px 20px; }
        }
    </style>
</head>
<body>

<div class="card">
    <div class="card-accent"></div>

    <!-- Logo -->
    <div class="logo-wrap">
        <div class="logo-circle">
            <svg viewBox="0 0 24 24">
                <path d="M19.5 8.5h-2v-2a5.5 5.5 0 0 0-11 0v2h-2A1.5 1.5 0 0 0 3 10v9a1.5 1.5 0 0 0 1.5 1.5h15A1.5 1.5 0 0 0 21 19v-9a1.5 1.5 0 0 0-1.5-1.5zm-9 6.5a1.5 1.5 0 1 1 3 0v2h-3v-2zm1-9a3.5 3.5 0 0 1 3.5 3.5v2h-7v-2A3.5 3.5 0 0 1 11.5 6z"/>
            </svg>
        </div>
    </div>

    <p class="card-title">مرحباً بعودتك</p>
    <p class="card-sub">سجّل دخولك للوصول إلى بوابة IRB البحثية</p>

    <?php if($error == 'invalid'): ?>
        <div class="alert alert-danger">
            <span class="alert-dot"></span>
            <span>البريد الإلكتروني أو كلمة المرور غير صحيحة</span>
        </div>
    <?php elseif($error == 'inactive'): ?>
        <div class="alert alert-warning">
            <span class="alert-dot"></span>
            <span>حسابك قيد المراجعة، يرجى انتظار موافقة الإدارة</span>
        </div>
    <?php elseif($error == 'must_login'): ?>
        <div class="alert alert-warning">
            <span class="alert-dot"></span>
            <span>يرجى تسجيل الدخول للوصول لهذه الصفحة</span>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success">
            <span class="alert-dot"></span>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form action="submitlogin.php" method="POST">
        <div class="field">
            <label for="email">البريد الإلكتروني</label>
            <input type="email" id="email" name="email"
                   placeholder="name@university.edu.eg"
                   class="<?php echo $error == 'invalid' ? 'error' : ''; ?>"
                   required>
        </div>

        <div class="field">
            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••"
                   class="<?php echo $error == 'invalid' ? 'error' : ''; ?>"
                   required>
        </div>

        <button type="submit" class="btn-next">
            دخول للمنصة
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15.41 16.59L10.83 12l4.58-4.59L14 6l-6 6 6 6z"/>
            </svg>
        </button>
    </form>

    <div class="divider">
        <div class="divider-line"></div>
        <span class="divider-text">أو</span>
        <div class="divider-line"></div>
    </div>

    <p class="footer-note">
        ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a>
    </p>
</div>

</body>
</html>