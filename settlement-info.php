<?php
require_once 'php/database.php';
require_once 'php/emoji-config.php';

// Get settlement ID and current player's settlement ID from URL parameters
$settlementId = $_GET['settlementId'] ?? null;
$currentSettlementId = $_GET['currentSettlementId'] ?? null;

if (!$settlementId) {
    header('Location: map.php');
    exit;
}

// Initialize database and fetch settlement information
$database = new Database();
$settlementInfo = null;
$tradeHistory = [];
$currentPlayerId = null;
$targetPlayerId = null;

try {
    if ($database->isConnected()) {
        // Get all settlements to find settlement info and coordinates
        $allSettlements = $database->getAllSettlements();
        
        foreach ($allSettlements as $settlement) {
            if ($settlement['settlementId'] == $settlementId) {
                $settlementInfo = $settlement;
                $targetPlayerId = $settlement['playerId'] ?? null;
                break;
            }
        }
        
        if ($settlementInfo) {
            // Fix Issue #92 part 2: Improve ownership detection  
            // For single-player scenarios or when currentSettlementId is not provided,
            // assume we're viewing our own settlement if it belongs to the main player (ID 1)
            $currentPlayerId = 1; // Default to main player in demo mode
            
            if ($currentSettlementId) {
                // If currentSettlementId is explicitly provided, try to get that player's ID
                foreach ($allSettlements as $settlement) {
                    if ($settlement['settlementId'] == $currentSettlementId) {
                        $currentPlayerId = $settlement['playerId'] ?? 1;
                        break;
                    }
                }
            }
            
            // Fix Issue #92 part 3: Get coordinates from Map table
            $mapData = $database->getMap();
            $coordinatesFound = false;
            foreach ($mapData as $mapEntry) {
                if ($mapEntry['settlementId'] == $settlementId) {
                    $settlementInfo['xCoordinate'] = $mapEntry['xCoordinate'];
                    $settlementInfo['yCoordinate'] = $mapEntry['yCoordinate'];
                    $coordinatesFound = true;
                    break;
                }
            }
            if (!$coordinatesFound) {
                $settlementInfo['xCoordinate'] = '';
                $settlementInfo['yCoordinate'] = '';
            }
        }
        
        // Get trade history between the current player and target player
        if ($currentPlayerId && $targetPlayerId && $currentPlayerId != $targetPlayerId) {
            $tradeHistory = $database->getTradeHistoryBetweenPlayers($currentPlayerId, $targetPlayerId);
        }
    }
} catch (Exception $e) {
    error_log("Settlement info fetch failed: " . $e->getMessage());
}

// If settlement not found, redirect back to map
if (!$settlementInfo) {
    header('Location: map.php');
    exit;
}

// Determine if this is the current player's own settlement
$isOwnSettlement = ($currentPlayerId && $targetPlayerId && $currentPlayerId == $targetPlayerId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Info - <?= htmlspecialchars($settlementInfo['name']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <main class="main-content">
        <div class="settlement-info-container">
            <div class="settlement-info-header">
                <h2><?= EmojiConfig::getUIEmoji('settlement') ?> Settlement Information</h2>
                <button class="back-button" onclick="history.back()">← Back to Map</button>
            </div>
            
            <div class="settlement-info-content">
                <div class="settlement-basic-info">
                    <h3><?= htmlspecialchars($settlementInfo['name']) ?></h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label"><?= EmojiConfig::getUIEmoji('player') ?> Owner:</span>
                            <span class="info-value"><?= htmlspecialchars($settlementInfo['playerName'] ?? 'Unknown') ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><?= EmojiConfig::getUIEmoji('map') ?> Location:</span>
                            <span class="info-value">(<?= $settlementInfo['xCoordinate'] ?>, <?= $settlementInfo['yCoordinate'] ?>)</span>
                        </div>
                        <?php if ($isOwnSettlement): ?>
                        <div class="info-item">
                            <span class="info-label"><?= EmojiConfig::getUIEmoji('status') ?> Status:</span>
                            <span class="info-value own-settlement">Your Settlement</span>
                        </div>
                        <?php else: ?>
                        <div class="info-item">
                            <span class="info-label"><?= EmojiConfig::getUIEmoji('status') ?> Status:</span>
                            <span class="info-value foreign-settlement">Foreign Settlement</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$isOwnSettlement): ?>
                <div class="trade-history-section">
                    <h3><?= EmojiConfig::getUIEmoji('trade') ?> Trade History</h3>
                    <?php if (empty($tradeHistory)): ?>
                        <p class="no-trades">No trade history with this settlement.</p>
                    <?php else: ?>
                        <div class="trade-history-list">
                            <?php foreach ($tradeHistory as $trade): ?>
                            <div class="trade-item">
                                <div class="trade-date">
                                    <?= EmojiConfig::getUIEmoji('time') ?> <?= date('Y-m-d H:i', strtotime($trade['completedAt'])) ?>
                                </div>
                                <div class="trade-details">
                                    <span class="trade-resources">
                                        <?php if ($trade['wood'] > 0): ?>
                                            <?= $trade['wood'] ?> <?= EmojiConfig::getResourceEmoji('wood') ?>
                                        <?php endif; ?>
                                        <?php if ($trade['stone'] > 0): ?>
                                            <?= $trade['stone'] ?> <?= EmojiConfig::getResourceEmoji('stone') ?>
                                        <?php endif; ?>
                                        <?php if ($trade['ore'] > 0): ?>
                                            <?= $trade['ore'] ?> <?= EmojiConfig::getResourceEmoji('ore') ?>
                                        <?php endif; ?>
                                        <?php if ($trade['gold'] > 0): ?>
                                            <?= $trade['gold'] ?> <?= EmojiConfig::getResourceEmoji('gold') ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="trade-actions">
                    <h3><?= EmojiConfig::getUIEmoji('trade') ?> Trading</h3>
                    <p>Want to trade with this settlement?</p>
                    <a href="market.php?settlementId=<?= $currentSettlementId ?>" class="trade-button">
                        <?= EmojiConfig::getUIEmoji('trade') ?> Visit Market
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($isOwnSettlement): ?>
                <div class="own-settlement-actions">
                    <h3><?= EmojiConfig::getUIEmoji('manage') ?> Manage Settlement</h3>
                    <a href="index.php?settlementId=<?= $settlementId ?>" class="manage-button">
                        <?= EmojiConfig::getUIEmoji('manage') ?> Manage Buildings
                    </a>
                    <a href="market.php?settlementId=<?= $settlementId ?>" class="trade-button">
                        <?= EmojiConfig::getUIEmoji('trade') ?> Visit Market
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Initialize data loading for settlement-info page
        const currentSettlementId = <?= json_encode($settlementId) ?>;
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial data
            fetchResources(currentSettlementId);
            fetchPlayerInfo(currentSettlementId);
            getRegen(currentSettlementId);
            getSettlementName(currentSettlementId);
            
            // Set up periodic updates for resources
            setInterval(() => {
                fetchResources(currentSettlementId);
                getRegen(currentSettlementId);
            }, 1000);
        });
    </script>
</body>
</html>