<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    // Process incoming requests
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
    $targetSettlementId = $_GET['target'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle - Command Center</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <div class="main-content">
        <h2><?= EmojiConfig::getBuildingEmoji('kaserne') ?> Battle - Command Center</h2>
        <p>Launch strategic attacks against other settlements using your trained military units. Victory brings glory and valuable resources!</p>
    </div>
    
    <!-- Military Power Overview Section -->
    <section class="buildings">
        <h3>🏴‍☠️ Your Military Power</h3>
        <div id="militaryPowerDisplay">
            <div class="loading-state">
                <span class="loading-icon">⏳</span>
                <p>Loading military information...</p>
            </div>
        </div>
    </section>
    
    <!-- Traveling Armies Section -->
    <section class="buildings">
        <h3>⚔️ Armies in Transit</h3>
        <div id="travelingArmiesDisplay">
            <div class="loading-state">
                <span class="loading-icon">⏳</span>
                <p>Loading traveling armies...</p>
            </div>
        </div>
    </section>
    
    <!-- Target Selection Section -->
    <section class="buildings">
        <h3>🎯 Target Selection</h3>
        <table>
            <thead>
                <tr>
                    <th>Target Settlement</th>
                    <th>Coordinates</th>
                    <th>Owner</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr id="targetSelectionRow">
                    <td colspan="4" class="target-selection-cell">
                        <div class="target-selection-content">
                            <button onclick="selectFromMap()" class="select-from-map-btn-primary">
                                <span class="btn-icon">🗺️</span>
                                Select Target from Map
                            </button>
                            <p class="selection-instruction">Click to open the map and select an enemy settlement to attack</p>
                        </div>
                    </td>
                </tr>
                <tr id="selectedTargetRow" style="display: none;">
                    <td id="selectedTargetName">-</td>
                    <td id="selectedTargetCoords">-</td>
                    <td id="selectedTargetOwner">-</td>
                    <td>
                        <button onclick="clearTarget()" class="clear-target-btn">
                            ❌ Clear Selection
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>
    
    <!-- Attack Planning Section -->
    <section class="buildings">
        <h3>⚔️ Launch Attack</h3>
        
        <!-- Unit Selection -->
        <table>
            <thead>
                <tr>
                    <th>Unit Type</th>
                    <th>Available</th>
                    <th>Send to Battle</th>
                    <th>Attack Power</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('guards') ?></span>
                        <span class="unit-name">Guards</span>
                    </td>
                    <td><span id="availableGuards">0</span></td>
                    <td>
                        <input type="number" id="attackGuards" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                    </td>
                    <td><span id="guardsAttackPower">0</span></td>
                </tr>
                
                <tr>
                    <td>
                        <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('soldiers') ?></span>
                        <span class="unit-name">Soldiers</span>
                    </td>
                    <td><span id="availableSoldiers">0</span></td>
                    <td>
                        <input type="number" id="attackSoldiers" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                    </td>
                    <td><span id="soldiersAttackPower">0</span></td>
                </tr>
                
                <tr>
                    <td>
                        <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('archers') ?></span>
                        <span class="unit-name">Archers</span>
                    </td>
                    <td><span id="availableArchers">0</span></td>
                    <td>
                        <input type="number" id="attackArchers" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                    </td>
                    <td><span id="archersAttackPower">0</span></td>
                </tr>
                
                <tr>
                    <td>
                        <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('cavalry') ?></span>
                        <span class="unit-name">Cavalry</span>
                    </td>
                    <td><span id="availableCavalry">0</span></td>
                    <td>
                        <input type="number" id="attackCavalry" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                    </td>
                    <td><span id="cavalryAttackPower">0</span></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Attack Summary -->
        <div class="attack-summary">
            <div class="summary-stats">
                <div class="summary-item">
                    <span class="summary-icon">💪</span>
                    <div class="summary-content">
                        <span class="summary-label">Total Attack Power</span>
                        <span class="summary-value" id="totalAttackPower">0</span>
                    </div>
                </div>
                <div class="summary-item">
                    <span class="summary-icon">👥</span>
                    <div class="summary-content">
                        <span class="summary-label">Units Selected</span>
                        <span class="summary-value" id="totalUnitsSelected">0</span>
                    </div>
                </div>
            </div>
            
            <button id="launchAttackBtn" onclick="launchAttack()" class="launch-attack-btn" disabled>
                <span class="btn-icon">🚀</span>
                Launch Attack
            </button>
        </div>
    </section>
    
    <!-- Battle History Section -->
    <section class="buildings">
        <h3>📜 Recent Battles</h3>
        <div id="battleHistory" class="battle-history">
            <div class="loading-state">
                <span class="loading-icon">⏳</span>
                <p>Loading battle history...</p>
            </div>
        </div>
    </section>

<script>
let currentSettlementId = '<?= htmlspecialchars($settlementId ?? '1') ?>';
let preselectedTargetId = '<?= htmlspecialchars($targetSettlementId ?? '') ?>';
let availableUnits = {guards: 0, soldiers: 0, archers: 0, cavalry: 0};
let attackableSettlements = [];
let militaryPower = {};
let selectedTarget = null;

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    if (currentSettlementId) {
        loadMilitaryPower();
        loadAttackableSettlements();
        loadBattleHistory();
        loadTravelingArmies();
        
        // If a target was preselected from URL, handle it
        if (preselectedTargetId) {
            // Wait for settlements to load, then select the target
            setTimeout(() => {
                const target = attackableSettlements.find(s => s.settlementId == preselectedTargetId);
                if (target) {
                    selectTarget(target);
                }
            }, 1000);
        }
        
        // Auto-refresh traveling armies every 30 seconds
        setInterval(loadTravelingArmies, 30000);
    }
});

