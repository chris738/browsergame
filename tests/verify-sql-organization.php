#!/usr/bin/env php
<?php
/**
 * SQL Organization Verification Script
 * Verifies that the SQL files are properly organized and contain expected content
 */

echo "=== SQL Organization Verification ===\n";

$sqlDir = __DIR__ . '/../sql';
$expectedDirs = ['data', 'procedures', 'tables', 'views', 'archive'];
$expectedFiles = [
    'data' => ['initial_data.sql', 'kaserne_data.sql', 'military_data.sql', 'research_data.sql', 'database_events.sql'],
    'procedures' => ['building_procedures.sql', 'player_procedures.sql', 'military_procedures.sql'],
    'tables' => ['core_tables.sql', 'kaserne_tables.sql', 'military_tables.sql', 'research_tables.sql'],
    'views' => ['game_views.sql', 'enhanced_views.sql']
];

$results = [];

// Check directory structure
echo "Checking directory structure...\n";
foreach ($expectedDirs as $dir) {
    $dirPath = $sqlDir . '/' . $dir;
    if (is_dir($dirPath)) {
        echo "✓ Directory $dir exists\n";
        $results['dirs'][$dir] = true;
    } else {
        echo "✗ Directory $dir missing\n";
        $results['dirs'][$dir] = false;
    }
}

// Check file organization
echo "\nChecking file organization...\n";
foreach ($expectedFiles as $dir => $files) {
    foreach ($files as $file) {
        $filePath = $sqlDir . '/' . $dir . '/' . $file;
        if (file_exists($filePath)) {
            echo "✓ File $dir/$file exists\n";
            $results['files'][$dir][$file] = true;
        } else {
            echo "✗ File $dir/$file missing\n";
            $results['files'][$dir][$file] = false;
        }
    }
}

// Check main database.sql
echo "\nChecking main database.sql...\n";
$databaseSql = $sqlDir . '/database.sql';
if (file_exists($databaseSql)) {
    $content = file_get_contents($databaseSql);
    $checks = [
        'has_tables' => strpos($content, 'CREATE TABLE') !== false,
        'has_procedures' => strpos($content, 'CREATE PROCEDURE') !== false,
        'has_views' => strpos($content, 'CREATE OR REPLACE VIEW') !== false,
        'has_events' => strpos($content, 'CREATE EVENT') !== false,
        'has_data' => strpos($content, 'INSERT INTO') !== false
    ];
    
    foreach ($checks as $check => $passed) {
        if ($passed) {
            echo "✓ Database.sql $check\n";
        } else {
            echo "✗ Database.sql missing $check\n";
        }
        $results['database_sql'][$check] = $passed;
    }
} else {
    echo "✗ Main database.sql file missing\n";
    $results['database_sql']['exists'] = false;
}

// Check archive
echo "\nChecking archived files...\n";
$archiveDir = $sqlDir . '/archive';
$archivedFiles = ['add-kaserne.sql', 'add-research-system.sql', 'create-start-research.sql', 
                  'military-units.sql', 'update-military-units.sql', 'update-settlement-settlers.sql', 
                  'update-training-with-research.sql'];

foreach ($archivedFiles as $file) {
    $filePath = $archiveDir . '/' . $file;
    if (file_exists($filePath)) {
        echo "✓ Archived file $file\n";
        $results['archive'][$file] = true;
    } else {
        echo "✗ Archived file $file missing\n";
        $results['archive'][$file] = false;
    }
}

// Summary
echo "\n=== Verification Summary ===\n";
$totalChecks = 0;
$passedChecks = 0;

foreach ($results as $category => $items) {
    if (is_array($items)) {
        foreach ($items as $item => $status) {
            $totalChecks++;
            if (is_array($status)) {
                foreach ($status as $subitem => $substatus) {
                    $totalChecks++;
                    if ($substatus) $passedChecks++;
                }
            } else {
                if ($status) $passedChecks++;
            }
        }
    }
}

echo "Total checks: $totalChecks\n";
echo "Passed: $passedChecks\n";
echo "Failed: " . ($totalChecks - $passedChecks) . "\n";

if ($passedChecks == $totalChecks) {
    echo "\n✓ All organization checks passed!\n";
    exit(0);
} else {
    echo "\n✗ Some organization checks failed!\n";
    exit(1);
}
?>