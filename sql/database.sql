-- Browsergame Database Schema
-- Organized database initialization file with modular structure
-- This file orchestrates the database creation using organized SQL files

-- Setup
-- Löschen der bestehenden Datenbank
DROP DATABASE IF EXISTS browsergame;

-- Erstellung einer neuen Datenbank
CREATE DATABASE browsergame;

-- Erstellung eines neuen Benutzers mit Standard-Zugangsdaten
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';

-- Zusätzlich: Root-Benutzer Zugriff sicherstellen (für Standard-Installation)
-- Root hat bereits Vollzugriff, aber explizit für diese Datenbank freigeben
GRANT ALL PRIVILEGES ON browsergame.* TO 'root'@'localhost';

-- Berechtigungen für den Benutzer
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';

-- Wechsel zur neuen Datenbank
USE browsergame;

-- Enable global event scheduler
SET GLOBAL event_scheduler = ON;

-- ====================================
-- CORE TABLES (from tables/core_tables.sql)
-- ====================================
-- NOTE: This file now references organized SQL files
-- To rebuild database, use: mysql -u root -p < database.sql
-- Or run individual files in order as needed

-- For now, including core table definitions inline for compatibility
-- Tabelle: Spieler
CREATE TABLE Spieler (
    playerId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    punkte INT NOT NULL DEFAULT 0,
    gold INT NOT NULL DEFAULT 500,
    UNIQUE (name)
);

-- Tabelle: Settlement
CREATE TABLE Settlement (
    settlementId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    wood FLOAT NOT NULL DEFAULT 1000.0,
    stone FLOAT NOT NULL DEFAULT 1000.0,
    ore FLOAT NOT NULL DEFAULT 1000.0,
    playerId INT NOT NULL,
    FOREIGN KEY (playerId) REFERENCES Spieler(playerId) ON DELETE CASCADE
);

-- Tabelle: BuildingConfig
CREATE TABLE BuildingConfig (
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    level INT NOT NULL,
    costWood FLOAT NOT NULL DEFAULT 100,
    costStone FLOAT NOT NULL DEFAULT 100,
    costOre FLOAT NOT NULL DEFAULT 100,
    settlers FLOAT NOT NULL DEFAULT 1.0,
    productionRate FLOAT NOT NULL DEFAULT 5.0,
    buildTime INT NOT NULL DEFAULT 30,
    PRIMARY KEY (buildingType, level)
);

-- Tabelle: Buildings
CREATE TABLE Buildings (
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    level INT NOT NULL DEFAULT 0,
    visable TINYINT(1) NOT NULL DEFAULT 1,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, buildingType)
);

-- Tabelle: BuildingQueue
CREATE TABLE BuildingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    level INT NOT NULL DEFAULT 0,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Military and Trading tables
CREATE TABLE MilitaryUnitConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    level INT NOT NULL DEFAULT 1,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    costGold FLOAT NOT NULL DEFAULT 0,
    costSettlers INT NOT NULL DEFAULT 1,
    trainingTime INT NOT NULL,
    defensePower INT NOT NULL DEFAULT 0,
    attackPower INT NOT NULL DEFAULT 0,
    rangedPower INT NOT NULL DEFAULT 0,
    speed INT NOT NULL DEFAULT 1,
    PRIMARY KEY (unitType, level)
);