function loadMilitaryPower() {
    fetch(`php/battle-backend.php?action=getMilitaryPower&settlementId=${currentSettlementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                militaryPower = data.power;
                availableUnits = data.power.units;
                updateMilitaryPowerDisplay();
                updateUnitInputs();
            } else {
                console.error('Failed to load military power:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading military power:', error);
        });
}

function updateMilitaryPowerDisplay() {
    const display = document.getElementById('militaryPowerDisplay');
    display.innerHTML = `
        <div class="power-stats">
            <div class="stat">
                <label>Total Attack Power:</label>
                <span>${militaryPower.totalAttack}</span>
            </div>
            <div class="stat">
                <label>Total Defense Power:</label>
                <span>${militaryPower.totalDefense}</span>
            </div>
            <div class="stat">
                <label>Ranged Power:</label>
                <span>${militaryPower.totalRanged}</span>
            </div>
        </div>
        <div class="unit-counts">
            <p><strong>Available Units:</strong></p>
            <p>Guards: ${availableUnits.guards} | Soldiers: ${availableUnits.soldiers} | Archers: ${availableUnits.archers} | Cavalry: ${availableUnits.cavalry}</p>
        </div>
    `;
}

function updateUnitInputs() {
    document.getElementById('availableGuards').textContent = availableUnits.guards;
    document.getElementById('availableSoldiers').textContent = availableUnits.soldiers;
    document.getElementById('availableArchers').textContent = availableUnits.archers;
    document.getElementById('availableCavalry').textContent = availableUnits.cavalry;
    
    document.getElementById('attackGuards').max = availableUnits.guards;
    document.getElementById('attackSoldiers').max = availableUnits.soldiers;
    document.getElementById('attackArchers').max = availableUnits.archers;
    document.getElementById('attackCavalry').max = availableUnits.cavalry;
}

function loadAttackableSettlements() {
    fetch(`php/battle-backend.php?action=getAttackableSettlements&settlementId=${currentSettlementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                attackableSettlements = data.settlements;
                // No need to update dropdown since we removed it
            } else {
                console.error('Failed to load attackable settlements:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading attackable settlements:', error);
        });
}

function selectTarget(target) {
    selectedTarget = target;
    
    // Update the UI to show selected target
    document.getElementById('targetSelectionRow').style.display = 'none';
    document.getElementById('selectedTargetRow').style.display = '';
    document.getElementById('selectedTargetName').textContent = target.settlementName;
    document.getElementById('selectedTargetCoords').textContent = `(${target.coordinateX}, ${target.coordinateY})`;
    document.getElementById('selectedTargetOwner').textContent = target.playerName || 'Unknown';
    
    // Re-validate unit selection to enable/disable launch button
    validateUnitSelection();
}

function clearTarget() {
    selectedTarget = null;
    
    // Update the UI to show selection prompt
    document.getElementById('targetSelectionRow').style.display = '';
    document.getElementById('selectedTargetRow').style.display = 'none';
    
    // Re-validate unit selection to disable launch button
    validateUnitSelection();
}

