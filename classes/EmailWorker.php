<?php


if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

if (!isset($argv[1])) {
    error_log("[EmailWorker] No payload provided.");
    exit(1);
}

$payload = json_decode(base64_decode($argv[1]), true);

if (!$payload || empty($payload['to_email'])) {
    error_log("[EmailWorker] Invalid payload.");
    exit(1);
}

require_once __DIR__ . '/EmailService.php';

$result = EmailService::send(
    $payload['to_email'],
    $payload['to_name'] ?? '',
    $payload['subject'] ?? 'إشعار من نظام IRB',
    $payload['message'] ?? '',
    $payload['app_serial'] ?? ''
);

if ($result) {
    error_log("[EmailWorker] Email sent successfully to {$payload['to_email']}");
} else {
    error_log("[EmailWorker] Failed to send email to {$payload['to_email']}");
}

exit($result ? 0 : 1);
?>
