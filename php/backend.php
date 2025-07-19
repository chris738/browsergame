<?php
require_once 'database.php';
header('Content-Type: text/html; charset=utf-8');

// Centralized building type translations
function getBuildingTranslations() {
    return [
        'Rathaus' => 'Town Hall',
        'Holzfäller' => 'Lumberjack',
        'Steinbruch' => 'Quarry',
        'Erzbergwerk' => 'Mine',
        'Lager' => 'Storage',
        'Farm' => 'Farm',
        'Markt' => 'Market',
        'Kaserne' => 'Barracks'
    ];
}

function translateBuildingName($germanName) {
    $translations = getBuildingTranslations();
    return $translations[$germanName] ?? $germanName;
}

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
            'ore' => $resources['ore'],
            'storageCapacity' => $resources['storageCapacity'],
            'maxSettlers' => $resources['maxSettlers'], // Max settlers
            'freeSettlers' => $resources['freeSettlers'] // Free settlers
        ],
    ];
}

function fetchPlayerInfo($settlementId) {
    $database = new Database();
    $playerName = $database->getPlayerNameFromSettlement($settlementId);
    $playerGold = $database->getPlayerGold($settlementId);
    $playerId = $database->getPlayerIdFromSettlement($settlementId);
    
    return [
        'playerName' => $playerName,
        'playerGold' => $playerGold,
        'playerId' => $playerId
    ];
}

function validateSettlementOwnership($settlementId, $currentPlayerId = null) {
    $database = new Database();
    $settlementOwnerId = $database->getPlayerIdFromSettlement($settlementId);
    
    // If no current player specified, assume they can access any settlement (for backwards compatibility)
    if ($currentPlayerId === null) {
        return true;
    }
    
    return $settlementOwnerId == $currentPlayerId;
}

