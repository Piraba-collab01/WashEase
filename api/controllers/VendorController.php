<?php
// washease-api/controllers/VendorController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/PdfService.php';

class VendorController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getProfile($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id as vendor_id, u.username, u.email, v.shop_name, v.owner_name, v.contact_number, v.shop_address, v.district, v.latitude, v.longitude, v.opening_time, v.closing_time, v.reward_level, v.commission_pct, v.unpaid_commission
                FROM users u
                JOIN vendors v ON u.id = v.user_id
                WHERE u.id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            if (!$profile) {
                return ["success" => false, "message" => "Vendor profile not found."];
            }

            return ["success" => true, "data" => $profile];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateProfile($userId, $data) {
        $email = trim($data['email'] ?? '');
        $phone = trim($data['contact_number'] ?? '');
        $shopAddress = trim($data['shop_address'] ?? '');
        $shopName = trim($data['shop_name'] ?? '');
        $ownerName = trim($data['owner_name'] ?? '');
        $openTime = $data['opening_time'] ?? '08:00';
        $closeTime = $data['closing_time'] ?? '20:00';
        $password = $data['password'] ?? '';

        if (empty($email) || empty($phone) || empty($shopAddress) || empty($shopName) || empty($ownerName)) {
            return ["success" => false, "message" => "Email, Phone, Shop Name, Owner Name, and Shop Address are required."];
        }

        try {
            $this->db->beginTransaction();

            // Check email uniqueness
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception("Email is already in use by another user.");
            }

            if (!empty($password)) {
                if (strlen($password) < 6) {
                    throw new Exception("Password must be at least 6 characters.");
                }
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $this->db->prepare("UPDATE users SET email = ?, password_hash = ? WHERE id = ?");
                $stmt->execute([$email, $password_hash, $userId]);
            } else {
                $stmt = $this->db->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $userId]);
            }

            // Update vendor
            $stmt = $this->db->prepare("
                UPDATE vendors 
                SET shop_name = ?, owner_name = ?, contact_number = ?, shop_address = ?, opening_time = ?, closing_time = ?
                WHERE user_id = ?");
            $stmt->execute([$shopName, $ownerName, $phone, $shopAddress, $openTime, $closeTime, $userId]);

            $this->db->commit();

            return ["success" => true, "message" => "Profile updated successfully."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getRewardStats($userId) {
        try {
            // Count total completed orders
            $stmt = $this->db->prepare("SELECT COUNT(id) as total_completed FROM orders WHERE vendor_id = ? AND status = 'Delivered'");
            $stmt->execute([$userId]);
            $res = $stmt->fetch();
            $completedOrders = intval($res['total_completed'] ?? 0);

            // Get reward level details
            $stmt = $this->db->prepare("SELECT reward_level, commission_pct, unpaid_commission FROM vendors WHERE user_id = ?");
            $stmt->execute([$userId]);
            $vendor = $stmt->fetch();

            $currentLevel = $vendor['reward_level'] ?? 'Bronze';
            $currentCommission = floatval($vendor['commission_pct'] ?? 15.00);
            $unpaidCommission = floatval($vendor['unpaid_commission'] ?? 0.00);

            // Calculate progress to next level
            $nextLevel = 'Gold';
            $targetOrders = 101;
            $progress = 100;

            if ($completedOrders <= 50) {
                $nextLevel = 'Silver';
                $targetOrders = 51;
                $progress = round(($completedOrders / 50) * 100);
            } else if ($completedOrders <= 100) {
                $nextLevel = 'Gold';
                $targetOrders = 101;
                $progress = round((($completedOrders - 50) / 50) * 100);
            }

            return [
                "success" => true,
                "data" => [
                    "completed_orders" => $completedOrders,
                    "current_level" => $currentLevel,
                    "current_commission" => $currentCommission,
                    "unpaid_commission" => $unpaidCommission,
                    "next_level" => $nextLevel,
                    "target_orders" => $targetOrders,
                    "progress" => min(100, $progress)
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function getReports($userId, $data) {
        $period = $data['period'] ?? 'daily'; // daily, weekly, monthly
        $export = isset($data['export']) && $data['export'] === 'pdf';

        try {
            $dateConstraint = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
            $title = "Daily Sales Report";
            if ($period === 'weekly') {
                $dateConstraint = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $title = "Weekly Sales Report";
            } else if ($period === 'monthly') {
                $dateConstraint = "AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $title = "Monthly Sales Report";
            }

            $query = "
                SELECT 
                    o.id as order_id, 
                    o.tracking_number, 
                    c.full_name as customer_name, 
                    o.service_type, 
                    o.clothes_weight, 
                    o.status, 
                    i.total_amount, 
                    i.created_at as invoice_date
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.user_id
                LEFT JOIN invoices i ON o.id = i.order_id
                WHERE o.vendor_id = :vendor_id $dateConstraint
                ORDER BY o.created_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute([':vendor_id' => $userId]);
            $reportData = $stmt->fetchAll();

            $totalRevenue = 0;
            $orderCount = count($reportData);
            $tableData = [];

            foreach ($reportData as $row) {
                $amt = $row['total_amount'] ?? 0;
                $totalRevenue += $amt;
                $tableData[] = [
                    $row['tracking_number'],
                    $row['customer_name'] ?? 'N/A',
                    $row['service_type'],
                    $row['clothes_weight'] . " kg",
                    $row['status'],
                    "$" . number_format($amt, 2)
                ];
            }

            if ($export) {
                $headers = ["Tracking #", "Customer", "Service", "Weight", "Status", "Amount"];
                $titleExtended = $title . " - Total Revenue: $" . number_format($totalRevenue, 2) . " ($orderCount Orders)";
                $pdfContent = PdfService::generateReportPDF($titleExtended, $headers, $tableData);
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="report_' . $period . '.pdf"');
                echo $pdfContent;
                exit;
            }

            return [
                "success" => true,
                "data" => [
                    "orders" => $reportData,
                    "summary" => [
                        "total_revenue" => $totalRevenue,
                        "total_orders" => $orderCount,
                        "period" => $period
                    ]
                ]
            ];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Report generation failed: " . $e->getMessage()];
        }
    }

    public function submitCommissionPayment($userId, $data) {
        $amount = floatval($data['amount'] ?? 0);
        $transactionRef = trim($data['transaction_ref'] ?? '');

        if ($amount <= 0 || empty($transactionRef)) {
            return ["success" => false, "message" => "Amount and transaction reference are required."];
        }

        try {
            $this->db->beginTransaction();

            // Fetch vendor shop name
            $stmt = $this->db->prepare("SELECT shop_name FROM vendors WHERE user_id = ?");
            $stmt->execute([$userId]);
            $vendor = $stmt->fetch();
            $shopName = $vendor['shop_name'] ?? "Vendor #$userId";

            // Insert payment submission
            $stmt = $this->db->prepare("
                INSERT INTO commission_payments (vendor_id, amount, transaction_ref, status) 
                VALUES (?, ?, ?, 'Pending')
            ");
            $stmt->execute([$userId, $amount, $transactionRef]);

            // Notify all administrators
            $stmtAdmins = $this->db->query("SELECT id FROM users WHERE role = 'admin'");
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);
            
            $msg = "Vendor '$shopName' has submitted a commission payment of Rs " . number_format($amount, 2) . " (Ref: $transactionRef) for approval.";
            $stmtNotify = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
            foreach ($admins as $adminId) {
                $stmtNotify->execute([$adminId, $msg]);
            }

            $this->db->commit();
            return ["success" => true, "message" => "Commission payment submitted to admin for confirmation."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
