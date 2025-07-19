-- Browsergame Database Schema
-- Main database initialization file (restructured and organized)

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
-- TABLE DEFINITIONS (from tables/core_tables.sql)
-- ====================================

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

-- Tabelle: Map
CREATE TABLE Map (
    settlementId INT PRIMARY KEY,
    xCoordinate INT NOT NULL,
    yCoordinate INT NOT NULL,
    UNIQUE (xCoordinate, yCoordinate),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Tabelle: BuildingConfig
CREATE TABLE BuildingConfig (
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    level INT NOT NULL,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    settlers FLOAT NOT NULL DEFAULT 0.0,
    productionRate FLOAT NOT NULL,
    buildTime INT,
    PRIMARY KEY (buildingType, level)
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

-- Tabelle: Buildings
CREATE TABLE Buildings (
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    level INT NOT NULL DEFAULT 0,
    visable boolean NOT NULL DEFAULT false,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, buildingType)
);

-- Tabelle: TradeOffers - for player-to-player trading
CREATE TABLE TradeOffers (
    offerId INT AUTO_INCREMENT PRIMARY KEY,
    fromSettlementId INT NOT NULL,
    offerType ENUM('resource_trade', 'resource_sell', 'resource_buy') NOT NULL,
    -- What the offering player gives
    offerWood FLOAT NOT NULL DEFAULT 0,
    offerStone FLOAT NOT NULL DEFAULT 0,
    offerOre FLOAT NOT NULL DEFAULT 0,
    offerGold INT NOT NULL DEFAULT 0,
    -- What the offering player wants in return
    requestWood FLOAT NOT NULL DEFAULT 0,
    requestStone FLOAT NOT NULL DEFAULT 0,
    requestOre FLOAT NOT NULL DEFAULT 0,
    requestGold INT NOT NULL DEFAULT 0,
    -- Offer details
    maxTrades INT NOT NULL DEFAULT 1, -- How many times this offer can be accepted
    currentTrades INT NOT NULL DEFAULT 0, -- How many times it has been accepted
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiresAt DATETIME NULL, -- Optional expiration
    isActive BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Tabelle: TradeTransactions - log of completed trades
CREATE TABLE TradeTransactions (
    transactionId INT AUTO_INCREMENT PRIMARY KEY,
    offerId INT NOT NULL,
    fromSettlementId INT NOT NULL,
    toSettlementId INT NOT NULL,
    -- Resources traded from offering player
    tradedWood FLOAT NOT NULL DEFAULT 0,
    tradedStone FLOAT NOT NULL DEFAULT 0,
    tradedOre FLOAT NOT NULL DEFAULT 0,
    tradedGold INT NOT NULL DEFAULT 0,
    -- Resources received by offering player
    receivedWood FLOAT NOT NULL DEFAULT 0,
    receivedStone FLOAT NOT NULL DEFAULT 0,
    receivedOre FLOAT NOT NULL DEFAULT 0,
    receivedGold INT NOT NULL DEFAULT 0,
    completedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offerId) REFERENCES TradeOffers(offerId) ON DELETE CASCADE,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (toSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Military tables
CREATE TABLE MilitaryUnits (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 0,
    PRIMARY KEY (settlementId, unitType),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE MilitaryUnitConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    level INT NOT NULL DEFAULT 1,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    costGold INT NOT NULL DEFAULT 0,
    trainingTime INT NOT NULL, -- in seconds
    defensePower INT NOT NULL DEFAULT 1,
    attackPower INT NOT NULL DEFAULT 1,
    rangedPower INT NOT NULL DEFAULT 0,
    PRIMARY KEY (unitType, level)
);

CREATE TABLE MilitaryTrainingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Research tables
CREATE TABLE UnitResearch (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    isResearched BOOLEAN NOT NULL DEFAULT FALSE,
    researchedAt DATETIME NULL,
    PRIMARY KEY (settlementId, unitType),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE ResearchConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    costGold INT NOT NULL DEFAULT 0,
    researchTime INT NOT NULL, -- in seconds
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

-- Trade History table for compatibility
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
-- VIEWS (from views/game_views.sql)
-- ====================================

-- View: Open Building Queue
CREATE VIEW OpenBuildingQueue AS
SELECT 
    queueId,
    settlementId,
    buildingType,
    startTime,
    endTime,
    level,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds, -- Verbleibende Zeit in Sekunden
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage -- Fertigstellungsprozentsatz
FROM BuildingQueue
WHERE NOW() < endTime -- Nur Bauvorhaben, die noch nicht abgeschlossen sind
ORDER BY endTime ASC; -- Sortiere nach dem frühesten Abschlusszeitpunkt

-- View: Building Details with costs considering queue
CREATE OR REPLACE VIEW BuildingDetails AS
SELECT 
    b.settlementId,                           -- Siedlung
    b.buildingType,                           -- Gebäudetyp
    b.level AS currentLevel,                  -- Aktuelles Level aus Buildings
    COALESCE((
        SELECT MAX(bq.level) + 1              -- Höchstes Level in BuildingQueue + 1
        FROM BuildingQueue bq
        WHERE bq.settlementId = b.settlementId
        AND bq.buildingType = b.buildingType
    ), b.level + 1) AS nextLevel,             -- Oder aktuelles Level + 1, falls keine Warteschlange existiert
    bc.costWood,                              -- Kosten für das nächste Level
    bc.costStone,
    bc.costOre,
    COALESCE(bc.productionRate, 0) AS productionRate, -- Produktionsrate für das nächste Level
    bc.settlers,                              -- Siedlerbedarf für das nächste Level
    -- Calculate town hall reduced build time (same logic as UpgradeBuilding procedure)
    ROUND(COALESCE(bc.buildTime, 30) * GREATEST(0.1, 1.0 - (COALESCE(th.level, 0) * 0.05))) AS buildTime
FROM Buildings b
INNER JOIN BuildingConfig bc
ON b.buildingType = bc.buildingType
AND bc.level = COALESCE((
    SELECT MAX(bq.level) + 1              -- Höchstes Level in BuildingQueue + 1
    FROM BuildingQueue bq
    WHERE bq.settlementId = b.settlementId
        AND bq.buildingType = b.buildingType
), b.level + 1)                           -- Oder aktuelles Level + 1, falls keine Warteschlange existiert
-- Left join to get town hall level for build time reduction calculation
LEFT JOIN (
    SELECT settlementId, level
    FROM Buildings 
    WHERE buildingType = 'Rathaus'
) th ON b.settlementId = th.settlementId;

-- View: Settlement Settlers
CREATE OR REPLACE VIEW SettlementSettlers AS
SELECT 
    s.settlementId,
    -- Used settlers from Buildings and BuildingQueue, summing up all levels
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
    -- Max settlers based on Farm level
    COALESCE(
        (
            SELECT bc.productionRate
            FROM Buildings b
            INNER JOIN BuildingConfig bc
            ON b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
            LIMIT 1
        ), 100
    ) AS maxSettlers,
    -- Free settlers (maxSettlers - usedSettlers)
    GREATEST(
        COALESCE(
            (
                SELECT bc.productionRate
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Farm'
                LIMIT 1
            ), 100
        ) - (
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

-- Military views
CREATE OR REPLACE VIEW OpenMilitaryTrainingQueue AS
SELECT 
    queueId,
    settlementId,
    unitType,
    count,
    startTime,
    endTime,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds,
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage
FROM MilitaryTrainingQueue
WHERE NOW() < endTime
ORDER BY endTime ASC;

-- Research views
CREATE OR REPLACE VIEW OpenResearchQueue AS
SELECT 
    queueId,
    settlementId,
    unitType,
    startTime,
    endTime,
    TIMESTAMPDIFF(SECOND, NOW(), endTime) AS remainingTimeSeconds,
    ROUND(
        100 - (TIMESTAMPDIFF(SECOND, NOW(), endTime) * 100.0 / TIMESTAMPDIFF(SECOND, startTime, endTime)),
        2
    ) AS completionPercentage
FROM ResearchQueue
WHERE NOW() < endTime
ORDER BY endTime ASC;

-- ====================================
-- STORED PROCEDURES (from procedures/player_procedures.sql)
-- ====================================

DROP PROCEDURE IF EXISTS CreatePlayerWithSettlement;

DELIMITER //

CREATE PROCEDURE CreatePlayerWithSettlement (IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    DECLARE xCoord INT;
    DECLARE yCoord INT;

    -- Zufällige Koordinaten generieren
    SET xCoord = FLOOR(RAND() * 21) - 10;
    SET yCoord = FLOOR(RAND() * 21) - 10;

    -- Spieler erstellen
    INSERT INTO Spieler (name, punkte) VALUES (playerName, 0);
    SET newPlayerId = LAST_INSERT_ID();

    -- Siedlung erstellen
    INSERT INTO Settlement (name, wood, stone, ore, playerId)
    VALUES (CONCAT(playerName, '_Settlement'), 10000, 10000, 10000, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();

    -- Siedlung auf Karte platzieren
    INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
    VALUES (newSettlementId, xCoord, yCoord);

    -- Insert necessary building types and levels into BuildingConfig
    INSERT IGNORE INTO BuildingConfig (buildingType, level) VALUES
        ('Holzfäller', 1),
        ('Steinbruch', 1),
        ('Erzbergwerk', 1),
        ('Lager', 1),
        ('Farm', 1),
        ('Rathaus', 1),
        ('Markt', 1),
        ('Kaserne', 1);

    -- Gebäude erstellen - nur Startsiedlung mit Rathaus, Ressourcengebäuden und Lager
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
        (newSettlementId, 'Rathaus', 1, true),
        (newSettlementId, 'Holzfäller', 1, true),
        (newSettlementId, 'Steinbruch', 1, true),
        (newSettlementId, 'Erzbergwerk', 1, true),
        (newSettlementId, 'Lager', 1, true);
END //

DELIMITER ;

-- ====================================
-- STORED PROCEDURES (from procedures/building_procedures.sql)
-- ====================================

DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE PROCEDURE UpgradeBuilding(
        IN inSettlementId INT,
        IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
    )
BEGIN
    DECLARE currentBuildingLevel INT;
    DECLARE nextLevel INT;
    DECLARE nextLevelWoodCost FLOAT;
    DECLARE nextLevelStoneCost FLOAT;
    DECLARE nextLevelOreCost FLOAT;
    DECLARE nextLevelSettlerCost FLOAT;
    DECLARE nextBuildTime INT;
    DECLARE lastEndTime DATETIME;
    DECLARE nextEndTime DATETIME;
    DECLARE maxQueueLevel INT;
    DECLARE nextQueueId INT;
    DECLARE nextBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne');
    DECLARE townHallLevel INT DEFAULT 0;
    DECLARE buildTimeReduction FLOAT DEFAULT 1.0;

    
    SELECT level INTO currentBuildingLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

    
    SELECT COALESCE(MAX(level), 0) INTO maxQueueLevel
    FROM BuildingQueue
    WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

    
    IF maxQueueLevel > 0 THEN
        SET nextLevel = maxQueueLevel + 1;
    ELSE
        SET nextLevel = currentBuildingLevel + 1;
    END IF;

    
    SELECT costWood, costStone, costOre, settlers, buildTime INTO 
        nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost, nextLevelSettlerCost, nextBuildTime
    FROM BuildingConfig
    WHERE buildingType = inBuildingType AND level = nextLevel;

    -- Get Town Hall level for build time reduction
    SELECT COALESCE(level, 0) INTO townHallLevel
    FROM Buildings
    WHERE settlementId = inSettlementId AND buildingType = 'Rathaus';

    -- Calculate build time reduction based on Town Hall level (5% per level)
    SET buildTimeReduction = 1.0 - (townHallLevel * 0.05);
    IF buildTimeReduction < 0.1 THEN
        SET buildTimeReduction = 0.1; -- Minimum 10% of original build time
    END IF;
    
    -- Apply build time reduction
    SET nextBuildTime = ROUND(nextBuildTime * buildTimeReduction);

    
    IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelWoodCost AND
    (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelStoneCost AND
    (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelOreCost AND
    (SELECT COALESCE(freeSettlers, 0) FROM SettlementSettlers WHERE settlementId = inSettlementId) >= nextLevelSettlerCost THEN

        
        UPDATE Settlement
        SET wood = wood - nextLevelWoodCost,
            stone = stone - nextLevelStoneCost,
            ore = ore - nextLevelOreCost
        WHERE settlementId = inSettlementId;

        
        SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
        FROM BuildingQueue
        WHERE settlementId = inSettlementId;

        
        INSERT INTO BuildingQueue (settlementId, buildingType, startTime, endTime, isActive, level)
        VALUES (
            inSettlementId,
            inBuildingType,
            lastEndTime,
            DATE_ADD(lastEndTime, INTERVAL nextBuildTime SECOND),
            FALSE,
            nextLevel
        );

        -- If we're upgrading the Town Hall, recalculate all existing queue times
        IF inBuildingType = 'Rathaus' THEN
            CALL UpdateQueueTimesAfterTownHallUpgrade(inSettlementId, nextLevel);
        END IF;

        -- Note: Building completion is now handled by the ProcessBuildingQueue event
        -- which runs every 5 seconds and processes all completed building upgrades

    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
    END IF;
END //
DELIMITER ;

-- Prozedur: Update queue times after Town Hall upgrade
DROP PROCEDURE IF EXISTS UpdateQueueTimesAfterTownHallUpgrade;

DELIMITER //
CREATE PROCEDURE UpdateQueueTimesAfterTownHallUpgrade(
    IN inSettlementId INT,
    IN newTownHallLevel INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE currentQueueId INT;
    DECLARE currentBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne');
    DECLARE currentLevel INT;
    DECLARE originalBuildTime INT;
    DECLARE newBuildTime INT;
    DECLARE newBuildTimeReduction FLOAT;
    DECLARE currentStartTime DATETIME;
    DECLARE previousEndTime DATETIME;
    
    -- Cursor to iterate through all existing queue items for this settlement
    DECLARE queue_cursor CURSOR FOR 
        SELECT queueId, buildingType, level, startTime
        FROM BuildingQueue 
        WHERE settlementId = inSettlementId
        ORDER BY endTime ASC;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Calculate new build time reduction based on new Town Hall level
    SET newBuildTimeReduction = 1.0 - (newTownHallLevel * 0.05);
    IF newBuildTimeReduction < 0.1 THEN
        SET newBuildTimeReduction = 0.1; -- Minimum 10% of original build time
    END IF;
    
    -- Initialize previous end time to current time for the first item
    SET previousEndTime = NOW();
    
    OPEN queue_cursor;
    
    queue_loop: LOOP
        FETCH queue_cursor INTO currentQueueId, currentBuildingType, currentLevel, currentStartTime;
        IF done THEN
            LEAVE queue_loop;
        END IF;
        
        -- Get original build time from config
        SELECT buildTime INTO originalBuildTime
        FROM BuildingConfig 
        WHERE buildingType = currentBuildingType AND level = currentLevel;
        
        -- Calculate new reduced build time
        SET newBuildTime = ROUND(originalBuildTime * newBuildTimeReduction);
        
        -- Update this queue item with new times
        UPDATE BuildingQueue 
        SET startTime = previousEndTime,
            endTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND)
        WHERE queueId = currentQueueId;
        
        -- Set previous end time for next iteration
        SET previousEndTime = DATE_ADD(previousEndTime, INTERVAL newBuildTime SECOND);
        
    END LOOP;
    
    CLOSE queue_cursor;
END //

DELIMITER ;

-- ====================================
-- INITIAL DATA (from data/initial_data.sql)
-- ====================================

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

-- Events for automated processing
-- Event: Process Building Queue
DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //

CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update active buildings that are completed
    UPDATE Buildings b
    INNER JOIN BuildingQueue bq ON b.settlementId = bq.settlementId AND b.buildingType = bq.buildingType
    SET b.level = bq.level, b.visable = TRUE
    WHERE bq.endTime <= NOW() AND bq.isActive = FALSE;

    -- Delete completed building queue entries
    DELETE FROM BuildingQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Event: Update Resources (every 10 seconds)
DROP EVENT IF EXISTS UpdateResources;

DELIMITER //

CREATE EVENT UpdateResources
ON SCHEDULE EVERY 10 SECOND
DO
BEGIN
    UPDATE Settlement s
    SET 
        wood = LEAST(
            s.wood + COALESCE((
                SELECT SUM(bc.productionRate) / 360  -- Divided by 360 for 10-second intervals (3600/10)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        ),
        stone = LEAST(
            s.stone + COALESCE((
                SELECT SUM(bc.productionRate) / 360
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        ),
        ore = LEAST(
            s.ore + COALESCE((
                SELECT SUM(bc.productionRate) / 360
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        );
END //

DELIMITER ;

-- Event: Process Military Training Queue
DROP EVENT IF EXISTS ProcessMilitaryTrainingQueue;

DELIMITER //

CREATE EVENT ProcessMilitaryTrainingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update military units with completed training
    INSERT INTO MilitaryUnits (settlementId, unitType, count)
    SELECT mtq.settlementId, mtq.unitType, mtq.count
    FROM MilitaryTrainingQueue mtq
    WHERE mtq.endTime <= NOW()
    ON DUPLICATE KEY UPDATE count = MilitaryUnits.count + VALUES(count);

    -- Delete completed training queue entries
    DELETE FROM MilitaryTrainingQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Event: Process Research Queue
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //

CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update research completion
    UPDATE UnitResearch ur
    INNER JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE, ur.researchedAt = NOW()
    WHERE rq.endTime <= NOW();

    -- Delete completed research queue entries
    DELETE FROM ResearchQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Database initialization complete
-- Note: All components are now organized in separate files for better maintainability