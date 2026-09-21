<?php
require_once __DIR__ . '/../api/config/database.php';

$db = Database::getInstance()->getConnection();

// Update admin password to 'admin123'
$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
$stmt->execute([$adminHash]);

// Update Piraba password to '123456'
$pirabaHash = password_hash('123456', PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'Piraba'");
$stmt->execute([$pirabaHash]);

echo "Updated admin and Piraba passwords successfully.\n";
