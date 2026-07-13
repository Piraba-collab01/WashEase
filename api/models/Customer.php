<?php
// washease-api/models/Customer.php

require_once __DIR__ . '/User.php';

class Customer extends User {
    private $address;

    // Constructor
    public function __construct($userId = null, $name = "", $email = "", $passwordHash = "", $phone = "", $role = "customer", $address = "") {
        parent::__construct($userId, $name, $email, $passwordHash, $phone, $role);
        $this->address = $address;
    }

    // Getters and Setters
    public function getAddress() {
        return $this->address;
    }

    public function setAddress($address) {
        $this->address = $address;
    }

    // Class Methods
    public function placeOrder() {
        // Place a new laundry order
        return true;
    }

    public function trackOrder() {
        // Fetch current status of active orders
        return [];
    }

    public function submitComplaint() {
        // Submit an order-related complaint
        return true;
    }
}