CREATE TABLE MilitaryUnits (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

CREATE TABLE MilitaryTrainingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 1,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE UnitResearch (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    isResearched BOOLEAN NOT NULL DEFAULT FALSE,
    researchStartTime DATETIME NULL,
    researchEndTime DATETIME NULL,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

CREATE TABLE ResearchConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    researchCostWood FLOAT NOT NULL,
    researchCostStone FLOAT NOT NULL,
    researchCostOre FLOAT NOT NULL,
    researchTime INT NOT NULL,
    prerequisiteUnit ENUM('guards', 'soldiers', 'archers', 'cavalry') NULL,
    PRIMARY KEY (unitType)
);

CREATE TABLE ResearchQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE TradeOffers (
    offerId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    wood FLOAT NOT NULL DEFAULT 0,
    stone FLOAT NOT NULL DEFAULT 0,
    ore FLOAT NOT NULL DEFAULT 0,
    gold INT NOT NULL DEFAULT 0,
    exchangeRate FLOAT NOT NULL DEFAULT 1.0,
    maxTrades INT NOT NULL DEFAULT 1,
    currentTrades INT NOT NULL DEFAULT 0,
    isActive BOOLEAN NOT NULL DEFAULT TRUE,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiresAt DATETIME NULL,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE TradeHistory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fromSettlementId INT NOT NULL,
    toSettlementId INT NOT NULL,
    wood FLOAT NOT NULL DEFAULT 0,
    stone FLOAT NOT NULL DEFAULT 0,
    ore FLOAT NOT NULL DEFAULT 0,
    gold INT NOT NULL DEFAULT 0,
    completedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (toSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- ====================================
-- ESSENTIAL PROCEDURES
-- ====================================

-- Procedure: CreatePlayerWithSettlement
DROP PROCEDURE IF EXISTS CreatePlayerWithSettlement;

DELIMITER //
CREATE PROCEDURE CreatePlayerWithSettlement(IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    
    INSERT INTO Spieler (name, punkte, gold) VALUES (playerName, 0, 500);
    SET newPlayerId = LAST_INSERT_ID();
    
    INSERT INTO Settlement (name, wood, stone, ore, playerId) 
    VALUES (CONCAT(playerName, 's Settlement'), 1000.0, 1000.0, 1000.0, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();
    
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
    (newSettlementId, 'Rathaus', 1, 1),
    (newSettlementId, 'Holzfäller', 1, 1),
    (newSettlementId, 'Steinbruch', 1, 1),
    (newSettlementId, 'Erzbergwerk', 1, 1),
    (newSettlementId, 'Lager', 1, 1),
    (newSettlementId, 'Farm', 1, 1),
    (newSettlementId, 'Markt', 0, 1),
    (newSettlementId, 'Kaserne', 0, 1);
    
    INSERT INTO MilitaryUnits (settlementId, unitType, count) VALUES
    (newSettlementId, 'guards', 0),
    (newSettlementId, 'soldiers', 0),
    (newSettlementId, 'archers', 0),
    (newSettlementId, 'cavalry', 0);
    
    INSERT INTO UnitResearch (settlementId, unitType, isResearched) VALUES
    (newSettlementId, 'guards', FALSE),
    (newSettlementId, 'soldiers', FALSE),
    (newSettlementId, 'archers', FALSE),
    (newSettlementId, 'cavalry', FALSE);
END //
DELIMITER ;

-- ====================================
-- ESSENTIAL VIEWS
-- ====================================

-- View: Open Building Queue
CREATE OR REPLACE VIEW OpenBuildingQueue AS
SELECT 
    queueId,
    settlementId,
    buildingType,
    startTime,
    endTime,
    level,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds,
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage
FROM BuildingQueue
WHERE NOW() < endTime
ORDER BY endTime ASC;

-- View: SettlementSettlers
CREATE OR REPLACE VIEW SettlementSettlers AS
SELECT 
    s.settlementId,
    (
        COALESCE(
            (
                SELECT SUM(totalSettlers) 
                FROM (
                    SELECT b.settlementId, b.buildingType, SUM(bc.settlers) AS totalSettlers
                    FROM Buildings b
                    INNER JOIN BuildingConfig bc
                    ON b.buildingType = bc.buildingType AND bc.level <= b.level
                    GROUP BY b.settlementId, b.buildingType
                ) AS BuildingSummed
                WHERE BuildingSummed.settlementId = s.settlementId
            ), 0
        ) + 
        COALESCE(
            (
                SELECT SUM(totalSettlers) 
                FROM (
                    SELECT bq.settlementId, bq.buildingType, SUM(bc.settlers) AS totalSettlers
                    FROM BuildingQueue bq
                    INNER JOIN BuildingConfig bc
                    ON bq.buildingType = bc.buildingType AND bc.level <= bq.level
                    GROUP BY bq.settlementId, bq.buildingType
                ) AS QueueSummed
                WHERE QueueSummed.settlementId = s.settlementId
            ), 0
        )
    ) AS usedSettlers,
    (100 + COALESCE(
        (
            SELECT bc.productionRate
            FROM Buildings b
            INNER JOIN BuildingConfig bc
            ON b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
            LIMIT 1
        ), 0
    )) AS maxSettlers,
    GREATEST(
        (100 + COALESCE(
            (
                SELECT bc.productionRate
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
                LIMIT 1
            ), 0
        )) - (
            COALESCE(
                (
                    SELECT SUM(totalSettlers) 
                    FROM (
                        SELECT b.settlementId, b.buildingType, SUM(bc.settlers) AS totalSettlers
                        FROM Buildings b
                        INNER JOIN BuildingConfig bc
                        ON b.buildingType = bc.buildingType AND bc.level <= b.level
                        GROUP BY b.settlementId, b.buildingType
                    ) AS BuildingSummed
                    WHERE BuildingSummed.settlementId = s.settlementId
                ), 0
            ) + 
            COALESCE(
                (
                    SELECT SUM(totalSettlers) 
                    FROM (
                        SELECT bq.settlementId, bq.buildingType, SUM(bc.settlers) AS totalSettlers
                        FROM BuildingQueue bq
                        INNER JOIN BuildingConfig bc
                        ON bq.buildingType = bc.buildingType AND bc.level <= bq.level
                        GROUP BY bq.settlementId, bq.buildingType
                    ) AS QueueSummed
                    WHERE QueueSummed.settlementId = s.settlementId
                ), 0
            )
        ), 0
    ) AS freeSettlers
FROM Settlement s;

-- ====================================
-- ESSENTIAL DATA
-- ====================================

-- Insert building configuration data for all building types
INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
-- Town Hall
('Rathaus', 1, 100, 100, 100, 1, 0, 30),
('Rathaus', 2, 110, 110, 110, 1, 0, 33),
('Rathaus', 3, 121, 121, 121, 1, 0, 36),
-- Lumberjack
('Holzfäller', 1, 100, 100, 100, 1, 5, 30),
('Holzfäller', 2, 110, 110, 110, 1, 5.5, 33),
('Holzfäller', 3, 121, 121, 121, 1, 6.05, 36),
-- Quarry
('Steinbruch', 1, 100, 100, 100, 1, 5, 30),
('Steinbruch', 2, 110, 110, 110, 1, 5.5, 33),
('Steinbruch', 3, 121, 121, 121, 1, 6.05, 36),
-- Mine
('Erzbergwerk', 1, 100, 100, 100, 1, 5, 30),
('Erzbergwerk', 2, 110, 110, 110, 1, 5.5, 33),
('Erzbergwerk', 3, 121, 121, 121, 1, 6.05, 36),
-- Storage
('Lager', 1, 100, 100, 100, 1, 5000, 30),
('Lager', 2, 110, 110, 110, 1, 5500, 33),
('Lager', 3, 121, 121, 121, 1, 6050, 36),
-- Farm
('Farm', 1, 100, 100, 100, 1, 10, 30),
('Farm', 2, 110, 110, 110, 1, 11, 33),
('Farm', 3, 121, 121, 121, 1, 12, 36),
-- Market
('Markt', 1, 100, 100, 100, 1, 0, 30),
('Markt', 2, 110, 110, 110, 1, 0, 33),
('Markt', 3, 121, 121, 121, 1, 0, 36),
-- Kaserne
('Kaserne', 1, 150, 150, 200, 1, 0, 40),
('Kaserne', 2, 165, 165, 220, 1, 0, 44),
('Kaserne', 3, 181, 181, 242, 1, 0, 48);

-- Insert military unit configurations
INSERT INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, costSettlers, trainingTime, defensePower, attackPower, rangedPower, speed) VALUES
('guards', 1, 50, 30, 20, 10, 1, 30, 2, 0, 0, 1),
('soldiers', 1, 80, 60, 40, 25, 2, 60, 1, 3, 0, 1),
('archers', 1, 100, 40, 60, 20, 1, 90, 1, 0, 4, 1),
('cavalry', 1, 150, 100, 120, 50, 3, 180, 2, 5, 0, 2);

-- Insert research configurations
INSERT INTO ResearchConfig (unitType, researchCostWood, researchCostStone, researchCostOre, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 300, NULL),
('soldiers', 400, 300, 250, 600, 'guards'),
('archers', 350, 200, 300, 450, 'guards'),
('cavalry', 800, 600, 500, 1200, 'soldiers');

-- ====================================
-- DATABASE EVENTS
-- ====================================

-- Event: Update Resources
DROP EVENT IF EXISTS UpdateResources;

DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    UPDATE Settlement s
    SET 
        wood = LEAST(
            wood + (
                SELECT COALESCE(SUM(bc.productionRate), 0)
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
            ),
            (SELECT COALESCE(SUM(bc.productionRate), 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager')
        ),
        stone = LEAST(
            stone + (
                SELECT COALESCE(SUM(bc.productionRate), 0)
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
            ),
            (SELECT COALESCE(SUM(bc.productionRate), 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager')
        ),
        ore = LEAST(
            ore + (
                SELECT COALESCE(SUM(bc.productionRate), 0)
                FROM Buildings b
                JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
            ),
            (SELECT COALESCE(SUM(bc.productionRate), 10000) FROM Buildings b2 JOIN BuildingConfig bc2 ON b2.buildingType = bc2.buildingType AND b2.level = bc2.level WHERE b2.settlementId = s.settlementId AND b2.buildingType = 'Lager')
        );
END //
DELIMITER ;

-- Event: Process Building Queue
DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //
CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    INSERT INTO Buildings (settlementId, buildingType, level, visable)
    SELECT bq.settlementId, bq.buildingType, bq.level, 1
    FROM BuildingQueue bq
    WHERE NOW() >= bq.endTime
    ON DUPLICATE KEY UPDATE level = VALUES(level);
    
    DELETE FROM BuildingQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Event: Process Military Training Queue
DROP EVENT IF EXISTS ProcessMilitaryTrainingQueue;

DELIMITER //
CREATE EVENT ProcessMilitaryTrainingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    INSERT INTO MilitaryUnits (settlementId, unitType, count)
    SELECT mtq.settlementId, mtq.unitType, mtq.count
    FROM MilitaryTrainingQueue mtq
    WHERE NOW() >= mtq.endTime
    ON DUPLICATE KEY UPDATE count = MilitaryUnits.count + VALUES(count);
    
    DELETE FROM MilitaryTrainingQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Event: Process Research Queue
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //
CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    UPDATE UnitResearch ur
    JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE,
        ur.researchStartTime = rq.startTime,
        ur.researchEndTime = rq.endTime
    WHERE NOW() >= rq.endTime;
    
    DELETE FROM ResearchQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- Activate events (disabled for now to avoid conflicts during initialization)
-- These will be enabled after all initialization is complete
-- ALTER EVENT UpdateResources ENABLE;
-- ALTER EVENT ProcessBuildingQueue ENABLE;
-- ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
-- ALTER EVENT ProcessResearchQueue ENABLE;

-- Database initialization complete
SELECT 'Browsergame database initialized with organized structure!' AS status;