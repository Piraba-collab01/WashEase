<?php
// washease-api/models/User.php

class User {
    private $userId;
    private $name;
    private $email;
    private $passwordHash;
    private $phone;
    private $role;

    // Constructor
    public function __construct($userId = null, $name = "", $email = "", $passwordHash = "", $phone = "", $role = "") {
        $this->userId = $userId;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->phone = $phone;
        $this->role = $role;
    }

    // Getters and Setters
    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getPasswordHash() {
        return $this->passwordHash;
    }

    public function setPasswordHash($passwordHash) {
        $this->passwordHash = $passwordHash;
    }

    public function getPhone() {
        return $this->phone;
    }

    public function setPhone($phone) {
        $this->phone = $phone;
    }

    public function getRole() {
        return $this->role;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    // Class Methods
    public function login() {
        // Authenticates user and initializes session
        return true;
    }

    public function logout() {
        // Clear session and log user out
        return true;
    }

    public function updateProfile() {
        // Save name, email, phone changes to DB
        return true;
    }
}
