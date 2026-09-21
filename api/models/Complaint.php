<?php
// washease-api/models/Complaint.php

class Complaint {
    private $complaintId;
    private $customerId;
    private $orderId;
    private $category;
    private $description;
    private $status;
    private $adminResponse;

    // Constructor
    public function __construct($complaintId = null, $customerId = null, $orderId = null, $category = "", $description = "", $status = "Pending", $adminResponse = "") {
        $this->complaintId = $complaintId;
        $this->customerId = $customerId;
        $this->orderId = $orderId;
        $this->category = $category;
        $this->description = $description;
        $this->status = $status;
        $this->adminResponse = $adminResponse;
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

    public function getCategory() {
        return $this->category;
    }

    public function setCategory($category) {
        $this->category = $category;
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

    public function getAdminResponse() {
        return $this->adminResponse;
    }

    public function setAdminResponse($adminResponse) {
        $this->adminResponse = $adminResponse;
    }

    // Backward compatibility alias getter/setter for resolution
    public function getResolution() {
        return $this->adminResponse;
    }

    public function setResolution($resolution) {
        $this->adminResponse = $resolution;
    }

    // Class Methods (Matching UML Class Diagram)
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
