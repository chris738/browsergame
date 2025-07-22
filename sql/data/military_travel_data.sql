-- Military unit configuration with speed and loot amounts
-- Insert or update military unit configurations with travel speeds and loot values

USE browsergame;

-- Insert default military unit configurations if they don't exist
INSERT IGNORE INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, costSettlers, trainingTime, defensePower, attackPower, rangedPower, speed, lootAmount) VALUES
-- Guards: Slow but defensive, low loot
('guards', 1, 50, 25, 10, 5, 1, 120, 8, 4, 0, 8, 5.0),
-- Soldiers: Balanced speed and combat, medium loot
('soldiers', 1, 75, 35, 25, 10, 1, 180, 6, 8, 0, 6, 10.0),
-- Archers: Fast with ranged capability, medium loot
('archers', 1, 40, 15, 20, 8, 1, 150, 4, 6, 8, 4, 8.0),
-- Cavalry: Very fast but expensive, high loot capacity
('cavalry', 1, 100, 50, 75, 25, 2, 300, 5, 12, 0, 2, 20.0);

-- Update existing records with speed and loot if they exist but are missing these values
UPDATE MilitaryUnitConfig SET 
    speed = CASE 
        WHEN unitType = 'guards' THEN 8
        WHEN unitType = 'soldiers' THEN 6  
        WHEN unitType = 'archers' THEN 4
        WHEN unitType = 'cavalry' THEN 2
        ELSE speed
    END,
    lootAmount = CASE
        WHEN unitType = 'guards' THEN 5.0
        WHEN unitType = 'soldiers' THEN 10.0
        WHEN unitType = 'archers' THEN 8.0
        WHEN unitType = 'cavalry' THEN 20.0
        ELSE lootAmount
    END
WHERE level = 1 AND (speed IS NULL OR speed = 1 OR lootAmount IS NULL OR lootAmount = 0);

-- Note: Higher speed values mean SLOWER travel (more seconds per block)
-- Guards: 8 seconds per block (slowest)
-- Soldiers: 6 seconds per block 
-- Archers: 4 seconds per block
-- Cavalry: 2 seconds per block (fastest)

-- Insert default travel configuration
INSERT IGNORE INTO TravelConfig (travelType, baseSpeed, description) VALUES
('trade', 5, 'Base travel speed for trades: 5 seconds per block distance'),
('military', 5, 'Base travel speed for military units: 5 seconds per block (modified by unit speed)');