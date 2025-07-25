-- Military unit system table definitions
-- This script adds tables for military units and training

USE browsergame;

-- Add speed column to existing MilitaryUnitConfig table if it doesn't exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'MilitaryUnitConfig' 
     AND column_name = 'speed' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE MilitaryUnitConfig ADD COLUMN speed INT NOT NULL DEFAULT 1',
    'SELECT ''speed column already exists'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Table: MilitaryUnits - Store unit counts per settlement
CREATE TABLE IF NOT EXISTS MilitaryUnits (
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, unitType)
);

-- Table: MilitaryTrainingQueue - Handle unit training queue
CREATE TABLE IF NOT EXISTS MilitaryTrainingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    count INT NOT NULL DEFAULT 1, -- how many units to train
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Add costSettlers column to MilitaryUnitConfig if it doesn't exist  
SET @sql2 = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'MilitaryUnitConfig' 
     AND column_name = 'costSettlers' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE MilitaryUnitConfig ADD COLUMN costSettlers INT NOT NULL DEFAULT 1',
    'SELECT ''costSettlers column already exists'''));

PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;