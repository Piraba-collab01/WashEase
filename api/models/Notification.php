<?php
// washease-api/models/Notification.php

class Notification {
    private $notificationId;
    private $userId;
    private $type;
    private $message;
    private $isRead;
    private $sentAt;

    // Constructor
    public function __construct($notificationId = null, $userId = null, $type = "info", $message = "", $isRead = false, $sentAt = null) {
        $this->notificationId = $notificationId;
        $this->userId = $userId;
        $this->type = $type;
        $this->message = $message;
        $this->isRead = $isRead;
        $this->sentAt = $sentAt ? $sentAt : date('Y-m-d H:i:s');
    }

    // Getters and Setters
    public function getNotificationId() {
        return $this->notificationId;
    }

    public function setNotificationId($notificationId) {
        $this->notificationId = $notificationId;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function setUserId($userId) {
        $this->userId = $userId;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getIsRead() {
        return $this->isRead;
    }

    public function setIsRead($isRead) {
        $this->isRead = $isRead;
    }

    public function getSentAt() {
        return $this->sentAt;
    }

    public function setSentAt($sentAt) {
        $this->sentAt = $sentAt;
    }

    // Class Methods
    public function send() {
        // Broadcast notification to user
        return true;
    }

    public function markAsRead() {
        // Toggle notification status flag
        return true;
    }

    public function delete() {
        // Erase alert from DB log
        return true;
    }
}
