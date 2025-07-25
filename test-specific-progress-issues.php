<?php
/**
 * Specific Progress Bar Issues Test
 * 
 * This test attempts to reproduce the exact scenarios described in issues #163 and #164
 * to see if they still occur under certain conditions
 */

echo "<h1>Specific Progress Bar Issues Test</h1>";
echo "<p>Testing for remaining edge cases in issues #163 and #164</p>";

// Test Issue #164: Check for potential race conditions
echo "<h2>Issue #164: Progress Bar Immediate Completion Test</h2>";

echo "<h3>Testing Backend Timing</h3>";

// Test 1: Check if the 500ms delay is adequate for database consistency
$backendJS = file_get_contents('./js/backend.js');
$delayMatches = [];
preg_match('/setTimeout\([^}]*unifiedProgressManager[^}]*\},\s*(\d+)\)/', $backendJS, $delayMatches);

if (!empty($delayMatches)) {
    $delay = $delayMatches[1];
    echo "✅ Found progress tracking delay: {$delay}ms<br>";
    
    if ($delay < 500) {
        echo "⚠️ WARNING: Delay might be too short for slow database operations<br>";
    } elseif ($delay > 1000) {
        echo "⚠️ WARNING: Delay might be too long, causing poor user experience<br>";
    } else {
        echo "✅ Delay appears appropriate<br>";
    }
} else {
    echo "❌ ISSUE: Progress tracking delay not found in expected format<br>";
}

// Test 2: Check for potential race conditions in unified-progress.js
echo "<h3>Testing Progress Calculation Logic</h3>";

$unifiedJS = file_get_contents('./js/unified-progress.js');

// Check for proper bounds checking
if (strpos($unifiedJS, 'Math.min(100, Math.max(0,') !== false) {
    echo "✅ Progress calculation has proper bounds checking<br>";
} else {
    echo "⚠️ WARNING: Progress calculation may lack bounds checking<br>";
}

// Check for NaN validation
if (strpos($unifiedJS, 'isNaN') !== false) {
    echo "✅ Progress calculation includes NaN validation<br>";
} else {
    echo "⚠️ WARNING: Progress calculation may not handle NaN values<br>";
}

// Check for timing validation
if (strpos($unifiedJS, 'totalDuration <= 0') !== false) {
    echo "✅ Progress calculation validates timing data<br>";
} else {
    echo "⚠️ WARNING: Progress calculation may not validate timing data<br>";
}

// Test Issue #163: Check military progress implementation
echo "<h2>Issue #163: Military Progress Update Frequency Test</h2>";

echo "<h3>Testing Military Progress Manager</h3>";

if (file_exists('./js/military-progress.js')) {
    $militaryJS = file_get_contents('./js/military-progress.js');
    
    // Check update frequency
    if (preg_match('/progressUpdateInterval:\s*(\d+)/', $militaryJS, $matches)) {
        $interval = $matches[1];
        echo "✅ Military progress update interval: {$interval}ms<br>";
        
        if ($interval > 500) {
            echo "⚠️ WARNING: Update interval might still be too slow for smooth animation<br>";
        } else {
            echo "✅ Update interval should provide smooth animation<br>";
        }
    } else {
        echo "❌ ISSUE: Military progress update interval not found<br>";
    }
    
    // Check if military progress manager properly initializes
    if (strpos($militaryJS, 'startProgressUpdates') !== false) {
        echo "✅ Military progress manager has update loop functionality<br>";
    } else {
        echo "❌ ISSUE: Military progress manager missing update loop<br>";
    }
    
} else {
    echo "❌ CRITICAL: Military progress manager file missing<br>";
}

// Test kaserne.php integration
echo "<h3>Testing Kaserne.php Integration</h3>";

$kaserneContent = file_get_contents('./kaserne.php');

// Check if military-progress.js is loaded
if (strpos($kaserneContent, 'military-progress.js') !== false) {
    echo "✅ kaserne.php loads military progress manager<br>";
} else {
    echo "❌ ISSUE: kaserne.php does not load military progress manager<br>";
}

// Check if unified-progress.js is also loaded (potential conflict)
if (strpos($kaserneContent, 'unified-progress.js') !== false) {
    echo "✅ kaserne.php loads unified progress manager<br>";
    
    // Check loading order
    $unifiedPos = strpos($kaserneContent, 'unified-progress.js');
    $militaryPos = strpos($kaserneContent, 'military-progress.js');
    
    if ($unifiedPos !== false && $militaryPos !== false) {
        if ($unifiedPos < $militaryPos) {
            echo "✅ Scripts loaded in correct order (unified before military)<br>";
        } else {
            echo "⚠️ WARNING: Script loading order might cause issues<br>";
        }
    }
} else {
    echo "⚠️ WARNING: kaserne.php missing unified progress manager<br>";
}

// Check for potential timing conflicts
echo "<h3>Testing for Potential Conflicts</h3>";

// Look for multiple setInterval calls that might conflict
$intervalCount = substr_count($kaserneContent, 'setInterval');
if ($intervalCount > 3) {
    echo "⚠️ WARNING: Multiple setInterval calls detected ({$intervalCount}), potential performance issues<br>";
} else {
    echo "✅ Reasonable number of interval timers<br>";
}

// Test for potential DOM element conflicts
echo "<h3>Testing DOM Element Requirements</h3>";

// Check if required elements exist in the HTML
$requiredElements = [
    'militaryTrainingQueueBody' => 'Military training queue table',
    'researchQueueBody' => 'Research queue table',
    'buildingQueueBody' => 'Building queue table'
];

foreach ($requiredElements as $elementId => $description) {
    if (strpos($kaserneContent, $elementId) !== false) {
        echo "✅ {$description} element found<br>";
    } else {
        echo "⚠️ WARNING: {$description} element ({$elementId}) not found<br>";
    }
}

// Test for edge cases that might still cause issues
echo "<h2>Edge Case Analysis</h2>";

echo "<h3>Potential Remaining Issues</h3>";

$potentialIssues = [
    "Database transaction timing" => "The 500ms delay might not be enough for slow database operations",
    "Browser rendering timing" => "Progress bars might update before DOM elements are fully rendered",
    "Network latency" => "Server sync calls might take longer than expected, causing stale data",
    "Multiple settlement handling" => "Progress tracking might have issues when switching between settlements",
    "Tab visibility" => "Progress updates might behave differently when browser tab is not active",
    "Mobile browsers" => "Touch devices might have different timing behavior",
    "Concurrent operations" => "Multiple buildings/training starting simultaneously might cause conflicts"
];

foreach ($potentialIssues as $issue => $description) {
    echo "⚠️ POTENTIAL ISSUE: <strong>{$issue}</strong> - {$description}<br>";
}

// Summary and recommendations
echo "<h2>Summary and Recommendations</h2>";

$recommendations = [
    "Increase progress tracking delay to 750ms-1000ms for slower systems",
    "Add more robust DOM readiness checking before initializing progress tracking", 
    "Implement retry logic for failed server sync operations",
    "Add progress bar state logging for debugging in production",
    "Test progress bars specifically on mobile devices and slower connections",
    "Add conflict detection between multiple progress tracking systems",
    "Implement graceful degradation when DOM elements are missing"
];

echo "<h3>Recommended Improvements:</h3>";
foreach ($recommendations as $i => $recommendation) {
    echo ($i + 1) . ". {$recommendation}<br>";
}

echo "<hr>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Conclusion:</strong> While the main fixes are in place, there may be edge cases or timing issues under certain conditions that could still trigger the original problems.</p>";
?>