-- Research system initial data
-- This script adds initial configuration data for the research system

USE browsergame;

-- Clear existing research configuration data
DELETE FROM ResearchConfig;

-- Insert research configuration data
INSERT INTO ResearchConfig (unitType, costWood, costStone, costOre, costGold, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 50, 1800, NULL),
('soldiers', 400, 300, 200, 100, 3600, 'guards'),
('archers', 350, 250, 150, 75, 2700, 'guards'),
('cavalry', 600, 400, 300, 200, 5400, 'soldiers');

-- Initialize research status for all existing settlements (nothing researched initially)
INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'guards', FALSE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'soldiers', FALSE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'archers', FALSE
FROM Settlement s;

INSERT IGNORE INTO UnitResearch (settlementId, unitType, isResearched)
SELECT s.settlementId, 'cavalry', FALSE
FROM Settlement s;