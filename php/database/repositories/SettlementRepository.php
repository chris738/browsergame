<?php

require_once __DIR__ . '/../../sql-data-validator.php';

class SettlementRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    public function getSettlementName($settlementId) {
        // Validate input
        try {
            $settlementId = SQLDataValidator::validateId($settlementId, 'Settlement ID');
        } catch (InvalidArgumentException $e) {
            SQLDataValidator::logValidationError('getSettlementName input', $e->getMessage());
            return false;
        }

        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return ['SettlementName' => 'Test-Siedlung'];
        }

        try {
            $sql = "
                SELECT 
                    s.name AS SettlementName
                FROM 
                    Settlement s
                WHERE 
                    s.settlementId = :settlementId";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validate output data
            if ($result) {
                try {
                    SQLDataValidator::validateSettlementName($result);
                } catch (InvalidArgumentException $e) {
                    SQLDataValidator::logValidationError('getSettlementName output', $e->getMessage());
                    error_log("Invalid settlement name data for settlement $settlementId: " . json_encode($result));
                    return false;
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            SQLDataValidator::logValidationError('getSettlementName SQL', $e->getMessage());
            error_log("SQL error in getSettlementName for settlement $settlementId: " . $e->getMessage());
            return false;
        }
    }

    public function getQueue($settlementId) {
        // Validate input
        try {
            $settlementId = SQLDataValidator::validateId($settlementId, 'Settlement ID');
        } catch (InvalidArgumentException $e) {
            SQLDataValidator::logValidationError('getQueue input', $e->getMessage());
            return [];
        }

        // Return mock empty queue if database connection failed
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "
            SELECT
                queueId,
                settlementId,
                buildingType,
                startTime,
                endTime, 
                completionPercentage,
                level
            FROM 
                OpenBuildingQueue 
            WHERE 
                settlementId = :settlementId";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->execute();
            $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Validate each queue entry
            if ($queue && is_array($queue)) {
                foreach ($queue as $index => $entry) {
                    try {
                        SQLDataValidator::validateQueueData($entry);
                    } catch (InvalidArgumentException $e) {
                        SQLDataValidator::logValidationError('getQueue output entry ' . $index, $e->getMessage());
                        error_log("Invalid queue entry for settlement $settlementId at index $index: " . json_encode($entry));
                        // Remove invalid entry instead of failing completely
                        unset($queue[$index]);
                    }
                }
                // Re-index array after potential unset operations
                $queue = array_values($queue);
            }
            
            return $queue ?: [];
        } catch (PDOException $e) {
            SQLDataValidator::logValidationError('getQueue SQL', $e->getMessage());
            error_log("SQL error in getQueue for settlement $settlementId: " . $e->getMessage());
            return [];
        }
    }

    public function deleteQueue($queueId) {
        try {
            $sql = "DELETE FROM BuildingQueue WHERE queueId = :queueId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':queueId', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function clearAllQueues() {
        try {
            $sql = "DELETE FROM BuildingQueue";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllQueues() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    bq.queueId,
                    bq.settlementId,
                    s.name as settlementName,
                    bq.buildingType,
                    bq.level,
                    bq.startTime,
                    bq.endTime,
                    bq.isActive,
                    ROUND(
                        100 - (TIMESTAMPDIFF(SECOND, NOW(), bq.endTime) * 100.0 / 
                               TIMESTAMPDIFF(SECOND, bq.startTime, bq.endTime)),
                        2
                    ) AS completionPercentage
                FROM BuildingQueue bq
                LEFT JOIN Settlement s ON bq.settlementId = s.settlementId
                WHERE NOW() < bq.endTime
                ORDER BY bq.endTime ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch queues: " . $e->getMessage());
            return [];
        }
    }
}

?>