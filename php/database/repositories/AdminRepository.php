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
            // Provide demo settlement data with proper coordinates
            return [
                [
                    'settlementId' => 1,
                    'name' => 'Test Settlement',
                    'wood' => 5000,
                    'stone' => 5000,
                    'ore' => 5000,
                    'playerId' => 1,
                    'playerName' => 'TestPlayer',
                    'xCoordinate' => 0,
                    'yCoordinate' => 0
                ],
                [
                    'settlementId' => 2,
                    'name' => 'Enemy Village',
                    'wood' => 3000,
                    'stone' => 3000,
                    'ore' => 3000,
                    'playerId' => 2,
                    'playerName' => 'Player2',
                    'xCoordinate' => 1,
                    'yCoordinate' => 2
                ],
                [
                    'settlementId' => 3,
                    'name' => 'Rival Town',
                    'wood' => 4000,
                    'stone' => 4000,
                    'ore' => 4000,
                    'playerId' => 3,
                    'playerName' => 'Player3',
                    'xCoordinate' => -1,
                    'yCoordinate' => 1
                ],
                [
                    'settlementId' => 4,
                    'name' => 'Hostile Camp',
                    'wood' => 2500,
                    'stone' => 2500,
                    'ore' => 2500,
                    'playerId' => 4,
                    'playerName' => 'Player4',
                    'xCoordinate' => 2,
                    'yCoordinate' => -1
                ]
            ];
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
            
            // Create starting buildings at level 1 and visible
            $startingBuildings = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Rathaus'];
            foreach ($startingBuildings as $buildingType) {
                try {
                    $sql = "INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES (:settlementId, :buildingType, 1, true)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                    $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Continue if building already exists
                    error_log("Starting building creation failed (continuing): " . $e->getMessage());
                }
            }
            
            // Create unlockable buildings at level 0 and invisible (will be unlocked when requirements are met)
            $unlockableBuildings = ['Farm', 'Markt', 'Kaserne'];
            foreach ($unlockableBuildings as $buildingType) {
                try {
                    $sql = "INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES (:settlementId, :buildingType, 0, false)";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                    $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                    $stmt->execute();
                } catch (PDOException $e) {
                    // Continue if building already exists
                    error_log("Unlockable building creation failed (continuing): " . $e->getMessage());
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
            // Use SettlementResources view for simplified access
            $sql = "SELECT playerName FROM SettlementResources WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['playerName'] : 'Unknown Player';
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
            // Use SettlementResources view for simplified access
            $sql = "SELECT playerId FROM SettlementResources WHERE settlementId = ?";
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
            // Use SettlementResources view for simplified access
            $sql = "SELECT playerGold FROM SettlementResources WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['playerGold'] : 0;
        } catch (Exception $e) {
            return 0;
        }
    }
}

?>