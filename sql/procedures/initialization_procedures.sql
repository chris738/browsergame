-- Enhanced Database Initialization Procedures
-- This file contains procedures for setting up initial game state and starting values

USE browsergame;

-- ====================================
-- INITIALIZATION PROCEDURE
-- ====================================

-- Procedure to initialize the complete database with starting values
DROP PROCEDURE IF EXISTS InitializeGameDatabase;

DELIMITER //
CREATE PROCEDURE InitializeGameDatabase()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Clear existing data if any
    DELETE FROM BuildingQueue;
    DELETE FROM MilitaryTrainingQueue;
    DELETE FROM ResearchQueue;
    DELETE FROM TradeOffers;
    DELETE FROM TradeTransactions;
    DELETE FROM TradeHistory;
    DELETE FROM MilitaryUnits;
    DELETE FROM UnitResearch;
    DELETE FROM MilitarySettlerCosts;
    DELETE FROM Buildings;
    DELETE FROM Map;
    DELETE FROM Settlement;
    DELETE FROM Spieler;
    
    -- Reset auto increment counters
    ALTER TABLE Spieler AUTO_INCREMENT = 1;
    ALTER TABLE Settlement AUTO_INCREMENT = 1;
    ALTER TABLE BuildingQueue AUTO_INCREMENT = 1;
    ALTER TABLE MilitaryTrainingQueue AUTO_INCREMENT = 1;
    ALTER TABLE ResearchQueue AUTO_INCREMENT = 1;
    ALTER TABLE TradeOffers AUTO_INCREMENT = 1;
    ALTER TABLE TradeTransactions AUTO_INCREMENT = 1;
    ALTER TABLE TradeHistory AUTO_INCREMENT = 1;
    
    -- Create default admin player
    CALL CreatePlayerWithSettlement('Admin');
    
    -- Create test players for development
    CALL CreatePlayerWithSettlement('TestPlayer1');
    CALL CreatePlayerWithSettlement('TestPlayer2');
    
    COMMIT;
    
    SELECT 
        'Database initialized successfully' AS status,
        (SELECT COUNT(*) FROM Spieler) AS totalPlayers,
        (SELECT COUNT(*) FROM Settlement) AS totalSettlements,
        'Default players created: Admin, TestPlayer1, TestPlayer2' AS message;
        
END //
DELIMITER ;

-- ====================================
-- BUILDING STARTING VALUES PROCEDURE
-- ====================================

-- Procedure to set enhanced starting values for buildings
DROP PROCEDURE IF EXISTS SetBuildingStartingValues;

DELIMITER //
CREATE PROCEDURE SetBuildingStartingValues(IN settlementId INT)
BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Update all resource buildings to level 2 for better start
    UPDATE Buildings 
    SET level = 2 
    WHERE settlementId = settlementId 
    AND buildingType IN ('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm');
    
    -- Keep Town Hall at level 1
    UPDATE Buildings 
    SET level = 1 
    WHERE settlementId = settlementId 
    AND buildingType = 'Rathaus';
    
    -- Market and Kaserne remain at level 0 (not built)
    
    -- Give bonus starting resources based on improved buildings
    UPDATE Settlement 
    SET wood = 15000, stone = 15000, ore = 15000
    WHERE Settlement.settlementId = settlementId;
    
    -- Give player bonus gold
    UPDATE Spieler 
    SET gold = 1000 
    WHERE playerId = (SELECT playerId FROM Settlement WHERE Settlement.settlementId = settlementId);
    
    COMMIT;
    
    SELECT 
        'Enhanced starting values applied' AS status,
        settlementId AS affectedSettlement;
        
END //
DELIMITER ;

-- ====================================
-- TESTING AND VALIDATION PROCEDURES
-- ====================================

-- Procedure to validate database integrity
DROP PROCEDURE IF EXISTS ValidateDatabase;

