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
        'Markt' => 'Market'
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
    <link rel="stylesheet" href="css/style.css">
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
            ?>
                <tr>
                    <td>
                        <?= EmojiConfig::formatBuildingWithEmoji($buildingId, $buildingName) ?>
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
</html>
