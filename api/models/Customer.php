<?php
// washease-api/models/Customer.php

require_once __DIR__ . '/User.php';

class Customer extends User {
    private $fullName;
    private $address;

    // Constructor
    public function __construct($userId = null, $username = "", $email = "", $passwordHash = "", $status = "active", $role = "customer", $phone = "", $fullName = "", $address = "") {
        parent::__construct($userId, $username, $email, $passwordHash, $status, $role, $phone);
        $this->fullName = $fullName;
        $this->address = $address;
    }

    // Getters and Setters
    public function getFullName() {
        return $this->fullName;
    }

    public function setFullName($fullName) {
        $this->fullName = $fullName;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setAddress($address) {
        $this->address = $address;
    }

    // Class Methods (Matching UML Class Diagram)
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
