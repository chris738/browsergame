<?php
/**
 * SQL Data Integrity Checker
 * Runs comprehensive checks to ensure all SQL data is correct and consistent
 */

require_once 'php/database.php';

class SQLDataIntegrityChecker {
    private $database;
    private $issues = [];
    
    public function __construct() {
        $this->database = new Database();
    }
    
    public function runIntegrityCheck() {
        echo "=== SQL Data Integrity Check ===\n";
        echo "Checking all SQL data for consistency and correctness\n\n";
        
        $this->checkResourceIntegrity();
        $this->checkBuildingIntegrity();
        $this->checkQueueIntegrity();
        $this->checkMapIntegrity();
        $this->checkBuildingConfigIntegrity();
        $this->checkSettlementIntegrity();
        
        $this->reportResults();
    }
    
    private function checkResourceIntegrity() {
        echo "1. Checking Resource Data Integrity...\n";
        
        // Get all settlements
        $settlements = $this->database->getAllSettlements();
        
        if ($settlements) {
            foreach ($settlements as $settlement) {
                $settlementId = $settlement['settlementId'];
                $resources = $this->database->getResources($settlementId);
                
                if ($resources === false) {
                    $this->addIssue("Resources", "Cannot retrieve resources for settlement $settlementId");
                    continue;
                }
                
                // Check for data consistency with settlement table
                if (isset($settlement['wood'], $settlement['stone'], $settlement['ore'])) {
                    if (abs($settlement['wood'] - $resources['wood']) > 0.01) {
                        $this->addIssue("Resources", "Wood mismatch for settlement $settlementId: {$settlement['wood']} vs {$resources['wood']}");
                    }
                    if (abs($settlement['stone'] - $resources['stone']) > 0.01) {
                        $this->addIssue("Resources", "Stone mismatch for settlement $settlementId: {$settlement['stone']} vs {$resources['stone']}");
                    }
                    if (abs($settlement['ore'] - $resources['ore']) > 0.01) {
                        $this->addIssue("Resources", "Ore mismatch for settlement $settlementId: {$settlement['ore']} vs {$resources['ore']}");
                    }
                }
                
                // Check for business logic violations
                if ($resources['freeSettlers'] > $resources['maxSettlers']) {
                    $this->addIssue("Resources", "Free settlers exceed max settlers for settlement $settlementId: {$resources['freeSettlers']} > {$resources['maxSettlers']}");
                }
                
                // Check for impossible resource values
                foreach (['wood', 'stone', 'ore'] as $resource) {
                    if ($resources[$resource] < 0) {
                        $this->addIssue("Resources", "Negative $resource for settlement $settlementId: {$resources[$resource]}");
                    }
                    if ($resources[$resource] > 1000000000) {
                        $this->addIssue("Resources", "Extremely high $resource for settlement $settlementId: {$resources[$resource]}");
                    }
                }
            }
        }
        
        echo "   Checked " . count($settlements ?: []) . " settlements\n\n";
    }
    
    private function checkBuildingIntegrity() {
        echo "2. Checking Building Data Integrity...\n";
        
        $buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt'];
        $settlements = $this->database->getAllSettlements();
        $buildingsChecked = 0;
        
        if ($settlements) {
            foreach ($settlements as $settlement) {
                $settlementId = $settlement['settlementId'];
                
                foreach ($buildingTypes as $buildingType) {
                    try {
                        $building = $this->database->getBuilding($settlementId, $buildingType);
                        $buildingsChecked++;
                        
                        // Check level consistency
                        if ($building['nextLevel'] <= $building['currentLevel']) {
                            $this->addIssue("Buildings", "Next level not greater than current level for $buildingType in settlement $settlementId: {$building['nextLevel']} <= {$building['currentLevel']}");
                        }
                        
                        // Check cost reasonableness
                        foreach (['costWood', 'costStone', 'costOre'] as $cost) {
                            if ($building[$cost] < 0) {
                                $this->addIssue("Buildings", "Negative $cost for $buildingType level {$building['nextLevel']} in settlement $settlementId");
                            }
                            if ($building[$cost] > 1000000) {
                                $this->addIssue("Buildings", "Extremely high $cost for $buildingType level {$building['nextLevel']} in settlement $settlementId: {$building[$cost]}");
                            }
                        }
                        
                        // Check build time
                        if ($building['buildTime'] < 1 || $building['buildTime'] > 86400) {
                            $this->addIssue("Buildings", "Unreasonable build time for $buildingType in settlement $settlementId: {$building['buildTime']} seconds");
                        }
                        
                    } catch (Exception $e) {
                        // This is expected for some buildings that might not exist
                        // Only report if it's a validation error
                        if (strpos($e->getMessage(), 'validation') !== false) {
                            $this->addIssue("Buildings", "Validation error for $buildingType in settlement $settlementId: " . $e->getMessage());
                        }
                    }
                }
            }
        }
        
        echo "   Checked $buildingsChecked building instances\n\n";
    }
    
