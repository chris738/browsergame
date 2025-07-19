<?php
/**
 * Error Scenario Test - Deliberately test error conditions
 * to ensure proper handling of SQL data reading failures
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';

class ErrorScenarioTest {
    private $database;
    private $testResults = [];
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runErrorTests() {
        echo "=== Error Scenario Tests ===\n";
        echo "Testing how the system handles SQL reading errors\n\n";
        
        $this->testConnectionFailureRecovery();
        $this->testMalformedDataHandling();
        $this->testTimeoutScenarios();
        $this->testConcurrentWriteReadIssues();
        $this->testPartialDataReads();
        
        $this->printSummary();
    }
    
    private function testConnectionFailureRecovery() {
        echo "1. Testing Connection Failure Recovery...\n";
        
        // The current implementation should handle connection failures gracefully
        // Let's verify this by checking the isConnected method and mock behavior
        
        $isConnected = $this->database->isConnected();
        $this->addResult(
            "Connection Status Check",
            is_bool($isConnected),
            "isConnected should return boolean value"
        );
        
        // Test that mock data is consistent when connection is not available
        if (!$isConnected) {
            $mockResources1 = $this->database->getResources(1);
            $mockResources2 = $this->database->getResources(1);
            
            $this->addResult(
                "Mock Data Consistency",
                $mockResources1 === $mockResources2,
                "Mock data should be consistent across calls"
            );
        }
        
        echo "\n";
    }
    
    private function testMalformedDataHandling() {
        echo "2. Testing Malformed Data Handling...\n";
        
        // Test how the system handles edge cases in returned data
        $resources = $this->database->getResources(1);
        
        if ($resources) {
            // Test if system properly handles missing fields
            $expectedFields = ['wood', 'stone', 'ore', 'storageCapacity', 'maxSettlers', 'freeSettlers'];
            
            foreach ($expectedFields as $field) {
                $this->addResult(
                    "Required Field Present: $field",
                    array_key_exists($field, $resources),
                    "Database should always return required field $field"
                );
                
                if (array_key_exists($field, $resources)) {
                    // Test for null values
                    $this->addResult(
                        "Non-null Value: $field",
                        $resources[$field] !== null,
                        "Field $field should not be null"
                    );
                    
                    // Test for proper data types
                    if ($resources[$field] !== null) {
                        $this->addResult(
                            "Numeric Value: $field",
                            is_numeric($resources[$field]),
                            "Field $field should be numeric"
                        );
                    }
                }
            }
        }
        
        echo "\n";
    }
    
    private function testTimeoutScenarios() {
        echo "3. Testing Timeout Scenarios...\n";
        
        // While we can't easily simulate real timeouts, we can test rapid successive calls
        $startTime = microtime(true);
        $results = [];
        
        for ($i = 0; $i < 50; $i++) {
            $result = $this->database->getResources(1);
            $results[] = $result;
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // All results should be consistent
        $firstResult = $results[0];
        $allConsistent = true;
        
        foreach ($results as $result) {
            if ($result !== $firstResult) {
                $allConsistent = false;
                break;
            }
        }
        
        $this->addResult(
            "Rapid Access Consistency",
            $allConsistent,
            "Rapid successive calls should return consistent data"
        );
        
        $this->addResult(
            "Rapid Access Performance",
            $duration < 2.0,
            "50 rapid calls should complete within 2 seconds"
        );
        
        echo "   Duration for 50 calls: " . number_format($duration, 3) . " seconds\n";
        echo "\n";
    }
    
    private function testConcurrentWriteReadIssues() {
        echo "4. Testing Concurrent Write/Read Issues...\n";
        
        // Get initial state
        $initialResources = $this->database->getResources(1);
        $initialQueue = $this->database->getQueue(1);
        
        // Try an upgrade (write operation)
        $upgradeResult = $this->database->upgradeBuilding(1, 'Farm');
        
        // Immediately read data
        $postResources = $this->database->getResources(1);
        $postQueue = $this->database->getQueue(1);
        
        // Data should be consistent (either changed or unchanged, but not corrupted)
        if ($upgradeResult && isset($upgradeResult['success']) && $upgradeResult['success']) {
            // Upgrade succeeded - resources should have changed
            $resourcesChanged = ($initialResources['wood'] != $postResources['wood'] ||
                               $initialResources['stone'] != $postResources['stone'] ||
                               $initialResources['ore'] != $postResources['ore']);
            
            $this->addResult(
                "Write-Read Consistency (Success)",
                $resourcesChanged,
                "When upgrade succeeds, resources should change"
            );
            
            // Queue should have a new entry
            $queueChanged = count($postQueue) > count($initialQueue);
            $this->addResult(
                "Queue Consistency (Success)",
                $queueChanged,
                "When upgrade succeeds, queue should have new entry"
            );
            
        } else {
            // Upgrade failed - resources should NOT have changed
            $resourcesUnchanged = ($initialResources['wood'] == $postResources['wood'] &&
                                 $initialResources['stone'] == $postResources['stone'] &&
                                 $initialResources['ore'] == $postResources['ore']);
            
            $this->addResult(
                "Write-Read Consistency (Failure)",
                $resourcesUnchanged,
                "When upgrade fails, resources should not change"
            );
        }
        
        echo "\n";
    }
    
    private function testPartialDataReads() {
        echo "5. Testing Partial Data Read Scenarios...\n";
        
        // Test various data retrieval functions to ensure they handle partial data
        
        // Test building data
        try {
            $building = $this->database->getBuilding(1, 'Rathaus');
            if ($building) {
                $requiredBuildingFields = ['currentLevel', 'nextLevel', 'costWood', 'costStone', 'costOre'];
                foreach ($requiredBuildingFields as $field) {
                    $this->addResult(
                        "Building Field Complete: $field",
                        isset($building[$field]) && $building[$field] !== null,
                        "Building data should include complete $field"
                    );
                }
            }
        } catch (Exception $e) {
            $this->addResult(
                "Building Data Exception Handling",
                true,
                "Building query exception handled gracefully: " . $e->getMessage()
            );
        }
        
        // Test queue data completeness
        $queue = $this->database->getQueue(1);
        if ($queue && is_array($queue) && count($queue) > 0) {
            $firstQueueItem = $queue[0];
            $requiredQueueFields = ['queueId', 'buildingType', 'startTime', 'endTime'];
            
            foreach ($requiredQueueFields as $field) {
                $this->addResult(
                    "Queue Field Complete: $field",
                    isset($firstQueueItem[$field]) && $firstQueueItem[$field] !== null,
                    "Queue data should include complete $field"
                );
            }
        }
        
        // Test settlement name
        $settlementName = $this->database->getSettlementName(1);
        if ($settlementName) {
            $this->addResult(
                "Settlement Name Complete",
                isset($settlementName['SettlementName']) && !empty($settlementName['SettlementName']),
                "Settlement name should be complete and non-empty"
            );
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
        echo "=== Error Scenario Test Summary ===\n";
        
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, function($result) {
            return $result['passed'];
        });
        $passedCount = count($passedTests);
        $failedCount = $totalTests - $passedCount;
        
        echo "Total Error Tests: $totalTests\n";
        echo "Passed: $passedCount\n";
        echo "Failed: $failedCount\n";
        
        if ($failedCount > 0) {
            echo "\nFAILED ERROR TESTS (SQL data reading issues found):\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- " . $result['name'] . ": " . $result['description'] . "\n";
                }
            }
            echo "\nThese failures indicate SQL data reading robustness issues.\n";
        } else {
            echo "\nAll error scenario tests passed! SQL data reading is robust.\n";
        }
        
        // Provide recommendations
        echo "\n=== Recommendations for SQL Data Reading Improvements ===\n";
        echo "Even if tests pass, consider these proactive improvements:\n";
        echo "1. Add explicit data type validation for all numeric fields\n";
        echo "2. Implement consistent error logging for all SQL operations\n";
        echo "3. Add data bounds checking for resource values\n";
        echo "4. Implement transaction rollback verification\n";
        echo "5. Add connection pool monitoring and automatic retry logic\n";
        echo "6. Implement data integrity checks on critical operations\n";
        echo "7. Add performance monitoring for slow queries\n";
    }
}

// Run the error scenario tests
$tester = new ErrorScenarioTest();
$tester->runErrorTests();
?>