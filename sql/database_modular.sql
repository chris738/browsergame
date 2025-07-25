-- Browsergame Database Schema - Modular Version
-- This file orchestrates the database creation using organized SQL files in the correct order
-- 
-- Usage: mysql -u root -p < database_modular.sql
-- 
-- The database is built in the following order:
-- 1. Database and user setup
-- 2. Core tables
-- 3. Additional tables (military, research, kaserne)
-- 4. Initial data and configuration
-- 5. Stored procedures
-- 6. Views (simple first, then enhanced)
-- 7. Database events
-- 8. Event scheduler activation

-- ====================================
-- DATABASE AND USER SETUP
-- ====================================
SOURCE sql/data/database_setup.sql;

-- ====================================
-- TABLE CREATION
-- ====================================

-- 1. Core tables (essential game structure)
SOURCE sql/tables/core_tables.sql;

-- 2. Military system tables
SOURCE sql/tables/military_tables.sql;

-- 3. Research system tables
SOURCE sql/tables/research_tables.sql;

-- 4. Travel system tables
SOURCE sql/tables/travel_tables.sql;

-- 5. Battle system tables
SOURCE sql/tables/battle_tables.sql;

-- 6. Kaserne system tables (if exists)
-- SOURCE sql/tables/kaserne_tables.sql;

-- ====================================
-- STORED PROCEDURES
-- ====================================

-- Player management procedures
SOURCE sql/procedures/player_procedures.sql;

-- Building management procedures
SOURCE sql/procedures/building_procedures.sql;

-- Military management procedures
SOURCE sql/procedures/military_procedures.sql;

-- Travel system procedures
SOURCE sql/procedures/travel_procedures.sql;

-- Initialization procedures
SOURCE sql/procedures/initialization_procedures.sql;

-- ====================================
-- VIEWS
-- ====================================

-- Core game views
SOURCE sql/views/game_views.sql;

-- Enhanced views for simplified PHP access
SOURCE sql/views/enhanced_views.sql;

-- ====================================
-- INITIAL DATA
-- ====================================

-- Initial configuration data
SOURCE sql/data/initial_data.sql;

-- Military system data
SOURCE sql/data/military_data.sql;

-- Research system data
SOURCE sql/data/research_data.sql;

-- Travel system data
SOURCE sql/data/travel_data.sql;

-- Kaserne system data
SOURCE sql/data/kaserne_data.sql;

-- ====================================
-- DATABASE EVENTS
-- ====================================

-- Core game events (resource updates, queue processing)
SOURCE sql/events/game_events.sql;

-- Travel system events
SOURCE sql/events/travel_events.sql;

-- Enable event scheduler and all events
SOURCE sql/events/enable_events.sql;

-- ====================================
-- FINAL VALIDATION AND COMPLETION
-- ====================================

-- Database initialization complete
SELECT 'Browsergame database initialized with modular structure!' AS status,
       'Database is ready for use' AS message,
       'Event scheduler is enabled for automated processing' AS events,
       'Enhanced views and procedures loaded' AS features;

-- Database initialization complete
SELECT 'Browsergame database initialized with modular structure!' AS status,
       'Database is ready for use' AS message,
       'Event scheduler is enabled for automated processing' AS events;