<?php
// washease-api/models/Complaint.php

class Complaint {
    private $complaintId;
    private $customerId;
    private $orderId;
    private $description;
    private $status;
    private $resolution;

    // Constructor
    public function __construct($complaintId = null, $customerId = null, $orderId = null, $description = "", $status = "Pending", $resolution = "") {
        $this->complaintId = $complaintId;
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->description = $description;
        $this->status = $status;
        $this->resolution = $resolution;
    }

    // Getters and Setters
    public function getComplaintId() {
        return $this->complaintId;
    }

    public function setComplaintId($complaintId) {
        $this->complaintId = $complaintId;
    }

    public function getCustomerId() {
        return $this->customerId;
    }

    public function setCustomerId($customerId) {
        $this->customerId = $customerId;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getResolution() {
        return $this->resolution;
    }

    public function setResolution($resolution) {
        $this->resolution = $resolution;
    }

    // Class Methods
    public function submit() {
        // Log complaint to DB
        return true;
    }

    public function updateStatus() {
        // Toggle complaint step (e.g. Assigned, Resolved)
        return true;
    }

    public function resolve() {
        // Resolve ticket and save admin response
        return true;
    }
}