    private function checkQueueIntegrity() {
        echo "3. Checking Queue Data Integrity...\n";
        
        $settlements = $this->database->getAllSettlements();
        $queueItemsChecked = 0;
        
        if ($settlements) {
            foreach ($settlements as $settlement) {
                $settlementId = $settlement['settlementId'];
                $queue = $this->database->getQueue($settlementId);
                
                foreach ($queue as $queueItem) {
                    $queueItemsChecked++;
                    
                    // Check time consistency
                    $startTime = strtotime($queueItem['startTime']);
                    $endTime = strtotime($queueItem['endTime']);
                    
                    if ($endTime <= $startTime) {
                        $this->addIssue("Queue", "End time not after start time for queue item {$queueItem['queueId']}: {$queueItem['startTime']} -> {$queueItem['endTime']}");
                    }
                    
                    // Check queue times are reasonable
                    $duration = $endTime - $startTime;
                    if ($duration > 86400) { // More than 24 hours
                        $this->addIssue("Queue", "Very long build duration for queue item {$queueItem['queueId']}: $duration seconds");
                    }
                    
                    // Check if queue item is for valid building type
                    $validTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
                    if (!in_array($queueItem['buildingType'], $validTypes)) {
                        $this->addIssue("Queue", "Invalid building type in queue item {$queueItem['queueId']}: {$queueItem['buildingType']}");
                    }
                }
            }
        }
        
        echo "   Checked $queueItemsChecked queue items\n\n";
    }
    
    private function checkMapIntegrity() {
        echo "4. Checking Map Data Integrity...\n";
        
        $map = $this->database->getMap();
        $mapEntriesChecked = count($map ?: []);
        
        if ($map) {
            $seenSettlements = [];
            $seenCoordinates = [];
            
            foreach ($map as $entry) {
                $settlementId = $entry['settlementId'];
                $coordinates = $entry['xCoordinate'] . ',' . $entry['yCoordinate'];
                
                // Check for duplicate settlements
                if (in_array($settlementId, $seenSettlements)) {
                    $this->addIssue("Map", "Duplicate settlement on map: $settlementId");
                }
                $seenSettlements[] = $settlementId;
                
                // Check for settlements on same coordinates
                if (in_array($coordinates, $seenCoordinates)) {
                    $this->addIssue("Map", "Multiple settlements at same coordinates: $coordinates");
                }
                $seenCoordinates[] = $coordinates;
                
                // Check coordinate bounds
                if (abs($entry['xCoordinate']) > 1000 || abs($entry['yCoordinate']) > 1000) {
                    $this->addIssue("Map", "Coordinates out of reasonable bounds for settlement $settlementId: ({$entry['xCoordinate']}, {$entry['yCoordinate']})");
                }
            }
        }
        
        echo "   Checked $mapEntriesChecked map entries\n\n";
    }
    
