<?php

require_once __DIR__ . '/../../sql-data-validator.php';

class BuildingRepository {
    private $conn;
    private $connectionFailed;
    private $schemaManager;

    public function __construct($connection, $connectionFailed = false, $schemaManager = null) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
        $this->schemaManager = $schemaManager;
    }

    public function getBuilding($settlementId, $buildingType) {
        // Validate inputs
        try {
            $settlementId = SQLDataValidator::validateId($settlementId, 'Settlement ID');
            $buildingType = SQLDataValidator::validateBuildingType($buildingType);
        } catch (InvalidArgumentException $e) {
            SQLDataValidator::logValidationError('getBuilding input', $e->getMessage());
            throw new Exception("Invalid input parameters: " . $e->getMessage());
        }

        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            // Return appropriate mock data based on building type
            if ($buildingType === 'Kaserne') {
                return [
                    'currentLevel' => 1,
                    'nextLevel' => 2,
                    'costWood' => 165,      // Level 2 cost for Kaserne (150 * 1.1)
                    'costStone' => 165,     // Level 2 cost for Kaserne (150 * 1.1)
                    'costOre' => 220,       // Level 2 cost for Kaserne (200 * 1.1)
                    'settlers' => 2.2,      // Level 2 cost for Kaserne (2 * 1.1)
                    'buildTime' => 60       // Level 2 build time for Kaserne
                ];
            } else {
                return [
                    'currentLevel' => 1,
                    'nextLevel' => 2,
                    'costWood' => 110,      // Standard level 2 cost (100 * 1.1)
                    'costStone' => 110,     // Standard level 2 cost (100 * 1.1)
                    'costOre' => 110,       // Standard level 2 cost (100 * 1.1)
                    'settlers' => 1.1,      // Standard level 2 cost (1 * 1.1)
                    'buildTime' => 40       // Standard level 2 build time
                ];
            }
        }

        try {
            // First check if the building exists in the Buildings table
            $sql = "
                SELECT 
                    b.currentLevel,
                    b.nextLevel, 
                    b.costWood, 
                    b.costStone, 
                    b.costOre, 
                    b.settlers,
                    b.buildTime
                FROM BuildingDetails b
                WHERE b.settlementId = :settlementId AND b.buildingType = :buildingType";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Check if building exists in Buildings table but not in BuildingDetails (max level case)
                $buildingSql = "SELECT level FROM Buildings WHERE settlementId = :settlementId AND buildingType = :buildingType";
                $buildingStmt = $this->conn->prepare($buildingSql);
                $buildingStmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                $buildingStmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                $buildingStmt->execute();
                $buildingResult = $buildingStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($buildingResult) {
                    // Building exists but not in BuildingDetails - this means it's at max level
                    return [
                        'currentLevel' => $buildingResult['level'],
                        'nextLevel' => $buildingResult['level'] + 1, // Not upgradeable but we provide next level for completeness
                        'costWood' => 0,
                        'costStone' => 0,
                        'costOre' => 0,
                        'settlers' => 0,
                        'buildTime' => 0
                    ];
                }
                
                // Building doesn't exist yet - return level 0 with costs for level 1
                $configSql = "SELECT costWood, costStone, costOre, settlers, buildTime 
                             FROM BuildingConfig 
                             WHERE buildingType = :buildingType AND level = 1";
                $configStmt = $this->conn->prepare($configSql);
                $configStmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                $configStmt->execute();
                $config = $configStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$config) {
                    throw new Exception("Gebäude-Konfiguration für '$buildingType' Level 1 nicht gefunden.");
                }
                
                // Apply Town Hall build time reduction
                $townHallLevel = 0;
                $thSql = "SELECT level FROM Buildings WHERE settlementId = :settlementId AND buildingType = 'Rathaus'";
                $thStmt = $this->conn->prepare($thSql);
                $thStmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                $thStmt->execute();
                $thResult = $thStmt->fetch(PDO::FETCH_ASSOC);
                if ($thResult) {
                    $townHallLevel = $thResult['level'];
                }
                
                $buildTimeReduction = max(0.1, 1.0 - ($townHallLevel * 0.05));
                $adjustedBuildTime = round($config['buildTime'] * $buildTimeReduction);
                
                return [
                    'currentLevel' => 0,
                    'nextLevel' => 1,
                    'costWood' => $config['costWood'],
                    'costStone' => $config['costStone'],
                    'costOre' => $config['costOre'],
                    'settlers' => $config['settlers'],
                    'buildTime' => $adjustedBuildTime
                ];
            }
            
            // Validate output data
            try {
                SQLDataValidator::validateBuildingData($result);
            } catch (InvalidArgumentException $e) {
                SQLDataValidator::logValidationError('getBuilding output', $e->getMessage());
                error_log("Invalid building data for settlement $settlementId, building $buildingType: " . json_encode($result));
                throw new Exception("Building data validation failed: " . $e->getMessage());
            }
        
            return $result;
        } catch (PDOException $e) {
            SQLDataValidator::logValidationError('getBuilding SQL', $e->getMessage());
            error_log("SQL error in getBuilding for settlement $settlementId, building $buildingType: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function upgradeBuilding($settlementId, $buildingType) {
        // Return mock success response if database connection failed
        if ($this->connectionFailed) {
            return [
                'success' => true,
                'message' => "Das Gebäude '$buildingType' wurde erfolgreich upgegradet (Mock-Modus)."
            ];
        }

        // Ensure schema is initialized
        if ($this->schemaManager) {
            $this->schemaManager->initializeSchemaIfNeeded();
        }

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
            if ($e->getCode() == '1305') { // PROCEDURE does not exist
                error_log("UpgradeBuilding procedure missing, attempting to reinitialize schema...");
                if ($this->schemaManager) {
                    $this->schemaManager->initializeSchemaIfNeeded();
                }
                
                // Try again after schema initialization
                try {
                    $stmt = $this->conn->prepare($sql);
                    $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
                    $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
                    $stmt->execute();
                    
                    return [
                        'success' => true,
                        'message' => "Das Gebäude '$buildingType' wurde erfolgreich upgegradet (nach Schema-Initialisierung)."
                    ];
                } catch (PDOException $e2) {
                    return [
                        'success' => false,
                        'message' => "Fehler beim Upgrade in database.php: " . $e2->getMessage()
                    ];
                }
            }
            
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

    // Building Configuration Management Methods
    public function getAllBuildingConfigs() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "SELECT * FROM BuildingConfig ORDER BY buildingType, level";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch building configs: " . $e->getMessage());
            return [];
        }
    }

    public function getBuildingConfig($buildingType, $level) {
        if ($this->connectionFailed) {
            return null;
        }

        try {
            $sql = "SELECT * FROM BuildingConfig WHERE buildingType = :buildingType AND level = :level";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch building config: " . $e->getMessage());
            return null;
        }
    }

    public function updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $sql = "UPDATE BuildingConfig 
                    SET costWood = :costWood, costStone = :costStone, costOre = :costOre, 
                        settlers = :settlers, productionRate = :productionRate, buildTime = :buildTime 
                    WHERE buildingType = :buildingType AND level = :level";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':costWood', $costWood, PDO::PARAM_STR);
            $stmt->bindParam(':costStone', $costStone, PDO::PARAM_STR);
            $stmt->bindParam(':costOre', $costOre, PDO::PARAM_STR);
            $stmt->bindParam(':settlers', $settlers, PDO::PARAM_STR);
            $stmt->bindParam(':productionRate', $productionRate, PDO::PARAM_STR);
            $stmt->bindParam(':buildTime', $buildTime, PDO::PARAM_INT);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to update building config: " . $e->getMessage());
            return false;
        }
    }

    public function createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $sql = "INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) 
                    VALUES (:buildingType, :level, :costWood, :costStone, :costOre, :settlers, :productionRate, :buildTime)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->bindParam(':costWood', $costWood, PDO::PARAM_STR);
            $stmt->bindParam(':costStone', $costStone, PDO::PARAM_STR);
            $stmt->bindParam(':costOre', $costOre, PDO::PARAM_STR);
            $stmt->bindParam(':settlers', $settlers, PDO::PARAM_STR);
            $stmt->bindParam(':productionRate', $productionRate, PDO::PARAM_STR);
            $stmt->bindParam(':buildTime', $buildTime, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to create building config: " . $e->getMessage());
            return false;
        }
    }

    public function deleteBuildingConfig($buildingType, $level) {
        if ($this->connectionFailed) {
            return false;
        }

        try {
            $sql = "DELETE FROM BuildingConfig WHERE buildingType = :buildingType AND level = :level";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
            $stmt->bindParam(':level', $level, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Failed to delete building config: " . $e->getMessage());
            return false;
        }
    }

    public function getDistinctBuildingTypes() {
        if ($this->connectionFailed) {
            // Return default building types if database connection failed
            return [
                ['buildingType' => 'Rathaus'],
                ['buildingType' => 'Holzfäller'],
                ['buildingType' => 'Steinbruch'],
                ['buildingType' => 'Erzbergwerk'],
                ['buildingType' => 'Lager'],
                ['buildingType' => 'Farm'],
                ['buildingType' => 'Markt'],
                ['buildingType' => 'Kaserne']
            ];
        }

        try {
            $sql = "SELECT DISTINCT buildingType FROM BuildingConfig ORDER BY buildingType";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch distinct building types: " . $e->getMessage());
            // Return default building types as fallback
            return [
                ['buildingType' => 'Rathaus'],
                ['buildingType' => 'Holzfäller'],
                ['buildingType' => 'Steinbruch'],
                ['buildingType' => 'Erzbergwerk'],
                ['buildingType' => 'Lager'],
                ['buildingType' => 'Farm'],
                ['buildingType' => 'Markt'],
                ['buildingType' => 'Kaserne']
            ];
        }
    }
}

?>