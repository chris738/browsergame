-- Event Scheduler Setup
-- Final event scheduler configuration and enabling

-- Enable event scheduler at the end
SET GLOBAL event_scheduler = ON;

-- Enable events (they are created but not enabled by default)
ALTER EVENT UpdateResources ENABLE;
ALTER EVENT ProcessBuildingQueue ENABLE;
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;
ALTER EVENT ProcessTravelArrivals ENABLE;