-- Military unit configuration data
-- This script adds initial military unit configuration data

USE browsergame;

-- Insert military unit configurations (update existing or ignore if exists)
INSERT INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, costSettlers, trainingTime, defensePower, attackPower, rangedPower, speed) VALUES
('guards', 1, 50, 30, 20, 10, 1, 30, 2, 0, 0, 1),
('soldiers', 1, 80, 60, 40, 25, 2, 60, 1, 3, 0, 1),
('archers', 1, 100, 40, 60, 20, 1, 90, 1, 0, 4, 1),
('cavalry', 1, 150, 100, 120, 50, 3, 180, 2, 5, 0, 2)
ON DUPLICATE KEY UPDATE
    costWood = VALUES(costWood),
    costStone = VALUES(costStone),
    costOre = VALUES(costOre),
    costGold = VALUES(costGold),
    costSettlers = VALUES(costSettlers),
    trainingTime = VALUES(trainingTime),
    defensePower = VALUES(defensePower),
    attackPower = VALUES(attackPower),
    rangedPower = VALUES(rangedPower),
    speed = VALUES(speed);

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