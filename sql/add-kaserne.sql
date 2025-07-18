-- Add Kaserne (Barracks) to existing database schema
-- This script adds Kaserne to all relevant tables

USE browsergame;

-- Add Kaserne to BuildingConfig ENUM
ALTER TABLE BuildingConfig MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;

-- Add Kaserne to BuildingQueue ENUM  
ALTER TABLE BuildingQueue MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;

-- Add Kaserne to Buildings ENUM
ALTER TABLE Buildings MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;

-- Add Kaserne configuration data for all levels (1-50)
INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime)
SELECT 
    'Kaserne' as buildingType,
    level,
    costWood * 1.5 as costWood,  -- Kaserne costs 50% more than base
    costStone * 1.5 as costStone,
    costOre * 2.0 as costOre,    -- Kaserne needs more ore (military equipment)
    settlers * 1.2 as settlers,  -- Needs more settlers to operate
    productionRate * 0.5 as productionRate, -- Produces military units slower than resources
    buildTime * 1.3 as buildTime -- Takes longer to build military structures
FROM BuildingConfig 
WHERE buildingType = 'Rathaus'  -- Use Town Hall as base template
ORDER BY level;

-- Add Kaserne building to all existing settlements
INSERT INTO Buildings (settlementId, buildingType, level, visable)
SELECT settlementId, 'Kaserne', 1, false
FROM Buildings 
WHERE buildingType = 'Rathaus'
GROUP BY settlementId;

-- Update stored procedures to include Kaserne
-- Update UpgradeBuilding procedure
DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE DEFINER=`root`@`localhost` PROCEDURE `UpgradeBuilding`(
        IN inSettlementId INT,
        IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
    )