function validateUnitSelection() {
    const guards = parseInt(document.getElementById('attackGuards').value) || 0;
    const soldiers = parseInt(document.getElementById('attackSoldiers').value) || 0;
    const archers = parseInt(document.getElementById('attackArchers').value) || 0;
    const cavalry = parseInt(document.getElementById('attackCavalry').value) || 0;
    
    const totalUnits = guards + soldiers + archers + cavalry;
    const totalAttackPower = (guards * 0) + (soldiers * 3) + (archers * 4) + (cavalry * 5); // Simplified calculation
    
    // Update individual unit power displays
    document.getElementById('guardsAttackPower').textContent = guards * 0;
    document.getElementById('soldiersAttackPower').textContent = soldiers * 3;
    document.getElementById('archersAttackPower').textContent = archers * 4;
    document.getElementById('cavalryAttackPower').textContent = cavalry * 5;
    
    document.getElementById('totalUnitsSelected').textContent = totalUnits;
    document.getElementById('totalAttackPower').textContent = totalAttackPower;
    
    const targetSelected = selectedTarget !== null;
    const unitsSelected = totalUnits > 0;
    
    document.getElementById('launchAttackBtn').disabled = !(targetSelected && unitsSelected);
}

function launchAttack() {
    if (!selectedTarget) {
        alert('Please select a target settlement from the map');
        return;
    }
    
    const units = {
        guards: parseInt(document.getElementById('attackGuards').value) || 0,
        soldiers: parseInt(document.getElementById('attackSoldiers').value) || 0,
        archers: parseInt(document.getElementById('attackArchers').value) || 0,
        cavalry: parseInt(document.getElementById('attackCavalry').value) || 0
    };
    
    const totalUnits = Object.values(units).reduce((a, b) => a + b, 0);
    if (totalUnits === 0) {
        alert('Please select units for the attack');
        return;
    }
    
    if (!confirm(`Are you sure you want to attack ${selectedTarget.settlementName} at (${selectedTarget.coordinateX}, ${selectedTarget.coordinateY}) with ${totalUnits} units? This action cannot be undone!`)) {
        return;
    }
    
    document.getElementById('launchAttackBtn').disabled = true;
    document.getElementById('launchAttackBtn').textContent = 'Attacking...';
    
    fetch('php/battle-backend.php?action=attack', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            attackerSettlementId: currentSettlementId,
            defenderSettlementId: selectedTarget.settlementId,
            units: units
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showBattleResult(data);
            // Reload data after battle
            setTimeout(() => {
                loadMilitaryPower();
                loadBattleHistory();
                resetAttackForm();
            }, 2000);
        } else {
            alert('Attack failed: ' + data.message);
            document.getElementById('launchAttackBtn').disabled = false;
            document.getElementById('launchAttackBtn').textContent = 'Launch Attack';
        }
    })
    .catch(error => {
        console.error('Error launching attack:', error);
        alert('Attack failed due to network error');
        document.getElementById('launchAttackBtn').disabled = false;
        document.getElementById('launchAttackBtn').textContent = 'Launch Attack';
    });
}

function showBattleResult(battleData) {
    const resultMessage = battleData.winner === 'attacker' ? 'Victory!' : 'Defeat!';
    const resultClass = battleData.winner === 'attacker' ? 'victory' : 'defeat';
    
    let message = `<div class="battle-result ${resultClass}">
        <h3>${resultMessage}</h3>
        <h4>Battle Summary:</h4>
        <p><strong>Winner:</strong> ${battleData.winner === 'attacker' ? 'You' : 'Defender'}</p>
        
        <h4>Your Losses:</h4>
        <ul>`;
    
    for (const [unitType, losses] of Object.entries(battleData.attackerLosses)) {
        if (losses > 0) {
            message += `<li>${unitType}: ${losses} units lost</li>`;
        }
    }
    
    if (battleData.winner === 'attacker' && Object.values(battleData.resourcesPlundered).some(v => v > 0)) {
        message += `</ul><h4>Resources Plundered:</h4><ul>`;
        for (const [resource, amount] of Object.entries(battleData.resourcesPlundered)) {
            if (amount > 0) {
                message += `<li>${resource}: ${amount}</li>`;
            }
        }
    }
    
    message += `</ul></div>`;
    
    // Show result in a modal or alert
    const resultDiv = document.createElement('div');
    resultDiv.innerHTML = message;
    resultDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border: 2px solid #333; border-radius: 8px; z-index: 1000; max-width: 400px;';
    
    const closeBtn = document.createElement('button');
    closeBtn.textContent = 'Close';
    closeBtn.onclick = () => document.body.removeChild(resultDiv);
    resultDiv.appendChild(closeBtn);
    
    document.body.appendChild(resultDiv);
}

