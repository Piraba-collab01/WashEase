<?php
// washease-api/models/Admin.php

require_once __DIR__ . '/User.php';

class Admin extends User {
    private $adminLevel;

    // Constructor
    public function __construct($userId = null, $name = "", $email = "", $passwordHash = "", $phone = "", $role = "admin", $adminLevel = 1) {
        parent::__construct($userId, $name, $email, $passwordHash, $phone, $role);
        $this->adminLevel = $adminLevel;
    }

    // Getters and Setters
    public function getAdminLevel() {
        return $this->adminLevel;
    }

    public function setAdminLevel($adminLevel) {
        $this->adminLevel = $adminLevel;
    }

    // Class Methods
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
