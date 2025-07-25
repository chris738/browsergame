-- Browsergame Database Schema - Modular Version
-- This file orchestrates the database creation using organized SQL files in the correct order
-- 
-- Usage: mysql -u root -p < database_modular.sql
-- 
-- The database is built in the following order:
-- 1. Database and user setup
-- 2. Core tables
-- 3. Additional tables (military, research, kaserne)
-- 4. Initial data and configuration
-- 5. Stored procedures
-- 6. Views (simple first, then enhanced)
-- 7. Database events
-- 8. Event scheduler activation

-- ====================================
-- DATABASE AND USER SETUP
-- ====================================

-- Drop and recreate database
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;

-- Create users with proper permissions
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
CREATE USER IF NOT EXISTS 'browsergame'@'%' IDENTIFIED BY 'sicheresPasswort';

-- Grant permissions
GRANT ALL PRIVILEGES ON browsergame.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'%';

-- Switch to the new database
USE browsergame;

-- Enable global event scheduler (will be enabled at the end)
-- SET GLOBAL event_scheduler = ON;

-- ====================================
-- MODULAR COMPONENT LOADING
-- ====================================

-- Note: This file references organized SQL files that should be executed in order
-- For deployment, the content of these files should be included inline
-- For development, reference these files for better maintainability

-- The following files need to be loaded in this exact order:

-- 1. Core tables (essential game structure)
-- SOURCE sql/tables/core_tables.sql;

-- 2. Military system tables
-- SOURCE sql/tables/military_tables.sql;

-- 3. Research system tables  
-- SOURCE sql/tables/research_tables.sql;

-- 4. Kaserne system tables
-- SOURCE sql/tables/kaserne_tables.sql;

-- 5. Initial data and configuration
-- SOURCE sql/data/initial_data.sql;

-- 6. Military system data
-- SOURCE sql/data/military_data.sql;

-- 7. Research system data
-- SOURCE sql/data/research_data.sql;

-- 8. Kaserne system data
-- SOURCE sql/data/kaserne_data.sql;

-- 9. Player management procedures
-- SOURCE sql/procedures/player_procedures.sql;

-- 10. Building management procedures
-- SOURCE sql/procedures/building_procedures.sql;

-- 11. Military management procedures
-- SOURCE sql/procedures/military_procedures.sql;

-- 12. Core game views
-- SOURCE sql/views/game_views.sql;

-- 13. Enhanced views for simplified PHP access
-- SOURCE sql/views/enhanced_views.sql;

-- 14. Database events for automated processing
-- SOURCE sql/data/database_events.sql;

-- ====================================
-- INLINE COMPONENTS FOR COMPATIBILITY
-- ====================================
-- Until modular loading is implemented, include essential components inline

-- Core Tables
CREATE TABLE Spieler (
    playerId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    punkte INT NOT NULL DEFAULT 0,
    gold INT NOT NULL DEFAULT 500,
    UNIQUE (name)
);

CREATE TABLE Settlement (
    settlementId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    wood FLOAT NOT NULL DEFAULT 1000.0,
    stone FLOAT NOT NULL DEFAULT 1000.0,
    ore FLOAT NOT NULL DEFAULT 1000.0,
    playerId INT NOT NULL,
    FOREIGN KEY (playerId) REFERENCES Spieler(playerId) ON DELETE CASCADE
);

CREATE TABLE Map (
    settlementId INT PRIMARY KEY,
    xCoordinate INT NOT NULL,
    yCoordinate INT NOT NULL,
    UNIQUE (xCoordinate, yCoordinate),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

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

CREATE TABLE Buildings (
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL,
    level INT NOT NULL DEFAULT 0,
    visable BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, buildingType)
);

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

-- Military Tables
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
    costSettlers INT NOT NULL DEFAULT 1,
    trainingTime INT NOT NULL,
    defensePower INT NOT NULL DEFAULT 1,
    attackPower INT NOT NULL DEFAULT 1,
    rangedPower INT NOT NULL DEFAULT 0,
    speed INT NOT NULL DEFAULT 1,
    lootAmount FLOAT NOT NULL DEFAULT 10.0,
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

