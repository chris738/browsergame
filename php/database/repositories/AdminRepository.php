<?php

class AdminRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    // Player management methods
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
        if ($this->connectionFailed) {
            return [];
        }
        
        $sql = "SELECT playerId, name, punkte as points, gold FROM Spieler ORDER BY playerId";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSettlements() {
        if ($this->connectionFailed) {
            return [];
        }
        
        $sql = "
            SELECT 
                s.settlementId,
                s.name,
                s.wood,
                s.stone,
                s.ore,
                s.playerId,
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

    public function createPlayer($name, $gold = 500) {
        try {
            // Try to call the stored procedure first
            try {
                $sql = "CALL CreatePlayerWithSettlement(:playerName)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bindParam(':playerName', $name, PDO::PARAM_STR);
                $stmt->execute();
            } catch (PDOException $e) {
                if ($e->getCode() == '1305') { // PROCEDURE does not exist
                    error_log("CreatePlayerWithSettlement procedure missing, using direct SQL...");
                    return $this->createPlayerDirect($name, $gold);
                }
                throw $e;
            }
            
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
            error_log("Error creating player: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create player directly with SQL if stored procedure is not available
     */
    private function createPlayerDirect($name, $gold = 500) {
        try {
            $this->conn->beginTransaction();
            
            // Create player
            $sql = "INSERT INTO Spieler (name, punkte, gold) VALUES (:name, 0, :gold)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':gold', $gold, PDO::PARAM_INT);
            $stmt->execute();
            $playerId = $this->conn->lastInsertId();
            
            // Generate random coordinates
            $xCoord = rand(-10, 10);
            $yCoord = rand(-10, 10);
            
            // Create settlement
            $settlementName = $name . '_Settlement';
            $sql = "INSERT INTO Settlement (name, wood, stone, ore, playerId) VALUES (:name, 10000, 10000, 10000, :playerId)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $settlementName, PDO::PARAM_STR);
            $stmt->bindParam(':playerId', $playerId, PDO::PARAM_INT);
            $stmt->execute();
            $settlementId = $this->conn->lastInsertId();
            
            // Create buildings
            $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
            foreach ($buildingTypes as $buildingType) {
                try {
                    $sql = "INSERT INTO Buildings (settlementId, buildingType) VALUES (:settlementId, :buildingType)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                    $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Continue if building already exists
                    error_log("Building creation failed (continuing): " . $e->getMessage());
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollback();
            error_log("Error in createPlayerDirect: " . $e->getMessage());
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

    // Helper methods
    public function getPlayerNameFromSettlement($settlementId) {
        if ($this->connectionFailed) {
            // Return mock data based on settlement ID
            $mockPlayers = [
                1 => 'TestPlayer',
                2 => 'Player2', 
                3 => 'Player3'
            ];
            return $mockPlayers[$settlementId] ?? 'Unknown Player';
        }
        
        try {
            $sql = "SELECT p.name FROM Spieler p 
                    INNER JOIN Settlement s ON p.playerId = s.playerId 
                    WHERE s.settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['name'] : 'Unknown Player';
        } catch (Exception $e) {
            return 'Unknown Player';
        }
    }

    public function getPlayerIdFromSettlement($settlementId) {
        if ($this->connectionFailed) {
            // Return mock data based on settlement ID - same player IDs as settlement IDs for simplicity
            return $settlementId;
        }
        
        try {
            $sql = "SELECT p.playerId FROM Spieler p 
                    INNER JOIN Settlement s ON p.playerId = s.playerId 
                    WHERE s.settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['playerId'] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getPlayerGold($settlementId) {
        if ($this->connectionFailed) {
            // Return different gold amounts for different players  
            $mockGold = [
                1 => 500,
                2 => 750,
                3 => 1000
            ];
            return $mockGold[$settlementId] ?? 500;
        }
        
        try {
            $sql = "SELECT p.gold FROM Spieler p 
                    INNER JOIN Settlement s ON p.playerId = s.playerId 
                    WHERE s.settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['gold'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

?>