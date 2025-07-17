<?php
    // Liste der Gebäude mit ihren Eigenschaften
    $buildings = [
        //["name" => "Rathaus", "id" => "rathaus"],
        ["name" => "Holzfäller", "id" => "holzfäller"],
        ["name" => "Steinbruch", "id" => "steinbruch"],
        ["name" => "Erzbergwerk", "id" => "erzbergwerk"],
        ["name" => "Lager", "id" => "lager"],
        ["name" => "Farm", "id" => "farm"],
        //["name" => "Kirche", "id" => "kirche"],
        //["name" => "Markt", "id" => "markt"],
        //["name" => "Versteck", "id" => "versteck"],
        //["name" => "Stadtmauer", "id" => "mauer"],
        //["name" => "Stall", "id" => "stall"],
        //["name" => "Kaserne", "id" => "kaserne"],
        //["name" => "Universität", "id" => "uni"],
    ];

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
    <link rel="stylesheet" href="style.css">
    <script src="backend.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; ?>
    
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
