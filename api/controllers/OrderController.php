<?php
// washease-api/controllers/OrderController.php

require_once __DIR__ . '/../config/database.php';

class OrderController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function createOrder($customerId, $data) {
        $vendorId = intval($data['vendor_id'] ?? 0);
        $pickupAddress = trim($data['pickup_address'] ?? '');
        $pickupDate = $data['pickup_date'] ?? '';
        $pickupTime = $data['pickup_time'] ?? '';
        $weight = floatval($data['clothes_weight'] ?? 0);
        $serviceType = $data['service_type'] ?? '';
        $specialInstructions = trim($data['special_instructions'] ?? '');

        if ($vendorId <= 0 || empty($pickupAddress) || empty($pickupDate) || empty($pickupTime) || $weight <= 0 || empty($serviceType)) {
            return ["success" => false, "message" => "Please fill in all required order booking details."];
        }

        // Generate unique tracking number (e.g. WE-XXXXXX)
        $trackingNumber = "WE-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO orders 
                (tracking_number, customer_id, vendor_id, pickup_address, pickup_date, pickup_time, clothes_weight, estimated_weight, service_type, special_instructions, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Unpaid')");
            
            $stmt->execute([
                $trackingNumber,
                $customerId,
                $vendorId,
                $pickupAddress,
                $pickupDate,
                $pickupTime,
                $weight,
                $weight,
                $serviceType,
                $specialInstructions
            ]);

            $orderId = $this->db->lastInsertId();

            // Create notification for Vendor
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $notificationMsg = "New laundry booking order placed! Tracking #: $trackingNumber. Pickup scheduled on $pickupDate.";
            $stmt->execute([$vendorId, $notificationMsg]);

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Booking placed successfully!",
                "data" => [
                    "order_id" => $orderId,
                    "tracking_number" => $trackingNumber
                ]
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Failed to book pickup: " . $e->getMessage()];
        }
    }

    public function listOrders($userId, $role, $filters = []) {
        try {
            $query = "
                SELECT 
                    o.id as order_id, 
                    o.tracking_number, 
                    o.pickup_date, 
                    o.pickup_time, 
                    o.clothes_weight, 
                    o.service_type, 
                    o.status, 
                    o.payment_status, 
                    o.created_at,
                    v.shop_name as vendor_name,
                    c.full_name as customer_name
                FROM orders o
                JOIN vendors v ON o.vendor_id = v.user_id
                JOIN customers c ON o.customer_id = c.user_id";

            $whereClauses = [];
            $params = [];

            if ($role === 'customer') {
                $whereClauses[] = "o.customer_id = :user_id";
                $params[':user_id'] = $userId;
            } else if ($role === 'vendor') {
                $whereClauses[] = "o.vendor_id = :user_id";
                $params[':user_id'] = $userId;
            }

            // Apply filters (for Admin or others)
            if (!empty($filters['date'])) {
                $whereClauses[] = "DATE(o.created_at) = :filter_date";
                $params[':filter_date'] = $filters['date'];
            }
            if (!empty($filters['vendor_id'])) {
                $whereClauses[] = "o.vendor_id = :filter_vendor";
                $params[':filter_vendor'] = $filters['vendor_id'];
            }
            if (!empty($filters['customer_id'])) {
                $whereClauses[] = "o.customer_id = :filter_customer";
                $params[':filter_customer'] = $filters['customer_id'];
            }
            if (!empty($filters['status'])) {
                $whereClauses[] = "o.status = :filter_status";
                $params[':filter_status'] = $filters['status'];
            }

            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(" AND ", $whereClauses);
            }

            $query .= " ORDER BY o.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            return ["success" => true, "data" => $orders];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error retrieving orders: " . $e->getMessage()];
        }
    }

    public function getOrderDetails($orderId, $userId, $role) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    o.*,
                    c.full_name as customer_name, c.contact_number as customer_phone,
                    v.shop_name, v.owner_name as vendor_owner, v.contact_number as vendor_phone, v.shop_address as vendor_address
                FROM orders o
                JOIN customers c ON o.customer_id = c.user_id
                JOIN vendors v ON o.vendor_id = v.user_id
                WHERE o.id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();

            if (!$order) {
                return ["success" => false, "message" => "Order not found."];
            }

            // Secure endpoint check: only customer of this order, vendor of this order, or admin can access
            if ($role === 'customer' && $order['customer_id'] != $userId) {
                return ["success" => false, "message" => "Access denied."];
            }
            if ($role === 'vendor' && $order['vendor_id'] != $userId) {
                return ["success" => false, "message" => "Access denied."];
            }

            // Get invoice if exists
            $stmt = $this->db->prepare("SELECT * FROM invoices WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $invoice = $stmt->fetch();

            return [
                "success" => true,
                "data" => [
                    "order" => $order,
                    "invoice" => $invoice ?: null
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateStatus($vendorId, $orderId, $data) {
        $status = $data['status'] ?? '';
        $validStatuses = ['Pending', 'Accepted', 'Pickup Scheduled', 'Picked Up', 'Washing', 'Ironing', 'Ready', 'Delivered'];

        if (!in_array($status, $validStatuses)) {
            return ["success" => false, "message" => "Invalid order status."];
        }

        try {
            $this->db->beginTransaction();

            // Check if order belongs to vendor
            $stmt = $this->db->prepare("SELECT id, customer_id, tracking_number, status FROM orders WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$orderId, $vendorId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Order not found or access denied.");
            }

            // Update status
            $stmt = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);

            // Notify Customer
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $custMessage = "Your order status (Tracking #: {$order['tracking_number']}) has been updated to: $status.";
            $stmt->execute([$order['customer_id'], $custMessage]);

            // Reward level calculation on Delivered status
            if ($status === 'Delivered') {
                // Count completed orders
                $stmt = $this->db->prepare("SELECT COUNT(id) as completed FROM orders WHERE vendor_id = ? AND status = 'Delivered'");
                $stmt->execute([$vendorId]);
                $countRes = $stmt->fetch();
                $completedCount = intval($countRes['completed'] ?? 0);

                // Determine level
                $newLevel = 'Bronze';
                if ($completedCount > 100) {
                    $newLevel = 'Gold';
                } else if ($completedCount > 50) {
                    $newLevel = 'Silver';
                }

                // Get settings for commission
                $settingKey = strtolower($newLevel) . "_pct";
                $stmt = $this->db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = ?");
                $stmt->execute([$settingKey]);
                $setting = $stmt->fetch();
                $commissionPct = floatval($setting['setting_value'] ?? ($newLevel === 'Gold' ? 5.00 : ($newLevel === 'Silver' ? 10.00 : 15.00)));

                // Get current level
                $stmt = $this->db->prepare("SELECT reward_level FROM vendors WHERE user_id = ?");
                $stmt->execute([$vendorId]);
                $vendorCur = $stmt->fetch();

                if ($vendorCur && $vendorCur['reward_level'] !== $newLevel) {
                    // Level updated!
                    $stmt = $this->db->prepare("UPDATE vendors SET reward_level = ?, commission_pct = ? WHERE user_id = ?");
                    $stmt->execute([$newLevel, $commissionPct, $vendorId]);

                    // Add level up notification
                    $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $lvlMessage = "Congratulations! Your Shop has reached $newLevel commission level! Commission is reduced to $commissionPct%.";
                    $stmt->execute([$vendorId, $lvlMessage]);
                }
            }

            $this->db->commit();
            return ["success" => true, "message" => "Order status updated to: $status."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function createBill($vendorId, $orderId, $data) {
        $serviceCharge = floatval($data['service_charge'] ?? 0);
        $additionalCharge = floatval($data['additional_charge'] ?? 0);
        $actualWeight = isset($data['actual_weight']) ? floatval($data['actual_weight']) : 0;

        try {
            $this->db->beginTransaction();

            // Verify order belongs to vendor
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$orderId, $vendorId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Order not found or access denied.");
            }

            // Update weight if vendor modified it
            if ($actualWeight > 0 && abs($actualWeight - $order['clothes_weight']) > 0.01) {
                $origWeight = $order['clothes_weight'];
                
                // If estimated_weight is not set yet, store the original weight there
                if (empty($order['estimated_weight'])) {
                    $stmt = $this->db->prepare("UPDATE orders SET estimated_weight = ? WHERE id = ?");
                    $stmt->execute([$origWeight, $orderId]);
                    $order['estimated_weight'] = $origWeight;
                }
                
                $stmt = $this->db->prepare("UPDATE orders SET clothes_weight = ? WHERE id = ?");
                $stmt->execute([$actualWeight, $orderId]);
                $order['clothes_weight'] = $actualWeight;

                // Send notification to customer about weight correction
                $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $weightMsg = "Order {$order['tracking_number']}: The laundry shop updated the measured weight from " . number_format($origWeight, 2) . " kg to " . number_format($actualWeight, 2) . " kg. Bill calculations have been updated.";
                $stmt->execute([$order['customer_id'], $weightMsg]);
            }

            // Retrieve rate per kg from setting
            $stmt = $this->db->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'default_rate_per_kg'");
            $stmt->execute();
            $setting = $stmt->fetch();
            $ratePerKg = floatval($setting['setting_value'] ?? 10.00);

            // Compute charges
            $laundryCharges = $order['clothes_weight'] * $ratePerKg;
            $subtotal = $laundryCharges + $serviceCharge + $additionalCharge;
            $taxes = $subtotal * 0.05; // 5% tax
            $totalAmount = $subtotal + $taxes;

            // Generate invoice number
            $invoiceNumber = "INV-" . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

            // Insert Invoice
            $stmt = $this->db->prepare("
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

            // Update order status to Pending Confirmation
            $stmt = $this->db->prepare("UPDATE orders SET payment_status = 'Pending Confirmation' WHERE id = ?");
            $stmt->execute([$orderId]);

            // Send notification to customer
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $custMsg = "Invoice $invoiceNumber generated for Order {$order['tracking_number']}. Total Amount: $" . number_format($totalAmount, 2) . ". Please confirm your payment.";
            $stmt->execute([$order['customer_id'], $custMsg]);

            $this->db->commit();
            return ["success" => true, "message" => "Invoice generated successfully and notification sent to customer."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => "Billing generation failed: " . $e->getMessage()];
        }
    }

    public function confirmPayment($customerId, $orderId, $data) {
        $actualPaidAmount = floatval($data['actual_paid_amount'] ?? 0);

        try {
            $this->db->beginTransaction();

            // Fetch order and invoice details
            $stmt = $this->db->prepare("
                SELECT o.*, i.id as invoice_id, i.total_amount 
                FROM orders o
                JOIN invoices i ON o.id = i.order_id
                WHERE o.id = ? AND o.customer_id = ?");
            $stmt->execute([$orderId, $customerId]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Order or invoice details not found.");
            }

            $invoiceAmount = floatval($order['total_amount']);

            // Mismatch Check
            if (abs($actualPaidAmount - $invoiceAmount) > 0.01) {
                // Fraud Alert!
                $stmt = $this->db->prepare("
                    INSERT INTO fraud_alerts (order_id, vendor_id, customer_id, vendor_amount, customer_amount, difference, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
                
                $diff = $invoiceAmount - $actualPaidAmount;
                $stmt->execute([
                    $orderId,
                    $order['vendor_id'],
                    $customerId,
                    $invoiceAmount,
                    $actualPaidAmount,
                    $diff
                ]);

                // Update payment to mismatch
                $stmt = $this->db->prepare("
                    INSERT INTO payments (order_id, invoice_id, amount_paid, payment_status) 
                    VALUES (?, ?, ?, 'Mismatch/Fraud')");
                $stmt->execute([$orderId, $order['invoice_id'], $actualPaidAmount]);

                // Create Admin notification
                // First get admin user ids
                $stmtAdmin = $this->db->prepare("SELECT id FROM users WHERE role = 'admin'");
                $stmtAdmin->execute();
                $admins = $stmtAdmin->fetchAll();
                
                $alertMsg = "FRAUD ALERT: Payment amount mismatch detected on Order #{$order['tracking_number']}. Invoice: $" . number_format($invoiceAmount, 2) . " | Paid: $" . number_format($actualPaidAmount, 2);
                foreach ($admins as $adm) {
                    $stmtNotif = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $stmtNotif->execute([$adm['id'], $alertMsg]);
                }

                $this->db->commit();
                return [
                    "success" => false, 
                    "message" => "Payment amount does not match the invoice amount! A security fraud alert has been sent to Admin.",
                    "fraud" => true
                ];
            } else {
                // Success Payment
                $stmt = $this->db->prepare("UPDATE orders SET payment_status = 'Paid' WHERE id = ?");
                $stmt->execute([$orderId]);

                $stmt = $this->db->prepare("
                    INSERT INTO payments (order_id, invoice_id, amount_paid, payment_status) 
                    VALUES (?, ?, ?, 'Confirmed')");
                $stmt->execute([$orderId, $order['invoice_id'], $actualPaidAmount]);

                // Calculate commission
                $stmt = $this->db->prepare("SELECT commission_pct FROM vendors WHERE user_id = ?");
                $stmt->execute([$order['vendor_id']]);
                $vendor = $stmt->fetch();
                $commPct = floatval($vendor['commission_pct'] ?? 15.00);
                
                $commissionOwed = $actualPaidAmount * ($commPct / 100);
                
                // Add to unpaid commission
                $stmt = $this->db->prepare("UPDATE vendors SET unpaid_commission = unpaid_commission + ? WHERE user_id = ?");
                $stmt->execute([$commissionOwed, $order['vendor_id']]);
                
                // Retrieve total unpaid commission
                $stmt = $this->db->prepare("SELECT unpaid_commission FROM vendors WHERE user_id = ?");
                $stmt->execute([$order['vendor_id']]);
                $vendorUpdated = $stmt->fetch();
                $unpaidComm = floatval($vendorUpdated['unpaid_commission'] ?? 0);

                // Notify Vendor about payment
                $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $vendorMsg = "Payment of Rs " . number_format($actualPaidAmount, 2) . " has been confirmed for Order #{$order['tracking_number']}. Commission of Rs " . number_format($commissionOwed, 2) . " ($commPct%) has been charged.";
                $stmt->execute([$order['vendor_id'], $vendorMsg]);

                // Check if commission limit reached (Rs 1000)
                if ($unpaidComm >= 1000) {
                    // Block the vendor
                    $stmt = $this->db->prepare("UPDATE users SET status = 'blocked' WHERE id = ?");
                    $stmt->execute([$order['vendor_id']]);

                    // Notify vendor about account block
                    $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    $blockMsg = "CRITICAL: Your account has been BLOCKED because your unpaid commission has reached Rs " . number_format($unpaidComm, 2) . " (limit: Rs 1000.00). Please pay your dues to reactivate your account.";
                    $stmt->execute([$order['vendor_id'], $blockMsg]);
                }

                $this->db->commit();
                return ["success" => true, "message" => "Payment confirmed successfully!"];
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
