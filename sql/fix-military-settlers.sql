-- Fix Military Unit settler cost implementation
-- This script fixes the settler cost tracking for military units

USE browsergame;

-- Create a separate table to track settler costs for military units
CREATE TABLE IF NOT EXISTS MilitarySettlerCosts (
    settlementId INT NOT NULL,
    totalSettlerCost INT NOT NULL DEFAULT 0,
    PRIMARY KEY (settlementId),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Initialize settler costs for existing settlements
INSERT IGNORE INTO MilitarySettlerCosts (settlementId, totalSettlerCost)
SELECT settlementId, 0 FROM Settlement;

-- Drop and recreate the procedure with correct settler cost tracking
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

    -- Calculate current free settlers: maxSettlers - (building costs + military costs)
    SELECT 
        GREATEST(0, 
            COALESCE((SELECT SUM(level * 10) FROM Buildings WHERE settlementId = inSettlementId AND buildingType = 'Farm'), 0) + 100 - 
            COALESCE((SELECT SUM(level * 7) FROM Buildings WHERE settlementId = inSettlementId AND buildingType != 'Farm'), 0) -
            currentMilitarySettlerCost
        ) INTO currentFreeSettlers;

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

-- Update the resource calculation function to account for military settler costs
-- Update the existing getResources function to include military settler costs
-- (This would need to be done in the PHP code as well)

SELECT 'Military unit settler cost system fixed!' as result;