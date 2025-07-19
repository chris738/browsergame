<?php

// Include all the new modular components
require_once __DIR__ . '/sql-data-validator.php';
require_once __DIR__ . '/database/interfaces/DatabaseInterface.php';
require_once __DIR__ . '/database/connection/DatabaseConnection.php';
require_once __DIR__ . '/database/schema/DatabaseSchemaManager.php';
require_once __DIR__ . '/database/procedures/BuildingProcedures.php';
require_once __DIR__ . '/database/repositories/ResourceRepository.php';
require_once __DIR__ . '/database/repositories/BuildingRepository.php';
require_once __DIR__ . '/database/repositories/SettlementRepository.php';
require_once __DIR__ . '/database/repositories/MapRepository.php';
require_once __DIR__ . '/database/repositories/AdminRepository.php';
require_once __DIR__ . '/database/repositories/TradingRepository.php';
require_once __DIR__ . '/database/repositories/MilitaryRepository.php';

class Database implements DatabaseInterface {
    private $connection;
    private $schemaManager;
    private $resourceRepo;
    private $buildingRepo;
    private $settlementRepo;
    private $mapRepo;
    private $adminRepo;
    private $tradingRepo;
    private $militaryRepo;
    private $connectionFailed = false;

    public function __construct() {
        // Initialize database connection
        $this->connection = new DatabaseConnection();
        $this->connectionFailed = !$this->connection->isConnected();
        
        // Initialize schema manager
        $this->schemaManager = new DatabaseSchemaManager(
            $this->connection->getConnection(), 
            $this->connection->getDbName()
        );
        
        // Initialize repositories
        $conn = $this->connection->getConnection();
        $this->resourceRepo = new ResourceRepository($conn, $this->connectionFailed);
        $this->buildingRepo = new BuildingRepository($conn, $this->connectionFailed, $this->schemaManager);
        $this->settlementRepo = new SettlementRepository($conn, $this->connectionFailed);
        $this->mapRepo = new MapRepository($conn, $this->connectionFailed);
        $this->adminRepo = new AdminRepository($conn, $this->connectionFailed);
        $this->tradingRepo = new TradingRepository($conn, $this->connectionFailed);
        $this->militaryRepo = new MilitaryRepository($conn, $this->connectionFailed);
        
        // Initialize schema if connected
        if (!$this->connectionFailed) {
            $this->schemaManager->initializeSchemaIfNeeded();
        }
    }

    public function isConnected() {
        return !$this->connectionFailed;
    }

    public function getConnection() {
        return $this->connection->getConnection();
    }

    // Resource methods
    public function getResources($settlementId) {
        return $this->resourceRepo->getResources($settlementId);
    }

    public function getRegen($settlementId) {
        return $this->resourceRepo->getRegen($settlementId);
    }

    public function updateSettlementResources($settlementId, $wood, $stone, $ore) {
        return $this->resourceRepo->updateSettlementResources($settlementId, $wood, $stone, $ore);
    }

    // Building methods
    public function getBuilding($settlementId, $buildingType) {
        return $this->buildingRepo->getBuilding($settlementId, $buildingType);
    }

    public function upgradeBuilding($settlementId, $buildingType) {
        return $this->buildingRepo->upgradeBuilding($settlementId, $buildingType);
    }

    public function getAllBuildingConfigs() {
        return $this->buildingRepo->getAllBuildingConfigs();
    }

    public function getBuildingConfig($buildingType, $level) {
        return $this->buildingRepo->getBuildingConfig($buildingType, $level);
    }

