<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market - Trading</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
    <script src="js/market.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <!-- Market Access Check -->
    <section class="market-access" id="marketAccess">
        <div class="access-info">
            <h3><?= EmojiConfig::getUIEmoji('market') ?> Market</h3>
            <p>Build a Market to start trading with other players!</p>
            <p>Market Level: <span id="marketLevel">0</span></p>
        </div>
    </section>

    <!-- Market Interface (hidden until market is built) -->
    <div id="marketInterface" class="market-interface" style="display: none;">
        
        <!-- Create Trade Offer Section -->
        <section class="create-offer">
            <h3>Create Trade Offer</h3>
            <div class="offer-form">
                <div class="offer-type">
                    <label>Trade Type:</label>
                    <select id="offerType">
                        <option value="resource_trade">Resource Trade</option>
                        <option value="resource_sell">Sell Resources for Gold</option>
                        <option value="resource_buy">Buy Resources with Gold</option>
                    </select>
                </div>
                
                <div class="offer-resources">
                    <h4>What you offer:</h4>
                    <div class="resource-inputs">
                        <label><?= EmojiConfig::getResourceEmoji('wood') ?> Wood: <input type="number" id="offerWood" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('stone') ?> Stone: <input type="number" id="offerStone" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('ore') ?> Ore: <input type="number" id="offerOre" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('gold') ?> Gold: <input type="number" id="offerGold" min="0" value="0"></label>
                    </div>
                </div>
                
                <div class="request-resources">
                    <h4>What you want in return:</h4>
                    <div class="resource-inputs">
                        <label><?= EmojiConfig::getResourceEmoji('wood') ?> Wood: <input type="number" id="requestWood" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('stone') ?> Stone: <input type="number" id="requestStone" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('ore') ?> Ore: <input type="number" id="requestOre" min="0" value="0"></label>
                        <label><?= EmojiConfig::getResourceEmoji('gold') ?> Gold: <input type="number" id="requestGold" min="0" value="0"></label>
                    </div>
                </div>
                
                <div class="offer-settings">
                    <label>Max Trades: <input type="number" id="maxTrades" min="1" value="1" max="10"></label>
                    <button onclick="createTradeOffer()" class="create-offer-btn">Create Offer</button>
                </div>
            </div>
        </section>

        <!-- Available Trade Offers -->
        <section class="available-offers">
            <h3>Available Trade Offers</h3>
            <div class="offers-filters">
                <select id="offerFilter">
                    <option value="all">All Offers</option>
                    <option value="resource_trade">Resource Trades</option>
                    <option value="resource_sell">Resource Sales</option>
                    <option value="resource_buy">Resource Purchases</option>
                </select>
                <button onclick="refreshOffers()"><?= EmojiConfig::getUIEmoji('refresh') ?> Refresh</button>
            </div>
            <div id="offersList" class="offers-list">
                <!-- Trade offers will be loaded here -->
            </div>
        </section>

        <!-- My Active Offers -->
        <section class="my-offers">
            <h3>My Active Offers</h3>
            <div id="myOffersList" class="offers-list">
                <!-- My offers will be loaded here -->
            </div>
        </section>

        <!-- Trade History -->
        <section class="trade-history">
            <h3>Recent Trade History</h3>
            <div id="tradeHistory" class="trade-history-list">
                <!-- Trade history will be loaded here -->
            </div>
        </section>
    </div>

</body>
</html>