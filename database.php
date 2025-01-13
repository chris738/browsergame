<?php

interface DatabaseInterface {
    public function getResources($settlementId);
    public function getBuilding($settlementId, $buildingType);
    public function upgradeBuilding($settlementId, $buildingType);
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
                (
                    SELECT IFNULL(SUM(bc.productionRate), 0)
                    FROM Buildings b
                    INNER JOIN BuildingConfig bc 
                    ON b.buildingType = bc.buildingType AND b.level = bc.level
                    WHERE b.settlementId = :settlementId AND b.buildingType = 'Lager'
                ) AS storageCapacity
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
                bc.costOre
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upgradeBuilding($settlementId, $buildingType) {
        $sql = "CALL UpgradeBuilding(:settlementId, :buildingType)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->bindParam(':buildingType', $buildingType, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->rowCount() > 0; // Gibt true zurück, wenn das Upgrade erfolgreich war
    }
}

?>
