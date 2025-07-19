-- Fix for building unlock bug
-- When Storage/Farm reach level 5, Market/Barracks should unlock at level 0, not level 1

USE browsergame;

-- Step 1: Change Buildings table default level from 1 to 0
ALTER TABLE Buildings ALTER COLUMN level SET DEFAULT 0;

-- Step 2: Reset Market and Barracks to level 0 (they should start as unlocked but not built)
UPDATE Buildings 
SET level = 0 
WHERE buildingType IN ('Markt', 'Kaserne');

-- Step 3: Add more building configs to prevent missing level configs breaking the BuildingDetails view
-- Add Storage levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Lager', 6, 161.051, 161.051, 161.051, 1.61051, 16105.1, 77),
('Lager', 7, 177.156, 177.156, 177.156, 1.77156, 17715.61, 85),
('Lager', 8, 194.872, 194.872, 194.872, 1.94872, 19487.17, 93),
('Lager', 9, 214.359, 214.359, 214.359, 2.14359, 21435.89, 102),
('Lager', 10, 235.795, 235.795, 235.795, 2.35795, 23579.48, 112);

-- Add Farm levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Farm', 6, 161.051, 161.051, 161.051, 1.61051, 161.051, 77),
('Farm', 7, 177.156, 177.156, 177.156, 1.77156, 177.156, 85),
('Farm', 8, 194.872, 194.872, 194.872, 1.94872, 194.872, 93),
('Farm', 9, 214.359, 214.359, 214.359, 2.14359, 214.359, 102),
('Farm', 10, 235.795, 235.795, 235.795, 2.35795, 235.795, 112);

-- Step 4: Add Market levels 6-10 
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Markt', 6, 241.577, 161.051, 80.525, 3.22102, 0, 116),
('Markt', 7, 265.734, 177.156, 88.578, 3.54312, 0, 127),
('Markt', 8, 292.308, 194.872, 97.436, 3.89743, 0, 140),
('Markt', 9, 321.539, 214.359, 107.18, 4.28718, 0, 154),
('Markt', 10, 353.693, 235.795, 117.898, 4.7159, 0, 169);

-- Step 5: Add Barracks levels 6-10
INSERT IGNORE INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
('Kaserne', 6, 241.577, 241.577, 322.102, 3.22102, 16.105, 116),
('Kaserne', 7, 265.734, 265.734, 354.312, 3.54312, 17.716, 127),
('Kaserne', 8, 292.308, 292.308, 389.743, 3.89743, 19.487, 140),
('Kaserne', 9, 321.539, 321.539, 428.718, 4.28718, 21.436, 154),
('Kaserne', 10, 353.693, 353.693, 471.59, 4.7159, 23.579, 169);

SELECT 'Building unlock bug fix applied successfully!' as result;