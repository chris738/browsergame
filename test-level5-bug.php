<?php
/**
 * Test to reproduce the level 5 reset bug
 * Problem: When upgrading to level 5, the level gets reset to 0
 */

require_once 'php/database.php';

class Level5BugTest {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runTest() {
        echo "=== Level 5 Reset Bug Test ===\n";
        echo "Testing the bug where upgrading to level 5 resets level to 0\n\n";
        
        if (!$this->database->isConnected()) {
            echo "Database not connected - using mock data (cannot test real bug)\n";
            return;
        }
        
        $this->testLevel5Progression();
    }
    
    private function testLevel5Progression() {
        echo "1. Testing building progression to level 5...\n";
        
        $settlementId = 1;
        $buildingType = 'Lager'; // Storage building - simpler than Kaserne
        
        // First, let's see current level
        $building = $this->database->getBuilding($settlementId, $buildingType);
        echo "   Current {$buildingType} level: " . ($building['currentLevel'] ?? 'N/A') . "\n";
        
        // Simulate upgrading to each level up to 5
        for ($targetLevel = 2; $targetLevel <= 5; $targetLevel++) {
            echo "\n   Attempting upgrade to level {$targetLevel}...\n";
            
            // Get sufficient resources first
            $this->ensureSufficientResources($settlementId, $buildingType, $targetLevel);
            
            // Attempt upgrade
            $result = $this->database->upgradeBuilding($settlementId, $buildingType);
            
            if ($result && $result['success']) {
                echo "   ✅ Upgrade to level {$targetLevel} queued successfully\n";
                
                // Simulate building completion by manually triggering ProcessBuildingQueue
                $this->simulateBuildingCompletion($settlementId, $buildingType, $targetLevel);
                
                // Check final level
                $building = $this->database->getBuilding($settlementId, $buildingType);
                $actualLevel = $building['currentLevel'] ?? 0;
                
                echo "   Final level after completion: {$actualLevel}\n";
                
                if ($targetLevel == 5 && $actualLevel == 0) {
                    echo "   🐛 BUG FOUND: Level 5 upgrade resulted in level 0!\n";
                    $this->investigateBugCause($settlementId, $buildingType);
                    break;
                } elseif ($actualLevel != $targetLevel) {
                    echo "   ⚠️  Unexpected level: Expected {$targetLevel}, got {$actualLevel}\n";
                }
            } else {
                echo "   ❌ Upgrade failed: " . ($result['message'] ?? 'Unknown error') . "\n";
                break;
            }
        }
    }
    
    private function ensureSufficientResources($settlementId, $buildingType, $level) {
        // Get building config for target level
        $building = $this->database->getBuilding($settlementId, $buildingType);
        
        if ($building) {
            $requiredWood = $building['costWood'] ?? 1000;
            $requiredStone = $building['costStone'] ?? 1000;
            $requiredOre = $building['costOre'] ?? 1000;
            
            // Ensure we have enough resources (add extra buffer)
            $this->database->updateSettlementResources($settlementId, $requiredWood * 2, $requiredStone * 2, $requiredOre * 2);
            
            echo "   Set resources: Wood={$requiredWood}x2, Stone={$requiredStone}x2, Ore={$requiredOre}x2\n";
        }
    }
    
    private function simulateBuildingCompletion($settlementId, $buildingType, $expectedLevel) {
        echo "   Simulating building completion...\n";
        
        try {
            // Get database connection
            $conn = $this->database->getConnection();
            
            // Check what's in the building queue
            $stmt = $conn->prepare("SELECT * FROM BuildingQueue WHERE settlementId = ? AND buildingType = ?");
            $stmt->execute([$settlementId, $buildingType]);
            $queueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "   Queue items found: " . count($queueItems) . "\n";
            
            foreach ($queueItems as $item) {
                echo "   Queue item: Level {$item['level']}, EndTime: {$item['endTime']}\n";
                
                // Force completion by setting endTime to past
                $updateStmt = $conn->prepare("UPDATE BuildingQueue SET endTime = DATE_SUB(NOW(), INTERVAL 1 SECOND) WHERE queueId = ?");
                $updateStmt->execute([$item['queueId']]);
            }
            
            // Now execute the ProcessBuildingQueue logic manually
            $this->executeProcessBuildingQueue($settlementId, $buildingType);
            
        } catch (Exception $e) {
            echo "   Error simulating completion: " . $e->getMessage() . "\n";
        }
    }
    
