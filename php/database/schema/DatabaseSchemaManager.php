<?php

class DatabaseSchemaManager {
    private $conn;
    private $dbname;
    private $schemaInitialized = false;

    public function __construct($connection, $dbname) {
        $this->conn = $connection;
        $this->dbname = $dbname;
    }

    /**
     * Check if database schema is initialized and initialize if needed
     */
    public function initializeSchemaIfNeeded() {
        if (!$this->conn || $this->schemaInitialized) {
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
    public function ensureEventSchedulerEnabled() {
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
     * Create essential tables if they don't exist
     */
    public function createEssentialTables() {
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
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
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
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
                startTime DATETIME NOT NULL,
                endTime DATETIME NOT NULL,
                isActive BOOLEAN NOT NULL DEFAULT FALSE,
                level INT NOT NULL DEFAULT 0,
                FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS Buildings (
                settlementId INT NOT NULL,
                buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
                level INT NOT NULL DEFAULT 0,
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

        $this->createSettlementSettlersView();
        $this->insertBasicBuildingConfig();
    }

    /**
     * Create SettlementSettlers view
     */
    private function createSettlementSettlersView() {
        try {
            $this->conn->exec("DROP VIEW IF EXISTS SettlementSettlers");
            $settlementSettlersView = "CREATE VIEW SettlementSettlers AS
            SELECT 
                s.settlementId,
                -- Used settlers from Buildings, summing up all levels
                (
                    COALESCE(
                        (
                            SELECT SUM(totalSettlers) 
                            FROM (
                                SELECT b.settlementId, b.buildingType, SUM(bc.settlers) AS totalSettlers
                                FROM Buildings b
                                INNER JOIN BuildingConfig bc
                                ON b.buildingType = bc.buildingType AND bc.level <= b.level
                                GROUP BY b.settlementId, b.buildingType
                            ) AS BuildingSummed
                            WHERE BuildingSummed.settlementId = s.settlementId
                        ), 0
                    ) + 
                    COALESCE(
                        (
                            SELECT SUM(totalSettlers) 
                            FROM (
                                SELECT bq.settlementId, bq.buildingType, SUM(bc.settlers) AS totalSettlers
                                FROM BuildingQueue bq
                                INNER JOIN BuildingConfig bc
                                ON bq.buildingType = bc.buildingType AND bc.level <= bq.level
                                GROUP BY bq.settlementId, bq.buildingType
                            ) AS QueueSummed
                            WHERE QueueSummed.settlementId = s.settlementId
                        ), 0
                    )
                ) AS usedSettlers,
                -- Max settlers based on Farm level
                COALESCE(
                    (
                        SELECT bc.productionRate
                        FROM Buildings b
                        INNER JOIN BuildingConfig bc
                        ON b.buildingType = bc.buildingType AND b.level = bc.level
                        WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
                        LIMIT 1
                    ), 100
                ) AS maxSettlers,
                -- Free settlers (maxSettlers - usedSettlers)
                GREATEST(
                    COALESCE(
                        (
                            SELECT bc.productionRate
                            FROM Buildings b
                            INNER JOIN BuildingConfig bc
                            ON b.buildingType = bc.buildingType AND b.level = bc.level
                            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
                            LIMIT 1
                        ), 100
                    ) - (
                        COALESCE(
                            (
                                SELECT SUM(totalSettlers) 
                                FROM (
                                    SELECT b.settlementId, b.buildingType, SUM(bc.settlers) AS totalSettlers
                                    FROM Buildings b
                                    INNER JOIN BuildingConfig bc
                                    ON b.buildingType = bc.buildingType AND bc.level <= b.level
                                    GROUP BY b.settlementId, b.buildingType
                                ) AS BuildingSummed
                                WHERE BuildingSummed.settlementId = s.settlementId
                            ), 0
                        ) + 
                        COALESCE(
                            (
                                SELECT SUM(totalSettlers) 
                                FROM (
                                    SELECT bq.settlementId, bq.buildingType, SUM(bc.settlers) AS totalSettlers
                                    FROM BuildingQueue bq
                                    INNER JOIN BuildingConfig bc
                                    ON bq.buildingType = bc.buildingType AND bc.level <= bq.level
                                    GROUP BY bq.settlementId, bq.buildingType
                                ) AS QueueSummed
                                WHERE QueueSummed.settlementId = s.settlementId
                            ), 0
                        )
                    ), 0
                ) AS freeSettlers
            FROM Settlement s";
            $this->conn->exec($settlementSettlersView);
        } catch (PDOException $e) {
            error_log("Failed to create SettlementSettlers view: " . $e->getMessage());
        }
    }

    /**
     * Insert basic building configuration
     */
    private function insertBasicBuildingConfig() {
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
                ('Rathaus', 2, 220, 220, 220, 2.2, 0, 80),
                ('Markt', 1, 150, 100, 50, 2, 0, 45),
                ('Markt', 2, 165, 110, 55, 2.2, 0, 60),
                ('Kaserne', 1, 150, 150, 200, 2, 10, 45),
                ('Kaserne', 2, 165, 165, 220, 2.2, 11, 60)");
        } catch (PDOException $e) {
            error_log("Failed to insert basic building config: " . $e->getMessage());
        }
    }

    /**
     * Initialize database schema from database.sql file
     */
    public function initializeDatabaseSchema() {
        $sqlFile = dirname(__DIR__, 2) . '/sql/database.sql';
        
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

    /**
     * Create a minimal UpgradeBuilding procedure if the full schema initialization fails
     */
    public function createEssentialUpgradeBuildingProcedure() {
        // This will be handled by a separate procedure manager
        require_once __DIR__ . '/../procedures/BuildingProcedures.php';
        $procedureManager = new BuildingProcedures($this->conn);
        return $procedureManager->createUpgradeBuildingProcedure();
    }
}

?>