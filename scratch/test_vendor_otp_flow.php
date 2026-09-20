<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';
require_once __DIR__ . '/../api/controllers/AdminController.php';

$db = Database::getInstance()->getConnection();
$auth = new AuthController();
$admin = new AdminController();

$testEmail = 'test_unverified_' . time() . '@vendor.com';
$testUsername = 'unverified_' . time();

echo "=== 1. Registering Vendor ($testUsername) ===\n";
$regRes = $auth->register([
    'username' => $testUsername,
    'email' => $testEmail,
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'vendor',
    'shop_name' => 'Unverified Test Wash',
    'owner_name' => 'Test Owner',
    'contact_number' => '0711112222',
    'shop_address' => '123 Test St',
    'district' => 'Colombo'
]);
print_r($regRes);

echo "\n=== 2. Attempting Vendor Login BEFORE OTP Verification ===\n";
$loginRes1 = $auth->login([
    'username' => $testUsername,
    'password' => 'password123'
]);
print_r($loginRes1);

echo "\n=== 3. Checking Admin Pending Approvals BEFORE OTP Verification ===\n";
$pending1 = $admin->getPendingUsers();
$foundBeforeOtp = false;
foreach ($pending1['data'] ?? [] as $pu) {
    if ($pu['email'] === $testEmail) $foundBeforeOtp = true;
}
echo "Vendor listed in Admin Pending list before OTP? " . ($foundBeforeOtp ? "YES (FAILED)" : "NO (PASSED)") . "\n";

echo "\n=== 4. Verifying OTP ===\n";
$stmtOtp = $db->prepare("SELECT otp FROM otp_verifications WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmtOtp->execute([$testEmail]);
$otp = $stmtOtp->fetchColumn();
echo "Fetched OTP: $otp\n";

$verifyRes = $auth->verifyOTP(['email' => $testEmail, 'otp' => $otp]);
print_r($verifyRes);

echo "\n=== 5. Checking Admin Pending Approvals AFTER OTP Verification ===\n";
$pending2 = $admin->getPendingUsers();
$foundAfterOtp = false;
$vendorUserId = null;
foreach ($pending2['data'] ?? [] as $pu) {
    if ($pu['email'] === $testEmail) {
        $foundAfterOtp = true;
        $vendorUserId = $pu['user_id'];
    }
}
echo "Vendor listed in Admin Pending list after OTP? " . ($foundAfterOtp ? "YES (PASSED)" : "NO (FAILED)") . "\n";

echo "\n=== 6. Attempting Vendor Login AFTER OTP Verification but BEFORE Admin Approval ===\n";
$loginRes2 = $auth->login([
    'username' => $testUsername,
    'password' => 'password123'
]);
print_r($loginRes2);

if ($vendorUserId) {
    echo "\n=== 7. Admin Approving Vendor (User ID: $vendorUserId) ===\n";
    $appRes = $admin->approveUser($vendorUserId);
    print_r($appRes);

    echo "\n=== 8. Attempting Vendor Login AFTER Admin Approval ===\n";
    $loginRes3 = $auth->login([
        'username' => $testUsername,
        'password' => 'password123'
    ]);
    print_r($loginRes3);
}

echo "\n=== Test Completed ===\n";
