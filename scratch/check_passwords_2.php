<?php
require_once __DIR__ . '/../api/config/database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT id, username, email, password_hash, role, status FROM users");
$users = $stmt->fetchAll();

$candidates = ['admin', 'admin123', 'Admin123', 'Admin@123', '123456', '12345678', 'password', 'Password123', 'vendor123', 'washease', 'washease123', 'piraba', 'piraba123', 'colombo123', 'kandy123', 'galle123', 'jaffna123'];

foreach ($users as $u) {
    if ($u['status'] !== 'active') continue;
    $found = false;
    foreach ($candidates as $pass) {
        if (password_verify($pass, $u['password_hash'])) {
            echo "User: {$u['username']} ({$u['role']}) -> '$pass'\n";
            $found = true;
            break;
        }
    }
    if (!$found) {
        echo "User: {$u['username']} ({$u['role']}) -> Password unknown\n";
    }
}
