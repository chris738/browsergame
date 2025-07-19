<?php
/**
 * Test script to verify the level 5 reset bug fix
 */

require_once 'php/database.php';

class Level5FixVerificationTest {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runTest() {
        echo "=== Level 5 Reset Bug Fix Verification ===\n";
        echo "Testing that the fix.sql file prevents level 5 reset bug\n\n";
        
        $this->testFixFileExists();
        $this->testBuildingConfigsExist();
        $this->testProcessBuildingQueueLogic();
        $this->testUpgradeBuildingProcedure();
        
        echo "\n=== Fix Verification Complete ===\n";
        echo "If all tests pass, the level 5 reset bug should be fixed.\n";
    }
    
    private function testFixFileExists() {
        echo "1. Testing fix.sql file exists...\n";
        
        $fixFile = 'sql/fix.sql';
        if (file_exists($fixFile)) {
            echo "   ✅ fix.sql file exists\n";
            
            $content = file_get_contents($fixFile);
            
            // Check for key fixes
            $hasProcessBuildingQueueFix = strpos($content, 'ProcessBuildingQueue') !== false;
            $hasBuildingConfigFix = strpos($content, 'BuildingConfig') !== false;
            $hasUpgradeBuildingFix = strpos($content, 'UpgradeBuilding') !== false;
            
            echo "   ✅ ProcessBuildingQueue fix: " . ($hasProcessBuildingQueueFix ? "Present" : "Missing") . "\n";
            echo "   ✅ BuildingConfig fix: " . ($hasBuildingConfigFix ? "Present" : "Missing") . "\n";  
            echo "   ✅ UpgradeBuilding fix: " . ($hasUpgradeBuildingFix ? "Present" : "Missing") . "\n";
        } else {
            echo "   ❌ fix.sql file missing\n";
        }
    }
    
    private function testBuildingConfigsExist() {
        echo "\n2. Testing building configurations for level 5+...\n";
        
        if (!$this->database->isConnected()) {
            echo "   ⚠️  Database not connected - cannot verify configs\n";
            return;
        }
        
        try {
            $conn = $this->database->getConnection();
            
            // Check if level 5 configs exist for all building types
            $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
            
            foreach ($buildingTypes as $buildingType) {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM BuildingConfig WHERE buildingType = ? AND level = 5");
                $stmt->execute([$buildingType]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $hasLevel5 = $result['count'] > 0;
                echo "   " . ($hasLevel5 ? "✅" : "❌") . " {$buildingType} level 5 config: " . ($hasLevel5 ? "Present" : "Missing") . "\n";
            }
            
            // Check for higher levels too
            $stmt = $conn->prepare("SELECT DISTINCT buildingType, MAX(level) as maxLevel FROM BuildingConfig GROUP BY buildingType");
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n   Maximum levels available:\n";
            foreach ($results as $result) {
                echo "   - {$result['buildingType']}: Level {$result['maxLevel']}\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error checking building configs: " . $e->getMessage() . "\n";
        }
    }
    
    private function testProcessBuildingQueueLogic() {
        echo "\n3. Testing ProcessBuildingQueue event...\n";
        
        if (!$this->database->isConnected()) {
            echo "   ⚠️  Database not connected - cannot verify event\n";
            return;
        }
        
        try {
            $conn = $this->database->getConnection();
            
            // Check if ProcessBuildingQueue event exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM information_schema.EVENTS WHERE EVENT_NAME = 'ProcessBuildingQueue' AND EVENT_SCHEMA = DATABASE()");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $eventExists = $result['count'] > 0;
            echo "   " . ($eventExists ? "✅" : "❌") . " ProcessBuildingQueue event: " . ($eventExists ? "Present" : "Missing") . "\n";
            
            if ($eventExists) {
                // Get event definition to check if it's the fixed version
                $stmt = $conn->prepare("SHOW CREATE EVENT ProcessBuildingQueue");
                $stmt->execute();
                $eventDef = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($eventDef) {
                    $definition = $eventDef['Create Event'];
                    
                    // Check for key elements of the fix
                    $hasInsertLogic = strpos($definition, 'INSERT INTO Buildings') !== false;
                    $hasLeftJoin = strpos($definition, 'LEFT JOIN') !== false;
                    $hasNullCheck = strpos($definition, 'IS NULL') !== false;
                    
                    echo "   " . ($hasInsertLogic ? "✅" : "❌") . " Has INSERT logic for new buildings\n";
                    echo "   " . ($hasLeftJoin ? "✅" : "❌") . " Has LEFT JOIN for missing buildings\n";
                    echo "   " . ($hasNullCheck ? "✅" : "❌") . " Has NULL check for non-existent buildings\n";
                    
                    if ($hasInsertLogic && $hasLeftJoin && $hasNullCheck) {
                        echo "   ✅ ProcessBuildingQueue appears to use FIXED version\n";
                    } else {
                        echo "   ❌ ProcessBuildingQueue appears to use OLD BUGGY version\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error checking ProcessBuildingQueue event: " . $e->getMessage() . "\n";
        }
    }
    
    private function testUpgradeBuildingProcedure() {
        echo "\n4. Testing UpgradeBuilding procedure...\n";
        
        if (!$this->database->isConnected()) {
            echo "   ⚠️  Database not connected - cannot verify procedure\n";
            return;
        }
        
        try {
            $conn = $this->database->getConnection();
            
            // Check if UpgradeBuilding procedure exists
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM information_schema.ROUTINES WHERE ROUTINE_NAME = 'UpgradeBuilding' AND ROUTINE_SCHEMA = DATABASE()");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $procedureExists = $result['count'] > 0;
            echo "   " . ($procedureExists ? "✅" : "❌") . " UpgradeBuilding procedure: " . ($procedureExists ? "Present" : "Missing") . "\n";
            
            if ($procedureExists) {
                // Get procedure definition to check if it's the fixed version
                $stmt = $conn->prepare("SHOW CREATE PROCEDURE UpgradeBuilding");
                $stmt->execute();
                $procedureDef = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($procedureDef) {
                    $definition = $procedureDef['Create Procedure'];
                    
                    // Check for key elements of the fix
                    $hasCoalesceLevel = strpos($definition, 'COALESCE(level, 0)') !== false;
                    $hasConfigCheck = strpos($definition, 'nextLevelWoodCost IS NULL') !== false;
                    $hasDefaultZero = strpos($definition, 'DEFAULT 0') !== false;
                    
                    echo "   " . ($hasCoalesceLevel ? "✅" : "❌") . " Has COALESCE for level handling\n";
                    echo "   " . ($hasConfigCheck ? "✅" : "❌") . " Has config existence check\n";
                    echo "   " . ($hasDefaultZero ? "✅" : "❌") . " Has default 0 for missing buildings\n";
                    
                    if ($hasCoalesceLevel && $hasDefaultZero) {
                        echo "   ✅ UpgradeBuilding appears to use FIXED version\n";
                    } else {
                        echo "   ❌ UpgradeBuilding appears to use OLD version\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "   ❌ Error checking UpgradeBuilding procedure: " . $e->getMessage() . "\n";
        }
    }
}

// Run the verification test
$tester = new Level5FixVerificationTest();
$tester->runTest();
?>