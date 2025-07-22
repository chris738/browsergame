<?php
/**
 * DEPRECATED: Travel Processor (Legacy Cron-based System)
 * 
 * This script is no longer needed as travel processing has been moved to MySQL events.
 * The travel system now uses the ProcessTravelArrivals event that runs every 5 seconds,
 * similar to other game systems like building queue processing.
 * 
 * For debugging purposes, this script can still be run manually to process arrivals,
 * but it should not be used in production via cron jobs.
 * 
 * Usage: php process-arrivals.php (for manual testing only)
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