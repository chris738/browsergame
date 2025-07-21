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
    <link rel="stylesheet" href="css/style.css">
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
    
    <!-- Attack Planning Section -->
    <section class="buildings">
        <h3>🎯 Launch Attack</h3>
        
        <!-- Target Selection -->
        <div class="form-group">
            <label for="targetSelect">
                <span class="label-icon">🏛️</span>
                Select Target Settlement
            </label>
            <div class="target-selection-container">
                <select id="targetSelect" onchange="updateTargetInfo()" class="target-select">
                    <option value="">-- Choose your target --</option>
                </select>
                <button onclick="selectFromMap()" class="select-from-map-btn">
                    <span class="btn-icon">🗺️</span>
                    Select from Map
                </button>
            </div>
        </div>
        
        <div id="targetInfo" class="target-info" style="display: none;">
            <h4>ℹ️ Target Information</h4>
            <div id="targetDetails" class="target-details"></div>
        </div>
        
        <!-- Unit Selection -->
        <h4>⚔️ Select Units for Attack</h4>
        <div class="units-grid">
                    <div class="unit-card">
                        <div class="unit-header">
                            <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('guards') ?></span>
                            <span class="unit-name">Guards</span>
                        </div>
                        <div class="unit-stats">
                            <div class="stat">
                                <span class="stat-label">Available:</span>
                                <span class="stat-value" id="availableGuards">0</span>
                            </div>
                            <div class="unit-input">
                                <label>Send to Battle:</label>
                                <input type="number" id="attackGuards" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="unit-card">
                        <div class="unit-header">
                            <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('soldiers') ?></span>
                            <span class="unit-name">Soldiers</span>
                        </div>
                        <div class="unit-stats">
                            <div class="stat">
                                <span class="stat-label">Available:</span>
                                <span class="stat-value" id="availableSoldiers">0</span>
                            </div>
                            <div class="unit-input">
                                <label>Send to Battle:</label>
                                <input type="number" id="attackSoldiers" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="unit-card">
                        <div class="unit-header">
                            <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('archers') ?></span>
                            <span class="unit-name">Archers</span>
                        </div>
                        <div class="unit-stats">
                            <div class="stat">
                                <span class="stat-label">Available:</span>
                                <span class="stat-value" id="availableArchers">0</span>
                            </div>
                            <div class="unit-input">
                                <label>Send to Battle:</label>
                                <input type="number" id="attackArchers" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                            </div>
                        </div>
                    </div>
                    
                    <div class="unit-card">
                        <div class="unit-header">
                            <span class="unit-icon"><?= EmojiConfig::getUnitEmoji('cavalry') ?></span>
                            <span class="unit-name">Cavalry</span>
                        </div>
                        <div class="unit-stats">
                            <div class="stat">
                                <span class="stat-label">Available:</span>
                                <span class="stat-value" id="availableCavalry">0</span>
                            </div>
                            <div class="unit-input">
                                <label>Send to Battle:</label>
                                <input type="number" id="attackCavalry" min="0" max="0" value="0" onchange="validateUnitSelection()" class="unit-count-input">
                            </div>
                        </div>
                    </div>
                </div>
                
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
let currentSettlementId = '<?= htmlspecialchars($settlementId) ?>';
let preselectedTargetId = '<?= htmlspecialchars($targetSettlementId) ?>';
let availableUnits = {guards: 0, soldiers: 0, archers: 0, cavalry: 0};
let attackableSettlements = [];
let militaryPower = {};

// Load initial data
document.addEventListener('DOMContentLoaded', function() {
    if (currentSettlementId) {
        loadMilitaryPower();
        loadAttackableSettlements();
        loadBattleHistory();
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
                updateTargetSelect();
            } else {
                console.error('Failed to load attackable settlements:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading attackable settlements:', error);
        });
}

function updateTargetSelect() {
    const select = document.getElementById('targetSelect');
    select.innerHTML = '<option value="">-- Choose a target --</option>';
    
    attackableSettlements.forEach(settlement => {
        const option = document.createElement('option');
        option.value = settlement.settlementId;
        option.textContent = `${settlement.settlementName} (${settlement.coordinateX}, ${settlement.coordinateY})`;
        
        // Pre-select target if specified in URL
        if (preselectedTargetId && settlement.settlementId == preselectedTargetId) {
            option.selected = true;
        }
        
        select.appendChild(option);
    });
    
    // If a target was pre-selected, update the target info
    if (preselectedTargetId && select.value) {
        updateTargetInfo();
    }
}

