-- Building Management Procedures

DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE PROCEDURE UpgradeBuilding(
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
    DECLARE nextBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne');
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

-- Prozedur: Update queue times after Town Hall upgrade
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