<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    public static function send(string $toEmail, string $toName, string $subject, string $messageBody, string $appSerial = ''): bool
    {
        $env = require __DIR__ . '/../includes/env.php';
       
    global $env;

        // Skip if SMTP is not configured
        if (empty($env['MAIL_USERNAME']) || empty($env['MAIL_PASSWORD'])) {
            error_log("[EmailService] SMTP credentials not configured — skipping email to $toEmail");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = $env['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['MAIL_USERNAME'];
            $mail->Password   = $env['MAIL_PASSWORD'];
            $mail->SMTPSecure = ($env['MAIL_ENCRYPTION'] === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $env['MAIL_PORT'];

            // Encoding
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            // Sender & recipient
            $mail->setFrom($env['MAIL_USERNAME'], $env['MAIL_FROM_NAME'] ?? 'IRB Digital System');
            $mail->addAddress($toEmail, $toName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = self::buildTemplate($toName, $messageBody, $appSerial);
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $messageBody));

            $mail->send();
            return true;

        } catch (Exception $e) {
            error_log("[EmailService] Failed to send email to $toEmail: " . $mail->ErrorInfo);
        
            // die("Gmail Error: " . $mail->ErrorInfo);
   
            return false;
        }
    }
    public static function sendAsync(string $toEmail, string $toName, string $subject, string $messageBody, string $appSerial = ''): void
    {
        require_once __DIR__ . '/../includes/env.php';
        global $env;
        $scriptPath = __DIR__ . '/EmailWorker.php';

        // Encode arguments as base64 JSON to safely pass via CLI
        $payload = base64_encode(json_encode([
            'to_email'   => $toEmail,
            'to_name'    => $toName,
            'subject'    => $subject,
            'message'    => $messageBody,
            'app_serial' => $appSerial,
        ], JSON_UNESCAPED_UNICODE));

        $phpBin = self::findPhpBinary();

        // Launch background process (Windows-compatible)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B \"\" \"$phpBin\" \"$scriptPath\" \"$payload\" > NUL 2>&1";
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = "$phpBin \"$scriptPath\" \"$payload\" > /dev/null 2>&1 &";
            exec($cmd);
        }
    }

    public static function triggerCron(): void
    {
        $scriptPath = __DIR__ . '/EmailCron.php';
        $phpBin = self::findPhpBinary();

        // Launch the cron worker in background (one-shot mode)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = "start /B \"\" \"$phpBin\" \"$scriptPath\" > NUL 2>&1";
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = "$phpBin \"$scriptPath\" > /dev/null 2>&1 &";
            exec($cmd);
        }
    }

    /**
     * Find the PHP binary path.
     */
    private static function findPhpBinary(): string
    {
        // Try common XAMPP locations first
        $candidates = [
            'C:\\xampp\\php\\php.exe',
            PHP_BINARY,
            'php',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return 'php'; // fallback
    }

    /**
     * Build a beautiful RTL HTML email template.
     */
    private static function buildTemplate(string $recipientName, string $messageBody, string $appSerial = ''): string
    {
        $serialBadge = '';
        if (!empty($appSerial)) {
            $serialBadge = '<div style="background:#2c3e50;color:#fff;display:inline-block;padding:6px 16px;border-radius:8px;font-weight:800;font-size:14px;margin-bottom:16px;letter-spacing:0.5px;">' . htmlspecialchars($appSerial) . '</div><br>';
        }

        $year = date('Y');
        $escapedName = htmlspecialchars($recipientName);
        $escapedBody = nl2br($messageBody);

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f2f5;padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#2c3e50 0%,#34495e 50%,#1abc9c 100%);padding:36px 40px;border-radius:16px 16px 0 0;text-align:center;">
                            <div style="font-size:28px;margin-bottom:10px;">🔬</div>
                            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:800;letter-spacing:1px;">نظام IRB الرقمي</h1>
                            <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:13px;font-weight:500;">لجنة أخلاقيات البحث العلمي</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="background:#ffffff;padding:36px 40px;border-left:1px solid #e8e8e8;border-right:1px solid #e8e8e8;">
                            
                            <!-- Greeting -->
                            <p style="margin:0 0 20px;font-size:17px;color:#2c3e50;font-weight:700;">
                                مرحباً {$escapedName} 👋
                            </p>

                            <!-- Serial Badge -->
                            {$serialBadge}

                            <!-- Message -->
                            <div style="background:linear-gradient(135deg,#f8f9fa 0%,#ffffff 100%);border:1px solid #e8e8e8;border-right:4px solid #1abc9c;border-radius:12px;padding:22px 24px;margin:0 0 24px;font-size:15px;color:#34495e;line-height:1.8;font-weight:600;">
                                {$escapedBody}
                            </div>

                            <!-- CTA Button -->
                            <div style="text-align:center;margin:28px 0;">
                                <a href="http://localhost/irb-digital-system/" style="display:inline-block;background:linear-gradient(135deg,#1abc9c 0%,#16a085 100%);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:10px;font-size:15px;font-weight:800;letter-spacing:0.5px;box-shadow:0 4px 15px rgba(26,188,156,0.35);">
                                    الدخول إلى النظام ←
                                </a>
                            </div>

                            <!-- Divider -->
                            <hr style="border:none;border-top:2px dashed #e8e8e8;margin:24px 0;">

                            <!-- Info Note -->
                            <p style="margin:0;font-size:12px;color:#95a5a6;line-height:1.6;font-weight:500;">
                                💡 هذا البريد مرسل تلقائياً من نظام IRB الرقمي. في حال وجود أي استفسار، يرجى التواصل مع إدارة اللجنة.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#2c3e50;padding:24px 40px;border-radius:0 0 16px 16px;text-align:center;">
                            <p style="margin:0 0 6px;color:rgba(255,255,255,0.7);font-size:12px;font-weight:500;">
                                © {$year} نظام IRB الرقمي — جميع الحقوق محفوظة
                            </p>
                            <p style="margin:0;color:rgba(255,255,255,0.45);font-size:11px;">
                                Institutional Review Board Digital System
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
?>
