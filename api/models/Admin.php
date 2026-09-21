<?php
// washease-api/models/Admin.php

require_once __DIR__ . '/User.php';

class Admin extends User {
    private $adminLevel;

    // Constructor
    public function __construct($userId = null, $username = "", $email = "", $passwordHash = "", $status = "active", $role = "admin", $phone = "", $adminLevel = "SuperAdmin") {
        parent::__construct($userId, $username, $email, $passwordHash, $status, $role, $phone);
        $this->adminLevel = $adminLevel;
    }

    // Getters and Setters
    public function getAdminLevel() {
        return $this->adminLevel;
    }

    public function setAdminLevel($adminLevel) {
        $this->adminLevel = $adminLevel;
    }

    // Class Methods (Matching UML Class Diagram)
    public function manageUsers() {
        // Activate/deactivate customer or vendor users
        return true;
    }

    public function approveShops() {
        // Approve new vendor profiles
        return true;
    }

    public function generateReports() {
        // Generate analytical metrics (revenue, orders, etc.)
        return [];
    }
}
