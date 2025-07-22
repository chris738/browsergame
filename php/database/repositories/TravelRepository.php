<?php

class TravelRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    // Travel Configuration Methods
    public function getTravelConfig($travelType) {
        if ($this->connectionFailed) {
            return $travelType === 'trade' ? 5 : 5; // default values
        }

        try {
            $sql = "SELECT baseSpeed FROM TravelConfig WHERE travelType = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$travelType]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['baseSpeed'] : ($travelType === 'trade' ? 5 : 5);
        } catch (Exception $e) {
            error_log("Error getting travel config: " . $e->getMessage());
            return $travelType === 'trade' ? 5 : 5;
        }
    }

    public function updateTravelConfig($travelType, $baseSpeed) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $sql = "UPDATE TravelConfig SET baseSpeed = ? WHERE travelType = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$baseSpeed, $travelType]);
        } catch (Exception $e) {
            error_log("Error updating travel config: " . $e->getMessage());
            return false;
        }
    }

    // Distance Calculation Methods
    public function calculateDistance($fromSettlementId, $toSettlementId) {
        if ($this->connectionFailed) {
            return 1; // default distance
        }

        try {
            $sql = "SELECT 
                        SQRT(
                            POW(m1.xCoordinate - m2.xCoordinate, 2) + 
                            POW(m1.yCoordinate - m2.yCoordinate, 2)
                        ) as distance
                    FROM Map m1, Map m2 
                    WHERE m1.settlementId = ? AND m2.settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$fromSettlementId, $toSettlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? max(1, ceil((float)$result['distance'])) : 1;
        } catch (Exception $e) {
            error_log("Error calculating distance: " . $e->getMessage());
            return 1;
        }
    }

    // Military Travel Methods
    public function startMilitaryTravel($attackerSettlementId, $defenderSettlementId, $units) {
        if ($this->connectionFailed) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            // Calculate distance
            $distance = $this->calculateDistance($attackerSettlementId, $defenderSettlementId);
            
            // Get slowest unit speed to determine travel time
            $slowestSpeed = $this->calculateMilitaryTravelSpeed($units);
            
            // Calculate arrival time
            $travelTimeSeconds = $distance * $slowestSpeed;
            $arrivalTime = date('Y-m-d H:i:s', time() + $travelTimeSeconds);

            // Insert traveling army record
            $sql = "INSERT INTO TravelingArmies 
                    (attackerSettlementId, defenderSettlementId, guardsCount, soldiersCount, 
                     archersCount, cavalryCount, startTime, arrivalTime, distance, travelSpeed) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                $attackerSettlementId,
                $defenderSettlementId,
                $units['guards'] ?? 0,
                $units['soldiers'] ?? 0,
                $units['archers'] ?? 0,
                $units['cavalry'] ?? 0,
                $arrivalTime,
                $distance,
                $slowestSpeed
            ]);

            if ($success) {
                $travelId = $this->conn->lastInsertId();
                
                // Remove units from attacking settlement
                $this->removeUnitsFromSettlement($attackerSettlementId, $units);
                
                return [
                    'success' => true, 
                    'travelId' => $travelId,
                    'arrivalTime' => $arrivalTime,
                    'travelTimeSeconds' => $travelTimeSeconds,
                    'message' => "Army is traveling. Arrival time: " . date('Y-m-d H:i:s', time() + $travelTimeSeconds)
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to start military travel'];
            }
        } catch (Exception $e) {
            error_log("Error starting military travel: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error starting travel: ' . $e->getMessage()];
        }
    }

    private function calculateMilitaryTravelSpeed($units) {
        if ($this->connectionFailed) {
            return 5; // default speed
        }

        try {
            $slowestSpeed = 2; // minimum speed
            
            $sql = "SELECT unitType, speed FROM MilitaryUnitConfig WHERE level = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $unitSpeeds = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($units as $unitType => $count) {
                if ($count > 0 && isset($unitSpeeds[$unitType])) {
                    $slowestSpeed = max($slowestSpeed, (int)$unitSpeeds[$unitType]);
                }
            }
            
            return $slowestSpeed;
        } catch (Exception $e) {
            error_log("Error calculating military travel speed: " . $e->getMessage());
            return 5;
        }
    }

    private function removeUnitsFromSettlement($settlementId, $units) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            foreach ($units as $unitType => $count) {
                if ($count > 0) {
                    $sql = "UPDATE MilitaryUnits SET count = GREATEST(0, count - ?) 
                            WHERE settlementId = ? AND unitType = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$count, $settlementId, $unitType]);
                }
            }
            return true;
        } catch (Exception $e) {
            error_log("Error removing units: " . $e->getMessage());
            return false;
        }
    }

    // Trade Travel Methods
    public function startTradeTravel($fromSettlementId, $toSettlementId, $resources, $tradeType, $offerId = null) {
        if ($this->connectionFailed) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            // Calculate distance and travel time
            $distance = $this->calculateDistance($fromSettlementId, $toSettlementId);
            $tradeSpeed = $this->getTravelConfig('trade');
            $travelTimeSeconds = $distance * $tradeSpeed;
            $arrivalTime = date('Y-m-d H:i:s', time() + $travelTimeSeconds);

            // Insert traveling trade record
            $sql = "INSERT INTO TravelingTrades 
                    (fromSettlementId, toSettlementId, woodAmount, stoneAmount, oreAmount, 
                     goldAmount, startTime, arrivalTime, distance, travelSpeed, tradeType, originalOfferId) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                $fromSettlementId,
                $toSettlementId,
                $resources['wood'] ?? 0,
                $resources['stone'] ?? 0,
                $resources['ore'] ?? 0,
                $resources['gold'] ?? 0,
                $arrivalTime,
                $distance,
                $tradeSpeed,
                $tradeType,
                $offerId
            ]);

            if ($success) {
                $travelId = $this->conn->lastInsertId();
                
                return [
                    'success' => true, 
                    'travelId' => $travelId,
                    'arrivalTime' => $arrivalTime,
                    'travelTimeSeconds' => $travelTimeSeconds,
                    'message' => "Trade is traveling. Arrival time: " . date('Y-m-d H:i:s', time() + $travelTimeSeconds)
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to start trade travel'];
            }
        } catch (Exception $e) {
            error_log("Error starting trade travel: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error starting trade travel: ' . $e->getMessage()];
        }
    }

    // Get Traveling Status Methods
    public function getTravelingArmies($settlementId) {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT ta.*, 
                           s1.name as attackerName, 
                           s2.name as defenderName,
                           TIMESTAMPDIFF(SECOND, NOW(), ta.arrivalTime) as timeRemaining
                    FROM TravelingArmies ta
                    LEFT JOIN Settlement s1 ON ta.attackerSettlementId = s1.settlementId
                    LEFT JOIN Settlement s2 ON ta.defenderSettlementId = s2.settlementId
                    WHERE (ta.attackerSettlementId = ? OR ta.defenderSettlementId = ?) 
                    AND ta.status = 'traveling'
                    ORDER BY ta.arrivalTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId, $settlementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting traveling armies: " . $e->getMessage());
            return [];
        }
    }

    public function getTravelingTrades($settlementId) {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT tt.*, 
                           s1.name as fromName, 
                           s2.name as toName,
                           TIMESTAMPDIFF(SECOND, NOW(), tt.arrivalTime) as timeRemaining
                    FROM TravelingTrades tt
                    LEFT JOIN Settlement s1 ON tt.fromSettlementId = s1.settlementId
                    LEFT JOIN Settlement s2 ON tt.toSettlementId = s2.settlementId
                    WHERE (tt.fromSettlementId = ? OR tt.toSettlementId = ?) 
                    AND tt.status = 'traveling'
                    ORDER BY tt.arrivalTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId, $settlementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting traveling trades: " . $e->getMessage());
            return [];
        }
    }

    // Process Arrivals (called by cron/scheduler)
    public function processArrivals() {
        if ($this->connectionFailed) {
            return ['processed' => 0];
        }

        $processed = 0;
        
        try {
            // Process arrived armies
            $processed += $this->processArrivedArmies();
            
            // Process arrived trades
            $processed += $this->processArrivedTrades();
            
            return ['processed' => $processed];
        } catch (Exception $e) {
            error_log("Error processing arrivals: " . $e->getMessage());
            return ['processed' => 0];
        }
    }

    private function processArrivedArmies() {
        try {
            $sql = "SELECT * FROM TravelingArmies WHERE arrivalTime <= NOW() AND status = 'traveling'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $arrivedArmies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            foreach ($arrivedArmies as $army) {
                // Execute the battle
                $units = [
                    'guards' => $army['guardsCount'],
                    'soldiers' => $army['soldiersCount'],
                    'archers' => $army['archersCount'],
                    'cavalry' => $army['cavalryCount']
                ];
                
                // Use existing battle system to execute the attack
                // This would call the same battle logic but with the traveling units
                $this->executeBattleOnArrival($army['attackerSettlementId'], $army['defenderSettlementId'], $units);
                
                // Mark army as arrived
                $updateSql = "UPDATE TravelingArmies SET status = 'arrived' WHERE travelId = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([$army['travelId']]);
                
                $processed++;
            }
            
            return $processed;
        } catch (Exception $e) {
            error_log("Error processing arrived armies: " . $e->getMessage());
            return 0;
        }
    }

    private function processArrivedTrades() {
        try {
            $sql = "SELECT * FROM TravelingTrades WHERE arrivalTime <= NOW() AND status = 'traveling'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $arrivedTrades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            foreach ($arrivedTrades as $trade) {
                // Process the trade delivery
                $this->executeTradeOnArrival($trade);
                
                // Mark trade as arrived/completed
                $updateSql = "UPDATE TravelingTrades SET status = 'completed' WHERE travelId = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->execute([$trade['travelId']]);
                
                $processed++;
            }
            
            return $processed;
        } catch (Exception $e) {
            error_log("Error processing arrived trades: " . $e->getMessage());
            return 0;
        }
    }

    private function executeBattleOnArrival($attackerSettlementId, $defenderSettlementId, $units) {
        // This would integrate with the existing battle system
        // For now, we'll create a placeholder that calls the existing attackSettlement method
        try {
            // Note: This requires integration with the existing Database class battle method
            // We'll need to modify this once we integrate with the main Database class
            return true;
        } catch (Exception $e) {
            error_log("Error executing battle on arrival: " . $e->getMessage());
            return false;
        }
    }

    private function executeTradeOnArrival($trade) {
        try {
            // Add resources to the destination settlement
            $sql = "UPDATE Settlement SET 
                        wood = wood + ?, 
                        stone = stone + ?, 
                        ore = ore + ? 
                    WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $trade['woodAmount'],
                $trade['stoneAmount'], 
                $trade['oreAmount'],
                $trade['toSettlementId']
            ]);
            
            // Handle gold if applicable
            if ($trade['goldAmount'] > 0) {
                $goldSql = "UPDATE Spieler p 
                           INNER JOIN Settlement s ON p.playerId = s.playerId 
                           SET p.gold = p.gold + ? 
                           WHERE s.settlementId = ?";
                $goldStmt = $this->conn->prepare($goldSql);
                $goldStmt->execute([$trade['goldAmount'], $trade['toSettlementId']]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error executing trade on arrival: " . $e->getMessage());
            return false;
        }
    }

    // Admin Methods
    public function getAllTravelingArmies() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT ta.*, 
                           s1.name as attackerName, 
                           s2.name as defenderName,
                           TIMESTAMPDIFF(SECOND, NOW(), ta.arrivalTime) as timeRemaining
                    FROM TravelingArmies ta
                    LEFT JOIN Settlement s1 ON ta.attackerSettlementId = s1.settlementId
                    LEFT JOIN Settlement s2 ON ta.defenderSettlementId = s2.settlementId
                    WHERE ta.status = 'traveling'
                    ORDER BY ta.arrivalTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all traveling armies: " . $e->getMessage());
            return [];
        }
    }

    public function getAllTravelingTrades() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT tt.*, 
                           s1.name as fromName, 
                           s2.name as toName,
                           TIMESTAMPDIFF(SECOND, NOW(), tt.arrivalTime) as timeRemaining
                    FROM TravelingTrades tt
                    LEFT JOIN Settlement s1 ON tt.fromSettlementId = s1.settlementId
                    LEFT JOIN Settlement s2 ON tt.toSettlementId = s2.settlementId
                    WHERE tt.status = 'traveling'
                    ORDER BY tt.arrivalTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting all traveling trades: " . $e->getMessage());
            return [];
        }
    }

    public function getMilitaryUnitConfig() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT * FROM MilitaryUnitConfig ORDER BY unitType, level";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting military unit config: " . $e->getMessage());
            return [];
        }
    }

    public function updateMilitaryUnitConfig($unitType, $level, $field, $value) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $allowedFields = ['speed', 'lootAmount', 'costWood', 'costStone', 'costOre', 'costGold', 'costSettlers', 'trainingTime', 'defensePower', 'attackPower', 'rangedPower'];
            
            if (!in_array($field, $allowedFields)) {
                return false;
            }

            $sql = "UPDATE MilitaryUnitConfig SET $field = ? WHERE unitType = ? AND level = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$value, $unitType, $level]);
        } catch (Exception $e) {
            error_log("Error updating military unit config: " . $e->getMessage());
            return false;
        }
    }
}