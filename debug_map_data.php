<?php
require_once 'php/database.php';

// Get the current settlement ID from URL parameter
$currentSettlementId = $_GET['settlementId'] ?? 1;

// Initialize database and fetch real settlement data
$database = new Database();
$mapData = [];
$currentPlayerId = null;

try {
    // Get all settlements - the repository will handle database fallback  
    $allSettlements = $database->getAllSettlements();
    
    echo "All settlements fetched: " . count($allSettlements) . "\n";
    print_r($allSettlements);
    
    // Get the current player ID from the selected settlement
    foreach ($allSettlements as $settlement) {
        if ($settlement['settlementId'] == $currentSettlementId) {
            $currentPlayerId = $settlement['playerId'] ?? null;
            break;
        }
    }
    
    echo "Current player ID: $currentPlayerId\n";
    
    // Process settlements with their coordinates and player information
    foreach ($allSettlements as $settlement) {
        if (isset($settlement['xCoordinate']) && isset($settlement['yCoordinate'])) {
            $mapData[] = [
                'settlementId' => $settlement['settlementId'],
                'xCoordinate' => $settlement['xCoordinate'],
                'yCoordinate' => $settlement['yCoordinate'],
                'name' => $settlement['name'],
                'playerId' => $settlement['playerId'] ?? null,
                'playerName' => $settlement['playerName'] ?? 'Unknown'
            ];
        }
    }
    
    echo "Map data after processing: " . count($mapData) . "\n";
    print_r($mapData);
    
} catch (Exception $e) {
    echo "Exception caught: " . $e->getMessage() . "\n";
}

// Check empty condition
echo "Is mapData empty? " . (empty($mapData) ? "YES" : "NO") . "\n";
?>