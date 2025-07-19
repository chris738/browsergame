-- Additional game views for simplified PHP queries
-- These views replace complex JOIN queries in PHP with simple SELECT statements

USE browsergame;

-- View: SettlementResources - Complete settlement resource information
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

-- View: BuildingLevels - Current building levels per settlement
CREATE OR REPLACE VIEW BuildingLevels AS
SELECT 
    settlementId,
    buildingType,
    level
FROM Buildings
WHERE level > 0;

-- View: SettlementOverview - Complete settlement overview with key metrics
CREATE OR REPLACE VIEW SettlementOverview AS
SELECT 
    sr.settlementId,
    sr.settlementName,
    sr.playerName,
    sr.wood,
    sr.stone,
    sr.ore,
    sr.playerGold,
    ss.maxSettlers,
    ss.usedSettlers,
    ss.freeSettlers,
    COALESCE(th.level, 0) AS townHallLevel,
    COALESCE(ka.level, 0) AS kaserneLevel,
    COALESCE(fa.level, 0) AS farmLevel
FROM SettlementResources sr
LEFT JOIN SettlementSettlers ss ON sr.settlementId = ss.settlementId
LEFT JOIN (SELECT settlementId, level FROM Buildings WHERE buildingType = 'Rathaus') th ON sr.settlementId = th.settlementId
LEFT JOIN (SELECT settlementId, level FROM Buildings WHERE buildingType = 'Kaserne') ka ON sr.settlementId = ka.settlementId
LEFT JOIN (SELECT settlementId, level FROM Buildings WHERE buildingType = 'Farm') fa ON sr.settlementId = fa.settlementId;

-- View: MilitaryOverview - Military unit overview per settlement
CREATE OR REPLACE VIEW MilitaryOverview AS
SELECT 
    settlementId,
    SUM(CASE WHEN unitType = 'guards' THEN count ELSE 0 END) AS guards,
    SUM(CASE WHEN unitType = 'soldiers' THEN count ELSE 0 END) AS soldiers,
    SUM(CASE WHEN unitType = 'archers' THEN count ELSE 0 END) AS archers,
    SUM(CASE WHEN unitType = 'cavalry' THEN count ELSE 0 END) AS cavalry,
    SUM(count) AS totalUnits
FROM MilitaryUnits
GROUP BY settlementId;

-- View: ResearchStatus - Research status per settlement
CREATE OR REPLACE VIEW ResearchStatus AS
SELECT 
    settlementId,
    SUM(CASE WHEN unitType = 'guards' AND isResearched = TRUE THEN 1 ELSE 0 END) AS guardsResearched,
    SUM(CASE WHEN unitType = 'soldiers' AND isResearched = TRUE THEN 1 ELSE 0 END) AS soldiersResearched,
    SUM(CASE WHEN unitType = 'archers' AND isResearched = TRUE THEN 1 ELSE 0 END) AS archersResearched,
    SUM(CASE WHEN unitType = 'cavalry' AND isResearched = TRUE THEN 1 ELSE 0 END) AS cavalryResearched,
    COUNT(CASE WHEN isResearched = TRUE THEN 1 END) AS totalResearched
FROM UnitResearch
GROUP BY settlementId;

-- View: ActiveQueues - All active queues (building, military, research) per settlement
CREATE OR REPLACE VIEW ActiveQueues AS
SELECT 
    settlementId,
    'building' AS queueType,
    buildingType AS itemType,
    level AS itemLevel,
    startTime,
    endTime,
    remainingTimeSeconds,
    completionPercentage
FROM OpenBuildingQueue
UNION ALL
SELECT 
    settlementId,
    'military' AS queueType,
    unitType AS itemType,
    count AS itemLevel,
    startTime,
    endTime,
    remainingTimeSeconds,
    completionPercentage
FROM OpenMilitaryTrainingQueue
UNION ALL
SELECT 
    settlementId,
    'research' AS queueType,
    unitType AS itemType,
    NULL AS itemLevel,
    startTime,
    endTime,
    remainingTimeSeconds,
    completionPercentage
FROM OpenResearchQueue;

-- View: BuildingUpgradeCosts - Next level upgrade costs for all buildings
CREATE OR REPLACE VIEW BuildingUpgradeCosts AS
SELECT 
    bd.*,
    sr.wood AS currentWood,
    sr.stone AS currentStone,
    sr.ore AS currentOre,
    ss.freeSettlers,
    CASE 
        WHEN sr.wood >= bd.costWood AND sr.stone >= bd.costStone AND sr.ore >= bd.costOre AND ss.freeSettlers >= bd.settlers THEN TRUE
        ELSE FALSE
    END AS canAfford
FROM BuildingDetails bd
JOIN SettlementResources sr ON bd.settlementId = sr.settlementId
JOIN SettlementSettlers ss ON bd.settlementId = ss.settlementId;