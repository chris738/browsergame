-- Create initial test player for Docker setup
USE browsergame;

-- Enable event scheduler for automatic resource generation
SET GLOBAL event_scheduler = ON;

-- Create a test player
CALL CreatePlayerWithSettlement('TestPlayer');

-- Show that the player was created
SELECT 'Initial player created successfully' AS status;
SELECT * FROM Spieler LIMIT 5;
SELECT * FROM Settlement LIMIT 5;

-- Ensure event scheduler remains enabled after initialization
SET GLOBAL event_scheduler = ON;

-- Verify event scheduler is enabled
SHOW VARIABLES LIKE 'event_scheduler';

-- Show that events are created
SHOW EVENTS;