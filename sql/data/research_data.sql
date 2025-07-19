-- Research system initial data
-- This script adds initial configuration data for the research system

USE browsergame;

-- Insert research configuration data
INSERT IGNORE INTO ResearchConfig (unitType, researchCostWood, researchCostStone, researchCostOre, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 300, NULL), -- 5 minutes, no prerequisite
('soldiers', 400, 300, 250, 600, 'guards'), -- 10 minutes, requires guards
('archers', 350, 200, 300, 450, 'guards'), -- 7.5 minutes, requires guards
('cavalry', 800, 600, 500, 1200, 'soldiers'); -- 20 minutes, requires soldiers

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