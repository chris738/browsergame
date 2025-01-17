-- Setup

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
    gold INT NOT NULL DEFAULT 500,
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
    buildTime INT,
    PRIMARY KEY (buildingType, level)
);

-- Tabelle: BuildingQueue
CREATE TABLE BuildingQueue (
    queueId INT AUTO_INCREMENT PRIMARY KEY,
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm') NOT NULL,
    startTime DATETIME NOT NULL,
    endTime DATETIME NOT NULL,
    isActive BOOLEAN NOT NULL DEFAULT FALSE,
    level INT NOT NULL DEFAULT 0,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (buildingType) REFERENCES BuildingConfig(buildingType) ON DELETE CASCADE
);

-- Tabelle: Buildings
CREATE TABLE Buildings (
    settlementId INT NOT NULL,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm') NOT NULL,
    level INT NOT NULL DEFAULT 1,
    visable boolean NOT NULL DEFAULT false,
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE,
    FOREIGN KEY (buildingType, level) REFERENCES BuildingConfig(buildingType, level) ON DELETE CASCADE,
    PRIMARY KEY (settlementId, buildingType)
);

-- Prozedur: Spieler erstellen und initialisieren
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
            ('Farm', 1);

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
    DROP PROCEDURE IF EXISTS UpgradeBuilding;
    DELIMITER //

    CREATE PROCEDURE UpgradeBuilding (
        IN inSettlementId INT,
        IN inBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm')
    )
    BEGIN
        DECLARE currentBuildingLevel INT;
        DECLARE nextLevel INT;
        DECLARE nextLevelWoodCost FLOAT;
        DECLARE nextLevelStoneCost FLOAT;
        DECLARE nextLevelOreCost FLOAT;
        DECLARE nextBuildTime INT;
        DECLARE lastEndTime DATETIME;

        -- Aktuelles Level aus Buildings abrufen
        SELECT level INTO currentBuildingLevel
        FROM Buildings
        WHERE settlementId = inSettlementId AND buildingType = inBuildingType;

        SET nextLevel = currentBuildingLevel + 1;

        -- Kosten und Bauzeit für das nächste Level aus BuildingConfig abrufen
        SELECT costWood, costStone, costOre, buildTime INTO 
            nextLevelWoodCost, nextLevelStoneCost, nextLevelOreCost, nextBuildTime
        FROM BuildingConfig
        WHERE buildingType = inBuildingType AND level = nextLevel;

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

            -- Letztes Endzeit aus BuildingQueue abrufen oder NOW() verwenden, wenn keine Einträge existieren
            SELECT COALESCE(MAX(endTime), NOW()) INTO lastEndTime
            FROM BuildingQueue
            WHERE settlementId = inSettlementId;

            -- Einfügen des Bauvorhabens in die Warteschlange
            INSERT INTO BuildingQueue (settlementId, buildingType, startTime, endTime, isActive, level)
            VALUES (
                inSettlementId,
                inBuildingType,
                lastEndTime,
                DATE_ADD(lastEndTime, INTERVAL nextBuildTime SECOND),
                FALSE,
                nextLevel
            );

            -- Prüfen, ob ein Bauvorhaben aktiv ist
            IF NOT EXISTS (SELECT 1 FROM BuildingQueue WHERE isActive = TRUE) THEN
                -- Aktiviert das erste Bauvorhaben
                CALL ProcessBuildingQueue();
            END IF;
        ELSE
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Nicht genügend Ressourcen für das Upgrade';
        END IF;
    END //
    DELIMITER ;


