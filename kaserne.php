<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    // Process incoming requests
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Military - Training</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <div class="main-content">
        <h2><?= EmojiConfig::getBuildingEmoji('kaserne') ?> Military - Command Center</h2>
        <p>Train military units, manage your army, and prepare for battles. Higher level barracks allow training of more powerful units.</p>
    </div>
    
    <!-- Building Queue Section -->
    <section class="buildings">
        <h3>Military Training Queue</h3>
        <table>
            <thead>
                <tr>
                    <th>Unit/Building</th>
                    <th>Level</th>
                    <th>Progress</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody id="buildingQueueBody">
                <!-- Queue items will be populated by JavaScript -->
            </tbody>
        </table>
    </section>

    <!-- Military Units Section -->
    <section class="buildings">
        <h3>Military Units</h3>
        <div class="military-units-grid">
            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">🛡️</span>
                    <h4>Guards</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Defense:</strong> +2 per unit</p>
                    <p><strong>Cost:</strong> 50🪵 30🧱 20🪨 1👥</p>
                    <p><strong>Training Time:</strong> 2m per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="guards-count">0</span></span>
                </div>
                <div class="unit-training">
                    <label for="guards-quantity">Quantity:</label>
                    <input type="number" id="guards-quantity" min="1" max="10" value="1" style="width: 60px;">
                    <button class="train-unit-btn" onclick="trainMultipleUnits('guards', <?= $settlementId ?>)">
                        Train Guards
                    </button>
                </div>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">⚔️</span>
                    <h4>Soldiers</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Attack:</strong> +3 per unit</p>
                    <p><strong>Cost:</strong> 80🪵 60🧱 40🪨 1👥</p>
                    <p><strong>Training Time:</strong> 3m per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="soldiers-count">0</span></span>
                </div>
                <div class="unit-training">
                    <label for="soldiers-quantity">Quantity:</label>
                    <input type="number" id="soldiers-quantity" min="1" max="10" value="1" style="width: 60px;">
                    <button class="train-unit-btn" onclick="trainMultipleUnits('soldiers', <?= $settlementId ?>)">
                        Train Soldiers
                    </button>
                </div>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">🏹</span>
                    <h4>Archers</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Ranged Attack:</strong> +4 per unit</p>
                    <p><strong>Cost:</strong> 100🪵 40🧱 60🪨 1👥</p>
                    <p><strong>Training Time:</strong> 4m per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="archers-count">0</span></span>
                </div>
                <div class="unit-training">
                    <label for="archers-quantity">Quantity:</label>
                    <input type="number" id="archers-quantity" min="1" max="10" value="1" style="width: 60px;">
                    <button class="train-unit-btn" onclick="trainMultipleUnits('archers', <?= $settlementId ?>)">
                        Train Archers
                    </button>
                </div>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">🐎</span>
                    <h4>Cavalry</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Speed & Attack:</strong> +5 per unit</p>
                    <p><strong>Cost:</strong> 150🪵 100🧱 120🪨 1👥</p>
                    <p><strong>Training Time:</strong> 5m per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="cavalry-count">0</span></span>
                </div>
                <div class="unit-training">
                    <label for="cavalry-quantity">Quantity:</label>
                    <input type="number" id="cavalry-quantity" min="1" max="10" value="1" style="width: 60px;">
                    <button class="train-unit-btn" onclick="trainMultipleUnits('cavalry', <?= $settlementId ?>)">
                        Train Cavalry
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Military Statistics -->
    <section class="buildings">
        <h3>Military Statistics</h3>
        <div class="military-stats">
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-content">
                    <h4>Total Defense</h4>
                    <span class="stat-value" id="total-defense">0</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚔️</div>
                <div class="stat-content">
                    <h4>Total Attack</h4>
                    <span class="stat-value" id="total-attack">0</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h4>Total Units</h4>
                    <span class="stat-value" id="total-units">0</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏹</div>
                <div class="stat-content">
                    <h4>Ranged Power</h4>
                    <span class="stat-value" id="ranged-power">0</span>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Military unit training functionality - multiple units
        function trainMultipleUnits(unitType, settlementId) {
            const quantityInput = document.getElementById(unitType + '-quantity');
            const count = parseInt(quantityInput.value) || 1;
            
            if (count < 1 || count > 10) {
                alert('Please enter a quantity between 1 and 10');
                return;
            }
            
            // Check if current player owns this settlement
            fetch(`../php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
                .then(response => response.json())
                .then(ownerData => {
                    const settlementOwnerId = ownerData.playerInfo ? ownerData.playerInfo.playerId : null;
                    const currentPlayerId = window.currentPlayerId || null;
                    
                    // Check ownership
                    if (currentPlayerId !== null && settlementOwnerId !== null && currentPlayerId !== settlementOwnerId) {
                        alert('You can only train units in your own settlement. Switch to your settlement first.');
                        return;
                    }
                    
                    // Proceed with training multiple units
                    fetch('../php/backend.php?settlementId=' + settlementId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            action: 'trainUnit',
                            unitType: unitType,
                            count: count,
                            currentPlayerId: currentPlayerId 
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Training started for ${count} ${unitType}!`);
                            // Refresh the page data
                            loadMilitaryData(settlementId);
                            fetchResources(settlementId);
                        } else {
                            alert('Training failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error training unit:', error);
                        alert('Error training unit. Please try again.');
                    });
                })
                .catch(error => {
                    console.error('Error checking settlement ownership:', error);
                    alert('Error checking settlement ownership. Please try again.');
                });
        }

        // Military unit training functionality - single unit (kept for compatibility)
        function trainUnit(unitType, settlementId) {
            // Check if current player owns this settlement
            fetch(`../php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
                .then(response => response.json())
                .then(ownerData => {
                    const settlementOwnerId = ownerData.playerInfo ? ownerData.playerInfo.playerId : null;
                    const currentPlayerId = window.currentPlayerId || null;
                    
                    // Check ownership
                    if (currentPlayerId !== null && settlementOwnerId !== null && currentPlayerId !== settlementOwnerId) {
                        alert('You can only train units in your own settlement. Switch to your settlement first.');
                        return;
                    }
                    
                    // Proceed with training 1 unit
                    fetch('../php/backend.php?settlementId=' + settlementId, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ 
                            action: 'trainUnit',
                            unitType: unitType,
                            count: 1,
                            currentPlayerId: currentPlayerId 
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Training started for ' + unitType + '!');
                            // Refresh the page data
                            loadMilitaryData(settlementId);
                            fetchResources(settlementId);
                        } else {
                            alert('Training failed: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error training unit:', error);
                        alert('Error training unit. Please try again.');
                    });
                })
                .catch(error => {
                    console.error('Error checking settlement ownership:', error);
                    alert('Error checking settlement ownership. Please try again.');
                });
        }
        
        // Load military data from backend
        function loadMilitaryData(settlementId) {
            // Load unit counts
            fetch(`../php/backend.php?settlementId=${settlementId}&getMilitaryUnits=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.militaryUnits && data.militaryUnits.units) {
                        const units = data.militaryUnits.units;
                        document.getElementById('guards-count').textContent = units.guards || 0;
                        document.getElementById('soldiers-count').textContent = units.soldiers || 0;
                        document.getElementById('archers-count').textContent = units.archers || 0;
                        document.getElementById('cavalry-count').textContent = units.cavalry || 0;
                    }
                })
                .catch(error => console.error('Error loading military units:', error));
                
            // Load military stats
            fetch(`../php/backend.php?settlementId=${settlementId}&getMilitaryStats=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.militaryStats && data.militaryStats.stats) {
                        const stats = data.militaryStats.stats;
                        document.getElementById('total-defense').textContent = stats.totalDefense || 0;
                        document.getElementById('total-attack').textContent = stats.totalAttack || 0;
                        document.getElementById('total-units').textContent = stats.totalUnits || 0;
                        document.getElementById('ranged-power').textContent = stats.rangedPower || 0;
                    }
                })
                .catch(error => console.error('Error loading military stats:', error));
                
            // Load training queue
            fetch(`../php/backend.php?settlementId=${settlementId}&getMilitaryQueue=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.militaryQueue && data.militaryQueue.queue) {
                        updateMilitaryQueue(data.militaryQueue.queue);
                    }
                })
                .catch(error => console.error('Error loading military queue:', error));
        }
        
        // Update military training queue display
        function updateMilitaryQueue(queue) {
            const tbody = document.getElementById('buildingQueueBody');
            tbody.innerHTML = '';
            
            if (queue.length === 0) {
                const row = tbody.insertRow();
                const cell = row.insertCell(0);
                cell.colSpan = 4;
                cell.textContent = 'No units currently in training';
                cell.style.textAlign = 'center';
                cell.style.fontStyle = 'italic';
                return;
            }
            
            queue.forEach((item, index) => {
                const row = tbody.insertRow();
                
                // Unit type with training progress
                const unitCell = row.insertCell(0);
                if (item.count > 1) {
                    // For multiple units, show "Training X of Y" format
                    const completedUnits = Math.floor((item.completionPercentage || 0) / 100 * item.count);
                    const currentUnit = Math.min(completedUnits + 1, item.count);
                    unitCell.textContent = `Training ${currentUnit} of ${item.count} ${item.unitType.charAt(0).toUpperCase() + item.unitType.slice(1)}`;
                } else {
                    unitCell.textContent = `${item.unitType.charAt(0).toUpperCase() + item.unitType.slice(1)}`;
                }
                
                // Count info
                const levelCell = row.insertCell(1);
                levelCell.textContent = `${item.count} unit${item.count > 1 ? 's' : ''}`;
                
                // Progress
                const progressCell = row.insertCell(2);
                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#e0e0e0';
                progressBar.style.borderRadius = '4px';
                progressBar.style.height = '20px';
                progressBar.style.position = 'relative';
                
                const progressFill = document.createElement('div');
                progressFill.style.width = `${Math.max(0, Math.min(100, item.completionPercentage || 0))}%`;
                progressFill.style.backgroundColor = '#4CAF50';
                progressFill.style.height = '100%';
                progressFill.style.borderRadius = '4px';
                progressFill.style.transition = 'width 0.3s ease';
                
                const progressText = document.createElement('span');
                progressText.style.position = 'absolute';
                progressText.style.left = '50%';
                progressText.style.top = '50%';
                progressText.style.transform = 'translate(-50%, -50%)';
                progressText.style.fontSize = '12px';
                progressText.style.fontWeight = 'bold';
                progressText.textContent = `${Math.round(item.completionPercentage || 0)}%`;
                
                progressBar.appendChild(progressFill);
                progressBar.appendChild(progressText);
                progressCell.appendChild(progressBar);
                
                // End time
                const timeCell = row.insertCell(3);
                const endTime = new Date(item.endTime);
                timeCell.textContent = endTime.toLocaleString();
                
                // Add remaining time
                if (item.remainingTimeSeconds > 0) {
                    const remainingDiv = document.createElement('div');
                    remainingDiv.style.fontSize = '12px';
                    remainingDiv.style.color = '#666';
                    const hours = Math.floor(item.remainingTimeSeconds / 3600);
                    const minutes = Math.floor((item.remainingTimeSeconds % 3600) / 60);
                    const seconds = item.remainingTimeSeconds % 60;
                    
                    if (hours > 0) {
                        remainingDiv.textContent = `(${hours}h ${minutes}m remaining)`;
                    } else {
                        remainingDiv.textContent = `(${minutes}m ${seconds}s remaining)`;
                    }
                    timeCell.appendChild(remainingDiv);
                }
            });
        }
        
        // Initialize military data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Get settlement ID from URL or other source
            const urlParams = new URLSearchParams(window.location.search);
            const settlementId = urlParams.get('settlementId');
            
            if (settlementId) {
                loadMilitaryData(settlementId);
                // Refresh military data every 5 seconds
                setInterval(() => loadMilitaryData(settlementId), 5000);
            } else {
                // Fallback: Initialize with zeros if no settlement ID
                document.getElementById('guards-count').textContent = '0';
                document.getElementById('soldiers-count').textContent = '0';
                document.getElementById('archers-count').textContent = '0';
                document.getElementById('cavalry-count').textContent = '0';
                
                document.getElementById('total-defense').textContent = '0';
                document.getElementById('total-attack').textContent = '0';
                document.getElementById('total-units').textContent = '0';
                document.getElementById('ranged-power').textContent = '0';
            }
        });
    </script>
</body>
</html>