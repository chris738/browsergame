-- Fix starting buildings according to issues
USE browsergame;

-- Remove Market and Kaserne from existing settlement (Issue #71)
DELETE FROM Buildings WHERE settlementId = 1 AND buildingType IN ('Markt', 'Kaserne');

-- Remove Farm from existing settlement as it's not part of starting buildings
DELETE FROM Buildings 
WHERE settlementId = 1 AND buildingType = 'Farm';

-- Ensure starting buildings exist and are visible for existing settlement
INSERT IGNORE INTO Buildings (settlementId, buildingType, level, visable) VALUES
    (1, 'Rathaus', 1, true),
    (1, 'Holzfäller', 1, true),
    (1, 'Steinbruch', 1, true),
    (1, 'Erzbergwerk', 1, true),
    (1, 'Lager', 1, true);

-- Update existing buildings to be visible if they already exist
UPDATE Buildings 
SET level = 1, visable = true 
WHERE settlementId = 1 AND buildingType IN ('Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager');

-- Update the CreatePlayerWithSettlement procedure to fix starting buildings
DROP PROCEDURE IF EXISTS CreatePlayerWithSettlement;

DELIMITER //

CREATE PROCEDURE CreatePlayerWithSettlement (IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    DECLARE xCoord INT;
    DECLARE yCoord INT;

    -- Generate random coordinates
    SET xCoord = FLOOR(RAND() * 21) - 10;
    SET yCoord = FLOOR(RAND() * 21) - 10;

    -- Create player
    INSERT INTO Spieler (name, punkte) VALUES (playerName, 0);
    SET newPlayerId = LAST_INSERT_ID();

    -- Create settlement
    INSERT INTO Settlement (name, wood, stone, ore, playerId)
    VALUES (CONCAT(playerName, '_Settlement'), 10000, 10000, 10000, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();

    -- Place settlement on map
    INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
    VALUES (newSettlementId, xCoord, yCoord);

    -- Create initial buildings - Rathaus, resource buildings and storage at level 1
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
        (newSettlementId, 'Rathaus', 1, true),        -- Town Hall
        (newSettlementId, 'Holzfäller', 1, true),     -- Lumberjack for wood production
        (newSettlementId, 'Steinbruch', 1, true),     -- Quarry for stone production
        (newSettlementId, 'Erzbergwerk', 1, true),    -- Mine for ore production
        (newSettlementId, 'Lager', 1, true);          -- Storage for resources
    
    -- Other buildings (Farm, Market, Kaserne) are NOT created initially
    -- They will be created when first built by the player
    -- Market requires Storage Level 5 (will be created when requirements are met)
    -- Kaserne requires Farm Level 5 (will be created when requirements are met)
    
    -- Create SettlementSettlers entry for the new settlement
    INSERT IGNORE INTO SettlementSettlers (settlementId, maxSettlers, freeSettlers) 
    VALUES (newSettlementId, 100, 95); -- Start with 100 max, 95 free (5 used by Town Hall)
    
END //

DELIMITER ;

SELECT 'Starting buildings fixed for new settlements' as result;