<?php

interface DatabaseInterface {
    public function getResources($settlementId);
    public function getBuilding($settlementId, $buildingType);
    public function upgradeBuilding($settlementId, $buildingType);
    public function getRegen($settlementId);
    public function getSettlementName($settlementId);
    public function getQueue($settlementId);
    public function getMap();
    
    // Admin-specific methods
    public function getPlayerCount();
    public function getSettlementCount();
    public function getActiveQueuesCount();
    public function getAllPlayers();
    public function getAllSettlements();
    public function getAllQueues();
    public function createPlayer($name, $gold);
    public function deletePlayer($playerId);
    public function updatePlayerStats($playerId, $points, $gold);
    public function updateSettlementResources($settlementId, $wood, $stone, $ore);
    public function deleteQueue($queueId);
    public function clearAllQueues();
    
    // Building configuration management methods
    public function getAllBuildingConfigs();
    public function getBuildingConfig($buildingType, $level);
    public function updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    public function createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    public function deleteBuildingConfig($buildingType, $level);
}

class Database implements DatabaseInterface {
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $conn;
    private $connectionFailed = false;
    private $schemaInitialized = false;

    public function __construct() {
        // Support environment variables for Docker/containerized deployments
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'browsergame';
        $this->username = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'browsergame';
        $this->password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'sicheresPasswort';
        
        // Try standard database credentials as fallback
        $credentialSets = [
            // Original credentials
            ['user' => $this->username, 'pass' => $this->password],
            // Common standard credentials
            ['user' => 'root', 'pass' => 'root'],
            ['user' => 'root', 'pass' => ''],
            ['user' => 'root', 'pass' => 'password'],
            ['user' => 'root', 'pass' => 'admin'],
        ];
        
        foreach ($credentialSets as $credentials) {
            try {
                $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $credentials['user'], $credentials['pass']);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                // If we get here, connection was successful
                $this->username = $credentials['user'];
                $this->password = $credentials['pass'];
                break;
            } catch (PDOException $e) {
                // Continue to try next credential set
                continue;
            }
        }
        
