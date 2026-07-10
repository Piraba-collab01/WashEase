<?php
require 'api/config/database.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("DESCRIBE orders");
print_r($stmt->fetchAll());
