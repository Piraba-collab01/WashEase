<?php
// washease-api/controllers/CustomerController.php

require_once __DIR__ . '/../config/database.php';

class CustomerController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function searchVendors($data) {
        $lat = floatval($data['latitude'] ?? 0);
        $lng = floatval($data['longitude'] ?? 0);
        $district = trim($data['district'] ?? '');

        // If lat/lng are 0, use default coordinates for the city (e.g. central district coordinates)
        if ($lat == 0 && $lng == 0) {
            // Default center coordinates if user hasn't allowed geolocation
            $lat = 6.9271; // Colombo Center
            $lng = 79.8612;
        }

        try {
            // Select vendors and calculate distance using Haversine formula
            $query = "
                SELECT 
                    v.user_id as vendor_id,
                    v.shop_name,
                    v.owner_name,
                    v.contact_number,
                    v.shop_address,
                    v.district,
                    v.opening_time,
                    v.closing_time,
                    v.latitude,
                    v.longitude,
                    v.services_offered,
                    u.email,
                    -- Haversine formula to compute distance in Kilometers
                    (6371 * acos(
                        LEAST(1.0, GREATEST(-1.0, 
                            cos(radians(:lat1)) * cos(radians(v.latitude)) * 
                            cos(radians(v.longitude) - radians(:lng)) + 
                            sin(radians(:lat2)) * sin(radians(v.latitude))
                        ))
                    )) AS distance
                FROM vendors v
                JOIN users u ON v.user_id = u.id
                WHERE u.status = 'active'";

            if (!empty($district)) {
                $query .= " AND v.district LIKE :district";
            }

            $query .= " ORDER BY distance ASC";

            $stmt = $this->db->prepare($query);
            $params = [
                ':lat1' => $lat,
                ':lat2' => $lat,
                ':lng' => $lng
            ];
            if (!empty($district)) {
                $params[':district'] = "%$district%";
            }

            $stmt->execute($params);
            $shops = $stmt->fetchAll();

            // Simulate rating and load services from DB
            foreach ($shops as &$shop) {
                $shop['distance'] = round($shop['distance'], 2); // rounded distance in km
                $shop['rating'] = round(4.0 + (mt_rand(0, 10) / 10), 1); // Mock rating 4.0 - 5.0
                $servicesStr = $shop['services_offered'] ?? '';
                if (!empty($servicesStr)) {
                    $shop['services'] = array_map('trim', explode(',', $servicesStr));
                } else {
                    $shop['services'] = ["Wash Only", "Wash & Iron", "Dry Cleaning", "Ironing"];
                }
            }

            return ["success" => true, "data" => $shops];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error searching shops: " . $e->getMessage()];
        }
    }

    public function getProfile($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id as customer_id, u.username, u.email, c.full_name, c.contact_number, c.address 
                FROM users u
                JOIN customers c ON u.id = c.user_id
                WHERE u.id = ?");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            if (!$profile) {
                return ["success" => false, "message" => "Profile not found."];
            }

            return ["success" => true, "data" => $profile];
        } catch (Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function updateProfile($userId, $data) {
        $email = trim($data['email'] ?? '');
        $phone = trim($data['contact_number'] ?? '');
        $address = trim($data['address'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($phone) || empty($address)) {
            return ["success" => false, "message" => "Email, Phone and Address are required."];
        }

        try {
            $this->db->beginTransaction();

            // Check if email taken by another user
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                throw new Exception("Email is already in use by another user.");
            }

            // Update user table
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

            // Update customer table
            $stmt = $this->db->prepare("UPDATE customers SET contact_number = ?, address = ? WHERE user_id = ?");
            $stmt->execute([$phone, $address, $userId]);

            $this->db->commit();

            return ["success" => true, "message" => "Profile updated successfully."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
