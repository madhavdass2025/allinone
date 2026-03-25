<?php
class Database {
    private static $instance = null;
    private $conn;

    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $name = 'pharmacy_erp';

    private function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->name);

            if ($this->conn->connect_error) {
                error_log("Database connection failed: " . $this->conn->connect_error);
                throw new Exception("Unable to connect to database. Please check your configuration.");
            }
            $this->conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Prevent cloning
    private function __clone() {}

    // Public for PHP 8 compliance
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}

// Global function to get connection
function get_db_conn() {
    return Database::getInstance()->getConnection();
}
?>
