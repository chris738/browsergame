<?php
/**
 * Test script for the travel time system
 * This script tests basic functionality without requiring a full server setup
 */

require_once __DIR__ . '/php/database.php';

echo "Travel Time System Test\n";
echo "=======================\n\n";

try {
    $database = new Database();
    
    if (!$database->isConnected()) {
        echo "❌ Database connection failed\n";
        echo "The system will work in demo mode but database features are unavailable.\n";
        exit(1);
    }
    
    echo "✅ Database connection successful\n";
    
    // Test travel configuration
    echo "\nTesting travel configuration...\n";
    $tradeSpeed = $database->getTravelConfig('trade');
    $militarySpeed = $database->getTravelConfig('military');
    echo "Trade speed: {$tradeSpeed} seconds per block\n";
    echo "Military base speed: {$militarySpeed} seconds per block\n";
    
    // Test military unit configuration
    echo "\nTesting military unit configuration...\n";
    $unitConfigs = $database->getMilitaryUnitConfig();
    if (empty($unitConfigs)) {
        echo "⚠️  No military unit configurations found\n";
        echo "Run sql/data/military_travel_data.sql to initialize unit data\n";
    } else {
        echo "Found " . count($unitConfigs) . " military unit configurations:\n";
        foreach ($unitConfigs as $config) {
            echo "- {$config['unitType']} (Level {$config['level']}): Speed {$config['speed']}s/block, Loot {$config['lootAmount']}\n";
        }
    }
    
    // Test distance calculation (requires at least one settlement)
    echo "\nTesting distance calculation...\n";
    $distance = $database->calculateDistance(1, 1); // Same settlement should be distance 1
    echo "Distance from settlement 1 to settlement 1: {$distance} blocks\n";
    
    // Test current traveling status
    echo "\nTesting traveling status...\n";
    $armies = $database->getAllTravelingArmies();
    $trades = $database->getAllTravelingTrades();
    echo "Current traveling armies: " . count($armies) . "\n";
    echo "Current traveling trades: " . count($trades) . "\n";
    
    // Test arrival processing
    echo "\nTesting arrival processing...\n";
    $result = $database->processArrivals();
    echo "Processed arrivals: {$result['processed']}\n";
    
    echo "\n✅ All tests completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Access the admin panel at admin-travel.php to configure settings\n";
    echo "2. Set up the cron job using travel-cron.txt\n";
    echo "3. Test attacks and trades to see travel times in action\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>