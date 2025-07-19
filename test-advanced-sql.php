<?php
/**
 * Advanced SQL Data Reading Tests
 * Tests edge cases and potential data corruption scenarios
 */

require_once 'php/database.php';

class AdvancedSQLDataTest {
    private $database;
    private $testResults = [];
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runAdvancedTests() {
        echo "=== Advanced SQL Data Reading Tests ===\n";
        echo "Testing edge cases and potential data corruption\n\n";
        
        $this->testFloatPrecision();
        $this->testStringEscaping();
        $this->testTransactionIntegrity();
        $this->testMemoryAndPerformance();
        $this->testDataCorruption();
        $this->testSpecialCharacters();
        $this->testLargeDataSets();
        
        $this->printSummary();
    }
    
    private function testFloatPrecision() {
        echo "1. Testing Float Precision in Resources...\n";
        
        // Test that float values are handled correctly
        $resources = $this->database->getResources(1);
        
        if ($resources) {
            foreach (['wood', 'stone', 'ore'] as $resource) {
                if (isset($resources[$resource])) {
                    $value = $resources[$resource];
                    
                    // Check if it's actually a float or can be converted
                    $floatValue = (float)$value;
                    $this->addResult(
                        "Float conversion $resource",
                        $floatValue == $value,
                        "Resource $resource should convert to float accurately"
                    );
                    
                    // Check precision - floats might lose precision
                    $stringValue = (string)$value;
                    $backConverted = (float)$stringValue;
                    $this->addResult(
                        "Float precision $resource",
                        abs($backConverted - $floatValue) < 0.01,
                        "Resource $resource should maintain reasonable precision"
                    );
                }
            }
        }
        echo "\n";
    }
    
    private function testStringEscaping() {
        echo "2. Testing String Escaping and Special Characters...\n";
        
        // Test settlement names with potential problematic characters
        $settlementName = $this->database->getSettlementName(1);
        
        if ($settlementName && isset($settlementName['SettlementName'])) {
            $name = $settlementName['SettlementName'];
            
            // Check for potential injection artifacts
            $dangerousPatterns = ["'", '"', '<', '>', '\\', '\0', '\n', '\r'];
            $safe = true;
            foreach ($dangerousPatterns as $pattern) {
                if (strpos($name, $pattern) !== false) {
                    $safe = false;
                    break;
                }
            }
            
            $this->addResult(
                "Settlement Name Safe",
                $safe,
                "Settlement name should not contain dangerous characters"
            );
            
            // Check encoding
            $this->addResult(
                "Settlement Name UTF-8",
                mb_check_encoding($name, 'UTF-8'),
                "Settlement name should be valid UTF-8"
            );
        }
        echo "\n";
    }
    
    private function testTransactionIntegrity() {
        echo "3. Testing Transaction Integrity...\n";
        
        // Get initial resource state
        $initialResources = $this->database->getResources(1);
        
        if ($initialResources) {
            // Try to upgrade a building (this should be transactional)
            $upgradeResult = $this->database->upgradeBuilding(1, 'Holzfäller');
            
            // Get resources after upgrade attempt
            $afterResources = $this->database->getResources(1);
            
            if ($upgradeResult && $upgradeResult['success']) {
                // If upgrade succeeded, resources should have changed
                $resourcesChanged = 
                    $initialResources['wood'] != $afterResources['wood'] ||
                    $initialResources['stone'] != $afterResources['stone'] ||
                    $initialResources['ore'] != $afterResources['ore'];
                
                $this->addResult(
                    "Transaction Resources Changed",
                    $resourcesChanged,
                    "Resources should change when upgrade succeeds"
                );
            } else {
                // If upgrade failed, resources should NOT have changed
                $resourcesUnchanged = 
                    $initialResources['wood'] == $afterResources['wood'] &&
                    $initialResources['stone'] == $afterResources['stone'] &&
                    $initialResources['ore'] == $afterResources['ore'];
                
                $this->addResult(
                    "Transaction Resources Unchanged",
                    $resourcesUnchanged,
                    "Resources should not change when upgrade fails"
                );
            }
        }
        echo "\n";
    }
    
    private function testMemoryAndPerformance() {
        echo "4. Testing Memory Usage and Performance...\n";
        
        $startMemory = memory_get_usage();
        $startTime = microtime(true);
        
        // Perform multiple database operations
        for ($i = 0; $i < 10; $i++) {
            $resources = $this->database->getResources(1);
            $queue = $this->database->getQueue(1);
            $map = $this->database->getMap();
        }
        
        $endMemory = memory_get_usage();
        $endTime = microtime(true);
        
        $memoryUsed = $endMemory - $startMemory;
        $timeUsed = $endTime - $startTime;
        
        $this->addResult(
            "Memory Usage Reasonable",
            $memoryUsed < 10 * 1024 * 1024, // Less than 10MB
            "Multiple DB operations should not use excessive memory"
        );
        
        $this->addResult(
            "Performance Reasonable",
            $timeUsed < 5.0, // Less than 5 seconds
            "Multiple DB operations should complete in reasonable time"
        );
        
        echo "   Memory used: " . number_format($memoryUsed / 1024, 2) . " KB\n";
        echo "   Time used: " . number_format($timeUsed, 3) . " seconds\n";
        echo "\n";
    }
    
