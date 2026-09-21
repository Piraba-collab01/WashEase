<?php
// washease-api/models/Invoice.php

class Invoice {
    private $invoiceId;
    private $orderId;
    private $invoiceNumber;
    private $totalAmount;
    private $laundryCharges;
    private $serviceCharges;
    private $createdAt;
    private $taxes;

    // Constructor
    public function __construct(
        $invoiceId = null, 
        $orderId = null, 
        $invoiceNumber = "", 
        $totalAmount = 0.0, 
        $laundryCharges = 0.0, 
        $serviceCharges = 0.0, 
        $createdAt = null, 
        $taxes = 0.0
    ) {
        $this->invoiceId = $invoiceId;
        $this->orderId = $orderId;
        $this->invoiceNumber = $invoiceNumber;
        $this->totalAmount = $totalAmount;
        $this->laundryCharges = $laundryCharges;
        $this->serviceCharges = $serviceCharges;
        $this->createdAt = $createdAt ? $createdAt : date('Y-m-d H:i:s');
        $this->taxes = $taxes;
    }

    // Getters and Setters
    public function getInvoiceId() {
        return $this->invoiceId;
    }

    public function setInvoiceId($invoiceId) {
        $this->invoiceId = $invoiceId;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getInvoiceNumber() {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber($invoiceNumber) {
        $this->invoiceNumber = $invoiceNumber;
    }

    public function getTotalAmount() {
        return $this->totalAmount;
    }

    public function setTotalAmount($totalAmount) {
        $this->totalAmount = $totalAmount;
    }

    public function getLaundryCharges() {
        return $this->laundryCharges;
    }

    public function setLaundryCharges($laundryCharges) {
        $this->laundryCharges = $laundryCharges;
    }

    public function getServiceCharges() {
        return $this->serviceCharges;
    }

    public function setServiceCharges($serviceCharges) {
        $this->serviceCharges = $serviceCharges;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }

    public function getTaxes() {
        return $this->taxes;
    }

    public function setTaxes($taxes) {
        $this->taxes = $taxes;
    }

    // Class Methods (Matching UML Class Diagram)
    public function generate() {
        // Generate customer bill
        return true;
    }

    public function processPayment() {
        // Confirm client transfer
        return true;
    }

    public function updateStatus() {
        // Adjust payment verification flags
        return true;
    }
}
