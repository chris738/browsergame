<?php

interface DatabaseInterface {
    public function getResources($settlementId);
    public function getBuilding($settlementId, $buildingType);
    public function upgradeBuilding($settlementId, $buildingType);
    public function getRegen($settlementId);
    public function getSettlementName($settlementId);
    public function getQueue($settlementId);
    public function getMap();
    
    // Admin-specific methods
    public function getPlayerCount();
    public function getSettlementCount();
    public function getActiveQueuesCount();
    public function getAllPlayers();
    public function getAllSettlements();
    public function getAllQueues();
    public function createPlayer($name, $gold);
    public function deletePlayer($playerId);
    public function updatePlayerStats($playerId, $points, $gold);
    public function updateSettlementResources($settlementId, $wood, $stone, $ore);
    public function deleteQueue($queueId);
    public function clearAllQueues();
    
    // Building configuration management methods
    public function getAllBuildingConfigs();
    public function getBuildingConfig($buildingType, $level);
    public function updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    public function createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    public function deleteBuildingConfig($buildingType, $level);
    public function getDistinctBuildingTypes();
    
    // Market/Trade management methods
    public function getTotalTradeOffers();
    public function getActiveTradeOffers();
    public function getCompletedTradesCount();
    public function cancelTradeOffer($tradeId);
    public function clearAllTradeOffers();
    public function getActiveTradesForAdmin();
    public function getTradeHistoryForAdmin($limit = 20);
    public function getTradeAnalytics();
    public function getTradeHistoryBetweenPlayers($playerId1, $playerId2, $limit = 10);

    // Military methods
    public function getMilitaryUnits($settlementId);
    public function getMilitaryTrainingQueue($settlementId);
    public function getMilitaryStats($settlementId);
    public function trainMilitaryUnit($settlementId, $unitType, $count);
    
    // Research methods  
    public function getUnitResearch($settlementId);
    public function getResearchQueue($settlementId);
    public function getResearchConfig();
    public function startResearch($settlementId, $unitType);

    // Helper methods
    public function getConnection();
    public function getPlayerNameFromSettlement($settlementId);
    public function getPlayerIdFromSettlement($settlementId);
    public function getPlayerGold($settlementId);
    public function isConnected();
}

?>