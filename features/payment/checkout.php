<?php
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../classes/Payment.php';

Auth::checkRole(['student']);
$currentUser = Auth::user();
$application_id = (int) ($_GET['app_id'] ?? 0);

if (!$application_id)
    die("Error: No application selected.");

$db = new Database();

try {
    $applicationData = $db->selectById('applications', $application_id);

    if (!$applicationData || $applicationData['student_id'] != $currentUser['id']) {
        die("Error: Application not found or access denied.");
    }

    $userData = $db->selectById('users', $currentUser['id']);

    $payment = new Payment();
    $paymentResult = null;
    $phase = '';
    $amount = 0;

    if ($applicationData['current_stage'] === 'awaiting_initial_payment') {
        $paymentResult = $payment->createInitialPayment($applicationData, $userData);
        $phase = 'initial';
        $amount = 500.00;
    } elseif ($applicationData['current_stage'] === 'awaiting_sample_payment') {
        $paymentResult = $payment->createSamplePayment($applicationData, $userData);
        $phase = 'sample';
        $sampleSize = $applicationData['sample_size'];
        $amount = ($sampleSize <= 100) ? 200 : (($sampleSize <= 500) ? 500 : 1000);
    } else {
        die("Error: This application is not pending any payments.");
    }

    if ($paymentResult && $paymentResult['success']) {
        $insertData = [
            'application_id' => $application_id,
            'phase' => $phase,
            'amount' => $amount,
            'provider' => 'Paymob',
            'transaction_reference' => $paymentResult['special_reference'],
            'status' => 'pending'
        ];
        $db->insert('payments', $insertData);

        header("Location: " . $paymentResult['checkout_url']);
        exit;
    } else {
        echo "Payment Initialization Failed. Details: <br>";
        echo "<pre>" . print_r($paymentResult['error'], true) . "</pre>";
    }
} catch (Exception $e) {
    die("System Error: " . $e->getMessage());
}
?>