<?php
// Fuel consumption tracking backend
// Handles CRUD operations for fuel consumption records

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'database.php';

try {
    $database = new Database();
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        handleGet($database);
    } elseif ($method === 'POST') {
        handlePost($database);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Fuel backend error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function handleGet($database) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'getFuelRecords':
            getFuelRecords($database);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($database) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'addFuelRecord':
            addFuelRecord($database, $input);
            break;
        case 'deleteFuelRecord':
            deleteFuelRecord($database, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function getFuelRecords($database) {
    $settlementId = $_GET['settlementId'] ?? null;
    
    if (!$settlementId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Settlement ID required']);
        return;
    }
    
    try {
        $conn = $database->getConnection();
        
        // Get fuel records
        $stmt = $conn->prepare("
            SELECT id, date, fuelType, pricePerLiter, liters, totalCost, 
                   displayedConsumption, engineRuntime, createdAt
            FROM FuelConsumption 
            WHERE settlementId = ? 
            ORDER BY date DESC, createdAt DESC
        ");
        $stmt->execute([$settlementId]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate statistics
        $statsStmt = $conn->prepare("
            SELECT 
                COALESCE(SUM(liters), 0) as totalLiters,
                COALESCE(SUM(totalCost), 0) as totalCosts,
                COALESCE(AVG(displayedConsumption), 0) as avgConsumption,
                COALESCE(AVG(pricePerLiter), 0) as avgPrice,
                COUNT(*) as recordCount
            FROM FuelConsumption 
            WHERE settlementId = ?
        ");
        $statsStmt->execute([$settlementId]);
        $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'records' => $records,
            'statistics' => $statistics
        ]);
        
    } catch (Exception $e) {
        error_log("Error fetching fuel records: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch records']);
    }
}

function addFuelRecord($database, $input) {
    // Validate required fields
    $required = ['settlementId', 'date', 'fuelType', 'pricePerLiter', 'liters', 'displayedConsumption', 'engineRuntime'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Validate fuel type
    $validFuelTypes = ['Super', 'Super E10', 'Diesel', 'Super Premium'];
    if (!in_array($input['fuelType'], $validFuelTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid fuel type']);
        return;
    }
    
    // Validate numeric values
    if (!is_numeric($input['pricePerLiter']) || $input['pricePerLiter'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid price per liter']);
        return;
    }
    
    if (!is_numeric($input['liters']) || $input['liters'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid liters amount']);
        return;
    }
    
    if (!is_numeric($input['displayedConsumption']) || $input['displayedConsumption'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid consumption value']);
        return;
    }
    
    if (!is_numeric($input['engineRuntime']) || $input['engineRuntime'] <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid engine runtime']);
        return;
    }
    
    // Parse and validate date
    $date = null;
    if (is_string($input['date'])) {
        $date = $input['date'];
    } else {
        // Assume it's a timestamp from JavaScript Date
        $timestamp = $input['date'] / 1000; // JavaScript timestamps are in milliseconds
        $date = date('Y-m-d', $timestamp);
    }
    
    // Handle ISO datetime format from JavaScript
    if (strpos($date, 'T') !== false) {
        $dateTime = new DateTime($date);
        $date = $dateTime->format('Y-m-d');
    }
    
    if (!$date || !strtotime($date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        return;
    }
    
    try {
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO FuelConsumption 
            (settlementId, date, fuelType, pricePerLiter, liters, displayedConsumption, engineRuntime)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $input['settlementId'],
            $date,
            $input['fuelType'],
            $input['pricePerLiter'],
            $input['liters'],
            $input['displayedConsumption'],
            $input['engineRuntime']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Fuel record added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to add fuel record']);
        }
        
    } catch (Exception $e) {
        error_log("Error adding fuel record: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteFuelRecord($database, $input) {
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid record ID required']);
        return;
    }
    
    try {
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM FuelConsumption WHERE id = ?");
        $result = $stmt->execute([$input['id']]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Fuel record deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fuel record not found']);
        }
        
    } catch (Exception $e) {
        error_log("Error deleting fuel record: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete fuel record']);
    }
}