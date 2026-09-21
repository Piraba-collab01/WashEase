<?php
require_once __DIR__ . '/../api/config/database.php';

try {
    $db = Database::getInstance()->getConnection();

    // Check if registration_data column exists in otp_verifications
    $stmt = $db->query("SHOW COLUMNS FROM otp_verifications LIKE 'registration_data'");
    $col = $stmt->fetch();

    if (!$col) {
        echo "Adding 'registration_data' column to 'otp_verifications' table...\n";
        $db->exec("ALTER TABLE otp_verifications ADD COLUMN registration_data TEXT NULL AFTER is_verified");
        echo "Column 'registration_data' added successfully!\n";
    } else {
        echo "Column 'registration_data' already exists in 'otp_verifications'.\n";
    }

    $stmtDesc = $db->query("DESCRIBE otp_verifications");
    print_r($stmtDesc->fetchAll(PDO::FETCH_ASSOC));

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
