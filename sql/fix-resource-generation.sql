-- Fix for automatic resource generation issue
-- This script fixes the UpdateResources database event that was failing due to execution context issues

USE browsergame;

-- Drop existing event if it exists
DROP EVENT IF EXISTS UpdateResources;

-- Create working UpdateResources event with proper storage limits
-- This event runs every second and adds 1 resource per second to each settlement
-- Resources are capped at 10,000 (default storage capacity)
DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    SET SESSION autocommit = 1;
    
    -- Simple resource generation with storage limit
    -- Adds 1 wood, stone, and ore per second to all settlements
    -- Resources are capped at 10,000 to respect storage limits
    UPDATE Settlement 
    SET 
        wood = LEAST(wood + 1, 10000),
        stone = LEAST(stone + 1, 10000), 
        ore = LEAST(ore + 1, 10000);
    
END //
DELIMITER ;

-- Enable the event
ALTER EVENT UpdateResources ENABLE;

-- Log successful completion
SELECT 'UpdateResources event fixed and enabled - automatic resource generation is now working!' AS status;