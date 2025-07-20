-- Fix for automatic resource generation issue
-- This script fixes the UpdateResources database event that was failing due to execution context issues

USE browsergame;

-- Drop existing event if it exists
DROP EVENT IF EXISTS UpdateResources;

-- Create working UpdateResources event with dynamic storage limits based on Lager building level
-- This event runs every second and adds resources based on building production rates
-- Resources are capped at the storage capacity from the highest level Lager building
DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    SET SESSION autocommit = 1;
    
    -- Resource generation with dynamic storage limits based on Lager building level
    UPDATE Settlement s
    SET 
        wood = LEAST(
            wood + (
                SELECT COALESCE(SUM(bc.productionRate), 0) / 3600  -- Convert per hour to per second
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
            ),
            (SELECT COALESCE(bc.productionRate, 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager' ORDER BY b2.level DESC LIMIT 1)
        ),
        stone = LEAST(
            stone + (
                SELECT COALESCE(SUM(bc.productionRate), 0) / 3600  -- Convert per hour to per second
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
            ),
            (SELECT COALESCE(bc.productionRate, 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager' ORDER BY b2.level DESC LIMIT 1)
        ),
        ore = LEAST(
            ore + (
                SELECT COALESCE(SUM(bc.productionRate), 0) / 3600  -- Convert per hour to per second
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
            ),
            (SELECT COALESCE(bc.productionRate, 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager' ORDER BY b2.level DESC LIMIT 1)
        );
    
END //
DELIMITER ;

-- Enable the event
ALTER EVENT UpdateResources ENABLE;

-- Log successful completion
SELECT 'UpdateResources event fixed and enabled - automatic resource generation is now working!' AS status;