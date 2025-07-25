-- Travel System Events
-- Events for processing travel arrivals and battles

USE browsergame;

-- Event: Process Travel Arrivals
DROP EVENT IF EXISTS ProcessTravelArrivals;

DELIMITER //
CREATE EVENT ProcessTravelArrivals
ON SCHEDULE EVERY 5 SECOND
DO
BEGIN
    CALL ProcessAllArrivals();
END//
DELIMITER ;