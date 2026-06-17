<?php
// washease-api/config/database.php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'washease');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error
            error_log("Database Connection Error: " . $e->getMessage());
            header('Content-Type: application/json', true, 500);
            echo json_encode([
                "success" => false,
                "message" => "Database connection failed. Please make sure MySQL is running."
            ]);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
