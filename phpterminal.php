<?php
// Autoload or include your database connection and class files
require_once 'database.php'; // Anpassen, falls notwendig

function fetchResources($settlementId) {
    $database = new Database(); // Deine Datenbankklasse instanziieren
    $resources = $database->getResources($settlementId);

    if (!$resources) {
        return ['error' => 'Ressourcen konnten nicht abgerufen werden.'];
    }
    return [
        'resources' => [
            'wood' => $resources['wood'],
            'stone' => $resources['stone'],
            'ore' => $resources['ore'],
            'storageCapacity' => $resources['storageCapacity'],
            'settlers' => $resources['maxSettlers'], // Max settlers
            //'freeSettlers' => $resources['freeSettlers'] // Free settlers
        ],
    ];
}

// Terminal-Test-Funktion
function testFetchResources($settlementId) {
    $result = fetchResources($settlementId);

    if (isset($result['error'])) {
        echo "Fehler: " . $result['error'] . PHP_EOL;
    } else {
        echo "Ressourcen für Siedlung ID $settlementId:" . PHP_EOL;
        foreach ($result['resources'] as $key => $value) {
            echo ucfirst($key) . ": " . $value . PHP_EOL;
        }
    }
}

// Terminal-Input verarbeiten
if (isset($argv[1])) {
    $settlementId = (int)$argv[1];
    testFetchResources($settlementId);
} else {
    echo "Bitte eine Siedlungs-ID als Argument übergeben." . PHP_EOL;
}
