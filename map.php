<?php
// Simplified map with static data for now (can be enhanced later with proper database calls)
$mapData = [
    ['xCoordinate' => 0, 'yCoordinate' => 0],
    ['xCoordinate' => 1, 'yCoordinate' => 1],
    ['xCoordinate' => -1, 'yCoordinate' => -1],
    ['xCoordinate' => 2, 'yCoordinate' => 0],
    ['xCoordinate' => 0, 'yCoordinate' => 2],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karte - Siedlungsaufbau</title>
    <link rel="stylesheet" href="style.css">
    <script src="backend.js" defer></script>
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <main class="main-content">
        <h2>Kartenansicht</h2>
        <p>Hier siehst du die in der Nähe gelegenen Siedlungen:</p>
        
        <div class="map-container">
            <div class="grid">
                <?php foreach ($mapData as $point): ?>
                    <div 
                        class="settlement" 
                        style="left: <?= ($point['xCoordinate'] + 10) * 20 ?>px; 
                               top: <?= (10 - $point['yCoordinate']) * 20 ?>px;"
                        title="Siedlung bei (<?= $point['xCoordinate'] ?>, <?= $point['yCoordinate'] ?>)">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>