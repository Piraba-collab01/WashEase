<?php
require_once __DIR__ . '/../api/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    echo "DB Connected Successfully!\n";
    
    // Check tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
    // Check users table count
    if (in_array('users', $tables)) {
        $stmt = $db->query("SELECT id, username, email, role, status FROM users");
        $users = $stmt->fetchAll();
        echo "Users count: " . count($users) . "\n";
        print_r($users);
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
