<?php
/**
 * Test Progress Bar Functionality
 * Tests the client-side progress management system
 */

// Change to parent directory to find php files
chdir(__DIR__ . '/..');
require_once 'php/database.php';

class ProgressBarTest {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runAllTests() {
        echo "=== Testing Progress Bar Functionality ===\n\n";
        
        $this->testProgressCalculation();
        $this->testQueueDisplay();
        $this->testServerSync();
        $this->testClientSideUpdates();
        
        echo "\n=== All Progress Bar Tests Completed ===\n";
    }
    
    public function testProgressCalculation() {
        echo "1. Testing Progress Calculation Logic...\n";
        
        // Test data - simulating a building in progress
        $testData = [
            'buildingType' => 'Rathaus',
            'level' => 2,
            'startTime' => time() * 1000 - 30000, // Started 30 seconds ago
            'endTime' => time() * 1000 + 60000,   // Ends in 60 seconds
        ];
        
        $totalDuration = $testData['endTime'] - $testData['startTime'];
        $elapsed = time() * 1000 - $testData['startTime'];
        $completionPercentage = ($elapsed / $totalDuration) * 100;
        
        echo "   - Start Time: " . date('H:i:s', $testData['startTime'] / 1000) . "\n";
        echo "   - End Time: " . date('H:i:s', $testData['endTime'] / 1000) . "\n";
        echo "   - Total Duration: " . ($totalDuration / 1000) . " seconds\n";
        echo "   - Elapsed: " . ($elapsed / 1000) . " seconds\n";
        echo "   - Completion: " . round($completionPercentage, 2) . "%\n";
        
        // Validate calculation
        if ($completionPercentage >= 0 && $completionPercentage <= 100) {
            echo "   ✓ Progress calculation is valid\n";
        } else {
            echo "   ✗ Progress calculation is invalid: $completionPercentage%\n";
        }
        
        echo "\n";
    }
    
    public function testQueueDisplay() {
        echo "2. Testing Queue Display Logic...\n";
        
        // Simulate multiple buildings in queue
        $queue = [
            [
                'buildingType' => 'Rathaus',
                'level' => 2,
                'startTime' => time() * 1000 - 30000,
                'endTime' => time() * 1000 + 60000,
            ],
            [
                'buildingType' => 'Holzfäller',
                'level' => 3,
                'startTime' => time() * 1000 + 60000,
                'endTime' => time() * 1000 + 180000,
            ]
        ];
        
        echo "   - Queue contains " . count($queue) . " buildings\n";
        
        foreach ($queue as $index => $item) {
            $status = $index === 0 ? 'active' : 'queued';
            $remainingTime = max(0, $item['endTime'] - time() * 1000);
            
            echo "   - Building $index: {$item['buildingType']} Level {$item['level']} ($status)\n";
            echo "     Remaining: " . round($remainingTime / 1000) . " seconds\n";
        }
        
        echo "   ✓ Queue display logic tested\n\n";
    }
    
