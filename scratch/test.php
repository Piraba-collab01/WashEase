<?php
require 'api/config/database.php';
$db = Database::getInstance()->getConnection();
$orig_hash = '$2y$10$jJLkE1SHvTMv0pzPcY9i2..qASnOGrM3lxDhYmVNTbyvr9DUeFnQS';
$stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = 'bbanu2341@gmail.com'");
$stmt->execute([$orig_hash]);
echo "Restored original password hash for bbanu2341@gmail.com\n";
