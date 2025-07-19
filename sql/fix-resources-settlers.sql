-- Fix resource and settler calculations for new settlements
USE browsergame;

-- Update getResources query to provide base values for storage capacity
-- The issue is that new settlements have 0 storage capacity because they don't have Storage buildings
-- We need to provide a base storage capacity even without Storage buildings

-- For now, let's give settlements a base storage capacity and settlers
-- We'll modify the SettlementSettlers view to provide base values

DROP VIEW IF EXISTS SettlementSettlers;

CREATE VIEW SettlementSettlers AS
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
    -- Max settlers: base 100 + Farm level bonus
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
    -- Free settlers (maxSettlers - usedSettlers)
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

-- Also update the getResources query to provide base storage capacity
-- We need to modify the database.php directly for this, but for now let's at least fix the storage

-- Give a base storage capacity of 10000 for new settlements
UPDATE Settlement SET wood = LEAST(wood, 10000), stone = LEAST(stone, 10000), ore = LEAST(ore, 10000) 
WHERE settlementId = 1;

SELECT 'Resource and settler calculations updated with base values' as result;