    public function testServerSync() {
        echo "3. Testing Server Sync Functionality...\n";
        
        try {
            // Test if we can get settlement data
            $settlementId = 1;
            
            // Test resources endpoint
            $resources = $this->database->getResources($settlementId);
            if ($resources) {
                echo "   ✓ Resources retrieval working\n";
                echo "     - Wood: " . $resources['wood'] . "\n";
                echo "     - Stone: " . $resources['stone'] . "\n";
                echo "     - Ore: " . $resources['ore'] . "\n";
            } else {
                echo "   ✗ Resources retrieval failed\n";
            }
            
            // Test building queue retrieval
            $buildingQueue = $this->database->getQueue($settlementId);
            if (is_array($buildingQueue)) {
                echo "   ✓ Building queue retrieval working\n";
                echo "     - Queue length: " . count($buildingQueue) . "\n";
                if (!empty($buildingQueue)) {
                    foreach ($buildingQueue as $queueItem) {
                        echo "     - " . ($queueItem['buildingType'] ?? 'Unknown') . " Level " . ($queueItem['level'] ?? 'N/A') . "\n";
                    }
                }
            } else {
                echo "   ✗ Building queue retrieval failed\n";
            }
            
            // Test regeneration rates
            $regenRates = $this->database->getRegen($settlementId);
            if ($regenRates) {
                echo "   ✓ Regeneration rates retrieval working\n";
                echo "     - Wood/hour: " . ($regenRates['wood'] ?? 'N/A') . "\n";
                echo "     - Stone/hour: " . ($regenRates['stone'] ?? 'N/A') . "\n";
                echo "     - Ore/hour: " . ($regenRates['ore'] ?? 'N/A') . "\n";
            } else {
                echo "   ✗ Regeneration rates retrieval failed\n";
            }
            
        } catch (Exception $e) {
            echo "   ✗ Server sync test failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    public function testClientSideUpdates() {
        echo "4. Testing Client-Side Update Logic...\n";
        
        // Test progress update frequency
        $updateInterval = 1000; // 1 second in milliseconds
        $serverSyncInterval = 120000; // 2 minutes in milliseconds
        
        echo "   - Progress update interval: " . ($updateInterval / 1000) . " seconds\n";
        echo "   - Server sync interval: " . ($serverSyncInterval / 1000) . " seconds\n";
        
        // Test time formatting
        $testTimes = [1000, 65000, 3661000]; // 1s, 1m5s, 1h1m1s
        
        foreach ($testTimes as $time) {
            $formatted = $this->formatTime($time);
            echo "   - $time ms = $formatted\n";
        }
        
        echo "   ✓ Client-side update logic tested\n\n";
    }
    
    private function formatTime($milliseconds) {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        
        if ($hours > 0) {
            return $hours . "h " . ($minutes % 60) . "m " . ($seconds % 60) . "s";
        } else if ($minutes > 0) {
            return $minutes . "m " . ($seconds % 60) . "s";
        } else {
            return $seconds . "s";
        }
    }
    
    public function generateTestHTML() {
        echo "5. Generating Test HTML for Progress Bar Visualization...\n";
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Bar Test</title>
    <style>
        .progress-container {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            height: 20px;
            margin: 10px 0;
        }
        .progress-bar {
            height: 100%;
            background-color: #4457ff69;
            width: 0%;
            border-radius: 10px;
            transition: width 0.8s ease-out;
        }
        .progress-bar.active-building {
            background-color: #4457ff69;
        }
        .progress-bar.queued-building {
            background-color: #cccccc;
            opacity: 0.5;
        }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Progress Bar Test</h1>
    <table>
        <thead>
            <tr>
                <th>Building</th>
                <th>Level</th>
                <th>Progress</th>
                <th>End Time</th>
            </tr>
        </thead>
        <tbody id="buildingQueueBody">
            <tr>
                <td class="active-building">Town Hall</td>
                <td>2</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar active-building" id="test-progress-1" style="width: 0%;"></div>
                    </div>
                </td>
                <td>1m 30s</td>
            </tr>
            <tr>
                <td class="queued-building">Lumberjack (queued)</td>
                <td>3</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar queued-building" id="test-progress-2" style="width: 0%;"></div>
                    </div>
                </td>
                <td>3m 45s (queued)</td>
            </tr>
        </tbody>
    </table>
    
    <button onclick="testProgress()">Test Progress Animation</button>
    <button onclick="resetProgress()">Reset Progress</button>
    
    <script>
        function testProgress() {
            const bar1 = document.getElementById("test-progress-1");
            const bar2 = document.getElementById("test-progress-2");
            
            let progress1 = 0;
            let progress2 = 0;
            
            const interval = setInterval(() => {
                progress1 += 2;
                if (progress1 <= 100) {
                    bar1.style.width = progress1 + "%";
                }
                
                if (progress1 >= 100 && progress2 < 100) {
                    progress2 += 3;
                    bar2.style.width = progress2 + "%";
                }
                
                if (progress1 >= 100 && progress2 >= 100) {
                    clearInterval(interval);
                }
            }, 100);
        }
        
        function resetProgress() {
            document.getElementById("test-progress-1").style.width = "0%";
            document.getElementById("test-progress-2").style.width = "0%";
        }
    </script>
</body>
</html>';
        
        file_put_contents(__DIR__ . '/progress-bar-test.html', $html);
        echo "   ✓ Test HTML generated: tests/progress-bar-test.html\n\n";
    }
}

// Run the tests
$test = new ProgressBarTest();
$test->runAllTests();
$test->generateTestHTML();
?>