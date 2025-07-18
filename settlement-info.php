<?php
require_once 'php/database.php';
require_once 'php/emoji-config.php';

// Get the settlement ID from URL parameter
$settlementId = $_GET['settlementId'] ?? null;
$currentSettlementId = $_GET['currentSettlementId'] ?? 1; // The player's current settlement context

if (!$settlementId) {
    header('Location: map.php?settlementId=' . $currentSettlementId);
    exit;
}

// Get public settlement info
$database = new Database();
$settlementInfo = null;
$error = null;

try {
    if ($database->isConnected()) {
        // Call the public settlement info function directly instead of including backend.php
        $playerName = $database->getPlayerNameFromSettlement($settlementId);
        $playerId = $database->getPlayerIdFromSettlement($settlementId);
        $settlementName = $database->getSettlementName($settlementId);
        
        // Get all settlements to find coordinates for this settlement
        $allSettlements = $database->getAllSettlements();
        $coordinates = ['x' => 0, 'y' => 0];
        foreach ($allSettlements as $settlement) {
            if ($settlement['settlementId'] == $settlementId) {
                $coordinates = [
                    'x' => $settlement['xCoordinate'] ?? 0,
                    'y' => $settlement['yCoordinate'] ?? 0
                ];
                break;
            }
        }
        
        // Get market building info to determine if trading is possible
        $marketBuilding = $database->getBuilding($settlementId, 'Markt');
        $hasMarket = $marketBuilding && $marketBuilding['currentLevel'] > 0;
        $marketLevel = $marketBuilding ? $marketBuilding['currentLevel'] : 0;
        
        $settlementInfo = [
            'settlementId' => $settlementId,
            'settlementName' => $settlementName['SettlementName'] ?? 'Unknown Settlement',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'xCoordinate' => $coordinates['x'],
            'yCoordinate' => $coordinates['y'],
            'publicStats' => [
                'hasMarket' => $hasMarket,
                'marketLevel' => $marketLevel,
            ]
        ];
    } else {
        throw new Exception("Database not connected");
    }
} catch (Exception $e) {
    $error = "Unable to load settlement information: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Info - <?= htmlspecialchars($settlementInfo['settlementName'] ?? 'Unknown') ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php 
    // Set navigation context to the current player's settlement, not the viewed settlement
    $originalSettlementId = $settlementId; // Preserve the original settlement ID
    $_GET['settlementId'] = $currentSettlementId;
    include 'php/navigation.php'; 
    $settlementId = $originalSettlementId; // Restore the original settlement ID
    ?>
    
    <main class="main-content">
        <div class="settlement-info-container">
            <div class="settlement-info-header">
                <h2>🏘️ Settlement Information</h2>
                <a href="map.php?settlementId=<?= htmlspecialchars($currentSettlementId) ?>" class="back-to-map-btn">
                    ← Back to Map
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php elseif ($settlementInfo): ?>
                <div class="settlement-info-content">
                    <div class="settlement-basic-info">
                        <h3>📋 Basic Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Settlement Name:</label>
                                <span class="settlement-name"><?= htmlspecialchars($settlementInfo['settlementName']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Owner:</label>
                                <span class="player-name">👤 <?= htmlspecialchars($settlementInfo['playerName']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Coordinates:</label>
                                <span class="coordinates">📍 (<?= $settlementInfo['xCoordinate'] ?>, <?= $settlementInfo['yCoordinate'] ?>)</span>
                            </div>
                        </div>
                    </div>

                    <div class="settlement-trading-info">
                        <h3>🏪 Trading Information</h3>
                        <div class="trading-stats">
                            <?php if ($settlementInfo['publicStats']['hasMarket']): ?>
                                <div class="trading-available">
                                    <p>✅ <strong>Trading Available</strong></p>
                                    <p>Market Level: <?= $settlementInfo['publicStats']['marketLevel'] ?></p>
                                    <div class="trade-actions">
                                        <a href="market.php?settlementId=<?= htmlspecialchars($currentSettlementId) ?>&targetSettlement=<?= htmlspecialchars($settlementId) ?>" 
                                           class="trade-btn">
                                            ⚖️ Open Trade with this Settlement
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="trading-unavailable">
                                    <p>❌ <strong>Trading Not Available</strong></p>
                                    <p>This settlement doesn't have a market or it's not operational.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="settlement-additional-info">
                        <h3>📊 Additional Information</h3>
                        <div class="additional-stats">
                            <p><em>🔒 Detailed building information and resources are private to the settlement owner.</em></p>
                            <p>🤝 Trade history and diplomatic relations will be shown here when available.</p>
                        </div>
                    </div>

                    <div class="settlement-actions">
                        <h3>⚡ Quick Actions</h3>
                        <div class="action-buttons">
                            <a href="map.php?settlementId=<?= htmlspecialchars($currentSettlementId) ?>" class="action-btn">
                                🗺️ Return to Map
                            </a>
                            <?php if ($settlementInfo['publicStats']['hasMarket']): ?>
                                <a href="market.php?settlementId=<?= htmlspecialchars($currentSettlementId) ?>&targetSettlement=<?= htmlspecialchars($settlementId) ?>" 
                                   class="action-btn trade-btn">
                                    🛒 Start Trading
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <style>
        .settlement-info-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 8px;
        }

        .settlement-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--card-border);
        }

        .settlement-info-header h2 {
            margin: 0;
            color: var(--text-color);
        }

        .back-to-map-btn {
            background: var(--button-bg);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        .back-to-map-btn:hover {
            background: var(--button-hover);
        }

        .settlement-info-content > div {
            margin-bottom: 30px;
            padding: 20px;
            background: var(--resource-bg);
            border-radius: 6px;
            border: 1px solid var(--card-border);
        }

        .settlement-info-content h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-item label {
            font-weight: bold;
            color: var(--text-color);
            opacity: 0.8;
        }

        .info-item span {
            font-size: 1.1em;
            color: var(--text-color);
        }

        .settlement-name {
            color: #2980b9;
            font-weight: bold;
        }

        .player-name {
            color: #27ae60;
            font-weight: bold;
        }

        .coordinates {
            color: #8e44ad;
            font-weight: bold;
        }

        .trading-available {
            color: #27ae60;
        }

        .trading-unavailable {
            color: #e74c3c;
        }

        .trade-actions, .action-buttons {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .trade-btn, .action-btn {
            background: var(--button-bg);
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
            display: inline-block;
        }

        .trade-btn:hover, .action-btn:hover {
            background: var(--button-hover);
        }

        .trade-btn {
            background: #27ae60;
        }

        .trade-btn:hover {
            background: #219a52;
        }

        .error-message {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }

        .additional-stats {
            font-style: italic;
            color: var(--text-color);
            opacity: 0.8;
        }

        .additional-stats p {
            margin: 10px 0;
        }
    </style>
</body>
</html>