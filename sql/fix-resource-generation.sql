-- Fix for automatic resource generation issue
-- This script fixes the UpdateResources database event that was failing due to SQL syntax issues
-- The original event had table aliasing conflicts that caused MySQL errors

USE browsergame;

-- Drop existing event if it exists
DROP EVENT IF EXISTS UpdateResources;

-- Create working UpdateResources event using cursor-based approach
-- This event runs every second and adds resources based on building production rates
-- Resources are capped at the storage capacity from the highest level Lager building
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
        
        -- Calculate wood production per second (production rates are per hour, so divide by 3600)
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

-- Enable the event
ALTER EVENT UpdateResources ENABLE;

-- Log successful completion
SELECT 'UpdateResources event fixed and enabled - automatic resource generation is now working!' AS status;