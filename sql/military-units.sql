-- Add Military Unit system to existing database schema
-- This script adds tables for military units and training

USE browsergame;

-- Add speed column to existing MilitaryUnitConfig table if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'MilitaryUnitConfig' 
     AND column_name = 'speed' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE MilitaryUnitConfig ADD COLUMN speed INT NOT NULL DEFAULT 1',
    'SELECT ''speed column already exists'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert military unit configurations (update existing or ignore if exists)
INSERT INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, trainingTime, defensePower, attackPower, rangedPower, speed) VALUES
('guards', 1, 50, 30, 20, 10, 30, 2, 0, 0, 1),
('soldiers', 1, 80, 60, 40, 25, 60, 1, 3, 0, 1),
('archers', 1, 100, 40, 60, 20, 90, 1, 0, 4, 1),
('cavalry', 1, 150, 100, 120, 50, 180, 2, 5, 0, 2)
ON DUPLICATE KEY UPDATE
    costWood = VALUES(costWood),
    costStone = VALUES(costStone),
    costOre = VALUES(costOre),
    costGold = VALUES(costGold),
    trainingTime = VALUES(trainingTime),
    defensePower = VALUES(defensePower),
    attackPower = VALUES(attackPower),
    rangedPower = VALUES(rangedPower),
    speed = VALUES(speed);

-- Table: MilitaryUnits - Store unit counts per settlement
CREATE TABLE IF NOT EXISTS MilitaryUnits (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

-- Table: MilitaryTrainingQueue - Handle unit training queue
CREATE TABLE IF NOT EXISTS MilitaryTrainingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 1, -- how many units to train
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Initialize military units for all existing settlements
INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'guards', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'soldiers', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'archers', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'cavalry', 0
FROM Settlement s;

-- Event: Process completed military unit training
DROP EVENT IF EXISTS ProcessMilitaryTrainingQueue;

DELIMITER //
CREATE EVENT ProcessMilitaryTrainingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Add completed units to military units table
    INSERT INTO MilitaryUnits (settlementId, unitType, count)
    SELECT mtq.settlementId, mtq.unitType, mtq.count
    FROM MilitaryTrainingQueue mtq
    WHERE NOW() >= mtq.endTime
    ON DUPLICATE KEY UPDATE count = MilitaryUnits.count + VALUES(count);
    
    -- Remove completed training queue items
    DELETE FROM MilitaryTrainingQueue 
    WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Activate the event
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;

-- View: Open Military Training Queue
CREATE OR REPLACE VIEW OpenMilitaryTrainingQueue AS
SELECT 
    queueId,
    settlementId,
    unitType,
    count,
    startTime,
    endTime,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds,
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage
FROM MilitaryTrainingQueue
WHERE NOW() < endTime
ORDER BY endTime ASC;

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

SELECT 'Military unit system successfully added to database!' as result;