-- Player and Settlement Management Procedures

DROP PROCEDURE IF EXISTS CreatePlayerWithSettlement;

DELIMITER //

CREATE PROCEDURE CreatePlayerWithSettlement (IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    DECLARE xCoord INT;
    DECLARE yCoord INT;

    -- Zufällige Koordinaten generieren
    SET xCoord = FLOOR(RAND() * 21) - 10;
    SET yCoord = FLOOR(RAND() * 21) - 10;

    -- Spieler erstellen
    INSERT INTO Spieler (name, punkte) VALUES (playerName, 0);
    SET newPlayerId = LAST_INSERT_ID();

    -- Siedlung erstellen
    INSERT INTO Settlement (name, wood, stone, ore, playerId)
    VALUES (CONCAT(playerName, '_Settlement'), 10000, 10000, 10000, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();

    -- Siedlung auf Karte platzieren
    INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
    VALUES (newSettlementId, xCoord, yCoord);

    -- Insert necessary building types and levels into BuildingConfig
    INSERT IGNORE INTO BuildingConfig (buildingType, level) VALUES
        ('Holzfäller', 1),
        ('Steinbruch', 1),
        ('Erzbergwerk', 1),
        ('Lager', 1),
        ('Farm', 1),
        ('Rathaus', 1),
        ('Markt', 1),
        ('Kaserne', 1);

    -- Gebäude erstellen - nur Startsiedlung mit Rathaus, Ressourcengebäuden und Lager
    INSERT INTO Buildings (settlementId, buildingType, level, visable) VALUES
        (newSettlementId, 'Rathaus', 1, true),
        (newSettlementId, 'Holzfäller', 1, true),
        (newSettlementId, 'Steinbruch', 1, true),
        (newSettlementId, 'Erzbergwerk', 1, true),
        (newSettlementId, 'Lager', 1, true),
        (newSettlementId, 'Farm', 1, true),
        (newSettlementId, 'Markt', 1, true),
        (newSettlementId, 'Kaserne', 1, true);
END //

DELIMITER ;