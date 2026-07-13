<?php
// washease-api/models/LaundryShop.php

class LaundryShop {
    private $shopId;
    private $shopName;
    private $location;
    private $openTime;
    private $closeTime;
    private $servicesOffered;

    // Constructor
    public function __construct($shopId = null, $shopName = "", $location = "", $openTime = "", $closeTime = "", $servicesOffered = []) {
        $this->shopId = $shopId;
        $this->shopName = $shopName;
        $this->location = $location;
        $this->openTime = $openTime;
        $this->closeTime = $closeTime;
        $this->servicesOffered = $servicesOffered;
    }

    // Getters and Setters
    public function getShopId() {
        return $this->shopId;
    }

    public function setShopId($shopId) {
        $this->shopId = $shopId;
    }

    public function getShopName() {
        return $this->shopName;
    }

    public function setShopName($shopName) {
        $this->shopName = $shopName;
    }

    public function getLocation() {
        return $this->location;
    }

    public function setLocation($location) {
        $this->location = $location;
    }

    public function getOpenTime() {
        return $this->openTime;
    }

    public function setOpenTime($openTime) {
        $this->openTime = $openTime;
    }

    public function getCloseTime() {
        return $this->closeTime;
    }

    public function setCloseTime($closeTime) {
        $this->closeTime = $closeTime;
    }

    public function getServicesOffered() {
        return $this->servicesOffered;
    }

    public function setServicesOffered($servicesOffered) {
        $this->servicesOffered = $servicesOffered;
    }

    // Class Methods
    public function addService() {
        // Toggle new service on
        return true;
    }

    public function updateService() {
        // Edit service rates or details
        return true;
    }

    public function processOrder() {
        // Process order weight billing
        return true;
    }
}
