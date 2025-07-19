-- Kaserne (Barracks) initial data
-- This script adds initial configuration data for Kaserne buildings

USE browsergame;

-- Add Kaserne configuration data for all levels (1-50)
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime)
SELECT 
    'Kaserne' as buildingType,
    level,
    costWood * 1.5 as costWood,  -- Kaserne costs 50% more than base
    costStone * 1.5 as costStone,
    costOre * 2.0 as costOre,    -- Kaserne needs more ore (military equipment)
    settlers * 1.2 as settlers,  -- Needs more settlers to operate
    productionRate * 0.5 as productionRate, -- Produces military units slower than resources
    buildTime * 1.3 as buildTime -- Takes longer to build military structures
FROM BuildingConfig 
WHERE buildingType = 'Rathaus'  -- Use Town Hall as base template
ORDER BY level;

-- Add Kaserne building to all existing settlements
INSERT IGNORE INTO Buildings (settlementId, buildingType, level, visable)
SELECT settlementId, 'Kaserne', 0, 1
FROM Settlement;