<?php
// washease-api/models/Invoice.php

class Invoice {
    private $invoiceId;
    private $orderId;
    private $totalAmount;
    private $paidAmount;
    private $paymentMethod;
    private $isConfirmed;

    // Constructor
    public function __construct($invoiceId = null, $orderId = null, $totalAmount = 0.0, $paidAmount = 0.0, $paymentMethod = "", $isConfirmed = false) {
        $this->invoiceId = $invoiceId;
        $this->orderId = $orderId;
        $this->totalAmount = $totalAmount;
        $this->paidAmount = $paidAmount;
        $this->paymentMethod = $paymentMethod;
        $this->isConfirmed = $isConfirmed;
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

    public function getTotalAmount() {
        return $this->totalAmount;
    }

    public function setTotalAmount($totalAmount) {
        $this->totalAmount = $totalAmount;
    }

    public function getPaidAmount() {
        return $this->paidAmount;
    }

    public function setPaidAmount($paidAmount) {
        $this->paidAmount = $paidAmount;
    }

    public function getPaymentMethod() {
        return $this->paymentMethod;
    }

    public function setPaymentMethod($paymentMethod) {
        $this->paymentMethod = $paymentMethod;
    }

    public function getIsConfirmed() {
        return $this->isConfirmed;
    }

    public function setIsConfirmed($isConfirmed) {
        $this->isConfirmed = $isConfirmed;
    }

    // Class Methods
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
