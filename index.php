<?php
    require_once 'php/database.php';
    require_once 'php/emojis.php';
    
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
        'Farm' => 'Farm'
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
    
    // Get emoji configurations
    $resourceEmojis = Emojis::getResourceEmojis();
    $buildingEmojis = Emojis::getBuildingEmojis();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement Building</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
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
            // Building emoji mapping - now using centralized emojis
            
            foreach ($buildings as $building): 
                $emoji = $buildingEmojis[$building['id']] ?? ['emoji' => Emojis::DEFAULT_BUILDING, 'title' => 'Building'];
            ?>
                <tr>
                    <td>
                        <span class="building-emoji" title="<?= htmlspecialchars($emoji['title']) ?>"><?= $emoji['emoji'] ?></span>
                        <?= htmlspecialchars($building['name']) ?>
                    </td>
                    <td><span id="<?= htmlspecialchars($building['id']) ?>">0</span></td>
                    <td>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenHolz">0 <?= $resourceEmojis['wood'] ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenStein">0 <?= $resourceEmojis['stone'] ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenErz">0 <?= $resourceEmojis['ore'] ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenSiedler">0 <?= $resourceEmojis['settlers'] ?></span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>Bauzeit">0s <?= $resourceEmojis['time'] ?></span>
                    </td>
                    <td style="text-align: right;">
                        <!-- Button with a unique ID -->
                        <button id="<?= htmlspecialchars($building['id']) ?>upgradeButton" 
                            onclick="upgradeBuilding('<?= htmlspecialchars($building['originalName']) ?>','<?= htmlspecialchars($settlementId) ?>')">>
                            Upgrade
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </section>
</body>

<script>
    // Make settlement ID available globally for JavaScript
    const settlementId = <?= $settlementId ?>;
    
    // Make all emojis available globally for JavaScript
    const emojis = <?= Emojis::getAllEmojisAsJS() ?>;
    // Keep backward compatibility
    const resourceEmojis = <?= Emojis::getResourceEmojisAsJS() ?>;
</script>
</html>