-- Prozedur: PopulateBuildingConfig
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
        DECLARE baseSettlers FLOAT DEFAULT 1.0;
        DECLARE baseProduction FLOAT DEFAULT 3600.0;
        DECLARE baseBuildTime INT DEFAULT 10;

        DECLARE buildingType VARCHAR(50);
        DECLARE done INT DEFAULT FALSE;
        DECLARE cur CURSOR FOR 
            SELECT name FROM TempBuildings;
        DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

        -- Temporary table for building types
        DROP TEMPORARY TABLE IF EXISTS TempBuildings;

        CREATE TEMPORARY TABLE TempBuildings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm')
        );

        INSERT INTO TempBuildings (name) VALUES
            ('Holzfäller'), ('Steinbruch'), ('Erzbergwerk'), ('Lager'), ('Farm');

        -- Debugging: Check TempBuildings
        SELECT * FROM TempBuildings;

        -- Open the cursor to iterate over building types
        OPEN cur;

        -- Loop through all building types
        read_loop: LOOP
            FETCH cur INTO buildingType;
            IF done THEN
                LEAVE read_loop;
            END IF;

            -- Werte initialisieren
            SET baseCostWood = 100.0;
            SET baseCostStone = 100.0;
            SET baseCostOre = 100.0;
            SET baseSettlers = 1.0;
            SET baseBuildTime = 10;

            -- Set production rates for specific buildings
            IF buildingType = 'Lager' THEN
                SET baseProduction = 10000.0;
            ELSEIF buildingType = 'Farm' THEN
                SET baseProduction = 100.0;
            ELSE
                SET baseProduction = 3600.0;
            END IF;

            -- Füge Daten für Levels hinzu
            SET lvl = 1;
            WHILE lvl <= maxLvl DO
                INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, settlers, productionRate, buildTime)
                VALUES (buildingType, lvl, baseCostWood, baseCostStone, baseCostOre, baseSettlers, baseProduction, baseBuildTime);

                -- Werte erhöhen für das nächste Level
                SET baseCostWood = baseCostWood * 1.1;
                SET baseCostStone = baseCostStone * 1.1;
                SET baseCostOre = baseCostOre * 1.1;
                SET baseSettlers = baseSettlers * 1.1;
                SET baseProduction = baseProduction * 1.1;
                SET baseBuildTime = baseBuildTime + 10;

                SET lvl = lvl + 1;
            END WHILE;
        END LOOP;

        -- Close the cursor
        CLOSE cur;

        -- Temporäre Tabelle löschen
        DROP TEMPORARY TABLE IF EXISTS TempBuildings;
    END //

    DELIMITER ;

    -- Call the procedure
    CALL PopulateBuildingConfig();

    -- Check the result
    SELECT * FROM BuildingConfig;



-- Prozedur: ProcessBuildingQueue
    DELIMITER //
    CREATE PROCEDURE ProcessBuildingQueue()
    BEGIN
        DECLARE nextQueueId INT;
        DECLARE nextSettlementId INT;
        DECLARE nextBuildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm');
        DECLARE nextEndTime DATETIME;

        -- Entferne abgeschlossene Bauvorhaben
        DELETE FROM BuildingQueue
        WHERE isActive = TRUE AND endTime <= NOW();

        -- Prüfe, ob ein weiteres Bauvorhaben existiert
        SELECT queueId, settlementId, buildingType, endTime
        INTO nextQueueId, nextSettlementId, nextBuildingType, nextEndTime
        FROM BuildingQueue
        WHERE isActive = FALSE
        ORDER BY queueId ASC
        LIMIT 1;

        -- Falls ein weiteres Bauvorhaben existiert, aktiviere es
        IF nextQueueId IS NOT NULL THEN
            -- Bauvorhaben aktivieren
            UPDATE BuildingQueue
            SET isActive = TRUE,
                startTime = NOW(),
                endTime = DATE_ADD(NOW(), INTERVAL TIMESTAMPDIFF(SECOND, NOW(), nextEndTime) SECOND)
            WHERE queueId = nextQueueId;

            -- Event erstellen, um das nächste Bauvorhaben zu aktivieren
            SET @eventName = CONCAT('ProcessBuildingQueue_', nextQueueId);
            SET @eventSQL = CONCAT(
                'CREATE EVENT ', @eventName, '
                ON SCHEDULE AT "', nextEndTime, '"
                DO BEGIN
                    UPDATE Buildings
                    SET level = level + 1
                    WHERE settlementId = ', nextSettlementId, ' AND buildingType = "', nextBuildingType, '";
                    CALL ProcessBuildingQueue();
                END;'
            );
            PREPARE stmt FROM @eventSQL;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        END IF;
    END //
    DELIMITER ;

-- Event: Resourcen Updaten
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

-- View: Warteschleife
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

-- Beispielaufrufe
SHOW EVENTS;
CALL CreatePlayerWithSettlement('Chris');
CALL UpgradeBuilding(1, 'Holzfäller');
SELECT * FROM OpenBuildingQueue WHERE settlementId = 1;