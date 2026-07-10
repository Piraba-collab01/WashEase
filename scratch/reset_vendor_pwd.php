<?php
require 'api/config/database.php';
$db = Database::getInstance()->getConnection();
$new_hash = password_hash('password123', PASSWORD_BCRYPT);
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = 'bubble@washease.mock'");
$stmt->execute([$new_hash]);
echo "Updated password for bubble@washease.mock to password123\n";
