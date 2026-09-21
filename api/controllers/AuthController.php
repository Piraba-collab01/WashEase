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

        // Check if username/email already exists in users table
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ["success" => false, "message" => "Username or Email already registered."];
        }

        try {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(100000, 999999));
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store registration payload in otp_verifications WITHOUT creating users/customers/vendors rows yet
            $stmt = $this->db->prepare("INSERT INTO otp_verifications (email, otp, otp_expiry, is_verified, registration_data) VALUES (?, ?, ?, 0, ?)");
            $stmt->execute([$email, $otp, $expiry, json_encode($data)]);

            // Send OTP Email
            $sent = MailService::sendOTP($email, $otp);

            if (!$sent) {
                return [
                    "success" => false, 
                    "message" => "Failed to send OTP email to $email. Please check SMTP configuration or email address and try again."
                ];
            }

            return [
                "success" => true, 
                "message" => "Registration submitted. An OTP has been sent to your email ($email). Please check your inbox to complete registration.",
                "email" => $email
            ];

        } catch (Exception $e) {
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
        $stmt = $this->db->prepare("SELECT id, registration_data FROM otp_verifications WHERE email = ? AND otp = ? AND otp_expiry > ? AND is_verified = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$email, $otp, $now]);
        $verification = $stmt->fetch();

        if (!$verification) {
            return ["success" => false, "message" => "Invalid or expired OTP."];
        }

        try {
            $this->db->beginTransaction();

            // Mark OTP as verified
            $stmt = $this->db->prepare("UPDATE otp_verifications SET is_verified = 1 WHERE id = ?");
            $stmt->execute([$verification['id']]);

            // Check if user is already created in users table
            $stmtUser = $this->db->prepare("SELECT id, role, status FROM users WHERE email = ?");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if (!$user) {
                $regData = json_decode($verification['registration_data'] ?? '{}', true);
                if (empty($regData) || !isset($regData['username'])) {
                    throw new Exception("Registration data not found for this OTP.");
                }

                $username = trim($regData['username']);
                $password = $regData['password'];
                $role = $regData['role'] ?? 'customer';
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $status = ($role === 'customer') ? 'active' : 'pending';

                // NOW insert into users table ONLY after OTP is confirmed
                $stmtInstUser = $this->db->prepare("INSERT INTO users (username, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmtInstUser->execute([$username, $email, $password_hash, $role, $status]);
                $userId = $this->db->lastInsertId();

                if ($role === 'customer') {
                    $fullName = trim($regData['full_name'] ?? '');
                    $phone = trim($regData['contact_number'] ?? '');
                    $address = trim($regData['address'] ?? '');

                    $stmtCust = $this->db->prepare("INSERT INTO customers (user_id, full_name, contact_number, address) VALUES (?, ?, ?, ?)");
                    $stmtCust->execute([$userId, $fullName, $phone, $address]);

                    $stmtNotif = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, 'Welcome to WashEase! Your account is active.')");
                    $stmtNotif->execute([$userId]);

                    $this->db->commit();
                    return ["success" => true, "message" => "OTP verified successfully! Your customer account is now active. Please sign in."];

                } else if ($role === 'vendor') {
                    $shopName = trim($regData['shop_name'] ?? '');
                    $ownerName = trim($regData['owner_name'] ?? '');
                    $phone = trim($regData['contact_number'] ?? '');
                    $shopAddress = trim($regData['shop_address'] ?? '');
                    $district = trim($regData['district'] ?? '');
                    $lat = floatval($regData['latitude'] ?? 0);
                    $lng = floatval($regData['longitude'] ?? 0);
                    $openTime = $regData['opening_time'] ?? '08:00';
                    $closeTime = $regData['closing_time'] ?? '20:00';

                    $stmtVend = $this->db->prepare("INSERT INTO vendors (user_id, shop_name, owner_name, contact_number, shop_address, district, latitude, longitude, opening_time, closing_time, services_offered) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Wash Only,Wash & Iron,Dry Cleaning,Ironing')");
                    $stmtVend->execute([$userId, $shopName, $ownerName, $phone, $shopAddress, $district, $lat, $lng, $openTime, $closeTime]);

                    // Send notification to all admin users
                    $stmtAdmin = $this->db->query("SELECT id FROM users WHERE role = 'admin'");
                    $admins = $stmtAdmin->fetchAll();

                    $notifMsg = "New vendor shop registration request: '$shopName' (Owner: $ownerName) has verified OTP and is pending your approval.";
                    $stmtN = $this->db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                    foreach ($admins as $adm) {
                        $stmtN->execute([$adm['id'], $notifMsg]);
                    }

                    $this->db->commit();
                    return ["success" => true, "message" => "OTP verified successfully! Your vendor account is now pending admin approval."];
                }
            } else {
                $this->db->commit();
                if ($user['role'] === 'customer') {
                    return ["success" => true, "message" => "OTP verified successfully. Your account is active!"];
                } else {
                    return ["success" => true, "message" => "OTP verified. Your vendor account is pending admin approval."];
                }
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
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
            // Check if OTP was verified for this user's email
            $stmtOtp = $this->db->prepare("SELECT id FROM otp_verifications WHERE email = ? AND is_verified = 1");
            $stmtOtp->execute([$user['email']]);
            $isOtpVerified = $stmtOtp->fetch();

            if (!$isOtpVerified) {
                // Generate a fresh OTP for unverified account
                $email = $user['email'];
                $otp = sprintf("%06d", mt_rand(100000, 999999));
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $stmtInsert = $this->db->prepare("INSERT INTO otp_verifications (email, otp, otp_expiry) VALUES (?, ?, ?)");
                $stmtInsert->execute([$email, $otp, $expiry]);

                $sent = MailService::sendOTP($email, $otp);

                if (!$sent) {
                    return [
                        "success" => false,
                        "message" => "Failed to send OTP email to $email. Please check SMTP configuration or try again later."
                    ];
                }

                return [
                    "success" => false,
                    "needs_verification" => true,
                    "email" => $email,
                    "message" => "Your account email is not verified yet. An OTP has been sent to your email ($email)."
                ];
            }

            if ($user['role'] === 'vendor') {
                return ["success" => false, "message" => "Your OTP is verified. Your vendor account is currently pending admin approval."];
            }
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

        $sent = MailService::sendOTP($email, $otp);

        if (!$sent) {
            return ["success" => false, "message" => "Failed to send password reset OTP to $email. Please check SMTP configuration."];
        }

        return ["success" => true, "message" => "An OTP has been sent to your email ($email)."];
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
