-- Travel time system table definitions
-- This script adds tables for tracking travel times for attacks and trades

USE browsergame;

-- Add lootAmount column to MilitaryUnitConfig for configurable resource theft
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'MilitaryUnitConfig' 
     AND column_name = 'lootAmount' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE MilitaryUnitConfig ADD COLUMN lootAmount FLOAT NOT NULL DEFAULT 10.0',
    'SELECT ''lootAmount column already exists'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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

-- Insert default travel configuration
INSERT IGNORE INTO TravelConfig (travelType, baseSpeed, description) VALUES
('trade', 5, 'Base travel speed for trades: 5 seconds per block distance'),
('military', 5, 'Base travel speed for military units: 5 seconds per block (modified by unit speed)');