<?php
session_start();
require_once 'database.php';
require_once 'emoji-config.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../admin.php');
    exit;
}

$database = new Database();

// Handle POST requests for trade management
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'cancelTrade':
            $tradeId = $_POST['tradeId'] ?? 0;
            if ($tradeId > 0 && $database->isConnected()) {
                $success = $database->cancelTradeOffer($tradeId);
                $message = $success ? "Trade offer cancelled successfully." : "Failed to cancel trade offer.";
            } else {
                $message = "Database not available or invalid trade ID.";
            }
            break;
            
        case 'clearAllTrades':
            if ($database->isConnected()) {
                $success = $database->clearAllTradeOffers();
                $message = $success ? "All trade offers cleared successfully." : "Failed to clear all trade offers.";
            } else {
                $message = "Database not available.";
            }
            break;
    }
}

// Get trade statistics
try {
    if ($database->isConnected()) {
        $totalTrades = $database->getTotalTradeOffers();
        $activeTrades = $database->getActiveTradeOffers();
        $completedTrades = $database->getCompletedTradesCount();
    } else {
        // Use mock data when database is not available
        $totalTrades = 15;
        $activeTrades = 8;
        $completedTrades = 23;
        $databaseError = "Database connection not available (showing demo data)";
    }
} catch (Exception $e) {
    $totalTrades = 15;
    $activeTrades = 8;
    $completedTrades = 23;
    $databaseError = "Database connection failed (using demo data): " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Management - Admin Panel</title>
    <link rel="stylesheet" href="../css/main.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../js/theme-switcher.js"></script>
    <script src="../js/emoji-config.js"></script>
    <script src="../js/admin.js" defer></script>
</head>
<body>
    <?php include 'admin-navigation.php'; ?>

    <?php if (isset($message)): ?>
        <div class="success-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($databaseError)): ?>
        <div class="error-message"><?= htmlspecialchars($databaseError) ?></div>
    <?php endif; ?>

    <div class="admin-container">
        <!-- Market Statistics -->
        <section class="admin-section">
            <h2><?= EmojiConfig::getUIEmoji('market') ?> Market Trade Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Trade Offers</h3>
                    <div class="stat-number"><?= $totalTrades ?? 'N/A' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Trades</h3>
                    <div class="stat-number"><?= $activeTrades ?? 'N/A' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed Trades</h3>
                    <div class="stat-number"><?= $completedTrades ?? 'N/A' ?></div>
                </div>
            </div>
        </section>

        <!-- Trade Management Controls -->
        <section class="admin-section">
            <h2>Trade Management</h2>
            <div class="admin-controls">
                <button id="refreshTrades" class="admin-btn"><?= EmojiConfig::getUIEmoji('refresh') ?> Refresh Trades</button>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear ALL trade offers? This action cannot be undone.')">
                    <input type="hidden" name="action" value="clearAllTrades">
                    <button type="submit" class="admin-btn danger">Clear All Trades</button>
                </form>
            </div>
        </section>

        <!-- Active Trade Offers -->
        <section class="admin-section">
            <h2>Active Trade Offers</h2>
            <div class="table-container">
                <table id="activeTradesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From Player</th>
                            <th>Type</th>
                            <th>Offering</th>
                            <th>Requesting</th>
                            <th>Trades</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activeTradesTableBody">
                        <tr><td colspan="8">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Trade History -->
        <section class="admin-section">
            <h2>Recent Trade History</h2>
            <div class="table-container">
                <table id="tradeHistoryTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From Player</th>
                            <th>To Player</th>
                            <th>Resources Traded</th>
                            <th>Completed At</th>
                        </tr>
                    </thead>
                    <tbody id="tradeHistoryTableBody">
                        <tr><td colspan="5">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Trade Analytics -->
        <section class="admin-section">
            <h2>Trade Analytics</h2>
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Most Traded Resource</h3>
                    <div id="mostTradedResource">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Average Trade Value</h3>
                    <div id="avgTradeValue">Loading...</div>
                </div>
                <div class="analytics-card">
                    <h3>Top Trader</h3>
                    <div id="topTrader">Loading...</div>
                </div>
            </div>
        </section>
    </div>

    <!-- Confirm Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Confirm Action</h3>
            <p id="confirmText">Are you sure you want to perform this action?</p>
            <div class="modal-buttons">
                <button id="confirmYes" class="admin-btn">Yes</button>
                <button id="confirmNo" class="admin-btn secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        let marketData = {
            activeTrades: [],
            tradeHistory: [],
            analytics: {}
        };

        // Load market data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadMarketData();
            
            // Set up refresh button
            document.getElementById('refreshTrades').addEventListener('click', loadMarketData);
            
            // Set up modal
            setupModal();
        });

        function loadMarketData() {
            Promise.all([
                loadActiveTrades(),
                loadTradeHistory(),
                loadTradeAnalytics()
            ]).then(() => {
                console.log('Market data loaded successfully');
            }).catch(error => {
                console.error('Error loading market data:', error);
            });
        }

        function loadActiveTrades() {
            return fetch('admin-backend.php?action=getActiveTrades')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.trades) {
                        marketData.activeTrades = data.trades;
                        renderActiveTradesTable();
                    } else {
                        throw new Error(data.message || 'Failed to load active trades');
                    }
                })
                .catch(error => {
                    console.error('Error loading active trades:', error);
                    renderActiveTradesError();
                });
        }

        function loadTradeHistory() {
            return fetch('admin-backend.php?action=getTradeHistory&limit=20')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history) {
                        marketData.tradeHistory = data.history;
                        renderTradeHistoryTable();
                    } else {
                        throw new Error(data.message || 'Failed to load trade history');
                    }
                })
                .catch(error => {
                    console.error('Error loading trade history:', error);
                    renderTradeHistoryError();
                });
        }

        function loadTradeAnalytics() {
            return fetch('admin-backend.php?action=getTradeAnalytics')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.analytics) {
                        marketData.analytics = data.analytics;
                        renderTradeAnalytics();
                    } else {
                        throw new Error(data.message || 'Failed to load trade analytics');
                    }
                })
                .catch(error => {
                    console.error('Error loading trade analytics:', error);
                    renderTradeAnalyticsError();
                });
        }

        function renderActiveTradesTable() {
            const tbody = document.getElementById('activeTradesTableBody');
            
            if (marketData.activeTrades.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No active trades found</td></tr>';
                return;
            }

            tbody.innerHTML = marketData.activeTrades.map(trade => {
                const offering = formatTradeResources(trade.offering);
                const requesting = formatTradeResources(trade.requesting);
                
                return `
                    <tr>
                        <td>${trade.id}</td>
                        <td>${trade.fromPlayer}</td>
                        <td><span class="trade-type-${trade.type}">${trade.typeName}</span></td>
                        <td>${offering}</td>
                        <td>${requesting}</td>
                        <td>${trade.currentTrades}/${trade.maxTrades}</td>
                        <td>${new Date(trade.createdAt).toLocaleDateString()}</td>
                        <td>
                            <button class="admin-btn small danger" onclick="cancelTrade(${trade.id})">Cancel</button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function renderTradeHistoryTable() {
            const tbody = document.getElementById('tradeHistoryTableBody');
            
            if (marketData.tradeHistory.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No trade history found</td></tr>';
                return;
            }

            tbody.innerHTML = marketData.tradeHistory.map(trade => {
                const resources = formatTradeResources(trade.resources);
                
                return `
                    <tr>
                        <td>${trade.id}</td>
                        <td>${trade.fromPlayer}</td>
                        <td>${trade.toPlayer}</td>
                        <td>${resources}</td>
                        <td>${new Date(trade.completedAt).toLocaleString()}</td>
                    </tr>
                `;
            }).join('');
        }

        function renderTradeAnalytics() {
            document.getElementById('mostTradedResource').textContent = marketData.analytics.mostTradedResource || 'N/A';
            document.getElementById('avgTradeValue').textContent = marketData.analytics.avgTradeValue || 'N/A';
            document.getElementById('topTrader').textContent = marketData.analytics.topTrader || 'N/A';
        }

        function renderActiveTradesError() {
            document.getElementById('activeTradesTableBody').innerHTML = 
                '<tr><td colspan="8" style="text-align: center; color: red;">Error loading active trades</td></tr>';
        }

        function renderTradeHistoryError() {
            document.getElementById('tradeHistoryTableBody').innerHTML = 
                '<tr><td colspan="5" style="text-align: center; color: red;">Error loading trade history</td></tr>';
        }

        function renderTradeAnalyticsError() {
            document.getElementById('mostTradedResource').textContent = 'Error';
            document.getElementById('avgTradeValue').textContent = 'Error';
            document.getElementById('topTrader').textContent = 'Error';
        }

        function formatTradeResources(resources) {
            const parts = [];
            
            if (resources.wood > 0) parts.push(`${getResourceEmoji('wood')} ${resources.wood}`);
            if (resources.stone > 0) parts.push(`${getResourceEmoji('stone')} ${resources.stone}`);
            if (resources.ore > 0) parts.push(`${getResourceEmoji('ore')} ${resources.ore}`);
            if (resources.gold > 0) parts.push(`${getResourceEmoji('gold')} ${resources.gold}`);
            
            return parts.join(', ') || 'None';
        }

        function cancelTrade(tradeId) {
            showConfirmModal(`Are you sure you want to cancel trade offer #${tradeId}?`, () => {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancelTrade">
                    <input type="hidden" name="tradeId" value="${tradeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }

        function setupModal() {
            const modal = document.getElementById('confirmModal');
            const span = document.getElementsByClassName('close')[0];
            const yesBtn = document.getElementById('confirmYes');
            const noBtn = document.getElementById('confirmNo');

            span.onclick = function() {
                modal.style.display = 'none';
            }

            noBtn.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        }

        function showConfirmModal(text, callback) {
            const modal = document.getElementById('confirmModal');
            const confirmText = document.getElementById('confirmText');
            const yesBtn = document.getElementById('confirmYes');
            
            confirmText.textContent = text;
            modal.style.display = 'block';
            
            yesBtn.onclick = function() {
                modal.style.display = 'none';
                callback();
            }
        }
    </script>

    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .analytics-card {
            background: var(--bg-secondary);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--card-border);
        }

        .analytics-card h3 {
            margin: 0 0 10px 0;
            color: var(--text-color);
        }

        .analytics-card div {
            font-size: 18px;
            font-weight: bold;
            color: var(--nav-active);
        }

        .trade-type-resource_trade {
            background: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .trade-type-resource_sell {
            background: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .trade-type-resource_buy {
            background: #ffc107;
            color: black;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }

        .admin-btn.small {
            padding: 4px 8px;
            font-size: 12px;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .admin-btn.secondary {
            background: var(--bg-secondary);
            color: var(--text-color);
            border: 1px solid var(--card-border);
        }

        .admin-btn.secondary:hover {
            background: var(--table-hover);
        }
    </style>
</body>
</html>