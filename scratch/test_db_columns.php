<?php
require_once __DIR__ . '/../api/config/database.php';
$db = Database::getInstance()->getConnection();
foreach (['users', 'vendors', 'otp_verifications'] as $table) {
    echo "=== Table: $table ===\n";
    $stmt = $db->query("DESCRIBE $table");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }
    echo "\n";
}