CREATE TABLE MilitarySettlerCosts (
    settlementId INT NOT NULL,
    totalSettlerCost INT NOT NULL DEFAULT 0,
    PRIMARY KEY (settlementId),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Research Tables
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

-- Trading Tables
CREATE TABLE TradeOffers (
    offerId INT AUTO_INCREMENT PRIMARY KEY,
    fromSettlementId INT NOT NULL,
    offerType ENUM('resource_trade', 'resource_sell', 'resource_buy') NOT NULL,
    offerWood FLOAT NOT NULL DEFAULT 0,
    offerStone FLOAT NOT NULL DEFAULT 0,
    offerOre FLOAT NOT NULL DEFAULT 0,
    offerGold INT NOT NULL DEFAULT 0,
    requestWood FLOAT NOT NULL DEFAULT 0,
    requestStone FLOAT NOT NULL DEFAULT 0,
    requestOre FLOAT NOT NULL DEFAULT 0,
    requestGold INT NOT NULL DEFAULT 0,
    maxTrades INT NOT NULL DEFAULT 1,
    currentTrades INT NOT NULL DEFAULT 0,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiresAt DATETIME NULL,
    isActive BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

CREATE TABLE TradeTransactions (
    transactionId INT AUTO_INCREMENT PRIMARY KEY,
    offerId INT NOT NULL,
    fromSettlementId INT NOT NULL,
    toSettlementId INT NOT NULL,
    tradedWood FLOAT NOT NULL DEFAULT 0,
    tradedStone FLOAT NOT NULL DEFAULT 0,
    tradedOre FLOAT NOT NULL DEFAULT 0,
    tradedGold INT NOT NULL DEFAULT 0,
    receivedWood FLOAT NOT NULL DEFAULT 0,
    receivedStone FLOAT NOT NULL DEFAULT 0,
    receivedOre FLOAT NOT NULL DEFAULT 0,
    receivedGold INT NOT NULL DEFAULT 0,
    completedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offerId) REFERENCES TradeOffers(offerId) ON DELETE CASCADE,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (toSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Legacy compatibility table
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
-- TRAVEL SYSTEM TABLES
-- ====================================

-- Table: TravelConfig - Configuration for travel times
CREATE TABLE IF NOT EXISTS TravelConfig (
    configId INT AUTO_INCREMENT PRIMARY KEY,
    travelType ENUM('trade', 'military') NOT NULL,
    baseSpeed INT NOT NULL DEFAULT 5, -- seconds per block distance
    description VARCHAR(255),
    UNIQUE KEY (travelType)
);

-- Table: TravelingArmies - Track armies in transit for attacks
CREATE TABLE IF NOT EXISTS TravelingArmies (
    travelId INT AUTO_INCREMENT PRIMARY KEY,
    attackerSettlementId INT NOT NULL,
    defenderSettlementId INT NOT NULL,
    -- Units being sent
    guardsCount INT NOT NULL DEFAULT 0,
    soldiersCount INT NOT NULL DEFAULT 0,
    archersCount INT NOT NULL DEFAULT 0,
    cavalryCount INT NOT NULL DEFAULT 0,
    -- Travel timing
    startTime DATETIME NOT NULL,
    arrivalTime DATETIME NOT NULL,
    distance INT NOT NULL, -- block distance between settlements
    travelSpeed FLOAT NOT NULL, -- calculated speed based on slowest unit
    -- Status
    status ENUM('traveling', 'arrived', 'cancelled') NOT NULL DEFAULT 'traveling',
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attackerSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (defenderSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_attacker (attackerSettlementId),
    INDEX idx_defender (defenderSettlementId),
    INDEX idx_arrival (arrivalTime),
    INDEX idx_status (status)
);

-- Table: TravelingTrades - Track trades in transit
CREATE TABLE IF NOT EXISTS TravelingTrades (
    travelId INT AUTO_INCREMENT PRIMARY KEY,
    fromSettlementId INT NOT NULL,
    toSettlementId INT NOT NULL,
    -- Resources being sent
    woodAmount FLOAT NOT NULL DEFAULT 0,
    stoneAmount FLOAT NOT NULL DEFAULT 0,
    oreAmount FLOAT NOT NULL DEFAULT 0,
    goldAmount INT NOT NULL DEFAULT 0,
    -- Resources expected in return (for resource trades)
    expectedWood FLOAT NOT NULL DEFAULT 0,
    expectedStone FLOAT NOT NULL DEFAULT 0,
    expectedOre FLOAT NOT NULL DEFAULT 0,
    expectedGold INT NOT NULL DEFAULT 0,
    -- Travel timing
    startTime DATETIME NOT NULL,
    arrivalTime DATETIME NOT NULL,
    distance INT NOT NULL, -- block distance between settlements
    travelSpeed FLOAT NOT NULL DEFAULT 5.0, -- seconds per block
    -- Trade details
    tradeType ENUM('resource_trade', 'resource_sell', 'resource_buy', 'direct_send') NOT NULL,
    originalOfferId INT NULL, -- Reference to original trade offer if applicable
    -- Status
    status ENUM('traveling', 'arrived', 'completed', 'cancelled') NOT NULL DEFAULT 'traveling',
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (toSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (originalOfferId) REFERENCES TradeOffers(offerId) ON DELETE SET NULL,
    INDEX idx_from (fromSettlementId),
    INDEX idx_to (toSettlementId),
    INDEX idx_arrival (arrivalTime),
    INDEX idx_status (status)
);

-- Table: TravelHistory - Archive of completed travels for statistics
CREATE TABLE IF NOT EXISTS TravelHistory (
    historyId INT AUTO_INCREMENT PRIMARY KEY,
    travelType ENUM('military', 'trade') NOT NULL,
    fromSettlementId INT NOT NULL,
    toSettlementId INT NOT NULL,
    distance INT NOT NULL,
    travelTime INT NOT NULL, -- total travel time in seconds
    startTime DATETIME NOT NULL,
    completedTime DATETIME NOT NULL,
    outcome TEXT, -- description of what happened when travel completed
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fromSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (toSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_travel_type (travelType),
    INDEX idx_from (fromSettlementId),
    INDEX idx_completed (completedTime)
);

-- Table: BattleHistory - Track battle outcomes for travel arrivals
CREATE TABLE IF NOT EXISTS BattleHistory (
    battleId INT AUTO_INCREMENT PRIMARY KEY,
    attackerSettlementId INT NOT NULL,
    defenderSettlementId INT NOT NULL,
    result ENUM('victory', 'defeat') NOT NULL,
    battleTime DATETIME NOT NULL,
    createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attackerSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (defenderSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_attacker (attackerSettlementId),
    INDEX idx_defender (defenderSettlementId),
    INDEX idx_battle_time (battleTime)
);

-- ====================================
-- STORED PROCEDURES
-- ====================================

-- Player Creation Procedure with Enhanced Starting Values
DROP PROCEDURE IF EXISTS CreatePlayerWithSettlement;

DELIMITER //
CREATE PROCEDURE CreatePlayerWithSettlement(IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    DECLARE xCoord INT;
    DECLARE yCoord INT;
    DECLARE attemptCount INT DEFAULT 0;
    DECLARE maxAttempts INT DEFAULT 100;
    
    -- Start transaction for data consistency
    START TRANSACTION;
    
    -- Generate unique map coordinates
    coordinate_loop: LOOP
        SET attemptCount = attemptCount + 1;
        SET xCoord = FLOOR(RAND() * 21) - 10;  -- -10 to +10
        SET yCoord = FLOOR(RAND() * 21) - 10;  -- -10 to +10
        
        -- Check if coordinates are available
        IF NOT EXISTS (SELECT 1 FROM Map WHERE xCoordinate = xCoord AND yCoordinate = yCoord) THEN
            LEAVE coordinate_loop;
        END IF;
        
        -- Prevent infinite loop
        IF attemptCount >= maxAttempts THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Could not find available coordinates for new settlement';
        END IF;
    END LOOP;
    
    -- Create player with starting values
    INSERT INTO Spieler (name, punkte, gold) VALUES (playerName, 0, 500);
    SET newPlayerId = LAST_INSERT_ID();
    
    -- Create settlement with generous starting resources
    INSERT INTO Settlement (name, wood, stone, ore, playerId) 
    VALUES (CONCAT(playerName, 's Settlement'), 10000.0, 10000.0, 10000.0, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();
    
    -- Place settlement on map
    INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
    VALUES (newSettlementId, xCoord, yCoord);
    
    -- Create starting buildings with proper levels
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
    (newSettlementId, 'Rathaus', 1, TRUE),        -- Town Hall level 1
    (newSettlementId, 'Holzfäller', 1, TRUE),     -- Lumberjack level 1
    (newSettlementId, 'Steinbruch', 1, TRUE),     -- Quarry level 1  
    (newSettlementId, 'Erzbergwerk', 1, TRUE),    -- Mine level 1
    (newSettlementId, 'Lager', 1, TRUE),          -- Storage level 1
    (newSettlementId, 'Farm', 1, TRUE),           -- Farm level 1
    (newSettlementId, 'Markt', 0, TRUE),          -- Market level 0 (not built yet)
    (newSettlementId, 'Kaserne', 0, TRUE);        -- Kaserne level 0 (not built yet)
    
    -- Initialize military units (all at 0)
    INSERT INTO MilitaryUnits (settlementId, unitType, count) VALUES
    (newSettlementId, 'guards', 0),
    (newSettlementId, 'soldiers', 0),
    (newSettlementId, 'archers', 0),
    (newSettlementId, 'cavalry', 0);
    
    -- Initialize research status (all unresearched)
    INSERT INTO UnitResearch (settlementId, unitType, isResearched, researchedAt) VALUES
    (newSettlementId, 'guards', FALSE, NULL),
    (newSettlementId, 'soldiers', FALSE, NULL),
    (newSettlementId, 'archers', FALSE, NULL),
    (newSettlementId, 'cavalry', FALSE, NULL);
    
    -- Initialize military settler costs tracking
    INSERT INTO MilitarySettlerCosts (settlementId, totalSettlerCost) 
    VALUES (newSettlementId, 0);
    
    -- Commit transaction
    COMMIT;
    
    -- Return success information
    SELECT 
        newPlayerId AS playerId,
        newSettlementId AS settlementId,
        xCoord AS mapX,
        yCoord AS mapY,
        'Player and settlement created successfully' AS message;
        
END //
DELIMITER ;

-- Building Upgrade Procedure
DROP PROCEDURE IF EXISTS UpgradeBuilding;

DELIMITER //
CREATE PROCEDURE UpgradeBuilding(
    IN p_settlementId INT,
    IN p_buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne')
)
BEGIN
    DECLARE currentLevel INT DEFAULT 0;
    DECLARE nextLevel INT;
    DECLARE costWood FLOAT;
    DECLARE costStone FLOAT; 
    DECLARE costOre FLOAT;
    DECLARE requiredSettlers FLOAT;
    DECLARE buildTime INT;
    DECLARE townHallLevel INT DEFAULT 0;
    DECLARE reducedBuildTime INT;
    DECLARE availableWood FLOAT;
    DECLARE availableStone FLOAT;
    DECLARE availableOre FLOAT;
    DECLARE freeSettlers INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Get current building level
    SELECT COALESCE(level, 0) INTO currentLevel 
    FROM Buildings 
    WHERE settlementId = p_settlementId AND buildingType = p_buildingType;
    
    -- Calculate next level
    SET nextLevel = currentLevel + 1;
    
    -- Get costs for next level
    SELECT bc.costWood, bc.costStone, bc.costOre, bc.settlers, bc.buildTime
    INTO costWood, costStone, costOre, requiredSettlers, buildTime
    FROM BuildingConfig bc
    WHERE bc.buildingType = p_buildingType AND bc.level = nextLevel;
    
    -- Check if next level configuration exists
    IF costWood IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Building cannot be upgraded further - max level reached';
    END IF;
    
    -- Get current resources
    SELECT wood, stone, ore INTO availableWood, availableStone, availableOre
    FROM Settlement
    WHERE settlementId = p_settlementId;
    
    -- Get free settlers
    SELECT COALESCE(freeSettlers, 0) INTO freeSettlers
    FROM SettlementSettlers
    WHERE settlementId = p_settlementId;
    
    -- Check resource requirements
    IF availableWood < costWood OR availableStone < costStone OR availableOre < costOre THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient resources for building upgrade';
    END IF;
    
    -- Check settler requirements
    IF freeSettlers < requiredSettlers THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient free settlers for building upgrade';
    END IF;
    
    -- Get town hall level for build time reduction
    SELECT COALESCE(level, 0) INTO townHallLevel
    FROM Buildings
    WHERE settlementId = p_settlementId AND buildingType = 'Rathaus';
    
    -- Calculate reduced build time (5% reduction per town hall level, minimum 10% of original time)
    SET reducedBuildTime = ROUND(buildTime * GREATEST(0.1, 1.0 - (townHallLevel * 0.05)));
    
    -- Deduct resources
    UPDATE Settlement 
    SET wood = wood - costWood,
        stone = stone - costStone,
        ore = ore - costOre
    WHERE settlementId = p_settlementId;
    
    -- Add to building queue
    INSERT INTO BuildingQueue (settlementId, buildingType, startTime, endTime, level)
    VALUES (
        p_settlementId, 
        p_buildingType, 
        NOW(), 
        DATE_ADD(NOW(), INTERVAL reducedBuildTime SECOND),
        nextLevel
    );
    
    COMMIT;
    
    -- Return success information
    SELECT 
        p_buildingType AS buildingType,
        currentLevel AS currentLevel,
        nextLevel AS targetLevel,
        reducedBuildTime AS buildTimeSeconds,
        'Building upgrade queued successfully' AS message;
        
END //
DELIMITER ;

-- ====================================
-- ESSENTIAL VIEWS
-- ====================================

-- Open Building Queue View
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

-- Settlement Settlers View
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

-- Enhanced Settlement Resources View
CREATE OR REPLACE VIEW SettlementResources AS
SELECT 
    s.settlementId,
    s.name AS settlementName,
    s.wood,
    s.stone,
    s.ore,
    s.playerId,
    p.name AS playerName,
    p.gold AS playerGold,
    p.punkte AS playerPoints
FROM Settlement s
JOIN Spieler p ON s.playerId = p.playerId;

-- Building Details View
CREATE OR REPLACE VIEW BuildingDetails AS
SELECT 
    b.settlementId,
    b.buildingType,
    b.level AS currentLevel,
    COALESCE((
        SELECT MAX(bq.level) + 1
        FROM BuildingQueue bq
        WHERE bq.settlementId = b.settlementId
        AND bq.buildingType = b.buildingType
    ), b.level + 1) AS nextLevel,
    bc.costWood,
    bc.costStone,
    bc.costOre,
    COALESCE(bc.productionRate, 0) AS productionRate,
    bc.settlers,
    ROUND(COALESCE(bc.buildTime, 30) * GREATEST(0.1, 1.0 - (COALESCE(th.level, 0) * 0.05))) AS buildTime
FROM Buildings b
INNER JOIN BuildingConfig bc
ON b.buildingType = bc.buildingType
AND bc.level = COALESCE((
    SELECT MAX(bq.level) + 1
    FROM BuildingQueue bq
    WHERE bq.settlementId = b.settlementId
        AND bq.buildingType = b.buildingType
), b.level + 1)
LEFT JOIN (
    SELECT settlementId, level
    FROM Buildings 
    WHERE buildingType = 'Rathaus'
) th ON b.settlementId = th.settlementId;

-- Game Statistics View
CREATE OR REPLACE VIEW GameStatistics AS
SELECT 
    (SELECT COUNT(*) FROM Spieler) AS totalPlayers,
    (SELECT COUNT(*) FROM Settlement) AS totalSettlements,
    (SELECT SUM(wood) FROM Settlement) AS totalWoodInGame,
    (SELECT SUM(stone) FROM Settlement) AS totalStoneInGame,
    (SELECT SUM(ore) FROM Settlement) AS totalOreInGame,
    (SELECT SUM(gold) FROM Spieler) AS totalGoldInGame,
    (SELECT COUNT(*) FROM BuildingQueue) AS activeBuildingQueues,
    (SELECT COUNT(*) FROM MilitaryTrainingQueue) AS activeMilitaryQueues,
    (SELECT COUNT(*) FROM ResearchQueue) AS activeResearchQueues,
    (SELECT COUNT(*) FROM TradeOffers WHERE isActive = TRUE) AS activeTradeOffers,
    (SELECT AVG(punkte) FROM Spieler) AS averagePlayerPoints,
    (SELECT MAX(level) FROM Buildings) AS highestBuildingLevel,
    NOW() AS statisticsGeneratedAt;

-- ====================================
-- INITIAL DATA POPULATION
-- ====================================

-- Clear existing configuration data
DELETE FROM BuildingConfig;
DELETE FROM MilitaryUnitConfig;
DELETE FROM ResearchConfig;

-- Building configuration data
INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime) VALUES
-- Holzfäller (levels 1-10)
('Holzfäller', 1, 100, 100, 100, 1, 3600, 30),
('Holzfäller', 2, 110, 110, 110, 1.1, 3960, 40),
('Holzfäller', 3, 121, 121, 121, 1.21, 4356, 50),
('Holzfäller', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Holzfäller', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),
('Holzfäller', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Holzfäller', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Holzfäller', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Holzfäller', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Holzfäller', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112),

-- Steinbruch (levels 1-10)
('Steinbruch', 1, 100, 100, 100, 1, 3600, 30),
('Steinbruch', 2, 110, 110, 110, 1.1, 3960, 40),
('Steinbruch', 3, 121, 121, 121, 1.21, 4356, 50),
('Steinbruch', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Steinbruch', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),
('Steinbruch', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Steinbruch', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Steinbruch', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Steinbruch', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Steinbruch', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112),

-- Erzbergwerk (levels 1-10)
('Erzbergwerk', 1, 100, 100, 100, 1, 3600, 30),
('Erzbergwerk', 2, 110, 110, 110, 1.1, 3960, 40),
('Erzbergwerk', 3, 121, 121, 121, 1.21, 4356, 50),
('Erzbergwerk', 4, 133.1, 133.1, 133.1, 1.331, 4791.6, 60),
('Erzbergwerk', 5, 146.41, 146.41, 146.41, 1.4641, 5270.76, 70),
('Erzbergwerk', 6, 161.051, 161.051, 161.051, 1.61051, 5797.836, 77),
('Erzbergwerk', 7, 177.1561, 177.1561, 177.1561, 1.771561, 6377.6196, 85),
('Erzbergwerk', 8, 194.87171, 194.87171, 194.87171, 1.9487171, 7015.38156, 93),
('Erzbergwerk', 9, 214.358881, 214.358881, 214.358881, 2.14358881, 7716.919716, 102),
('Erzbergwerk', 10, 235.7947691, 235.7947691, 235.7947691, 2.357947691, 8488.6116876, 112),

-- Lager (levels 1-10)
('Lager', 1, 100, 100, 100, 1, 10000, 30),
('Lager', 2, 110, 110, 110, 1.1, 11000, 40),
('Lager', 3, 121, 121, 121, 1.21, 12100, 50),
('Lager', 4, 133.1, 133.1, 133.1, 1.331, 13310, 60),
('Lager', 5, 146.41, 146.41, 146.41, 1.4641, 14641, 70),
('Lager', 6, 161.051, 161.051, 161.051, 1.61051, 16105.1, 77),
('Lager', 7, 177.156, 177.156, 177.156, 1.77156, 17715.61, 85),
('Lager', 8, 194.872, 194.872, 194.872, 1.94872, 19487.17, 93),
('Lager', 9, 214.359, 214.359, 214.359, 2.14359, 21435.89, 102),
('Lager', 10, 235.795, 235.795, 235.795, 2.35795, 23579.48, 112),

-- Farm (provides settlers - levels 1-10)
('Farm', 1, 100, 100, 100, 1, 100, 30),
('Farm', 2, 110, 110, 110, 1.1, 110, 40),
('Farm', 3, 121, 121, 121, 1.21, 121, 50),
('Farm', 4, 133.1, 133.1, 133.1, 1.331, 133.1, 60),
('Farm', 5, 146.41, 146.41, 146.41, 1.4641, 146.41, 70),
('Farm', 6, 161.051, 161.051, 161.051, 1.61051, 161.051, 77),
('Farm', 7, 177.156, 177.156, 177.156, 1.77156, 177.156, 85),
('Farm', 8, 194.872, 194.872, 194.872, 1.94872, 194.872, 93),
('Farm', 9, 214.359, 214.359, 214.359, 2.14359, 214.359, 102),
('Farm', 10, 235.795, 235.795, 235.795, 2.35795, 235.795, 112),

-- Rathaus (reduces build times - levels 1-10)
('Rathaus', 1, 200, 200, 200, 2, 0, 60),
('Rathaus', 2, 220, 220, 220, 2.2, 0, 80),
('Rathaus', 3, 242, 242, 242, 2.42, 0, 100),
('Rathaus', 4, 266.2, 266.2, 266.2, 2.662, 0, 120),
('Rathaus', 5, 292.82, 292.82, 292.82, 2.9282, 0, 140),
('Rathaus', 6, 322.102, 322.102, 322.102, 3.22102, 0, 154),
('Rathaus', 7, 354.3122, 354.3122, 354.3122, 3.543122, 0, 169),
('Rathaus', 8, 389.74342, 389.74342, 389.74342, 3.8974342, 0, 186),
('Rathaus', 9, 428.717762, 428.717762, 428.717762, 4.28717762, 0, 205),
('Rathaus', 10, 471.5895382, 471.5895382, 471.5895382, 4.715895382, 0, 225),

-- Markt (enables trading - levels 1-10)
('Markt', 1, 150, 100, 50, 2, 0, 45),
('Markt', 2, 165, 110, 55, 2.2, 0, 60),
('Markt', 3, 181.5, 121, 60.5, 2.42, 0, 75),
('Markt', 4, 199.65, 133.1, 66.55, 2.662, 0, 90),
('Markt', 5, 219.615, 146.41, 73.205, 2.9282, 0, 105),
('Markt', 6, 241.577, 161.051, 80.525, 3.22102, 0, 116),
('Markt', 7, 265.734, 177.156, 88.578, 3.54312, 0, 127),
('Markt', 8, 292.308, 194.872, 97.436, 3.89743, 0, 140),
('Markt', 9, 321.539, 214.359, 107.18, 4.28718, 0, 154),
('Markt', 10, 353.693, 235.795, 117.898, 4.7159, 0, 169),

-- Kaserne (military - levels 1-10)
('Kaserne', 1, 150, 150, 200, 2, 10, 45),
('Kaserne', 2, 165, 165, 220, 2.2, 11, 60),
('Kaserne', 3, 181.5, 181.5, 242, 2.42, 12.1, 75),
('Kaserne', 4, 199.65, 199.65, 266.2, 2.662, 13.31, 90),
('Kaserne', 5, 219.615, 219.615, 292.82, 2.9282, 14.641, 105),
('Kaserne', 6, 241.577, 241.577, 322.102, 3.22102, 16.105, 116),
('Kaserne', 7, 265.734, 265.734, 354.312, 3.54312, 17.716, 127),
('Kaserne', 8, 292.308, 292.308, 389.743, 3.89743, 19.487, 140),
('Kaserne', 9, 321.539, 321.539, 428.718, 4.28718, 21.436, 154),
('Kaserne', 10, 353.693, 353.693, 471.59, 4.7159, 23.579, 169);

-- Military unit configuration
INSERT INTO MilitaryUnitConfig (unitType, level, costWood, costStone, costOre, costGold, costSettlers, trainingTime, defensePower, attackPower, rangedPower, speed, lootAmount) VALUES
('guards', 1, 50, 30, 20, 10, 1, 300, 3, 2, 0, 1, 5.0),
('soldiers', 1, 80, 60, 40, 25, 2, 600, 5, 6, 0, 1, 10.0),
('archers', 1, 60, 40, 30, 20, 1, 450, 2, 4, 8, 1, 8.0),
('cavalry', 1, 120, 80, 60, 50, 3, 900, 8, 10, 0, 2, 15.0);

-- Travel system configuration
INSERT INTO TravelConfig (travelType, baseSpeed, description) VALUES
('trade', 5, 'Base travel speed for trades: 5 seconds per block distance'),
('military', 5, 'Base travel speed for military units: 5 seconds per block (modified by unit speed)');

-- Research configuration
INSERT INTO ResearchConfig (unitType, costWood, costStone, costOre, costGold, researchTime, prerequisiteUnit) VALUES
('guards', 200, 150, 100, 50, 60, NULL),
('soldiers', 400, 300, 200, 100, 120, 'guards'),
('archers', 350, 250, 150, 75, 90, 'guards'),
('cavalry', 600, 400, 300, 200, 180, 'soldiers');

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
    DECLARE done INT DEFAULT FALSE;
    DECLARE settlement_id INT;
    DECLARE wood_production FLOAT;
    DECLARE stone_production FLOAT;
    DECLARE ore_production FLOAT;
    DECLARE storage_limit FLOAT;
    
    DECLARE settlement_cursor CURSOR FOR SELECT settlementId FROM Settlement;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN settlement_cursor;
    
    settlement_loop: LOOP
        FETCH settlement_cursor INTO settlement_id;
        IF done THEN
            LEAVE settlement_loop;
        END IF;
        
        -- Calculate wood production per second
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO wood_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Holzfäller';
        
        -- Calculate stone production per second  
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO stone_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Steinbruch';
        
        -- Calculate ore production per second
        SELECT COALESCE(SUM(bc.productionRate), 0) / 3600 INTO ore_production
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Erzbergwerk';
        
        -- Get storage limit from highest level Lager
        SELECT COALESCE(bc.productionRate, 10000) INTO storage_limit
        FROM Buildings b
        JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
        WHERE b.settlementId = settlement_id AND b.buildingType = 'Lager'
        ORDER BY b.level DESC LIMIT 1;
        
        -- Update resources with limits
        UPDATE Settlement 
        SET 
            wood = LEAST(wood + wood_production, storage_limit),
            stone = LEAST(stone + stone_production, storage_limit),
            ore = LEAST(ore + ore_production, storage_limit)
        WHERE settlementId = settlement_id;
        
    END LOOP;
    
    CLOSE settlement_cursor;
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
    SELECT bq.settlementId, bq.buildingType, bq.level, TRUE
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
        ur.researchedAt = NOW()
    WHERE NOW() >= rq.endTime;
    
    DELETE FROM ResearchQueue WHERE NOW() >= endTime;
END //
DELIMITER ;

-- ====================================
-- INITIALIZATION PROCEDURES
-- ====================================

-- Enhanced Database Initialization Procedures
-- Procedure to initialize the complete database with starting values
DROP PROCEDURE IF EXISTS InitializeGameDatabase;

DELIMITER //
CREATE PROCEDURE InitializeGameDatabase()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Clear existing data if any
    DELETE FROM BuildingQueue;
    DELETE FROM MilitaryTrainingQueue;
    DELETE FROM ResearchQueue;
    DELETE FROM TradeOffers;
    DELETE FROM TradeTransactions;
    DELETE FROM TradeHistory;
    DELETE FROM MilitaryUnits;
    DELETE FROM UnitResearch;
    DELETE FROM MilitarySettlerCosts;
    DELETE FROM Buildings;
    DELETE FROM Map;
    DELETE FROM Settlement;
    DELETE FROM Spieler;
    
    -- Reset auto increment counters
    ALTER TABLE Spieler AUTO_INCREMENT = 1;
    ALTER TABLE Settlement AUTO_INCREMENT = 1;
    ALTER TABLE BuildingQueue AUTO_INCREMENT = 1;
    ALTER TABLE MilitaryTrainingQueue AUTO_INCREMENT = 1;
    ALTER TABLE ResearchQueue AUTO_INCREMENT = 1;
    ALTER TABLE TradeOffers AUTO_INCREMENT = 1;
    ALTER TABLE TradeTransactions AUTO_INCREMENT = 1;
    ALTER TABLE TradeHistory AUTO_INCREMENT = 1;
    
    -- Create default admin player
    CALL CreatePlayerWithSettlement('Admin');
    
    COMMIT;
    
    SELECT 
        'Database initialized successfully' AS status,
        (SELECT COUNT(*) FROM Spieler) AS totalPlayers,
        (SELECT COUNT(*) FROM Settlement) AS totalSettlements,
        'Default admin player created' AS message;
        
END //
DELIMITER ;

-- Procedure to validate database integrity
DROP PROCEDURE IF EXISTS ValidateDatabase;

DELIMITER //
CREATE PROCEDURE ValidateDatabase()
BEGIN
    DECLARE tableCount INT DEFAULT 0;
    DECLARE playerCount INT DEFAULT 0;
    DECLARE settlementCount INT DEFAULT 0;
    DECLARE buildingCount INT DEFAULT 0;
    DECLARE configCount INT DEFAULT 0;
    DECLARE viewCount INT DEFAULT 0;
    DECLARE procedureCount INT DEFAULT 0;
    DECLARE eventCount INT DEFAULT 0;
    
    -- Count tables
    SELECT COUNT(*) INTO tableCount
    FROM information_schema.tables 
    WHERE table_schema = 'browsergame';
    
    -- Count data
    SELECT COUNT(*) INTO playerCount FROM Spieler;
    SELECT COUNT(*) INTO settlementCount FROM Settlement;
    SELECT COUNT(*) INTO buildingCount FROM Buildings;
    SELECT COUNT(*) INTO configCount FROM BuildingConfig;
    
    -- Count views
    SELECT COUNT(*) INTO viewCount
    FROM information_schema.views 
    WHERE table_schema = 'browsergame';
    
    -- Count procedures
    SELECT COUNT(*) INTO procedureCount
    FROM information_schema.routines 
    WHERE routine_schema = 'browsergame' AND routine_type = 'PROCEDURE';
    
    -- Count events
    SELECT COUNT(*) INTO eventCount
    FROM information_schema.events 
    WHERE event_schema = 'browsergame';
    
    SELECT 
        'Database validation complete' AS status,
        tableCount AS totalTables,
        viewCount AS totalViews,
        procedureCount AS totalProcedures,
        eventCount AS totalEvents,
        playerCount AS totalPlayers,
        settlementCount AS totalSettlements,
        buildingCount AS totalBuildings,
        configCount AS buildingConfigs,
        CASE 
            WHEN tableCount >= 15 AND viewCount >= 5 AND procedureCount >= 3 AND eventCount >= 4 THEN 'PASSED'
            ELSE 'FAILED'
        END AS validationResult;
        
END //
DELIMITER ;

-- ====================================
-- TRAVEL SYSTEM PROCEDURES
-- ====================================

-- Procedure: Process Arrived Armies
DROP PROCEDURE IF EXISTS ProcessArrivedArmies;

DELIMITER //
CREATE PROCEDURE ProcessArrivedArmies()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE army_travel_id INT;
    DECLARE army_attacker_id INT;
    DECLARE army_defender_id INT;
    DECLARE army_guards INT;
    DECLARE army_soldiers INT;
    DECLARE army_archers INT;
    DECLARE army_cavalry INT;
    
    -- Cursor for arrived armies
    DECLARE army_cursor CURSOR FOR 
        SELECT travelId, attackerSettlementId, defenderSettlementId, 
               guardsCount, soldiersCount, archersCount, cavalryCount
        FROM TravelingArmies 
        WHERE arrivalTime <= NOW() AND status = 'traveling';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN army_cursor;
    
    read_loop: LOOP
        FETCH army_cursor INTO army_travel_id, army_attacker_id, army_defender_id,
                               army_guards, army_soldiers, army_archers, army_cavalry;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Execute battle processing
        CALL ExecuteBattleOnArrival(army_attacker_id, army_defender_id, 
                                  army_guards, army_soldiers, army_archers, army_cavalry);
        
        -- Mark army as arrived
        UPDATE TravelingArmies SET status = 'arrived' WHERE travelId = army_travel_id;
        
    END LOOP;
    
    CLOSE army_cursor;
END//

-- Procedure: Process Arrived Trades
DROP PROCEDURE IF EXISTS ProcessArrivedTrades//
CREATE PROCEDURE ProcessArrivedTrades()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE trade_travel_id INT;
    DECLARE trade_to_settlement INT;
    DECLARE trade_wood FLOAT;
    DECLARE trade_stone FLOAT;
    DECLARE trade_ore FLOAT;
    DECLARE trade_gold INT;
    
    -- Cursor for arrived trades
    DECLARE trade_cursor CURSOR FOR 
        SELECT travelId, toSettlementId, woodAmount, stoneAmount, oreAmount, goldAmount
        FROM TravelingTrades 
        WHERE arrivalTime <= NOW() AND status = 'traveling';
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN trade_cursor;
    
    read_loop: LOOP
        FETCH trade_cursor INTO trade_travel_id, trade_to_settlement,
                               trade_wood, trade_stone, trade_ore, trade_gold;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Execute trade delivery
        CALL ExecuteTradeOnArrival(trade_to_settlement, trade_wood, trade_stone, trade_ore, trade_gold);
        
        -- Mark trade as completed
        UPDATE TravelingTrades SET status = 'completed' WHERE travelId = trade_travel_id;
        
    END LOOP;
    
    CLOSE trade_cursor;
END//

-- Procedure: Execute Battle On Arrival (Simplified)
DROP PROCEDURE IF EXISTS ExecuteBattleOnArrival//
CREATE PROCEDURE ExecuteBattleOnArrival(
    IN p_attacker_settlement_id INT,
    IN p_defender_settlement_id INT,
    IN p_guards INT,
    IN p_soldiers INT,
    IN p_archers INT,
    IN p_cavalry INT
)
BEGIN
    DECLARE attacker_power INT DEFAULT 0;
    DECLARE defender_power INT DEFAULT 0;
    DECLARE battle_result VARCHAR(20) DEFAULT 'defeat';
    DECLARE plunder_wood FLOAT DEFAULT 0;
    DECLARE plunder_stone FLOAT DEFAULT 0;
    DECLARE plunder_ore FLOAT DEFAULT 0;
    
    -- Add traveling units back to attacker temporarily for battle calculation
    IF p_guards > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'guards', p_guards)
        ON DUPLICATE KEY UPDATE count = count + p_guards;
    END IF;
    
    IF p_soldiers > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'soldiers', p_soldiers)
        ON DUPLICATE KEY UPDATE count = count + p_soldiers;
    END IF;
    
    IF p_archers > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'archers', p_archers)
        ON DUPLICATE KEY UPDATE count = count + p_archers;
    END IF;
    
    IF p_cavalry > 0 THEN
        INSERT INTO MilitaryUnits (settlementId, unitType, count) 
        VALUES (p_attacker_settlement_id, 'cavalry', p_cavalry)
        ON DUPLICATE KEY UPDATE count = count + p_cavalry;
    END IF;
    
    -- Calculate military power
    SELECT COALESCE(SUM(mu.count * muc.attackPower), 0)
    INTO attacker_power
    FROM MilitaryUnits mu
    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
    WHERE mu.settlementId = p_attacker_settlement_id;
    
    SELECT COALESCE(SUM(mu.count * muc.defensePower), 0)
    INTO defender_power
    FROM MilitaryUnits mu
    INNER JOIN MilitaryUnitConfig muc ON mu.unitType = muc.unitType
    WHERE mu.settlementId = p_defender_settlement_id;
    
    -- Simple battle calculation (attacker needs higher power to win)
    IF attacker_power > defender_power THEN
        SET battle_result = 'victory';
        
        -- Calculate plunder (simplified: 10% of resources, max based on units)
        SELECT 
            LEAST(s.wood * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20)),
            LEAST(s.stone * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20)),
            LEAST(s.ore * 0.1, (p_guards * 5 + p_soldiers * 10 + p_archers * 8 + p_cavalry * 20))
        INTO plunder_wood, plunder_stone, plunder_ore
        FROM Settlement s WHERE s.settlementId = p_defender_settlement_id;
        
        -- Transfer plundered resources
        UPDATE Settlement SET 
            wood = GREATEST(0, wood - plunder_wood),
            stone = GREATEST(0, stone - plunder_stone),
            ore = GREATEST(0, ore - plunder_ore)
        WHERE settlementId = p_defender_settlement_id;
        
        UPDATE Settlement SET 
            wood = wood + plunder_wood,
            stone = stone + plunder_stone,
            ore = ore + plunder_ore
        WHERE settlementId = p_attacker_settlement_id;
    END IF;
    
    -- Apply battle losses (simplified: 20% unit loss for attacker, 30% for defender)
    IF battle_result = 'victory' THEN
        -- Attacker losses (20%)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.8))
        WHERE settlementId = p_attacker_settlement_id;
        
        -- Defender losses (30%)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.7))
        WHERE settlementId = p_defender_settlement_id;
    ELSE
        -- Attacker losses (50% for defeat)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.5))
        WHERE settlementId = p_attacker_settlement_id;
        
        -- Defender losses (10% for victory)
        UPDATE MilitaryUnits SET count = GREATEST(0, FLOOR(count * 0.9))
        WHERE settlementId = p_defender_settlement_id;
    END IF;
    
    -- Record battle in history table
    INSERT INTO BattleHistory (attackerSettlementId, defenderSettlementId, result, battleTime)
    VALUES (p_attacker_settlement_id, p_defender_settlement_id, battle_result, NOW());
    
