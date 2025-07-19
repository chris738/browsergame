-- Add Research System for Military Units
-- This script adds the research system where units must be researched before they can be trained

USE browsergame;

-- Table: UnitResearch - Tracks which units have been researched by each settlement
CREATE TABLE IF NOT EXISTS UnitResearch (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    isResearched BOOLEAN NOT NULL DEFAULT FALSE,
    researchStartTime DATETIME NULL,
    researchEndTime DATETIME NULL,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

-- Table: ResearchConfig - Configuration for research costs and times
CREATE TABLE IF NOT EXISTS ResearchConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    researchCostWood FLOAT NOT NULL,
    researchCostStone FLOAT NOT NULL,
    researchCostOre FLOAT NOT NULL,
    researchTime INT NOT NULL, -- in seconds
    prerequisiteUnit ENUM('guards', 'soldiers', 'archers', 'cavalry') NULL, -- unit that must be researched first
    PRIMARY KEY (unitType)
);

-- Insert research configuration data
INSERT INTO ResearchConfig (unitType, researchCostWood, researchCostStone, researchCostOre, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 300, NULL), -- 5 minutes, no prerequisite
('soldiers', 400, 300, 200, 600, 'guards'), -- 10 minutes, requires guards
('archers', 500, 200, 400, 900, 'guards'), -- 15 minutes, requires guards  
('cavalry', 800, 600, 700, 1800, 'soldiers'); -- 30 minutes, requires soldiers

-- Initialize research for all existing settlements - only guards are initially available
INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'guards', TRUE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'soldiers', FALSE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'archers', FALSE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'cavalry', FALSE
FROM Settlement s;

-- Table: ResearchQueue - Handle research queue (similar to building queue)
CREATE TABLE IF NOT EXISTS ResearchQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- View: OpenResearchQueue - Show active research in progress
CREATE OR REPLACE VIEW OpenResearchQueue AS
SELECT 
    queueId,
    settlementId,
    unitType,
    startTime,
    endTime,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds,
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage
FROM ResearchQueue
WHERE NOW() < endTime
ORDER BY endTime ASC;

-- Event: Process completed research
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //
CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Mark completed research as researched
    UPDATE UnitResearch ur
    INNER JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE
    WHERE NOW() >= rq.endTime;
    
    -- Remove completed research queue items
    DELETE FROM ResearchQueue 
    WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Activate the event
ALTER EVENT ProcessResearchQueue ENABLE;

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

    -- Check prerequisite
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

SELECT 'Research system successfully added to database!' as result;