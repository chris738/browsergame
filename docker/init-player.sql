-- Create initial test player for Docker setup
USE browsergame;

-- Create a test player
CALL CreatePlayerWithSettlement('TestPlayer');

-- Show that the player was created
SELECT 'Initial player created successfully' AS status;
SELECT * FROM Spieler LIMIT 5;
SELECT * FROM Settlement LIMIT 5;