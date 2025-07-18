<?php
    require_once 'php/database.php';
    
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
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
    <script src="js/market.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <!-- Market Access Check -->
    <section class="market-access" id="marketAccess">
        <div class="access-info">
            <h3>⚖️ Market</h3>
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
                        <label>🪵 Wood: <input type="number" id="offerWood" min="0" value="0"></label>
                        <label>🧱 Stone: <input type="number" id="offerStone" min="0" value="0"></label>
                        <label>🪨 Ore: <input type="number" id="offerOre" min="0" value="0"></label>
                        <label>💰 Gold: <input type="number" id="offerGold" min="0" value="0"></label>
                    </div>
                </div>
                
                <div class="request-resources">
                    <h4>What you want in return:</h4>
                    <div class="resource-inputs">
                        <label>🪵 Wood: <input type="number" id="requestWood" min="0" value="0"></label>
                        <label>🧱 Stone: <input type="number" id="requestStone" min="0" value="0"></label>
                        <label>🪨 Ore: <input type="number" id="requestOre" min="0" value="0"></label>
                        <label>💰 Gold: <input type="number" id="requestGold" min="0" value="0"></label>
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
                <button onclick="refreshOffers()">🔄 Refresh</button>
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

    <style>
    .market-interface {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .create-offer, .available-offers, .my-offers, .trade-history {
        background: var(--bg-secondary);
        margin: 20px 0;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .offer-form {
        display: grid;
        gap: 20px;
    }

    .resource-inputs {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .resource-inputs label {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .resource-inputs input {
        flex: 1;
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .create-offer-btn {
        background: var(--accent-color);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
    }

    .create-offer-btn:hover {
        background: var(--accent-hover);
    }

    .offer-item {
        background: var(--bg-primary);
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 15px;
        margin: 10px 0;
    }

    .offer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .offer-type-badge {
        background: var(--accent-color);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }

    .offer-resources {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 20px;
        align-items: center;
    }

    .resources-offered, .resources-wanted {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .resource-amount {
        background: var(--bg-secondary);
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 14px;
    }

    .trade-arrow {
        font-size: 24px;
        color: var(--accent-color);
    }

    .accept-offer-btn {
        background: var(--success-color, #28a745);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }

    .accept-offer-btn:hover {
        background: var(--success-hover, #218838);
    }

    .cancel-offer-btn {
        background: var(--danger-color, #dc3545);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
    }

    .cancel-offer-btn:hover {
        background: var(--danger-hover, #c82333);
    }

    .offers-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .offers-filters select {
        padding: 8px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        background: var(--bg-primary);
        color: var(--text-primary);
    }

    .market-access {
        text-align: center;
        padding: 40px 20px;
        background: var(--bg-secondary);
        margin: 20px;
        border-radius: 8px;
    }

    .access-info h3 {
        color: var(--accent-color);
        margin-bottom: 15px;
    }
    </style>
</body>
</html>