function updateTargetInfo() {
    const targetId = document.getElementById('targetSelect').value;
    const targetInfo = document.getElementById('targetInfo');
    
    if (targetId) {
        const target = attackableSettlements.find(s => s.settlementId == targetId);
        if (target) {
            document.getElementById('targetDetails').innerHTML = `
                <p><strong>Settlement:</strong> ${target.settlementName}</p>
                <p><strong>Coordinates:</strong> (${target.coordinateX}, ${target.coordinateY})</p>
                <p><em>Gather intelligence before attacking!</em></p>
            `;
            targetInfo.style.display = 'block';
        }
    } else {
        targetInfo.style.display = 'none';
    }
}

function validateUnitSelection() {
    const guards = parseInt(document.getElementById('attackGuards').value) || 0;
    const soldiers = parseInt(document.getElementById('attackSoldiers').value) || 0;
    const archers = parseInt(document.getElementById('attackArchers').value) || 0;
    const cavalry = parseInt(document.getElementById('attackCavalry').value) || 0;
    
    const totalUnits = guards + soldiers + archers + cavalry;
    const totalAttackPower = (guards * 0) + (soldiers * 3) + (archers * 4) + (cavalry * 5); // Simplified calculation
    
    document.getElementById('totalUnitsSelected').textContent = totalUnits;
    document.getElementById('totalAttackPower').textContent = totalAttackPower;
    
    const targetSelected = document.getElementById('targetSelect').value !== '';
    const unitsSelected = totalUnits > 0;
    
    document.getElementById('launchAttackBtn').disabled = !(targetSelected && unitsSelected);
}

function launchAttack() {
    const targetId = document.getElementById('targetSelect').value;
    const units = {
        guards: parseInt(document.getElementById('attackGuards').value) || 0,
        soldiers: parseInt(document.getElementById('attackSoldiers').value) || 0,
        archers: parseInt(document.getElementById('attackArchers').value) || 0,
        cavalry: parseInt(document.getElementById('attackCavalry').value) || 0
    };
    
    if (!targetId) {
        alert('Please select a target settlement');
        return;
    }
    
    const totalUnits = Object.values(units).reduce((a, b) => a + b, 0);
    if (totalUnits === 0) {
        alert('Please select units for the attack');
        return;
    }
    
    if (!confirm(`Are you sure you want to attack with ${totalUnits} units? This action cannot be undone!`)) {
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
            defenderSettlementId: targetId,
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
    document.getElementById('targetSelect').value = '';
    document.getElementById('attackGuards').value = 0;
    document.getElementById('attackSoldiers').value = 0;
    document.getElementById('attackArchers').value = 0;
    document.getElementById('attackCavalry').value = 0;
    document.getElementById('targetInfo').style.display = 'none';
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

function selectFromMap() {
    // Navigate to map with special parameter to indicate we're selecting a target
    const mapUrl = `map.php?settlementId=${currentSettlementId}&mode=selectTarget&returnTo=battle`;
    window.location.href = mapUrl;
}
</script>

<style>
.battle-result.victory {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}

.battle-result.defeat {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}

.attack-form {
    max-width: 600px;
}

.target-selection-container {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.target-selection-container .target-select {
    flex: 1;
}

.select-from-map-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    white-space: nowrap;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
    height: 34px; /* Match select height */
}

.select-from-map-btn:hover {
    background: #0056b3;
}

.select-from-map-btn .btn-icon {
    font-size: 16px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.unit-selection table {
    width: 100%;
    margin-bottom: 15px;
}

.unit-selection input[type="number"] {
    width: 80px;
    padding: 4px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.attack-summary {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
    margin-bottom: 15px;
}

.power-stats {
    display: flex;
    gap: 20px;
    margin-bottom: 10px;
}

.power-stats .stat {
    flex: 1;
}

.power-stats .stat label {
    font-weight: bold;
}

.victory {
    color: #28a745;
}

.defeat {
    color: #dc3545;
}

#launchAttackBtn {
    background-color: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

#launchAttackBtn:hover:not(:disabled) {
    background-color: #c82333;
}

#launchAttackBtn:disabled {
    background-color: #6c757d;
    cursor: not-allowed;
}
</style>

</body>
</html>