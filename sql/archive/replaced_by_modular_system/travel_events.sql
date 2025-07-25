-- Travel System Events
-- These events replace the cron job system for processing travel arrivals

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

-- Enable the event immediately
ALTER EVENT ProcessTravelArrivals ENABLE;

SELECT 'Travel processing event created and enabled!' AS status;