-- Database and User Setup
-- Core database creation and user management
-- 
-- This file creates the database, users, and sets permissions

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