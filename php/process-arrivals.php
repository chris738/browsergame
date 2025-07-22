<?php
/**
 * Travel Processor - Handles arrival of traveling armies and trades
 * This script should be run periodically (every 30 seconds recommended) via cron
 * Usage: php process-arrivals.php
 */

require_once __DIR__ . '/database.php';

// Log to file for debugging
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents(__DIR__ . '/../logs/travel-processor.log', "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Ensure logs directory exists
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
}

try {
    logMessage("Starting travel processor...");
    
    $database = new Database();
    
    if (!$database->isConnected()) {
        logMessage("ERROR: Database connection failed");
        exit(1);
    }
    
    // Process arrivals
    $result = $database->processArrivals();
    
    if ($result['processed'] > 0) {
        logMessage("Processed {$result['processed']} arrivals");
    } else {
        logMessage("No arrivals to process");
    }
    
    logMessage("Travel processor completed successfully");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    exit(1);
}
?>