BEGIN
    DECLARE currentBuildingLevel INT;
    DECLARE nextLevel INT;
    DECLARE nextLevelWoodCost FLOAT;
    DECLARE nextLevelStoneCost FLOAT;
    DECLARE nextLevelOreCost FLOAT;
    DECLARE nextLevelSettlerCost FLOAT;
    DECLARE nextBuildTime INT;
    DECLARE lastEndTime DATETIME;
    DECLARE nextEndTime DATETIME;
    DECLARE maxQueueLevel INT;
    DECLARE nextQueueId INT;
    DECLARE nextBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Kaserne');
    DECLARE townHallLevel INT DEFAULT 0;
    DECLARE buildTimeReduction FLOAT DEFAULT 1.0;

    
    SELECT level INTO currentBuildingLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

    
    SELECT COALESCE(MAX(level), 0) INTO maxQueueLevel
    FROM BuildingQueue
    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

    
    IF maxQueueLevel > 0 THEN
        SET nextLevel = maxQueueLevel + 1;
    ELSE
        SET nextLevel = currentBuildingLevel + 1;
    END IF;

    
    SELECT costWood, costStone, costOre, settlers, buildTime INTO 
        nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost, nextLevelSettlerCost, nextBuildTime
    FROM BuildingConfig
    WHERE buildingType = inBuildingType AND level = nextLevel;

    -- Get Town Hall level for build time reduction
    SELECT COALESCE(level, 0) INTO townHallLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = 'Rathaus';

    -- Calculate build time reduction based on Town Hall level (5% per level)
    SET buildTimeReduction = 1.0 - (townHallLevel * 0.05);
    IF buildTimeReduction < 0.1 THEN
        SET buildTimeReduction = 0.1; -- Minimum 10% of original build time
    END IF;
    
    -- Apply build time reduction
    SET nextBuildTime = ROUND(nextBuildTime * buildTimeReduction);

    
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelWoodCost AND
    (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelStoneCost AND
    (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelOreCost AND
    (SELECT COALESCE(freeSettlers, 0) FROM SettlementSettlers WHERE settlementId = inSettlementId) >= nextLevelSettlerCost THEN

        
        UPDATE Settlement
        SET wood = wood - nextLevelWoodCost,
            stone = stone - nextLevelStoneCost,
            ore = ore - nextLevelOreCost
        WHERE settlementId = inSettlementId;

        
        SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
        FROM BuildingQueue
        WHERE settlementId = inSettlementId;

        
        INSERT INTO BuildingQueue (settlementId, buildingType, startTime, endTime, isActive, level)
        VALUES (
            inSettlementId,
            inBuildingType,
            lastEndTime,
            DATE_ADD(lastEndTime, INTERVAL nextBuildTime SECOND),
            FALSE,
            nextLevel
        );

        -- If we're upgrading the Town Hall, recalculate all existing queue times
        IF inBuildingType = 'Rathaus' THEN
            CALL UpdateQueueTimesAfterTownHallUpgrade(inSettlementId, nextLevel);
        END IF;

        -- Note: Building completion is now handled by the ProcessBuildingQueue event
        -- which runs every 5 seconds and processes all completed building upgrades

    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
    END IF;
END //
DELIMITER ;

-- Update UpdateQueueTimesAfterTownHallUpgrade procedure
DROP PROCEDURE IF EXISTS UpdateQueueTimesAfterTownHallUpgrade;

DELIMITER //
CREATE PROCEDURE UpdateQueueTimesAfterTownHallUpgrade(
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
    DECLARE currentStartTime DATETIME;
    DECLARE previousEndTime DATETIME;
    
    -- Cursor to iterate through all existing queue items for this settlement
    DECLARE queue_cursor CURSOR FOR 
        SELECT queueId, buildingType, level, startTime
        FROM BuildingQueue 
        WHERE settlementId = inSettlementId
        ORDER BY endTime ASC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Calculate new build time reduction based on new Town Hall level
    SET newBuildTimeReduction = 1.0 - (newTownHallLevel * 0.05);
    IF newBuildTimeReduction < 0.1 THEN
        SET newBuildTimeReduction = 0.1; -- Minimum 10% of original build time
    END IF;
    
    -- Initialize previous end time to current time for the first item
    SET previousEndTime = NOW();
    
    OPEN queue_cursor;
    
    queue_loop: LOOP
        FETCH queue_cursor INTO currentQueueId, currentBuildingType, currentLevel, currentStartTime;
        IF done THEN
            LEAVE queue_loop;
        END IF;
        
        -- Get original build time from config
        SELECT buildTime INTO originalBuildTime
        FROM BuildingConfig 
        WHERE buildingType = currentBuildingType AND level = currentLevel;
        
        -- Calculate new reduced build time
        SET newBuildTime = ROUND(originalBuildTime * newBuildTimeReduction);
        
        -- Update this queue item with new times
        UPDATE BuildingQueue 
        SET startTime = previousEndTime,
            endTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND)
        WHERE queueId = currentQueueId;
        
        -- Set previous end time for next iteration
        SET previousEndTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND);
        
    END LOOP;
    
    CLOSE queue_cursor;
END //

DELIMITER ;

-- Update PopulateBuildingConfig procedure to include Kaserne
DROP PROCEDURE IF EXISTS PopulateBuildingConfig;

DELIMITER //

CREATE PROCEDURE PopulateBuildingConfig()
BEGIN
    DECLARE lvl INT DEFAULT 1;
    DECLARE maxLvl INT DEFAULT 50;

    DECLARE baseCostWood FLOAT DEFAULT 100.0;
    DECLARE baseCostStone FLOAT DEFAULT 100.0;
    DECLARE baseCostOre FLOAT DEFAULT 100.0;
    DECLARE baseSettlers FLOAT DEFAULT 1.0;
    DECLARE baseProduction FLOAT DEFAULT 3600.0;
    DECLARE baseBuildTime INT DEFAULT 10;

    DECLARE buildingType VARCHAR(50);
    DECLARE done INT DEFAULT FALSE;
    DECLARE cur CURSOR FOR 
        SELECT name FROM TempBuildings;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    -- Temporary table for building types
    DROP TEMPORARY TABLE IF EXISTS TempBuildings;

    CREATE TEMPORARY TABLE TempBuildings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
    );

    INSERT INTO TempBuildings (name) VALUES
        ('Holzfäller'), ('Steinbruch'), ('Erzbergwerk'), ('Lager'), ('Farm'), ('Rathaus'), ('Markt'), ('Kaserne');

    -- Open the cursor to iterate over building types
    OPEN cur;

    -- Loop through all building types
    read_loop: LOOP
        FETCH cur INTO buildingType;
        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Werte initialisieren
        SET baseCostWood = 100.0;
        SET baseCostStone = 100.0;
        SET baseCostOre = 100.0;
        SET baseSettlers = 1.0;
        SET baseBuildTime = 10;

        -- Set production rates for specific buildings
        IF buildingType = 'Lager' THEN
            SET baseProduction = 10000.0;
        ELSEIF buildingType = 'Farm' THEN
            SET baseProduction = 100.0;
        ELSEIF buildingType = 'Rathaus' THEN
            SET baseProduction = 0.0; -- Town Hall doesn't produce resources, it provides build time reduction
        ELSEIF buildingType = 'Markt' THEN
            SET baseProduction = 0.0; -- Market doesn't produce resources, it enables trading
        ELSEIF buildingType = 'Kaserne' THEN
            SET baseProduction = 10.0; -- Kaserne produces military units
            SET baseCostWood = 150.0; -- Higher base costs for military building
            SET baseCostStone = 150.0;
            SET baseCostOre = 200.0;
            SET baseSettlers = 2.0; -- Requires more settlers
            SET baseBuildTime = 15; -- Takes longer to build
        ELSE
            SET baseProduction = 3600.0;
        END IF;

        -- Füge Daten für Levels hinzu
        SET lvl = 1;
        WHILE lvl <= maxLvl DO
            INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime)
            VALUES (buildingType, lvl, baseCostWood, baseCostStone, baseCostOre, baseSettlers, baseProduction, baseBuildTime)
            ON DUPLICATE KEY UPDATE
                costWood = VALUES(costWood),
                costStone = VALUES(costStone),
                costOre = VALUES(costOre),
                settlers = VALUES(settlers),
                productionRate = VALUES(productionRate),
                buildTime = VALUES(buildTime);

            -- Werte erhöhen für das nächste Level
            SET baseCostWood = baseCostWood * 1.1;
            SET baseCostStone = baseCostStone * 1.1;
            SET baseCostOre = baseCostOre * 1.1;
            SET baseSettlers = baseSettlers * 1.1;
            SET baseProduction = baseProduction * 1.1;
            SET baseBuildTime = baseBuildTime + 10;

            SET lvl = lvl + 1;
        END WHILE;
    END LOOP;

    -- Close the cursor
    CLOSE cur;

    -- Temporäre Tabelle löschen
    DROP TEMPORARY TABLE IF EXISTS TempBuildings;
END //

DELIMITER ;

SELECT 'Kaserne (Barracks) successfully added to database schema!' as result;