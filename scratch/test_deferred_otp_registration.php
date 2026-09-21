<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';

$db = Database::getInstance()->getConnection();
$auth = new AuthController();

$custEmail = 'deferred_cust_' . time() . '@test.com';
$custUsername = 'defcust_' . time();

$vendEmail = 'deferred_vend_' . time() . '@test.com';
$vendUsername = 'defvend_' . time();

echo "=== 1. Submitting Customer Registration ($custEmail) ===\n";
$regRes1 = $auth->register([
    'username' => $custUsername,
    'email' => $custEmail,
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'customer',
    'full_name' => 'Deferred Customer',
    'contact_number' => '0711111111',
    'address' => '123 Test Street'
]);
print_r($regRes1);

echo "\n=== 2. Checking Database BEFORE OTP Verification ===\n";
$stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmtCheck->execute([$custEmail]);
$countBefore = $stmtCheck->fetchColumn();
echo "User rows in 'users' table before OTP: $countBefore " . ($countBefore == 0 ? "(PASSED: No data inserted!)" : "(FAILED)") . "\n";

echo "\n=== 3. Verifying Customer OTP ===\n";
$stmtOtp = $db->prepare("SELECT otp FROM otp_verifications WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmtOtp->execute([$custEmail]);
$otp1 = $stmtOtp->fetchColumn();

$verifyRes1 = $auth->verifyOTP(['email' => $custEmail, 'otp' => $otp1]);
print_r($verifyRes1);

echo "\n=== 4. Checking Database AFTER OTP Verification ===\n";
$stmtCheck->execute([$custEmail]);
$countAfter = $stmtCheck->fetchColumn();
echo "User rows in 'users' table after OTP: $countAfter " . ($countAfter == 1 ? "(PASSED: Data created upon OTP verification!)" : "(FAILED)") . "\n";


echo "\n\n=== 5. Submitting Vendor Registration ($vendEmail) ===\n";
$regRes2 = $auth->register([
    'username' => $vendUsername,
    'email' => $vendEmail,
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'vendor',
    'shop_name' => 'Deferred Vendor Wash',
    'owner_name' => 'Deferred Owner',
    'contact_number' => '0722222222',
    'shop_address' => '456 Vendor Road',
    'district' => 'Colombo'
]);
print_r($regRes2);

echo "\n=== 6. Checking Database BEFORE Vendor OTP Verification ===\n";
$stmtCheck->execute([$vendEmail]);
$vCountBefore = $stmtCheck->fetchColumn();
echo "Vendor rows in 'users' table before OTP: $vCountBefore " . ($vCountBefore == 0 ? "(PASSED: No vendor data inserted!)" : "(FAILED)") . "\n";

echo "\n=== 7. Verifying Vendor OTP ===\n";
$stmtOtp->execute([$vendEmail]);
$otp2 = $stmtOtp->fetchColumn();

$verifyRes2 = $auth->verifyOTP(['email' => $vendEmail, 'otp' => $otp2]);
print_r($verifyRes2);

echo "\n=== 8. Checking Database AFTER Vendor OTP Verification ===\n";
$stmtCheck->execute([$vendEmail]);
$vCountAfter = $stmtCheck->fetchColumn();
echo "Vendor rows in 'users' table after OTP: $vCountAfter " . ($vCountAfter == 1 ? "(PASSED: Vendor data created upon OTP verification!)" : "(FAILED)") . "\n";

echo "\n=== Test Complete ===\n";
