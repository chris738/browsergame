<?php

require_once __DIR__ . '/../../sql-data-validator.php';

class ResourceRepository {
    private $conn;
    private $connectionFailed;

    public function __construct($connection, $connectionFailed = false) {
        $this->conn = $connection;
        $this->connectionFailed = $connectionFailed;
    }

    public function getResources($settlementId) {
        // Validate input
        try {
            $settlementId = SQLDataValidator::validateId($settlementId, 'Settlement ID');
        } catch (InvalidArgumentException $e) {
            SQLDataValidator::logValidationError('getResources input', $e->getMessage());
            return false;
        }

        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return [
                'wood' => 5000,        // Sufficient for building upgrades including Kaserne
                'stone' => 5000,       // Sufficient for building upgrades including Kaserne  
                'ore' => 5000,         // Sufficient for building upgrades including Kaserne
                'storageCapacity' => 12100, // Simulate upgraded Lager level 3 (not hardcoded to 10k)
                'maxSettlers' => 100,
                'freeSettlers' => 50   // Sufficient for settler requirements
            ];
        }

        try {
            // Use the enhanced SettlementResources view for simplified access
            $sql = "
                SELECT 
                    sr.wood, 
                    sr.stone, 
                    sr.ore, 
                    sr.storageCapacity,
                    ss.maxSettlers,
                    ss.freeSettlers
                FROM 
                    SettlementResources sr
                LEFT JOIN 
                    SettlementSettlers ss
                ON 
                    sr.settlementId = ss.settlementId
                WHERE 
                    sr.settlementId = :settlementId";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
            $stmt->execute();
            $resources = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Validate output data
            if ($resources) {
                try {
                    SQLDataValidator::validateResourceData($resources);
                } catch (InvalidArgumentException $e) {
                    SQLDataValidator::logValidationError('getResources output', $e->getMessage());
                    // Log the actual data for debugging
                    error_log("Invalid resource data for settlement $settlementId: " . json_encode($resources));
                    return false;
                }
            }
            
            return $resources;
        } catch (PDOException $e) {
            SQLDataValidator::logValidationError('getResources SQL', $e->getMessage());
            error_log("SQL error in getResources for settlement $settlementId: " . $e->getMessage());
            return false;
        }
    }

    public function getRegen($settlementId) {
        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return [
                'woodProductionRate' => 10,
                'stoneProductionRate' => 5,
                'oreProductionRate' => 2
            ];
        }

        // Use the enhanced SettlementResources view that includes production rates
        $sql = "
            SELECT 
                woodProduction AS woodProductionRate,
                stoneProduction AS stoneProductionRate,
                oreProduction AS oreProductionRate
            FROM SettlementResources
            WHERE settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
}

?>