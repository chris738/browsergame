<?php
require_once 'database.php';
header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Funktion zum Laden der Daten aus der Datenbank und zum Formatieren für das Frontend
function fetchResources($settlementId) {
    $database = new Database();
    $resources = $database->getResources($settlementId);

    if (!$resources) {
        return ['error' => 'Ressourcen konnten nicht abgerufen werden.'];
    }
    return [
        'resources' => [
            'wood' => $resources['wood'],
            'stone' => $resources['stone'],
            'ore' => $resources['ore']
        ]
    ];
}

// Funktion zum Laden der Gebäudeinformationen
function fetchBuilding($settlementId, $buildingType) {
    $database = new Database();
    $building = $database->getBuilding($settlementId, $buildingType);

    if (!$building) {
        return ['error' => "Gebäude $buildingType konnte nicht abgerufen werden."];
    }
    return [
        'level' => $building['level'],
        'costWood' => $building['costWood'],
        'costStone' => $building['costStone'],
        'costOre' => $building['costOre']
    ];
}

function handleBuildingUpgrade($settlementId, $input) {
    $buildingType = $input['buildingType'] ?? null;

    $database = new Database();

    if (!$settlementId || !$buildingType) {
        return json_encode(['error' => 'Parameter settlementId oder buildingType fehlt.']);
    }

    // Datenbankzugriff und Upgrade
    $success = $database->upgradeBuilding($settlementId, $buildingType);

    if ($success) {
        return json_encode(['success' => true, 'message' => "$buildingType wurde erfolgreich aufgewertet."]);
    } else {
        return json_encode(['success' => false, 'message' => "$buildingType konnte nicht aufgewertet werden."]);
    }
}

// Eingehende Anfrage verarbeiten
$method = $_SERVER['REQUEST_METHOD'];
$settlementId = $_GET['settlementId'] ?? null;
$buildingType = $_GET['buildingType'] ?? null;

try {
    if ($method === 'GET') {
        if (!$settlementId) {
            echo json_encode(['error' => 'Parameter settlementId fehlt.']);
            exit;
        }

        // Wenn buildingType gesetzt ist, Gebäudeinformationen hinzufügen
        if (!$buildingType) {
            $response = ['resources' => fetchResources($settlementId)];
        } else {
            $response['building'] = fetchBuilding($settlementId, $buildingType);
        }

        echo json_encode($response);
    } elseif ($method === 'POST') {
        // Upgrade-Building-Logik auslagern
        $input = json_decode(file_get_contents('php://input'), true);
        echo handleBuildingUpgrade($settlementId, $input);
    } else {
        echo json_encode(['success' => false, 'message' => 'Methode nicht unterstützt.']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Interner Serverfehler: ' . $e->getMessage()]);
}

?>