<?php
// washease-api/controllers/ComplaintController.php

require_once __DIR__ . '/../config/database.php';

class ComplaintController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function submitComplaint($customerId, $data) {
        $orderId = intval($data['order_id'] ?? 0);
        $category = $data['category'] ?? '';
        $description = trim($data['description'] ?? '');

        $validCategories = ['Late Delivery', 'Damaged Clothes', 'Missing Item', 'Wrong Billing', 'Other'];

        if ($orderId <= 0 || !in_array($category, $validCategories) || empty($description)) {
            return ["success" => false, "message" => "Please provide a valid order, category, and description."];
        }

        try {
            // Verify order belongs to customer
            $stmt = $this->db->prepare("SELECT id FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$orderId, $customerId]);
            if (!$stmt->fetch()) {
                return ["success" => false, "message" => "Order not found or access denied."];
            }

            $stmt = $this->db->prepare("
                INSERT INTO complaints (order_id, customer_id, category, description, status) 
                VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->execute([$orderId, $customerId, $category, $description]);

            return ["success" => true, "message" => "Complaint submitted successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function listComplaints($userId, $role) {
        try {
            if ($role === 'customer') {
                $stmt = $this->db->prepare("
                    SELECT cmp.*, o.tracking_number 
                    FROM complaints cmp
                    JOIN orders o ON cmp.order_id = o.id
                    WHERE cmp.customer_id = ? 
                    ORDER BY cmp.created_at DESC");
                $stmt->execute([$userId]);
                $data = $stmt->fetchAll();
            } else if ($role === 'vendor') {
                $stmt = $this->db->prepare("
                    SELECT cmp.*, o.tracking_number, c.full_name as customer_name
                    FROM complaints cmp
                    JOIN orders o ON cmp.order_id = o.id
                    JOIN customers c ON cmp.customer_id = c.user_id
                    WHERE o.vendor_id = ? 
                    ORDER BY cmp.created_at DESC");
                $stmt->execute([$userId]);
                $data = $stmt->fetchAll();
            } else if ($role === 'admin') {
                $stmt = $this->db->prepare("
                    SELECT cmp.*, o.tracking_number, c.full_name as customer_name, v.shop_name as vendor_name
                    FROM complaints cmp
                    JOIN orders o ON cmp.order_id = o.id
                    JOIN customers c ON cmp.customer_id = c.user_id
                    JOIN vendors v ON o.vendor_id = v.user_id
                    ORDER BY cmp.created_at DESC");
                $stmt->execute();
                $data = $stmt->fetchAll();
            } else {
                return ["success" => false, "message" => "Invalid role."];
            }

            return ["success" => true, "data" => $data];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function assignComplaint($complaintId) {
        try {
            $stmt = $this->db->prepare("UPDATE complaints SET status = 'Assigned' WHERE id = ?");
            $stmt->execute([$complaintId]);
            return ["success" => true, "message" => "Complaint assigned successfully."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function resolveComplaint($complaintId, $data) {
        $adminResponse = trim($data['admin_response'] ?? '');

        if (empty($adminResponse)) {
            return ["success" => false, "message" => "Please enter a resolution response."];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE complaints SET status = 'Resolved', admin_response = ? WHERE id = ?");
            $stmt->execute([$adminResponse, $complaintId]);

            // Get customer ID of this complaint
            $stmt = $this->db->prepare("SELECT customer_id FROM complaints WHERE id = ?");
            $stmt->execute([$complaintId]);
            $comp = $stmt->fetch();

            if ($comp) {
                // Send notification
                $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $msg = "Your complaint #$complaintId has been resolved. Response: $adminResponse";
                $stmt->execute([$comp['customer_id'], $msg]);
            }

            $this->db->commit();
            return ["success" => true, "message" => "Complaint resolved and customer notified."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function closeComplaint($complaintId) {
        try {
            $stmt = $this->db->prepare("UPDATE complaints SET status = 'Closed' WHERE id = ?");
            $stmt->execute([$complaintId]);
            return ["success" => true, "message" => "Complaint closed."];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