    public function updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime) {
        return $this->buildingRepo->updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    }

    public function createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime) {
        return $this->buildingRepo->createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
    }

    public function deleteBuildingConfig($buildingType, $level) {
        return $this->buildingRepo->deleteBuildingConfig($buildingType, $level);
    }

    public function getDistinctBuildingTypes() {
        return $this->buildingRepo->getDistinctBuildingTypes();
    }

    // Settlement methods
    public function getSettlementName($settlementId) {
        return $this->settlementRepo->getSettlementName($settlementId);
    }

    public function getQueue($settlementId) {
        return $this->settlementRepo->getQueue($settlementId);
    }

    public function deleteQueue($queueId) {
        return $this->settlementRepo->deleteQueue($queueId);
    }

    public function clearAllQueues() {
        return $this->settlementRepo->clearAllQueues();
    }

    public function getAllQueues() {
        return $this->settlementRepo->getAllQueues();
    }

    // Map methods
    public function getMap() {
        return $this->mapRepo->getMap();
    }

    // Admin methods
    public function getPlayerCount() {
        return $this->adminRepo->getPlayerCount();
    }

    public function getSettlementCount() {
        return $this->adminRepo->getSettlementCount();
    }

    public function getActiveQueuesCount() {
        return $this->adminRepo->getActiveQueuesCount();
    }

    public function getAllPlayers() {
        return $this->adminRepo->getAllPlayers();
    }

    public function getAllSettlements() {
        return $this->adminRepo->getAllSettlements();
    }

    public function createPlayer($name, $gold = 500) {
        return $this->adminRepo->createPlayer($name, $gold);
    }

    public function deletePlayer($playerId) {
        return $this->adminRepo->deletePlayer($playerId);
    }

    public function updatePlayerStats($playerId, $points, $gold) {
        return $this->adminRepo->updatePlayerStats($playerId, $points, $gold);
    }

    public function getPlayerNameFromSettlement($settlementId) {
        return $this->adminRepo->getPlayerNameFromSettlement($settlementId);
    }

    public function getPlayerIdFromSettlement($settlementId) {
        return $this->adminRepo->getPlayerIdFromSettlement($settlementId);
    }

    public function getPlayerGold($settlementId) {
        return $this->adminRepo->getPlayerGold($settlementId);
    }

    // Trading methods
    public function getTotalTradeOffers() {
        return $this->tradingRepo->getTotalTradeOffers();
    }

    public function getActiveTradeOffers() {
        return $this->tradingRepo->getActiveTradeOffers();
    }

    public function getCompletedTradesCount() {
        return $this->tradingRepo->getCompletedTradesCount();
    }

    public function cancelTradeOffer($tradeId) {
        return $this->tradingRepo->cancelTradeOffer($tradeId);
    }

    public function clearAllTradeOffers() {
        return $this->tradingRepo->clearAllTradeOffers();
    }

    public function getActiveTradesForAdmin() {
        return $this->tradingRepo->getActiveTradesForAdmin();
    }

    public function getTradeHistoryForAdmin($limit = 20) {
        return $this->tradingRepo->getTradeHistoryForAdmin($limit);
    }

    public function getTradeAnalytics() {
        return $this->tradingRepo->getTradeAnalytics();
    }

    public function getTradeHistoryBetweenPlayers($playerId1, $playerId2, $limit = 10) {
        return $this->tradingRepo->getTradeHistoryBetweenPlayers($playerId1, $playerId2, $limit);
    }

    // Military methods
    public function getMilitaryUnits($settlementId) {
        return $this->militaryRepo->getMilitaryUnits($settlementId);
    }

    public function getMilitaryTrainingQueue($settlementId) {
        return $this->militaryRepo->getMilitaryTrainingQueue($settlementId);
    }

    public function getMilitaryStats($settlementId) {
        return $this->militaryRepo->getMilitaryStats($settlementId);
    }

    public function trainMilitaryUnit($settlementId, $unitType, $count) {
        return $this->militaryRepo->trainMilitaryUnit($settlementId, $unitType, $count);
    }

    // Research methods
    public function getUnitResearch($settlementId) {
        return $this->militaryRepo->getUnitResearch($settlementId);
    }

    public function getResearchQueue($settlementId) {
        return $this->militaryRepo->getResearchQueue($settlementId);
    }

    public function getResearchConfig() {
        return $this->militaryRepo->getResearchConfig();
    }

    public function startResearch($settlementId, $unitType) {
        return $this->militaryRepo->startResearch($settlementId, $unitType);
    }
}

?>