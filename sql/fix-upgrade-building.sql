-- Fix UpgradeBuilding procedure to handle buildings that don't exist yet
USE browsergame;

DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE PROCEDURE `UpgradeBuilding`(
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
    DECLARE nextBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Kaserne');
    DECLARE townHallLevel INT DEFAULT 0;
    DECLARE buildTimeReduction FLOAT DEFAULT 1.0;

    
    -- Get current building level (defaults to 0 if building doesn't exist)
    SELECT COALESCE(level, 0) INTO currentBuildingLevel
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

        -- If this is the first time building this building (level 1), create the Buildings entry
        IF currentBuildingLevel = 0 AND nextLevel = 1 THEN
            INSERT INTO Buildings (settlementId, buildingType, level, visable) 
            VALUES (inSettlementId, inBuildingType, 0, FALSE);
        END IF;

        -- Note: Building completion is now handled by the ProcessBuildingQueue event
        -- which runs every 5 seconds and processes all completed building upgrades

    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
    END IF;
END //
DELIMITER ;

SELECT 'UpgradeBuilding procedure updated to handle new buildings' as result;