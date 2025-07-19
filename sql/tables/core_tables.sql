-- Database Tables Definition
-- Core table structure for the browser game

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
    level INT NOT NULL DEFAULT 1,
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