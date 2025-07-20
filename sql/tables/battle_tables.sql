-- Battle system table definitions
-- This script adds tables for the battle system

USE browsergame;

-- Table: Battles - Store battle information
CREATE TABLE IF NOT EXISTS Battles (
    battleId INT AUTO_INCREMENT PRIMARY KEY,
    attackerSettlementId INT NOT NULL,
    defenderSettlementId INT NOT NULL,
    battleTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    attackerUnitsTotal JSON,  -- Store unit counts before battle
    defenderUnitsTotal JSON,  -- Store unit counts before battle
    attackerUnitsLost JSON,   -- Store units lost in battle
    defenderUnitsLost JSON,   -- Store units lost in battle
    winner ENUM('attacker', 'defender') NOT NULL,
    resourcesPlundered JSON,  -- Resources gained by attacker
    battleResult TEXT,        -- Detailed battle calculation results
    FOREIGN KEY (attackerSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (defenderSettlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_attacker (attackerSettlementId),
    INDEX idx_defender (defenderSettlementId),
    INDEX idx_battle_time (battleTime)
);

-- Table: BattleParticipants - Store which units participated in battle
CREATE TABLE IF NOT EXISTS BattleParticipants (
    participantId INT AUTO_INCREMENT PRIMARY KEY,
    battleId INT NOT NULL,
    settlementId INT NOT NULL,
    unitType ENUM('guards', 'soldiers', 'archers', 'cavalry') NOT NULL,
    unitsUsed INT NOT NULL DEFAULT 0,
    unitsLost INT NOT NULL DEFAULT 0,
    role ENUM('attacker', 'defender') NOT NULL,
    FOREIGN KEY (battleId) REFERENCES Battles(battleId) ON DELETE CASCADE,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    INDEX idx_battle (battleId),
    INDEX idx_settlement (settlementId)
);

-- Table: BattleLogs - Store battle events and logs
CREATE TABLE IF NOT EXISTS BattleLogs (
    logId INT AUTO_INCREMENT PRIMARY KEY,
    battleId INT NOT NULL,
    logTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logType ENUM('start', 'calculation', 'result', 'plunder') NOT NULL,
    logMessage TEXT NOT NULL,
    logData JSON, -- Additional structured data
    FOREIGN KEY (battleId) REFERENCES Battles(battleId) ON DELETE CASCADE,
    INDEX idx_battle (battleId),
    INDEX idx_log_time (logTime)
);