<?php
    require_once 'php/database.php';
    
    // Get building types from database instead of hardcoding
    $database = new Database();
    $buildingTypesData = $database->getDistinctBuildingTypes();
    
    // Transform the data to match the expected format
    $buildings = array_map(function($type) {
        return [
            'name' => $type['buildingType'],
            'id' => strtolower($type['buildingType'])
        ];
    }, $buildingTypesData);

    // Eingehende Anfrage verarbeiten
    $method = $_SERVER['REQUEST_METHOD'];
    $settlementId = $_GET['settlementId'] ?? null;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siedlungsaufbau</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <section class="buildings">
        <table>
            <thead>
                <tr>
                    <th>Gebäude</th>
                    <th>Stufe</th>
                    <th>Fortschritt</th>
                    <th>Endzeitpunkt</th>
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
                <th>Gebäude</th>
                <th>Stufe</th>
                <th>Kosten</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($buildings as $building): ?>
                <tr>
                    <td><?= htmlspecialchars($building['name']) ?></td>
                    <td><span id="<?= htmlspecialchars($building['id']) ?>">0</span></td>
                    <td>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenHolz">0 Holz</span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenStein">0 Stein</span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenErz">0 Erz</span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>KostenSiedler">0 Siedler</span>
                        <span class="cost-box" id="<?= htmlspecialchars($building['id']) ?>Bauzeit">0s Bauzeit</span>
                    </td>
                    <td style="text-align: right;">
                        <!-- Button with a unique ID -->
                        <button id="<?= htmlspecialchars($building['id']) ?>upgradeButton" 
                            onclick="upgradeBuilding('<?= htmlspecialchars($building['id']) ?>','<?= htmlspecialchars($settlementId) ?>')">
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
