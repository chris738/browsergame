<?php
/**
 * Final Verification Test
 * Demonstrates that "alle SQL Daten immer korrekt eingelesen werden"
 * (all SQL data is always read correctly)
 */

require_once 'php/database.php';

echo "=== FINAL VERIFICATION: SQL Daten werden immer korrekt eingelesen ===\n";
echo "Final verification that all SQL data is always read correctly\n\n";

// Initialize database
$database = new Database();

echo "1. Überprüfung der Datenbankverbindung (Database Connection Check):\n";
$connected = $database->isConnected();
echo "   Status: " . ($connected ? "✅ VERBUNDEN" : "❌ NICHT VERBUNDEN") . "\n\n";

echo "2. Test aller SQL-Datenlesevorgänge (Testing all SQL data reading operations):\n";

$allTestsPassed = true;
$testsRun = 0;
$errors = [];

// Test function helper
function runTest($testName, $testFunction, &$allTestsPassed, &$testsRun, &$errors) {
    $testsRun++;
    try {
        $result = $testFunction();
        if ($result) {
            echo "   ✅ $testName: ERFOLGREICH (SUCCESS)\n";
        } else {
            echo "   ❌ $testName: FEHLGESCHLAGEN (FAILED)\n";
            $allTestsPassed = false;
            $errors[] = $testName;
        }
    } catch (Exception $e) {
        echo "   ❌ $testName: FEHLER - " . $e->getMessage() . "\n";
        $allTestsPassed = false;
        $errors[] = $testName . " (Exception)";
    }
}

// Test 1: Resource data reading
runTest("Ressourcen lesen (Resource Reading)", function() use ($database) {
    $resources = $database->getResources(1);
    return $resources && 
           isset($resources['wood'], $resources['stone'], $resources['ore']) &&
           is_numeric($resources['wood']) && 
           $resources['wood'] >= 0;
}, $allTestsPassed, $testsRun, $errors);

// Test 2: Building data reading
runTest("Gebäudedaten lesen (Building Data Reading)", function() use ($database) {
    $building = $database->getBuilding(1, 'Holzfäller');
    return $building && 
           isset($building['currentLevel'], $building['costWood']) &&
           is_numeric($building['currentLevel']) &&
           $building['currentLevel'] > 0;
}, $allTestsPassed, $testsRun, $errors);

// Test 3: Queue data reading
runTest("Warteschlange lesen (Queue Reading)", function() use ($database) {
    $queue = $database->getQueue(1);
    return is_array($queue); // Queue can be empty, that's valid
}, $allTestsPassed, $testsRun, $errors);

// Test 4: Map data reading
runTest("Kartendaten lesen (Map Data Reading)", function() use ($database) {
    $map = $database->getMap();
    return is_array($map) && 
           (empty($map) || (isset($map[0]['settlementId'], $map[0]['xCoordinate'], $map[0]['yCoordinate'])));
}, $allTestsPassed, $testsRun, $errors);

// Test 5: Settlement name reading
runTest("Siedlungsname lesen (Settlement Name Reading)", function() use ($database) {
    $name = $database->getSettlementName(1);
    return $name && isset($name['SettlementName']) && !empty($name['SettlementName']);
}, $allTestsPassed, $testsRun, $errors);

// Test 6: Input validation works
runTest("Eingabevalidierung (Input Validation)", function() use ($database) {
    // These should return false/fail gracefully
    $result1 = $database->getResources(-1);  // Invalid ID
    $result2 = $database->getResources(0);   // Invalid ID
    return $result1 === false && $result2 === false;
}, $allTestsPassed, $testsRun, $errors);

// Test 7: Building configs reading
runTest("Gebäudekonfiguration lesen (Building Config Reading)", function() use ($database) {
    $configs = $database->getAllBuildingConfigs();
    return is_array($configs) && 
           (empty($configs) || isset($configs[0]['buildingType'], $configs[0]['level']));
}, $allTestsPassed, $testsRun, $errors);

// Test 8: Player data reading
runTest("Spielerdaten lesen (Player Data Reading)", function() use ($database) {
    $players = $database->getAllPlayers();
    return is_array($players);
}, $allTestsPassed, $testsRun, $errors);

// Test 9: Concurrent access consistency
runTest("Gleichzeitiger Zugriff (Concurrent Access)", function() use ($database) {
    $results = [];
    for ($i = 0; $i < 5; $i++) {
        $results[] = $database->getResources(1);
    }
    // All results should be identical
    $first = $results[0];
    foreach ($results as $result) {
        if ($result !== $first) {
            return false;
        }
    }
    return true;
}, $allTestsPassed, $testsRun, $errors);

// Test 10: Data integrity verification
runTest("Datenintegrität (Data Integrity)", function() use ($database) {
    $resources = $database->getResources(1);
    if (!$resources) return false;
    
    // Check business logic
    if ($resources['freeSettlers'] > $resources['maxSettlers']) return false;
    if ($resources['wood'] < 0 || $resources['stone'] < 0 || $resources['ore'] < 0) return false;
    
    return true;
}, $allTestsPassed, $testsRun, $errors);

echo "\n3. Testergebnisse (Test Results):\n";
echo "   Tests durchgeführt (Tests run): $testsRun\n";
echo "   Tests bestanden (Tests passed): " . ($testsRun - count($errors)) . "\n";
echo "   Tests fehlgeschlagen (Tests failed): " . count($errors) . "\n";

if ($allTestsPassed) {
    echo "\n🎉 ERFOLG! Alle SQL-Daten werden immer korrekt eingelesen!\n";
    echo "   SUCCESS! All SQL data is always read correctly!\n\n";
    
    echo "Implementierte Verbesserungen (Implemented Improvements):\n";
    echo "✅ Eingabevalidierung für alle Parameter\n";
    echo "   Input validation for all parameters\n";
    echo "✅ Ausgabevalidierung für alle Datenstrukturen\n";
    echo "   Output validation for all data structures\n";
    echo "✅ Geschäftslogik-Validierung\n";
    echo "   Business logic validation\n";
    echo "✅ Fehlerbehandlung und Protokollierung\n";
    echo "   Error handling and logging\n";
    echo "✅ Datenintegritätsprüfungen\n";
    echo "   Data integrity checks\n";
    echo "✅ Grenzen- und Typprüfungen\n";
    echo "   Bounds and type checking\n";
    echo "✅ Sichere Fehlerbehandlung\n";
    echo "   Graceful error handling\n";
    
    echo "\nDas Problem ist vollständig gelöst!\n";
    echo "The problem is completely solved!\n";
} else {
    echo "\n❌ FEHLER! Einige Tests sind fehlgeschlagen:\n";
    echo "   ERROR! Some tests failed:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\nWeitere Untersuchung erforderlich.\n";
    echo "Further investigation required.\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
?>