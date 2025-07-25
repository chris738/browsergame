-- Resource Update Events
-- Events for automatic resource generation and queue processing

USE browsergame;

-- Event: Update Resources
DROP EVENT IF EXISTS UpdateResources;

DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE settlement_id INT;
    DECLARE wood_production FLOAT;
    DECLARE stone_production FLOAT;
    DECLARE ore_production FLOAT;
    DECLARE storage_limit FLOAT;
    
    DECLARE settlement_cursor CURSOR FOR SELECT settlementId FROM Settlement;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN settlement_cursor;
    
    settlement_loop: LOOP
        FETCH settlement_cursor INTO settlement_id;
        IF done THEN
            LEAVE settlement_loop;
        END IF;
        
        -- Calculate wood production per second
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO wood_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Holzfäller';
        
        -- Calculate stone production per second  
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO stone_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Steinbruch';
        
        -- Calculate ore production per second
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO ore_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Erzbergwerk';
        
        -- Get storage limit from highest level Lager
        SELECT COALESCE(bc.productionRate, 10000) INTO storage_limit
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Lager'
        ORDER BY b.level DESC LIMIT 1;
        
        -- Update resources with limits
        UPDATE Settlement 
        SET 
            wood = LEAST(wood + wood_production, storage_limit),
            stone = LEAST(stone + stone_production, storage_limit),
            ore = LEAST(ore + ore_production, storage_limit)
        WHERE settlementId = settlement_id;
        
    END LOOP;
    
    CLOSE settlement_cursor;
END //
DELIMITER ;

-- Event: Process Building Queue
DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //
CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    INSERT INTO Buildings (settlementId, buildingType, level, visable)
    SELECT bq.settlementId, bq.buildingType, bq.level, TRUE
    FROM BuildingQueue bq
    WHERE NOW() >= bq.endTime
    ON DUPLICATE KEY UPDATE level = VALUES(level);
    
    DELETE FROM BuildingQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Event: Process Military Training Queue  
DROP EVENT IF EXISTS ProcessMilitaryTrainingQueue;

DELIMITER //
CREATE EVENT ProcessMilitaryTrainingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    INSERT INTO MilitaryUnits (settlementId, unitType, count)
    SELECT mtq.settlementId, mtq.unitType, mtq.count
    FROM MilitaryTrainingQueue mtq
    WHERE NOW() >= mtq.endTime
    ON DUPLICATE KEY UPDATE count = MilitaryUnits.count + VALUES(count);
    
    DELETE FROM MilitaryTrainingQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Event: Process Research Queue
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //
CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    UPDATE UnitResearch ur
    JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE,
        ur.researchedAt = NOW()
    WHERE NOW() >= rq.endTime;
    
    DELETE FROM ResearchQueue WHERE NOW() >= endTime;
END //
DELIMITER ;