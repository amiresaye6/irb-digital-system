<?php
session_start();
$success = $_SESSION['reset_success'] ?? '';
$error   = $_SESSION['reset_error']   ?? '';
unset($_SESSION['reset_success'], $_SESSION['reset_error']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور | IRB</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            line-height: 1.6;
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

        .field input::placeholder { color: #94a3b8; font-size: 13px; }

        .btn-submit {
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
        }

        .btn-submit:hover { background: #16a085; transform: translateY(-1px); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
        }

        .divider-line { flex: 1; height: 0.5px; background: #e2e8f0; }
        .divider-text { font-size: 12px; color: #94a3b8; }

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
    </style>
</head>
<body>
<div class="card">
    <div class="card-accent"></div>

    <div class="logo-wrap">
        <div class="logo-circle">
            <svg viewBox="0 0 24 24">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 4c1.4 0 2.8 1.1 2.8 2.5V9c.6.3 1.2.9 1.2 1.5v5c0 .8-.9 1.5-2 1.5H10c-1.1 0-2-.7-2-1.5v-5c0-.6.6-1.2 1.2-1.5V7.5C9.2 6.1 10.6 5 12 5z"/>
            </svg>
        </div>
    </div>

    <p class="card-title">استعادة كلمة المرور</p>
    <p class="card-sub">أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة التعيين</p>

    <?php if($error): ?>
        <div class="alert alert-danger">
            <span class="alert-dot"></span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if($success): ?>
        <div class="alert alert-success">
            <span class="alert-dot"></span>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form action="send_reset.php" method="POST">
        <div class="field">
            <label>البريد الإلكتروني</label>
            <input type="email" name="email"
                   placeholder="name@university.edu.eg"
                   required>
        </div>

        <button type="submit" class="btn-submit">
            إرسال رابط الاستعادة
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
        تذكرت كلمة المرور؟ <a href="login.php">تسجيل دخول</a>
    </p>
</div>
</body>
</html>