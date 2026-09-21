<?php
// washease-api/models/FraudAlert.php

class FraudAlert {
    private $alertId;
    private $orderId;
    private $customerId;
    private $vendorId;
    private $vendorAmount;
    private $customerAmount;
    private $difference;
    private $status;

    // Constructor
    public function __construct(
        $alertId = null, 
        $orderId = null, 
        $customerId = null, 
        $vendorId = null, 
        $vendorAmount = 0.0, 
        $customerAmount = 0.0, 
        $difference = 0.0, 
        $status = "Pending"
    ) {
        $this->alertId = $alertId;
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->vendorId = $vendorId;
        $this->vendorAmount = $vendorAmount;
        $this->customerAmount = $customerAmount;
        $this->difference = $difference;
        $this->status = $status;
    }

    // Getters and Setters
    public function getAlertId() {
        return $this->alertId;
    }

    public function setAlertId($alertId) {
        $this->alertId = $alertId;
    }

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

    public function getVendorId() {
        return $this->vendorId;
    }

    public function setVendorId($vendorId) {
        $this->vendorId = $vendorId;
    }

    public function getVendorAmount() {
        return $this->vendorAmount;
    }

    public function setVendorAmount($vendorAmount) {
        $this->vendorAmount = $vendorAmount;
    }

    public function getCustomerAmount() {
        return $this->customerAmount;
    }

    public function setCustomerAmount($customerAmount) {
        $this->customerAmount = $customerAmount;
    }

    public function getDifference() {
        return $this->difference;
    }

    public function setDifference($difference) {
        $this->difference = $difference;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    // Class Methods (Matching UML Class Diagram)
    public function createAlert() {
        // Flag system discrepancy alert
        return true;
    }

    public function resolveAlert() {
        // Clear or resolve fraud flag
        return true;
    }
}