END//

-- Procedure: Execute Trade On Arrival
DROP PROCEDURE IF EXISTS ExecuteTradeOnArrival//
CREATE PROCEDURE ExecuteTradeOnArrival(
    IN p_to_settlement_id INT,
    IN p_wood_amount FLOAT,
    IN p_stone_amount FLOAT,
    IN p_ore_amount FLOAT,
    IN p_gold_amount INT
)
BEGIN
    -- Add resources to destination settlement
    UPDATE Settlement SET 
        wood = wood + p_wood_amount,
        stone = stone + p_stone_amount,
        ore = ore + p_ore_amount
    WHERE settlementId = p_to_settlement_id;
    
    -- Add gold to player if applicable
    IF p_gold_amount > 0 THEN
        UPDATE Spieler p 
        INNER JOIN Settlement s ON p.playerId = s.playerId 
        SET p.gold = p.gold + p_gold_amount
        WHERE s.settlementId = p_to_settlement_id;
    END IF;
END//

-- Main procedure that processes all arrivals
DROP PROCEDURE IF EXISTS ProcessAllArrivals//
CREATE PROCEDURE ProcessAllArrivals()
BEGIN
    -- Process armies
    CALL ProcessArrivedArmies();
    
    -- Process trades  
    CALL ProcessArrivedTrades();
