<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';
require_once __DIR__ . '/../api/controllers/AdminController.php';

$db = Database::getInstance()->getConnection();

echo "=== 1. Registering New Vendor ===\n";
$auth = new AuthController();
$regData = [
    'username' => 'superclean_' . time(),
    'email' => 'superclean_' . time() . '@test.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'vendor',
    'shop_name' => 'Super Clean Laundry',
    'owner_name' => 'John Clean',
    'contact_number' => '0771234567',
    'shop_address' => '123 Main St, Colombo',
    'district' => 'Colombo',
    'latitude' => 6.9271,
    'longitude' => 79.8612,
    'opening_time' => '08:00',
    'closing_time' => '20:00'
];

$regRes = $auth->register($regData);
print_r($regRes);

if (!$regRes['success']) {
    die("Registration failed\n");
}

echo "\n=== 2. Fetching OTP for email: " . $regData['email'] . " ===\n";
$stmt = $db->prepare("SELECT otp FROM otp_verifications WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$regData['email']]);
$otpRow = $stmt->fetch();
$otp = $otpRow['otp'];
echo "Fetched OTP: $otp\n";

echo "\n=== 3. Verifying OTP ===\n";
$verifyRes = $auth->verifyOTP(['email' => $regData['email'], 'otp' => $otp]);
print_r($verifyRes);

echo "\n=== 4. Checking Admin Notifications ===\n";
$stmtN = $db->prepare("SELECT n.*, u.username FROM notifications n JOIN users u ON n.user_id = u.id WHERE u.role = 'admin' ORDER BY n.id DESC LIMIT 3");
$stmtN->execute();
$adminNotifs = $stmtN->fetchAll(PDO::FETCH_ASSOC);
print_r($adminNotifs);

echo "\n=== 5. Fetching Pending Vendor Registrations for Admin ===\n";
$admin = new AdminController();
$pendingRes = $admin->getPendingUsers();
print_r($pendingRes);

if (!empty($pendingRes['data'])) {
    $newVendorUserId = $pendingRes['data'][0]['user_id'];
    echo "\n=== 6. Admin Approving Vendor User ID: $newVendorUserId ===\n";
    $approveRes = $admin->approveUser($newVendorUserId);
    print_r($approveRes);

    echo "\n=== 7. Checking Vendor Status & Notifications ===\n";
    $stmtU = $db->prepare("SELECT status FROM users WHERE id = ?");
    $stmtU->execute([$newVendorUserId]);
    echo "Vendor User Status: " . $stmtU->fetchColumn() . "\n";

    $stmtVN = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmtVN->execute([$newVendorUserId]);
    print_r($stmtVN->fetch(PDO::FETCH_ASSOC));
}

echo "\n=== Test Complete ===\n";
