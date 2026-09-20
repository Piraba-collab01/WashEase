<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';

$auth = new AuthController();
$regData = [
    'username' => 'apexwash',
    'email' => 'contact@apexwash.com',
    'password' => 'password123',
    'confirm_password' => 'password123',
    'role' => 'vendor',
    'shop_name' => 'Apex Wash & Dry',
    'owner_name' => 'Michael Scott',
    'contact_number' => '0779998888',
    'shop_address' => '456 Galle Road, Colombo',
    'district' => 'Colombo',
    'latitude' => 6.9000,
    'longitude' => 79.8500,
    'opening_time' => '07:30',
    'closing_time' => '21:00'
];

$regRes = $auth->register($regData);
print_r($regRes);

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT otp FROM otp_verifications WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute(['contact@apexwash.com']);
$otpRow = $stmt->fetch();

$verifyRes = $auth->verifyOTP(['email' => 'contact@apexwash.com', 'otp' => $otpRow['otp']]);
print_r($verifyRes);
echo "Pending vendor 'Apex Wash & Dry' is now waiting for admin approval!\n";
