#!/usr/bin/env php
<?php
/**
 * Test Runner Script
 * Runs all tests in the tests directory
 */

define('ROOT_DIR', dirname(__DIR__));
chdir(ROOT_DIR);

class TestRunner {
    private $testFiles = [];
    private $results = [];
    
    public function __construct() {
        $this->findTestFiles();
    }
    
    private function findTestFiles() {
        $testDir = ROOT_DIR . '/tests';
        $files = glob($testDir . '/test-*.php');
        $files = array_merge($files, glob($testDir . '/*test.php'));
        
        // Exclude bootstrap and this runner
        $files = array_filter($files, function($file) {
            $basename = basename($file);
            return $basename !== 'bootstrap.php' && $basename !== 'run-tests.php';
        });
        
        $this->testFiles = $files;
    }
    
    public function runAllTests() {
        echo "=== Browser Game Test Suite ===\n";
        echo "Found " . count($this->testFiles) . " test files\n\n";
        
        foreach ($this->testFiles as $testFile) {
            $this->runTestFile($testFile);
        }
        
        $this->printSummary();
    }
    
    private function runTestFile($testFile) {
        $testName = basename($testFile, '.php');
        echo "Running $testName...\n";
        echo str_repeat('-', 50) . "\n";
        
        $startTime = microtime(true);
        
        // Capture output
        ob_start();
        $exitCode = 0;
        
        try {
            // Include and run the test
            include $testFile;
            
            // If the file defines a test class, try to run it
            $className = $this->guessClassName($testFile);
            if (class_exists($className)) {
                $test = new $className();
                if (method_exists($test, 'run')) {
                    $result = $test->run();
                    $exitCode = $result ? 0 : 1;
                } elseif (method_exists($test, 'runIntegrityCheck')) {
                    $test->runIntegrityCheck();
                } elseif (method_exists($test, 'runValidationTests')) {
                    $test->runValidationTests();
                }
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            $exitCode = 1;
        }
        
        $output = ob_get_clean();
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        echo $output;
        
        $this->results[$testName] = [
            'success' => $exitCode === 0,
            'duration' => $duration,
            'output' => $output
        ];
        
        $status = $exitCode === 0 ? 'PASSED' : 'FAILED';
        echo "\n$testName: $status ({$duration}ms)\n";
        echo str_repeat('=', 50) . "\n\n";
    }
    
    private function guessClassName($testFile) {
        $basename = basename($testFile, '.php');
        
        // Convert test-name to TestName
        $parts = explode('-', $basename);
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        // Common test class patterns
        $patterns = [
            $className,
            $className . 'Test',
            str_replace('Test', '', $className),
            'ValidationTest',
            'SQLDataIntegrityChecker',
            'DatabaseTest',
            'PHPInfoTest'
        ];
        
        foreach ($patterns as $pattern) {
            if (class_exists($pattern)) {
                return $pattern;
            }
        }
        
        return '';
    }
    
    private function printSummary() {
        $total = count($this->results);
        $passed = count(array_filter($this->results, function($result) {
            return $result['success'];
        }));
        $failed = $total - $passed;
        
        echo "=== Test Summary ===\n";
        echo "Total tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        
        if ($failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->results as $name => $result) {
                if (!$result['success']) {
                    echo "- $name\n";
                }
            }
        }
        
        $successRate = $total > 0 ? round(($passed / $total) * 100, 1) : 0;
        echo "\nSuccess rate: {$successRate}%\n";
        
        // Exit with error code if any tests failed
        exit($failed > 0 ? 1 : 0);
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $runner = new TestRunner();
    $runner->runAllTests();
}