-- Enhanced views for simplified PHP database access
-- These views replace complex JOIN queries in PHP with simple SELECT statements

USE browsergame;

-- View: SettlementResources - Complete settlement resource information with storage limits
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
    p.punkte AS playerPoints,
    -- Add storage capacity from Lager building
    COALESCE(
        (SELECT bc.productionRate 
         FROM Buildings b 
         JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
         WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
         LIMIT 1), 10000
    ) AS storageCapacity,
    -- Add resource production rates
    COALESCE(
        (SELECT bc.productionRate 
         FROM Buildings b 
         JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
         WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
         LIMIT 1), 0
    ) AS woodProduction,
    COALESCE(
        (SELECT bc.productionRate 
         FROM Buildings b 
         JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
         WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
         LIMIT 1), 0
    ) AS stoneProduction,
    COALESCE(
        (SELECT bc.productionRate 
         FROM Buildings b 
         JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
         WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
         LIMIT 1), 0
    ) AS oreProduction
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

-- View: BuildingUpgradeCosts - Next level upgrade costs for all buildings with affordability
CREATE OR REPLACE VIEW BuildingUpgradeCosts AS
SELECT 
    bd.*,
    sr.wood AS currentWood,
    sr.stone AS currentStone,
    sr.ore AS currentOre,
    sr.playerGold AS currentGold,
    ss.freeSettlers,
    ss.maxSettlers,
    ss.usedSettlers,
    CASE 
        WHEN sr.wood >= bd.costWood AND sr.stone >= bd.costStone AND sr.ore >= bd.costOre AND ss.freeSettlers >= bd.settlers THEN TRUE
        ELSE FALSE
    END AS canAfford,
    CASE
        WHEN sr.wood < bd.costWood THEN CONCAT('Need ', ROUND(bd.costWood - sr.wood), ' more wood')
        WHEN sr.stone < bd.costStone THEN CONCAT('Need ', ROUND(bd.costStone - sr.stone), ' more stone')
        WHEN sr.ore < bd.costOre THEN CONCAT('Need ', ROUND(bd.costOre - sr.ore), ' more ore')
        WHEN ss.freeSettlers < bd.settlers THEN CONCAT('Need ', ROUND(bd.settlers - ss.freeSettlers), ' more settlers')
        ELSE 'Can afford upgrade'
    END AS affordabilityMessage
FROM BuildingDetails bd
JOIN SettlementResources sr ON bd.settlementId = sr.settlementId
JOIN SettlementSettlers ss ON bd.settlementId = ss.settlementId;

-- View: AllBuildingsOverview - Complete building overview per settlement
CREATE OR REPLACE VIEW AllBuildingsOverview AS
SELECT 
    s.settlementId,
    s.settlementName,
    s.playerName,
    -- Building levels
    COALESCE(b1.level, 0) AS rathaus_level,
    COALESCE(b2.level, 0) AS holzfaeller_level,
    COALESCE(b3.level, 0) AS steinbruch_level,
    COALESCE(b4.level, 0) AS erzbergwerk_level,
    COALESCE(b5.level, 0) AS lager_level,
    COALESCE(b6.level, 0) AS farm_level,
    COALESCE(b7.level, 0) AS markt_level,
    COALESCE(b8.level, 0) AS kaserne_level,
    -- Building visibility
    COALESCE(b1.visable, FALSE) AS rathaus_visible,
    COALESCE(b2.visable, FALSE) AS holzfaeller_visible,
    COALESCE(b3.visable, FALSE) AS steinbruch_visible,
    COALESCE(b4.visable, FALSE) AS erzbergwerk_visible,
    COALESCE(b5.visable, FALSE) AS lager_visible,
    COALESCE(b6.visable, FALSE) AS farm_visible,
    COALESCE(b7.visable, FALSE) AS markt_visible,
    COALESCE(b8.visable, FALSE) AS kaserne_visible
FROM SettlementResources s
LEFT JOIN Buildings b1 ON s.settlementId = b1.settlementId AND b1.buildingType = 'Rathaus'
LEFT JOIN Buildings b2 ON s.settlementId = b2.settlementId AND b2.buildingType = 'Holzfäller'
LEFT JOIN Buildings b3 ON s.settlementId = b3.settlementId AND b3.buildingType = 'Steinbruch'
LEFT JOIN Buildings b4 ON s.settlementId = b4.settlementId AND b4.buildingType = 'Erzbergwerk'
LEFT JOIN Buildings b5 ON s.settlementId = b5.settlementId AND b5.buildingType = 'Lager'
LEFT JOIN Buildings b6 ON s.settlementId = b6.settlementId AND b6.buildingType = 'Farm'
LEFT JOIN Buildings b7 ON s.settlementId = b7.settlementId AND b7.buildingType = 'Markt'
LEFT JOIN Buildings b8 ON s.settlementId = b8.settlementId AND b8.buildingType = 'Kaserne';

