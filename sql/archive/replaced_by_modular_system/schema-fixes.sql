-- Additional tables and schema fixes for browsergame
-- This script adds missing tables and columns needed for proper game functionality

USE browsergame;

-- Add Map table for settlement positioning
CREATE TABLE IF NOT EXISTS Map (
    settlementId INT PRIMARY KEY,
    xCoordinate INT NOT NULL,
    yCoordinate INT NOT NULL,
    UNIQUE (xCoordinate, yCoordinate),
    FOREIGN KEY (settlementId) REFERENCES Settlement(settlementId) ON DELETE CASCADE
);

-- Add productionRate column to BuildingConfig if it doesn't exist
-- This fixes the UpdateResources event scheduler error
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'BuildingConfig' 
     AND column_name = 'productionRate' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE BuildingConfig ADD COLUMN productionRate FLOAT NOT NULL DEFAULT 1.0',
    'SELECT ''productionRate column already exists'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Fix BuildingConfig settlers column type from INT to FLOAT if needed
-- This fixes compatibility with the building upgrade procedures
SET @sql = (SELECT IF(
    (SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'BuildingConfig' 
     AND column_name = 'settlers' 
     AND table_schema = DATABASE()) = 'int',
    'ALTER TABLE BuildingConfig MODIFY COLUMN settlers FLOAT NOT NULL DEFAULT 1.0',
    'SELECT ''settlers column is already FLOAT type'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add isActive column to BuildingQueue if it doesn't exist
-- This fixes the UpgradeBuilding procedure error
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'BuildingQueue' 
     AND column_name = 'isActive' 
     AND table_schema = DATABASE()) = 0,
    'ALTER TABLE BuildingQueue ADD COLUMN isActive BOOLEAN NOT NULL DEFAULT FALSE AFTER endTime',
    'SELECT ''isActive column already exists in BuildingQueue'''));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default Map position for settlement if it exists
INSERT IGNORE INTO Map (settlementId, xCoordinate, yCoordinate) 
SELECT settlementId, 5, 5 FROM Settlement WHERE settlementId = 1;