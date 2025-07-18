<?php
require_once 'php/database.php';

// Get the current settlement ID from URL parameter
$currentSettlementId = $_GET['settlementId'] ?? 1;

// Initialize database and fetch real settlement data
$database = new Database();
$mapData = [];
$currentPlayerId = null;

try {
    // Check if database is connected
    if ($database->isConnected()) {
        // Get the current player ID from the selected settlement
        $currentSettlement = $database->getAllSettlements();
        foreach ($currentSettlement as $settlement) {
            if ($settlement['settlementId'] == $currentSettlementId) {
                $currentPlayerId = $settlement['playerId'] ?? null;
                break;
            }
        }
        
        // Get all settlements with their coordinates and player information
        $allSettlements = $database->getAllSettlements();
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
    } else {
        throw new Exception("Database not connected");
    }
} catch (Exception $e) {
    // Fallback to demo settlement when database fails - showing only the current settlement
    error_log("Map data fetch failed: " . $e->getMessage());
    $currentPlayerId = 1; // Demo player ID
    $mapData = [
        [
            'settlementId' => $currentSettlementId,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Test Settlement',
            'playerId' => $currentPlayerId,
            'playerName' => 'Player'
        ]
    ];
}

// If no settlements found, show at least the current settlement
if (empty($mapData)) {
    $currentPlayerId = 1;
    $mapData = [
        [
            'settlementId' => $currentSettlementId,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Fallback Settlement',
            'playerId' => $currentPlayerId,
            'playerName' => 'Player'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map - Settlement Building</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <main class="main-content">
        <h2>Map View</h2>
        <p>Here you can see nearby settlements:</p>
        
        <div class="map-container">
            <div class="grid">
                <?php foreach ($mapData as $settlement): ?>
                    <?php 
                        // Determine settlement type for CSS class
                        $settlementClass = 'settlement';
                        if ($settlement['settlementId'] == $currentSettlementId) {
                            $settlementClass .= ' selected-settlement'; // White for selected settlement
                        } elseif ($settlement['playerId'] == $currentPlayerId) {
                            $settlementClass .= ' own-settlement'; // Black for own settlements
                        } else {
                            $settlementClass .= ' other-settlement'; // Brown for other settlements
                        }
                    ?>
                    <div 
                        class="<?= $settlementClass ?>" 
                        style="left: <?= ($settlement['xCoordinate'] + 10) * 20 ?>px; 
                               top: <?= (10 - $settlement['yCoordinate']) * 20 ?>px;"
                        title="<?= htmlspecialchars($settlement['name']) ?> (<?= $settlement['xCoordinate'] ?>, <?= $settlement['yCoordinate'] ?>) - Player: <?= htmlspecialchars($settlement['playerName']) ?>"
                        onclick="window.location.href='index.php?settlementId=<?= $settlement['settlementId'] ?>'">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>