function fetchAllPlayersWithSettlements() {
    $database = new Database();
    
    // Return mock data if database connection failed
    if (!$database->isConnected()) {
        return [
            [
                'settlementId' => 1,
                'settlementName' => 'TestPlayer_Settlement',
                'playerName' => 'TestPlayer',
                'playerId' => 1
            ],
            [
                'settlementId' => 2,
                'settlementName' => 'Player2_Settlement',
                'playerName' => 'Player2',
                'playerId' => 2
            ],
            [
                'settlementId' => 3,
                'settlementName' => 'Player3_Settlement',
                'playerName' => 'Player3',
                'playerId' => 3
            ]
        ];
    }
    
    $sql = "
        SELECT 
            s.settlementId,
            s.name as settlementName,
            p.name as playerName,
            p.playerId
        FROM Settlement s
        INNER JOIN Spieler p ON s.playerId = p.playerId
        ORDER BY p.name, s.name
    ";
    
    try {
        $stmt = $database->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function fetchRegen($settlementId) {
    $database = new Database();
    $regen = $database->getRegen($settlementId);

    if (!$regen) {
        return ['error' => 'Regen konnten nicht abgerufen werden.'];
    }

    return [
        'regens' => [
            'wood' => $regen['woodProductionRate'],
            'stone' => $regen['stoneProductionRate'],
            'ore' => $regen['oreProductionRate'],
        ],
    ];
}

function fetchBuildingQueue($settlementId) {
    $database = new Database();
    $queue = $database->getQueue($settlementId);

    if (!$queue) {
        return ['error' => 'BuildingQueue konnte nicht abgerufen werden - ist sie leer???.'];
    }

    return [
        'queue' => array_map(function($item) {
            return [
                'queueId' => $item['queueId'],
                'buildingType' => $item['buildingType'],
                'startTime' => $item['startTime'],
                'endTime' => $item['endTime'],
                'completionPercentage' => $item['completionPercentage'],
                'level' => $item['level'],
            ];
        }, $queue),
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
        'level' => $building['currentLevel'],
        'nextLevel' => $building['nextLevel'],
        'costWood' => $building['costWood'],
        'costStone' => $building['costStone'],
        'costOre' => $building['costOre'],
        'costSettlers' => $building['settlers'],
        'buildTime' => $building['buildTime']
    ];
}

function handleBuildingUpgrade($settlementId, $input) {
    $buildingType = $input['buildingType'] ?? null;
    $currentPlayerId = $input['currentPlayerId'] ?? null;

    $database = new Database();

    if (!$settlementId) {
        return json_encode(['error' => 'Parameter settlementId oder buildingType fehlt.']);
    }

    // Validate settlement ownership
    if ($currentPlayerId !== null) {
        $isOwner = validateSettlementOwnership($settlementId, $currentPlayerId);
        if (!$isOwner) {
            return json_encode(['success' => false, 'message' => 'You can only upgrade buildings in your own settlement.']);
        }
    }

    // Datenbankzugriff und Upgrade
    $result = $database->upgradeBuilding($settlementId, $buildingType);

    if ($result['success']) {
        return json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        return json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function getSettlementName($settlementId) {
    $database = new Database();
    $name = $database->getSettlementName($settlementId);

    if (!$name) {
        return ['error' => 'getSettlementName konnten nicht abgerufen werden.'];
    }

    return [
        'SettlementName' => $name['SettlementName']
    ];
}

function getBuildingTypes() {
    $database = new Database();
    $buildingTypes = $database->getDistinctBuildingTypes();

    if (!$buildingTypes) {
        return ['error' => 'Gebäudetypen konnten nicht abgerufen werden.'];
    }

    return [
        'buildingTypes' => array_map(function($type) {
            return [
                'name' => $type['buildingType'],
                'id' => strtolower($type['buildingType'])
            ];
        }, $buildingTypes),
    ];
}

function getMap() {
    $database = new Database();
    $map = $database->getMap();

    if (!$map) {
        return ['error' => 'getMap konnten nicht abgerufen werden.'];
    }

    return [
        'map' => array_map(function($map) {
            return [
                'settlementId' => $map['settlementId'],
                'xCoordinate' => $map['xCoordinate'],
                'yCoordinate' => $map['yCoordinate'],
            ];
        }, $map),
    ];
}

function fetchMilitaryUnits($settlementId) {
    $database = new Database();
    $units = $database->getMilitaryUnits($settlementId);
    
    if ($units === false || $units === null) {
        return ['error' => 'Military units could not be retrieved.'];
    }
    
    return ['units' => $units];
}

function fetchMilitaryTrainingQueue($settlementId) {
    $database = new Database();
    $queue = $database->getMilitaryTrainingQueue($settlementId);
    
    if ($queue === false || $queue === null) {
        return ['error' => 'Military training queue could not be retrieved.'];
    }
    
    return ['queue' => $queue];
}

function fetchMilitaryStats($settlementId) {
    $database = new Database();
    $stats = $database->getMilitaryStats($settlementId);
    
    if ($stats === false || $stats === null) {
        return ['error' => 'Military stats could not be retrieved.'];
    }
    
    return ['stats' => $stats];
}

function handleMilitaryTraining($settlementId, $input) {
    if (!$settlementId || !isset($input['unitType']) || !isset($input['count'])) {
        return json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    }

    $unitType = $input['unitType'];
    $count = (int)$input['count'];
    
    if ($count <= 0) {
        return json_encode(['success' => false, 'message' => 'Invalid unit count.']);
    }

    $database = new Database();
    $result = $database->trainMilitaryUnit($settlementId, $unitType, $count);

    if ($result['success']) {
        return json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        return json_encode(['success' => false, 'message' => $result['message']]);
    }
}

// Research System Functions
function fetchUnitResearch($settlementId) {
    $database = new Database();
    $research = $database->getUnitResearch($settlementId);
    
    if ($research === false || $research === null) {
        return ['error' => 'Unit research data could not be retrieved.'];
    }
    
    return ['research' => $research];
}

function fetchResearchQueue($settlementId) {
    $database = new Database();
    $queue = $database->getResearchQueue($settlementId);
    
    if ($queue === false || $queue === null) {
        return ['error' => 'Research queue could not be retrieved.'];
    }
    
    return ['queue' => $queue];
}

function fetchResearchConfig() {
    $database = new Database();
    $config = $database->getResearchConfig();
    
    if ($config === false || $config === null) {
        return ['error' => 'Research configuration could not be retrieved.'];
    }
    
    return ['config' => $config];
}

function handleResearch($settlementId, $input) {
    if (!$settlementId || !isset($input['unitType'])) {
        return json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    }

    $unitType = $input['unitType'];
    
    $database = new Database();
    $result = $database->startResearch($settlementId, $unitType);

    if ($result['success']) {
        return json_encode(['success' => true, 'message' => $result['message']]);
    } else {
        return json_encode(['success' => false, 'message' => $result['message']]);
    }
}

// Eingehende Anfrage verarbeiten
$method = $_SERVER['REQUEST_METHOD'];
$settlementId = $_GET['settlementId'] ?? null;
$buildingType = $_GET['buildingType'] ?? null;
$getRegen = $_GET['getRegen'] ?? null;
$getSettlementName = $_GET['getSettlementName'] ?? null;
$getBuildingQueue = $_GET['getBuildingQueue'] ?? null;
$getMap = $_GET['getMap'] ?? null;
$getBuildingTypes = $_GET['getBuildingTypes'] ?? null;
$getPlayerInfo = $_GET['getPlayerInfo'] ?? null;
$getAllPlayers = $_GET['getAllPlayers'] ?? null;
$getMilitaryUnits = $_GET['getMilitaryUnits'] ?? null;
$getMilitaryQueue = $_GET['getMilitaryQueue'] ?? null;
$getMilitaryStats = $_GET['getMilitaryStats'] ?? null;
$getUnitResearch = $_GET['getUnitResearch'] ?? null;
$getResearchQueue = $_GET['getResearchQueue'] ?? null;
$getResearchConfig = $_GET['getResearchConfig'] ?? null;

try {
    if ($method === 'GET') {
        // Handle building types request (doesn't require settlementId)
        if ($getBuildingTypes == True) {
            header('Content-Type: application/json; charset=utf-8');
            $response = ['buildingTypes' => getBuildingTypes()];
            echo json_encode($response);
            exit;
        }
        
        // Handle all players request (doesn't require settlementId)
        if ($getAllPlayers == True) {
            header('Content-Type: application/json; charset=utf-8');
            $response = ['players' => fetchAllPlayersWithSettlements()];
            echo json_encode($response);
            exit;
        }
        
        // Handle research config request (doesn't require settlementId)
        if ($getResearchConfig == True) {
            header('Content-Type: application/json; charset=utf-8');
            $response = ['researchConfig' => fetchResearchConfig()];
            echo json_encode($response);
            exit;
        }
        
        if (!$settlementId) {
            echo json_encode(['error' => 'Parameter settlementId fehlt.']);
            exit;
        }

        // Initialize response array
        $response = [];
        
        // Wenn buildingType gesetzt ist, Gebäudeinformationen hinzufügen
        if (!$buildingType) {
            $response['resources'] = fetchResources($settlementId);
        } else {
            $response['building'] = fetchBuilding($settlementId, $buildingType);
        }

        //wenn getRegen
        if ($getRegen == True) {
            $response = ['regen' => fetchRegen($settlementId)];
        }

        //Settlement Name
        if ($getSettlementName == True) {
            $response = ['info' => getSettlementName($settlementId)];
        }
        
        //Player Info (name and gold)
        if ($getPlayerInfo == True) {
            $response = ['playerInfo' => fetchPlayerInfo($settlementId)];
        }

        //fetchBuildingQueue
        if ($getBuildingQueue == True) {
            $response = ['info' => fetchBuildingQueue($settlementId)];
        }

        //Settlement Name
        if ($getMap == True) {
            $response = ['info' => getMap()];
        }

        // Military Units
        if ($getMilitaryUnits == True) {
            $response = ['militaryUnits' => fetchMilitaryUnits($settlementId)];
        }

        // Military Training Queue
        if ($getMilitaryQueue == True) {
            $response = ['militaryQueue' => fetchMilitaryTrainingQueue($settlementId)];
        }

        // Military Stats
        if ($getMilitaryStats == True) {
            $response = ['militaryStats' => fetchMilitaryStats($settlementId)];
        }

        // Unit Research
        if ($getUnitResearch == True) {
            $response = ['unitResearch' => fetchUnitResearch($settlementId)];
        }

        // Research Queue
        if ($getResearchQueue == True) {
            $response = ['researchQueue' => fetchResearchQueue($settlementId)];
        }

        echo json_encode($response);
    } elseif ($method === 'POST') {
        // Check if this is a military training or research request
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['action']) && $input['action'] === 'trainUnit') {
            echo handleMilitaryTraining($settlementId, $input);
        } elseif (isset($input['action']) && $input['action'] === 'startResearch') {
            echo handleResearch($settlementId, $input);
        } else {
            // Upgrade-Building-Logik auslagern
            echo handleBuildingUpgrade($settlementId, $input);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Methode nicht unterstützt.']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Interner Serverfehler: ' . $e->getMessage()]);
}

?>