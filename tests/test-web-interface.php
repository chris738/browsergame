<?php
/**
 * Test simulated web request for barracks upgrade
 * This simulates what happens when a user clicks the upgrade button
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';
require_once 'php/backend.php';

class WebInterfaceTest {
    public function testBarracksUpgradeRequest() {
        echo "=== Testing Simulated Web Request for Barracks Upgrade ===\n\n";
        
        // Simulate the web request that happens when user clicks upgrade
        $settlementId = 1;
        $buildingType = 'Kaserne';
        
        echo "1. Simulating user click on 'Upgrade Kaserne' button...\n";
        echo "   Settlement ID: $settlementId\n";
        echo "   Building Type: $buildingType\n\n";
        
        // Get current resources before upgrade
        $database = new Database();
        $initialResources = $database->getResources($settlementId);
        
        echo "2. Current resources before upgrade:\n";
        if ($initialResources) {
            echo "   - Wood: " . $initialResources['wood'] . "\n";
            echo "   - Stone: " . $initialResources['stone'] . "\n";
            echo "   - Ore: " . $initialResources['ore'] . "\n";
            echo "   - Free Settlers: " . ($initialResources['freeSettlers'] ?? 0) . "\n";
        }
        echo "\n";
        
        // Get building info
        echo "3. Getting building configuration...\n";
        try {
            $buildingInfo = $database->getBuilding($settlementId, $buildingType);
            if ($buildingInfo) {
                echo "   - Current Level: " . ($buildingInfo['currentLevel'] ?? 'N/A') . "\n";
                echo "   - Next Level: " . ($buildingInfo['nextLevel'] ?? 'N/A') . "\n";
                echo "   - Required Wood: " . ($buildingInfo['costWood'] ?? 'N/A') . "\n";
                echo "   - Required Stone: " . ($buildingInfo['costStone'] ?? 'N/A') . "\n";
                echo "   - Required Ore: " . ($buildingInfo['costOre'] ?? 'N/A') . "\n";
                echo "   - Required Settlers: " . ($buildingInfo['settlers'] ?? 'N/A') . "\n";
                echo "   - Build Time: " . ($buildingInfo['buildTime'] ?? 'N/A') . " seconds\n";
            }
        } catch (Exception $e) {
            echo "   Error getting building info: " . $e->getMessage() . "\n";
        }
        echo "\n";
        
        // Attempt the upgrade (this is what the backend does)
        echo "4. Attempting upgrade...\n";
        try {
            $upgradeResult = $database->upgradeBuilding($settlementId, $buildingType);
            
            if ($upgradeResult && is_array($upgradeResult)) {
                $success = $upgradeResult['success'] ?? false;
                $message = $upgradeResult['message'] ?? 'No message';
                
                echo "   Result: " . ($success ? "SUCCESS" : "FAILED") . "\n";
                echo "   Message: $message\n";
                
                // Check for the specific error we were fixing
                if (!$success && strpos($message, 'Nicht genügend Ressourcen für das Upgrade') !== false) {
                    echo "\n   *** ERROR STILL PRESENT ***\n";
                    echo "   The user would still see the resource error!\n";
                    return false;
                } else if ($success) {
                    echo "\n   *** SUCCESS ***\n";
                    echo "   The upgrade worked! User would see success message.\n";
                    return true;
                } else {
                    echo "\n   *** DIFFERENT ERROR ***\n";
                    echo "   There's a different issue: $message\n";
                    return false;
                }
            } else {
                echo "   Invalid response format\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "   Exception during upgrade: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function testMultipleBuildings() {
        echo "\n=== Testing Multiple Building Types ===\n";
        
        $database = new Database();
        $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
        
        foreach ($buildingTypes as $buildingType) {
            echo "\nTesting $buildingType upgrade:\n";
            
            try {
                $result = $database->upgradeBuilding(1, $buildingType);
                if ($result && is_array($result)) {
                    $success = $result['success'] ?? false;
                    $message = $result['message'] ?? 'No message';
                    
                    $status = $success ? "SUCCESS" : "FAILED";
                    echo "   $status: $message\n";
                    
                    // Check for resource error
                    if (!$success && strpos($message, 'Nicht genügend Ressourcen für das Upgrade') !== false) {
                        echo "   ⚠️  Resource error found for $buildingType!\n";
                    }
                } else {
                    echo "   Invalid response\n";
                }
            } catch (Exception $e) {
                echo "   Exception: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Run the test
$tester = new WebInterfaceTest();
$success = $tester->testBarracksUpgradeRequest();

if ($success) {
    echo "\n🎉 ISSUE RESOLVED! 🎉\n";
    echo "The barracks upgrade should now work properly for users.\n";
} else {
    echo "\n❌ Issue may still exist\n";
    echo "Further investigation needed.\n";
}

$tester->testMultipleBuildings();

echo "\n=== Test Complete ===\n";
?>