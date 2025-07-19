-- Fix ProcessBuildingQueue event to handle both new buildings and upgrades
USE browsergame;

DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //

CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Handle completed building upgrades/construction
    
    -- First, create new building entries for buildings that don't exist yet
    INSERT INTO Buildings (settlementId, buildingType, level, visable)
    SELECT bq.settlementId, bq.buildingType, bq.level, TRUE
    FROM BuildingQueue bq
    LEFT JOIN Buildings b ON bq.settlementId = b.settlementId AND bq.buildingType = b.buildingType
    WHERE NOW() >= bq.endTime 
    AND b.buildingType IS NULL;  -- Building doesn't exist yet
    
    -- Then, update existing building levels
    UPDATE Buildings b
    INNER JOIN BuildingQueue bq ON b.settlementId = bq.settlementId AND b.buildingType = bq.buildingType
    SET b.level = bq.level, b.visable = TRUE
    WHERE NOW() >= bq.endTime;
    
    -- Finally, remove completed queue items
    DELETE FROM BuildingQueue 
    WHERE NOW() >= endTime;
    
END //

DELIMITER ;

SELECT 'ProcessBuildingQueue event updated to handle new buildings' as result;