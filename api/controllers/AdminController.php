<?php
// washease-api/controllers/AdminController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PdfService.php';

class AdminController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getStats() {
        try {
            $stats = [];
            
            // Total Users
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM users");
            $stats['total_users'] = intval($stmt->fetch()['cnt']);

            // Total Customers
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM customers");
            $stats['total_customers'] = intval($stmt->fetch()['cnt']);

            // Total Vendors
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM vendors");
            $stats['total_vendors'] = intval($stmt->fetch()['cnt']);

            // Total Orders
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM orders");
            $stats['total_orders'] = intval($stmt->fetch()['cnt']);

            // Completed Orders
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM orders WHERE status = 'Delivered'");
            $stats['completed_orders'] = intval($stmt->fetch()['cnt']);

            // Pending Orders
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM orders WHERE status != 'Delivered'");
            $stats['pending_orders'] = intval($stmt->fetch()['cnt']);

            // Revenue (Total of Paid Invoices)
            $stmt = $this->db->query("SELECT SUM(total_amount) as total FROM invoices i JOIN orders o ON i.order_id = o.id WHERE o.payment_status = 'Paid'");
            $stats['revenue'] = floatval($stmt->fetch()['total'] ?? 0);

            // Active Complaints
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM complaints WHERE status != 'Closed'");
            $stats['active_complaints'] = intval($stmt->fetch()['cnt']);

            return ["success" => true, "data" => $stats];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getPendingUsers() {
        try {
            // Fetch users with 'pending' status (who verified OTP but need admin approval)
            $query = "
                SELECT 
                    u.id as user_id, 
                    u.username, 
                    u.email, 
                    u.role, 
                    COALESCE(c.full_name, v.owner_name) as name, 
                    COALESCE(c.contact_number, v.contact_number) as phone,
                    v.shop_name
                FROM users u
                LEFT JOIN customers c ON u.id = c.user_id
                LEFT JOIN vendors v ON u.id = v.user_id
                WHERE u.status = 'pending' AND u.role = 'vendor'"; 
                // Normally only vendors require approval after OTP verification in our workflow.
            
            $stmt = $this->db->query($query);
            $users = $stmt->fetchAll();

            return ["success" => true, "data" => $users];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function approveUser($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);

            // Add notification
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, 'Welcome to WashEase! Your application has been approved by the Admin.')");
            $stmt->execute([$userId]);

            return ["success" => true, "message" => "User approved successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function rejectUser($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$userId]);

            return ["success" => true, "message" => "User registration rejected."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getCommissionRules() {
        try {
            $stmt = $this->db->query("SELECT * FROM admin_settings WHERE setting_key IN ('bronze_pct', 'silver_pct', 'gold_pct')");
            $rules = [];
            while ($row = $stmt->fetch()) {
                $rules[$row['setting_key']] = floatval($row['setting_value']);
            }

            // Fallback default rules if empty
            if (empty($rules)) {
                $rules = [
                    'bronze_pct' => 15.00,
                    'silver_pct' => 10.00,
                    'gold_pct' => 5.00
                ];
            }

            return ["success" => true, "data" => $rules];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateCommissionRules($data) {
        $bronze = floatval($data['bronze_pct'] ?? 15.00);
        $silver = floatval($data['silver_pct'] ?? 10.00);
        $gold = floatval($data['gold_pct'] ?? 5.00);

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('bronze_pct', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$bronze, $bronze]);

            $stmt = $this->db->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('silver_pct', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$silver, $silver]);

            $stmt = $this->db->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('gold_pct', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$gold, $gold]);

            // Sync with reward_levels table too
            $this->db->query("UPDATE reward_levels SET default_commission_pct = $bronze WHERE level_name = 'Bronze'");
            $this->db->query("UPDATE reward_levels SET default_commission_pct = $silver WHERE level_name = 'Silver'");
            $this->db->query("UPDATE reward_levels SET default_commission_pct = $gold WHERE level_name = 'Gold'");

            // Apply immediately to current vendors based on their levels
            $this->db->query("UPDATE vendors SET commission_pct = $bronze WHERE reward_level = 'Bronze'");
            $this->db->query("UPDATE vendors SET commission_pct = $silver WHERE reward_level = 'Silver'");
            $this->db->query("UPDATE vendors SET commission_pct = $gold WHERE reward_level = 'Gold'");

            $this->db->commit();
            return ["success" => true, "message" => "Commission rules updated successfully."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getFraudAlerts() {
        try {
            $query = "
                SELECT 
                    fa.*, 
                    o.tracking_number,
                    v.shop_name, 
                    c.full_name as customer_name
                FROM fraud_alerts fa
                JOIN orders o ON fa.order_id = o.id
                JOIN vendors v ON fa.vendor_id = v.user_id
                JOIN customers c ON fa.customer_id = c.user_id
                ORDER BY fa.created_at DESC";
            
            $stmt = $this->db->query($query);
            $alerts = $stmt->fetchAll();

            return ["success" => true, "data" => $alerts];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function resolveFraudAlert($alertId) {
        try {
            $stmt = $this->db->prepare("UPDATE fraud_alerts SET status = 'Resolved' WHERE id = ?");
            $stmt->execute([$alertId]);
            return ["success" => true, "message" => "Fraud alert marked as resolved."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getAdminReports($data) {
        $reportType = $data['type'] ?? 'revenue'; // revenue, vendor, complaints, customer
        $export = isset($data['export']) && $data['export'] === 'pdf';

        try {
            $title = "";
            $headers = [];
            $tableData = [];

            if ($reportType === 'revenue') {
                $title = "Revenue Report (Payments Summary)";
                $headers = ["Invoice #", "Order Tracking", "Vendor", "Customer", "Paid Date", "Amount"];
                
                $query = "
                    SELECT i.invoice_number, o.tracking_number, v.shop_name, c.full_name, p.paid_at, p.amount_paid
                    FROM payments p
                    JOIN invoices i ON p.invoice_id = i.id
                    JOIN orders o ON p.order_id = o.id
                    JOIN vendors v ON o.vendor_id = v.user_id
                    JOIN customers c ON o.customer_id = c.user_id
                    WHERE p.payment_status = 'Confirmed'
                    ORDER BY p.paid_at DESC";
                
                $stmt = $this->db->query($query);
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $tableData[] = [
                        $row['invoice_number'],
                        $row['tracking_number'],
                        $row['shop_name'],
                        $row['full_name'],
                        $row['paid_at'],
                        "$" . number_format($row['amount_paid'], 2)
                    ];
                }
            } else if ($reportType === 'vendor') {
                $title = "Vendor Performance Report";
                $headers = ["Vendor ID", "Shop Name", "Reward Level", "Commission %", "Total Orders", "Completed", "Total Earned"];
                
                $query = "
                    SELECT 
                        v.user_id, v.shop_name, v.reward_level, v.commission_pct,
                        COUNT(o.id) as total_orders,
                        SUM(CASE WHEN o.status = 'Delivered' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(CASE WHEN o.payment_status = 'Paid' THEN i.total_amount ELSE 0 END) as total_earned
                    FROM vendors v
                    LEFT JOIN orders o ON v.user_id = o.vendor_id
                    LEFT JOIN invoices i ON o.id = i.order_id
                    GROUP BY v.user_id";
                
                $stmt = $this->db->query($query);
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $tableData[] = [
                        $row['user_id'],
                        $row['shop_name'],
                        $row['reward_level'],
                        $row['commission_pct'] . "%",
                        $row['total_orders'],
                        $row['completed_orders'],
                        "$" . number_format($row['total_earned'] ?? 0, 2)
                    ];
                }
            } else if ($reportType === 'complaints') {
                $title = "Complaints Summary Report";
                $headers = ["Complaint ID", "Customer", "Order Tracking", "Category", "Date Filed", "Status"];
                
                $query = "
                    SELECT cmp.id, c.full_name, o.tracking_number, cmp.category, cmp.created_at, cmp.status
                    FROM complaints cmp
                    JOIN customers c ON cmp.customer_id = c.user_id
                    JOIN orders o ON cmp.order_id = o.id
                    ORDER BY cmp.created_at DESC";
                
                $stmt = $this->db->query($query);
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $tableData[] = [
                        $row['id'],
                        $row['full_name'],
                        $row['tracking_number'],
                        $row['category'],
                        $row['created_at'],
                        $row['status']
                    ];
                }
            } else if ($reportType === 'customer') {
                $title = "Customer Activity Report";
                $headers = ["Customer ID", "Name", "Email", "Phone", "Orders Placed", "Total Spent"];
                
                $query = "
                    SELECT 
                        c.user_id, c.full_name, u.email, c.contact_number,
                        COUNT(o.id) as orders_count,
                        SUM(CASE WHEN o.payment_status = 'Paid' THEN i.total_amount ELSE 0 END) as total_spent
                    FROM customers c
                    JOIN users u ON c.user_id = u.id
                    LEFT JOIN orders o ON c.user_id = o.customer_id
                    LEFT JOIN invoices i ON o.id = i.order_id
                    GROUP BY c.user_id";
                
                $stmt = $this->db->query($query);
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    $tableData[] = [
                        $row['user_id'],
                        $row['full_name'],
                        $row['email'],
                        $row['contact_number'],
                        $row['orders_count'],
                        "$" . number_format($row['total_spent'] ?? 0, 2)
                    ];
                }
            }

            if ($export) {
                $pdfContent = PdfService::generateReportPDF($title, $headers, $tableData);
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="admin_report_' . $reportType . '.pdf"');
                echo $pdfContent;
                exit;
            }

            return [
                "success" => true,
                "data" => [
                    "title" => $title,
                    "headers" => $headers,
                    "rows" => $tableData
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getAllVendors() {
        try {
            $query = "
                SELECT 
                    u.id as vendor_id, 
                    u.username, 
                    u.email, 
                    u.status, 
                    v.shop_name, 
                    v.owner_name, 
                    v.contact_number, 
                    v.reward_level, 
                    v.commission_pct,
                    v.unpaid_commission
                FROM users u
                JOIN vendors v ON u.id = v.user_id
                ORDER BY v.unpaid_commission DESC, u.created_at DESC";
            
            $stmt = $this->db->query($query);
            $vendors = $stmt->fetchAll();

            return ["success" => true, "data" => $vendors];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateVendorStatus($data) {
        $vendorId = intval($data['vendor_id'] ?? 0);
        $status = $data['status'] ?? ''; // active, inactive, blocked
        $resetCommission = isset($data['reset_commission']) && $data['reset_commission'] === true;

        if ($vendorId <= 0 || !in_array($status, ['active', 'inactive', 'blocked'])) {
            return ["success" => false, "message" => "Invalid vendor ID or status."];
        }

        try {
            $this->db->beginTransaction();

            // Update user status
            $stmt = $this->db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $vendorId]);

            // Reset commission if requested
            if ($resetCommission) {
                $stmt = $this->db->prepare("UPDATE vendors SET unpaid_commission = 0.00 WHERE user_id = ?");
                $stmt->execute([$vendorId]);
            }

            // Create notification for vendor
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $msg = "Your account status has been updated to: " . strtoupper($status) . ".";
            if ($resetCommission) {
                $msg .= " Your unpaid commission has been cleared/reset by Admin.";
            }
            $stmt->execute([$vendorId, $msg]);

            $this->db->commit();
            return ["success" => true, "message" => "Vendor status updated successfully to $status."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function sendVendorWarning($data) {
        $vendorId = intval($data['vendor_id'] ?? 0);
        $message = trim($data['message'] ?? '');

        if ($vendorId <= 0 || empty($message)) {
            return ["success" => false, "message" => "Vendor ID and message are required."];
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmt->execute([$vendorId, "ADMIN WARNING: " . $message]);

            return ["success" => true, "message" => "Warning message sent to vendor successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function listCommissionPayments() {
        try {
            $stmt = $this->db->query("
                SELECT cp.id as payment_id, cp.amount, cp.transaction_ref, cp.payment_date, cp.status as payment_status, v.shop_name, v.owner_name, v.user_id as vendor_id
                FROM commission_payments cp
                JOIN vendors v ON cp.vendor_id = v.user_id
                ORDER BY cp.payment_date DESC
            ");
            $payments = $stmt->fetchAll();
            return ["success" => true, "data" => $payments];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function approveCommissionPayment($data) {
        $paymentId = intval($data['payment_id'] ?? 0);
        if ($paymentId <= 0) {
            return ["success" => false, "message" => "Payment ID is required."];
        }

        try {
            $this->db->beginTransaction();

            // Fetch payment details
            $stmt = $this->db->prepare("SELECT vendor_id, amount, transaction_ref, status FROM commission_payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                throw new Exception("Payment record not found.");
            }

            if ($payment['status'] !== 'Pending') {
                throw new Exception("Payment has already been processed.");
            }

            $vendorId = $payment['vendor_id'];
            $amount = floatval($payment['amount']);

            // Update status
            $stmt = $this->db->prepare("UPDATE commission_payments SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$paymentId]);

            // Deduct from vendor's unpaid_commission
            $stmt = $this->db->prepare("UPDATE vendors SET unpaid_commission = GREATEST(0.00, unpaid_commission - ?) WHERE user_id = ?");
            $stmt->execute([$amount, $vendorId]);

            // Fetch updated unpaid commission
            $stmt = $this->db->prepare("SELECT unpaid_commission FROM vendors WHERE user_id = ?");
            $stmt->execute([$vendorId]);
            $vendor = $stmt->fetch();
            $newUnpaid = floatval($vendor['unpaid_commission'] ?? 0.00);

            // Automatically unblock if unpaid commission is now below 1000
            if ($newUnpaid < 1000) {
                // Check if user status is blocked, then change to active
                $stmtUser = $this->db->prepare("SELECT status FROM users WHERE id = ?");
                $stmtUser->execute([$vendorId]);
                $userStatus = $stmtUser->fetchColumn();
                if ($userStatus === 'blocked') {
                    $stmtUpdateStatus = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmtUpdateStatus->execute([$vendorId]);
                }
            }

            // Create notification for vendor
            $msg = "Your commission payment submission of Rs " . number_format($amount, 2) . " (Ref: {$payment['transaction_ref']}) was APPROVED. Your current outstanding balance is Rs " . number_format($newUnpaid, 2) . ".";
            $stmtNotify = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmtNotify->execute([$vendorId, $msg]);

            $this->db->commit();
            return ["success" => true, "message" => "Payment submission approved and vendor balance updated."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function rejectCommissionPayment($data) {
        $paymentId = intval($data['payment_id'] ?? 0);
        $reason = trim($data['reason'] ?? 'Invalid details or receipt.');

        if ($paymentId <= 0) {
            return ["success" => false, "message" => "Payment ID is required."];
        }

        try {
            $this->db->beginTransaction();

            // Fetch payment details
            $stmt = $this->db->prepare("SELECT vendor_id, amount, transaction_ref, status FROM commission_payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                throw new Exception("Payment record not found.");
            }

            if ($payment['status'] !== 'Pending') {
                throw new Exception("Payment has already been processed.");
            }

            $vendorId = $payment['vendor_id'];

            // Update status
            $stmt = $this->db->prepare("UPDATE commission_payments SET status = 'Rejected' WHERE id = ?");
            $stmt->execute([$paymentId]);

            // Create notification for vendor
            $msg = "Your commission payment submission of Rs " . number_format(floatval($payment['amount']), 2) . " (Ref: {$payment['transaction_ref']}) was REJECTED. Reason: " . $reason;
            $stmtNotify = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            $stmtNotify->execute([$vendorId, $msg]);

            $this->db->commit();
            return ["success" => true, "message" => "Payment submission rejected."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
