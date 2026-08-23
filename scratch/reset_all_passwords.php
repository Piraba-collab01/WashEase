<?php
require 'api/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    $users = [
        'admin@washease.com' => 'admin123',
        'bbanu2341@gmail.com' => 'password123',
        'bubble@washease.mock' => 'password123'
    ];
    
    foreach ($users as $email => $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$hash, $email]);
        echo "Updated password for $email to '$password'\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
