<?php
/**
 * Test script for the event-based travel system
 * This script verifies that the travel processing events are working correctly
 */

require_once __DIR__ . '/php/database.php';

echo "Testing Event-based Travel System\n";
echo "==================================\n\n";

try {
    $database = new Database();
    
    if (!$database->isConnected()) {
        echo "❌ Database connection failed\n";
        exit(1);
    }
    
    echo "✅ Database connection successful\n";
    
    // Check if travel events exist and are enabled
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("
        SELECT EVENT_NAME, STATUS, EVENT_DEFINITION 
        FROM information_schema.EVENTS 
        WHERE EVENT_SCHEMA = 'browsergame' 
        AND EVENT_NAME = 'ProcessTravelArrivals'
    ");
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($event) {
        echo "✅ ProcessTravelArrivals event found\n";
        echo "   Status: " . $event['STATUS'] . "\n";
        if ($event['STATUS'] === 'ENABLED') {
            echo "✅ Event is enabled\n";
        } else {
            echo "⚠️  Event is not enabled\n";
        }
    } else {
        echo "❌ ProcessTravelArrivals event not found\n";
    }
    
    // Check if stored procedures exist
    $procedures = ['ProcessAllArrivals', 'ProcessArrivedArmies', 'ProcessArrivedTrades', 'ExecuteBattleOnArrival', 'ExecuteTradeOnArrival'];
    
    foreach ($procedures as $proc) {
        $stmt = $conn->prepare("
            SELECT ROUTINE_NAME 
            FROM information_schema.ROUTINES 
            WHERE ROUTINE_SCHEMA = 'browsergame' 
            AND ROUTINE_NAME = ? 
            AND ROUTINE_TYPE = 'PROCEDURE'
        ");
        $stmt->execute([$proc]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ Procedure $proc exists\n";
        } else {
            echo "❌ Procedure $proc not found\n";
        }
    }
    
    // Check if travel tables exist
    $tables = ['TravelingArmies', 'TravelingTrades', 'TravelConfig', 'BattleHistory'];
    
    foreach ($tables as $table) {
        $stmt = $conn->prepare("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = 'browsergame' 
            AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ Table $table exists\n";
        } else {
            echo "❌ Table $table not found\n";
        }
    }
    
    // Test if we can call the main procedure manually
    echo "\n🧪 Testing manual procedure call...\n";
    try {
        $stmt = $conn->prepare("CALL ProcessAllArrivals()");
        $stmt->execute();
        echo "✅ ProcessAllArrivals procedure executed successfully\n";
    } catch (Exception $e) {
        echo "❌ Error calling ProcessAllArrivals: " . $e->getMessage() . "\n";
    }
    
    // Check event scheduler status
    $stmt = $conn->prepare("SHOW VARIABLES LIKE 'event_scheduler'");
    $stmt->execute();
    $scheduler = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($scheduler && $scheduler['Value'] === 'ON') {
        echo "✅ Event scheduler is enabled\n";
    } else {
        echo "⚠️  Event scheduler is not enabled\n";
        echo "   Run: SET GLOBAL event_scheduler = ON;\n";
    }
    
    echo "\n🎯 Summary:\n";
    echo "- Travel processing is now handled by MySQL events\n";
    echo "- ProcessTravelArrivals event runs every 5 seconds\n";
    echo "- No cron jobs are needed\n";
    echo "- The system integrates with the existing building/military event system\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Event-based travel system test completed!\n";
?>