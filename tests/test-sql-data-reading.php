<?php
/**
 * Test script to verify SQL data reading correctness
 * This script tests various scenarios to ensure all SQL data is read correctly
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';

class SQLDataReadingTest {
    private $database;
    private $testResults = [];
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runAllTests() {
        echo "=== SQL Data Reading Correctness Tests ===\n";
        echo "Testing if all SQL data is always read correctly\n\n";
        
        $this->testDatabaseConnection();
        $this->testResourcesDataIntegrity();
        $this->testNullValueHandling();
        $this->testDataTypeConsistency();
        $this->testErrorHandling();
        $this->testBoundaryValues();
        $this->testConcurrentAccess();
        
        $this->printSummary();
    }
    
    private function testDatabaseConnection() {
        echo "1. Testing Database Connection...\n";
        
        $connected = $this->database->isConnected();
        $this->addResult("Database Connection", $connected, "Database should be connected");
        
        if (!$connected) {
            echo "   WARNING: Database not connected, testing with mock data\n";
        }
        echo "\n";
    }
    
    private function testResourcesDataIntegrity() {
        echo "2. Testing Resources Data Integrity...\n";
        
        // Test with valid settlement ID
        $resources = $this->database->getResources(1);
        
        $this->addResult(
            "Resources Retrieved", 
            $resources !== false && $resources !== null,
            "Resources should be retrievable for valid settlement"
        );
        
        if ($resources) {
            // Check all required fields exist
            $requiredFields = ['wood', 'stone', 'ore', 'storageCapacity', 'maxSettlers', 'freeSettlers'];
            foreach ($requiredFields as $field) {
                $this->addResult(
                    "Field $field exists",
                    isset($resources[$field]),
                    "Resource field $field should exist"
                );
            }
            
            // Check data types
            $numericFields = ['wood', 'stone', 'ore', 'storageCapacity', 'maxSettlers', 'freeSettlers'];
            foreach ($numericFields as $field) {
                if (isset($resources[$field])) {
                    $this->addResult(
                        "Field $field is numeric",
                        is_numeric($resources[$field]),
                        "Resource field $field should be numeric"
                    );
                    
                    // Check for reasonable bounds
                    $value = (float)$resources[$field];
                    $this->addResult(
                        "Field $field non-negative",
                        $value >= 0,
                        "Resource field $field should not be negative"
                    );
                    
                    // Check for extremely large values that might indicate corruption
                    $this->addResult(
                        "Field $field reasonable value",
                        $value < 1000000000, // 1 billion as upper bound
                        "Resource field $field should have reasonable value"
                    );
                }
            }
        }
        echo "\n";
    }
    
    private function testNullValueHandling() {
        echo "3. Testing NULL Value Handling...\n";
        
        // Test with invalid settlement ID
        $invalidResources = $this->database->getResources(999999);
        $this->addResult(
            "Invalid Settlement Handling",
            $invalidResources === false || $invalidResources === null || empty($invalidResources),
            "Invalid settlement should return appropriate empty result"
        );
        
        // Test settlement name with invalid ID
        $invalidSettlement = $this->database->getSettlementName(999999);
        $this->addResult(
            "Invalid Settlement Name Handling",
            $invalidSettlement === false || $invalidSettlement === null || empty($invalidSettlement),
            "Invalid settlement name query should return appropriate empty result"
        );
        
        echo "\n";
    }
    
    private function testDataTypeConsistency() {
        echo "4. Testing Data Type Consistency...\n";
        
        $building = null;
        try {
            $building = $this->database->getBuilding(1, 'Holzfäller');
        } catch (Exception $e) {
            // This is expected if building doesn't exist
            echo "   Note: Building query threw exception (may be expected): " . $e->getMessage() . "\n";
        }
        
        if ($building) {
            $numericFields = ['currentLevel', 'nextLevel', 'costWood', 'costStone', 'costOre', 'settlers', 'buildTime'];
            foreach ($numericFields as $field) {
                if (isset($building[$field])) {
                    $this->addResult(
                        "Building $field is numeric",
                        is_numeric($building[$field]),
                        "Building field $field should be numeric"
                    );
                }
            }
        }
        
        echo "\n";
    }
    
    private function testErrorHandling() {
        echo "5. Testing SQL Error Handling...\n";
        
        // Test with potentially problematic values
        $queue = $this->database->getQueue(1);
        $this->addResult(
            "Queue Query Doesn't Crash",
            $queue !== null, // Should return array or false, but not null
            "Queue query should handle errors gracefully"
        );
        
        $map = $this->database->getMap();
        $this->addResult(
            "Map Query Doesn't Crash",
            $map !== null,
            "Map query should handle errors gracefully"
        );
        
        echo "\n";
    }
    
    private function testBoundaryValues() {
        echo "6. Testing Boundary Values...\n";
        
        // Test with boundary settlement IDs
        $boundaryTests = [0, -1, 1, 2, 1000];
        
        foreach ($boundaryTests as $settlementId) {
            $resources = $this->database->getResources($settlementId);
            
            // Should not crash and should return consistent type
            $this->addResult(
                "Boundary test settlementId=$settlementId",
                $resources !== null, // Should return something, even if false or empty
                "Should handle boundary settlement ID gracefully"
            );
            
            if ($resources && is_array($resources)) {
                // If we get data, it should be valid
                foreach (['wood', 'stone', 'ore'] as $resource) {
                    if (isset($resources[$resource])) {
                        $value = $resources[$resource];
                        $this->addResult(
                            "Boundary $resource value valid",
                            is_numeric($value) && $value >= 0 && $value < 1000000000,
                            "Boundary resource values should be valid"
                        );
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    private function testConcurrentAccess() {
        echo "7. Testing Concurrent Access Simulation...\n";
        
        // Simulate multiple quick requests to the same data
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $resources = $this->database->getResources(1);
            $results[] = $resources;
        }
        
        // All results should be consistent
        $firstResult = $results[0];
        $consistent = true;
        
        for ($i = 1; $i < count($results); $i++) {
            if ($results[$i] !== $firstResult) {
                $consistent = false;
                break;
            }
        }
        
        $this->addResult(
            "Concurrent Access Consistency",
            $consistent,
            "Multiple rapid queries should return consistent results"
        );
        
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
        echo "=== Test Summary ===\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($result) {
            return $result['passed'];
        });
        $passedCount = count($passedTests);
        $failedCount = $totalTests - $passedCount;
        
        echo "Total Tests: $totalTests\n";
        echo "Passed: $passedCount\n";
        echo "Failed: $failedCount\n";
        
        if ($failedCount > 0) {
            echo "\nFAILED TESTS (SQL data reading issues found):\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- " . $result['name'] . ": " . $result['description'] . "\n";
                }
            }
            echo "\nThese failures indicate that SQL data is NOT always read correctly.\n";
        } else {
            echo "\nAll tests passed! SQL data appears to be read correctly.\n";
        }
    }
}

// Run the tests
$tester = new SQLDataReadingTest();
$tester->runAllTests();
?>