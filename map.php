<?php
// API-Endpunkt
$url = 'http://localhost/game/backend.php?settlementId=1&getMap=True';

// Daten von backend.php abrufen
$response = file_get_contents($url);
if ($response === FALSE) {
    die('Fehler beim Abrufen der Kartendaten');
}

// JSON-Daten dekodieren
$data = json_decode($response, true);
if ($data === NULL) {
    die('Fehler beim Dekodieren der JSON-Daten');
}

// Extrahierte Karten-Daten
$mapData = $data['info']['map'] ?? [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <h1>Karte Übersicht</h1>
    </header>
    <div class="grid">
        <?php foreach ($mapData as $point): ?>
            <div 
                class="settlement" 
                style="left: <?= ($point['xCoordinate'] + 10) * 20 ?>px; 
                       top: <?= (10 - $point['yCoordinate']) * 20 ?>px;">
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>