function resetAttackForm() {
    selectedTarget = null;
    document.getElementById('targetSelectionRow').style.display = '';
    document.getElementById('selectedTargetRow').style.display = 'none';
    document.getElementById('attackGuards').value = 0;
    document.getElementById('attackSoldiers').value = 0;
    document.getElementById('attackArchers').value = 0;
    document.getElementById('attackCavalry').value = 0;
    document.getElementById('launchAttackBtn').disabled = true;
    document.getElementById('launchAttackBtn').textContent = 'Launch Attack';
    validateUnitSelection();
}

function loadBattleHistory() {
    fetch(`php/battle-backend.php?action=getBattleHistory&settlementId=${currentSettlementId}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateBattleHistory(data.battles);
            } else {
                console.error('Failed to load battle history:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading battle history:', error);
        });
}

function updateBattleHistory(battles) {
    const container = document.getElementById('battleHistory');
    
    if (battles.length === 0) {
        container.innerHTML = '<p>No battles yet. Launch your first attack!</p>';
        return;
    }
    
    let html = '<table><thead><tr><th>Date</th><th>Opponent</th><th>Result</th><th>Your Role</th></tr></thead><tbody>';
    
    battles.forEach(battle => {
        const isAttacker = battle.attackerSettlementId == currentSettlementId;
        const opponentName = isAttacker ? battle.defenderName : battle.attackerName;
        const yourRole = isAttacker ? 'Attacker' : 'Defender';
        const result = (battle.winner === 'attacker' && isAttacker) || (battle.winner === 'defender' && !isAttacker) ? 'Victory' : 'Defeat';
        const resultClass = result === 'Victory' ? 'victory' : 'defeat';
        
        html += `<tr>
            <td>${new Date(battle.battleTime).toLocaleString()}</td>
            <td>${opponentName}</td>
            <td class="${resultClass}">${result}</td>
            <td>${yourRole}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function loadTravelingArmies() {
    try {
        const response = await fetch(`php/battle-backend.php?action=getTravelingArmies&settlementId=${currentSettlementId}`);
        const data = await response.json();
        
        if (data.success) {
            displayTravelingArmies(data.armies);
        } else {
            console.error('Failed to load traveling armies:', data.message);
        }
    } catch (error) {
        console.error('Error loading traveling armies:', error);
    }
}

function displayTravelingArmies(armies) {
    const container = document.getElementById('travelingArmiesDisplay');
    
    if (!armies || armies.length === 0) {
        container.innerHTML = '<p>No armies currently traveling</p>';
        return;
    }
    
    let html = '<div class="travel-list">';
    armies.forEach(army => {
        const isOutgoing = army.attackerSettlementId == currentSettlementId;
        const direction = isOutgoing ? 'Outgoing Attack' : 'Incoming Attack';
        const targetName = isOutgoing ? army.defenderName : army.attackerName;
        const timeRemaining = army.timeRemaining > 0 ? formatTime(army.timeRemaining) : 'Arrived';
        
        const unitSummary = [
            army.guardsCount > 0 ? `G:${army.guardsCount}` : null,
            army.soldiersCount > 0 ? `S:${army.soldiersCount}` : null,
            army.archersCount > 0 ? `A:${army.archersCount}` : null,
            army.cavalryCount > 0 ? `C:${army.cavalryCount}` : null
        ].filter(u => u).join(', ');
        
        html += `
            <div class="travel-item ${isOutgoing ? 'outgoing' : 'incoming'}">
                <div class="travel-header">
                    <strong>${direction}</strong>
                    <span class="travel-time">${timeRemaining}</span>
                </div>
                <div class="travel-details">
                    Target: <strong>${targetName || 'Unknown'}</strong><br>
                    Units: ${unitSummary}<br>
                    Distance: ${army.distance} blocks
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function formatTime(seconds) {
    if (seconds <= 0) return 'Arrived';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m ${secs}s`;
    } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
    } else {
        return `${secs}s`;
    }
}

function selectFromMap() {
    // Navigate to map with special parameter to indicate we're selecting a target
    const mapUrl = `map.php?settlementId=${currentSettlementId}&mode=selectTarget&returnTo=battle`;
    window.location.href = mapUrl;
}
</script>

<style>
.travel-list {
    max-height: 300px;
    overflow-y: auto;
}

.travel-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: var(--bg-secondary);
}

.travel-item.outgoing {
    border-left: 4px solid #ff6b6b;
}

.travel-item.incoming {
    border-left: 4px solid #ffa726;
}

.travel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.travel-time {
    font-weight: bold;
    color: #007bff;
}

.travel-details {
    font-size: 0.9rem;
    line-height: 1.4;
}
</style>



</body>
</html>