DELIMITER //
CREATE PROCEDURE ValidateDatabase()
BEGIN
    DECLARE tableCount INT DEFAULT 0;
    DECLARE playerCount INT DEFAULT 0;
    DECLARE settlementCount INT DEFAULT 0;
    DECLARE buildingCount INT DEFAULT 0;
    DECLARE configCount INT DEFAULT 0;
    DECLARE viewCount INT DEFAULT 0;
    DECLARE procedureCount INT DEFAULT 0;
    DECLARE eventCount INT DEFAULT 0;
    
    -- Count tables
    SELECT COUNT(*) INTO tableCount
    FROM information_schema.tables 
    WHERE table_schema = 'browsergame';
    
    -- Count data
    SELECT COUNT(*) INTO playerCount FROM Spieler;
    SELECT COUNT(*) INTO settlementCount FROM Settlement;
    SELECT COUNT(*) INTO buildingCount FROM Buildings;
    SELECT COUNT(*) INTO configCount FROM BuildingConfig;
    
    -- Count views
    SELECT COUNT(*) INTO viewCount
    FROM information_schema.views 
    WHERE table_schema = 'browsergame';
    
    -- Count procedures
    SELECT COUNT(*) INTO procedureCount
    FROM information_schema.routines 
    WHERE routine_schema = 'browsergame' AND routine_type = 'PROCEDURE';
    
    -- Count events
    SELECT COUNT(*) INTO eventCount
    FROM information_schema.events 
    WHERE event_schema = 'browsergame';
    
    SELECT 
        'Database validation complete' AS status,
        tableCount AS totalTables,
        viewCount AS totalViews,
        procedureCount AS totalProcedures,
        eventCount AS totalEvents,
        playerCount AS totalPlayers,
        settlementCount AS totalSettlements,
        buildingCount AS totalBuildings,
        configCount AS buildingConfigs,
        CASE 
            WHEN tableCount >= 15 AND viewCount >= 5 AND procedureCount >= 3 AND eventCount >= 4 THEN 'PASSED'
            ELSE 'FAILED'
        END AS validationResult;
        
END //
DELIMITER ;

-- ====================================
-- RESET AND CLEANUP PROCEDURES
-- ====================================

-- Procedure to reset a player's settlement to starting values
DROP PROCEDURE IF EXISTS ResetPlayerToStart;

DELIMITER //
CREATE PROCEDURE ResetPlayerToStart(IN playerId INT)
BEGIN
    DECLARE settlementId INT;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get settlement ID
    SELECT Settlement.settlementId INTO settlementId 
    FROM Settlement 
    WHERE Settlement.playerId = playerId 
    LIMIT 1;
    
    IF settlementId IS NULL THEN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Player not found or has no settlement';
    END IF;
    
    -- Clear queues
    DELETE FROM BuildingQueue WHERE BuildingQueue.settlementId = settlementId;
    DELETE FROM MilitaryTrainingQueue WHERE MilitaryTrainingQueue.settlementId = settlementId;
    DELETE FROM ResearchQueue WHERE ResearchQueue.settlementId = settlementId;
    
    -- Reset buildings to starting state
    UPDATE Buildings 
    SET level = CASE buildingType
        WHEN 'Rathaus' THEN 1
        WHEN 'Holzfäller' THEN 1
        WHEN 'Steinbruch' THEN 1
        WHEN 'Erzbergwerk' THEN 1
        WHEN 'Lager' THEN 1
        WHEN 'Farm' THEN 1
        WHEN 'Markt' THEN 0
        WHEN 'Kaserne' THEN 0
    END
    WHERE Buildings.settlementId = settlementId;
    
    -- Reset resources
    UPDATE Settlement 
    SET wood = 10000, stone = 10000, ore = 10000
    WHERE Settlement.settlementId = settlementId;
    
    -- Reset player stats
    UPDATE Spieler 
    SET punkte = 0, gold = 500
    WHERE Spieler.playerId = playerId;
    
    -- Reset military units
    UPDATE MilitaryUnits 
    SET count = 0 
    WHERE MilitaryUnits.settlementId = settlementId;
    
    -- Reset research
    UPDATE UnitResearch 
    SET isResearched = FALSE, researchedAt = NULL
    WHERE UnitResearch.settlementId = settlementId;
    
    -- Reset military settler costs
    UPDATE MilitarySettlerCosts 
    SET totalSettlerCost = 0
    WHERE MilitarySettlerCosts.settlementId = settlementId;
    
    COMMIT;
    
    SELECT 
        'Player reset to starting values' AS status,
        playerId AS affectedPlayer,
        settlementId AS affectedSettlement;
        
END //
DELIMITER ;

-- ====================================
-- AUTOMATIC INITIALIZATION
-- ====================================

-- Call initialization if database is empty
-- This procedure will run automatically when this file is loaded

-- Check if database needs initialization
SET @player_count = (SELECT COUNT(*) FROM Spieler);

-- Only initialize if empty
CALL ValidateDatabase();

-- If no players exist, run initialization
/*
-- Commented out to prevent automatic initialization
-- Uncomment this block to enable automatic initialization on database creation

IF @player_count = 0 THEN
    CALL InitializeGameDatabase();
    SELECT 'Database automatically initialized with starting values' AS auto_init_status;
ELSE
    SELECT 'Database already contains data, skipping auto-initialization' AS auto_init_status;
END IF;
*/

SELECT 'Database initialization procedures loaded successfully' AS status;