<?php
// scratch/update_schema.php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Modifying users table status column...\n";
    $db->exec("ALTER TABLE users MODIFY COLUMN status ENUM('pending', 'active', 'rejected', 'inactive', 'blocked') DEFAULT 'active'");
    echo "users table status modified successfully.\n";

    echo "Checking if unpaid_commission column exists in vendors table...\n";
    $stmt = $db->query("SHOW COLUMNS FROM vendors LIKE 'unpaid_commission'");
    $columnExists = $stmt->fetch();

    if (!$columnExists) {
        echo "Adding unpaid_commission column to vendors table...\n";
        $db->exec("ALTER TABLE vendors ADD COLUMN unpaid_commission DECIMAL(10,2) DEFAULT 0.00");
        echo "unpaid_commission column added successfully.\n";
    } else {
        echo "unpaid_commission column already exists.\n";
    }

    echo "Updating unpaid_commission for existing paid orders...\n";
    $db->exec("
        UPDATE vendors v 
        SET unpaid_commission = COALESCE((
            SELECT SUM(i.total_amount * (v.commission_pct / 100)) 
            FROM orders o 
            JOIN invoices i ON o.id = i.order_id 
            WHERE o.vendor_id = v.user_id AND o.payment_status = 'Paid'
        ), 0.00)
    ");
    echo "Existing commissions updated.\n";
    
    echo "Done.\n";
} catch (Exception $e) {
    echo "Error updating database schema: " . $e->getMessage() . "\n";
}
