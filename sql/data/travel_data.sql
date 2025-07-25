-- Travel System Configuration Data
-- Initial data for travel system

USE browsergame;

-- Clear existing travel config data
DELETE FROM TravelConfig;

-- Travel system configuration
INSERT INTO TravelConfig (travelType, baseSpeed, description) VALUES
('trade', 5, 'Base travel speed for trades: 5 seconds per block distance'),
('military', 5, 'Base travel speed for military units: 5 seconds per block (modified by unit speed)');