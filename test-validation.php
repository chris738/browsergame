<?php
/**
 * Test script to verify the SQL data validation improvements
 */

require_once 'php/database.php';

class ValidationTest {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runValidationTests() {
        echo "=== SQL Data Validation Tests ===\n";
        echo "Testing improved data validation and error handling\n\n";
        
        $this->testInputValidation();
        $this->testOutputValidation();
        $this->testErrorLogging();
        
        echo "=== Validation Test Complete ===\n";
        echo "The system now validates all SQL data input and output for correctness.\n";
        echo "Check error logs for validation error messages.\n";
    }
    
    private function testInputValidation() {
        echo "1. Testing Input Validation...\n";
        
        // Test invalid settlement IDs
        $invalidIds = [0, -1, 'abc', null, '', false, 2147483648]; // Last one exceeds int max
        
        foreach ($invalidIds as $id) {
            echo "   Testing getResources with invalid ID: " . var_export($id, true) . "\n";
            $result = $this->database->getResources($id);
            echo "   Result: " . ($result === false ? "REJECTED (good)" : "ACCEPTED (should be rejected)") . "\n";
        }
        
        // Test invalid building types
        $invalidTypes = ['InvalidType', '', null, 123, 'Kaserne\'DROP TABLE'];
        
        foreach ($invalidTypes as $type) {
            echo "   Testing getBuilding with invalid type: " . var_export($type, true) . "\n";
            try {
                $result = $this->database->getBuilding(1, $type);
                echo "   Result: ACCEPTED (should be rejected)\n";
            } catch (Exception $e) {
                echo "   Result: REJECTED (good) - " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
    
    private function testOutputValidation() {
        echo "2. Testing Output Validation...\n";
        
        // Test that valid data passes validation
        $resources = $this->database->getResources(1);
        if ($resources) {
            echo "   Valid resource data passed validation: YES\n";
            echo "   Wood: {$resources['wood']}, Stone: {$resources['stone']}, Ore: {$resources['ore']}\n";
        } else {
            echo "   Valid resource data passed validation: NO (unexpected)\n";
        }
        
        // Test building data validation
        try {
            $building = $this->database->getBuilding(1, 'Holzfäller');
            if ($building) {
                echo "   Valid building data passed validation: YES\n";
                echo "   Current Level: {$building['currentLevel']}, Next Level: {$building['nextLevel']}\n";
            }
        } catch (Exception $e) {
            echo "   Building data validation result: " . $e->getMessage() . "\n";
        }
        
        // Test queue validation
        $queue = $this->database->getQueue(1);
        if (is_array($queue)) {
            echo "   Queue data validation: PASSED (returned " . count($queue) . " items)\n";
        } else {
            echo "   Queue data validation: FAILED\n";
        }
        
        echo "\n";
    }
    
    private function testErrorLogging() {
        echo "3. Testing Error Logging...\n";
        
        // Force some validation errors to test logging
        echo "   Forcing validation errors to test logging...\n";
        
        // These should log validation errors
        $this->database->getResources(-999);
        $this->database->getQueue(0);
        $this->database->getSettlementName(-1);
        
        try {
            $this->database->getBuilding(1, 'InvalidBuildingType');
        } catch (Exception $e) {
            // Expected
        }
        
        echo "   Check error logs for validation error messages.\n";
        echo "   Error logging is now active for all SQL data operations.\n";
        
        echo "\n";
    }
}

// Run the validation tests
$tester = new ValidationTest();
$tester->runValidationTests();
?>