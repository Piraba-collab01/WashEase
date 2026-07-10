<?php
require 'api/config/database.php';
$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    $trackingNumber = "WE-MOCK" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
    
    // 1. Insert Order (estimated weight = 4.50, clothes_weight/measured weight = 6.50)
    $stmt = $db->prepare("
        INSERT INTO orders 
        (tracking_number, customer_id, vendor_id, pickup_address, pickup_date, pickup_time, clothes_weight, estimated_weight, service_type, special_instructions, status, payment_status) 
        VALUES (?, 7, 10, 'Jaffna Mock Road', '2026-12-25', '10:00:00', 6.50, 4.50, 'Wash Only', 'Mock weight correction test order', 'Ready', 'Pending Confirmation')");
    $stmt->execute([$trackingNumber]);
    $orderId = $db->lastInsertId();

    // 2. Insert Invoice
    $laundryCharges = 6.50 * 10.00;
    $serviceCharge = 5.00;
    $subtotal = $laundryCharges + $serviceCharge;
    $taxes = $subtotal * 0.05;
    $totalAmount = $subtotal + $taxes;
    $invoiceNumber = "INV-MOCK" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

    $stmt = $db->prepare("
        INSERT INTO invoices (invoice_number, order_id, laundry_charges, service_charges, taxes, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $invoiceNumber,
        $orderId,
        $laundryCharges,
        $serviceCharge,
        $taxes,
        $totalAmount
    ]);

    // 3. Insert Notification
    $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (7, ?)");
    $weightMsg = "Order $trackingNumber: The laundry shop updated the measured weight from 4.50 kg to 6.50 kg. Bill calculations have been updated.";
    $stmt->execute([$weightMsg]);

    $db->commit();
    echo "Successfully seeded mock adjusted order! Tracking: $trackingNumber, Order ID: $orderId, Invoice: $invoiceNumber\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
