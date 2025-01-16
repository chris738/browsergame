-- Löschen der bestehenden Datenbank
DROP DATABASE IF EXISTS browsergame;

-- Erstellung einer neuen Datenbank
CREATE DATABASE browsergame;

-- Erstellung eines neuen Benutzers
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';

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

-- Tabelle: Settlement
CREATE TABLE Settlement (
    settlementId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    wood FLOAT NOT NULL DEFAULT 1000.0,
    stone FLOAT NOT NULL DEFAULT 1000.0,
    ore FLOAT NOT NULL DEFAULT 1000.0,
    settlers FLOAT NOT NULL DEFAULT 100.0,
    playerId INT NOT NULL,
    FOREIGN KEY (playerId) REFERENCES Spieler(playerId) ON DELETE CASCADE
);

-- Tabelle: Map
CREATE TABLE Map (
    settlementId INT PRIMARY KEY,
    xCoordinate INT NOT NULL,
    yCoordinate INT NOT NULL,
    UNIQUE (xCoordinate, yCoordinate),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Tabelle: BuildingConfig
CREATE TABLE BuildingConfig (
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm') NOT NULL,
    level INT NOT NULL,
    costWood FLOAT NOT NULL,
    costStone FLOAT NOT NULL,
    costOre FLOAT NOT NULL,
    settlers FLOAT NOT NULL DEFAULT 0.0,
    productionRate FLOAT NOT NULL,
    PRIMARY KEY (buildingType, level)
);

-- Tabelle: Buildings
CREATE TABLE Buildings (
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm') NOT NULL,
    level INT NOT NULL DEFAULT 1,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (buildingType, level) REFERENCES BuildingConfig(buildingType, level) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, buildingType)
);

-- Prozedur: Spieler erstellen und initialisieren
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
        VALUES (CONCAT(playerName, '_Settlement'), 1000, 1000, 1000, newPlayerId);
        SET newSettlementId = LAST_INSERT_ID();

        -- Siedlung auf Karte platzieren
        INSERT INTO Map (settlementId, xCoordinate, yCoordinate)
        VALUES (newSettlementId, xCoord, yCoord);

        -- Gebäude erstellen
        INSERT INTO Buildings (settlementId, buildingType) VALUES
            (newSettlementId, 'Holzfäller'),
            (newSettlementId, 'Steinbruch'),
            (newSettlementId, 'Erzbergwerk'),
            (newSettlementId, 'Lager'),
            (newSettlementId, 'Farm');
    END //
    DELIMITER ;

