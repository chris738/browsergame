<?php
// Test battle system implementation
require_once __DIR__ . '/../php/database.php';

class BattleSystemTest {
    private $database;
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runTests() {
        echo "=== Battle System Tests ===\n";
        
        if (!$this->database->isConnected()) {
            echo "❌ Database connection failed\n";
            return false;
        }
        
        // Test 1: Check if battle tables exist
        echo "1. Checking battle table creation...\n";
        if ($this->testTableCreation()) {
            echo "✅ Battle tables created successfully\n";
        } else {
            echo "❌ Failed to create battle tables\n";
            return false;
        }
        
        // Test 2: Test military power calculation
        echo "2. Testing military power calculation...\n";
        if ($this->testMilitaryPowerCalculation()) {
            echo "✅ Military power calculation works\n";
        } else {
            echo "❌ Military power calculation failed\n";
        }
        
        // Test 3: Test battle calculation algorithm
        echo "3. Testing battle calculation algorithm...\n";
        if ($this->testBattleCalculation()) {
            echo "✅ Battle calculation algorithm works\n";
        } else {
            echo "❌ Battle calculation algorithm failed\n";
        }
        
        // Test 4: Test getting attackable settlements
        echo "4. Testing attackable settlements query...\n";
        if ($this->testAttackableSettlements()) {
            echo "✅ Attackable settlements query works\n";
        } else {
            echo "❌ Attackable settlements query failed\n";
        }
        
        echo "\n=== Battle System Tests Complete ===\n";
        return true;
    }
    
    private function testTableCreation() {
        try {
            // Try to apply the battle tables SQL
            $sql = file_get_contents(__DIR__ . '/../sql/tables/battle_tables.sql');
            $connection = $this->database->getConnection();
            $connection->exec($sql);
            
            // Check if tables exist
            $tables = ['Battles', 'BattleParticipants', 'BattleLogs'];
            foreach ($tables as $table) {
                $stmt = $connection->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if (!$stmt->fetch()) {
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            echo "Table creation error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testMilitaryPowerCalculation() {
        try {
            // Test with settlement 1 (should exist)
            $power = $this->database->getSettlementMilitaryPower(1);
            
            // Should return a valid structure
            $expectedKeys = ['totalAttack', 'totalDefense', 'totalRanged', 'units'];
            foreach ($expectedKeys as $key) {
                if (!array_key_exists($key, $power)) {
                    echo "Missing key: $key\n";
                    return false;
                }
            }
            
            // Units should have all unit types
            $expectedUnits = ['guards', 'soldiers', 'archers', 'cavalry'];
            foreach ($expectedUnits as $unitType) {
                if (!array_key_exists($unitType, $power['units'])) {
                    echo "Missing unit type: $unitType\n";
                    return false;
                }
            }
            
            echo "   Power: Attack=" . $power['totalAttack'] . ", Defense=" . $power['totalDefense'] . ", Ranged=" . $power['totalRanged'] . "\n";
            return true;
        } catch (Exception $e) {
            echo "Military power calculation error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testBattleCalculation() {
        try {
            // Create mock battle repository to test calculation
            $battleRepo = new BattleRepository($this->database->getConnection(), false);
            
            $attackerPower = [
                'totalAttack' => 100,
                'totalDefense' => 50,
                'totalRanged' => 30,
                'units' => ['guards' => 10, 'soldiers' => 5, 'archers' => 3, 'cavalry' => 2]
            ];
            
            $defenderPower = [
                'totalAttack' => 80,
                'totalDefense' => 70,
                'totalRanged' => 20,
                'units' => ['guards' => 8, 'soldiers' => 4, 'archers' => 2, 'cavalry' => 1]
            ];
            
            $result = $battleRepo->calculateBattle($attackerPower, $defenderPower);
            
            $expectedKeys = ['winner', 'attackerLossRate', 'defenderLossRate', 'powerRatio'];
            foreach ($expectedKeys as $key) {
                if (!array_key_exists($key, $result)) {
                    echo "Missing result key: $key\n";
                    return false;
                }
            }
            
            if (!in_array($result['winner'], ['attacker', 'defender'])) {
                echo "Invalid winner: " . $result['winner'] . "\n";
                return false;
            }
            
            echo "   Battle result: Winner=" . $result['winner'] . ", Attacker loss rate=" . number_format($result['attackerLossRate'], 2) . "\n";
            return true;
        } catch (Exception $e) {
            echo "Battle calculation error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testAttackableSettlements() {
        try {
            $settlements = $this->database->getAttackableSettlements(1);
            
            // Should return an array (might be empty)
            if (!is_array($settlements)) {
                echo "Attackable settlements should return an array\n";
                return false;
            }
            
            echo "   Found " . count($settlements) . " attackable settlements\n";
            
            // If settlements exist, check structure
            if (count($settlements) > 0) {
                $expectedKeys = ['settlementId', 'settlementName', 'coordinateX', 'coordinateY'];
                foreach ($expectedKeys as $key) {
                    if (!array_key_exists($key, $settlements[0])) {
                        echo "Missing settlement key: $key\n";
                        return false;
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            echo "Attackable settlements error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run the tests
$tester = new BattleSystemTest();
$tester->runTests();

?>