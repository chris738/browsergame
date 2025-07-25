-- Kaserne (Barracks) table modifications
-- This script modifies existing tables to support Kaserne building type

USE browsergame;

-- Add Kaserne to BuildingConfig ENUM
ALTER TABLE BuildingConfig MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;

-- Add Kaserne to BuildingQueue ENUM  
ALTER TABLE BuildingQueue MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;

-- Add Kaserne to Buildings ENUM
ALTER TABLE Buildings MODIFY COLUMN buildingType ENUM('Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne') NOT NULL;