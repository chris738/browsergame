-- Military procedures
-- This script contains stored procedures for military operations

USE browsergame;

-- Procedure: Start Research
DROP PROCEDURE IF EXISTS StartResearch;

DELIMITER //
CREATE PROCEDURE StartResearch(
    IN inSettlementId INT,
    IN inUnitType ENUM('guards', 'soldiers', 'archers', 'cavalry')
)
BEGIN
    DECLARE researchCostWood FLOAT;
    DECLARE researchCostStone FLOAT;
    DECLARE researchCostOre FLOAT;
    DECLARE researchTime INT;
    DECLARE prerequisiteUnit ENUM('guards', 'soldiers', 'archers', 'cavalry');
    DECLARE prerequisiteResearched BOOLEAN DEFAULT FALSE;
    DECLARE alreadyResearched BOOLEAN DEFAULT FALSE;
    DECLARE alreadyInQueue BOOLEAN DEFAULT FALSE;
    DECLARE lastEndTime DATETIME;

    -- Check if unit is already researched
    SELECT isResearched INTO alreadyResearched
    FROM UnitResearch
    WHERE settlementId = inSettlementId AND unitType = inUnitType;

    IF alreadyResearched THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Unit is already researched';
    END IF;

    -- Check if unit is already in research queue
    SELECT COUNT(*) > 0 INTO alreadyInQueue
    FROM ResearchQueue
    WHERE settlementId = inSettlementId AND unitType = inUnitType;

    IF alreadyInQueue THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Unit is already being researched';
    END IF;

    -- Get research configuration
    SELECT researchCostWood, researchCostStone, researchCostOre, researchTime, prerequisiteUnit INTO 
        researchCostWood, researchCostStone, researchCostOre, researchTime, prerequisiteUnit
    FROM ResearchConfig
    WHERE unitType = inUnitType;

    -- Check prerequisite if exists
    IF prerequisiteUnit IS NOT NULL THEN
        SELECT isResearched INTO prerequisiteResearched
        FROM UnitResearch
        WHERE settlementId = inSettlementId AND unitType = prerequisiteUnit;

        IF NOT prerequisiteResearched THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Prerequisite unit must be researched first';
        END IF;
    END IF;

    -- Check if settlement has enough resources
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= researchCostWood AND
       (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= researchCostStone AND
       (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= researchCostOre THEN

        -- Deduct resources
        UPDATE Settlement
        SET wood = wood - researchCostWood,
            stone = stone - researchCostStone,
            ore = ore - researchCostOre
        WHERE settlementId = inSettlementId;

        -- Get the last end time from the research queue
        SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
        FROM ResearchQueue
        WHERE settlementId = inSettlementId;

        -- Add to research queue
        INSERT INTO ResearchQueue (settlementId, unitType, startTime, endTime, isActive)
        VALUES (
            inSettlementId,
            inUnitType,
            lastEndTime,
            DATE_ADD(lastEndTime, INTERVAL researchTime SECOND),
            FALSE
        );

    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Not enough resources for research';
    END IF;
END //
DELIMITER ;

-- Procedure: Train Military Unit
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
    DECLARE unitTrainingTime INT;
    DECLARE totalCostWood FLOAT;
    DECLARE totalCostStone FLOAT;
    DECLARE totalCostOre FLOAT;
    DECLARE totalTrainingTime INT;
    DECLARE lastEndTime DATETIME;
    DECLARE kaserneLevel INT DEFAULT 0;
    DECLARE trainingTimeReduction FLOAT DEFAULT 1.0;
    DECLARE isUnitResearched BOOLEAN DEFAULT FALSE;

    -- Check if unit is researched
    SELECT isResearched INTO isUnitResearched
    FROM UnitResearch
    WHERE settlementId = inSettlementId AND unitType = inUnitType;

    IF NOT isUnitResearched THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Unit must be researched before it can be trained';
    END IF;

    -- Get unit configuration
    SELECT costWood, costStone, costOre, trainingTime INTO 
        unitCostWood, unitCostStone, unitCostOre, unitTrainingTime
    FROM MilitaryUnitConfig
    WHERE unitType = inUnitType;

    -- Calculate total costs
    SET totalCostWood = unitCostWood * inCount;
    SET totalCostStone = unitCostStone * inCount;
    SET totalCostOre = unitCostOre * inCount;

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

    -- Check if settlement has enough resources
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= totalCostWood AND
       (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= totalCostStone AND
       (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= totalCostOre THEN

        -- Deduct resources
        UPDATE Settlement
        SET wood = wood - totalCostWood,
            stone = stone - totalCostStone,
            ore = ore - totalCostOre
        WHERE settlementId = inSettlementId;

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
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Not enough resources for unit training';
    END IF;
END //
DELIMITER ;