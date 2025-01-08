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
    wood INT NOT NULL DEFAULT 1000,
    stone INT NOT NULL DEFAULT 1000,
    ore INT NOT NULL DEFAULT 1000,
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
CREATE TABLE BuildingConfig (
    configId INT AUTO_INCREMENT PRIMARY KEY,
    buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk') NOT NULL,
    level INT NOT NULL,
    costWood INT NOT NULL,
    costStone INT NOT NULL,
    costOre INT NOT NULL,
    productionRate INT NOT NULL,
    UNIQUE (buildingType, level)
);

-- Beispiel-Daten für BuildingConfig
INSERT INTO BuildingConfig (buildingType, level, costWood, costStone, costOre, productionRate) VALUES
('Holzfäller', 1, 100, 50, 0, 10),
('Holzfäller', 2, 200, 100, 0, 20),
('Holzfäller', 3, 300, 150, 0, 35),
('Holzfäller', 4, 400, 200, 0, 50),
('Holzfäller', 5, 500, 250, 0, 70),
('Steinbruch', 1, 150, 100, 0, 8),
('Steinbruch', 2, 300, 200, 0, 18),
('Steinbruch', 3, 450, 300, 0, 30),
('Steinbruch', 4, 600, 400, 0, 45),
('Steinbruch', 5, 750, 500, 0, 65),
('Erzbergwerk', 1, 200, 150, 50, 5),
('Erzbergwerk', 2, 400, 300, 100, 12),
('Erzbergwerk', 3, 600, 450, 150, 22),
('Erzbergwerk', 4, 800, 600, 200, 35),
('Erzbergwerk', 5, 1000, 750, 250, 50);

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

DROP EVENT IF EXISTS UpdateResources;

-- Event: Ressourcen in der Siedlung aktualisieren
DELIMITER //
CREATE EVENT UpdateResources
ON SCHEDULE EVERY 1 SECOND
DO
BEGIN
    UPDATE Settlement s
    JOIN Buildings b ON s.settlementId = b.settlementId
    JOIN BuildingConfig bc ON b.type = bc.buildingType AND b.level = bc.level
    SET
        s.wood = s.wood + (CASE WHEN b.buildingType = 'Holzfäller' THEN bc.productionRate ELSE 1 END),
        s.stone = s.stone + (CASE WHEN b.buildingType = 'Steinbruch' THEN bc.productionRate  ELSE 1 END),
        s.ore = s.ore + (CASE WHEN b.buildingType = 'Erzbergwerk' THEN bc.productionRate ELSE 1 END)
    WHERE s.settlementId = b.settlementId;
END //
DELIMITER ;


CALL CreatePlayerWithSettlement('Chris');


