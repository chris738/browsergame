-- Create StartResearch procedure
USE browsergame;

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

SELECT 'StartResearch procedure created successfully!' as result;