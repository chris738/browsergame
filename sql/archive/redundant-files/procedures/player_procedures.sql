-- Player and Settlement Management Procedures

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

    -- Create initial buildings - Rathaus, resource buildings, storage and farm at level 1
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
        (newSettlementId, 'Rathaus', 1, true),        -- Town Hall
        (newSettlementId, 'Holzfäller', 1, true),     -- Lumberjack for wood production
        (newSettlementId, 'Steinbruch', 1, true),     -- Quarry for stone production
        (newSettlementId, 'Erzbergwerk', 1, true),    -- Mine for ore production
        (newSettlementId, 'Lager', 1, true),          -- Storage for resources
        (newSettlementId, 'Farm', 1, true);           -- Farm for settlers production
    
    -- Other buildings (Market, Kaserne) are NOT created initially
    -- They will be created when first built by the player
    -- Market requires Storage Level 5 (will be created when requirements are met)
    -- Kaserne requires Farm Level 5 (will be created when requirements are met)
    
    -- Initialize MilitarySettlerCosts for the new settlement
    INSERT INTO MilitarySettlerCosts (settlementId, totalSettlerCost) 
    VALUES (newSettlementId, 0);
    
END //

DELIMITER ;