<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    // Get building types from database instead of hardcoding
    $database = new Database();
    $buildingTypesData = $database->getDistinctBuildingTypes();
    
    // Transform the data to match the expected format and translate to English
    $buildingTranslations = [
        'Rathaus' => 'Town Hall',
        'Holzfäller' => 'Lumberjack',
        'Steinbruch' => 'Quarry',
        'Erzbergwerk' => 'Mine',
        'Lager' => 'Storage',
        'Farm' => 'Farm',
        'Markt' => 'Market',
        'Kaserne' => 'Barracks'
    ];
    
    $buildings = array_map(function($type) use ($buildingTranslations) {
        $translatedName = $buildingTranslations[$type['buildingType']] ?? $type['buildingType'];
        return [
            'name' => $translatedName,
            'id' => strtolower($type['buildingType']),
            'originalName' => $type['buildingType'] // Keep original for backend communication
        ];
    }, $buildingTypesData);

    // Eingehende Anfrage verarbeiten
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Building</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
    <script src="js/translations.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <section class="buildings">
        <table>
            <thead>
                <tr>
                    <th>Building</th>
                    <th>Level</th>
                    <th>Progress</th>
                    <th>End Time</th>
                </tr>
            </thead>
            <tbody id="buildingQueueBody">

            </tbody>
        </table>
    </section>


    <section class="buildings">
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
            <?php 
            foreach ($buildings as $building): 
                $buildingId = $building['id'];
                $buildingName = $building['name'];
                $originalName = $building['originalName'];
                
                // Define building requirements
                $requirements = [];
                $requirementText = '';
                
                if ($originalName === 'Kaserne') {
                    $requirements = ['farm' => 5];
                    $requirementText = 'Requires Farm Level 5';
                } elseif ($originalName === 'Markt') {
                    $requirements = ['lager' => 5];
                    $requirementText = 'Requires Storage Level 5';
                }
            ?>
                <tr data-building="<?= htmlspecialchars($buildingId) ?>" 
                    data-requirements='<?= json_encode($requirements) ?>'>
                    <td>
                        <?= EmojiConfig::formatBuildingWithEmoji($buildingId, $buildingName) ?>
                        <?php if ($requirementText): ?>
                            <br><small style="color: #666; font-style: italic;"><?= $requirementText ?></small>
                        <?php endif; ?>
                    </td>
                    <td><span id="<?= htmlspecialchars($buildingId) ?>">0</span></td>
                    <td>
                        <span class="cost-box" id="<?= htmlspecialchars($buildingId) ?>KostenHolz">0 <?= EmojiConfig::getResourceEmoji('wood') ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($buildingId) ?>KostenStein">0 <?= EmojiConfig::getResourceEmoji('stone') ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($buildingId) ?>KostenErz">0 <?= EmojiConfig::getResourceEmoji('ore') ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($buildingId) ?>KostenSiedler">0 <?= EmojiConfig::getResourceEmoji('settlers') ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($buildingId) ?>Bauzeit">0s <?= EmojiConfig::getUIEmoji('time') ?></span>
                    </td>
                    <td style="text-align: right;">
                        <!-- Button with a unique ID -->
                        <button id="<?= htmlspecialchars($buildingId) ?>upgradeButton" 
                            onclick="upgradeBuilding('<?= htmlspecialchars($building['originalName']) ?>','<?= htmlspecialchars($settlementId) ?>')"
                            class="building-upgrade-btn">
                            Upgrade
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </section>

<script>
// Function to check and update building requirements
function checkBuildingRequirements() {
    const buildingRows = document.querySelectorAll('tr[data-requirements]');
    
    buildingRows.forEach(row => {
        const buildingId = row.getAttribute('data-building');
        const requirements = JSON.parse(row.getAttribute('data-requirements') || '{}');
        const upgradeButton = document.getElementById(buildingId + 'upgradeButton');
        
        if (Object.keys(requirements).length === 0) {
            // No requirements, always enabled
            return;
        }
        
        let requirementsMet = true;
        let requirementText = '';
        
        for (const [requiredBuilding, requiredLevel] of Object.entries(requirements)) {
            const currentLevel = parseInt(document.getElementById(requiredBuilding)?.textContent || '0');
            if (currentLevel < requiredLevel) {
                requirementsMet = false;
                const buildingNames = {
                    'farm': 'Farm',
                    'lager': 'Storage'
                };
                requirementText = `Requires ${buildingNames[requiredBuilding] || requiredBuilding} Level ${requiredLevel} (current: ${currentLevel})`;
                break;
            }
        }
        
        if (upgradeButton) {
            if (requirementsMet) {
                upgradeButton.disabled = false;
                upgradeButton.style.opacity = '1';
                upgradeButton.title = '';
            } else {
                upgradeButton.disabled = true;
                upgradeButton.style.opacity = '0.5';
                upgradeButton.title = requirementText;
            }
        }
        
        // Hide the building row only if requirements aren't met and it has never been unlocked
        const currentLevel = parseInt(document.getElementById(buildingId)?.textContent || '0');
        // Check if this building was previously unlocked (stored in sessionStorage)
        const wasUnlocked = sessionStorage.getItem(`building_${buildingId}_unlocked`) === 'true';
        
        if (!requirementsMet && currentLevel === 0 && !wasUnlocked) {
            row.style.display = 'none';
        } else {
            row.style.display = '';
            // Mark as unlocked once requirements are met
            if (requirementsMet) {
                sessionStorage.setItem(`building_${buildingId}_unlocked`, 'true');
            }
        }
    });
}

// Override the existing resource update function to include requirement checking
const originalFetchResources = window.fetchResources;
if (originalFetchResources) {
    window.fetchResources = function(settlementId) {
        originalFetchResources(settlementId);
        // Add a small delay to ensure resources are updated first
        setTimeout(checkBuildingRequirements, 100);
    };
}

// Check requirements when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(checkBuildingRequirements, 500); // Give time for initial data to load
    // Check periodically
    setInterval(checkBuildingRequirements, 2000);
});
</script>
</body>
</html>
