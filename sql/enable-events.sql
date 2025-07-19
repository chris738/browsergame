-- Enable database events after initialization
USE browsergame;

-- Activate events
ALTER EVENT UpdateResources ENABLE;
ALTER EVENT ProcessBuildingQueue ENABLE;
ALTER EVENT ProcessMilitaryTrainingQueue ENABLE;
ALTER EVENT ProcessResearchQueue ENABLE;

SELECT 'Database events enabled successfully!' AS status;