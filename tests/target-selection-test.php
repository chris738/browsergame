<?php
/**
 * Target Selection Functionality Test
 * 
 * Tests the new "Select from Map" feature implementation
 */

class TargetSelectionTest {
    private $passed = 0;
    private $failed = 0;
    
    public function run() {
        echo "=== Target Selection Functionality Tests ===\n";
        
        // Test 1: Check if battle.php contains the Select from Map button
        $this->testBattlePageHasSelectFromMapButton();
        
        // Test 2: Check if map.php handles selectTarget mode
        $this->testMapPageHandlesSelectTargetMode();
        
        // Test 3: Check if CSS contains target selection styles
        $this->testCSSContainsTargetSelectionStyles();
        
        // Test 4: Check if JavaScript functions exist in battle.php
        $this->testBattlePageHasSelectFromMapFunction();
        
        // Test 5: Check if JavaScript functions exist in map.php
        $this->testMapPageHasSelectThisTargetFunction();
        
        echo "\n=== Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        return $this->failed === 0;
    }
    
    private function testBattlePageHasSelectFromMapButton() {
        echo "Test 1: Battle page has 'Select from Map' button... ";
        
        $battleContent = file_get_contents(__DIR__ . '/../battle.php');
        
        // Check if the button exists with proper structure
        $hasButton = strpos($battleContent, 'Select from Map') !== false;
        $hasButtonFunction = strpos($battleContent, 'onclick="selectFromMap()"') !== false;
        $hasIcon = strpos($battleContent, '🗺️') !== false;
        
        if ($hasButton && $hasButtonFunction && $hasIcon) {
            $this->passed++;
            echo "PASS\n";
        } else {
            $this->failed++;
            echo "FAIL - Button structure not found\n";
        }
    }
    
    private function testMapPageHandlesSelectTargetMode() {
        echo "Test 2: Map page handles selectTarget mode... ";
        
        $mapContent = file_get_contents(__DIR__ . '/../map.php');
        
        // Check if selectTarget mode is handled
        $hasSelectTargetMode = strpos($mapContent, 'selectTarget') !== false;
        $hasTargetSelectionHeader = strpos($mapContent, 'Select Target for Attack') !== false;
        $hasReturnToParam = strpos($mapContent, 'returnTo') !== false;
        
        if ($hasSelectTargetMode && $hasTargetSelectionHeader && $hasReturnToParam) {
            $this->passed++;
            echo "PASS\n";
        } else {
            $this->failed++;
            echo "FAIL - Select target mode not properly handled\n";
        }
    }
    
    private function testCSSContainsTargetSelectionStyles() {
        echo "Test 3: CSS contains target selection styles... ";
        
        $cssContent = file_get_contents(__DIR__ . '/../css/style.css');
        $battleContent = file_get_contents(__DIR__ . '/../battle.php');
        
        // Check if main CSS contains the new styles
        $hasTargetSelectionStyles = strpos($cssContent, 'target-selection-mode-header') !== false;
        
        // Check if battle.php contains button styles
        $hasSelectFromMapBtn = strpos($battleContent, 'select-from-map-btn') !== false;
        
        // Check if CSS contains select target button styles
        $hasSelectTargetBtn = strpos($cssContent, 'action-button.select-target') !== false;
        
        if ($hasTargetSelectionStyles && $hasSelectFromMapBtn && $hasSelectTargetBtn) {
            $this->passed++;
            echo "PASS\n";
        } else {
            $this->failed++;
            echo "FAIL - Required CSS styles not found\n";
            if (!$hasTargetSelectionStyles) echo "  - Missing target-selection-mode-header in main CSS\n";
            if (!$hasSelectFromMapBtn) echo "  - Missing select-from-map-btn in battle.php\n"; 
            if (!$hasSelectTargetBtn) echo "  - Missing action-button.select-target in main CSS\n";
        }
    }
    
    private function testBattlePageHasSelectFromMapFunction() {
        echo "Test 4: Battle page has selectFromMap JavaScript function... ";
        
        $battleContent = file_get_contents(__DIR__ . '/../battle.php');
        
        // Check if the selectFromMap function exists
        $hasFunction = strpos($battleContent, 'function selectFromMap()') !== false;
        $hasMapNavigation = strpos($battleContent, 'mode=selectTarget') !== false;
        
        if ($hasFunction && $hasMapNavigation) {
            $this->passed++;
            echo "PASS\n";
        } else {
            $this->failed++;
            echo "FAIL - selectFromMap function not found or incomplete\n";
        }
    }
    
    private function testMapPageHasSelectThisTargetFunction() {
        echo "Test 5: Map page has selectThisTarget JavaScript function... ";
        
        $mapContent = file_get_contents(__DIR__ . '/../map.php');
        
        // Check if the selectThisTarget function exists
        $hasFunction = strpos($mapContent, 'function selectThisTarget') !== false;
        $hasBattleNavigation = strpos($mapContent, 'battle.php?settlementId=') !== false;
        
        if ($hasFunction && $hasBattleNavigation) {
            $this->passed++;
            echo "PASS\n";
        } else {
            $this->failed++;
            echo "FAIL - selectThisTarget function not found or incomplete\n";
        }
    }
}

// Run the tests
$test = new TargetSelectionTest();
$success = $test->run();

exit($success ? 0 : 1);
?>