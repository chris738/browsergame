<?php

class BuildingProcedures {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Create a minimal UpgradeBuilding procedure if the full schema initialization fails
     */
    public function createUpgradeBuildingProcedure() {
        try {
            // Drop procedure if exists
            $this->conn->exec("DROP PROCEDURE IF EXISTS UpgradeBuilding");
            
            // Create procedure with proper delimiter handling
            $procedureSQL = "CREATE PROCEDURE UpgradeBuilding(
                    IN inSettlementId INT,
                    IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
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
    public function createUpdateQueueProcedure() {
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
                    DECLARE currentBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne');
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
}

?>