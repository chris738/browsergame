-- Research system table definitions
-- This script adds tables for the military unit research system

USE browsergame;

-- Table: UnitResearch - Tracks which units have been researched by each settlement
CREATE TABLE IF NOT EXISTS UnitResearch (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    isResearched BOOLEAN NOT NULL DEFAULT FALSE,
    researchStartTime DATETIME NULL,
    researchEndTime DATETIME NULL,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

-- Table: ResearchConfig - Configuration for research costs and times
CREATE TABLE IF NOT EXISTS ResearchConfig (
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    researchCostWood FLOAT NOT NULL,
    researchCostStone FLOAT NOT NULL,
    researchCostOre FLOAT NOT NULL,
    researchTime INT NOT NULL, -- in seconds
    prerequisiteUnit ENUM('guards', 'soldiers', 'archers', 'cavalry') NULL, -- unit that must be researched first
    PRIMARY KEY (unitType)
);

-- Table: ResearchQueue - Queue for ongoing research
CREATE TABLE IF NOT EXISTS ResearchQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);