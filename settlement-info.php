<?php
require_once 'php/database.php';
require_once 'php/emoji-config.php';

// Get settlement ID and current player's settlement ID from URL parameters
$settlementId = $_GET['settlementId'] ?? null;
$currentSettlementId = $_GET['currentSettlementId'] ?? $settlementId; // Default to same settlement for single-player

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
        // Get settlement information with coordinates
        $allSettlements = $database->getAllSettlements();
        
        // Find the target settlement
        foreach ($allSettlements as $settlement) {
            if ($settlement['settlementId'] == $settlementId) {
                $settlementInfo = $settlement;
                $targetPlayerId = $settlement['playerId'] ?? null;
                break;
            }
        }
        
        // For single-player game, assume current player is player 1
        $currentPlayerId = 1;
        
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
    <link rel="stylesheet" href="css/main.css">
    <script src="js/theme-switcher.js"></script>
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
        // Declare settlementId only if not already declared
        if (typeof settlementId === 'undefined') {
            const settlementId = <?= json_encode($settlementId) ?>;
        }
    </script>
</body>
</html>