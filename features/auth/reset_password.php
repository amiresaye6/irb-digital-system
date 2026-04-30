<?php
session_start();
require_once "../../init.php";

$token  = $_GET['token']  ?? '';
$error  = $_SESSION['reset_error']   ?? '';
$success= $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// ✅ نتحقق من الـ token
$dbobj      = new Database();
$conn       = $dbobj->getconn();
$safe_token = $conn->real_escape_string($token);

$result = $conn->query("
    SELECT * FROM password_resets 
    WHERE token = '$safe_token' 
    AND expires_at > NOW()
    LIMIT 1
");

$reset = $result->fetch_assoc();

if(!$reset) {
    $expired = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور | IRB</title>
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

        .logo-circle svg { width: 28px; height: 28px; fill: #0f6e56; }

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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-dot { width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0; }
        .alert-danger  { background: #fadbd8; color: #991b1b; }
        .alert-success { background: #d5f4e6; color: #0f6e56; }
        .alert-warning { background: #fef3c7; color: #92400e; }

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
            background: #fff;
            box-shadow: 0 0 0 4px rgba(26,188,156,0.08);
        }

        .field input::placeholder { color: #94a3b8; font-size: 13px; }

        .password-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .btn-submit {
            background: #1abc9c;
            border: none;
            color: white;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 8px;
            transition: all 0.2s;
            font-family: 'Cairo', sans-serif;
        }

        .btn-submit:hover { background: #16a085; transform: translateY(-1px); }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #64748b;
            font-size: 13px;
            text-decoration: none;
        }

        .btn-back:hover { color: #1abc9c; }

        .expired-box {
            text-align: center;
            padding: 20px 0;
        }

        .expired-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: #fadbd8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .expired-icon svg { width: 32px; height: 32px; fill: #991b1b; }

        .btn-try-again {
            display: inline-block;
            margin-top: 16px;
            background: #1abc9c;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-try-again:hover { background: #16a085; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-accent"></div>

    <div class="logo-wrap">
        <div class="logo-circle">
            <svg viewBox="0 0 24 24">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
            </svg>
        </div>
    </div>

    <?php if(isset($expired)): ?>
        <div class="expired-box">
            <div class="expired-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </div>
            <p class="card-title">الرابط غير صالح</p>
            <p class="card-sub" style="margin-top:8px">
                هذا الرابط منتهي الصلاحية أو تم استخدامه مسبقاً.<br>
                يرجى طلب رابط جديد.
            </p>
            <a href="forgot_password.php" class="btn-try-again">طلب رابط جديد</a>
        </div>

    <?php else: ?>
        <p class="card-title">تعيين كلمة مرور جديدة</p>
        <p class="card-sub">يجب أن تكون كلمة المرور 8 أحرف على الأقل</p>

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

        <form action="update_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="field">
                <label>كلمة المرور الجديدة</label>
                <input type="password" name="password"
                       placeholder="••••••••" required minlength="8">
                <span class="password-hint">8 أحرف على الأقل</span>
            </div>

            <div class="field">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirm_password"
                       placeholder="••••••••" required minlength="8">
            </div>

            <button type="submit" class="btn-submit">
                حفظ كلمة المرور الجديدة
            </button>
        </form>

        <a href="login.php" class="btn-back">← العودة لتسجيل الدخول</a>

    <?php endif; ?>
</div>
</body>
</html>