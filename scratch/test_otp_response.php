<?php
require_once __DIR__ . '/../api/services/MailService.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';

$auth = new AuthController();
$email = 'otp_test_' . time() . '@test.com';

$res = $auth->register([
    'username' => 'otptest_' . time(),
    'email' => $email,
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'customer',
    'full_name' => 'OTP Test User',
    'contact_number' => '0712345678',
    'address' => '123 Test Rd'
]);

echo "API Register Response:\n";
print_r($res);
