-- Browsergame Database Schema
-- Main database initialization file (restructured and organized)

-- Setup
-- Löschen der bestehenden Datenbank
DROP DATABASE IF EXISTS browsergame;

-- Erstellung einer neuen Datenbank
CREATE DATABASE browsergame;

-- Erstellung eines neuen Benutzers mit Standard-Zugangsdaten
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';

-- Zusätzlich: Root-Benutzer Zugriff sicherstellen (für Standard-Installation)
-- Root hat bereits Vollzugriff, aber explizit für diese Datenbank freigeben
GRANT ALL PRIVILEGES ON browsergame.* TO 'root'@'localhost';

-- Berechtigungen für den Benutzer
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';

-- Wechsel zur neuen Datenbank
USE browsergame;

-- Enable global event scheduler
SET GLOBAL event_scheduler = ON;

-- Include table definitions
SOURCE tables/core_tables.sql;

-- Include views
SOURCE views/game_views.sql;

-- Include stored procedures
SOURCE procedures/player_procedures.sql;
SOURCE procedures/building_procedures.sql;

-- Include initial data
SOURCE data/initial_data.sql;

-- Events for automated processing
-- Event: Process Building Queue
DROP EVENT IF EXISTS ProcessBuildingQueue;

DELIMITER //

CREATE EVENT ProcessBuildingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update active buildings that are completed
    UPDATE Buildings b
    INNER JOIN BuildingQueue bq ON b.settlementId = bq.settlementId AND b.buildingType = bq.buildingType
    SET b.level = bq.level, b.visable = TRUE
    WHERE bq.endTime <= NOW() AND bq.isActive = FALSE;

    -- Delete completed building queue entries
    DELETE FROM BuildingQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Event: Update Resources (every 10 seconds)
DROP EVENT IF EXISTS UpdateResources;

DELIMITER //

CREATE EVENT UpdateResources
ON SCHEDULE EVERY 10 SECOND
DO
BEGIN
    UPDATE Settlement s
    SET 
        wood = LEAST(
            s.wood + COALESCE((
                SELECT SUM(bc.productionRate) / 360  -- Divided by 360 for 10-second intervals (3600/10)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Holzfäller'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        ),
        stone = LEAST(
            s.stone + COALESCE((
                SELECT SUM(bc.productionRate) / 360
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Steinbruch'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        ),
        ore = LEAST(
            s.ore + COALESCE((
                SELECT SUM(bc.productionRate) / 360
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Erzbergwerk'
            ), 0),
            GREATEST(10000, COALESCE((
                SELECT SUM(bc.productionRate)
                FROM Buildings b
                INNER JOIN BuildingConfig bc ON b.buildingType = bc.buildingType AND b.level = bc.level
                WHERE b.settlementId = s.settlementId AND b.buildingType = 'Lager'
            ), 0))
        );
END //

DELIMITER ;

-- Event: Process Military Training Queue
DROP EVENT IF EXISTS ProcessMilitaryTrainingQueue;

DELIMITER //

CREATE EVENT ProcessMilitaryTrainingQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update military units with completed training
    INSERT INTO MilitaryUnits (settlementId, unitType, count)
    SELECT settlementId, unitType, count
    FROM MilitaryTrainingQueue
    WHERE endTime <= NOW()
    ON DUPLICATE KEY UPDATE count = count + VALUES(count);

    -- Delete completed training queue entries
    DELETE FROM MilitaryTrainingQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Event: Process Research Queue
DROP EVENT IF EXISTS ProcessResearchQueue;

DELIMITER //

CREATE EVENT ProcessResearchQueue
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    -- Update research completion
    UPDATE UnitResearch ur
    INNER JOIN ResearchQueue rq ON ur.settlementId = rq.settlementId AND ur.unitType = rq.unitType
    SET ur.isResearched = TRUE, ur.researchedAt = NOW()
    WHERE rq.endTime <= NOW();

    -- Delete completed research queue entries
    DELETE FROM ResearchQueue WHERE endTime <= NOW();
END //

DELIMITER ;

-- Database initialization complete
-- Note: All components are now organized in separate files for better maintainability