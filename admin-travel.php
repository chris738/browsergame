<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: ../admin.php');
    exit;
}

require_once 'php/database.php';
require_once 'php/emoji-config.php';

$database = new Database();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel & Military Config - Admin Panel</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
</head>
<body>
    <?php include 'php/admin-navigation.php'; ?>
    
    <div class="admin-content">
        <h2><?= EmojiConfig::getUIEmoji('clock') ?> Travel & Military Configuration</h2>
        <p>Configure travel times and military unit settings</p>
        
        <!-- Travel Configuration Section -->
        <section class="admin-section">
            <h3><?= EmojiConfig::getUIEmoji('road') ?> Travel Speed Configuration</h3>
            <div class="config-grid">
                <div class="config-item">
                    <label for="tradeSpeed">Trade Travel Speed (seconds per block):</label>
                    <input type="number" id="tradeSpeed" min="1" max="60" value="5">
                    <button onclick="updateTravelConfig('trade')">Update</button>
                </div>
                <div class="config-item">
                    <label for="militarySpeed">Military Base Speed (seconds per block):</label>
                    <input type="number" id="militarySpeed" min="1" max="60" value="5">
                    <button onclick="updateTravelConfig('military')">Update</button>
                </div>
            </div>
        </section>

        <!-- Military Unit Configuration Section -->
        <section class="admin-section">
            <h3><?= EmojiConfig::getUIEmoji('shield') ?> Military Unit Configuration</h3>
            <div class="unit-config-table">
                <table id="unitConfigTable">
                    <thead>
                        <tr>
                            <th>Unit Type</th>
                            <th>Level</th>
                            <th>Speed</th>
                            <th>Loot Amount</th>
                            <th>Attack Power</th>
                            <th>Defense Power</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Current Traveling Status Section -->
        <section class="admin-section">
            <h3><?= EmojiConfig::getUIEmoji('chart') ?> Current Traveling Status</h3>
            
            <div class="travel-status-grid">
                <div class="travel-status-card">
                    <h4><?= EmojiConfig::getUIEmoji('soldier') ?> Traveling Armies</h4>
                    <div id="travelingArmies">
                        <div class="loading">Loading...</div>
                    </div>
                </div>
                
                <div class="travel-status-card">
                    <h4><?= EmojiConfig::getUIEmoji('market') ?> Traveling Trades</h4>
                    <div id="travelingTrades">
                        <div class="loading">Loading...</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Arrivals Section -->
        <section class="admin-section">
            <h3><?= EmojiConfig::getUIEmoji('gear') ?> Travel Processing</h3>
            <div class="process-controls">
                <button id="processArrivals" onclick="processArrivals()">Process All Arrivals Now</button>
                <div id="processResult"></div>
                <p><small>Note: Arrivals are normally processed automatically every 30 seconds. Use this button for manual processing during testing.</small></p>
            </div>
        </section>
    </div>

    <script>
        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadTravelConfig();
            loadUnitConfig();
            loadTravelingStatus();
            
            // Auto-refresh traveling status every 30 seconds
            setInterval(loadTravelingStatus, 30000);
        });

        async function loadTravelConfig() {
            try {
                const response = await fetch('php/admin-backend.php?action=getTravelConfig');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('tradeSpeed').value = data.tradeSpeed || 5;
                    document.getElementById('militarySpeed').value = data.militarySpeed || 5;
                }
            } catch (error) {
                console.error('Error loading travel config:', error);
            }
        }

        async function updateTravelConfig(type) {
            const speed = document.getElementById(type + 'Speed').value;
            
            try {
                const response = await fetch('php/admin-backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'updateTravelConfig',
                        travelType: type,
                        baseSpeed: parseInt(speed)
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Travel config updated successfully', 'success');
                } else {
                    showMessage('Failed to update travel config: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error updating travel config:', error);
                showMessage('Error updating travel config', 'error');
            }
        }

        async function loadUnitConfig() {
            try {
                const response = await fetch('php/admin-backend.php?action=getMilitaryUnitConfig');
                const data = await response.json();
                
                if (data.success && data.units) {
                    const tbody = document.querySelector('#unitConfigTable tbody');
                    tbody.innerHTML = '';
                    
                    data.units.forEach(unit => {
                        const row = tbody.insertRow();
                        row.innerHTML = `
                            <td>${unit.unitType}</td>
                            <td>${unit.level}</td>
                            <td><input type="number" value="${unit.speed}" min="2" max="10" data-unit="${unit.unitType}" data-level="${unit.level}" data-field="speed"></td>
                            <td><input type="number" value="${unit.lootAmount || 10}" min="1" max="100" step="0.1" data-unit="${unit.unitType}" data-level="${unit.level}" data-field="lootAmount"></td>
                            <td><input type="number" value="${unit.attackPower}" min="1" max="50" data-unit="${unit.unitType}" data-level="${unit.level}" data-field="attackPower"></td>
                            <td><input type="number" value="${unit.defensePower}" min="1" max="50" data-unit="${unit.unitType}" data-level="${unit.level}" data-field="defensePower"></td>
                            <td><button onclick="updateUnitConfig('${unit.unitType}', ${unit.level})">Update</button></td>
                        `;
                    });
                }
            } catch (error) {
                console.error('Error loading unit config:', error);
            }
        }

        async function updateUnitConfig(unitType, level) {
            const row = document.querySelector(`button[onclick="updateUnitConfig('${unitType}', ${level})"]`).closest('tr');
            const inputs = row.querySelectorAll('input[data-unit="' + unitType + '"][data-level="' + level + '"]');
            
            const updates = [];
            inputs.forEach(input => {
                updates.push({
                    field: input.dataset.field,
                    value: parseFloat(input.value)
                });
            });
            
            try {
                for (const update of updates) {
                    const response = await fetch('php/admin-backend.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'updateMilitaryUnitConfig',
                            unitType: unitType,
                            level: level,
                            field: update.field,
                            value: update.value
                        })
                    });
                    
                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'Update failed');
                    }
                }
                
                showMessage(`Updated ${unitType} level ${level} configuration`, 'success');
            } catch (error) {
                console.error('Error updating unit config:', error);
                showMessage('Error updating unit config: ' + error.message, 'error');
            }
        }

        async function loadTravelingStatus() {
            try {
                // Load traveling armies
                const armiesResponse = await fetch('php/admin-backend.php?action=getAllTravelingArmies');
                const armiesData = await armiesResponse.json();
                
                if (armiesData.success) {
                    displayTravelingArmies(armiesData.armies);
                }
                
                // Load traveling trades
                const tradesResponse = await fetch('php/admin-backend.php?action=getAllTravelingTrades');
                const tradesData = await tradesResponse.json();
                
                if (tradesData.success) {
                    displayTravelingTrades(tradesData.trades);
                }
            } catch (error) {
                console.error('Error loading traveling status:', error);
            }
        }

        function displayTravelingArmies(armies) {
            const container = document.getElementById('travelingArmies');
            
            if (armies.length === 0) {
                container.innerHTML = '<p>No armies currently traveling</p>';
                return;
            }
            
            let html = '<div class="travel-list">';
            armies.forEach(army => {
                const timeRemaining = army.timeRemaining > 0 ? `${Math.floor(army.timeRemaining / 60)}m ${army.timeRemaining % 60}s` : 'Arrived';
                html += `
                    <div class="travel-item">
                        <strong>${army.attackerName || 'Unknown'}</strong> → <strong>${army.defenderName || 'Unknown'}</strong><br>
                        Units: G:${army.guardsCount} S:${army.soldiersCount} A:${army.archersCount} C:${army.cavalryCount}<br>
                        <small>Remaining: ${timeRemaining}</small>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        function displayTravelingTrades(trades) {
            const container = document.getElementById('travelingTrades');
            
            if (trades.length === 0) {
                container.innerHTML = '<p>No trades currently traveling</p>';
                return;
            }
            
            let html = '<div class="travel-list">';
            trades.forEach(trade => {
                const timeRemaining = trade.timeRemaining > 0 ? `${Math.floor(trade.timeRemaining / 60)}m ${trade.timeRemaining % 60}s` : 'Arrived';
                html += `
                    <div class="travel-item">
                        <strong>${trade.fromName || 'Unknown'}</strong> → <strong>${trade.toName || 'Unknown'}</strong><br>
                        Resources: W:${trade.woodAmount} S:${trade.stoneAmount} O:${trade.oreAmount} G:${trade.goldAmount}<br>
                        <small>Remaining: ${timeRemaining}</small>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        async function processArrivals() {
            const button = document.getElementById('processArrivals');
            const resultDiv = document.getElementById('processResult');
            
            button.disabled = true;
            resultDiv.innerHTML = 'Processing...';
            
            try {
                const response = await fetch('php/admin-backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'processArrivals'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `Processed ${data.processed} arrivals`;
                    loadTravelingStatus(); // Refresh the status
                } else {
                    resultDiv.innerHTML = 'Error: ' + data.message;
                }
            } catch (error) {
                console.error('Error processing arrivals:', error);
                resultDiv.innerHTML = 'Error processing arrivals';
            } finally {
                button.disabled = false;
            }
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message-${type}`;
            messageDiv.textContent = message;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                border-radius: 4px;
                z-index: 1000;
                background: ${type === 'success' ? '#4CAF50' : '#f44336'};
                color: white;
            `;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }
    </script>

    <style>
        .admin-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: var(--bg-secondary);
        }

        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .config-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .config-item label {
            flex: 1;
            font-weight: 600;
        }

        .config-item input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .unit-config-table {
            overflow-x: auto;
        }

        .unit-config-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .unit-config-table th,
        .unit-config-table td {
            padding: 0.75rem;
            border: 1px solid #ddd;
            text-align: left;
        }

        .unit-config-table th {
            background: var(--bg-primary);
            font-weight: 600;
        }

        .unit-config-table input {
            width: 80px;
            padding: 0.25rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .travel-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1rem;
        }

        .travel-status-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            background: var(--bg-primary);
        }

        .travel-status-card h4 {
            margin-top: 0;
        }

        .travel-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .travel-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border: 1px solid #eee;
            border-radius: 4px;
            background: var(--bg-secondary);
        }

        .process-controls {
            text-align: center;
        }

        .process-controls button {
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }

        .process-controls button:hover {
            background: #0056b3;
        }

        .process-controls button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        #processResult {
            margin-top: 1rem;
            padding: 0.5rem;
            font-weight: 600;
        }
    </style>
</body>
</html>