<?php
// washease-api/models/Payment.php

class Payment {
    private $paymentId;
    private $orderId;
    private $invoiceId;
    private $amountPaid;
    private $paymentStatus;
    private $paidAt;

    // Constructor
    public function __construct($paymentId = null, $orderId = null, $invoiceId = null, $amountPaid = 0.0, $paymentStatus = "Pending", $paidAt = null) {
        $this->paymentId = $paymentId;
        $this->orderId = $orderId;
        $this->invoiceId = $invoiceId;
        $this->amountPaid = $amountPaid;
        $this->paymentStatus = $paymentStatus;
        $this->paidAt = $paidAt ? $paidAt : date('Y-m-d H:i:s');
    }

    // Getters and Setters
    public function getPaymentId() {
        return $this->paymentId;
    }

    public function setPaymentId($paymentId) {
        $this->paymentId = $paymentId;
    }

    public function getOrderId() {
        return $this->orderId;
    }

    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    public function getInvoiceId() {
        return $this->invoiceId;
    }

    public function setInvoiceId($invoiceId) {
        $this->invoiceId = $invoiceId;
    }

    public function getAmountPaid() {
        return $this->amountPaid;
    }

    public function setAmountPaid($amountPaid) {
        $this->amountPaid = $amountPaid;
    }

    public function getPaymentStatus() {
        return $this->paymentStatus;
    }

    public function setPaymentStatus($paymentStatus) {
        $this->paymentStatus = $paymentStatus;
    }

    public function getPaidAt() {
        return $this->paidAt;
    }

    public function setPaidAt($paidAt) {
        $this->paidAt = $paidAt;
    }

    // Class Methods (Matching UML Class Diagram)
    public function process() {
        // Execute payment processing
        return true;
    }

    public function getPaymentDetails() {
        // Retrieve transaction record details
        return [
            'paymentId' => $this->paymentId,
            'orderId' => $this->orderId,
            'invoiceId' => $this->invoiceId,
            'amountPaid' => $this->amountPaid,
            'paymentStatus' => $this->paymentStatus,
            'paidAt' => $this->paidAt
        ];
    }
}
