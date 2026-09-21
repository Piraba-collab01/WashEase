<?php
// washease-api/models/Vendor.php

require_once __DIR__ . '/User.php';

class Vendor extends User {
    private $shopId;
    private $ownerName;

    // Constructor
    public function __construct($userId = null, $username = "", $email = "", $passwordHash = "", $status = "active", $role = "vendor", $phone = "", $shopId = null, $ownerName = "") {
        parent::__construct($userId, $username, $email, $passwordHash, $status, $role, $phone);
        $this->shopId = $shopId;
        $this->ownerName = $ownerName;
    }

    // Getters and Setters
    public function getShopId() {
        return $this->shopId;
    }

    public function setShopId($shopId) {
        $this->shopId = $shopId;
    }

    public function getOwnerName() {
        return $this->ownerName;
    }

    public function setOwnerName($ownerName) {
        $this->ownerName = $ownerName;
    }

    // Class Methods (Matching UML Class Diagram)
    public function manageOrders() {
        // Retrieve and process assigned bookings
        return [];
    }

    public function updateStatus() {
        // Toggle orders status (e.g. Washing, Ready, Delivered)
        return true;
    }

    public function manageServices() {
        // Configure options offered by the shop
        return true;
    }
}
