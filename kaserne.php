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
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/progress-bars.css">
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
                    <th>Unit</th>
                    <th>Quantity</th>
                    <th>Progress</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody id="militaryTrainingQueueBody">
                <!-- Queue items will be populated by JavaScript -->
            </tbody>
        </table>
    </section>

    <!-- Compact Research Queue -->
    <section class="buildings" id="researchQueueSection" style="margin-bottom: 10px;">
        <h3>🔬 Research Queue</h3>
        <div id="researchQueueCompact">
            <table id="researchQueueTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Progress</th>
                        <th>End Time</th>
                    </tr>
                </thead>
                <tbody id="researchQueueBody">
                    <!-- Research queue items will be populated by JavaScript -->
                </tbody>
            </table>
            <p id="noResearchMessage" style="font-style: italic; color: #666; margin: 10px 0;">No research currently in progress</p>
        </div>
    </section>

    <!-- Military Units Section -->
    <section class="buildings">
        <h3>Military Units</h3>
        <div class="military-units-grid">
            <div class="military-unit-card" id="unit-card-guards">
                <div class="unit-header">
                    <span class="unit-emoji">🛡️</span>
                    <h4>Guards</h4>
                    <span class="research-status-mini" id="status-mini-guards">🔬</span>
                </div>
                <div class="unit-stats">
                    <p><strong>Defense:</strong> +2 per unit</p>
                    <p><strong>Cost:</strong> 50<script>document.write(getResourceEmoji('wood'))</script> 30<script>document.write(getResourceEmoji('stone'))</script> 20<script>document.write(getResourceEmoji('ore'))</script> 1<script>document.write(getResourceEmoji('settlers'))</script></p>
                    <p><strong>Training Time:</strong> 30s per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="guards-count">0</span></span>
                </div>
                
                <!-- Research Info (initially hidden) -->
                <div class="unit-research-info" id="research-info-guards" style="display: none;">
                    <div class="research-costs-mini">
                        <h5>Research Required:</h5>
                        <p id="guards-research-cost"><script>document.write(getResourceEmoji('wood'))</script> 100 <script>document.write(getResourceEmoji('stone'))</script> 50 <script>document.write(getResourceEmoji('ore'))</script> 30 <script>document.write(getUIEmoji('time'))</script> 1min</p>
                    </div>
                </div>
                
                <div class="unit-actions">
                    <button class="research-unit-btn" id="research-btn-guards" onclick="startResearch('guards', <?= $settlementId ?>)" style="display: none;">
                        🔬 Research Guards
                    </button>
                    <div class="unit-training" id="training-controls-guards">
                        <button class="train-unit-btn" id="train-btn-guards" onclick="trainMultipleUnits('guards', <?= $settlementId ?>)">
                            Train
                        </button>
                        <label for="guards-quantity">Quantity:</label>
                        <input type="number" id="guards-quantity" min="1" max="10" value="1" style="width: 60px;">
                    </div>
                </div>
            </div>

            <div class="military-unit-card" id="unit-card-soldiers">
                <div class="unit-header">
                    <span class="unit-emoji">⚔️</span>
                    <h4>Soldiers</h4>
                    <span class="research-status-mini" id="status-mini-soldiers">🔬</span>
                </div>
                <div class="unit-stats">
                    <p><strong>Attack:</strong> +3 per unit</p>
                    <p><strong>Cost:</strong> 80<script>document.write(getResourceEmoji('wood'))</script> 60<script>document.write(getResourceEmoji('stone'))</script> 40<script>document.write(getResourceEmoji('ore'))</script> 1<script>document.write(getResourceEmoji('settlers'))</script></p>
                    <p><strong>Training Time:</strong> 45s per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="soldiers-count">0</span></span>
                </div>
                
                <!-- Research Info (initially hidden) -->
                <div class="unit-research-info" id="research-info-soldiers" style="display: none;">
                    <div class="research-costs-mini">
                        <h5>Research Required:</h5>
                        <p id="soldiers-research-cost"><script>document.write(getResourceEmoji('wood'))</script> 150 <script>document.write(getResourceEmoji('stone'))</script> 100 <script>document.write(getResourceEmoji('ore'))</script> 50 <script>document.write(getUIEmoji('time'))</script> 2min</p>
                    </div>
                </div>
                
                <div class="unit-actions">
                    <button class="research-unit-btn" id="research-btn-soldiers" onclick="startResearch('soldiers', <?= $settlementId ?>)" style="display: none;">
                        🔬 Research Soldiers
                    </button>
                    <div class="unit-training" id="training-controls-soldiers">
                        <button class="train-unit-btn" id="train-btn-soldiers" onclick="trainMultipleUnits('soldiers', <?= $settlementId ?>)">
                            Train
                        </button>
                        <label for="soldiers-quantity">Quantity:</label>
                        <input type="number" id="soldiers-quantity" min="1" max="10" value="1" style="width: 60px;">
                    </div>
                </div>
            </div>

            <div class="military-unit-card" id="unit-card-archers">
                <div class="unit-header">
                    <span class="unit-emoji">🏹</span>
                    <h4>Archers</h4>
                    <span class="research-status-mini" id="status-mini-archers">🔬</span>
                </div>
                <div class="unit-stats">
                    <p><strong>Ranged Attack:</strong> +4 per unit</p>
                    <p><strong>Cost:</strong> 100<script>document.write(getResourceEmoji('wood'))</script> 40<script>document.write(getResourceEmoji('stone'))</script> 60<script>document.write(getResourceEmoji('ore'))</script> 1<script>document.write(getResourceEmoji('settlers'))</script></p>
                    <p><strong>Training Time:</strong> 1m per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="archers-count">0</span></span>
                </div>
                
                <!-- Research Info (initially hidden) -->
                <div class="unit-research-info" id="research-info-archers" style="display: none;">
                    <div class="research-costs-mini">
                        <h5>Research Required:</h5>
                        <p id="archers-research-cost"><script>document.write(getResourceEmoji('wood'))</script> 200 <script>document.write(getResourceEmoji('stone'))</script> 80 <script>document.write(getResourceEmoji('ore'))</script> 120 <script>document.write(getUIEmoji('time'))</script> 3min</p>
                    </div>
                </div>
                
                <div class="unit-actions">
                    <button class="research-unit-btn" id="research-btn-archers" onclick="startResearch('archers', <?= $settlementId ?>)" style="display: none;">
                        🔬 Research Archers
                    </button>
                    <div class="unit-training" id="training-controls-archers">
                        <button class="train-unit-btn" id="train-btn-archers" onclick="trainMultipleUnits('archers', <?= $settlementId ?>)">
                            Train
                        </button>
                        <label for="archers-quantity">Quantity:</label>
                        <input type="number" id="archers-quantity" min="1" max="10" value="1" style="width: 60px;">
                    </div>
                </div>
            </div>

            <div class="military-unit-card" id="unit-card-cavalry">
                <div class="unit-header">
                    <span class="unit-emoji">🐎</span>
                    <h4>Cavalry</h4>
                    <span class="research-status-mini" id="status-mini-cavalry">🔬</span>
                </div>
                <div class="unit-stats">
                    <p><strong>Speed & Attack:</strong> +5 per unit</p>
                    <p><strong>Cost:</strong> 150<script>document.write(getResourceEmoji('wood'))</script> 100<script>document.write(getResourceEmoji('stone'))</script> 120<script>document.write(getResourceEmoji('ore'))</script> 1<script>document.write(getResourceEmoji('settlers'))</script></p>
                    <p><strong>Training Time:</strong> 1m 30s per unit</p>
                </div>
                <div class="unit-count">
                    <span>Available: <span id="cavalry-count">0</span></span>
                </div>
                
                <!-- Research Info (initially hidden) -->
                <div class="unit-research-info" id="research-info-cavalry" style="display: none;">
                    <div class="research-costs-mini">
                        <h5>Research Required:</h5>
                        <p id="cavalry-research-cost"><script>document.write(getResourceEmoji('wood'))</script> 300 <script>document.write(getResourceEmoji('stone'))</script> 200 <script>document.write(getResourceEmoji('ore'))</script> 250 <script>document.write(getUIEmoji('time'))</script> 5min</p>
                    </div>
                </div>
                
                <div class="unit-actions">
                    <button class="research-unit-btn" id="research-btn-cavalry" onclick="startResearch('cavalry', <?= $settlementId ?>)" style="display: none;">
                        🔬 Research Cavalry
                    </button>
                    <div class="unit-training" id="training-controls-cavalry">
                        <button class="train-unit-btn" id="train-btn-cavalry" onclick="trainMultipleUnits('cavalry', <?= $settlementId ?>)">
                            Train
                        </button>
                        <label for="cavalry-quantity">Quantity:</label>
                        <input type="number" id="cavalry-quantity" min="1" max="10" value="1" style="width: 60px;">
                    </div>
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
                
            // Load research data
            loadResearchData(settlementId);
        }
        
        // Update military training queue display
        function updateMilitaryQueue(queue) {
            const tbody = document.getElementById('militaryTrainingQueueBody');
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
        
        // Research System Functions
        function loadResearchData(settlementId) {
            let researchStatus = {};
            
            // Load research status
            fetch(`../php/backend.php?settlementId=${settlementId}&getUnitResearch=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.unitResearch && data.unitResearch.research) {
                        researchStatus = data.unitResearch.research;
                        updateUnitAvailability(researchStatus);
                        // Update research button states if we have resources data
                        updateResearchValidation(settlementId);
                    }
                })
                .catch(error => console.error('Error loading unit research:', error));
                
            // Load research queue
            fetch(`../php/backend.php?settlementId=${settlementId}&getResearchQueue=true`)
                .then(response => response.json())
                .then(data => {
                    const queue = (data.researchQueue && data.researchQueue.queue) ? data.researchQueue.queue : [];
                    updateResearchQueue(queue);
                    updateResearchButtonStates(queue);
                })
                .catch(error => console.error('Error loading research queue:', error));
                
            // Load research config and update costs
            fetch(`../php/backend.php?getResearchConfig=true`)
                .then(response => response.json())
                .then(data => {
                    if (data.researchConfig && data.researchConfig.config) {
                        updateResearchCosts(data.researchConfig.config);
                        // Update research validation after costs are loaded
                        setTimeout(() => updateResearchValidation(settlementId), 100);
                    }
                })
                .catch(error => console.error('Error loading research config:', error));
        }
        
        function updateResearchValidation(settlementId) {
            // Fetch current resources and research status to update validation
            Promise.all([
                fetch(`../php/backend.php?settlementId=${settlementId}`).then(r => r.json()),
                fetch(`../php/backend.php?settlementId=${settlementId}&getUnitResearch=true`).then(r => r.json())
            ]).then(([resourcesData, researchData]) => {
                const resources = resourcesData.resources?.resources;
                const researchStatus = researchData.unitResearch?.research;
                
                if (resources) {
                    updateResearchCostColors(resources);
                    updateResearchButtonStates(resources, researchStatus);
                }
            }).catch(error => {
                console.error('Error updating research validation:', error);
            });
        }
        
        function updateResearchCosts(researchConfig) {
            // Update research cost displays in unit cards
            researchConfig.forEach(config => {
                const costElement = document.getElementById(`${config.unitType}-research-cost`);
                const researchBtn = document.getElementById(`research-btn-${config.unitType}`);
                
                if (costElement) {
                    const timeInMinutes = Math.floor(config.researchTime / 60);
                    costElement.textContent = `${getResourceEmoji('wood')} ${config.costWood} ${getResourceEmoji('stone')} ${config.costStone} ${getResourceEmoji('ore')} ${config.costOre} ${getUIEmoji('time')} ${timeInMinutes}min`;
                    
                    if (config.prerequisiteUnit) {
                        costElement.innerHTML += `<br><small>${getUIEmoji('status') || '📋'} Requires: ${config.prerequisiteUnit.charAt(0).toUpperCase() + config.prerequisiteUnit.slice(1)}</small>`;
                    }
                }
                
                // Store research costs as data attributes on the button for validation
                if (researchBtn) {
                    researchBtn.setAttribute('data-cost-wood', config.costWood);
                    researchBtn.setAttribute('data-cost-stone', config.costStone);
                    researchBtn.setAttribute('data-cost-ore', config.costOre);
                    researchBtn.setAttribute('data-prerequisite', config.prerequisiteUnit || '');
                }
            });
        }
        
        function updateResearchButtonStates(resources, researchStatus) {
            // Find all research buttons and check if they can be enabled
            const researchButtons = document.querySelectorAll('.research-unit-btn');
            
            researchButtons.forEach(button => {
                if (button.style.display === 'none') {
                    return; // Skip hidden buttons (already researched units)
                }
                
                const costWood = parseInt(button.getAttribute('data-cost-wood')) || 0;
                const costStone = parseInt(button.getAttribute('data-cost-stone')) || 0;
                const costOre = parseInt(button.getAttribute('data-cost-ore')) || 0;
                const prerequisite = button.getAttribute('data-prerequisite') || '';
                
                // Check if all resources are sufficient
                const hasResources = resources.wood >= costWood &&
                                   resources.stone >= costStone &&
                                   resources.ore >= costOre;
                
                // Check prerequisite if it exists
                const hasPrerequisite = !prerequisite || (researchStatus && researchStatus[prerequisite]);
                
                if (hasResources && hasPrerequisite) {
                    button.disabled = false;
                    button.classList.remove('insufficient-resources');
                } else {
                    button.disabled = true;
                    button.classList.add('insufficient-resources');
                }
            });
        }
        
        function updateResearchCostColors(resources) {
            // Update colors for research cost displays
            const resourceTypes = ['wood', 'stone', 'ore'];
            
            resourceTypes.forEach(resourceType => {
                // Find all research cost elements
                const costElements = document.querySelectorAll('[id$="-research-cost"]');
                
                costElements.forEach(element => {
                    const unitType = element.id.replace('-research-cost', '');
                    const researchBtn = document.getElementById(`research-btn-${unitType}`);
                    
                    if (researchBtn) {
                        const costValue = parseInt(researchBtn.getAttribute(`data-cost-${resourceType}`)) || 0;
                        const available = resources[resourceType] || 0;
                        
                        // Extract the cost text for this resource type and update color
                        const costText = element.textContent;
                        if (costText.includes(costValue.toString())) {
                            if (available < costValue) {
                                element.classList.add('insufficient');
                            } else {
                                element.classList.remove('insufficient');
                            }
                        }
                    }
                });
            });
        }
        
        function updateUnitAvailability(researchStatus) {
            // Update unit cards based on research status
            Object.keys(researchStatus).forEach(unitType => {
                const isResearched = researchStatus[unitType];
                const card = document.getElementById(`unit-card-${unitType}`);
                const statusMini = document.getElementById(`status-mini-${unitType}`);
                const researchInfo = document.getElementById(`research-info-${unitType}`);
                const researchBtn = document.getElementById(`research-btn-${unitType}`);
                const trainBtn = document.getElementById(`train-btn-${unitType}`);
                const trainingControls = document.getElementById(`training-controls-${unitType}`);
                const unitStats = card?.querySelector('.unit-stats');
                const unitCount = card?.querySelector('.unit-count');
                
                if (card && statusMini && researchInfo && researchBtn && trainBtn) {
                    if (isResearched) {
                        // Unit is researched - show training controls and stats
                        card.classList.remove('unit-needs-research');
                        card.classList.add('unit-researched');
                        statusMini.textContent = '✅';
                        statusMini.title = 'Research Complete';
                        researchInfo.style.display = 'none';
                        researchBtn.style.display = 'none';
                        trainingControls.style.display = 'block';
                        trainBtn.disabled = false;
                        trainBtn.textContent = 'Train';
                        trainBtn.classList.remove('btn-disabled');
                        
                        // Show unit stats and count when researched
                        if (unitStats) unitStats.style.display = 'block';
                        if (unitCount) unitCount.style.display = 'block';
                    } else {
                        // Unit needs research - hide stats and show research controls  
                        card.classList.add('unit-needs-research');
                        card.classList.remove('unit-researched');
                        statusMini.textContent = '🔬';
                        statusMini.title = 'Research Required';
                        researchInfo.style.display = 'block';
                        researchBtn.style.display = 'block';
                        trainingControls.style.display = 'none';
                        trainBtn.disabled = true;
                        trainBtn.textContent = 'Research Required';
                        trainBtn.classList.add('btn-disabled');
                        
                        // Hide unit stats and count when not researched
                        if (unitStats) unitStats.style.display = 'none';
                        if (unitCount) unitCount.style.display = 'none';
                    }
                }
            });
        }
        
        function updateResearchQueue(queue) {
            const tbody = document.getElementById('researchQueueBody');
            const table = document.getElementById('researchQueueTable');
            const noMessage = document.getElementById('noResearchMessage');
            
            tbody.innerHTML = '';
            
            if (queue.length === 0) {
                table.style.display = 'none';
                noMessage.style.display = 'block';
                return;
            }
            
            table.style.display = 'table';
            noMessage.style.display = 'none';
            
            queue.forEach(item => {
                const row = tbody.insertRow();
                
                // Unit type
                const unitCell = row.insertCell(0);
                unitCell.textContent = item.unitType.charAt(0).toUpperCase() + item.unitType.slice(1);
                
                // Quantity (for research, this is always 1)
                const quantityCell = row.insertCell(1);
                quantityCell.textContent = '1 unit';
                
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
                progressFill.style.backgroundColor = '#3498db';
                progressFill.style.height = '100%';
                progressFill.style.borderRadius = '4px';
                
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
                
                if (item.remainingTimeSeconds > 0) {
                    const remainingDiv = document.createElement('div');
                    remainingDiv.style.fontSize = '12px';
                    remainingDiv.style.color = '#666';
                    const hours = Math.floor(item.remainingTimeSeconds / 3600);
                    const minutes = Math.floor((item.remainingTimeSeconds % 3600) / 60);
                    
                    if (hours > 0) {
                        remainingDiv.textContent = `(${hours}h ${minutes}m remaining)`;
                    } else {
                        remainingDiv.textContent = `(${minutes}m remaining)`;
                    }
                    timeCell.appendChild(remainingDiv);
                }
            });
        }
        
        function updateResearchButtonStates(researchQueue) {
            // Transform research buttons into progress bars when research is active
            const allUnitTypes = ['guards', 'soldiers', 'archers', 'cavalry'];
            
            // Ensure researchQueue is an array
            if (!Array.isArray(researchQueue)) {
                researchQueue = [];
            }
            
            allUnitTypes.forEach(unitType => {
                const researchBtn = document.getElementById(`research-btn-${unitType}`);
                const researchInfo = document.getElementById(`research-info-${unitType}`);
                
                if (!researchBtn || !researchInfo) return;
                
                // Check if this unit is currently being researched
                const activeResearch = researchQueue.find(item => item.unitType === unitType);
                
                if (activeResearch) {
                    // Transform button into progress bar
                    researchBtn.style.display = 'none';
                    
                    // Create or update progress bar in the research info section
                    let progressContainer = document.getElementById(`research-progress-${unitType}`);
                    if (!progressContainer) {
                        progressContainer = document.createElement('div');
                        progressContainer.id = `research-progress-${unitType}`;
                        progressContainer.className = 'research-progress-container';
                        progressContainer.style.marginTop = '10px';
                        
                        const progressLabel = document.createElement('div');
                        progressLabel.textContent = 'Research in Progress';
                        progressLabel.style.fontSize = '13px';
                        progressLabel.style.fontWeight = 'bold';
                        progressLabel.style.marginBottom = '5px';
                        progressLabel.style.color = '#3498db';
                        
                        const progressBar = document.createElement('div');
                        progressBar.className = 'progress-bar-container';
                        progressBar.style.width = '100%';
                        progressBar.style.backgroundColor = '#e0e0e0';
                        progressBar.style.borderRadius = '4px';
                        progressBar.style.height = '25px';
                        progressBar.style.position = 'relative';
                        progressBar.style.border = '1px solid #ccc';
                        
                        const progressFill = document.createElement('div');
                        progressFill.className = 'progress-fill';
                        progressFill.style.backgroundColor = '#3498db';
                        progressFill.style.height = '100%';
                        progressFill.style.borderRadius = '4px';
                        progressFill.style.transition = 'width 0.3s ease';
                        
                        const progressText = document.createElement('span');
                        progressText.className = 'progress-text';
                        progressText.style.position = 'absolute';
                        progressText.style.left = '50%';
                        progressText.style.top = '50%';
                        progressText.style.transform = 'translate(-50%, -50%)';
                        progressText.style.fontSize = '12px';
                        progressText.style.fontWeight = 'bold';
                        progressText.style.color = 'white';
                        progressText.style.textShadow = '1px 1px 1px rgba(0,0,0,0.5)';
                        
                        progressBar.appendChild(progressFill);
                        progressBar.appendChild(progressText);
                        progressContainer.appendChild(progressLabel);
                        progressContainer.appendChild(progressBar);
                        
                        researchInfo.appendChild(progressContainer);
                    }
                    
                    // Update progress bar
                    const progressFill = progressContainer.querySelector('.progress-fill');
                    const progressText = progressContainer.querySelector('.progress-text');
                    const percentage = Math.max(0, Math.min(100, activeResearch.completionPercentage || 0));
                    
                    if (progressFill) {
                        progressFill.style.width = `${percentage}%`;
                    }
                    if (progressText) {
                        progressText.textContent = `${Math.round(percentage)}%`;
                    }
                } else {
                    // Remove progress bar if research is not active
                    const progressContainer = document.getElementById(`research-progress-${unitType}`);
                    if (progressContainer) {
                        progressContainer.remove();
                    }
                    
                    // Check if unit is already researched to determine button visibility
                    const currentSettlementId = new URLSearchParams(window.location.search).get('settlementId');
                    if (currentSettlementId) {
                        fetch(`../php/backend.php?settlementId=${currentSettlementId}&getUnitResearch=true`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.unitResearch && data.unitResearch.research) {
                                    const isResearched = data.unitResearch.research[unitType];
                                    if (!isResearched) {
                                        researchBtn.style.display = 'block';
                                    }
                                }
                            })
                            .catch(error => console.error('Error checking research status:', error));
                    }
                }
            });
        }
        
        function startResearch(unitType, settlementId) {
            // First check if button is disabled (insufficient resources)
            const researchBtn = document.getElementById(`research-btn-${unitType}`);
            if (researchBtn && researchBtn.disabled) {
                alert('Not enough resources for research. Please gather more resources first.');
                return;
            }
            
            fetch(`../php/backend.php?settlementId=${settlementId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    action: 'startResearch',
                    unitType: unitType
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Research started for ${unitType}!`);
                    loadResearchData(settlementId);
                    fetchResources(settlementId);
                } else {
                    alert('Research failed: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error starting research:', error);
                alert('Error starting research. Please try again.');
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
                setInterval(() => {
                    loadMilitaryData(settlementId);
                    updateResearchValidation(settlementId); // Update research validation periodically
                }, 5000);
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