-- Browsergame Database Schema - Working Modular Version
-- This file orchestrates the database creation using organized SQL files in the correct order
-- 
-- Usage: mysql -u root -p < database_modular_working.sql
-- 
-- The database is built in the following order:
-- 1. Database and user setup
-- 2. Core tables (essential game structure)
-- 3. Feature tables (military, research, kaserne, travel, battle)
-- 4. Initial configuration data 
-- 5. Feature data (military, research, kaserne)
-- 6. Stored procedures (organized by functionality)
-- 7. Views (game UI support)
-- 8. Database events (background automation)
-- 9. Event scheduler activation

-- ====================================
-- DATABASE AND USER SETUP
-- ====================================

-- Drop and recreate database
DROP DATABASE IF EXISTS browsergame;
CREATE DATABASE browsergame;

-- Create users with proper permissions
CREATE USER IF NOT EXISTS 'browsergame'@'localhost' IDENTIFIED BY 'sicheresPasswort';
CREATE USER IF NOT EXISTS 'browsergame'@'%' IDENTIFIED BY 'sicheresPasswort';

-- Grant permissions
GRANT ALL PRIVILEGES ON browsergame.* TO 'root'@'localhost';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'localhost';
GRANT ALL PRIVILEGES ON browsergame.* TO 'browsergame'@'%';

-- Switch to the new database
USE browsergame;

-- ====================================
-- MODULAR COMPONENT LOADING
-- ====================================

-- 1. CORE TABLES (essential game structure)
SOURCE sql/tables/core_tables.sql;

-- 2. FEATURE TABLES (additional game systems)
SOURCE sql/tables/military_tables.sql;
SOURCE sql/tables/research_tables.sql;
SOURCE sql/tables/kaserne_tables.sql;
SOURCE sql/tables/travel_tables.sql;
SOURCE sql/tables/battle_tables.sql;

-- 3. INITIAL CONFIGURATION DATA (required for game functionality)
SOURCE sql/data/initial_data.sql;

-- 4. FEATURE CONFIGURATION DATA (military, research, etc.)
SOURCE sql/data/military_data.sql;
SOURCE sql/data/research_data.sql;
SOURCE sql/data/kaserne_data.sql;
SOURCE sql/data/military_travel_data.sql;

-- 5. STORED PROCEDURES (organized by functionality)
SOURCE sql/procedures/player_procedures.sql;
SOURCE sql/procedures/building_procedures.sql;
SOURCE sql/procedures/military_procedures.sql;
SOURCE sql/procedures/travel_procedures.sql;
SOURCE sql/procedures/initialization_procedures.sql;

-- 6. VIEWS (for PHP application interface)
SOURCE sql/views/game_views.sql;
SOURCE sql/views/enhanced_views.sql;

-- 7. DATABASE EVENTS (background automation)
SOURCE sql/data/database_events.sql;

-- ====================================
-- FINAL SETUP AND INITIALIZATION
-- ====================================

-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Enable all events
ALTER EVENT UpdateResources ENABLE;
ALTER EVENT ProcessBuildingQueue ENABLE;
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;
ALTER EVENT ProcessTravelArrivals ENABLE;

-- Run database validation
CALL ValidateDatabase();

-- Safe conditional initialization
CALL SafeInitializeDatabase();

-- Database initialization complete
SELECT 'Browsergame database initialized with working modular structure!' AS status,
       'Database is ready for use' AS message,
       'Event scheduler is enabled for automated processing' AS events,
       'Modular file loading completed successfully' AS loading_result;