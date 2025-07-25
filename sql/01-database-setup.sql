-- Browsergame Database Schema - Bootstrap/Setup Phase
-- This file creates the database and user setup only
-- Part 1 of the modular loading sequence
-- 
-- Loading order managed by Docker init sequence:
-- 01-database-setup.sql (this file)
-- 02-*-tables.sql files  
-- 03-*-data.sql files
-- 04-*-procedures.sql files
-- 05-*-views.sql files
-- 06-*-events.sql files

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
FLUSH PRIVILEGES;

-- Switch to the new database
USE browsergame;

-- Create marker table to track modular loading progress
CREATE TABLE _ModularLoadingProgress (
    phase VARCHAR(50) PRIMARY KEY,
    loaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(255)
);

-- Mark setup phase as complete
INSERT INTO _ModularLoadingProgress (phase, description) VALUES 
('database_setup', 'Database and user setup completed');

SELECT 'Database setup phase completed successfully' AS status;