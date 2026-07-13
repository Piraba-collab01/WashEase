<?php
// washease-api/models/Shopkeeper.php

require_once __DIR__ . '/User.php';

class Shopkeeper extends User {
    private $shopId;

    // Constructor
    public function __construct($userId = null, $name = "", $email = "", $passwordHash = "", $phone = "", $role = "vendor", $shopId = null) {
        parent::__construct($userId, $name, $email, $passwordHash, $phone, $role);
        $this->shopId = $shopId;
    }

    // Getters and Setters
    public function getShopId() {
        return $this->shopId;
    }

    public function setShopId($shopId) {
        $this->shopId = $shopId;
    }

    // Class Methods
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
