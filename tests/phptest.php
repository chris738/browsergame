<?php
/**
 * PHP Info Test
 * Simple test to verify PHP installation and configuration
 */

class PHPInfoTest {
    
    public function run() {
        echo "=== PHP Configuration Test ===\n";
        echo "PHP Version: " . phpversion() . "\n";
        echo "Memory Limit: " . ini_get('memory_limit') . "\n";
        echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
        echo "Error Reporting: " . ini_get('error_reporting') . "\n";
        
        // Check required extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session'];
        echo "\nRequired Extensions:\n";
        foreach ($requiredExtensions as $ext) {
            $status = extension_loaded($ext) ? 'OK' : 'MISSING';
            echo "- $ext: $status\n";
        }
        
        echo "\n=== PHP Configuration Test Complete ===\n";
        return true;
    }
}

// Run the test if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PHPInfoTest();
    $test->run();
}
