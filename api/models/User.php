<?php
// washease-api/models/User.php

class User {
    private $userId;
    private $username;
    private $email;
    private $passwordHash;
    private $status;
    private $role;
    private $phone;

    // Constructor
    public function __construct($userId = null, $username = "", $email = "", $passwordHash = "", $status = "active", $role = "", $phone = "") {
        $this->userId = $userId;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->status = $status;
        $this->role = $role;
        $this->phone = $phone;
    }

    // Getters and Setters
    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    // Alias for name getter/setter compatibility
    public function getName() {
        return $this->username;
    }

    public function setName($name) {
        $this->username = $name;
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

    public function getStatus() {
        return $this->status;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getRole() {
        return $this->role;
    }

    public function setRole($role) {
        $this->role = $role;
    }

    public function getPhone() {
        return $this->phone;
    }

    public function setPhone($phone) {
        $this->phone = $phone;
    }

    // Class Methods (Matching UML Class Diagram)
    public function login() {
        // Authenticates user and initializes session
        return true;
    }

    public function logout() {
        // Clear session and log user out
        return true;
    }

    public function updateProfile() {
        // Save profile changes to DB
        return true;
    }
}
