<?php

class Payment
{
    private $secretKey;
    private $publicKey;
    private $paymentMethods;
    private $appUrl;
    private $baseUrl = "https://accept.paymob.com/v1/intention/";

    public function __construct()
    {
        $configPath = __DIR__ . '/../includes/env.php';

        if (!file_exists($configPath)) {
            throw new Exception("The config file is missing at: " . $configPath);
        }

        $config = require $configPath;

        $this->secretKey = $config['PAYMOB_SECRET_KEY'];
        $this->publicKey = $config['PAYMOB_PUBLIC_KEY'];
        $this->appUrl = rtrim($config['APP_URL'], '/');
        $this->paymentMethods = array_map('intval', explode(',', $config['PAYMOB_PAYMENT_METHODS']));
    }

    public function createInitialPayment($application, $user)
    {
        $fixedAmount = 500;
        $reference = $application['serial_number'] . '-INIT-' . time();

        $items = [
            [
                "name" => "دفع رسوم التقديم للطلب رقم " . $application['id'],
                "amount" => $fixedAmount * 100,
                "description" => "الرسوم المبدئية الثابتة للطلب رقم " . $application['serial_number'],
                "quantity" => 1
            ]
        ];

        return $this->sendIntentionRequest($fixedAmount, $user, $reference, $items);
    }

    public function createSamplePayment($application, $user)
    {
        if (!isset($application['sample_amount'])) {
            throw new Exception("Sample amount is not set for this application.");
        }

        $variableAmount = $application['sample_amount'];
        $reference = $application['serial_number'] . '-SAMP-' . time();

        $items = [
            [
                "name" => "رسوم مراجعة حجم العينة",
                "amount" => $variableAmount * 100,
                "description" => "رسوم مراجعة لعينة بحجم " . ($application['sample_size'] ?? 'محدد مسبقاً'),
                "quantity" => 1
            ]
        ];

        return $this->sendIntentionRequest($variableAmount, $user, $reference, $items);
    }

    public function getIntentionDetails($clientSecret)
    {
        $url = "https://accept.paymob.com/v1/intention/element/{$this->publicKey}/{$clientSecret}/";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'data' => $responseData,
                'is_paid' => $responseData['paid'] ?? false
            ];
        } else {
            return [
                'success' => false,
                'error' => json_decode($response, true)
            ];
        }
    }

    private function sendIntentionRequest($amountInEGP, $user, $reference, $items)
    {
        $nameParts = explode(' ', trim($user['full_name']), 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName = $nameParts[1] ?? 'Name';

        // Redirect URL points to our new receipt file
        $redirectionUrl = $this->appUrl . "/features/payment/receipt.php";

        $payload = [
            "amount" => $amountInEGP * 100,
            "currency" => "EGP",
            "payment_methods" => $this->paymentMethods,
            "items" => $items,
            "billing_data" => [
                "first_name" => $firstName,
                "last_name" => $lastName,
                "phone_number" => $user['phone_number'] ?? "NA",
                "email" => $user['email'] ?? "no-email@irb.edu",
                "street" => "NA",
                "building" => "NA",
                "floor" => "NA",
                "apartment" => "NA",
                "city" => "NA",
                "country" => "EG"
            ],
            "special_reference" => $reference,
            "redirection_url" => $redirectionUrl
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Token ' . $this->secretKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);

        $curl_error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            return [
                'success' => false,
                'error' => "cURL Connection Failed: " . $curl_error
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'client_secret' => $responseData['client_secret'],
                'special_reference' => $reference,
                'checkout_url' => "https://accept.paymob.com/unifiedcheckout/?publicKey={$this->publicKey}&clientSecret={$responseData['client_secret']}"
            ];
        } else {
            $decodedError = json_decode($response, true);
            return [
                'success' => false,
                'error' => $decodedError ? $decodedError : "Raw Paymob Response: " . $response
            ];
        }
    }
}
?>