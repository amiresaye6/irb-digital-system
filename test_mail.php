<?php
require_once __DIR__ . '/classes/EmailService.php';
EmailService::sendAsync('amirmawla27@gmail.com', 'Amir', 'Test Async Email', 'This is an async test');
echo "Async trigger sent!";
