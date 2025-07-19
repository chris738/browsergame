<?php
/**
 * Bootstrap file for PHPUnit tests
 */

// Set the root directory
define('ROOT_DIR', dirname(__DIR__));

// Change to the root directory for proper file inclusion
chdir(ROOT_DIR);

// Include Composer autoloader if it exists
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require_once ROOT_DIR . '/vendor/autoload.php';
}

// For now, we'll manually include the Database class and other dependencies
// This will be improved once we have proper autoloading
if (file_exists(ROOT_DIR . '/php/database.php')) {
    require_once ROOT_DIR . '/php/database.php';
}

// Create results directory if it doesn't exist
$resultsDir = __DIR__ . '/results';
if (!is_dir($resultsDir)) {
    mkdir($resultsDir, 0755, true);
}