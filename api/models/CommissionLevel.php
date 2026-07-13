<?php
// washease-api/models/CommissionLevel.php

class CommissionLevel {
    private $levelId;
    private $shopId;
    private $level;
    private $orderThreshold;
    private $commissionRate;

    // Constructor
    public function __construct($levelId = null, $shopId = null, $level = "Bronze", $orderThreshold = 0, $commissionRate = 15.0) {
        $this->levelId = $levelId;
        $this->shopId = $shopId;
        $this->level = $level;
        $this->orderThreshold = $orderThreshold;
        $this->commissionRate = $commissionRate;
    }

    // Getters and Setters
    public function getLevelId() {
        return $this->levelId;
    }

    public function setLevelId($levelId) {
        $this->levelId = $levelId;
    }

    public function getShopId() {
        return $this->shopId;
    }

    public function setShopId($shopId) {
        $this->shopId = $shopId;
    }

    public function getLevel() {
        return $this->level;
    }

    public function setLevel($level) {
        $this->level = $level;
    }

    public function getOrderThreshold() {
        return $this->orderThreshold;
    }

    public function setOrderThreshold($orderThreshold) {
        $this->orderThreshold = $orderThreshold;
    }

    public function getCommissionRate() {
        return $this->commissionRate;
    }

    public function setCommissionRate($commissionRate) {
        $this->commissionRate = $commissionRate;
    }

    // Class Methods
    public function calculateRate() {
        // Compute commission based on orders count
        return 15.0;
    }

    public function updateLevel() {
        // Adjust the reward status levels
        return true;
    }

    public function assignLevel() {
        // Update vendor's level tier (Bronze, Silver, Gold)
        return true;
    }
}
