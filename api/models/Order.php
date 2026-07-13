<?php
// washease-api/models/Order.php

class Order {
    private $orderId;
    private $customerId;
    private $shopId;
    private $weight;
    private $specialNote;
    private $status;
    private $createdAt;

    // Constructor
    public function __construct($orderId = null, $customerId = null, $shopId = null, $weight = 0.0, $specialNote = "", $status = "Pending", $createdAt = null) {
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->shopId = $shopId;
        $this->weight = $weight;
        $this->specialNote = $specialNote;
        $this->status = $status;
        $this->createdAt = $createdAt ? $createdAt : date('Y-m-d H:i:s');
    }

    // Getters and Setters
    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getCustomerId() {
        return $this->customerId;
    }

    public function setCustomerId($customerId) {
        $this->customerId = $customerId;
    }

    public function getShopId() {
        return $this->shopId;
    }

    public function setShopId($shopId) {
        $this->shopId = $shopId;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function setWeight($weight) {
        $this->weight = $weight;
    }

    public function getSpecialNote() {
        return $this->specialNote;
    }

    public function setSpecialNote($specialNote) {
        $this->specialNote = $specialNote;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }

    // Class Methods
    public function calculateCost() {
        // Compute base charges based on weight
        return 0.0;
    }

    public function updateStatus() {
        // Update booking state
        return true;
    }

    public function cancelOrder() {
        // Cancel the laundry booking
        return true;
    }
}
