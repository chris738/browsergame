-- Comprehensive fix.sql file
-- This file ensures all critical fixes are applied and level 5 reset bug is prevented
-- Problem: When upgrading to level 5, the level gets reset to 0
-- Solution: Fix ProcessBuildingQueue event and ensure all building configs exist

USE browsergame;

-- ===========================================
-- 1. Fix ProcessBuildingQueue Event (Critical Fix)
-- ===========================================
-- This is the main cause of the level 5 reset bug
-- The original event has incorrect logic that can cause level resets

DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //

CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Handle completed building upgrades/construction
    -- This is the FIXED version that prevents level resets
    
    -- First, create new building entries for buildings that don't exist yet
    INSERT INTO Buildings (settlementId, buildingType, level, visable)
    SELECT bq.settlementId, bq.buildingType, bq.level, TRUE
    FROM BuildingQueue bq
    LEFT JOIN Buildings b ON bq.settlementId = b.settlementId AND bq.buildingType = b.buildingType
    WHERE NOW() >= bq.endTime 
    AND b.buildingType IS NULL;  -- Building doesn't exist yet
    
    -- Then, update existing building levels (FIXED: removed isActive condition that caused issues)
    UPDATE Buildings b
    INNER JOIN BuildingQueue bq ON b.settlementId = bq.settlementId AND b.buildingType = bq.buildingType
    SET b.level = bq.level, b.visable = TRUE
    WHERE NOW() >= bq.endTime;
    
    -- Finally, remove completed queue items
    DELETE FROM BuildingQueue 
    WHERE NOW() >= endTime;
    
END //

DELIMITER ;

-- ===========================================
-- 2. Ensure Building Configurations Exist for Level 5+
-- ===========================================
-- Add missing building configs to prevent level config lookup failures

-- Ensure all building types have configurations up to level 10
-- (This prevents issues when the system can't find a config for a level)

-- Holzfäller levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Holzfäller', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Holzfäller', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Holzfäller', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Holzfäller', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Holzfäller', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112);

-- Steinbruch levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Steinbruch', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Steinbruch', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Steinbruch', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Steinbruch', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Steinbruch', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112);

-- Erzbergwerk levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Erzbergwerk', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Erzbergwerk', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Erzbergwerk', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Erzbergwerk', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Erzbergwerk', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112);

-- Lager levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Lager', 6, 161.051, 161.051, 161.051, 1.61051, 16105.1, 77),
('Lager', 7, 177.156, 177.156, 177.156, 1.77156, 17715.61, 85),
('Lager', 8, 194.872, 194.872, 194.872, 1.94872, 19487.17, 93),
('Lager', 9, 214.359, 214.359, 214.359, 2.14359, 21435.89, 102),
('Lager', 10, 235.795, 235.795, 235.795, 2.35795, 23579.48, 112);

-- Farm levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Farm', 6, 161.051, 161.051, 161.051, 1.61051, 161.051, 77),
('Farm', 7, 177.156, 177.156, 177.156, 1.77156, 177.156, 85),
('Farm', 8, 194.872, 194.872, 194.872, 1.94872, 194.872, 93),
('Farm', 9, 214.359, 214.359, 214.359, 2.14359, 214.359, 102),
('Farm', 10, 235.795, 235.795, 235.795, 2.35795, 235.795, 112);

-- Rathaus levels 6-10  
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Rathaus', 6, 322.102, 322.102, 322.102, 3.22102, 0, 154),
('Rathaus', 7, 354.3122, 354.3122, 354.3122, 3.543122, 0, 169),
('Rathaus', 8, 389.74342, 389.74342, 389.74342, 3.8974342, 0, 186),
('Rathaus', 9, 428.717762, 428.717762, 428.717762, 4.28717762, 0, 205),
('Rathaus', 10, 471.5895382, 471.5895382, 471.5895382, 4.715895382, 0, 225);

-- Markt levels 6-10 
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Markt', 6, 241.577, 161.051, 80.525, 3.22102, 0, 116),
('Markt', 7, 265.734, 177.156, 88.578, 3.54312, 0, 127),
('Markt', 8, 292.308, 194.872, 97.436, 3.89743, 0, 140),
('Markt', 9, 321.539, 214.359, 107.18, 4.28718, 0, 154),
('Markt', 10, 353.693, 235.795, 117.898, 4.7159, 0, 169);

-- Kaserne levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Kaserne', 6, 241.577, 241.577, 322.102, 3.22102, 16.105, 116),
('Kaserne', 7, 265.734, 265.734, 354.312, 3.54312, 17.716, 127),
('Kaserne', 8, 292.308, 292.308, 389.743, 3.89743, 19.487, 140),
('Kaserne', 9, 321.539, 321.539, 428.718, 4.28718, 21.436, 154),
('Kaserne', 10, 353.693, 353.693, 471.59, 4.7159, 23.579, 169);

-- ===========================================
-- 3. Fix Building Default Level (from fix-building-unlock-bug.sql)
-- ===========================================
-- When Storage/Farm reach level 5, Market/Barracks should unlock at level 0, not level 1

-- Step 1: Change Buildings table default level from 1 to 0
ALTER TABLE Buildings ALTER COLUMN level SET DEFAULT 0;

-- Step 2: Reset Market and Barracks to level 0 (they should start as unlocked but not built)
UPDATE Buildings 
SET level = 0 
WHERE buildingType IN ('Markt', 'Kaserne') AND level = 1 AND visable = FALSE;

-- ===========================================
-- 4. Fix UpgradeBuilding Procedure (from fix-upgrade-building.sql)
-- ===========================================
-- Ensure the UpgradeBuilding procedure handles buildings that don't exist properly

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
    DECLARE maxQueueLevel INT;
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
        SET MESSAGE_TEXT = CONCAT('Keine Konfiguration für ', inBuildingType, ' Level ', nextLevel, ' gefunden');
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

-- ===========================================
-- 5. Enable Events (Critical for automated processing)
-- ===========================================
-- Ensure the event scheduler is running so ProcessBuildingQueue works

SET GLOBAL event_scheduler = ON;

-- Enable all critical events
ALTER EVENT ProcessBuildingQueue ENABLE;
ALTER EVENT UpdateResources ENABLE;
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;

-- ===========================================
-- Success Message
-- ===========================================

SELECT 'Comprehensive fix.sql applied successfully! Level 5 reset bug should be fixed.' as result;
SELECT 'All building configurations extended to level 10' as buildingConfigStatus;
SELECT 'ProcessBuildingQueue event fixed and enabled' as eventStatus;
SELECT 'UpgradeBuilding procedure updated' as procedureStatus;