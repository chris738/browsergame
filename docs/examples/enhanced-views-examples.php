<?php
/**
 * Enhanced Views Usage Examples
 * Demonstrates how PHP code can be simplified using the new database views
 */

// Include database connection
require_once __DIR__ . '/php/database.php';

echo "=== Enhanced Views Usage Examples ===\n\n";

try {
    $database = new Database();
    
    if (!$database->isConnected()) {
        echo "❌ Database connection failed - showing examples with mock data\n\n";
        showExamples(null);
        exit(0);
    }
    
    echo "✅ Database connected successfully\n\n";
    $conn = $database->getConnection();
    
    showExamples($conn);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    showExamples(null);
}

function showExamples($conn) {
    echo "📚 Code Examples - Before and After Enhanced Views\n";
    echo "=================================================\n\n";
    
    // Example 1: Getting settlement resources
    echo "1️⃣  Settlement Resources\n";
    echo "------------------------\n";
    echo "❌ BEFORE (Complex JOIN query):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT s.wood, s.stone, s.ore, p.name as playerName, p.gold \n";
    echo "        FROM Settlement s \n";
    echo "        JOIN Spieler p ON s.playerId = p.playerId \n";
    echo "        WHERE s.settlementId = ?\";\n";
    echo "```\n\n";
    
    echo "✅ AFTER (Simple view query):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT * FROM SettlementResources WHERE settlementId = ?\";\n";
    echo "```\n\n";
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM SettlementResources WHERE settlementId = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "🔍 Sample Result:\n";
                echo "   Settlement: {$result['settlementName']}\n";
                echo "   Player: {$result['playerName']}\n";
                echo "   Resources: Wood={$result['wood']}, Stone={$result['stone']}, Ore={$result['ore']}\n";
                echo "   Gold: {$result['playerGold']}\n";
                if (isset($result['woodProduction'])) {
                    echo "   Production: Wood={$result['woodProduction']}/s, Stone={$result['stoneProduction']}/s, Ore={$result['oreProduction']}/s\n";
                }
                echo "   Storage: {$result['storageCapacity']}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Error fetching sample data: " . $e->getMessage() . "\n";
        }
    } else {
        echo "🔍 Sample Result (Mock):\n";
        echo "   Settlement: TestSettlement\n";
        echo "   Player: TestPlayer\n";
        echo "   Resources: Wood=10000, Stone=10000, Ore=10000\n";
        echo "   Gold: 500\n";
        echo "   Production: Wood=3600/s, Stone=3600/s, Ore=3600/s\n";
        echo "   Storage: 10000\n";
    }
    echo "\n";
    
    // Example 2: Building upgrade costs
    echo "2️⃣  Building Upgrade Costs\n";
    echo "--------------------------\n";
    echo "❌ BEFORE (Multiple queries and complex logic):\n";
    echo "```php\n";
    echo "// Get building level\n";
    echo "\$sql1 = \"SELECT level FROM Buildings WHERE settlementId = ? AND buildingType = ?\";\n";
    echo "// Get next level costs\n";
    echo "\$sql2 = \"SELECT * FROM BuildingConfig WHERE buildingType = ? AND level = ?\";\n";
    echo "// Get current resources\n";
    echo "\$sql3 = \"SELECT wood, stone, ore FROM Settlement WHERE settlementId = ?\";\n";
    echo "// Get available settlers\n";
    echo "\$sql4 = \"SELECT freeSettlers FROM SettlementSettlers WHERE settlementId = ?\";\n";
    echo "// Check affordability in PHP\n";
    echo "\$canAfford = (\$resources['wood'] >= \$costs['costWood'] && ...);\n";
    echo "```\n\n";
    
    echo "✅ AFTER (Single view query with built-in affordability check):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT * FROM BuildingUpgradeCosts \n";
    echo "        WHERE settlementId = ? AND buildingType = ?\";\n";
    echo "// Everything included: costs, resources, affordability, settlers\n";
    echo "```\n\n";
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM BuildingUpgradeCosts WHERE settlementId = 1 AND buildingType = 'Holzfäller'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "🔍 Sample Result:\n";
                echo "   Building: {$result['buildingType']} Level {$result['currentLevel']} -> {$result['nextLevel']}\n";
                echo "   Costs: Wood={$result['costWood']}, Stone={$result['costStone']}, Ore={$result['costOre']}\n";
                echo "   Settlers: {$result['settlers']} required, {$result['freeSettlers']} available\n";
                echo "   Can Afford: " . ($result['canAfford'] ? 'YES' : 'NO') . "\n";
                echo "   Message: {$result['affordabilityMessage']}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Error fetching sample data: " . $e->getMessage() . "\n";
        }
    } else {
        echo "🔍 Sample Result (Mock):\n";
        echo "   Building: Holzfäller Level 1 -> 2\n";
        echo "   Costs: Wood=110, Stone=110, Ore=110\n";
        echo "   Settlers: 1.1 required, 50 available\n";
        echo "   Can Afford: YES\n";
        echo "   Message: Can afford upgrade\n";
    }
    echo "\n";
    
    // Example 3: Military training costs
    echo "3️⃣  Military Training Costs\n";
    echo "---------------------------\n";
    echo "❌ BEFORE (Complex joins with research checks):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT muc.*, ur.isResearched, s.wood, s.stone, s.ore, p.gold\n";
    echo "        FROM MilitaryUnitConfig muc\n";
    echo "        JOIN Settlement s ON s.settlementId = ?\n";
    echo "        JOIN Spieler p ON s.playerId = p.playerId\n";
    echo "        LEFT JOIN UnitResearch ur ON s.settlementId = ur.settlementId \n";
    echo "        AND muc.unitType = ur.unitType\n";
    echo "        WHERE muc.unitType = ?\";\n";
    echo "// Then check affordability and research status in PHP\n";
    echo "```\n\n";
    
    echo "✅ AFTER (Simple view with all checks included):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT * FROM MilitaryTrainingCosts \n";
    echo "        WHERE settlementId = ? AND unitType = ?\";\n";
    echo "// Includes costs, research status, affordability, and helpful messages\n";
    echo "```\n\n";
    
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM MilitaryTrainingCosts WHERE settlementId = 1 AND unitType = 'guards'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "🔍 Sample Result:\n";
                echo "   Unit: {$result['unitType']}\n";
                echo "   Costs: Wood={$result['costWood']}, Stone={$result['costStone']}, Ore={$result['costOre']}, Gold={$result['costGold']}\n";
                echo "   Settlers: {$result['costSettlers']} required, {$result['freeSettlers']} available\n";
                echo "   Researched: " . ($result['isResearched'] ? 'YES' : 'NO') . "\n";
                echo "   Can Train: " . ($result['canAfford'] ? 'YES' : 'NO') . "\n";
                echo "   Message: {$result['affordabilityMessage']}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Error fetching sample data: " . $e->getMessage() . "\n";
        }
    } else {
        echo "🔍 Sample Result (Mock):\n";
        echo "   Unit: guards\n";
        echo "   Costs: Wood=50, Stone=30, Ore=20, Gold=10\n";
        echo "   Settlers: 1 required, 50 available\n";
        echo "   Researched: NO\n";
        echo "   Can Train: NO\n";
        echo "   Message: Unit not researched\n";
    }
    echo "\n";
    
    // Example 4: Game statistics
    echo "4️⃣  Game Statistics\n";
    echo "-------------------\n";
    echo "❌ BEFORE (Multiple separate queries):\n";
    echo "```php\n";
    echo "\$playerCount = \"SELECT COUNT(*) FROM Spieler\";\n";
    echo "\$settlementCount = \"SELECT COUNT(*) FROM Settlement\";\n";
    echo "\$totalWood = \"SELECT SUM(wood) FROM Settlement\";\n";
    echo "\$totalGold = \"SELECT SUM(gold) FROM Spieler\";\n";
    echo "\$activeQueues = \"SELECT COUNT(*) FROM BuildingQueue\";\n";
    echo "// ... many more queries\n";
    echo "```\n\n";
    
    echo "✅ AFTER (Single comprehensive view):\n";
    echo "```php\n";
    echo "\$sql = \"SELECT * FROM GameStatistics\";\n";
    echo "// All game-wide statistics in one query\n";
    echo "```\n\n";
    
    if ($conn) {
        try {
            $stmt = $conn->query("SELECT * FROM GameStatistics");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                echo "🔍 Sample Result:\n";
                echo "   Players: {$result['totalPlayers']}\n";
                echo "   Settlements: {$result['totalSettlements']}\n";
                echo "   Total Resources: Wood={$result['totalWoodInGame']}, Stone={$result['totalStoneInGame']}, Ore={$result['totalOreInGame']}\n";
                echo "   Total Gold: {$result['totalGoldInGame']}\n";
                echo "   Active Queues: Building={$result['activeBuildingQueues']}, Military={$result['activeMilitaryQueues']}, Research={$result['activeResearchQueues']}\n";
                echo "   Trade Offers: {$result['activeTradeOffers']}\n";
                echo "   Avg Points: " . round($result['averagePlayerPoints'], 2) . "\n";
                echo "   Max Building Level: {$result['highestBuildingLevel']}\n";
            }
        } catch (Exception $e) {
            echo "⚠️  Error fetching sample data: " . $e->getMessage() . "\n";
        }
    } else {
        echo "🔍 Sample Result (Mock):\n";
        echo "   Players: 3\n";
        echo "   Settlements: 3\n";
        echo "   Total Resources: Wood=30000, Stone=30000, Ore=30000\n";
        echo "   Total Gold: 1500\n";
        echo "   Active Queues: Building=0, Military=0, Research=0\n";
        echo "   Trade Offers: 0\n";
        echo "   Avg Points: 0\n";
        echo "   Max Building Level: 1\n";
    }
    echo "\n";
    
    echo "🎯 Benefits of Enhanced Views:\n";
    echo "===============================\n";
    echo "✅ Simplified PHP Code - Complex JOINs moved to database\n";
    echo "✅ Better Performance - Database-optimized queries\n";
    echo "✅ Reduced Errors - Less complex PHP logic\n";
    echo "✅ Built-in Validation - Affordability checks in database\n";
    echo "✅ Consistency - Same logic used everywhere\n";
    echo "✅ Maintainability - Changes only needed in one place\n";
    echo "✅ Readability - Clear, simple PHP code\n\n";
    
    echo "📖 View Usage Guidelines:\n";
    echo "==========================\n";
    echo "• Use SettlementResources for player+settlement data\n";
    echo "• Use BuildingUpgradeCosts for building upgrade logic\n";
    echo "• Use MilitaryTrainingCosts for unit training logic\n";
    echo "• Use ResearchCosts for research affordability\n";
    echo "• Use AllBuildingsOverview for complete building status\n";
    echo "• Use GameStatistics for admin dashboards\n";
    echo "• Use ActiveQueues for all queue types in one query\n\n";
}
?>