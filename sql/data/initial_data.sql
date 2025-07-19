-- Initial Data Population
-- Basic configuration data for the browser game

-- Clear existing data
DELETE FROM BuildingConfig;

-- Building configuration data
INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
-- Holzfäller
('Holzfäller', 1, 100, 100, 100, 1, 3600, 30),
('Holzfäller', 2, 110, 110, 110, 1.1, 3960, 40),
('Holzfäller', 3, 121, 121, 121, 1.21, 4356, 50),
('Holzfäller', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Holzfäller', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),

-- Steinbruch
('Steinbruch', 1, 100, 100, 100, 1, 3600, 30),
('Steinbruch', 2, 110, 110, 110, 1.1, 3960, 40),
('Steinbruch', 3, 121, 121, 121, 1.21, 4356, 50),
('Steinbruch', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Steinbruch', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),

-- Erzbergwerk  
('Erzbergwerk', 1, 100, 100, 100, 1, 3600, 30),
('Erzbergwerk', 2, 110, 110, 110, 1.1, 3960, 40),
('Erzbergwerk', 3, 121, 121, 121, 1.21, 4356, 50),
('Erzbergwerk', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Erzbergwerk', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),

-- Lager
('Lager', 1, 100, 100, 100, 1, 10000, 30),
('Lager', 2, 110, 110, 110, 1.1, 11000, 40),
('Lager', 3, 121, 121, 121, 1.21, 12100, 50),
('Lager', 4, 133.1, 133.1, 133.1, 1.331, 13310, 60),
('Lager', 5, 146.41, 146.41, 146.41, 1.4641, 14641, 70),

-- Farm (provides settlers)
('Farm', 1, 100, 100, 100, 1, 100, 30),
('Farm', 2, 110, 110, 110, 1.1, 110, 40),
('Farm', 3, 121, 121, 121, 1.21, 121, 50),
('Farm', 4, 133.1, 133.1, 133.1, 1.331, 133.1, 60),
('Farm', 5, 146.41, 146.41, 146.41, 1.4641, 146.41, 70),

-- Rathaus (reduces build times)
('Rathaus', 1, 200, 200, 200, 2, 0, 60),
('Rathaus', 2, 220, 220, 220, 2.2, 0, 80),
('Rathaus', 3, 242, 242, 242, 2.42, 0, 100),
('Rathaus', 4, 266.2, 266.2, 266.2, 2.662, 0, 120),
('Rathaus', 5, 292.82, 292.82, 292.82, 2.9282, 0, 140),

-- Markt (enables trading)
('Markt', 1, 150, 100, 50, 2, 0, 45),
('Markt', 2, 165, 110, 55, 2.2, 0, 60),
('Markt', 3, 181.5, 121, 60.5, 2.42, 0, 75),
('Markt', 4, 199.65, 133.1, 66.55, 2.662, 0, 90),
('Markt', 5, 219.615, 146.41, 73.205, 2.9282, 0, 105),

-- Kaserne (military)
('Kaserne', 1, 150, 150, 200, 2, 10, 45),
('Kaserne', 2, 165, 165, 220, 2.2, 11, 60),
('Kaserne', 3, 181.5, 181.5, 242, 2.42, 12.1, 75),
('Kaserne', 4, 199.65, 199.65, 266.2, 2.662, 13.31, 90),
('Kaserne', 5, 219.615, 219.615, 292.82, 2.9282, 14.641, 105);

-- Military unit configuration
INSERT IGNORE INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, trainingTime, defensePower, attackPower, rangedPower) VALUES
('guards', 1, 50, 30, 20, 10, 300, 3, 2, 0),
('soldiers', 1, 80, 60, 40, 25, 600, 5, 6, 0),
('archers', 1, 60, 40, 30, 20, 450, 2, 4, 8),
('cavalry', 1, 120, 80, 60, 50, 900, 8, 10, 0);

-- Research configuration
INSERT IGNORE INTO ResearchConfig (unitType, costWood, costStone, costOre, costGold, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 50, 1800, NULL),
('soldiers', 400, 300, 200, 100, 3600, 'guards'),
('archers', 350, 250, 150, 75, 2700, 'guards'),
('cavalry', 600, 400, 300, 200, 5400, 'soldiers');