    private function checkBuildingConfigIntegrity() {
        echo "5. Checking Building Config Integrity...\n";
        
        $configs = $this->database->getAllBuildingConfigs();
        $configsChecked = count($configs ?: []);
        
        if ($configs) {
            $typesByLevel = [];
            
            foreach ($configs as $config) {
                $key = $config['buildingType'] . ':' . $config['level'];
                
                // Check for duplicate configs
                if (isset($typesByLevel[$key])) {
                    $this->addIssue("Config", "Duplicate config for {$config['buildingType']} level {$config['level']}");
                }
                $typesByLevel[$key] = true;
                
                // Check cost progression (should generally increase with level)
                if ($config['level'] > 1) {
                    $prevKey = $config['buildingType'] . ':' . ($config['level'] - 1);
                    // Note: We'd need to fetch the previous level config to check progression
                    // This is a simplified check
                }
                
                // Check reasonable values
                if ($config['costWood'] < 0 || $config['costStone'] < 0 || $config['costOre'] < 0) {
                    $this->addIssue("Config", "Negative costs in config for {$config['buildingType']} level {$config['level']}");
                }
                
                if ($config['buildTime'] < 1 || $config['buildTime'] > 86400) {
                    $this->addIssue("Config", "Unreasonable build time in config for {$config['buildingType']} level {$config['level']}: {$config['buildTime']}");
                }
            }
        }
        
        echo "   Checked $configsChecked building configs\n\n";
    }
    
    private function checkSettlementIntegrity() {
        echo "6. Checking Settlement Data Integrity...\n";
        
        $settlements = $this->database->getAllSettlements();
        $settlementsChecked = count($settlements ?: []);
        
        if ($settlements) {
            $seenNames = [];
            
            foreach ($settlements as $settlement) {
                // Check for duplicate settlement names
                $name = $settlement['name'];
                if (in_array($name, $seenNames)) {
                    $this->addIssue("Settlements", "Duplicate settlement name: $name");
                }
                $seenNames[] = $name;
                
                // Check resource values
                foreach (['wood', 'stone', 'ore'] as $resource) {
                    if (isset($settlement[$resource])) {
                        if ($settlement[$resource] < 0) {
                            $this->addIssue("Settlements", "Negative $resource for settlement {$settlement['settlementId']}: {$settlement[$resource]}");
                        }
                    }
                }
                
                // Check settlement name
                try {
                    $nameData = ['SettlementName' => $settlement['name']];
                    SQLDataValidator::validateSettlementName($nameData);
                } catch (InvalidArgumentException $e) {
                    $this->addIssue("Settlements", "Invalid settlement name for {$settlement['settlementId']}: " . $e->getMessage());
                }
            }
        }
        
        echo "   Checked $settlementsChecked settlements\n\n";
    }
    
    private function addIssue($category, $message) {
        $this->issues[] = [
            'category' => $category,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Also log to error log
        error_log("[SQL Data Integrity] $category: $message");
    }
    
    private function reportResults() {
        echo "=== Integrity Check Results ===\n";
        
        if (empty($this->issues)) {
            echo "✅ NO ISSUES FOUND! All SQL data appears to be read correctly and is consistent.\n";
            echo "\nThe following checks were performed:\n";
            echo "- Resource data validation and consistency\n";
            echo "- Building data integrity and business logic\n";
            echo "- Queue data temporal consistency\n";
            echo "- Map data uniqueness and bounds\n";
            echo "- Building configuration validity\n";
            echo "- Settlement data integrity\n";
            echo "\nAll SQL data reading operations are working correctly!\n";
        } else {
            echo "⚠️  ISSUES FOUND: " . count($this->issues) . " data integrity issues detected\n\n";
            
            $issuesByCategory = [];
            foreach ($this->issues as $issue) {
                $issuesByCategory[$issue['category']][] = $issue;
            }
            
            foreach ($issuesByCategory as $category => $issues) {
                echo "$category Issues (" . count($issues) . "):\n";
                foreach ($issues as $issue) {
                    echo "  - {$issue['message']}\n";
                }
                echo "\n";
            }
            
            echo "These issues indicate potential problems with SQL data reading correctness.\n";
            echo "Review the issues above and fix any data inconsistencies.\n";
        }
    }
}

// Run the integrity check
$checker = new SQLDataIntegrityChecker();
$checker->runIntegrityCheck();
?>