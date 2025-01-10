-- Erstellung einer neuen Datenbank
CREATE DATABASE browsergame;

-- Erstellung eines neuen Benutzers
CREATE USER 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';

-- Berechtigungen für den Benutzer
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';

-- Wechsel zur neuen Datenbank
USE browsergame;

-- Tabelle: Spieler
CREATE TABLE Spieler (
    playerId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    punkte INT NOT NULL DEFAULT 0,
    UNIQUE (name)
);

-- Tabelle: Map
CREATE TABLE Map (
    settlementId INT PRIMARY KEY,
    xCoordinate INT NOT NULL,
    yCoordinate INT NOT NULL,
    UNIQUE (xCoordinate, yCoordinate),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Tabelle: Settlement
CREATE TABLE Settlement (
    settlementId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    wood FLOAT NOT NULL DEFAULT 1000.0,
    stone FLOAT NOT NULL DEFAULT 1000.0,
    ore FLOAT NOT NULL DEFAULT 1000.0,
    playerId INT NOT NULL,
    FOREIGN KEY (playerId) REFERENCES Spieler(playerId) ON DELETE CASCADE
);

-- Tabelle: Buildings
CREATE TABLE Buildings (
    buildingId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk') NOT NULL,
    level INT NOT NULL DEFAULT 1,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    UNIQUE (settlementId, buildingType)
);

-- Tabelle: BuildingConfig 
DROP TABLE IF EXISTS BuildingConfig;
CREATE TABLE BuildingConfig (
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk') NOT NULL,
    level INT NOT NULL,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    productionRate FLOAT NOT NULL,
    PRIMARY KEY (buildingType, level)
);

-- Prozedur: Spieler erstellen und initialisieren
DELIMITER //
CREATE PROCEDURE CreatePlayerWithSettlement (IN playerName VARCHAR(100))
BEGIN
    DECLARE newPlayerId INT;
    DECLARE newSettlementId INT;
    DECLARE xCoord INT;
    DECLARE yCoord INT;

    -- Zufällige Koordinaten generieren (Umkreis wächst über Zeit)
    SET xCoord = FLOOR(RAND() * 21) - 10;
    SET yCoord = FLOOR(RAND() * 21) - 10;

    -- Spieler erstellen
    INSERT INTO Spieler (name, punkte) VALUES (playerName, 0);
    SET newPlayerId = LAST_INSERT_ID();

    -- Siedlung erstellen
    INSERT INTO Settlement (name, wood, stone, ore, playerId)
    VALUES (CONCAT(playerName, '_Settlement'), 1000, 1000, 1000, newPlayerId);
    SET newSettlementId = LAST_INSERT_ID();

    -- Siedlung auf Karte platzieren
    INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
    VALUES (newSettlementId, xCoord, yCoord);

    -- Gebäude erstellen
    INSERT INTO Buildings (settlementId, buildingType) VALUES
        (newSettlementId, 'Holzfäller'),
        (newSettlementId, 'Steinbruch'),
        (newSettlementId, 'Erzbergwerk');
END //
DELIMITER ;


-- Event, zum updaten der resourcen
DROP EVENT IF EXISTS UpdateResources;
DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    -- Debugging-Logik hinzufügen, falls notwendig (nur für Testzwecke)
    -- INSERT INTO DebugLog (message, createdAt) VALUES ('Event executed', NOW());
    
    -- Ressourcen aktualisieren
    UPDATE Settlement s
    SET 
        s.wood = s.wood + (
            SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
            FROM Buildings b
            JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
        ),
        s.stone = s.stone + (
            SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
            FROM Buildings b
            JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
        ),
        s.ore = s.ore + (
            SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
            FROM Buildings b
            JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
            WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
        );
END //
DELIMITER ;

ALTER EVENT UpdateResources ENABLE;
SHOW EVENTS LIKE 'UpdateResources';


-- Prozedur: Gebäude upgraden
DROP PROCEDURE IF EXISTS UpgradeBuilding;
DELIMITER //
CREATE PROCEDURE UpgradeBuilding (
    IN inSettlementId INT,
    IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk')
)
BEGIN
    DECLARE currentBuildingLevel INT;
    DECLARE nextLevelWoodCost INT;
    DECLARE nextLevelStoneCost INT;
    DECLARE nextLevelOreCost INT;

    -- Aktuelles Level des Gebäudes abrufen
    SELECT b.level INTO currentBuildingLevel
    FROM Buildings b
    WHERE b.settlementId = inSettlementId AND b.BuildingType = inBuildingType
    LIMIT 1;

    -- Kosten für das nächste Level abrufen
    SELECT bc.costWood, bc.costStone, bc.costOre INTO nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost
    FROM BuildingConfig bc
    WHERE bc.buildingType = inBuildingType AND bc.level = currentBuildingLevel + 1
    LIMIT 3;

    -- Überprüfen, ob genug Ressourcen vorhanden sind
    IF (SELECT s.wood FROM Settlement s WHERE s.settlementId = inSettlementId) >= nextLevelWoodCost AND
       (SELECT s.stone FROM Settlement s WHERE s.settlementId = inSettlementId) >= nextLevelStoneCost AND
       (SELECT s.ore FROM Settlement s WHERE s.settlementId = inSettlementId) >= nextLevelOreCost THEN

        -- Ressourcen abziehen
        UPDATE Settlement s
        SET s.wood = s.wood - nextLevelWoodCost,
            s.stone = s.stone - nextLevelStoneCost,
            s.ore = s.ore - nextLevelOreCost
        WHERE s.settlementId = inSettlementId;

        -- Gebäudelevel erhöhen
        UPDATE Buildings b
        SET b.level = b.level + 1
        WHERE b.settlementId = inSettlementId AND b.BuildingType = inBuildingType;
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
    END IF;
END //
DELIMITER ;

--Dynamische Einfügeoperation mit den Anforderungen
DELIMITER //
DROP PROCEDURE IF EXISTS PopulateBuildingConfig;
CREATE PROCEDURE PopulateBuildingConfig()
BEGIN
    DECLARE lvl INT;
    DECLARE maxLvl INT DEFAULT 50;
    DECLARE baseCostWood FLOAT DEFAULT 100.0;
    DECLARE baseCostStone FLOAT DEFAULT 100.0;
    DECLARE baseCostOre FLOAT DEFAULT 100.0;
    DECLARE baseProduction FLOAT DEFAULT 3600.0;

    -- Holzfäller
    SET lvl = 1;
    WHILE lvl <= maxLvl DO
        INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, productionRate)
        VALUES (
            'Holzfäller',
            lvl,
            baseCostWood,
            baseCostStone / 2,  -- Stein kostet nur die Hälfte
            baseCostOre,
            baseProduction
        );
        SET baseCostWood = baseCostWood * 1.2;  -- Kosten steigen um 20%
        SET baseCostStone = baseCostStone * 1.2;
        SET baseCostOre = baseCostOre * 1.2;
        SET baseProduction = baseProduction * 1.15;  -- Produktionsrate steigt um 15%
        SET lvl = lvl + 1;
    END WHILE;

    -- Steinbruch
    SET lvl = 1;
    SET baseCostWood = 100;
    SET baseCostStone = 100;
    SET baseCostOre = 100;
    SET baseProduction = 3600;
    WHILE lvl <= maxLvl DO
        INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, productionRate)
        VALUES (
            'Steinbruch',
            lvl,
            baseCostWood / 2,  -- Holz kostet nur die Hälfte
            baseCostStone,
            baseCostOre,
            baseProduction
        );
        SET baseCostWood = baseCostWood * 1.2;
        SET baseCostStone = baseCostStone * 1.2;
        SET baseCostOre = baseCostOre * 1.2;
        SET baseProduction = baseProduction * 1.15;
        SET lvl = lvl + 1;
    END WHILE;

    -- Erzbergwerk
    SET lvl = 1;
    SET baseCostWood = 100;
    SET baseCostStone = 100;
    SET baseCostOre = 100;
    SET baseProduction = 3600;
    WHILE lvl <= maxLvl DO
        INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, productionRate)
        VALUES (
            'Erzbergwerk',
            lvl,
            baseCostWood,
            baseCostStone / 2,  -- Stein kostet nur die Hälfte
            baseCostOre,
            baseProduction
        );
        SET baseCostWood = baseCostWood * 1.2;
        SET baseCostStone = baseCostStone * 1.2;
        SET baseCostOre = baseCostOre * 1.2;
        SET baseProduction = baseProduction * 1.15;
        SET lvl = lvl + 1;
    END WHILE;
END //
DELIMITER ;

-- Funktionen

CALL UpgradeBuilding(5, 'Holzfäller');
CALL CreatePlayerWithSettlement('Chris');
CALL PopulateBuildingConfig();
select * from BuildingConfig;

UPDATE Settlement SET wood = 1000, stone = 1000, ore = 1000;

UPDATE Buildings SET level = 1;