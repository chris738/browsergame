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
            // For now, just show an alert - this would connect to backend later
            alert(`Training ${unitType} in settlement ${settlementId}. This feature will be fully implemented in the next update!`);
        }
        
        // Initialize military stats (placeholder)
        document.addEventListener('DOMContentLoaded', function() {
            // These would be loaded from the backend in a full implementation
            document.getElementById('guards-count').textContent = '0';
            document.getElementById('soldiers-count').textContent = '0';
            document.getElementById('archers-count').textContent = '0';
            document.getElementById('cavalry-count').textContent = '0';
            
            document.getElementById('total-defense').textContent = '0';
            document.getElementById('total-attack').textContent = '0';
            document.getElementById('total-units').textContent = '0';
            document.getElementById('ranged-power').textContent = '0';
        });
    </script>
</body>
</html>