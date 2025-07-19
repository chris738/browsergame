<?php
/**
 * Database Test
 * Tests database connectivity and basic operations
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';

class DatabaseTest {
    private $database;
    private $testResults = [];

    public function __construct() {
        try {
            $this->database = new Database();
        } catch (Exception $e) {
            echo "Failed to initialize database: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function run($args = []) {
        echo "=== Database Connectivity Test ===\n";
        
        $this->testDatabaseConnection();
        $this->testBasicOperations();
        
        $this->reportResults();
        return count(array_filter($this->testResults)) === count($this->testResults);
    }

    private function testDatabaseConnection() {
        echo "Testing database connection... ";
        try {
            // Try a simple operation to test connection
            $playerCount = $this->database->getPlayerCount();
            if (is_numeric($playerCount) || $playerCount === null) {
                echo "OK\n";
                $this->testResults['connection'] = true;
            } else {
                echo "FAILED - Unexpected result\n";
                $this->testResults['connection'] = false;
            }
        } catch (Exception $e) {
            echo "FAILED - " . $e->getMessage() . "\n";
            $this->testResults['connection'] = false;
        }
    }

    private function testBasicOperations() {
        echo "Testing basic database operations... ";
        try {
            // Test if we can get resources for a settlement (basic read operation)
            $resources = $this->database->getResources(1);
            if (is_array($resources)) {
                echo "OK\n";
                $this->testResults['basic_ops'] = true;
            } else {
                echo "FAILED - Could not retrieve resources\n";
                $this->testResults['basic_ops'] = false;
            }
        } catch (Exception $e) {
            echo "FAILED - " . $e->getMessage() . "\n";
            $this->testResults['basic_ops'] = false;
        }
    }

    private function reportResults() {
        echo "\n=== Database Test Results ===\n";
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $test => $result) {
            $status = $result ? 'PASSED' : 'FAILED';
            echo "- " . ucfirst(str_replace('_', ' ', $test)) . ": $status\n";
            if ($result) $passed++;
        }
        
        echo "\nSummary: $passed/$total tests passed\n";
        echo "=== Database Test Complete ===\n";
    }
}

// Run the test if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new DatabaseTest();
    $result = $test->run($argv ?? []);
    exit($result ? 0 : 1);
}
