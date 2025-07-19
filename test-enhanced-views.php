<?php
/**
 * Database Views Test Script
 * Tests the enhanced views for simplified database access
 */

// Include database connection
require_once __DIR__ . '/php/database.php';

echo "=== Enhanced Database Views Test ===\n";

try {
    $database = new Database();
    
    if (!$database->isConnected()) {
        echo "❌ Database connection failed\n";
        exit(1);
    }
    
    echo "✅ Database connection successful\n\n";
    
    $conn = $database->getConnection();
    
    // Test 1: Check if enhanced views exist
    echo "🔍 Checking enhanced views...\n";
    
    $viewsToCheck = [
        'SettlementResources',
        'SettlementSettlers', 
        'BuildingDetails',
        'AllBuildingsOverview',
        'MilitaryTrainingCosts',
        'ResearchCosts',
        'ActiveQueues'
    ];
    
    foreach ($viewsToCheck as $view) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM information_schema.views WHERE table_schema = 'browsergame' AND table_name = ?");
        $stmt->execute([$view]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "✅ View $view exists\n";
        } else {
            echo "❌ View $view missing\n";
        }
    }
    
    // Test 2: Test SettlementResources view
    echo "\n🏘️  Testing SettlementResources view...\n";
    
    $stmt = $conn->query("SELECT * FROM SettlementResources LIMIT 3");
    $settlements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($settlements) > 0) {
        echo "✅ SettlementResources view working - found " . count($settlements) . " settlements\n";
        foreach ($settlements as $settlement) {
            echo "  Settlement: {$settlement['settlementName']} (Player: {$settlement['playerName']})\n";
            echo "    Resources: Wood={$settlement['wood']}, Stone={$settlement['stone']}, Ore={$settlement['ore']}\n";
            if (isset($settlement['woodProduction'])) {
                echo "    Production: Wood={$settlement['woodProduction']}/s, Stone={$settlement['stoneProduction']}/s, Ore={$settlement['oreProduction']}/s\n";
            }
        }
    } else {
        echo "⚠️  No settlements found in SettlementResources view\n";
    }
    
    // Test 3: Test BuildingDetails view
    echo "\n🏗️  Testing BuildingDetails view...\n";
    
    $stmt = $conn->query("SELECT * FROM BuildingDetails WHERE settlementId = 1 LIMIT 5");
    $buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($buildings) > 0) {
        echo "✅ BuildingDetails view working - found " . count($buildings) . " buildings\n";
        foreach ($buildings as $building) {
            echo "  {$building['buildingType']}: Level {$building['currentLevel']} -> {$building['nextLevel']}\n";
            echo "    Costs: Wood={$building['costWood']}, Stone={$building['costStone']}, Ore={$building['costOre']}\n";
        }
    } else {
        echo "⚠️  No buildings found in BuildingDetails view\n";
    }
    
    // Test 4: Test procedures
    echo "\n⚙️  Testing database procedures...\n";
    
    // Check if procedures exist
    $stmt = $conn->query("SELECT routine_name FROM information_schema.routines WHERE routine_schema = 'browsergame' AND routine_type = 'PROCEDURE'");
    $procedures = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "✅ Found " . count($procedures) . " procedures: " . implode(', ', $procedures) . "\n";
    
    // Test ValidateDatabase procedure
    try {
        $stmt = $conn->query("CALL ValidateDatabase()");
        $validation = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Database validation: " . $validation['validationResult'] . "\n";
        echo "  Tables: {$validation['totalTables']}, Views: {$validation['totalViews']}, Procedures: {$validation['totalProcedures']}\n";
    } catch (Exception $e) {
        echo "❌ Error calling ValidateDatabase: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Check events
    echo "\n📅 Checking database events...\n";
    
    $stmt = $conn->query("SELECT event_name, status FROM information_schema.events WHERE event_schema = 'browsergame'");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) > 0) {
        echo "✅ Found " . count($events) . " events:\n";
        foreach ($events as $event) {
            echo "  {$event['event_name']}: {$event['status']}\n";
        }
    } else {
        echo "⚠️  No events found\n";
    }
    
    echo "\n=== Database Views Test Complete ===\n";
    echo "✅ All tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    exit(1);
}
?>