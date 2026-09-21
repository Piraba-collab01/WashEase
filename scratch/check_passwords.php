<?php
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/controllers/AuthController.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, username, email, password_hash, role, status FROM users LIMIT 10");
$users = $stmt->fetchAll();

echo "User list and password checks:\n";
foreach ($users as $u) {
    echo "ID: {$u['id']} | Username: {$u['username']} | Role: {$u['role']} | Status: {$u['status']}\n";
    // Check common passwords
    $testPasswords = ['admin', 'admin123', '123456', 'password', 'password123', 'washease123'];
    foreach ($testPasswords as $pass) {
        if (password_verify($pass, $u['password_hash'])) {
            echo "   -> Password is: '$pass'\n";
        }
    }
}