    private function testDataCorruption() {
        echo "5. Testing Data Corruption Detection...\n";
        
        $resources = $this->database->getResources(1);
        
        if ($resources) {
            // Check for impossible resource combinations
            $totalResources = $resources['wood'] + $resources['stone'] + $resources['ore'];
            $this->addResult(
                "Total Resources Reasonable",
                $totalResources < 100000000, // 100 million total
                "Total resources should not exceed reasonable limits"
            );
            
            // Check storage capacity logic
            if (isset($resources['storageCapacity'])) {
                $maxIndividualResource = max($resources['wood'], $resources['stone'], $resources['ore']);
                $this->addResult(
                    "Storage Capacity Logic",
                    $resources['storageCapacity'] >= $maxIndividualResource || $resources['storageCapacity'] == 0,
                    "Storage capacity should be sufficient for current resources"
                );
            }
            
            // Check settler logic
            if (isset($resources['freeSettlers']) && isset($resources['maxSettlers'])) {
                $this->addResult(
                    "Settler Logic",
                    $resources['freeSettlers'] <= $resources['maxSettlers'],
                    "Free settlers should not exceed max settlers"
                );
                
                $this->addResult(
                    "Settler Values Valid",
                    $resources['freeSettlers'] >= 0 && $resources['maxSettlers'] >= 0,
                    "Settler values should be non-negative"
                );
            }
        }
        echo "\n";
    }
    
    private function testSpecialCharacters() {
        echo "6. Testing Special Character Handling...\n";
        
        // Test building types with special characters (umlauts, etc.)
        $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk'];
        
        foreach ($buildingTypes as $buildingType) {
            try {
                $building = $this->database->getBuilding(1, $buildingType);
                
                $this->addResult(
                    "Special Chars Building: $buildingType",
                    $building !== null,
                    "Should handle building type with special characters"
                );
                
                // Check if building type is properly encoded
                $this->addResult(
                    "Building Type UTF-8: $buildingType",
                    mb_check_encoding($buildingType, 'UTF-8'),
                    "Building type should be valid UTF-8"
                );
                
            } catch (Exception $e) {
                $this->addResult(
                    "Special Chars Building: $buildingType",
                    false,
                    "Should handle building type with special characters (Exception: " . $e->getMessage() . ")"
                );
            }
        }
        echo "\n";
    }
    
    private function testLargeDataSets() {
        echo "7. Testing Large Data Set Handling...\n";
        
        // Test map data (potentially large)
        $map = $this->database->getMap();
        
        if ($map && is_array($map)) {
            $this->addResult(
                "Map Data Array",
                true,
                "Map data should be returned as array"
            );
            
            $mapSize = count($map);
            $this->addResult(
                "Map Size Reasonable",
                $mapSize < 10000, // Less than 10k settlements
                "Map should not contain excessive number of settlements"
            );
            
            // Check map data integrity
            if ($mapSize > 0) {
                $validEntries = 0;
                foreach ($map as $entry) {
                    if (isset($entry['settlementId']) && 
                        isset($entry['xCoordinate']) && 
                        isset($entry['yCoordinate']) &&
                        is_numeric($entry['settlementId']) &&
                        is_numeric($entry['xCoordinate']) &&
                        is_numeric($entry['yCoordinate'])) {
                        $validEntries++;
                    }
                }
                
                $this->addResult(
                    "Map Data Integrity",
                    $validEntries == $mapSize,
                    "All map entries should have valid structure"
                );
            }
        }
        
        // Test building configs (potentially many)
        $buildingConfigs = $this->database->getAllBuildingConfigs();
        
        if ($buildingConfigs && is_array($buildingConfigs)) {
            $configCount = count($buildingConfigs);
            $this->addResult(
                "Building Config Count Reasonable",
                $configCount < 1000,
                "Building configs should not be excessive"
            );
            
            // Test config data integrity
            if ($configCount > 0) {
                $validConfigs = 0;
                foreach ($buildingConfigs as $config) {
                    if (isset($config['buildingType']) && 
                        isset($config['level']) &&
                        isset($config['costWood']) &&
                        is_numeric($config['level']) &&
                        is_numeric($config['costWood'])) {
                        $validConfigs++;
                    }
                }
                
                $this->addResult(
                    "Building Config Integrity",
                    $validConfigs == $configCount,
                    "All building configs should have valid structure"
                );
            }
        }
        echo "\n";
    }
    
    private function addResult($testName, $passed, $description) {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => $passed,
            'description' => $description
        ];
        
        $status = $passed ? "PASS" : "FAIL";
        echo "   [$status] $testName: $description\n";
        
        if (!$passed) {
            echo "          ^ This indicates a potential SQL data reading issue\n";
        }
    }
    
    private function printSummary() {
        echo "=== Advanced Test Summary ===\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($result) {
            return $result['passed'];
        });
        $passedCount = count($passedTests);
        $failedCount = $totalTests - $passedCount;
        
        echo "Total Advanced Tests: $totalTests\n";
        echo "Passed: $passedCount\n";
        echo "Failed: $failedCount\n";
        
        if ($failedCount > 0) {
            echo "\nFAILED ADVANCED TESTS (SQL data reading issues found):\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- " . $result['name'] . ": " . $result['description'] . "\n";
                }
            }
            echo "\nThese failures indicate that SQL data is NOT always read correctly.\n";
        } else {
            echo "\nAll advanced tests passed! SQL data reading appears robust.\n";
        }
    }
}

// Run the advanced tests
$tester = new AdvancedSQLDataTest();
$tester->runAdvancedTests();
?>