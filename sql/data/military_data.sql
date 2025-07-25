-- Military unit configuration data
-- This script adds initial military unit configuration data

USE browsergame;

-- Clear existing military unit configuration data
DELETE FROM MilitaryUnitConfig;

-- Insert military unit configurations
INSERT INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, costSettlers, trainingTime, defensePower, attackPower, rangedPower, speed, lootAmount) VALUES
('guards', 1, 50, 30, 20, 10, 1, 300, 3, 2, 0, 1, 5.0),
('soldiers', 1, 80, 60, 40, 25, 2, 600, 5, 6, 0, 1, 10.0),
('archers', 1, 60, 40, 30, 20, 1, 450, 2, 4, 8, 1, 8.0),
('cavalry', 1, 120, 80, 60, 50, 3, 900, 8, 10, 0, 2, 15.0);

-- Initialize military units for all existing settlements
INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'guards', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'soldiers', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'archers', 0
FROM Settlement s;

INSERT IGNORE INTO MilitaryUnits (settlementId, unitType, count)
SELECT s.settlementId, 'cavalry', 0
FROM Settlement s;

-- Update existing configurations for settler costs
UPDATE MilitaryUnitConfig SET costSettlers = 1 WHERE costSettlers IS NULL OR costSettlers = 0;