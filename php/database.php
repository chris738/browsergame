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
require_once __DIR__ . '/database/repositories/BattleRepository.php';
require_once __DIR__ . '/database/repositories/TravelRepository.php';

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
    private $battleRepo;
    private $travelRepo;
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
        $this->battleRepo = new BattleRepository($conn, $this->connectionFailed);
        $this->travelRepo = new TravelRepository($conn, $this->connectionFailed);
        
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

    // Battle methods
    public function getSettlementMilitaryPower($settlementId) {
        return $this->battleRepo->getSettlementMilitaryPower($settlementId);
    }

    public function attackSettlement($attackerSettlementId, $defenderSettlementId, $units) {
        try {
            // Get military power for both sides (repositories handle database fallback)
            $attackerPower = $this->battleRepo->getSettlementMilitaryPower($attackerSettlementId);
            $defenderPower = $this->battleRepo->getSettlementMilitaryPower($defenderSettlementId);

            // If connection failed, use demo battle calculation
            if ($this->connectionFailed) {
                return $this->simulateDemoBattle($attackerSettlementId, $defenderSettlementId, $units, $attackerPower, $defenderPower);
            }

            // Validate that attacker has enough units
            $attackerUnits = $this->getMilitaryUnits($attackerSettlementId);
            foreach ($units as $unitType => $count) {
                if ($count > $attackerUnits[$unitType]) {
                    return ['success' => false, 'message' => "Not enough $unitType units"];
                }
            }

            // Calculate battle outcome
            $battleResult = $this->battleRepo->calculateBattle($attackerPower, $defenderPower);

            // Calculate unit losses
            $attackerLosses = [];
            $defenderLosses = [];
            
            foreach ($attackerPower['units'] as $unitType => $count) {
                if ($count > 0) {
                    $attackerLosses[$unitType] = (int)($count * $battleResult['attackerLossRate']);
                }
            }
            
            foreach ($defenderPower['units'] as $unitType => $count) {
                if ($count > 0) {
                    $defenderLosses[$unitType] = (int)($count * $battleResult['defenderLossRate']);
                }
            }

            // Apply unit losses
            $this->battleRepo->applyUnitLosses($attackerSettlementId, $attackerLosses);
            $this->battleRepo->applyUnitLosses($defenderSettlementId, $defenderLosses);

            // Calculate resource plunder if attacker wins
            $resourcesPlundered = ['wood' => 0, 'stone' => 0, 'ore' => 0];
            if ($battleResult['winner'] === 'attacker') {
                $resourcesPlundered = $this->battleRepo->calculateResourcePlunder($defenderSettlementId, $battleResult);
                $this->battleRepo->transferResources($defenderSettlementId, $attackerSettlementId, $resourcesPlundered);
            }

            // Record battle
            $battleData = [
                'attackerUnitsTotal' => $attackerPower['units'],
                'defenderUnitsTotal' => $defenderPower['units'],
                'attackerUnitsLost' => $attackerLosses,
                'defenderUnitsLost' => $defenderLosses,
                'winner' => $battleResult['winner'],
                'resourcesPlundered' => $resourcesPlundered,
                'battleResult' => $battleResult
            ];

            $battleId = $this->battleRepo->recordBattle($attackerSettlementId, $defenderSettlementId, $battleData);

            return [
                'success' => true,
                'battleId' => $battleId,
                'winner' => $battleResult['winner'],
                'attackerLosses' => $attackerLosses,
                'defenderLosses' => $defenderLosses,
                'resourcesPlundered' => $resourcesPlundered,
                'battleResult' => $battleResult
            ];

        } catch (Exception $e) {
            error_log("Error in attackSettlement: " . $e->getMessage());
            return ['success' => false, 'message' => 'Battle failed: ' . $e->getMessage()];
        }
    }

    private function simulateDemoBattle($attackerSettlementId, $defenderSettlementId, $units, $attackerPower, $defenderPower) {
        // Calculate battle outcome using demo data
        $battleResult = $this->battleRepo->calculateBattle($attackerPower, $defenderPower);
        
        // Calculate unit losses based on the units sent in the attack
        $attackerLosses = [];
        foreach ($units as $unitType => $count) {
            if ($count > 0) {
                // Apply loss rate to units sent in attack
                $attackerLosses[$unitType] = (int)($count * $battleResult['attackerLossRate']);
            }
        }
        
        // Simulate defender losses (they lose some units too)
        $defenderLosses = [];
        foreach ($defenderPower['units'] as $unitType => $count) {
            if ($count > 0) {
                $defenderLosses[$unitType] = (int)($count * $battleResult['defenderLossRate']);
            }
        }

        // Calculate resource plunder if attacker wins
        $resourcesPlundered = ['wood' => 0, 'stone' => 0, 'ore' => 0];
        if ($battleResult['winner'] === 'attacker') {
            // Demo plunder amounts
            $resourcesPlundered = [
                'wood' => mt_rand(100, 500),
                'stone' => mt_rand(50, 300),
                'ore' => mt_rand(25, 150)
            ];
        }

        return [
            'success' => true,
            'battleId' => 'demo_' . time(),
            'winner' => $battleResult['winner'],
            'attackerLosses' => $attackerLosses,
            'defenderLosses' => $defenderLosses,
            'resourcesPlundered' => $resourcesPlundered,
            'battleResult' => $battleResult
        ];
    }

    public function getRecentBattles($settlementId, $limit = 10) {
        return $this->battleRepo->getRecentBattles($settlementId, $limit);
    }

    public function getAttackableSettlements($attackerSettlementId) {
        return $this->battleRepo->getAttackableSettlements($attackerSettlementId);
    }

    // Travel methods
    public function getTravelConfig($travelType) {
        return $this->travelRepo->getTravelConfig($travelType);
    }

    public function updateTravelConfig($travelType, $baseSpeed) {
        return $this->travelRepo->updateTravelConfig($travelType, $baseSpeed);
    }

    public function calculateDistance($fromSettlementId, $toSettlementId) {
        return $this->travelRepo->calculateDistance($fromSettlementId, $toSettlementId);
    }

    public function startMilitaryTravel($attackerSettlementId, $defenderSettlementId, $units) {
        return $this->travelRepo->startMilitaryTravel($attackerSettlementId, $defenderSettlementId, $units);
    }

    public function startTradeTravel($fromSettlementId, $toSettlementId, $resources, $tradeType, $offerId = null) {
        return $this->travelRepo->startTradeTravel($fromSettlementId, $toSettlementId, $resources, $tradeType, $offerId);
    }

    public function getTravelingArmies($settlementId) {
        return $this->travelRepo->getTravelingArmies($settlementId);
    }

    public function getTravelingTrades($settlementId) {
        return $this->travelRepo->getTravelingTrades($settlementId);
    }

    public function processArrivals() {
        return $this->travelRepo->processArrivals();
    }

    public function getAllTravelingArmies() {
        return $this->travelRepo->getAllTravelingArmies();
    }

    public function getAllTravelingTrades() {
        return $this->travelRepo->getAllTravelingTrades();
    }

    public function getMilitaryUnitConfig() {
        return $this->travelRepo->getMilitaryUnitConfig();
    }

    public function updateMilitaryUnitConfig($unitType, $level, $field, $value) {
        return $this->travelRepo->updateMilitaryUnitConfig($unitType, $level, $field, $value);
    }
}

?>