        // If no credentials worked, mark connection as failed
        if (!isset($this->conn)) {
            $this->connectionFailed = true;
            error_log("Database connection failed with all credential sets");
        } else {
            // Try to initialize schema if connected
            $this->initializeSchemaIfNeeded();
        }
    }

    public function isConnected() {
        return !$this->connectionFailed;
    }

    /**
     * Check if database schema is initialized and initialize if needed
     */
    private function initializeSchemaIfNeeded() {
        if ($this->connectionFailed || $this->schemaInitialized) {
            return;
        }

        try {
            // Check if UpgradeBuilding procedure exists
            $stmt = $this->conn->prepare("SHOW PROCEDURE STATUS WHERE Name = 'UpgradeBuilding' AND Db = ?");
            $stmt->execute([$this->dbname]);
            $procedureExists = $stmt->rowCount() > 0;

            if (!$procedureExists) {
                error_log("UpgradeBuilding procedure not found, initializing database schema...");
                $this->initializeDatabaseSchema();
                
                // If the full schema initialization failed, try creating just the essential procedure
                $stmt = $this->conn->prepare("SHOW PROCEDURE STATUS WHERE Name = 'UpgradeBuilding' AND Db = ?");
                $stmt->execute([$this->dbname]);
                $procedureExists = $stmt->rowCount() > 0;
                
                if (!$procedureExists) {
                    error_log("Full schema initialization failed, creating minimal UpgradeBuilding procedure...");
                    $this->createEssentialUpgradeBuildingProcedure();
                }
            }

            // Ensure event scheduler is enabled for automatic resource generation
            $this->ensureEventSchedulerEnabled();

            $this->schemaInitialized = true;
        } catch (PDOException $e) {
            error_log("Error checking database schema: " . $e->getMessage());
        }
    }

    /**
     * Ensure event scheduler is enabled for automatic resource generation
     */
    private function ensureEventSchedulerEnabled() {
        try {
            // Check if event scheduler is already enabled
            $stmt = $this->conn->prepare("SHOW VARIABLES LIKE 'event_scheduler'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && strtoupper($result['Value']) === 'ON') {
                error_log("Event scheduler is already enabled");
                return true;
            }

            // Try to enable event scheduler
            // Note: This requires SUPER privilege, so it might fail for regular users
            try {
                $this->conn->exec("SET GLOBAL event_scheduler = ON");
                error_log("Event scheduler enabled successfully");
                return true;
            } catch (PDOException $e) {
                // If we can't enable it directly, log a warning
                error_log("Cannot enable event scheduler (requires SUPER privilege): " . $e->getMessage());
                error_log("Please manually run as root: mysql -e \"SET GLOBAL event_scheduler = ON;\"");
                
                // Check if UpdateResources event exists
                $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM information_schema.EVENTS WHERE EVENT_NAME = 'UpdateResources' AND EVENT_SCHEMA = ?");
                $stmt->execute([$this->dbname]);
                $eventResult = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($eventResult['count'] == 0) {
                    error_log("UpdateResources event not found. Automatic resource generation may not work.");
                    error_log("Please run the database initialization script to create the event.");
                }
                
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error checking event scheduler status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create a minimal UpgradeBuilding procedure if the full schema initialization fails
     */
    private function createEssentialUpgradeBuildingProcedure() {
        try {
            // First ensure essential tables exist
            $this->createEssentialTables();
            
            // Drop procedure if exists
            $this->conn->exec("DROP PROCEDURE IF EXISTS UpgradeBuilding");
            
            // Create procedure with proper delimiter handling
            $procedureSQL = "CREATE PROCEDURE UpgradeBuilding(
                    IN inSettlementId INT,
                    IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus')
                )
                BEGIN
                    DECLARE currentBuildingLevel INT DEFAULT 1;
                    DECLARE nextLevel INT DEFAULT 2;
                    DECLARE nextLevelWoodCost FLOAT DEFAULT 100;
                    DECLARE nextLevelStoneCost FLOAT DEFAULT 100;
                    DECLARE nextLevelOreCost FLOAT DEFAULT 100;
                    DECLARE nextLevelSettlerCost FLOAT DEFAULT 1;
                    DECLARE nextBuildTime INT DEFAULT 30;
                    DECLARE lastEndTime DATETIME;
                    DECLARE maxQueueLevel INT DEFAULT 0;
                    DECLARE currentWood FLOAT DEFAULT 0;
                    DECLARE currentStone FLOAT DEFAULT 0;
                    DECLARE currentOre FLOAT DEFAULT 0;
                    DECLARE currentFreeSettlers FLOAT DEFAULT 0;
                    DECLARE townHallLevel INT DEFAULT 0;
                    DECLARE buildTimeReduction FLOAT DEFAULT 1.0;

                    -- Get current building level (default to 1 if building doesn't exist)
                    SELECT COALESCE(level, 1) INTO currentBuildingLevel
                    FROM Buildings
                    WHERE settlementId = inSettlementId AND buildingType = inBuildingType
                    LIMIT 1;

                    -- Get max queue level for this building
                    SELECT COALESCE(MAX(level), 0) INTO maxQueueLevel
                    FROM BuildingQueue
                    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

                    -- Calculate next level
                    IF maxQueueLevel > 0 THEN
                        SET nextLevel = maxQueueLevel + 1;
                    ELSE
                        SET nextLevel = currentBuildingLevel + 1;
                    END IF;

                    -- Get costs for next level (use defaults if not found in config)
                    SELECT COALESCE(costWood, 100 * POW(1.1, nextLevel)), 
                           COALESCE(costStone, 100 * POW(1.1, nextLevel)), 
                           COALESCE(costOre, 100 * POW(1.1, nextLevel)),
                           COALESCE(settlers, 1 * POW(1.1, nextLevel)),
                           COALESCE(buildTime, 30)
                    INTO nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost, nextLevelSettlerCost, nextBuildTime
                    FROM BuildingConfig
                    WHERE buildingType = inBuildingType AND level = nextLevel
                    LIMIT 1;

                    -- Get Town Hall level for build time reduction
                    SELECT COALESCE(level, 0) INTO townHallLevel
                    FROM Buildings
                    WHERE settlementId = inSettlementId AND buildingType = 'Rathaus'
                    LIMIT 1;

                    -- Calculate build time reduction based on Town Hall level (5% per level)
                    SET buildTimeReduction = 1.0 - (townHallLevel * 0.05);
                    IF buildTimeReduction < 0.1 THEN
                        SET buildTimeReduction = 0.1; -- Minimum 10% of original build time
                    END IF;
                    
                    -- Apply build time reduction
                    SET nextBuildTime = ROUND(nextBuildTime * buildTimeReduction);

                    -- Get current resources
                    SELECT COALESCE(wood, 0), COALESCE(stone, 0), COALESCE(ore, 0)
                    INTO currentWood, currentStone, currentOre
                    FROM Settlement 
                    WHERE settlementId = inSettlementId
                    LIMIT 1;

                    -- Get current free settlers
                    SELECT COALESCE(freeSettlers, 0)
                    INTO currentFreeSettlers
                    FROM SettlementSettlers
                    WHERE settlementId = inSettlementId
                    LIMIT 1;

                    -- Check if settlement has enough resources including settlers
                    IF currentWood >= nextLevelWoodCost AND currentStone >= nextLevelStoneCost AND currentOre >= nextLevelOreCost AND currentFreeSettlers >= nextLevelSettlerCost THEN

                        -- Deduct resources
                        UPDATE Settlement
                        SET wood = wood - nextLevelWoodCost,
                            stone = stone - nextLevelStoneCost,
                            ore = ore - nextLevelOreCost
                        WHERE settlementId = inSettlementId;

                        -- Get last end time for queue
                        SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
                        FROM BuildingQueue
                        WHERE settlementId = inSettlementId;

                        -- Add to building queue
                        INSERT INTO BuildingQueue (settlementId, buildingType, startTime, endTime, isActive, level)
                        VALUES (
                            inSettlementId,
                            inBuildingType,
                            lastEndTime,
                            DATE_ADD(lastEndTime, INTERVAL nextBuildTime SECOND),
                            FALSE,
                            nextLevel
                        );

                        -- If we're upgrading the Town Hall, update all existing queue times with new reduction
                        IF inBuildingType = 'Rathaus' THEN
                            CALL UpdateQueueTimesAfterTownHallUpgrade(inSettlementId, nextLevel);
                        END IF;

                    ELSE
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
                    END IF;
                END";

            $this->conn->exec($procedureSQL);
            
            // Also create the UpdateQueueTimesAfterTownHallUpgrade procedure
            $this->createUpdateQueueProcedure();
            
            error_log("Essential UpgradeBuilding procedure created successfully");
            return true;
        } catch (PDOException $e) {
            error_log("Failed to create essential UpgradeBuilding procedure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create the UpdateQueueTimesAfterTownHallUpgrade procedure
     */
    private function createUpdateQueueProcedure() {
        try {
            // Drop procedure if exists
            $this->conn->exec("DROP PROCEDURE IF EXISTS UpdateQueueTimesAfterTownHallUpgrade");
            
            $procedureSQL = "CREATE PROCEDURE UpdateQueueTimesAfterTownHallUpgrade(
                    IN inSettlementId INT,
                    IN newTownHallLevel INT
                )
                BEGIN
                    DECLARE done INT DEFAULT FALSE;
                    DECLARE currentQueueId INT;
                    DECLARE currentBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus');
                    DECLARE currentLevel INT;
                    DECLARE originalBuildTime INT;
                    DECLARE newBuildTime INT;
                    DECLARE newBuildTimeReduction FLOAT;
                    DECLARE previousEndTime DATETIME;
                    
                    DECLARE queue_cursor CURSOR FOR 
                        SELECT queueId, buildingType, level
                        FROM BuildingQueue 
                        WHERE settlementId = inSettlementId
                        ORDER BY endTime ASC;
                    
                    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
                    
                    -- Calculate new build time reduction
                    SET newBuildTimeReduction = 1.0 - (newTownHallLevel * 0.05);
                    IF newBuildTimeReduction < 0.1 THEN
                        SET newBuildTimeReduction = 0.1;
                    END IF;
                    
                    SET previousEndTime = NOW();
                    
                    OPEN queue_cursor;
                    
                    queue_loop: LOOP
                        FETCH queue_cursor INTO currentQueueId, currentBuildingType, currentLevel;
                        IF done THEN
                            LEAVE queue_loop;
                        END IF;
                        
                        -- Get original build time
                        SELECT COALESCE(buildTime, 30) INTO originalBuildTime
                        FROM BuildingConfig 
                        WHERE buildingType = currentBuildingType AND level = currentLevel
                        LIMIT 1;
                        
                        -- If no config found, use default
                        IF originalBuildTime IS NULL THEN
                            SET originalBuildTime = 30;
                        END IF;
                        
                        -- Calculate new reduced build time
                        SET newBuildTime = ROUND(originalBuildTime * newBuildTimeReduction);
                        
                        -- Update this queue item
                        UPDATE BuildingQueue 
                        SET startTime = previousEndTime,
                            endTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND)
                        WHERE queueId = currentQueueId;
                        
                        -- Set for next iteration
                        SET previousEndTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND);
                        
                    END LOOP;
                    
                    CLOSE queue_cursor;
                END";
            
            $this->conn->exec($procedureSQL);
            error_log("UpdateQueueTimesAfterTownHallUpgrade procedure created successfully");
            return true;
        } catch (PDOException $e) {
            error_log("Failed to create UpdateQueueTimesAfterTownHallUpgrade procedure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create essential tables if they don't exist
     */
    private function createEssentialTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS Spieler (
                playerId INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                punkte INT NOT NULL DEFAULT 0,
                gold INT NOT NULL DEFAULT 500,
                UNIQUE (name)
            )",
            "CREATE TABLE IF NOT EXISTS Settlement (
                settlementId INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                wood FLOAT NOT NULL DEFAULT 1000.0,
                stone FLOAT NOT NULL DEFAULT 1000.0,
                ore FLOAT NOT NULL DEFAULT 1000.0,
                playerId INT NOT NULL,
                FOREIGN KEY (playerId) REFERENCES Spieler(playerId) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS BuildingConfig (
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus') NOT NULL,
                level INT NOT NULL,
                costWood FLOAT NOT NULL,
                costStone FLOAT NOT NULL,
                costOre FLOAT NOT NULL,
                settlers FLOAT NOT NULL DEFAULT 0.0,
                productionRate FLOAT NOT NULL,
                buildTime INT,
                PRIMARY KEY (buildingType, level)
            )",
            "CREATE TABLE IF NOT EXISTS BuildingQueue (
                queueId INT AUTO_INCREMENT PRIMARY KEY,
                settlementId INT NOT NULL,
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus') NOT NULL,
                startTime DATETIME NOT NULL,
                endTime DATETIME NOT NULL,
                isActive BOOLEAN NOT NULL DEFAULT FALSE,
                level INT NOT NULL DEFAULT 0,
                FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS Buildings (
                settlementId INT NOT NULL,
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus') NOT NULL,
                level INT NOT NULL DEFAULT 1,
                visable boolean NOT NULL DEFAULT false,
                FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
                PRIMARY KEY (settlementId, buildingType)
            )"
        ];

        foreach ($tables as $tableSQL) {
            try {
                $this->conn->exec($tableSQL);
            } catch (PDOException $e) {
                error_log("Failed to create table: " . $e->getMessage());
            }
        }

        // Insert some basic building config if it doesn't exist
        try {
            $this->conn->exec("INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
                ('Holzfäller', 1, 100, 100, 100, 1, 3600, 30),
                ('Holzfäller', 2, 110, 110, 110, 1.1, 3960, 40),
                ('Steinbruch', 1, 100, 100, 100, 1, 3600, 30),
                ('Steinbruch', 2, 110, 110, 110, 1.1, 3960, 40),
                ('Erzbergwerk', 1, 100, 100, 100, 1, 3600, 30),
                ('Erzbergwerk', 2, 110, 110, 110, 1.1, 3960, 40),
                ('Lager', 1, 100, 100, 100, 1, 10000, 30),
                ('Lager', 2, 110, 110, 110, 1.1, 11000, 40),
                ('Farm', 1, 100, 100, 100, 1, 100, 30),
                ('Farm', 2, 110, 110, 110, 1.1, 110, 40),
                ('Rathaus', 1, 200, 200, 200, 2, 0, 60),
                ('Rathaus', 2, 220, 220, 220, 2.2, 0, 80)");
        } catch (PDOException $e) {
            error_log("Failed to insert basic building config: " . $e->getMessage());
        }
    }

    /**
     * Initialize database schema from database.sql file
     */
    private function initializeDatabaseSchema() {
        $sqlFile = __DIR__ . '/database.sql';
        
        if (!file_exists($sqlFile)) {
            error_log("database.sql file not found at: " . $sqlFile);
            return false;
        }

        try {
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                error_log("Could not read database.sql file");
                return false;
            }

            // Extract only the schema parts we need (skip database/user creation)
            $sql = $this->filterSchemaSQL($sql);
            
            // Split SQL into individual statements and execute them
            $statements = $this->splitSqlStatements($sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) {
                    continue;
                }

                try {
                    $stmt = $this->conn->prepare($statement);
                    if ($stmt) {
                        $stmt->execute();
                        $stmt->closeCursor(); // Close cursor to prevent result set issues
                        $stmt = null; // Clear statement
                    }
                } catch (PDOException $e) {
                    // Log but continue with other statements - some might fail due to existing objects
                    error_log("SQL statement failed (continuing): " . $e->getMessage() . " | Statement: " . substr($statement, 0, 100) . "...");
                }
            }
            
            error_log("Database schema initialization completed");
            return true;
        } catch (Exception $e) {
            error_log("Error initializing database schema: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Filter SQL to only include schema creation, not database/user management
     */
    private function filterSchemaSQL($sql) {
        $lines = explode("\n", $sql);
        $filteredLines = [];
        $skipUntilUse = true;
        $skipExampleCalls = false;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Skip database creation and user management commands
            if ($skipUntilUse) {
                // Look for USE statement to start including lines
                if (preg_match('/^\s*USE\s+/i', $trimmedLine)) {
                    $skipUntilUse = false;
                    continue; // Skip the USE statement itself
                }
                continue;
            }
            
            // Check for start of example calls section
            if (preg_match('/--\s*Beispielaufrufe/i', $trimmedLine)) {
                $skipExampleCalls = true;
                continue;
            }
            
            // Skip example calls and manual test statements
            if ($skipExampleCalls) {
                continue;
            }
            
            // Skip certain commands that might cause issues
            if (preg_match('/^\s*(DROP\s+DATABASE|CREATE\s+DATABASE|CREATE\s+USER|GRANT|SET\s+GLOBAL|SHOW\s+|SELECT\s+\*|CALL\s+|DELETE\s+FROM|UPDATE\s+.*SET|CREATE\s+EVENT\s+UpgradeBuildingEventNr_)/i', $trimmedLine)) {
                continue;
            }
            
            $filteredLines[] = $line;
        }
        
        return implode("\n", $filteredLines);
    }

    /**
     * Split SQL file into individual statements, handling DELIMITER changes
     */
    private function splitSqlStatements($sql) {
        $statements = [];
        $currentStatement = '';
        $delimiter = ';';
        $inDelimiterBlock = false;
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || strpos($line, '--') === 0) {
                continue;
            }
            
            // Handle DELIMITER changes
            if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
                // Save current statement if any
                if (!empty($currentStatement)) {
                    $statements[] = $currentStatement;
                    $currentStatement = '';
                }
                $delimiter = $matches[1];
                $inDelimiterBlock = true;
                continue;
            }
            
            $currentStatement .= $line . "\n";
            
            // Check if statement ends with current delimiter
            if (substr($line, -strlen($delimiter)) === $delimiter) {
                if ($inDelimiterBlock && $delimiter !== ';') {
                    // Remove the delimiter from the statement
                    $currentStatement = substr($currentStatement, 0, -strlen($delimiter) - 1);
                    $statements[] = $currentStatement;
                    $currentStatement = '';
                    $delimiter = ';';
                    $inDelimiterBlock = false;
                } else {
                    // Remove the semicolon and add to statements
                    $currentStatement = substr($currentStatement, 0, -2);
                    if (!empty(trim($currentStatement))) {
                        $statements[] = $currentStatement;
                    }
                    $currentStatement = '';
                }
            }
        }
        
        // Add any remaining statement
        if (!empty(trim($currentStatement))) {
            $statements[] = $currentStatement;
        }
        
        return $statements;
    }

    public function getResources($settlementId) {
        // Return mock data if database connection failed - modified for testing insufficient resources
        if ($this->connectionFailed) {
            return [
                'wood' => 50,        // Insufficient for 100 wood cost
                'stone' => 30,       // Insufficient for 50 stone cost  
                'ore' => 200,        // Sufficient for 25 ore cost
                'storageCapacity' => 10000,
                'maxSettlers' => 100,
                'freeSettlers' => 3  // Insufficient for 5 settler cost
            ];
        }

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
                ss.maxSettlers,
                ss.freeSettlers
            FROM 
                Settlement s
            LEFT JOIN 
                SettlementSettlers ss
            ON 
                s.settlementId = ss.settlementId
            WHERE 
                s.settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $resources = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getBuilding($settlementId, $buildingType) {
        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return [
                'currentLevel' => 1,
                'nextLevel' => 2,
                'costWood' => 100,
                'costStone' => 50,
                'costOre' => 25,
                'settlers' => 5,
                'buildTime' => 30
            ];
        }

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
            throw new Exception("Gebäude '$buildingType' im Settlement '$settlementId' nicht gefunden.");
        }
    
        return $result;
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
        $this->initializeSchemaIfNeeded();

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
                $this->schemaInitialized = false;
                $this->initializeSchemaIfNeeded();
                
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

    public function getRegen($settlementId) {
        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return [
                'woodProductionRate' => 10,
                'stoneProductionRate' => 5,
                'oreProductionRate' => 2
            ];
        }

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

    public function getSettlementName($settlementId) {
        // Return mock data if database connection failed
        if ($this->connectionFailed) {
            return ['SettlementName' => 'Test-Siedlung'];
        }

        $sql = "
            SELECT 
                s.name AS SettlementName
            FROM 
                Settlement s
            WHERE 
                s.settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getQueue($settlementId) {
        // Return mock empty queue if database connection failed
        if ($this->connectionFailed) {
            return [];
        }

        $sql = "
        SELECT
            queueId,
            settlementId,
            buildingType,
            startTime,
            endTime, 
            completionPercentage,
            level
        FROM 
            OpenBuildingQueue 
        WHERE 
            settlementId = :settlementId";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':settlementId', $settlementId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMap() {
        $sql = "
        SELECT
            settlementId, xCoordinate, yCoordinate
        FROM 
            Map";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Admin-specific methods
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
        $sql = "SELECT playerId, name, punkte as points, gold FROM Spieler ORDER BY playerId";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSettlements() {
        $sql = "
            SELECT 
                s.settlementId,
                s.name,
                s.wood,
                s.stone,
                s.ore,
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

    public function getAllQueues() {
        if ($this->connectionFailed) {
            return [];
        }

        try {
            $sql = "
                SELECT 
                    bq.queueId,
                    bq.settlementId,
                    s.name as settlementName,
                    bq.buildingType,
                    bq.level,
                    bq.startTime,
                    bq.endTime,
                    bq.isActive,
                    ROUND(
                        100 - (TIMESTAMPDIFF(SECOND, NOW(), bq.endTime) * 100.0 / 
                               TIMESTAMPDIFF(SECOND, bq.startTime, bq.endTime)),
                        2
                    ) AS completionPercentage
                FROM BuildingQueue bq
                LEFT JOIN Settlement s ON bq.settlementId = s.settlementId
                WHERE NOW() < bq.endTime
                ORDER BY bq.endTime ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch queues: " . $e->getMessage());
            return [];
        }
    }

    public function createPlayer($name, $gold = 500) {
        try {
            // Ensure schema is initialized
            $this->initializeSchemaIfNeeded();
            
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
            $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus'];
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

    public function deleteQueue($queueId) {
        try {
            $sql = "DELETE FROM BuildingQueue WHERE queueId = :queueId";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':queueId', $queueId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function clearAllQueues() {
        try {
            $sql = "DELETE FROM BuildingQueue";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
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
}

?>
