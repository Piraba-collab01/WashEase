<?php
require 'api/config/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, username, email, role, status FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
