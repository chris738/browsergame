<?php

interface DatabaseInterface {
    public function getResources($settlementId);
    public function getBuilding($settlementId, $buildingType);
    public function upgradeBuilding($settlementId, $buildingType);
    public function getRegen($settlementId);
    public function getSettlementName($settlementId);
    public function getQueue($settlementId);
    public function getMap();
    
    // Admin-specific methods
    public function getPlayerCount();
    public function getSettlementCount();
    public function getActiveQueuesCount();
    public function getAllPlayers();
    public function getAllSettlements();
    public function getAllQueues();
    public function createPlayer($name, $gold);
    public function deletePlayer($playerId);
    public function updatePlayerStats($playerId, $points, $gold);
    public function updateSettlementResources($settlementId, $wood, $stone, $ore);
    public function deleteQueue($queueId);
    public function clearAllQueues();
}

class Database implements DatabaseInterface {
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
        
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->connectionFailed = true;
            // Don't die, just mark connection as failed for graceful degradation
            error_log("Database connection failed: " . $e->getMessage());
        }
    }

    public function isConnected() {
        return !$this->connectionFailed;
    }

    public function getResources($settlementId) {
        $sql = "
            SELECT 
                s.wood, 
                s.stone, 
                s.ore, 
                COALESCE(
                    (
                        SELECT SUM(bc.productionRate)
                        FROM Buildings b
                        INNER JOIN BuildingConfig bc 
                        ON b.buildingType = bc.buildingType AND b.level = bc.level
                        WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
                    ), 
                    0
                ) AS storageCapacity,
                ss.maxSettlers,
                ss.freeSettlers
            FROM 
                Settlement s
            LEFT JOIN 
                SettlementSettlers ss
            ON 
                s.settlementId = ss.settlementId
            WHERE 
                s.settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $resources = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getBuilding($settlementId, $buildingType) {
        $sql = "
            SELECT 
                b.currentLevel,
                b.nextLevel, 
                b.costWood, 
                b.costStone, 
                b.costOre, 
                b.settlers
            FROM BuildingDetails b
            WHERE b.settlementId = :settlementId AND b.buildingType = :buildingType";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            throw new Exception("Gebäude '$buildingType' im Settlement '$settlementId' nicht gefunden.");
        }
    
        return $result;
    }
    
    public function upgradeBuilding($settlementId, $buildingType) {
        try {
            $sql = "CALL UpgradeBuilding(:settlementId, :buildingType)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
    
            $stmt->execute();
    
            // Überprüfen, ob die Prozedur erfolgreich ausgeführt wurde
            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => "Das Gebäude '$buildingType' wurde erfolgreich upgegradet."
                ];
            } else {
                return [
                    'success' => false,
                    'message' => "Keine Änderungen am Gebäude '$buildingType' vorgenommen."
                ];
            }
        } catch (PDOException $e) {
            // Fehler aus der Datenbank abfangen
            return [
                'success' => false,
                'message' => "Fehler beim Upgrade in database.php: " . $e->getMessage()
            ];
        } catch (Exception $e) {
            // Allgemeine Fehler abfangen
            return [
                'success' => false,
                'message' => "Unbekannter Fehler in database.php: " . $e->getMessage()
            ];
        }
    }

    public function getRegen($settlementId) {
        $sql = "
        SELECT 
            -- Get the total production rate for Holzfäller (Wood)
            (
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc 
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = :settlementId AND b.buildingType = 'Holzfäller'
            ) AS woodProductionRate,

            -- Get the total production rate for Steinbruch (Stone)
            (
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc 
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = :settlementId AND b.buildingType = 'Steinbruch'
            ) AS stoneProductionRate,

            -- Get the total production rate for Erzbergwerk (Ore)
            (
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc 
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = :settlementId AND b.buildingType = 'Erzbergwerk'
            ) AS oreProductionRate
        FROM Settlement s
        WHERE s.settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getSettlementName($settlementId) {
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getQueue($settlementId) {
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMap() {
        $sql = "
        SELECT
            settlementId, xCoordinate, yCoordinate
        FROM 
            Map";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Admin-specific methods
    public function getPlayerCount() {
        if ($this->connectionFailed) return 5; // Mock data
        $sql = "SELECT COUNT(*) as count FROM Spieler";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getSettlementCount() {
        if ($this->connectionFailed) return 8; // Mock data
        $sql = "SELECT COUNT(*) as count FROM Settlement";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getActiveQueuesCount() {
        if ($this->connectionFailed) return 3; // Mock data
        $sql = "SELECT COUNT(*) as count FROM BuildingQueue WHERE NOW() < endTime";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function getAllPlayers() {
        $sql = "SELECT playerId, name, punkte as points, gold FROM Spieler ORDER BY playerId";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSettlements() {
        $sql = "
            SELECT 
                s.settlementId,
                s.name,
                s.wood,
                s.stone,
                s.ore,
                p.name as playerName,
                m.xCoordinate,
                m.yCoordinate
            FROM Settlement s
            LEFT JOIN Spieler p ON s.playerId = p.playerId
            LEFT JOIN Map m ON s.settlementId = m.settlementId
            ORDER BY s.settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllQueues() {
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
    }

    public function createPlayer($name, $gold = 500) {
        try {
            $sql = "CALL CreatePlayerWithSettlement(:playerName)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':playerName', $name, PDO::PARAM_STR);
            $stmt->execute();
            
            // Update gold if different from default
            if ($gold != 500) {
                $updateSql = "UPDATE Spieler SET gold = :gold WHERE name = :name";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bindParam(':gold', $gold, PDO::PARAM_INT);
                $updateStmt->bindParam(':name', $name, PDO::PARAM_STR);
                $updateStmt->execute();
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deletePlayer($playerId) {
        try {
            $sql = "DELETE FROM Spieler WHERE playerId = :playerId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':playerId', $playerId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updatePlayerStats($playerId, $points, $gold) {
        try {
            $sql = "UPDATE Spieler SET punkte = :points, gold = :gold WHERE playerId = :playerId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':points', $points, PDO::PARAM_INT);
            $stmt->bindParam(':gold', $gold, PDO::PARAM_INT);
            $stmt->bindParam(':playerId', $playerId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updateSettlementResources($settlementId, $wood, $stone, $ore) {
        try {
            $sql = "UPDATE Settlement SET wood = :wood, stone = :stone, ore = :ore WHERE settlementId = :settlementId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':wood', $wood, PDO::PARAM_STR);
            $stmt->bindParam(':stone', $stone, PDO::PARAM_STR);
            $stmt->bindParam(':ore', $ore, PDO::PARAM_STR);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
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
}

?>
