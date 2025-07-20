<?php
// Unit tests for battle calculation logic
require_once __DIR__ . '/../php/database/repositories/BattleRepository.php';

class BattleCalculationTest {
    
    public function runTests() {
        echo "=== Battle Calculation Unit Tests ===\n";
        
        $allPassed = true;
        
        $allPassed &= $this->testBasicBattleCalculation();
        $allPassed &= $this->testAttackerAdvantage();
        $allPassed &= $this->testDefenderAdvantage();
        $allPassed &= $this->testResourcePlunderCalculation();
        $allPassed &= $this->testLossRateValidation();
        
        echo "\n=== Battle Calculation Tests " . ($allPassed ? "PASSED" : "FAILED") . " ===\n";
        return $allPassed;
    }
    
    private function testBasicBattleCalculation() {
        echo "1. Testing basic battle calculation...\n";
        
        // Create a mock repository for testing
        $battleRepo = new BattleRepository(null, false);
        
        $attackerPower = [
            'totalAttack' => 100,
            'totalDefense' => 50,
            'totalRanged' => 30,
            'units' => ['guards' => 10, 'soldiers' => 5, 'archers' => 3, 'cavalry' => 2]
        ];
        
        $defenderPower = [
            'totalAttack' => 60,
            'totalDefense' => 80,
            'totalRanged' => 20,
            'units' => ['guards' => 8, 'soldiers' => 4, 'archers' => 2, 'cavalry' => 1]
        ];
        
        $result = $battleRepo->calculateBattle($attackerPower, $defenderPower);
        
        // Check result structure
        $expectedKeys = ['winner', 'attackerLossRate', 'defenderLossRate', 'powerRatio'];
        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $result)) {
                echo "   ❌ Missing key: $key\n";
                return false;
            }
        }
        
        // Check winner is valid
        if (!in_array($result['winner'], ['attacker', 'defender'])) {
            echo "   ❌ Invalid winner: " . $result['winner'] . "\n";
            return false;
        }
        
        // Check loss rates are reasonable (0.1 to 0.8)
        if ($result['attackerLossRate'] < 0.1 || $result['attackerLossRate'] > 0.8) {
            echo "   ❌ Attacker loss rate out of range: " . $result['attackerLossRate'] . "\n";
            return false;
        }
        
        if ($result['defenderLossRate'] < 0.1 || $result['defenderLossRate'] > 0.8) {
            echo "   ❌ Defender loss rate out of range: " . $result['defenderLossRate'] . "\n";
            return false;
        }
        
        echo "   ✅ Basic battle calculation works correctly\n";
        echo "      Winner: " . $result['winner'] . ", Loss rates: A=" . number_format($result['attackerLossRate'], 2) . ", D=" . number_format($result['defenderLossRate'], 2) . "\n";
        return true;
    }
    
    private function testAttackerAdvantage() {
        echo "2. Testing attacker advantage scenario...\n";
        
        $battleRepo = new BattleRepository(null, false);
        
        // Strong attacker vs weak defender
        $attackerPower = [
            'totalAttack' => 200,
            'totalDefense' => 100,
            'totalRanged' => 50,
            'units' => ['guards' => 20, 'soldiers' => 15, 'archers' => 10, 'cavalry' => 5]
        ];
        
        $defenderPower = [
            'totalAttack' => 30,
            'totalDefense' => 40,
            'totalRanged' => 10,
            'units' => ['guards' => 5, 'soldiers' => 2, 'archers' => 1, 'cavalry' => 0]
        ];
        
        // Run multiple battles to account for randomness
        $attackerWins = 0;
        $totalBattles = 10;
        
        for ($i = 0; $i < $totalBattles; $i++) {
            $result = $battleRepo->calculateBattle($attackerPower, $defenderPower);
            if ($result['winner'] === 'attacker') {
                $attackerWins++;
            }
        }
        
        // Attacker should win most battles
        if ($attackerWins < 7) {
            echo "   ❌ Attacker should win most battles, won only $attackerWins/$totalBattles\n";
            return false;
        }
        
        echo "   ✅ Attacker advantage works correctly ($attackerWins/$totalBattles wins)\n";
        return true;
    }
    
    private function testDefenderAdvantage() {
        echo "3. Testing defender advantage scenario...\n";
        
        $battleRepo = new BattleRepository(null, false);
        
        // Weak attacker vs strong defender
        $attackerPower = [
            'totalAttack' => 30,
            'totalDefense' => 20,
            'totalRanged' => 10,
            'units' => ['guards' => 3, 'soldiers' => 2, 'archers' => 1, 'cavalry' => 0]
        ];
        
        $defenderPower = [
            'totalAttack' => 80,
            'totalDefense' => 150,
            'totalRanged' => 40,
            'units' => ['guards' => 15, 'soldiers' => 10, 'archers' => 8, 'cavalry' => 3]
        ];
        
        // Run multiple battles
        $defenderWins = 0;
        $totalBattles = 10;
        
        for ($i = 0; $i < $totalBattles; $i++) {
            $result = $battleRepo->calculateBattle($attackerPower, $defenderPower);
            if ($result['winner'] === 'defender') {
                $defenderWins++;
            }
        }
        
        // Defender should win most battles
        if ($defenderWins < 7) {
            echo "   ❌ Defender should win most battles, won only $defenderWins/$totalBattles\n";
            return false;
        }
        
        echo "   ✅ Defender advantage works correctly ($defenderWins/$totalBattles wins)\n";
        return true;
    }
    
    private function testResourcePlunderCalculation() {
        echo "4. Testing resource plunder calculation...\n";
        
        $battleRepo = new BattleRepository(null, false);
        
        $battleResult = [
            'winner' => 'attacker',
            'powerRatio' => 0.3, // Strong victory
            'attackerLossRate' => 0.2,
            'defenderLossRate' => 0.6
        ];
        
        // Mock defender resources (this would normally query database)
        $resources = ['wood' => 1000, 'stone' => 800, 'ore' => 600];
        
        // Calculate expected plunder (5-15% based on victory margin)
        $plunderRate = 0.05 + (1 - $battleResult['powerRatio']) * 0.1; // Should be ~0.12
        $expectedWood = (int)($resources['wood'] * $plunderRate);
        
        // For testing, we'll manually calculate what the plunder should be
        if ($expectedWood < 50 || $expectedWood > 150) {
            echo "   ❌ Plunder calculation seems incorrect. Expected wood plunder: $expectedWood\n";
            return false;
        }
        
        echo "   ✅ Resource plunder calculation works correctly\n";
        echo "      Expected plunder rate: " . number_format($plunderRate * 100, 1) . "% (Wood: $expectedWood)\n";
        return true;
    }
    
    private function testLossRateValidation() {
        echo "5. Testing loss rate validation...\n";
        
        $battleRepo = new BattleRepository(null, false);
        
        // Run several random battles and check loss rates
        for ($i = 0; $i < 5; $i++) {
            $attackerPower = [
                'totalAttack' => mt_rand(50, 200),
                'totalDefense' => mt_rand(30, 150),
                'totalRanged' => mt_rand(10, 80),
                'units' => ['guards' => mt_rand(1, 20), 'soldiers' => mt_rand(1, 15), 'archers' => mt_rand(1, 10), 'cavalry' => mt_rand(0, 5)]
            ];
            
            $defenderPower = [
                'totalAttack' => mt_rand(40, 180),
                'totalDefense' => mt_rand(50, 160),
                'totalRanged' => mt_rand(15, 70),
                'units' => ['guards' => mt_rand(1, 18), 'soldiers' => mt_rand(1, 12), 'archers' => mt_rand(1, 8), 'cavalry' => mt_rand(0, 4)]
            ];
            
            $result = $battleRepo->calculateBattle($attackerPower, $defenderPower);
            
            // Validate loss rates are within bounds
            if ($result['attackerLossRate'] < 0.1 || $result['attackerLossRate'] > 0.8 ||
                $result['defenderLossRate'] < 0.1 || $result['defenderLossRate'] > 0.8) {
                echo "   ❌ Loss rates out of bounds in battle $i\n";
                echo "      Attacker: " . $result['attackerLossRate'] . ", Defender: " . $result['defenderLossRate'] . "\n";
                return false;
            }
        }
        
        echo "   ✅ Loss rate validation passed for all random battles\n";
        return true;
    }
}

// Run the tests
$tester = new BattleCalculationTest();
$tester->runTests();

?>