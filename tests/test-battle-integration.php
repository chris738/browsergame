<?php
// Integration test for the complete battle system
require_once __DIR__ . '/../php/database.php';
require_once __DIR__ . '/../php/emoji-config.php';

class BattleSystemIntegrationTest {
    
    public function runTests() {
        echo "=== Battle System Integration Tests ===\n";
        
        // Test 1: Check all battle system files exist
        echo "1. Checking file structure...\n";
        if (!$this->testFileStructure()) {
            echo "❌ File structure test failed\n";
            return false;
        }
        echo "✅ All battle system files exist\n";
        
        // Test 2: Check PHP syntax of all files
        echo "2. Checking PHP syntax...\n";
        if (!$this->testPHPSyntax()) {
            echo "❌ PHP syntax test failed\n";
            return false;
        }
        echo "✅ All PHP files have valid syntax\n";
        
        // Test 3: Check class loading
        echo "3. Testing class loading...\n";
        if (!$this->testClassLoading()) {
            echo "❌ Class loading test failed\n";
            return false;
        }
        echo "✅ All classes load correctly\n";
        
        // Test 4: Check database methods (without connection)
        echo "4. Testing database methods...\n";
        if (!$this->testDatabaseMethods()) {
            echo "❌ Database methods test failed\n";
            return false;
        }
        echo "✅ Database methods work correctly\n";
        
        // Test 5: Check emoji configuration
        echo "5. Testing emoji configuration...\n";
        if (!$this->testEmojiConfiguration()) {
            echo "❌ Emoji configuration test failed\n";
            return false;
        }
        echo "✅ Emoji configuration works correctly\n";
        
        echo "\n=== Battle System Integration Tests PASSED ===\n";
        return true;
    }
    
    private function testFileStructure() {
        $requiredFiles = [
            'battle.php',
            'php/battle-backend.php', 
            'php/database/repositories/BattleRepository.php',
            'sql/tables/battle_tables.sql'
        ];
        
        foreach ($requiredFiles as $file) {
            $fullPath = __DIR__ . '/../' . $file;
            if (!file_exists($fullPath)) {
                echo "   Missing file: $file\n";
                return false;
            }
        }
        
        return true;
    }
    
    private function testPHPSyntax() {
        $phpFiles = [
            'battle.php',
            'php/battle-backend.php',
            'php/database/repositories/BattleRepository.php'
        ];
        
        foreach ($phpFiles as $file) {
            $fullPath = __DIR__ . '/../' . $file;
            $output = [];
            $returnCode = 0;
            
            exec("php -l \"$fullPath\" 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                echo "   Syntax error in $file: " . implode("\n", $output) . "\n";
                return false;
            }
        }
        
        return true;
    }
    
    private function testClassLoading() {
        try {
            // Test if Database class can be instantiated
            $database = new Database();
            
            // Test if BattleRepository can be instantiated
            $battleRepo = new BattleRepository(null, true); // connectionFailed = true for testing
            
            // Test if EmojiConfig class works
            $emoji = EmojiConfig::getUnitEmoji('guards');
            
            return true;
        } catch (Exception $e) {
            echo "   Class loading error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testDatabaseMethods() {
        try {
            // Create database instance with failed connection for testing
            $database = new Database();
            
            // Test battle methods (should return safe defaults with no connection)
            $power = $database->getSettlementMilitaryPower(1);
            if (!is_array($power) || !isset($power['totalAttack'])) {
                echo "   Military power method failed\n";
                return false;
            }
            
            $settlements = $database->getAttackableSettlements(1);
            if (!is_array($settlements)) {
                echo "   Attackable settlements method failed\n";
                return false;
            }
            
            $battles = $database->getRecentBattles(1);
            if (!is_array($battles)) {
                echo "   Recent battles method failed\n";
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            echo "   Database methods error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function testEmojiConfiguration() {
        try {
            // Test unit emojis
            $unitTypes = ['guards', 'soldiers', 'archers', 'cavalry'];
            foreach ($unitTypes as $unitType) {
                $emoji = EmojiConfig::getUnitEmoji($unitType);
                if (empty($emoji)) {
                    echo "   Missing emoji for unit: $unitType\n";
                    return false;
                }
                
                $title = EmojiConfig::getUnitTitle($unitType);
                if (empty($title)) {
                    echo "   Missing title for unit: $unitType\n";
                    return false;
                }
            }
            
            // Test that emojis are different
            $emojis = array_map(function($type) {
                return EmojiConfig::getUnitEmoji($type);
            }, $unitTypes);
            
            if (count(array_unique($emojis)) !== count($emojis)) {
                echo "   Unit emojis are not unique\n";
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            echo "   Emoji configuration error: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run the integration tests
$tester = new BattleSystemIntegrationTest();
$success = $tester->runTests();

exit($success ? 0 : 1);

?>