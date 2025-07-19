-- Database Views
-- Views for the browser game

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