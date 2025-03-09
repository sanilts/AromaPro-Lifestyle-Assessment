<?php
/**
 * Database connection configuration
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'dbolaxfygn7bgm';
    private $username = 'utei7xp26d6k4';  // Change to your MySQL username
    private $password = '8w3s6r2uzpbj';      // Change to your MySQL password
    private $conn;

    /**
     * Get database connection
     * 
     * @return PDO
     */
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }

        return $this->conn;
    }
}