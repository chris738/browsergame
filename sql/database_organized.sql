-- Browsergame Database Schema
-- Organized database initialization file with modular structure
-- This file orchestrates the database creation using organized SQL files

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

-- ====================================
-- CORE TABLES (from tables/core_tables.sql)
-- ====================================
SOURCE sql/tables/core_tables.sql;

-- ====================================
-- ADDITIONAL TABLES
-- ====================================
-- Kaserne table modifications
SOURCE sql/tables/kaserne_tables.sql;

-- Research system tables
SOURCE sql/tables/research_tables.sql;

-- Military system tables
SOURCE sql/tables/military_tables.sql;

-- ====================================
-- STORED PROCEDURES
-- ====================================
-- Building procedures
SOURCE sql/procedures/building_procedures.sql;

-- Player procedures
SOURCE sql/procedures/player_procedures.sql;

-- Military procedures
SOURCE sql/procedures/military_procedures.sql;

-- ====================================
-- VIEWS
-- ====================================
-- Core game views
SOURCE sql/views/game_views.sql;

-- Enhanced views for simplified queries
SOURCE sql/views/enhanced_views.sql;

-- ====================================
-- INITIAL DATA
-- ====================================
-- Core initial data
SOURCE sql/data/initial_data.sql;

-- Kaserne configuration data
SOURCE sql/data/kaserne_data.sql;

-- Research system data
SOURCE sql/data/research_data.sql;

-- Military configuration data
SOURCE sql/data/military_data.sql;

-- ====================================
-- DATABASE EVENTS
-- ====================================
-- Automated processing events
SOURCE sql/data/database_events.sql;

-- Database initialization complete
SELECT 'Browsergame database initialized with organized structure!' AS status;