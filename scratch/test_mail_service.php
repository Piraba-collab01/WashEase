<?php
require_once __DIR__ . '/../api/services/MailService.php';

echo "Testing MailService::sendOTP...\n";
$res = MailService::sendOTP('bbanu2341@gmail.com', '123456');
var_dump($res);

$logFile = __DIR__ . '/../api/logs/otp.log';
if (file_exists($logFile)) {
    echo "\nLatest OTP Log Content:\n";
    echo file_get_contents($logFile);
}
