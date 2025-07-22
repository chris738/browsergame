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
        // This integrates with the existing battle system
        try {
            // We need to add the traveling units back to the attacker settlement temporarily for the battle calculation
            foreach ($units as $unitType => $count) {
                if ($count > 0) {
                    $sql = "INSERT INTO MilitaryUnits (settlementId, unitType, count) 
                            VALUES (?, ?, ?) 
                            ON DUPLICATE KEY UPDATE count = count + ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$attackerSettlementId, $unitType, $count, $count]);
                }
            }
            
            // Now execute the battle using the same logic as immediate attacks
            // We'll simulate the battle calculation here since we can't directly call the Database class method
            
            // Get military power for both sides
            $attackerPowerResult = $this->getSettlementMilitaryPowerForBattle($attackerSettlementId);
            $defenderPowerResult = $this->getSettlementMilitaryPowerForBattle($defenderSettlementId);
            
            // Calculate battle outcome
            $battleResult = $this->calculateBattleOutcome($attackerPowerResult, $defenderPowerResult);
            
            // Apply unit losses
            $this->applyBattleLosses($attackerSettlementId, $defenderSettlementId, $battleResult, $units);
            
            // Apply resource plunder if attacker wins
            if ($battleResult['winner'] === 'attacker') {
                $this->applyResourcePlunder($attackerSettlementId, $defenderSettlementId, $units);
            }
            
            // Record battle in history
            $this->recordBattle($attackerSettlementId, $defenderSettlementId, $units, $battleResult);
            
            return true;
        } catch (Exception $e) {
            error_log("Error executing battle on arrival: " . $e->getMessage());
            return false;
        }
    }
    
    private function getSettlementMilitaryPowerForBattle($settlementId) {
        try {
            $sql = "SELECT 
                        SUM(mu.count * muc.attackPower) as totalAttack,
                        SUM(mu.count * muc.defensePower) as totalDefense,
                        SUM(mu.count * muc.rangedPower) as totalRanged,
                        SUM(mu.count) as totalUnits
                    FROM MilitaryUnits mu
                    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
                    WHERE mu.settlementId = ? AND muc.level = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $unitsSql = "SELECT unitType, count FROM MilitaryUnits WHERE settlementId = ?";
            $unitsStmt = $this->conn->prepare($unitsSql);
            $unitsStmt->execute([$settlementId]);
            $unitsData = $unitsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            return [
                'totalAttack' => (int)($result['totalAttack'] ?? 0),
                'totalDefense' => (int)($result['totalDefense'] ?? 0),
                'totalRanged' => (int)($result['totalRanged'] ?? 0),
                'totalUnits' => (int)($result['totalUnits'] ?? 0),
                'units' => $unitsData ?: []
            ];
        } catch (Exception $e) {
            error_log("Error getting military power for battle: " . $e->getMessage());
            return ['totalAttack' => 0, 'totalDefense' => 0, 'totalRanged' => 0, 'totalUnits' => 0, 'units' => []];
        }
    }
    
    private function calculateBattleOutcome($attackerPower, $defenderPower) {
        $attackerTotal = $attackerPower['totalAttack'] + $attackerPower['totalRanged'];
        $defenderTotal = $defenderPower['totalDefense'] + ($defenderPower['totalAttack'] * 0.5); // Defenders get some attack bonus
        
        // Add randomness
        $attackerRoll = $attackerTotal * (0.8 + (mt_rand(0, 40) / 100));
        $defenderRoll = $defenderTotal * (0.8 + (mt_rand(0, 40) / 100));
        
        $winner = $attackerRoll > $defenderRoll ? 'attacker' : 'defender';
        
        // Calculate loss rates based on power difference
        $powerRatio = $attackerTotal > 0 ? $defenderTotal / $attackerTotal : 1;
        $attackerLossRate = min(0.8, 0.2 + ($powerRatio * 0.3));
        $defenderLossRate = min(0.8, 0.2 + ((1 / max($powerRatio, 0.1)) * 0.3));
        
        return [
            'winner' => $winner,
            'attackerLossRate' => $attackerLossRate,
            'defenderLossRate' => $defenderLossRate,
            'attackerRoll' => $attackerRoll,
            'defenderRoll' => $defenderRoll
        ];
    }
    
    private function applyBattleLosses($attackerSettlementId, $defenderSettlementId, $battleResult, $attackingUnits) {
        try {
            // Apply losses to attacking units
            foreach ($attackingUnits as $unitType => $count) {
                if ($count > 0) {
                    $losses = (int)($count * $battleResult['attackerLossRate']);
                    $survivors = $count - $losses;
                    
                    if ($survivors > 0) {
                        // Add survivors back to attacker
                        $sql = "INSERT INTO MilitaryUnits (settlementId, unitType, count) 
                                VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE count = count + ?";
                        $stmt = $this->conn->prepare($sql);
                        $stmt->execute([$attackerSettlementId, $unitType, $survivors, $survivors]);
                    }
                }
            }
            
            // Apply losses to defending units
            $defenderUnits = $this->getMilitaryUnits($defenderSettlementId);
            foreach ($defenderUnits as $unitType => $count) {
                if ($count > 0) {
                    $losses = (int)($count * $battleResult['defenderLossRate']);
                    $sql = "UPDATE MilitaryUnits SET count = GREATEST(0, count - ?) 
                            WHERE settlementId = ? AND unitType = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$losses, $defenderSettlementId, $unitType]);
                }
            }
        } catch (Exception $e) {
            error_log("Error applying battle losses: " . $e->getMessage());
        }
    }
    
    private function getMilitaryUnits($settlementId) {
        try {
            $sql = "SELECT unitType, count FROM MilitaryUnits WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function applyResourcePlunder($attackerSettlementId, $defenderSettlementId, $attackingUnits) {
        try {
            // Calculate total loot capacity based on attacking units
            $totalLootCapacity = 0;
            foreach ($attackingUnits as $unitType => $count) {
                if ($count > 0) {
                    $sql = "SELECT lootAmount FROM MilitaryUnitConfig WHERE unitType = ? AND level = 1";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$unitType]);
                    $lootAmount = $stmt->fetchColumn();
                    $totalLootCapacity += $count * ($lootAmount ?: 10);
                }
            }
            
            // Get defender's resources
            $sql = "SELECT wood, stone, ore FROM Settlement WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$defenderSettlementId]);
            $defenderResources = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($defenderResources) {
                // Calculate what can be plundered (limited by loot capacity and available resources)
                $maxPlunderPercent = 0.3; // Max 30% of resources
                $woodPlunder = min($totalLootCapacity * 0.4, $defenderResources['wood'] * $maxPlunderPercent);
                $stonePlunder = min($totalLootCapacity * 0.3, $defenderResources['stone'] * $maxPlunderPercent);
                $orePlunder = min($totalLootCapacity * 0.3, $defenderResources['ore'] * $maxPlunderPercent);
                
                // Remove resources from defender
                $sql = "UPDATE Settlement SET 
                        wood = GREATEST(0, wood - ?),
                        stone = GREATEST(0, stone - ?),
                        ore = GREATEST(0, ore - ?)
                        WHERE settlementId = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$woodPlunder, $stonePlunder, $orePlunder, $defenderSettlementId]);
                
                // Add resources to attacker
                $sql = "UPDATE Settlement SET 
                        wood = wood + ?,
                        stone = stone + ?,
                        ore = ore + ?
                        WHERE settlementId = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$woodPlunder, $stonePlunder, $orePlunder, $attackerSettlementId]);
            }
        } catch (Exception $e) {
            error_log("Error applying resource plunder: " . $e->getMessage());
        }
    }
    
    private function recordBattle($attackerSettlementId, $defenderSettlementId, $attackingUnits, $battleResult) {
        try {
            // Record battle in Battles table if it exists
            $sql = "INSERT INTO Battles (attackerSettlementId, defenderSettlementId, attackerUnitsTotal, 
                    defenderUnitsTotal, winner, battleResult) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $attackerSettlementId,
                $defenderSettlementId,
                json_encode($attackingUnits),
                json_encode([]), // We don't have the original defender units
                $battleResult['winner'],
                json_encode($battleResult)
            ]);
        } catch (Exception $e) {
            // Battle table might not exist, that's okay
            error_log("Could not record battle (table might not exist): " . $e->getMessage());
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