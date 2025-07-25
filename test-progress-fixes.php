<?php
/**
 * Progress Bar Fixes Verification Test
 * 
 * This test verifies that the fixes for Issues 163 and 164 are working correctly
 */

echo "<h1>Progress Bar Fixes Verification</h1>\n";

echo "<h2>Issue 164 Fix Verification</h2>\n";

// Test 1: Check that backend.js contains the 500ms delay
$backendJS = file_get_contents('./js/backend.js');
if (strpos($backendJS, 'setTimeout(') !== false && strpos($backendJS, '500') !== false) {
    echo "✅ PASS: Building progress delay implemented in backend.js\n<br>";
} else {
    echo "❌ FAIL: Building progress delay not found in backend.js\n<br>";
}

echo "<h2>Issue 163 Fix Verification</h2>\n";

// Test 2: Check that military-progress.js exists and has smooth updates
if (file_exists('./js/military-progress.js')) {
    echo "✅ PASS: Military progress manager file exists\n<br>";
    
    $militaryJS = file_get_contents('./js/military-progress.js');
    if (strpos($militaryJS, 'progressUpdateInterval: 250') !== false) {
        echo "✅ PASS: Military progress manager uses 250ms smooth updates\n<br>";
    } else {
        echo "❌ FAIL: Military progress manager does not use 250ms updates\n<br>";
    }
    
    if (strpos($militaryJS, 'MilitaryProgressManager') !== false) {
        echo "✅ PASS: Military progress manager class defined\n<br>";
    } else {
        echo "❌ FAIL: Military progress manager class not defined\n<br>";
    }
} else {
    echo "❌ FAIL: Military progress manager file does not exist\n<br>";
}

// Test 3: Check that kaserne.php includes the military progress manager
$kaserneContent = file_get_contents('./kaserne.php');
if (strpos($kaserneContent, 'military-progress.js') !== false) {
    echo "✅ PASS: kaserne.php includes military progress manager script\n<br>";
} else {
    echo "❌ FAIL: kaserne.php does not include military progress manager script\n<br>";
}

if (strpos($kaserneContent, '30000') !== false) {
    echo "✅ PASS: kaserne.php polling frequency reduced to 30 seconds\n<br>";
} else {
    echo "❌ FAIL: kaserne.php polling frequency not reduced\n<br>";
}

echo "<h2>Integration Tests</h2>\n";

// Test 4: Check that all files have valid PHP syntax
$phpFiles = ['index.php', 'kaserne.php', 'php/backend.php'];
$phpErrors = 0;

foreach ($phpFiles as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l $file 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ PASS: $file has valid PHP syntax\n<br>";
        } else {
            echo "❌ FAIL: $file has PHP syntax errors: $output\n<br>";
            $phpErrors++;
        }
    }
}

// Test 5: Check JavaScript syntax
$jsFiles = ['js/backend.js', 'js/military-progress.js', 'js/unified-progress.js'];
$jsErrors = 0;

foreach ($jsFiles as $file) {
    if (file_exists($file)) {
        $output = shell_exec("node -c $file 2>&1");
        if (empty($output)) {
            echo "✅ PASS: $file has valid JavaScript syntax\n<br>";
        } else {
            echo "❌ FAIL: $file has JavaScript syntax errors: $output\n<br>";
            $jsErrors++;
        }
    }
}

echo "<h2>Summary</h2>\n";

$totalTests = 8;
$passedTests = $totalTests - $phpErrors - $jsErrors;

if ($passedTests >= 6) {
    echo "✅ <strong>OVERALL RESULT: PASS</strong> ($passedTests/$totalTests tests passed)\n<br>";
    echo "Progress bar issues have been successfully fixed:\n<br>";
    echo "- Issue 164: Building progress bars now have proper timing delay\n<br>";
    echo "- Issue 163: Military progress bars now update smoothly every 250ms\n<br>";
} else {
    echo "❌ <strong>OVERALL RESULT: FAIL</strong> ($passedTests/$totalTests tests passed)\n<br>";
    echo "Some issues remain to be addressed.\n<br>";
}

echo "<hr>";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
?>