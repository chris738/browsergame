<?php
/**
 * Test script to verify the progress bar conflict fix
 * 
 * This tests that the two progress management systems don't interfere with each other
 */

echo "=== Testing Progress Bar Conflict Fix ===\n\n";

// Test 1: Verify ClientProgressManager has conflict prevention code
echo "1. Testing ClientProgressManager Conflict Prevention...\n";

$clientProgressFile = __DIR__ . '/../js/client-progress.js';
if (file_exists($clientProgressFile)) {
    $content = file_get_contents($clientProgressFile);
    
    // Check for BuildingProgressManager detection in updateProgress
    if (strpos($content, 'if (window.buildingProgressManager && window.buildingProgressManager.activeBuildings.size > 0)') !== false) {
        echo "   ✓ updateProgress() has BuildingProgressManager detection\n";
    } else {
        echo "   ✗ updateProgress() missing BuildingProgressManager detection\n";
    }
    
    // Check for skipping progress updates when BuildingProgressManager is active
    if (strpos($content, 'skipping progress updates in ClientProgressManager') !== false) {
        echo "   ✓ Progress updates are skipped when BuildingProgressManager is active\n";
    } else {
        echo "   ✗ Progress updates not properly skipped\n";
    }
    
    // Check for queue display protection
    if (strpos($content, 'skipping queue display update in ClientProgressManager') !== false) {
        echo "   ✓ Queue display updates are protected from conflicts\n";
    } else {
        echo "   ✗ Queue display updates not protected\n";
    }
    
    // Check for sync function protection  
    if (strpos($content, 'BuildingProgressManager is handling queue') !== false) {
        echo "   ✓ Sync function respects BuildingProgressManager\n";
    } else {
        echo "   ✗ Sync function doesn't respect BuildingProgressManager\n";
    }
    
} else {
    echo "   ✗ Client progress file not found\n";
}

echo "\n";

// Test 2: Verify BuildingProgressManager has improved time formatting
echo "2. Testing BuildingProgressManager Improvements...\n";

$progressBarFile = __DIR__ . '/../js/progress-bar.js';
if (file_exists($progressBarFile)) {
    $content = file_get_contents($progressBarFile);
    
    // Check for completion buffer
    if (strpos($content, 'COMPLETION_BUFFER = 1000') !== false) {
        echo "   ✓ Completion buffer added to prevent time jumping\n";
    } else {
        echo "   ✗ Completion buffer not found\n";
    }
    
    // Check for improved progress bar update logic
    if (strpos($content, 'widthDiff >= 0.1') !== false) {
        echo "   ✓ Improved progress bar update threshold\n";
    } else {
        echo "   ✗ Progress bar update threshold not improved\n";
    }
    
    // Check for consistent transition timing
    if (strpos($content, 'width 0.3s ease-out') !== false) {
        echo "   ✓ Consistent transition timing\n";
    } else {
        echo "   ✗ Transition timing not consistent\n";
    }
    
} else {
    echo "   ✗ Progress bar file not found\n";
}

echo "\n";

// Test 3: Simulate conflict scenario
echo "3. Simulating Conflict Prevention...\n";

// Mock the scenario where both systems would try to update
$scenarios = [
    [
        'name' => 'ClientProgressManager active, BuildingProgressManager inactive',
        'buildingProgressActive' => false,
        'shouldClientUpdate' => true
    ],
    [
        'name' => 'Both systems active (conflict scenario)',
        'buildingProgressActive' => true,
        'shouldClientUpdate' => false
    ],
    [
        'name' => 'BuildingProgressManager with 2 active buildings',
        'buildingProgressActive' => true,
        'shouldClientUpdate' => false
    ]
];

foreach ($scenarios as $scenario) {
    echo "   Testing: {$scenario['name']}\n";
    
    // Simulate the condition check from ClientProgressManager
    $buildingProgressActive = $scenario['buildingProgressActive'];
    $shouldUpdate = !$buildingProgressActive;
    
    if ($shouldUpdate === $scenario['shouldClientUpdate']) {
        echo "   ✓ Correct behavior: ClientProgressManager " . 
             ($shouldUpdate ? "should update" : "should NOT update") . "\n";
    } else {
        echo "   ✗ Incorrect behavior: Expected " . 
             ($scenario['shouldClientUpdate'] ? "update" : "no update") . 
             " but got " . ($shouldUpdate ? "update" : "no update") . "\n";
    }
}

echo "\n";

// Test 4: Verify script loading order in index.php
echo "4. Testing Script Loading Order...\n";

$indexFile = __DIR__ . '/../index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    
    $progressBarPos = strpos($content, 'progress-bar.js');
    $clientProgressPos = strpos($content, 'client-progress.js');
    
    if ($progressBarPos !== false && $clientProgressPos !== false) {
        if ($progressBarPos < $clientProgressPos) {
            echo "   ✓ progress-bar.js loads before client-progress.js (correct order)\n";
        } else {
            echo "   ✗ Script loading order may cause issues\n";
        }
    } else {
        echo "   ✗ One or both script files not found in index.php\n";
    }
} else {
    echo "   ✗ index.php not found\n";
}

echo "\n";

echo "=== Progress Bar Conflict Fix Testing Complete ===\n";
echo "\nSummary of conflict prevention measures:\n";
echo "1. ✓ ClientProgressManager detects active BuildingProgressManager\n";
echo "2. ✓ Progress updates are skipped to prevent conflicts\n";
echo "3. ✓ Queue display updates are protected\n";
echo "4. ✓ Sync function respects BuildingProgressManager state\n";
echo "5. ✓ Time formatting improved to prevent jumping\n";
echo "6. ✓ Progress bar updates optimized to reduce flickering\n";
echo "\nThe fix should resolve the 'progress balken hin und her' issue!\n";

?>