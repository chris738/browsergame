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