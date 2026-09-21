<?php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'commission_payments'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "Table 'commission_payments' already exists in the database!\n";
    } else {
        echo "Creating table 'commission_payments'...\n";
        $sql = "CREATE TABLE IF NOT EXISTS commission_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            amount DECIMAL(10, 2) NOT NULL,
            transaction_ref VARCHAR(100) NOT NULL,
            status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (vendor_id) REFERENCES vendors(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB;";
        
        $db->exec($sql);
        echo "Table 'commission_payments' created successfully!\n";
    }

    // Verify table structure
    $stmtDesc = $db->query("DESCRIBE commission_payments");
    echo "\nStructure of 'commission_payments':\n";
    foreach ($stmtDesc->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo "{$col['Field']} - {$col['Type']} - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
