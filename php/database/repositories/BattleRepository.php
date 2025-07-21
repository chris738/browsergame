<?php

class BattleRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    /**
     * Get military power of a settlement
     */
    public function getSettlementMilitaryPower($settlementId) {
        if ($this->connectionFailed) {
            // Provide demo data for testing when database is unavailable
            $demoUnits = [
                'guards' => 5,
                'soldiers' => 8, 
                'archers' => 6,
                'cavalry' => 3
            ];
            
            // Unit stats: [attack, defense, ranged]
            $unitStats = [
                'guards' => [0, 2, 0],
                'soldiers' => [3, 2, 0],
                'archers' => [2, 1, 4],
                'cavalry' => [5, 2, 0]
            ];
            
            $totalAttack = $totalDefense = $totalRanged = 0;
            foreach ($demoUnits as $unitType => $count) {
                $stats = $unitStats[$unitType];
                $totalAttack += $count * $stats[0];
                $totalDefense += $count * $stats[1];
                $totalRanged += $count * $stats[2];
            }
            
            return [
                'totalAttack' => $totalAttack,
                'totalDefense' => $totalDefense,
                'totalRanged' => $totalRanged,
                'units' => $demoUnits
            ];
        }

        try {
            // Get units and their power
            $sql = "SELECT 
                        mu.unitType,
                        mu.count,
                        muc.attackPower,
                        muc.defensePower,
                        muc.rangedPower
                    FROM MilitaryUnits mu
                    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
                    WHERE mu.settlementId = ? AND mu.count > 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalAttack = 0;
            $totalDefense = 0;
            $totalRanged = 0;
            $unitCounts = ['guards' => 0, 'soldiers' => 0, 'archers' => 0, 'cavalry' => 0];
            
            foreach ($units as $unit) {
                $count = (int)$unit['count'];
                $unitCounts[$unit['unitType']] = $count;
                
                $totalAttack += $count * (int)$unit['attackPower'];
                $totalDefense += $count * (int)$unit['defensePower'];
                $totalRanged += $count * (int)$unit['rangedPower'];
            }
            
            return [
                'totalAttack' => $totalAttack,
                'totalDefense' => $totalDefense,
                'totalRanged' => $totalRanged,
                'units' => $unitCounts
            ];
        } catch (Exception $e) {
            error_log("Error getting settlement military power: " . $e->getMessage());
            return [
                'totalAttack' => 0,
                'totalDefense' => 0,
                'totalRanged' => 0,
                'units' => ['guards' => 0, 'soldiers' => 0, 'archers' => 0, 'cavalry' => 0]
            ];
        }
    }

    /**
     * Calculate battle outcome
     */
    public function calculateBattle($attackerPower, $defenderPower) {
        // Basic battle calculation algorithm
        $attackerTotal = $attackerPower['totalAttack'] + $attackerPower['totalRanged'];
        $defenderTotal = $defenderPower['totalDefense'] + ($defenderPower['totalRanged'] * 0.5); // Ranged units less effective on defense
        
        // Add some randomness (±20%)
        $randomFactor = 0.8 + (mt_rand() / mt_getrandmax()) * 0.4; // 0.8 to 1.2
        $attackerEffective = $attackerTotal * $randomFactor;
        
        $winner = $attackerEffective > $defenderTotal ? 'attacker' : 'defender';
        
        // Calculate casualties based on power difference
        $powerRatio = $winner === 'attacker' 
            ? $defenderTotal / max($attackerEffective, 1)
            : $attackerEffective / max($defenderTotal, 1);
        
        // Casualties: 10-50% for winner, 30-80% for loser
        $winnerLossRate = 0.1 + $powerRatio * 0.4;
        $loserLossRate = 0.3 + (1 - $powerRatio) * 0.5;
        
        $attackerLossRate = $winner === 'attacker' ? $winnerLossRate : $loserLossRate;
        $defenderLossRate = $winner === 'defender' ? $winnerLossRate : $loserLossRate;
        
        return [
            'winner' => $winner,
            'attackerLossRate' => min(0.8, max(0.1, $attackerLossRate)),
            'defenderLossRate' => min(0.8, max(0.1, $defenderLossRate)),
            'powerRatio' => $powerRatio,
            'attackerEffective' => $attackerEffective,
            'defenderEffective' => $defenderTotal
        ];
    }

    /**
     * Apply unit losses to a settlement
     */
    public function applyUnitLosses($settlementId, $unitLosses) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            
            foreach ($unitLosses as $unitType => $lossCount) {
                if ($lossCount > 0) {
                    $sql = "UPDATE MilitaryUnits 
                            SET count = GREATEST(0, count - ?) 
                            WHERE settlementId = ? AND unitType = ?";
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$lossCount, $settlementId, $unitType]);
                }
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error applying unit losses: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate and apply resource plunder
     */
    public function calculateResourcePlunder($defenderSettlementId, $battleResult) {
        if ($this->connectionFailed) {
            return ['wood' => 0, 'stone' => 0, 'ore' => 0];
        }

        try {
            // Get defender's resources
            $sql = "SELECT wood, stone, ore FROM Settlement WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$defenderSettlementId]);
            $resources = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$resources) {
                return ['wood' => 0, 'stone' => 0, 'ore' => 0];
            }
            
            // Plunder rate: 5-15% based on victory margin
            $plunderRate = 0.05 + (1 - $battleResult['powerRatio']) * 0.1;
            $plunderRate = min(0.15, max(0.05, $plunderRate));
            
            $plundered = [
                'wood' => (int)($resources['wood'] * $plunderRate),
                'stone' => (int)($resources['stone'] * $plunderRate),
                'ore' => (int)($resources['ore'] * $plunderRate)
            ];
            
            return $plundered;
        } catch (Exception $e) {
            error_log("Error calculating resource plunder: " . $e->getMessage());
            return ['wood' => 0, 'stone' => 0, 'ore' => 0];
        }
    }

    /**
     * Transfer resources between settlements
     */
    public function transferResources($fromSettlementId, $toSettlementId, $resources) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            
            // Remove from defender
            $sql = "UPDATE Settlement 
                    SET wood = GREATEST(0, wood - ?),
                        stone = GREATEST(0, stone - ?),
                        ore = GREATEST(0, ore - ?)
                    WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$resources['wood'], $resources['stone'], $resources['ore'], $fromSettlementId]);
            
            // Add to attacker
            $sql = "UPDATE Settlement 
                    SET wood = wood + ?,
                        stone = stone + ?,
                        ore = ore + ?
                    WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$resources['wood'], $resources['stone'], $resources['ore'], $toSettlementId]);
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error transferring resources: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record a battle in the database
     */
    public function recordBattle($attackerSettlementId, $defenderSettlementId, $battleData) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            
            // Insert battle record
            $sql = "INSERT INTO Battles (
                        attackerSettlementId, defenderSettlementId,
                        attackerUnitsTotal, defenderUnitsTotal,
                        attackerUnitsLost, defenderUnitsLost,
                        winner, resourcesPlundered, battleResult
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $attackerSettlementId,
                $defenderSettlementId,
                json_encode($battleData['attackerUnitsTotal']),
                json_encode($battleData['defenderUnitsTotal']),
                json_encode($battleData['attackerUnitsLost']),
                json_encode($battleData['defenderUnitsLost']),
                $battleData['winner'],
                json_encode($battleData['resourcesPlundered']),
                json_encode($battleData['battleResult'])
            ]);
            
            $battleId = $this->conn->lastInsertId();
            
            // Log battle start
            $this->addBattleLog($battleId, 'start', 'Battle initiated', [
                'attacker' => $attackerSettlementId,
                'defender' => $defenderSettlementId
            ]);
            
            $this->conn->commit();
            return $battleId;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error recording battle: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a battle log entry
     */
    public function addBattleLog($battleId, $logType, $message, $data = null) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $sql = "INSERT INTO BattleLogs (battleId, logType, logMessage, logData) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$battleId, $logType, $message, $data ? json_encode($data) : null]);
            return true;
        } catch (Exception $e) {
            error_log("Error adding battle log: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent battles for a settlement
     */
    public function getRecentBattles($settlementId, $limit = 10) {
        if ($this->connectionFailed) {
            // Provide demo battle history for testing
            $demoBattles = [
                [
                    'battleId' => 1,
                    'attackerSettlementId' => $settlementId,
                    'defenderSettlementId' => 2,
                    'battleTime' => date('Y-m-d H:i:s', time() - 3600), // 1 hour ago
                    'winner' => 'attacker',
                    'attackerName' => 'Your Settlement',
                    'defenderName' => 'Enemy Village'
                ],
                [
                    'battleId' => 2,
                    'attackerSettlementId' => 3,
                    'defenderSettlementId' => $settlementId,
                    'battleTime' => date('Y-m-d H:i:s', time() - 7200), // 2 hours ago
                    'winner' => 'defender',
                    'attackerName' => 'Rival Town',
                    'defenderName' => 'Your Settlement'
                ]
            ];
            
            return array_slice($demoBattles, 0, $limit);
        }

        try {
            $sql = "SELECT b.*, 
                           s1.settlementName as attackerName,
                           s2.settlementName as defenderName
                    FROM Battles b
                    INNER JOIN Settlement s1 ON b.attackerSettlementId = s1.settlementId
                    INNER JOIN Settlement s2 ON b.defenderSettlementId = s2.settlementId
                    WHERE b.attackerSettlementId = ? OR b.defenderSettlementId = ?
                    ORDER BY b.battleTime DESC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId, $settlementId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting recent battles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all settlements that can be attacked (excluding self)
     */
    public function getAttackableSettlements($attackerSettlementId) {
        if ($this->connectionFailed) {
            // Provide demo settlement data for testing
            $demoSettlements = [
                [
                    'settlementId' => 2,
                    'settlementName' => 'Enemy Village',
                    'coordinateX' => 1,
                    'coordinateY' => 2
                ],
                [
                    'settlementId' => 3,
                    'settlementName' => 'Rival Town',
                    'coordinateX' => -1,
                    'coordinateY' => 1
                ],
                [
                    'settlementId' => 4,
                    'settlementName' => 'Hostile Camp',
                    'coordinateX' => 2,
                    'coordinateY' => -1
                ]
            ];
            
            // Filter out attacker's own settlement
            return array_filter($demoSettlements, function($settlement) use ($attackerSettlementId) {
                return $settlement['settlementId'] != $attackerSettlementId;
            });
        }

        try {
            $sql = "SELECT settlementId, settlementName, coordinateX, coordinateY
                    FROM Settlement 
                    WHERE settlementId != ? 
                    ORDER BY settlementName";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$attackerSettlementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting attackable settlements: " . $e->getMessage());
            return [];
        }
    }
}

?>