-- View: MilitaryTrainingCosts - Military unit training costs with affordability check
CREATE OR REPLACE VIEW MilitaryTrainingCosts AS
SELECT 
    s.settlementId,
    s.settlementName,
    s.playerName,
    muc.unitType,
    muc.costWood,
    muc.costStone,
    muc.costOre,
    muc.costGold,
    muc.costSettlers,
    muc.trainingTime,
    muc.defensePower,
    muc.attackPower,
    muc.rangedPower,
    s.wood AS currentWood,
    s.stone AS currentStone,
    s.ore AS currentOre,
    s.playerGold AS currentGold,
    ss.freeSettlers,
    ur.isResearched,
    CASE 
        WHEN ur.isResearched = FALSE THEN FALSE
        WHEN s.wood >= muc.costWood AND s.stone >= muc.costStone AND s.ore >= muc.costOre 
             AND s.playerGold >= muc.costGold AND ss.freeSettlers >= muc.costSettlers THEN TRUE
        ELSE FALSE
    END AS canAfford,
    CASE
        WHEN ur.isResearched = FALSE THEN 'Unit not researched'
        WHEN s.wood < muc.costWood THEN CONCAT('Need ', ROUND(muc.costWood - s.wood), ' more wood')
        WHEN s.stone < muc.costStone THEN CONCAT('Need ', ROUND(muc.costStone - s.stone), ' more stone')
        WHEN s.ore < muc.costOre THEN CONCAT('Need ', ROUND(muc.costOre - s.ore), ' more ore')
        WHEN s.playerGold < muc.costGold THEN CONCAT('Need ', (muc.costGold - s.playerGold), ' more gold')
        WHEN ss.freeSettlers < muc.costSettlers THEN CONCAT('Need ', (muc.costSettlers - ss.freeSettlers), ' more settlers')
        ELSE 'Can train unit'
    END AS affordabilityMessage
FROM SettlementResources s
CROSS JOIN MilitaryUnitConfig muc
LEFT JOIN SettlementSettlers ss ON s.settlementId = ss.settlementId
LEFT JOIN UnitResearch ur ON s.settlementId = ur.settlementId AND muc.unitType = ur.unitType
WHERE muc.level = 1;

-- View: ResearchCosts - Research costs with affordability check
CREATE OR REPLACE VIEW ResearchCosts AS
SELECT 
    s.settlementId,
    s.settlementName,
    s.playerName,
    rc.unitType,
    rc.costWood,
    rc.costStone,
    rc.costOre,
    rc.costGold,
    rc.researchTime,
    rc.prerequisiteUnit,
    s.wood AS currentWood,
    s.stone AS currentStone,
    s.ore AS currentOre,
    s.playerGold AS currentGold,
    ur.isResearched,
    COALESCE(ur_prereq.isResearched, TRUE) AS prerequisiteResearched,
    CASE 
        WHEN ur.isResearched = TRUE THEN FALSE
        WHEN rc.prerequisiteUnit IS NOT NULL AND COALESCE(ur_prereq.isResearched, FALSE) = FALSE THEN FALSE
        WHEN s.wood >= rc.costWood AND s.stone >= rc.costStone AND s.ore >= rc.costOre 
             AND s.playerGold >= rc.costGold THEN TRUE
        ELSE FALSE
    END AS canAfford,
    CASE
        WHEN ur.isResearched = TRUE THEN 'Already researched'
        WHEN rc.prerequisiteUnit IS NOT NULL AND COALESCE(ur_prereq.isResearched, FALSE) = FALSE THEN CONCAT('Need to research ', rc.prerequisiteUnit, ' first')
        WHEN s.wood < rc.costWood THEN CONCAT('Need ', ROUND(rc.costWood - s.wood), ' more wood')
        WHEN s.stone < rc.costStone THEN CONCAT('Need ', ROUND(rc.costStone - s.stone), ' more stone')
        WHEN s.ore < rc.costOre THEN CONCAT('Need ', ROUND(rc.costOre - s.ore), ' more ore')
        WHEN s.playerGold < rc.costGold THEN CONCAT('Need ', (rc.costGold - s.playerGold), ' more gold')
        ELSE 'Can start research'
    END AS affordabilityMessage
FROM SettlementResources s
CROSS JOIN ResearchConfig rc
LEFT JOIN UnitResearch ur ON s.settlementId = ur.settlementId AND rc.unitType = ur.unitType
LEFT JOIN UnitResearch ur_prereq ON s.settlementId = ur_prereq.settlementId AND rc.prerequisiteUnit = ur_prereq.unitType;

-- View: GameStatistics - Game-wide statistics view
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