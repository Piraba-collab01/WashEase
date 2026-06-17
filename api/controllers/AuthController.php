<?php
// washease-api/controllers/AuthController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/MailService.php';

class AuthController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function register($data) {
        // Validation
        $role = $data['role'] ?? '';
        if (!in_array($role, ['customer', 'vendor'])) {
            return ["success" => false, "message" => "Invalid role selected."];
        }

        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirm_password = $data['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            return ["success" => false, "message" => "Please fill in all required fields."];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ["success" => false, "message" => "Invalid email format."];
        }

        if ($password !== $confirm_password) {
            return ["success" => false, "message" => "Passwords do not match."];
        }

        if (strlen($password) < 6) {
            return ["success" => false, "message" => "Password must be at least 6 characters."];
        }

        // Check if username/email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ["success" => false, "message" => "Username or Email already registered."];
        }

        try {
            $this->db->beginTransaction();

            // Insert into users table
            // Set status to pending. Once OTP is verified, customer becomes active, vendor becomes pending admin approval.
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$username, $email, $password_hash, $role]);
            $userId = $this->db->lastInsertId();

            if ($role === 'customer') {
                $fullName = trim($data['full_name'] ?? '');
                $phone = trim($data['contact_number'] ?? '');
                $address = trim($data['address'] ?? '');

                if (empty($fullName) || empty($phone) || empty($address)) {
                    throw new Exception("Please fill in all customer fields.");
                }

                $stmt = $this->db->prepare("INSERT INTO customers (user_id, full_name, contact_number, address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $fullName, $phone, $address]);

            } else if ($role === 'vendor') {
                $shopName = trim($data['shop_name'] ?? '');
                $ownerName = trim($data['owner_name'] ?? '');
                $phone = trim($data['contact_number'] ?? '');
                $shopAddress = trim($data['shop_address'] ?? '');
                $district = trim($data['district'] ?? '');
                $lat = floatval($data['latitude'] ?? 0);
                $lng = floatval($data['longitude'] ?? 0);
                $openTime = $data['opening_time'] ?? '08:00';
                $closeTime = $data['closing_time'] ?? '20:00';

                if (empty($shopName) || empty($ownerName) || empty($phone) || empty($shopAddress) || empty($district)) {
                    throw new Exception("Please fill in all vendor fields.");
                }

                $stmt = $this->db->prepare("INSERT INTO vendors (user_id, shop_name, owner_name, contact_number, shop_address, district, latitude, longitude, opening_time, closing_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $shopName, $ownerName, $phone, $shopAddress, $district, $lat, $lng, $openTime, $closeTime]);
            }

            // Generate OTP
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            $stmt = $this->db->prepare("INSERT INTO otp_verifications (email, otp, otp_expiry) VALUES (?, ?, ?)");
            $stmt->execute([$email, $otp, $expiry]);

            // Commit transaction
            $this->db->commit();

            // Send OTP Email (ignores SMTP issues and logs locally as fallback)
            MailService::sendOTP($email, $otp);

            return [
                "success" => true, 
                "message" => "Registration successful. An OTP has been sent to your email.",
                "email" => $email
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    public function verifyOTP($data) {
        $email = trim($data['email'] ?? '');
        $otp = trim($data['otp'] ?? '');

        if (empty($email) || empty($otp)) {
            return ["success" => false, "message" => "Email and OTP are required."];
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT id FROM otp_verifications WHERE email = ? AND otp = ? AND otp_expiry > ? AND is_verified = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp, $now]);
        $verification = $stmt->fetch();

        if (!$verification) {
            return ["success" => false, "message" => "Invalid or expired OTP."];
        }

        // Mark OTP as verified
        $stmt = $this->db->prepare("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$verification['id']]);

        // Get user details
        $stmt = $this->db->prepare("SELECT id, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['role'] === 'customer') {
                // Activate customer immediately
                $stmt = $this->db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Add welcome notification
                $stmt = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, 'Welcome to WashEase! Your account is active.')");
                $stmt->execute([$user['id']]);

                return ["success" => true, "message" => "OTP verified successfully. Your account is now active!"];
            } else {
                // Vendors must be approved by Admin after OTP is verified
                return ["success" => true, "message" => "OTP verified. Your account is pending admin approval."];
            }
        }

        return ["success" => false, "message" => "User not found."];
    }

    public function login($data) {
        $usernameOrEmail = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            return ["success" => false, "message" => "Please enter username/email and password."];
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ["success" => false, "message" => "Invalid username/email or password."];
        }

        if ($user['status'] === 'pending') {
            if ($user['role'] === 'vendor') {
                return ["success" => false, "message" => "Your vendor account is pending admin approval."];
            }
            return ["success" => false, "message" => "Your account is pending verification. Please verify your OTP."];
        }

        if ($user['status'] === 'rejected') {
            return ["success" => false, "message" => "Your account registration has been rejected by Admin."];
        }

        if ($user['status'] === 'blocked') {
            return ["success" => false, "message" => "Your account has been blocked due to unpaid commission. Please contact Admin."];
        }

        if ($user['status'] === 'inactive') {
            return ["success" => false, "message" => "Your account is currently inactive/deactivated. Please contact Admin."];
        }

        // Setup session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Get additional details based on role
        $details = [];
        if ($user['role'] === 'customer') {
            $stmt = $this->db->prepare("SELECT * FROM customers WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $details = $stmt->fetch();
        } else if ($user['role'] === 'vendor') {
            $stmt = $this->db->prepare("SELECT * FROM vendors WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $details = $stmt->fetch();
        }

        return [
            "success" => true,
            "message" => "Login successful.",
            "user" => [
                "id" => $user['id'],
                "username" => $user['username'],
                "email" => $user['email'],
                "role" => $user['role'],
                "details" => $details
            ]
        ];
    }

    public function forgotPassword($data) {
        $email = trim($data['email'] ?? '');

        if (empty($email)) {
            return ["success" => false, "message" => "Email is required."];
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            return ["success" => false, "message" => "No account found with this email."];
        }

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $stmt = $this->db->prepare("INSERT INTO otp_verifications (email, otp, otp_expiry) VALUES (?, ?, ?)");
        $stmt->execute([$email, $otp, $expiry]);

        MailService::sendOTP($email, $otp);

        return ["success" => true, "message" => "OTP sent to your email."];
    }

    public function resetPassword($data) {
        $email = trim($data['email'] ?? '');
        $otp = trim($data['otp'] ?? '');
        $newPassword = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($email) || empty($otp) || empty($newPassword)) {
            return ["success" => false, "message" => "All fields are required."];
        }

        if ($newPassword !== $confirmPassword) {
            return ["success" => false, "message" => "Passwords do not match."];
        }

        if (strlen($newPassword) < 6) {
            return ["success" => false, "message" => "Password must be at least 6 characters."];
        }

        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT id FROM otp_verifications WHERE email = ? AND otp = ? AND otp_expiry > ? AND is_verified = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp, $now]);
        $verification = $stmt->fetch();

        if (!$verification) {
            return ["success" => false, "message" => "Invalid or expired OTP."];
        }

        // Mark OTP verified
        $stmt = $this->db->prepare("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$verification['id']]);

        // Update password
        $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$password_hash, $email]);

        return ["success" => true, "message" => "Password updated successfully. You can now login."];
    }

    public function logout() {
        session_destroy();
        return ["success" => true, "message" => "Logged out successfully."];
    }
}
