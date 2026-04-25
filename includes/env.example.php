<?php
// Team: Copy this file, rename it to 'env.php', and fill in your local database details.

return [
    'DB_HOST' => 'localhost',
    'DB_USER' => 'root',
    'DB_PASS' => '',          // Leave blank or put default
    'DB_NAME' => 'irb_system',
    'DB_PORT' => 3306,         // Default MySQL port


     // Paymob Credentials
    'PAYMOB_SECRET_KEY' => 'egy_sk_test_somenumbershere213213123421',
    'PAYMOB_PUBLIC_KEY' => 'egy_pk_test_somenumbershere213213123421',

    // Integration IDs
    'PAYMOB_PAYMENT_METHODS' => '123,456,789',

    // redirection from paymob HMAC
    'PAYMOB_HMAC_SECRET' => 'somccharsandnumbers',
    'APP_URL' => 'http://localhost:80/irb-digital-system', // remimber to add yoru correct port number
]; 
?>