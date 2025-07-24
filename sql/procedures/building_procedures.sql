-- Building Management Procedures

USE browsergame;

DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE PROCEDURE UpgradeBuilding(
        IN inSettlementId INT,
        IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
    )
BEGIN
    DECLARE currentBuildingLevel INT DEFAULT 0;  -- Default to 0 if building doesn't exist
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

    -- Get current building level (defaults to 0 if building doesn't exist)
    SELECT COALESCE(level, 0) INTO currentBuildingLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

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

    -- Get costs for next level
    SELECT costWood, costStone, costOre, settlers, buildTime INTO 
        nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost, nextLevelSettlerCost, nextBuildTime
    FROM BuildingConfig
    WHERE buildingType = inBuildingType AND level = nextLevel;

    -- If no config found, signal error
    IF nextLevelWoodCost IS NULL THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Keine Konfiguration für das Gebäude-Level gefunden';
    END IF;

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

    -- Check resources
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelWoodCost AND
    (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelStoneCost AND
    (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelOreCost AND
    (SELECT COALESCE(freeSettlers, 0) FROM SettlementSettlers WHERE settlementId = inSettlementId) >= nextLevelSettlerCost THEN

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

        -- If we're upgrading the Town Hall, recalculate all existing queue times
        IF inBuildingType = 'Rathaus' THEN
            CALL UpdateQueueTimesAfterTownHallUpgrade(inSettlementId, nextLevel);
        END IF;

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

-- Military Unit Training Procedure
DROP PROCEDURE IF EXISTS TrainMilitaryUnit;

DELIMITER //
CREATE PROCEDURE TrainMilitaryUnit(
    IN inSettlementId INT,
    IN inUnitType ENUM('guards', 'soldiers', 'archers', 'cavalry'),
    IN inCount INT
)
BEGIN
    DECLARE unitCostWood FLOAT;
    DECLARE unitCostStone FLOAT;
    DECLARE unitCostOre FLOAT;
    DECLARE unitCostSettlers INT;
    DECLARE unitTrainingTime INT;
    DECLARE totalCostWood FLOAT;
    DECLARE totalCostStone FLOAT;
    DECLARE totalCostOre FLOAT;
    DECLARE totalCostSettlers INT;
    DECLARE totalTrainingTime INT;
    DECLARE lastEndTime DATETIME;
    DECLARE kaserneLevel INT DEFAULT 0;
    DECLARE trainingTimeReduction FLOAT DEFAULT 1.0;
    DECLARE currentFreeSettlers INT;
    DECLARE currentMilitarySettlerCost INT DEFAULT 0;

    -- Get unit configuration
    SELECT costWood, costStone, costOre, costSettlers, trainingTime INTO 
        unitCostWood, unitCostStone, unitCostOre, unitCostSettlers, unitTrainingTime
    FROM MilitaryUnitConfig
    WHERE unitType = inUnitType;

    -- Calculate total costs
    SET totalCostWood = unitCostWood * inCount;
    SET totalCostStone = unitCostStone * inCount;
    SET totalCostOre = unitCostOre * inCount;
    SET totalCostSettlers = unitCostSettlers * inCount;

    -- Get Kaserne level for training time reduction
    SELECT COALESCE(level, 0) INTO kaserneLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = 'Kaserne';

    -- Calculate training time reduction based on Kaserne level (3% per level)
    SET trainingTimeReduction = 1.0 - (kaserneLevel * 0.03);
    IF trainingTimeReduction < 0.2 THEN
        SET trainingTimeReduction = 0.2; -- Minimum 20% of original training time
    END IF;
    
    -- Apply training time reduction and calculate total time
    SET totalTrainingTime = ROUND(unitTrainingTime * inCount * trainingTimeReduction);

    -- Get current military settler cost
    SELECT COALESCE(totalSettlerCost, 0) INTO currentMilitarySettlerCost
    FROM MilitarySettlerCosts
    WHERE settlementId = inSettlementId;

    -- Get current free settlers from the SettlementSettlers view
    SELECT COALESCE(freeSettlers, 0) 
    INTO currentFreeSettlers
    FROM SettlementSettlers 
    WHERE settlementId = inSettlementId;
    
    -- Subtract current military settler costs to get actual free settlers available for training
    SET currentFreeSettlers = GREATEST(0, currentFreeSettlers - currentMilitarySettlerCost);

    -- Check if settlement has enough resources including settlers
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= totalCostWood AND
       (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= totalCostStone AND
       (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= totalCostOre AND
       currentFreeSettlers >= totalCostSettlers THEN

        -- Deduct resources
        UPDATE Settlement
        SET wood = wood - totalCostWood,
            stone = stone - totalCostStone,
            ore = ore - totalCostOre
        WHERE settlementId = inSettlementId;

        -- Update military settler costs
        INSERT INTO MilitarySettlerCosts (settlementId, totalSettlerCost)
        VALUES (inSettlementId, totalCostSettlers)
        ON DUPLICATE KEY UPDATE totalSettlerCost = totalSettlerCost + totalCostSettlers;

        -- Get the last end time from the training queue
        SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
        FROM MilitaryTrainingQueue
        WHERE settlementId = inSettlementId;

        -- Add to training queue
        INSERT INTO MilitaryTrainingQueue (settlementId, unitType, count, startTime, endTime, isActive)
        VALUES (
            inSettlementId,
            inUnitType,
            inCount,
            lastEndTime,
            DATE_ADD(lastEndTime, INTERVAL totalTrainingTime SECOND),
            FALSE
        );

    ELSE
        IF currentFreeSettlers < totalCostSettlers THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Not enough settlers for unit training';
        ELSE
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Not enough resources for unit training';
        END IF;
    END IF;
END //

DELIMITER ;