<?php

interface DatabaseInterface {
    public function getResources($settlementId);
    public function getBuilding($settlementId, $buildingType);
    public function upgradeBuilding($settlementId, $buildingType);
    public function getRegen($settlementId);
}

class Database implements DatabaseInterface {
    private $host = 'localhost';
    private $dbname = 'browsergame';
    private $username = 'browsergame';
    private $password = 'sicheresPasswort';
    private $conn;

    public function __construct() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
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
                settlers
            FROM 
                Settlement s
            WHERE 
                s.settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getBuilding($settlementId, $buildingType) {
        $sql = "
            SELECT 
                b.level, 
                bc.costWood, 
                bc.costStone, 
                bc.costOre, 
                COALESCE(bc.productionRate, 0) AS productionRate,
                bc.settlers
            FROM 
                Buildings b
            INNER JOIN 
                BuildingConfig bc
            ON 
                b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE 
                b.settlementId = :settlementId AND b.buildingType = :buildingType";
        
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
    
}

?>
