<?php
require_once 'database.php';

header('Content-Type: application/json');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$database = new Database();

// Remove the early database connection check - let repositories handle fallback data
// if (!$database->isConnected()) {
//     echo json_encode(['success' => false, 'message' => 'Database connection failed']);
//     exit;
// }

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$settlementId = $_GET['settlementId'] ?? null;

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'getAttackableSettlements':
                    if (!$settlementId) {
                        echo json_encode(['success' => false, 'message' => 'Settlement ID required']);
                        exit;
                    }
                    
                    $settlements = $database->getAttackableSettlements($settlementId);
                    echo json_encode(['success' => true, 'settlements' => $settlements]);
                    break;
                    
                case 'getMilitaryPower':
                    if (!$settlementId) {
                        echo json_encode(['success' => false, 'message' => 'Settlement ID required']);
                        exit;
                    }
                    
                    $power = $database->getSettlementMilitaryPower($settlementId);
                    echo json_encode(['success' => true, 'power' => $power]);
                    break;
                    
                case 'getBattleHistory':
                    if (!$settlementId) {
                        echo json_encode(['success' => false, 'message' => 'Settlement ID required']);
                        exit;
                    }
                    
                    $limit = $_GET['limit'] ?? 10;
                    $battles = $database->getRecentBattles($settlementId, $limit);
                    echo json_encode(['success' => true, 'battles' => $battles]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'attack':
                    $input = json_decode(file_get_contents('php://input'), true);
                    
                    $attackerSettlementId = $input['attackerSettlementId'] ?? null;
                    $defenderSettlementId = $input['defenderSettlementId'] ?? null;
                    $units = $input['units'] ?? [];
                    
                    if (!$attackerSettlementId || !$defenderSettlementId) {
                        echo json_encode(['success' => false, 'message' => 'Both attacker and defender settlement IDs required']);
                        exit;
                    }
                    
                    if ($attackerSettlementId == $defenderSettlementId) {
                        echo json_encode(['success' => false, 'message' => 'Cannot attack your own settlement']);
                        exit;
                    }
                    
                    // Validate units array
                    $validUnits = ['guards', 'soldiers', 'archers', 'cavalry'];
                    $attackUnits = [];
                    foreach ($validUnits as $unitType) {
                        $attackUnits[$unitType] = isset($units[$unitType]) ? max(0, (int)$units[$unitType]) : 0;
                    }
                    
                    // Check if any units are being sent
                    $totalUnits = array_sum($attackUnits);
                    if ($totalUnits == 0) {
                        echo json_encode(['success' => false, 'message' => 'No units selected for attack']);
                        exit;
                    }
                    
                    $result = $database->attackSettlement($attackerSettlementId, $defenderSettlementId, $attackUnits);
                    echo json_encode($result);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Battle backend error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

?>