END//

DELIMITER ;

-- ====================================
-- TRAVEL SYSTEM EVENTS
-- ====================================

-- Event: Process Travel Arrivals
DROP EVENT IF EXISTS ProcessTravelArrivals;

DELIMITER //
CREATE EVENT ProcessTravelArrivals
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    CALL ProcessAllArrivals();
END//

DELIMITER ;

-- ====================================
-- FINAL SETUP AND INITIALIZATION
-- ====================================

-- Enable event scheduler at the end
SET GLOBAL event_scheduler = ON;

-- Enable events (they are created but not enabled by default)
ALTER EVENT UpdateResources ENABLE;
ALTER EVENT ProcessBuildingQueue ENABLE;
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;
ALTER EVENT ProcessTravelArrivals ENABLE;

-- Run database validation
CALL ValidateDatabase();

-- Safe conditional initialization procedure
DROP PROCEDURE IF EXISTS SafeInitializeDatabase;

DELIMITER //
CREATE PROCEDURE SafeInitializeDatabase()
BEGIN
    DECLARE player_count INT DEFAULT 0;
    
    -- Check if database is empty
    SET player_count = (SELECT COUNT(*) FROM Spieler);
    
    IF player_count = 0 THEN
        CALL InitializeGameDatabase();
        SELECT 'Database automatically initialized with admin player' AS auto_init_status;
    ELSE
        SELECT 'Database already contains data, skipping auto-initialization' AS auto_init_status;
    END IF;
END //
DELIMITER ;

-- Call safe initialization
CALL SafeInitializeDatabase();

-- Create initial test player for Docker setup (from init-player.sql)
-- This ensures there's always a test player available
CALL CreatePlayerWithSettlement('TestPlayer');

-- Show that the player was created
SELECT 'Initial TestPlayer created successfully' AS status;

-- Verify final setup
SELECT 'Browsergame database initialized with consolidated structure!' AS status,
       'Database is ready for use' AS message,
       'Event scheduler is enabled for automated processing' AS events,
       'Enhanced views and procedures loaded' AS features,
       'TestPlayer created for testing' AS test_data;