-- Prozedur: Gebäude upgraden
    DELIMITER //
    CREATE PROCEDURE UpgradeBuilding (
        IN inSettlementId INT,
        IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm')
    )
    BEGIN
        DECLARE currentBuildingLevel INT;
        DECLARE nextLevelWoodCost FLOAT;
        DECLARE nextLevelStoneCost FLOAT;
        DECLARE nextLevelOreCost FLOAT;
        DECLARE nextSettlers FLOAT;

        -- Aktuelles Level abrufen
        SELECT b.level INTO currentBuildingLevel
        FROM Buildings b
        WHERE b.settlementId = inSettlementId AND b.buildingType = inBuildingType;

        -- Kosten für das nächste Level abrufen
        SELECT bc.costWood, bc.costStone, bc.costOre INTO nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost
        FROM BuildingConfig bc
        WHERE bc.buildingType = inBuildingType AND bc.level = currentBuildingLevel + 1;

        -- Überprüfen, ob genug Ressourcen vorhanden sind
        IF (SELECT wood FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelWoodCost AND
        (SELECT stone FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelStoneCost AND
        (SELECT ore FROM Settlement WHERE settlementId = inSettlementId) >= nextLevelOreCost THEN

            -- Ressourcen abziehen
            UPDATE Settlement
            SET wood = wood - nextLevelWoodCost,
                stone = stone - nextLevelStoneCost,
                ore = ore - nextLevelOreCost
            WHERE settlementId = inSettlementId;

            -- Gebäudelevel erhöhen
            UPDATE Buildings
            SET level = level + 1
            WHERE settlementId = inSettlementId AND buildingType = inBuildingType;
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
        END IF;
    END //
    DELIMITER ;

-- Prozedur PopulateBuildingConfig
    DELETE FROM BuildingConfig;

    DROP PROCEDURE IF EXISTS PopulateBuildingConfig;

    DELIMITER //
    CREATE PROCEDURE PopulateBuildingConfig()
    BEGIN
        DECLARE lvl INT DEFAULT 1;
        DECLARE maxLvl INT DEFAULT 50;

        DECLARE baseCostWood FLOAT DEFAULT 100.0;
        DECLARE baseCostStone FLOAT DEFAULT 100.0;
        DECLARE baseCostOre FLOAT DEFAULT 100.0;
        DECLARE baseCostSettlers FLOAT DEFAULT 5.0;
        DECLARE baseProduction FLOAT DEFAULT 3600.0;

        -- Holzfäller
            SET lvl = 1;
            SET baseCostWood = 100.0;
            SET baseCostStone = 100.0;
            SET baseCostOre = 100.0;
            SET baseCostSettlers = 5.0;
            SET baseProduction = 3600.0;

            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate)
                VALUES ('Holzfäller', lvl, baseCostWood, baseCostStone, baseCostOre, baseCostSettlers, baseProduction);

                SET baseCostWood = baseCostWood * 1.2;
                SET baseCostStone = baseCostStone * 1.2;
                SET baseCostOre = baseCostOre * 1.2;
                SET baseProduction = baseProduction * 1.15;

                SET lvl = lvl + 1;
            END WHILE;

        -- Steinbruch
            SET lvl = 1;
            SET baseCostWood = 120.0;
            SET baseCostStone = 80.0;  -- Steinbruch hat geringere Startkosten für Stein
            SET baseCostOre = 100.0;
            SET baseCostSettlers = 5.0;
            SET baseProduction = 3600.0;

            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate)
                VALUES ('Steinbruch', lvl, baseCostWood, baseCostStone, baseCostOre, baseCostSettlers, baseProduction);

                SET baseCostWood = baseCostWood * 1.2;
                SET baseCostStone = baseCostStone * 1.2;
                SET baseCostOre = baseCostOre * 1.2;
                SET baseProduction = baseProduction * 1.15;

                SET lvl = lvl + 1;
            END WHILE;

        -- Erzbergwerk
            SET lvl = 1;
            SET baseCostWood = 150.0;
            SET baseCostStone = 120.0;
            SET baseCostOre = 70.0;  -- Erzbergwerk hat geringere Startkosten für Erz
            SET baseCostSettlers = 5.0;
            SET baseProduction = 3600.0;

            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate)
                VALUES ('Erzbergwerk', lvl, baseCostWood, baseCostStone, baseCostOre, baseCostSettlers, baseProduction);

                SET baseCostWood = baseCostWood * 1.2;
                SET baseCostStone = baseCostStone * 1.2;
                SET baseCostOre = baseCostOre * 1.2;
                SET baseProduction = baseProduction * 1.15;

                SET lvl = lvl + 1;
            END WHILE;

        -- Lager
            SET lvl = 1;
            SET baseCostWood = 200.0;
            SET baseCostStone = 150.0;
            SET baseCostOre = 100.0;
            SET baseCostSettlers = 10.0;
            SET baseProduction = 10000.0; -- Lager hat Kapazität als Produktionsrate

            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate)
                VALUES ('Lager', lvl, baseCostWood, baseCostStone, baseCostOre, baseCostSettlers, baseProduction);

                SET baseCostWood = baseCostWood * 1.2;
                SET baseCostStone = baseCostStone * 1.2;
                SET baseCostOre = baseCostOre * 1.2;
                SET baseProduction = baseProduction * 1.2;  -- Kapazität steigt um 20%

                SET lvl = lvl + 1;
            END WHILE;

        -- Farm
            SET lvl = 1;
            SET baseCostWood = 100.0;
            SET baseCostStone = 80.0;
            SET baseCostOre = 30.0;
            SET baseCostSettlers = 0.0;
            SET baseProduction = 100.0; -- Lager hat Kapazität als Produktionsrate

            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate)
                VALUES ('Farm', lvl, baseCostWood, baseCostStone, baseCostOre, baseCostSettlers, baseProduction);

                SET baseCostWood = baseCostWood * 1.18;
                SET baseCostStone = baseCostStone * 1.18;
                SET baseCostOre = baseCostOre * 1.18;
                SET baseProduction = baseProduction * 1.2;  -- Kapazität steigt um 20%

                SET lvl = lvl + 1;
            END WHILE;

    END //
    DELIMITER ;

    CALL PopulateBuildingConfig();

-- Erstelle das Event zum Updaten der Resourcen
    DROP EVENT IF EXISTS UpdateResources;

    DELIMITER //
    CREATE EVENT UpdateResources
    ON SCHEDULE EVERY 1 SECOND
    DO
    BEGIN
        -- Aktualisiere Holz
        UPDATE Settlement s
        SET s.wood = LEAST(
            s.wood + (
                SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
            ),
            (
                SELECT IFNULL(SUM(bc.productionRate), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            )
        );

        -- Aktualisiere Stein
        UPDATE Settlement s
        SET s.stone = LEAST(
            s.stone + (
                SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
            ),
            (
                SELECT IFNULL(SUM(bc.productionRate), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            )
        );

        -- Aktualisiere Erz
        UPDATE Settlement s
        SET s.ore = LEAST(
            s.ore + (
                SELECT IFNULL(SUM(bc.productionRate / 3600), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
            ),
            (
                SELECT IFNULL(SUM(bc.productionRate), 0)
                FROM Buildings b
                INNER JOIN BuildingConfig bc
                ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            )
        );
    END //
    DELIMITER ;

    -- Aktiviere das Event
    ALTER EVENT UpdateResources ENABLE;
    SET GLOBAL event_scheduler = ON;



-- Beispielaufrufe

CALL CreatePlayerWithSettlement('Chris');
CALL UpgradeBuilding(1, 'Holzfäller');
