<?php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Verify table structure
    $stmt = $db->query("DESCRIBE commission_payments");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "commission_payments table columns: " . implode(', ', $columns) . "\n";
    echo "Table verified successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
