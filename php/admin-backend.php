<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json; charset=utf-8');

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$database = new Database();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'players':
                $players = $database->getAllPlayers();
                echo json_encode(['players' => $players]);
                break;
                
            case 'settlements':
                $settlements = $database->getAllSettlements();
                echo json_encode(['settlements' => $settlements]);
                break;
                
            case 'queues':
                $queues = $database->getAllQueues();
                echo json_encode(['queues' => $queues]);
                break;
                
            case 'stats':
                $stats = [
                    'playerCount' => $database->getPlayerCount(),
                    'settlementCount' => $database->getSettlementCount(),
                    'activeQueues' => $database->getActiveQueuesCount()
                ];
                echo json_encode(['stats' => $stats]);
                break;
                
            case 'buildingConfigs':
                $configs = $database->getAllBuildingConfigs();
                echo json_encode(['buildingConfigs' => $configs]);
                break;
                
            case 'buildingConfig':
                $buildingType = $_GET['buildingType'] ?? '';
                $level = $_GET['level'] ?? 0;
                
                if (empty($buildingType) || $level <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Building type and level are required']);
                    break;
                }
                
                $config = $database->getBuildingConfig($buildingType, $level);
                if ($config) {
                    echo json_encode(['buildingConfig' => $config]);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Building config not found']);
                }
                break;
                
            case 'buildingTypes':
                $buildingTypes = $database->getDistinctBuildingTypes();
                echo json_encode(['buildingTypes' => $buildingTypes]);
                break;
                
            // Market/Trade related actions
            case 'getActiveTrades':
                $activeTrades = $database->getActiveTradesForAdmin();
                // Transform data for frontend
                $formattedTrades = array_map(function($trade) {
                    return [
                        'id' => $trade['id'],
                        'fromPlayer' => $trade['fromPlayer'],
                        'type' => $trade['type'],
                        'typeName' => [
                            'resource_trade' => 'Resource Trade',
                            'resource_sell' => 'Resource Sale', 
                            'resource_buy' => 'Resource Purchase'
                        ][$trade['type']] ?? 'Unknown',
                        'offering' => [
                            'wood' => $trade['offerWood'],
                            'stone' => $trade['offerStone'],
                            'ore' => $trade['offerOre'],
                            'gold' => $trade['offerGold']
                        ],
                        'requesting' => [
                            'wood' => $trade['requestWood'],
                            'stone' => $trade['requestStone'],
                            'ore' => $trade['requestOre'],
                            'gold' => $trade['requestGold']
                        ],
                        'currentTrades' => $trade['currentTrades'],
                        'maxTrades' => $trade['maxTrades'],
                        'createdAt' => $trade['createdAt']
                    ];
                }, $activeTrades);
                echo json_encode(['success' => true, 'trades' => $formattedTrades]);
                break;
                
            case 'getTradeHistory':
                $limit = $_GET['limit'] ?? 20;
                $history = $database->getTradeHistoryForAdmin($limit);
                // Transform data for frontend
                $formattedHistory = array_map(function($trade) {
                    return [
                        'id' => $trade['id'],
                        'fromPlayer' => $trade['fromPlayer'],
                        'toPlayer' => $trade['toPlayer'],
                        'resources' => [
                            'wood' => $trade['wood'],
                            'stone' => $trade['stone'],
                            'ore' => $trade['ore'],
                            'gold' => $trade['gold']
                        ],
                        'completedAt' => $trade['completedAt']
                    ];
                }, $history);
                echo json_encode(['success' => true, 'history' => $formattedHistory]);
                break;
                
            case 'getTradeAnalytics':
                $analytics = $database->getTradeAnalytics();
                echo json_encode(['success' => true, 'analytics' => $analytics]);
                break;
                
            case 'getTravelConfig':
                $tradeSpeed = $database->getTravelConfig('trade');
                $militarySpeed = $database->getTravelConfig('military');
                echo json_encode([
                    'success' => true, 
                    'tradeSpeed' => $tradeSpeed, 
                    'militarySpeed' => $militarySpeed
                ]);
                break;
                
            case 'getMilitaryUnitConfig':
                $units = $database->getMilitaryUnitConfig();
                echo json_encode(['success' => true, 'units' => $units]);
                break;
                
            case 'getAllTravelingArmies':
                $armies = $database->getAllTravelingArmies();
                echo json_encode(['success' => true, 'armies' => $armies]);
                break;
                
            case 'getAllTravelingTrades':
                $trades = $database->getAllTravelingTrades();
                echo json_encode(['success' => true, 'trades' => $trades]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'createPlayer':
                $name = $input['name'] ?? '';
                $gold = $input['gold'] ?? 500;
                
                if (empty($name)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Player name is required']);
                    break;
                }
                
                $success = $database->createPlayer($name, $gold);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Player created successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create player']);
                }
                break;
                
            case 'updatePlayerStats':
                $playerId = $input['playerId'] ?? 0;
                $points = $input['points'] ?? 0;
                $gold = $input['gold'] ?? 0;
                
                if ($playerId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid player ID']);
                    break;
                }
                
                $success = $database->updatePlayerStats($playerId, $points, $gold);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Player stats updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update player stats']);
                }
                break;
                
            case 'updateSettlementResources':
                $settlementId = $input['settlementId'] ?? 0;
                $wood = $input['wood'] ?? 0;
                $stone = $input['stone'] ?? 0;
                $ore = $input['ore'] ?? 0;
                
                if ($settlementId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid settlement ID']);
                    break;
                }
                
                $success = $database->updateSettlementResources($settlementId, $wood, $stone, $ore);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Settlement resources updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update settlement resources']);
                }
                break;
                
            case 'clearAllQueues':
                $success = $database->clearAllQueues();
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'All building queues cleared successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to clear building queues']);
                }
                break;
                
            case 'createBuildingConfig':
                $buildingType = $input['buildingType'] ?? '';
                $level = $input['level'] ?? 0;
                $costWood = $input['costWood'] ?? 0;
                $costStone = $input['costStone'] ?? 0;
                $costOre = $input['costOre'] ?? 0;
                $settlers = $input['settlers'] ?? 0;
                $productionRate = $input['productionRate'] ?? 0;
                $buildTime = $input['buildTime'] ?? 30;
                
                if (empty($buildingType) || $level <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Building type and level are required']);
                    break;
                }
                
                $success = $database->createBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Building config created successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create building config (may already exist)']);
                }
                break;
                
            case 'updateBuildingConfig':
                $buildingType = $input['buildingType'] ?? '';
                $level = $input['level'] ?? 0;
                $costWood = $input['costWood'] ?? 0;
                $costStone = $input['costStone'] ?? 0;
                $costOre = $input['costOre'] ?? 0;
                $settlers = $input['settlers'] ?? 0;
                $productionRate = $input['productionRate'] ?? 0;
                $buildTime = $input['buildTime'] ?? 30;
                
                if (empty($buildingType) || $level <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Building type and level are required']);
                    break;
                }
                
                $success = $database->updateBuildingConfig($buildingType, $level, $costWood, $costStone, $costOre, $settlers, $productionRate, $buildTime);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Building config updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update building config']);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'deletePlayer':
                $playerId = $input['playerId'] ?? 0;
                
                if ($playerId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid player ID']);
                    break;
                }
                
                $success = $database->deletePlayer($playerId);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Player deleted successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete player']);
                }
                break;
                
            case 'deleteQueue':
                $queueId = $input['queueId'] ?? 0;
                
                if ($queueId <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid queue ID']);
                    break;
                }
                
                $success = $database->deleteQueue($queueId);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Queue entry deleted successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete queue entry']);
                }
                break;
                
            case 'deleteBuildingConfig':
                $buildingType = $input['buildingType'] ?? '';
                $level = $input['level'] ?? 0;
                
                if (empty($buildingType) || $level <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Building type and level are required']);
                    break;
                }
                
                $success = $database->deleteBuildingConfig($buildingType, $level);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Building config deleted successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to delete building config']);
                }
                break;
                
            case 'updateTravelConfig':
                $travelType = $input['travelType'] ?? '';
                $baseSpeed = $input['baseSpeed'] ?? 0;
                
                if (empty($travelType) || $baseSpeed <= 0) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Travel type and base speed are required']);
                    break;
                }
                
                $success = $database->updateTravelConfig($travelType, $baseSpeed);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Travel config updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update travel config']);
                }
                break;
                
            case 'updateMilitaryUnitConfig':
                $unitType = $input['unitType'] ?? '';
                $level = $input['level'] ?? 0;
                $field = $input['field'] ?? '';
                $value = $input['value'] ?? 0;
                
                if (empty($unitType) || $level <= 0 || empty($field)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Unit type, level, field and value are required']);
                    break;
                }
                
                $success = $database->updateMilitaryUnitConfig($unitType, $level, $field, $value);
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Military unit config updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update military unit config']);
                }
                break;
                
            case 'processArrivals':
                $result = $database->processArrivals();
                echo json_encode(['success' => true, 'processed' => $result['processed']]);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>