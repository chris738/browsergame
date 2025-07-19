<?php
/**
 * SQL Data Validator Class
 * Provides centralized validation for all SQL data reading
 * Ensures data integrity and correctness
 */

class SQLDataValidator {
    
    /**
     * Validate resource data structure and values
     */
    public static function validateResourceData($resources) {
        if (!is_array($resources)) {
            throw new InvalidArgumentException("Resources must be an array");
        }
        
        $requiredFields = ['wood', 'stone', 'ore', 'storageCapacity', 'maxSettlers', 'freeSettlers'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $resources)) {
                throw new InvalidArgumentException("Missing required resource field: $field");
            }
            
            if (!is_numeric($resources[$field])) {
                throw new InvalidArgumentException("Resource field $field must be numeric, got: " . gettype($resources[$field]));
            }
            
            $value = (float)$resources[$field];
            
            // Check for negative values
            if ($value < 0) {
                throw new InvalidArgumentException("Resource field $field cannot be negative: $value");
            }
            
            // Check for extremely large values (potential corruption)
            if ($value > 1000000000) { // 1 billion
                throw new InvalidArgumentException("Resource field $field value too large: $value");
            }
        }
        
        // Validate business logic
        if ($resources['freeSettlers'] > $resources['maxSettlers']) {
            throw new InvalidArgumentException("Free settlers ({$resources['freeSettlers']}) cannot exceed max settlers ({$resources['maxSettlers']})");
        }
        
        // Check storage capacity logic
        $maxResource = max($resources['wood'], $resources['stone'], $resources['ore']);
        if ($resources['storageCapacity'] > 0 && $maxResource > $resources['storageCapacity']) {
            throw new InvalidArgumentException("Resource exceeds storage capacity: max resource $maxResource > capacity {$resources['storageCapacity']}");
        }
        
        return true;
    }
    
    /**
     * Validate building data structure and values
     */
    public static function validateBuildingData($building) {
        if (!is_array($building)) {
            throw new InvalidArgumentException("Building data must be an array");
        }
        
        $requiredFields = ['currentLevel', 'nextLevel', 'costWood', 'costStone', 'costOre', 'settlers', 'buildTime'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $building)) {
                throw new InvalidArgumentException("Missing required building field: $field");
            }
            
            if (!is_numeric($building[$field])) {
                throw new InvalidArgumentException("Building field $field must be numeric, got: " . gettype($building[$field]));
            }
            
            $value = (float)$building[$field];
            
            // Check for negative values (except for some fields where negative might be valid)
            if ($value < 0 && !in_array($field, [])) { // Currently no fields allow negatives
                throw new InvalidArgumentException("Building field $field cannot be negative: $value");
            }
            
            // Check for reasonable bounds
            if ($field === 'currentLevel' || $field === 'nextLevel') {
                if ($value < 0 || $value > 1000) {
                    throw new InvalidArgumentException("Building level $field out of reasonable range: $value");
                }
            }
            
            if (in_array($field, ['costWood', 'costStone', 'costOre']) && $value > 1000000) {
                throw new InvalidArgumentException("Building cost $field too high: $value");
            }
            
            if ($field === 'buildTime' && $value > 86400) { // More than 24 hours
                throw new InvalidArgumentException("Build time too long: $value seconds");
            }
        }
        
        // Validate business logic
        if ($building['nextLevel'] <= $building['currentLevel']) {
            throw new InvalidArgumentException("Next level ({$building['nextLevel']}) must be greater than current level ({$building['currentLevel']})");
        }
        
        return true;
    }
    
    /**
     * Validate queue entry data
     */
    public static function validateQueueData($queueEntry) {
        if (!is_array($queueEntry)) {
            throw new InvalidArgumentException("Queue entry must be an array");
        }
        
        $requiredFields = ['queueId', 'buildingType', 'startTime', 'endTime'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $queueEntry)) {
                throw new InvalidArgumentException("Missing required queue field: $field");
            }
            
            if ($queueEntry[$field] === null || $queueEntry[$field] === '') {
                throw new InvalidArgumentException("Queue field $field cannot be null or empty");
            }
        }
        
        // Validate queue ID
        if (!is_numeric($queueEntry['queueId']) || $queueEntry['queueId'] <= 0) {
            throw new InvalidArgumentException("Queue ID must be positive integer: {$queueEntry['queueId']}");
        }
        
        // Validate building type
        $validBuildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
        if (!in_array($queueEntry['buildingType'], $validBuildingTypes)) {
            throw new InvalidArgumentException("Invalid building type: {$queueEntry['buildingType']}");
        }
        
        // Validate time format (basic check)
        $startTime = strtotime($queueEntry['startTime']);
        $endTime = strtotime($queueEntry['endTime']);
        
        if ($startTime === false) {
            throw new InvalidArgumentException("Invalid start time format: {$queueEntry['startTime']}");
        }
        
        if ($endTime === false) {
            throw new InvalidArgumentException("Invalid end time format: {$queueEntry['endTime']}");
        }
        
        if ($endTime <= $startTime) {
            throw new InvalidArgumentException("End time must be after start time: {$queueEntry['startTime']} -> {$queueEntry['endTime']}");
        }
        
        return true;
    }
    
    /**
     * Validate map entry data
     */
    public static function validateMapData($mapEntry) {
        if (!is_array($mapEntry)) {
            throw new InvalidArgumentException("Map entry must be an array");
        }
        
        $requiredFields = ['settlementId', 'xCoordinate', 'yCoordinate'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $mapEntry)) {
                throw new InvalidArgumentException("Missing required map field: $field");
            }
            
            if (!is_numeric($mapEntry[$field])) {
                throw new InvalidArgumentException("Map field $field must be numeric: {$mapEntry[$field]}");
            }
        }
        
        // Validate settlement ID
        if ($mapEntry['settlementId'] <= 0) {
            throw new InvalidArgumentException("Settlement ID must be positive: {$mapEntry['settlementId']}");
        }
        
        // Validate coordinate bounds (assuming reasonable game world size)
        $x = (int)$mapEntry['xCoordinate'];
        $y = (int)$mapEntry['yCoordinate'];
        
        if ($x < -1000 || $x > 1000) {
            throw new InvalidArgumentException("X coordinate out of bounds: $x");
        }
        
        if ($y < -1000 || $y > 1000) {
            throw new InvalidArgumentException("Y coordinate out of bounds: $y");
        }
        
        return true;
    }
    
    /**
     * Validate settlement name data
     */
    public static function validateSettlementName($nameData) {
        if (!is_array($nameData)) {
            throw new InvalidArgumentException("Settlement name data must be an array");
        }
        
        if (!array_key_exists('SettlementName', $nameData)) {
            throw new InvalidArgumentException("Missing SettlementName field");
        }
        
        $name = $nameData['SettlementName'];
        
        if (!is_string($name) || empty(trim($name))) {
            throw new InvalidArgumentException("Settlement name must be non-empty string");
        }
        
        // Check for dangerous characters
        $dangerousChars = ["'", '"', '<', '>', '\\', '\0', '\n', '\r'];
        foreach ($dangerousChars as $char) {
            if (strpos($name, $char) !== false) {
                throw new InvalidArgumentException("Settlement name contains dangerous character: $char");
            }
        }
        
        // Check UTF-8 encoding
        if (!mb_check_encoding($name, 'UTF-8')) {
            throw new InvalidArgumentException("Settlement name is not valid UTF-8");
        }
        
        // Check length bounds
        if (mb_strlen($name, 'UTF-8') > 100) {
            throw new InvalidArgumentException("Settlement name too long: " . mb_strlen($name, 'UTF-8') . " characters");
        }
        
        return true;
    }
    
    /**
     * Sanitize and validate integer ID
     */
    public static function validateId($id, $fieldName = 'ID') {
        if (!is_numeric($id)) {
            throw new InvalidArgumentException("$fieldName must be numeric: $id");
        }
        
        $intId = (int)$id;
        
        if ($intId <= 0) {
            throw new InvalidArgumentException("$fieldName must be positive integer: $intId");
        }
        
        if ($intId > 2147483647) { // Max INT value
            throw new InvalidArgumentException("$fieldName too large: $intId");
        }
        
        return $intId;
    }
    
    /**
     * Validate building type string
     */
    public static function validateBuildingType($buildingType) {
        if (!is_string($buildingType)) {
            throw new InvalidArgumentException("Building type must be string: " . gettype($buildingType));
        }
        
        $validTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Rathaus', 'Markt', 'Kaserne'];
        
        if (!in_array($buildingType, $validTypes)) {
            throw new InvalidArgumentException("Invalid building type: $buildingType");
        }
        
        // Check UTF-8 encoding
        if (!mb_check_encoding($buildingType, 'UTF-8')) {
            throw new InvalidArgumentException("Building type is not valid UTF-8: $buildingType");
        }
        
        return $buildingType;
    }
    
    /**
     * Log validation errors for debugging
     */
    public static function logValidationError($context, $error) {
        $timestamp = date('Y-m-d H:i:s');
        $message = "[$timestamp] SQL Data Validation Error in $context: $error";
        error_log($message);
        
        // Also log to a specific file if needed
        // file_put_contents('/tmp/sql-validation-errors.log', $message . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>