<?php
require_once __DIR__ . '/../api/config/database.php';
$db = Database::getInstance()->getConnection();

$stmt = $db->query("SELECT u.id, u.username, u.email, u.role, u.status, v.shop_name, v.owner_name FROM users u LEFT JOIN vendors v ON u.id = v.user_id WHERE u.role = 'vendor'");
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($vendors);
