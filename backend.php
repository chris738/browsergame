<?php

// Header für JSON-Antwort
header('Content-Type: application/json');

// Simulierte Datenbank (in einer echten Anwendung durch Datenbankzugriff ersetzen)
$resources = [
    'holz' => 100,
    'stein' => 200,
    'erz' => 150,
];

$buildings = [
    'holzfaeller' => ['level' => 1, 'productionRate' => 1, 'upgradeCost' => ['holz' => 50, 'stein' => 30, 'erz' => 20]],
    'steinbruch' => ['level' => 1, 'productionRate' => 1, 'upgradeCost' => ['holz' => 30, 'stein' => 50, 'erz' => 25]],
    'erzbergwerk' => ['level' => 1, 'productionRate' => 1, 'upgradeCost' => ['holz' => 40, 'stein' => 30, 'erz' => 50]],
];

// Letztes Update-Zeitstempel
$lastUpdate = time();

// Funktion zum Aktualisieren der Ressourcen basierend auf Produktionsraten
function updateResources(&$resources, $buildings, $lastUpdate) {
    $currentTime = time();
    $elapsedTime = $currentTime - $lastUpdate;

    foreach ($buildings as $building) {
        foreach ($resources as $resource => &$amount) {
            if (isset($building['productionRate'])) {
                $amount += $building['productionRate'] * $elapsedTime * $building['level'];
            }
        }
    }

    return $currentTime;
}

// Funktion zum Laden der Daten
function getData($resources, $buildings) {
    return [
        'resources' => $resources,
        'buildings' => array_map(function ($building) {
            return [
                'level' => $building['level'], 
                'productionRate' => $building['productionRate'], 
                'upgradeCost' => $building['upgradeCost']
            ];
        }, $buildings),
    ];
}

// Funktion zum Upgrade eines Gebäudes
function upgradeBuilding($buildingName, &$resources, &$buildings) {
    if (!isset($buildings[$buildingName])) {
        return ['success' => false, 'message' => 'Ungültiges Gebäude'];
    }

    $building = $buildings[$buildingName];
    $cost = $building['upgradeCost'];

    // Prüfen, ob genügend Ressourcen vorhanden sind
    foreach ($cost as $resource => $amount) {
        if ($resources[$resource] < $amount) {
            return ['success' => false, 'message' => 'Nicht genügend Ressourcen'];
        }
    }

    // Ressourcen abziehen
    foreach ($cost as $resource => $amount) {
        $resources[$resource] -= $amount;
    }

    // Gebäude upgraden
    $buildings[$buildingName]['level']++;
    $buildings[$buildingName]['productionRate']++;
    $buildings[$buildingName]['upgradeCost'] = array_map(function ($cost) {
        return (int)($cost * 1.5); // Upgrade-Kosten steigen
    }, $cost);

    return ['success' => true, 'message' => 'Gebäude erfolgreich geupgraded'];
}

// Eingehende Anfrage verarbeiten
$method = $_SERVER['REQUEST_METHOD'];
$lastUpdate = updateResources($resources, $buildings, $lastUpdate);

if ($method === 'GET') {
    // Ressourcen und Gebäudeinformationen zurückgeben
    echo json_encode(getData($resources, $buildings));
} elseif ($method === 'POST') {
    // JSON-Daten aus dem Request lesen
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['building'])) {
        $result = upgradeBuilding($input['building'], $resources, $buildings);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Methode nicht unterstützt']);
}

?>
