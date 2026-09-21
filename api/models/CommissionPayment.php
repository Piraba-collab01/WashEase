<?php
// washease-api/models/CommissionPayment.php

class CommissionPayment {
    private $paymentId;
    private $vendorId;
    private $amount;
    private $transactionRef;
    private $paymentDate;
    private $status;

    // Constructor
    public function __construct($paymentId = null, $vendorId = null, $amount = 0.00, $transactionRef = "", $paymentDate = null, $status = "Pending") {
        $this->paymentId = $paymentId;
        $this->vendorId = $vendorId;
        $this->amount = $amount;
        $this->transactionRef = $transactionRef;
        $this->paymentDate = $paymentDate ? $paymentDate : date('Y-m-d H:i:s');
        $this->status = $status;
    }

    // Getters and Setters
    public function getPaymentId() {
        return $this->paymentId;
    }

    public function setPaymentId($paymentId) {
        $this->paymentId = $paymentId;
    }

    // Compatibility getters/setters for id
    public function getId() {
        return $this->paymentId;
    }

    public function setId($id) {
        $this->paymentId = $id;
    }

    public function getVendorId() {
        return $this->vendorId;
    }

    public function setVendorId($vendorId) {
        $this->vendorId = $vendorId;
    }

    public function getAmount() {
        return $this->amount;
    }

    public function setAmount($amount) {
        $this->amount = $amount;
    }

    public function getTransactionRef() {
        return $this->transactionRef;
    }

    public function setTransactionRef($transactionRef) {
        $this->transactionRef = $transactionRef;
    }

    public function getPaymentDate() {
        return $this->paymentDate;
    }

    public function setPaymentDate($paymentDate) {
        $this->paymentDate = $paymentDate;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    // Class Methods (Matching UML Class Diagram)

    /**
     * Submit a commission payment.
     * Inserts into database if PDO instance $db is provided, or updates model state.
     */
    public function submit($db = null) {
        if ($db && $this->vendorId && $this->amount > 0 && !empty($this->transactionRef)) {
            $stmt = $db->prepare("
                INSERT INTO commission_payments (vendor_id, amount, transaction_ref, status) 
                VALUES (?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $this->vendorId,
                $this->amount,
                $this->transactionRef,
                $this->status ?: 'Pending'
            ]);
            if ($result) {
                $this->paymentId = $db->lastInsertId();
            }
            return $result;
        }
        return true;
    }

    /**
     * Update the status of the commission payment (e.g. 'Approved', 'Rejected').
     * Updates database record if PDO instance $db is provided, or updates model state.
     */
    public function updateStatus($newStatus = 'Approved', $db = null) {
        $this->status = $newStatus;
        if ($db && $this->paymentId) {
            $stmt = $db->prepare("UPDATE commission_payments SET status = ? WHERE id = ?");
            return $stmt->execute([$newStatus, $this->paymentId]);
        }
        return true;
    }
}
