<?php
require_once __DIR__ . '/../../init.php';

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

if (!$data || !isset($data['obj'])) {
    http_response_code(400);
    exit;
}

$obj = $data['obj'];
$env = require __DIR__ . '/../../includes/env.php';
$hmacSecret = $env['PAYMOB_HMAC_SECRET'];
$receivedHmac = $_GET['hmac'] ?? '';

// Sort HMAC fields alphabetically according to Paymob documentation
$hmacFields = [
    'amount_cents' => $obj['amount_cents'] ?? '',
    'created_at' => $obj['created_at'] ?? '',
    'currency' => $obj['currency'] ?? '',
    'error_occured' => ($obj['error_occured'] === true) ? 'true' : 'false',
    'has_parent_transaction' => ($obj['has_parent_transaction'] === true) ? 'true' : 'false',
    'id' => $obj['id'] ?? '',
    'integration_id' => $obj['integration_id'] ?? '',
    'is_3d_secure' => ($obj['is_3d_secure'] === true) ? 'true' : 'false',
    'is_auth' => ($obj['is_auth'] === true) ? 'true' : 'false',
    'is_capture' => ($obj['is_capture'] === true) ? 'true' : 'false',
    'is_refunded' => ($obj['is_refunded'] === true) ? 'true' : 'false',
    'is_standalone_payment' => ($obj['is_standalone_payment'] === true) ? 'true' : 'false',
    'is_voided' => ($obj['is_voided'] === true) ? 'true' : 'false',
    'order' => isset($obj['order']['id']) ? $obj['order']['id'] : ($obj['order'] ?? ''),
    'owner' => $obj['owner'] ?? '',
    'pending' => ($obj['pending'] === true) ? 'true' : 'false',
    'source_data.pan' => $obj['source_data']['pan'] ?? '',
    'source_data.sub_type' => $obj['source_data']['sub_type'] ?? '',
    'source_data.type' => $obj['source_data']['type'] ?? '',
    'success' => ($obj['success'] === true) ? 'true' : 'false'
];

$concatenatedString = implode('', $hmacFields);
$calculatedHmac = hash_hmac('sha512', $concatenatedString, $hmacSecret);

if ($calculatedHmac !== $receivedHmac) {
    http_response_code(403);
    die("HMAC validation failed.");
}

$db = new Database();
$merchant_order_id = $obj['order']['merchant_order_id'] ?? '';

if ($merchant_order_id) {
    $paymentRecord = $db->selectWhere('payments', 'transaction_reference', $merchant_order_id);

    // Allowing update if not already completed (allows promote failed to completed)
    if ($paymentRecord && $paymentRecord['status'] !== 'completed') {
        $isSuccess = $obj['success'] === true;
        $newStatus = $isSuccess ? 'completed' : 'failed';

        $updateData = [
            'status' => $newStatus,
            'gateway_transaction_id' => $obj['id'],
            'gateway_response' => json_encode($obj)
        ];

        // 2. Only add the timestamp if the payment was successful
        if ($isSuccess) {
            $updateData['paid_at'] = date('Y-m-d H:i:s');
        }

        // 3. Execute the update
        $db->updateById('payments', $paymentRecord['id'], $updateData);

        if ($isSuccess) {
            $application_id = $paymentRecord['application_id'];
            $phase = $paymentRecord['phase'];
            $current_pay_id = $paymentRecord['id'];
            $nextStage = ($phase === 'initial') ? 'awaiting_sample_calc' : 'under_review';

            // CLEANUP: Mark any other pending attempts for this application/phase as failed
            $cleanup_sql = "UPDATE payments SET status = 'failed' 
                           WHERE application_id = $application_id 
                           AND phase = '$phase' 
                           AND id != $current_pay_id 
                           AND status = 'pending'";
            $db->getconn()->query($cleanup_sql);

            // Update application stage
            $db->updateById('applications', $application_id, [
                'current_stage' => $nextStage
            ]);

            // Fetch application data needed for logs and notifications
            $appData = $db->selectById('applications', $application_id);
            if ($appData) {
                $serialNumber = $appData['serial_number'];
                $studentId = $appData['student_id'];

                // 1. Log the Action
                $phaseText = ucfirst($phase);
                $logAction = "$phaseText Payment completed for Serial [$serialNumber]";
                $db->insert('logs', [
                    'application_id' => $application_id,
                    'user_id' => $studentId,
                    'action' => $logAction
                ]);

                // 2. Notify the Student
                $arabicPhase = ($phase === 'initial') ? 'التقديم المبدئية' : 'مراجعة حجم العينة';
                $notificationMsg = "تم تأكيد استلام رسوم ($arabicPhase) بنجاح لطلبك رقم $serialNumber.";
                $db->insert('notifications', [
                    'user_id' => $studentId,
                    'message' => $notificationMsg,
                    'channel' => 'email'
                ]);


                if ($nextStage === 'awaiting_sample_calc') {
                    require_once __DIR__ . '/../../classes/Applications.php';
                    $samp_sql = "SELECT id FROM users WHERE role = 'sample_officer'";
                    $samp_res = $db->conn->query($samp_sql);
                    if ($samp_res && $samp_res->num_rows > 0) {
                        $samplerMsg = "تم سداد رسوم التقديم المبدئية للبحث رقم ({$serialNumber}) ويحتاج الآن لحساب العينة.";
                        while ($samp = $samp_res->fetch_assoc()) {
                            Applications::createNotification($db->conn, $samp['id'], $application_id, $samplerMsg);
                        }
                    }
                }
            }
        }
    }
}

http_response_code(200);
echo "Callback received.";
?>