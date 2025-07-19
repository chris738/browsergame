-- Database events for automatic processing
-- This script creates events for processing various queues

USE browsergame;

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

-- Event: Process completed research
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //
CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Mark completed research as researched
    UPDATE UnitResearch ur
    JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE,
        ur.researchStartTime = rq.startTime,
        ur.researchEndTime = rq.endTime
    WHERE NOW() >= rq.endTime;
    
    -- Remove completed research queue items
    DELETE FROM ResearchQueue 
    WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Activate the events
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;