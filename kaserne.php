<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    // Get Kaserne (Barracks) specific data from database
    $database = new Database();
    
    // We only need Kaserne building type for this page
    $kaserneBuilding = [
        'name' => 'Barracks',
        'id' => 'kaserne',
        'originalName' => 'Kaserne'
    ];

    // Process incoming requests
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barracks - Military Training</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <div class="main-content">
        <h2><?= EmojiConfig::getBuildingEmoji('kaserne') ?> Barracks - Military Command Center</h2>
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

    <!-- Barracks Building Management -->
    <section class="buildings">
        <h3>Barracks Building</h3>
        <table>
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Level</th>
                    <th>Cost</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?= EmojiConfig::formatBuildingWithEmoji('kaserne', $kaserneBuilding['name']) ?>
                    </td>
                    <td><span id="kaserne">0</span></td>
                    <td>
                        <span class="cost-box" id="kaserneKostenHolz">0 <?= EmojiConfig::getResourceEmoji('wood') ?></span>
                        <span class="cost-box" id="kaserneKostenStein">0 <?= EmojiConfig::getResourceEmoji('stone') ?></span>
                        <span class="cost-box" id="kaserneKostenErz">0 <?= EmojiConfig::getResourceEmoji('ore') ?></span>
                        <span class="cost-box" id="kaserneKostenSiedler">0 <?= EmojiConfig::getResourceEmoji('settlers') ?></span>
                        <span class="cost-box" id="kaserneBauzeit">0s <?= EmojiConfig::getUIEmoji('time') ?></span>
                    </td>
                    <td style="text-align: right;">
                        <button id="kaserneupgradeButton" 
                            onclick="upgradeBuilding('<?= htmlspecialchars($kaserneBuilding['originalName']) ?>','<?= htmlspecialchars($settlementId) ?>')">
                            Upgrade Barracks
                        </button>
                    </td>
                </tr>
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
                    <p><strong>Cost:</strong> 50🪵 30🧱 20🪨</p>
                    <p><strong>Training Time:</strong> 30s</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="guards-count">0</span></span>
                </div>
                <button class="train-unit-btn" onclick="trainUnit('guards', <?= $settlementId ?>)">
                    Train Guards
                </button>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">⚔️</span>
                    <h4>Soldiers</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Attack:</strong> +3 per unit</p>
                    <p><strong>Cost:</strong> 80🪵 60🧱 40🪨</p>
                    <p><strong>Training Time:</strong> 60s</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="soldiers-count">0</span></span>
                </div>
                <button class="train-unit-btn" onclick="trainUnit('soldiers', <?= $settlementId ?>)">
                    Train Soldiers
                </button>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">🏹</span>
                    <h4>Archers</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Ranged Attack:</strong> +4 per unit</p>
                    <p><strong>Cost:</strong> 100🪵 40🧱 60🪨</p>
                    <p><strong>Training Time:</strong> 90s</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="archers-count">0</span></span>
                </div>
                <button class="train-unit-btn" onclick="trainUnit('archers', <?= $settlementId ?>)">
                    Train Archers
                </button>
            </div>

            <div class="military-unit-card">
                <div class="unit-header">
                    <span class="unit-emoji">🐎</span>
                    <h4>Cavalry</h4>
                </div>
                <div class="unit-stats">
                    <p><strong>Speed & Attack:</strong> +5 per unit</p>
                    <p><strong>Cost:</strong> 150🪵 100🧱 120🪨</p>
                    <p><strong>Training Time:</strong> 180s</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="cavalry-count">0</span></span>
                </div>
                <button class="train-unit-btn" onclick="trainUnit('cavalry', <?= $settlementId ?>)">
                    Train Cavalry
                </button>
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
        // Military unit training functionality
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
            
            queue.forEach(item => {
                const row = tbody.insertRow();
                
                // Unit type
                const unitCell = row.insertCell(0);
                unitCell.textContent = `${item.count}x ${item.unitType.charAt(0).toUpperCase() + item.unitType.slice(1)}`;
                
                // Level (not applicable for units, show count instead)
                const levelCell = row.insertCell(1);
                levelCell.textContent = `Count: ${item.count}`;
                
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
                    const minutes = Math.floor(item.remainingTimeSeconds / 60);
                    const seconds = item.remainingTimeSeconds % 60;
                    remainingDiv.textContent = `(${minutes}m ${seconds}s remaining)`;
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