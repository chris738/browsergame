<?php

class DatabaseConnection {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $conn;
    private $connectionFailed = false;

    public function __construct() {
        // Support environment variables for Docker/containerized deployments
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'browsergame';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'browsergame';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'sicheresPasswort';
        
        $this->connect();
    }

    private function connect() {
        // Try standard database credentials as fallback
        $credentialSets = [
            // Original credentials
            ['user' => $this->username, 'pass' => $this->password],
            // Common standard credentials
            ['user' => 'root', 'pass' => 'root'],
            ['user' => 'root', 'pass' => ''],
            ['user' => 'root', 'pass' => 'password'],
            ['user' => 'root', 'pass' => 'admin'],
        ];
        
        foreach ($credentialSets as $credentials) {
            try {
                $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $credentials['user'], $credentials['pass']);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // If we get here, connection was successful
                $this->username = $credentials['user'];
                $this->password = $credentials['pass'];
                break;
            } catch (PDOException $e) {
                // Continue to try next credential set
                continue;
            }
        }
        
        // If no credentials worked, mark connection as failed
        if (!isset($this->conn)) {
            $this->connectionFailed = true;
            error_log("Database connection failed with all credential sets");
        }
    }

    public function getConnection() {
        return $this->conn;
    }

    public function isConnected() {
        return !$this->connectionFailed;
    }

    public function getDbName() {
        return $this->dbname;
    }
}

?>