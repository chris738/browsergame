<?php

class MilitaryRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    // Military Unit Methods
    public function getMilitaryUnits($settlementId) {
        if ($this->connectionFailed) {
            return ['guards' => 0, 'soldiers' => 0, 'archers' => 0, 'cavalry' => 0];
        }

        try {
            $sql = "SELECT unitType, count FROM MilitaryUnits WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = ['guards' => 0, 'soldiers' => 0, 'archers' => 0, 'cavalry' => 0];
            foreach ($units as $unit) {
                $result[$unit['unitType']] = (int)$unit['count'];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error getting military units: " . $e->getMessage());
            return ['guards' => 0, 'soldiers' => 0, 'archers' => 0, 'cavalry' => 0];
        }
    }

    public function getMilitaryTrainingQueue($settlementId) {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT * FROM OpenMilitaryTrainingQueue WHERE settlementId = ? ORDER BY endTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting military training queue: " . $e->getMessage());
            return [];
        }
    }

    public function getMilitaryStats($settlementId) {
        if ($this->connectionFailed) {
            return ['totalDefense' => 0, 'totalAttack' => 0, 'totalUnits' => 0, 'rangedPower' => 0];
        }

        try {
            $sql = "SELECT 
                        SUM(mu.count * muc.defensePower) as totalDefense,
                        SUM(mu.count * muc.attackPower) as totalAttack,
                        SUM(mu.count) as totalUnits,
                        SUM(mu.count * muc.rangedPower) as rangedPower
                    FROM MilitaryUnits mu
                    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
                    WHERE mu.settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'totalDefense' => (int)($stats['totalDefense'] ?? 0),
                'totalAttack' => (int)($stats['totalAttack'] ?? 0),
                'totalUnits' => (int)($stats['totalUnits'] ?? 0),
                'rangedPower' => (int)($stats['rangedPower'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log("Error getting military stats: " . $e->getMessage());
            return ['totalDefense' => 0, 'totalAttack' => 0, 'totalUnits' => 0, 'rangedPower' => 0];
        }
    }

    public function trainMilitaryUnit($settlementId, $unitType, $count) {
        if ($this->connectionFailed) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $stmt = $this->conn->prepare("CALL TrainMilitaryUnit(?, ?, ?)");
            $stmt->execute([$settlementId, $unitType, $count]);
            
            return ['success' => true, 'message' => "Successfully started training $count $unitType"];
        } catch (Exception $e) {
            error_log("Error training military unit: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'Not enough resources') !== false) {
                return ['success' => false, 'message' => 'Not enough resources for unit training'];
            }
            
            return ['success' => false, 'message' => 'Failed to train unit: ' . $e->getMessage()];
        }
    }

    // Research System Functions
    public function getUnitResearch($settlementId) {
        if ($this->connectionFailed) {
            return ['guards' => false, 'soldiers' => false, 'archers' => false, 'cavalry' => false];
        }

        try {
            $sql = "SELECT unitType, isResearched FROM UnitResearch WHERE settlementId = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            $research = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result = ['guards' => false, 'soldiers' => false, 'archers' => false, 'cavalry' => false];
            foreach ($research as $unit) {
                $result[$unit['unitType']] = (bool)$unit['isResearched'];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error getting unit research: " . $e->getMessage());
            return ['guards' => false, 'soldiers' => false, 'archers' => false, 'cavalry' => false];
        }
    }

    public function getResearchQueue($settlementId) {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT * FROM OpenResearchQueue WHERE settlementId = ? ORDER BY endTime ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settlementId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting research queue: " . $e->getMessage());
            return [];
        }
    }

    public function getResearchConfig() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT * FROM ResearchConfig ORDER BY FIELD(unitType, 'guards', 'soldiers', 'archers', 'cavalry')";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting research config: " . $e->getMessage());
            return [];
        }
    }

    public function startResearch($settlementId, $unitType) {
        if ($this->connectionFailed) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        try {
            $stmt = $this->conn->prepare("CALL StartResearch(?, ?)");
            $stmt->execute([$settlementId, $unitType]);
            
            return ['success' => true, 'message' => "Successfully started research for $unitType"];
        } catch (Exception $e) {
            error_log("Error starting research: " . $e->getMessage());
            
            if (strpos($e->getMessage(), 'already researched') !== false) {
                return ['success' => false, 'message' => 'Unit is already researched'];
            } elseif (strpos($e->getMessage(), 'already being researched') !== false) {
                return ['success' => false, 'message' => 'Unit is already being researched'];
            } elseif (strpos($e->getMessage(), 'Not enough resources') !== false) {
                return ['success' => false, 'message' => 'Not enough resources for research'];
            } elseif (strpos($e->getMessage(), 'Prerequisite unit') !== false) {
                return ['success' => false, 'message' => 'Prerequisite unit must be researched first'];
            }
            
            return ['success' => false, 'message' => 'Failed to start research: ' . $e->getMessage()];
        }
    }
}

?>