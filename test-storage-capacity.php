<?php
/**
 * Test script to verify that storage capacity bug is fixed
 * This test checks that resources can exceed 10,000 when Lager building is upgraded
 */

echo "=== Storage Capacity Fix Test ===\n\n";

// Include database connection
require_once 'php/database.php';

try {
    $database = new Database();
    echo "✅ Database connection established\n\n";
    
    // Test with ResourceRepository directly since we fixed the mock data
    echo "1. Testing ResourceRepository mock data (when database fails)...\n";
    
    // Test the mock data fix
    require_once 'php/database/repositories/ResourceRepository.php';
    $mockResourceRepo = new ResourceRepository(null, true); // Connection failed = true
    $mockResources = $mockResourceRepo->getResources(1);
    
    echo "   Mock storage capacity: {$mockResources['storageCapacity']}\n";
    
    if ($mockResources['storageCapacity'] > 10000) {
        echo "   ✅ Mock data storage capacity exceeds 10,000!\n";
    } else {
        echo "   ❌ Mock data still uses 10,000 limit\n";
    }
    echo "\n";
    
    // Test with actual database if connection works
    $resources = $database->getResources(1);
    
    if ($resources) {
        echo "2. Testing actual database resources...\n";
        echo "   Storage capacity: {$resources['storageCapacity']}\n";
        echo "   Wood: {$resources['wood']}, Stone: {$resources['stone']}, Ore: {$resources['ore']}\n";
        
        if ($resources['storageCapacity'] > 10000) {
            echo "   ✅ Database storage capacity exceeds 10,000!\n";
        } else if ($resources['storageCapacity'] == 10000) {
            echo "   ⚠️  Storage capacity is exactly 10,000 (may be default)\n";
        } else {
            echo "   ❌ Storage capacity is less than 10,000\n";
        }
        echo "\n";
    } else {
        echo "2. Database connection works but no resources found\n\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    
    // Test with mock data if database fails
    echo "\n=== Testing with mock data ===\n";
    
    $mockResourceRepo = new ResourceRepository(null, true); // Connection failed = true
    $mockResources = $mockResourceRepo->getResources(1);
    
    echo "Mock storage capacity: {$mockResources['storageCapacity']}\n";
    
    if ($mockResources['storageCapacity'] > 10000) {
        echo "✅ Mock data storage capacity exceeds 10,000!\n";
    } else {
        echo "❌ Mock data still uses 10,000 limit\n";
    }
}
?>