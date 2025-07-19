<?php

class MapRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    public function getMap() {
        // Return empty array if database connection failed
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "
            SELECT
                settlementId, xCoordinate, yCoordinate
            FROM 
                Map";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $map = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Validate each map entry
            if ($map && is_array($map)) {
                foreach ($map as $index => $entry) {
                    try {
                        require_once __DIR__ . '/../../sql-data-validator.php';
                        SQLDataValidator::validateMapData($entry);
                    } catch (InvalidArgumentException $e) {
                        require_once __DIR__ . '/../../sql-data-validator.php';
                        SQLDataValidator::logValidationError('getMap output entry ' . $index, $e->getMessage());
                        error_log("Invalid map entry at index $index: " . json_encode($entry));
                        // Remove invalid entry instead of failing completely
                        unset($map[$index]);
                    }
                }
                // Re-index array after potential unset operations
                $map = array_values($map);
            }
            
            return $map ?: [];
        } catch (PDOException $e) {
            require_once __DIR__ . '/../../sql-data-validator.php';
            SQLDataValidator::logValidationError('getMap SQL', $e->getMessage());
            error_log("SQL error in getMap: " . $e->getMessage());
            return [];
        }
    }
}

?>