    private function executeProcessBuildingQueue($settlementId, $buildingType) {
        echo "   Executing ProcessBuildingQueue logic...\n";
        
        try {
            $conn = $this->database->getConnection();
            
            // Check which version of ProcessBuildingQueue logic we're using
            // Version 1 (from fix-process-building-queue.sql):
            
            echo "   Executing fixed ProcessBuildingQueue logic...\n";
            
            // First, create new building entries for buildings that don't exist yet
            $stmt1 = $conn->prepare("
                INSERT INTO Buildings (settlementId, buildingType, level, visable)
                SELECT bq.settlementId, bq.buildingType, bq.level, TRUE
                FROM BuildingQueue bq
                LEFT JOIN Buildings b ON bq.settlementId = b.settlementId AND bq.buildingType = b.buildingType
                WHERE NOW() >= bq.endTime 
                AND b.buildingType IS NULL
                AND bq.settlementId = ?
                AND bq.buildingType = ?
            ");
            $stmt1->execute([$settlementId, $buildingType]);
            
            // Then, update existing building levels
            $stmt2 = $conn->prepare("
                UPDATE Buildings b
                INNER JOIN BuildingQueue bq ON b.settlementId = bq.settlementId AND b.buildingType = bq.buildingType
                SET b.level = bq.level, b.visable = TRUE
                WHERE NOW() >= bq.endTime
                AND b.settlementId = ?
                AND b.buildingType = ?
            ");
            $stmt2->execute([$settlementId, $buildingType]);
            
            echo "   Buildings updated: " . $stmt2->rowCount() . "\n";
            
            // Finally, remove completed queue items
            $stmt3 = $conn->prepare("
                DELETE FROM BuildingQueue 
                WHERE NOW() >= endTime
                AND settlementId = ?
                AND buildingType = ?
            ");
            $stmt3->execute([$settlementId, $buildingType]);
            
            echo "   Queue items removed: " . $stmt3->rowCount() . "\n";
            
        } catch (Exception $e) {
            echo "   Error in ProcessBuildingQueue: " . $e->getMessage() . "\n";
        }
    }
    
    private function investigateBugCause($settlementId, $buildingType) {
        echo "\n   🔍 Investigating bug cause...\n";
        
        try {
            $conn = $this->database->getConnection();
            
            // Check building table
            $stmt = $conn->prepare("SELECT * FROM Buildings WHERE settlementId = ? AND buildingType = ?");
            $stmt->execute([$settlementId, $buildingType]);
            $building = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($building) {
                echo "   Buildings table: Level = {$building['level']}, Visible = {$building['visable']}\n";
            } else {
                echo "   Buildings table: No entry found!\n";
            }
            
            // Check if there are any remaining queue items
            $stmt = $conn->prepare("SELECT * FROM BuildingQueue WHERE settlementId = ? AND buildingType = ?");
            $stmt->execute([$settlementId, $buildingType]);
            $queueItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "   Remaining queue items: " . count($queueItems) . "\n";
            foreach ($queueItems as $item) {
                echo "   - Level {$item['level']}, EndTime: {$item['endTime']}, Active: {$item['isActive']}\n";
            }
            
            // Check building config for level 5
            $stmt = $conn->prepare("SELECT * FROM BuildingConfig WHERE buildingType = ? AND level = 5");
            $stmt->execute([$buildingType]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($config) {
                echo "   BuildingConfig level 5: Found\n";
            } else {
                echo "   BuildingConfig level 5: NOT FOUND - This could be the bug!\n";
            }
            
        } catch (Exception $e) {
            echo "   Error investigating: " . $e->getMessage() . "\n";
        }
    }
}

// Run the test
$tester = new Level5BugTest();
$tester->runTest();
?>