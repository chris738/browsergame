<?php
/**
 * Test barracks (Kaserne) building upgrade functionality
 * This test specifically addresses the issue "Nicht genügend Ressourcen für das Upgrade"
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';

class BarracksUpgradeTest {
    private $database;
    private $testResults = [];
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runTests() {
        echo "=== Barracks (Kaserne) Building Upgrade Tests ===\n";
        echo "Testing the resource issue: 'Nicht genügend Ressourcen für das Upgrade'\n\n";
        
        $this->testDatabaseConnection();
        $this->testResourceAvailability();
        $this->testBuildingConfig();
        $this->testBarracksUpgrade();
        $this->testSettlerAvailability();
        
        $this->printSummary();
    }
    
    private function testDatabaseConnection() {
        echo "1. Testing Database Connection...\n";
        
        $isConnected = $this->database->isConnected();
        $this->addResult(
            "Database Connection Status",
            is_bool($isConnected),
            "Database connection status should be boolean"
        );
        
        echo "   Database connected: " . ($isConnected ? "YES" : "NO (using mock data)") . "\n";
        
        if (!$isConnected) {
            echo "   NOTE: Using mock data - this should now have sufficient resources\n";
        }
        
        echo "\n";
    }
    
    private function testResourceAvailability() {
        echo "2. Testing Resource Availability for Kaserne...\n";
        
        // Test resources for settlement ID 1
        $resources = $this->database->getResources(1);
        
        if ($resources) {
            echo "   Current Resources:\n";
            echo "   - Wood: " . $resources['wood'] . "\n";
            echo "   - Stone: " . $resources['stone'] . "\n";
            echo "   - Ore: " . $resources['ore'] . "\n";
            echo "   - Free Settlers: " . ($resources['freeSettlers'] ?? 0) . "\n";
            
            // Check if resources are sufficient for Kaserne level 2 (since we're upgrading from level 1)
            // Based on building config: Wood: 165, Stone: 165, Ore: 220, Settlers: 2.2
            $sufficientWood = $resources['wood'] >= 165;
            $sufficientStone = $resources['stone'] >= 165;
            $sufficientOre = $resources['ore'] >= 220;
            $sufficientSettlers = ($resources['freeSettlers'] ?? 0) >= 2.2;
            
            $this->addResult(
                "Sufficient Wood for Kaserne",
                $sufficientWood,
                "Need 165 wood, have " . $resources['wood']
            );
            
            $this->addResult(
                "Sufficient Stone for Kaserne",
                $sufficientStone,
                "Need 165 stone, have " . $resources['stone']
            );
            
            $this->addResult(
                "Sufficient Ore for Kaserne",
                $sufficientOre,
                "Need 220 ore, have " . $resources['ore']
            );
            
            $this->addResult(
                "Sufficient Settlers for Kaserne",
                $sufficientSettlers,
                "Need 2.2 settlers, have " . ($resources['freeSettlers'] ?? 0)
            );
            
            $allSufficient = $sufficientWood && $sufficientStone && $sufficientOre && $sufficientSettlers;
            $this->addResult(
                "All Resources Sufficient for Kaserne",
                $allSufficient,
                "All resources should be sufficient to build Kaserne"
            );
        } else {
            $this->addResult(
                "Resources Retrieved",
                false,
                "Failed to retrieve resources"
            );
        }
        
        echo "\n";
    }
    
    private function testBuildingConfig() {
        echo "3. Testing Building Configuration for Kaserne...\n";
        
        try {
            $building = $this->database->getBuilding(1, 'Kaserne');
            
            if ($building) {
                echo "   Kaserne Building Config:\n";
                echo "   - Current Level: " . ($building['currentLevel'] ?? 'N/A') . "\n";
                echo "   - Next Level: " . ($building['nextLevel'] ?? 'N/A') . "\n";
                echo "   - Cost Wood: " . ($building['costWood'] ?? 'N/A') . "\n";
                echo "   - Cost Stone: " . ($building['costStone'] ?? 'N/A') . "\n";
                echo "   - Cost Ore: " . ($building['costOre'] ?? 'N/A') . "\n";
                echo "   - Settlers: " . ($building['settlers'] ?? 'N/A') . "\n";
                echo "   - Build Time: " . ($building['buildTime'] ?? 'N/A') . " seconds\n";
                
                $this->addResult(
                    "Building Config Retrieved",
                    isset($building['costWood']),
                    "Building configuration should be available for Kaserne"
                );
                
                // Verify costs match expected values for level 2 (since we're at level 1)
                $expectedWood = 165;  // Level 2: 150 * 1.1 
                $expectedStone = 165; // Level 2: 150 * 1.1
                $expectedOre = 220;   // Level 2: 200 * 1.1
                $expectedSettlers = 2.2; // Level 2: 2 * 1.1
                
                $this->addResult(
                    "Correct Wood Cost",
                    isset($building['costWood']) && $building['costWood'] == $expectedWood,
                    "Expected wood cost $expectedWood, got " . ($building['costWood'] ?? 'N/A')
                );
                
                $this->addResult(
                    "Correct Stone Cost",
                    isset($building['costStone']) && $building['costStone'] == $expectedStone,
                    "Expected stone cost $expectedStone, got " . ($building['costStone'] ?? 'N/A')
                );
                
            } else {
                $this->addResult(
                    "Building Config Retrieved",
                    false,
                    "Failed to retrieve building configuration for Kaserne"
                );
            }
        } catch (Exception $e) {
            $this->addResult(
                "Building Config Exception",
                false,
                "Exception when getting building config: " . $e->getMessage()
            );
        }
        
        echo "\n";
    }
    
    private function testBarracksUpgrade() {
        echo "4. Testing Kaserne Upgrade...\n";
        
        // Get initial resources
        $initialResources = $this->database->getResources(1);
        echo "   Initial resources before upgrade attempt:\n";
        if ($initialResources) {
            echo "   - Wood: " . $initialResources['wood'] . "\n";
            echo "   - Stone: " . $initialResources['stone'] . "\n";
            echo "   - Ore: " . $initialResources['ore'] . "\n";
            echo "   - Free Settlers: " . ($initialResources['freeSettlers'] ?? 0) . "\n";
        }
        
        // Attempt to upgrade Kaserne
        try {
            $upgradeResult = $this->database->upgradeBuilding(1, 'Kaserne');
            
            echo "   Upgrade attempt result:\n";
            if ($upgradeResult && is_array($upgradeResult)) {
                echo "   - Success: " . ($upgradeResult['success'] ? 'YES' : 'NO') . "\n";
                echo "   - Message: " . ($upgradeResult['message'] ?? 'No message') . "\n";
                
                $this->addResult(
                    "Upgrade Attempt Successful",
                    $upgradeResult['success'] ?? false,
                    "Kaserne upgrade should succeed with sufficient resources: " . ($upgradeResult['message'] ?? 'No message')
                );
                
                // Check for the specific error message we're trying to fix
                if (!($upgradeResult['success'] ?? false)) {
                    $errorMessage = $upgradeResult['message'] ?? '';
                    $isResourceError = strpos($errorMessage, 'Nicht genügend Ressourcen für das Upgrade') !== false;
                    
                    $this->addResult(
                        "Not the Resource Error",
                        !$isResourceError,
                        "Should not get 'Nicht genügend Ressourcen' error with sufficient resources"
                    );
                    
                    if ($isResourceError) {
                        echo "   *** ISSUE FOUND: Still getting resource error despite sufficient resources ***\n";
                    }
                }
                
            } else {
                $this->addResult(
                    "Upgrade Response Format",
                    false,
                    "Upgrade should return array response"
                );
            }
            
        } catch (Exception $e) {
            echo "   Exception during upgrade: " . $e->getMessage() . "\n";
            $this->addResult(
                "Upgrade Exception",
                false,
                "Upgrade threw exception: " . $e->getMessage()
            );
        }
        
        echo "\n";
    }
    
    private function testSettlerAvailability() {
        echo "5. Testing Settler Availability...\n";
        
        // Try to get settlement info to understand settler calculations
        $resources = $this->database->getResources(1);
        
        if ($resources) {
            echo "   Settler Information:\n";
            echo "   - Max Settlers: " . ($resources['maxSettlers'] ?? 'N/A') . "\n";
            echo "   - Free Settlers: " . ($resources['freeSettlers'] ?? 'N/A') . "\n";
            
            $hasSettlers = ($resources['freeSettlers'] ?? 0) > 0;
            $this->addResult(
                "Has Free Settlers",
                $hasSettlers,
                "Settlement should have some free settlers available"
            );
            
            $sufficientForBuilding = ($resources['freeSettlers'] ?? 0) >= 2.2;
            $this->addResult(
                "Sufficient Settlers for Building",
                $sufficientForBuilding,
                "Should have at least 2.2 free settlers for Kaserne level 2"
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
            echo "          ^ This may indicate the source of the resource error\n";
        }
    }
    
    private function printSummary() {
        echo "=== Barracks Upgrade Test Summary ===\n";
        
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
            echo "\nFAILED TESTS (potential issues):\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- " . $result['name'] . ": " . $result['description'] . "\n";
                }
            }
            echo "\nThese failures may explain the 'Nicht genügend Ressourcen' error.\n";
        } else {
            echo "\nAll tests passed! The resource error should be fixed.\n";
        }
        
        echo "\n=== Next Steps ===\n";
        if ($failedCount > 0) {
            echo "1. Address the failed tests above\n";
            echo "2. Ensure database connection is working properly\n";
            echo "3. Verify SettlementSettlers view is functioning\n";
            echo "4. Check that Kaserne is properly added to all ENUM fields\n";
        } else {
            echo "1. Test the fix in the actual game interface\n";
            echo "2. Try building barracks through the web interface\n";
            echo "3. Verify the fix works with real user interactions\n";
        }
    }
}

// Run the test
$tester = new BarracksUpgradeTest();
$tester->runTests();
?>