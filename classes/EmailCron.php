<?php


if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/Database.php';

$isDaemon = in_array('--daemon', $argv ?? []);
$pollInterval = 5;

function processUnsentNotifications(): int
{
    $database = new Database();
    $db = $database->conn;

    // Fetch all unsent notifications with user info and optional app serial
    $sql = "SELECT 
                n.id, n.user_id, n.application_id, n.message,
                u.full_name, u.email,
                a.serial_number
            FROM notifications n
            JOIN users u ON n.user_id = u.id
            LEFT JOIN applications a ON n.application_id = a.id
            WHERE n.email_sent = 0
            ORDER BY n.created_at ASC
            LIMIT 50";

    $result = $db->query($sql);

    if (!$result || $result->num_rows === 0) {
        return 0;
    }

    $sent = 0;

    while ($row = $result->fetch_assoc()) {
        // Skip if user has no email
        if (empty($row['email'])) {
            // Mark as sent so we don't keep retrying
            $update = $db->prepare("UPDATE notifications SET email_sent = 1 WHERE id = ?");
            $update->bind_param("i", $row['id']);
            $update->execute();
            continue;
        }

        $success = EmailService::send(
            $row['email'],
            $row['full_name'] ?? '',
            'إشعار جديد — نظام IRB الرقمي',
            $row['message'],
            $row['serial_number'] ?? ''
        );

       
        $update = $db->prepare("UPDATE notifications SET email_sent = 1 WHERE id = ?");
        $update->bind_param("i", $row['id']);
        $update->execute();

        if ($success) {
            $sent++;
            echo "[" . date('Y-m-d H:i:s') . "] ✓ Email sent to {$row['email']} (notification #{$row['id']})\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] ✗ Failed to send to {$row['email']} (notification #{$row['id']})\n";
        }
    }

    $db->close();
    return $sent;
}

// ─── Main ───────────────────────────────────────────────
echo "═══════════════════════════════════════════\n";
echo "  IRB Email Worker — " . ($isDaemon ? "Daemon Mode" : "One-Shot Mode") . "\n";
echo "═══════════════════════════════════════════\n\n";

if ($isDaemon) {
    echo "[" . date('Y-m-d H:i:s') . "] Daemon started. Polling every {$pollInterval}s...\n\n";

    while (true) {
        try {
            $count = processUnsentNotifications();
            if ($count > 0) {
                echo "[" . date('Y-m-d H:i:s') . "] Batch complete: {$count} email(s) sent.\n";
            }
        } catch (\Throwable $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
            error_log("[EmailCron] " . $e->getMessage());
        }

        sleep($pollInterval);
    }
} else {
    try {
        $count = processUnsentNotifications();
        echo "\nDone. Sent {$count} email(s).\n";
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        error_log("[EmailCron] " . $e->getMessage());
        exit(1);
    }
}
