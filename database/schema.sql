-- Create WashEase Database
CREATE DATABASE IF NOT EXISTS washease;
USE washease;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendor', 'customer') NOT NULL,
    status ENUM('pending', 'active', 'rejected') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Customers Table
CREATE TABLE IF NOT EXISTS customers (
    user_id INT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Vendors Table
CREATE TABLE IF NOT EXISTS vendors (
    user_id INT PRIMARY KEY,
    shop_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    shop_address TEXT NOT NULL,
    district VARCHAR(50) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    reward_level ENUM('Bronze', 'Silver', 'Gold') DEFAULT 'Bronze',
    commission_pct DECIMAL(5,2) DEFAULT 15.00,
    services_offered TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    vendor_id INT NOT NULL,
    pickup_address TEXT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    clothes_weight DECIMAL(5, 2) NOT NULL,
    estimated_weight DECIMAL(5, 2) NULL,
    service_type ENUM('Wash Only', 'Wash & Iron', 'Dry Cleaning', 'Ironing') NOT NULL,
    special_instructions TEXT,
    status ENUM('Pending', 'Accepted', 'Pickup Scheduled', 'Picked Up', 'Washing', 'Ironing', 'Ready', 'Delivered') DEFAULT 'Pending',
    payment_status ENUM('Unpaid', 'Pending Confirmation', 'Paid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(user_id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Invoices Table
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    order_id INT NOT NULL UNIQUE,
    laundry_charges DECIMAL(10, 2) NOT NULL,
    service_charges DECIMAL(10, 2) NOT NULL,
    taxes DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    invoice_id INT NOT NULL,
    amount_paid DECIMAL(10, 2) NOT NULL,
    payment_status ENUM('Pending', 'Confirmed', 'Mismatch/Fraud') DEFAULT 'Pending',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Complaints Table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    category ENUM('Late Delivery', 'Damaged Clothes', 'Missing Item', 'Wrong Billing', 'Other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'Assigned', 'Resolved', 'Closed') DEFAULT 'Pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 9. Fraud Alerts Table
CREATE TABLE IF NOT EXISTS fraud_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    vendor_id INT NOT NULL,
    customer_id INT NOT NULL,
    vendor_amount DECIMAL(10, 2) NOT NULL,
    customer_amount DECIMAL(10, 2) NOT NULL,
    difference DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(user_id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. Reward Levels Table (Configuration / Rules cache)
CREATE TABLE IF NOT EXISTS reward_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_name VARCHAR(20) NOT NULL UNIQUE,
    min_orders INT NOT NULL,
    max_orders INT NOT NULL,
    default_commission_pct DECIMAL(5,2) NOT NULL
) ENGINE=InnoDB;

-- 11. Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 12. OTP Verifications Table
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(6) NOT NULL,
    otp_expiry DATETIME NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 13. Admin Settings Table
CREATE TABLE IF NOT EXISTS admin_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Seed Data
-- Default Admin User (Password is 'admin123' hashed with bcrypt)
INSERT INTO users (username, email, password_hash, role, status) VALUES 
('admin', 'admin@washease.com', '$2y$10$tM9sEw10U34u8.7d9H36cuzm2uRpyqj3P8H9B6l4vYF2JpQ6hH/Hq', 'admin', 'active');

-- Default Reward Levels
INSERT INTO reward_levels (level_name, min_orders, max_orders, default_commission_pct) VALUES
('Bronze', 0, 50, 15.00),
('Silver', 51, 100, 10.00),
('Gold', 101, 99999, 5.00);

-- Default Settings
INSERT INTO admin_settings (setting_key, setting_value) VALUES
('bronze_pct', '15.00'),
('silver_pct', '10.00'),
('gold_pct', '5.00'),
('default_rate_per_kg', '10.00'); -- default rate per kg for orders
