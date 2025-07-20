<?php
// Centralized emoji configuration for the browser game (PHP version)
// This file contains all emoji definitions used throughout the PHP backend

class EmojiConfig {
    // Resource emoji configuration
    public static $resources = [
        'wood' => [
            'emoji' => '🪵',
            'title' => 'Wood - Used for construction and upgrades'
        ],
        'stone' => [
            'emoji' => '🧱',
            'title' => 'Stone - Used for advanced buildings'
        ],
        'ore' => [
            'emoji' => '🪨',
            'title' => 'Ore - Used for high-level buildings'
        ],
        'gold' => [
            'emoji' => '💰',
            'title' => 'Gold - Universal currency for trading'
        ],
        'storage' => [
            'emoji' => '🏪',
            'title' => 'Storage Capacity - Maximum resources you can store'
        ],
        'settlers' => [
            'emoji' => '👥',
            'title' => 'Settlers - Population available for construction'
        ]
    ];
    
    // Building emoji configuration
    public static $buildings = [
        'rathaus' => [
            'emoji' => '🏛️',
            'title' => 'Town Hall - Center of your settlement'
        ],
        'holzfäller' => [
            'emoji' => '🌲',
            'title' => 'Lumberjack - Produces wood'
        ],
        'steinbruch' => [
            'emoji' => '🏔️',
            'title' => 'Quarry - Produces stone'
        ],
        'erzbergwerk' => [
            'emoji' => '⛏️',
            'title' => 'Mine - Produces ore'
        ],
        'lager' => [
            'emoji' => '🏪',
            'title' => 'Storage - Increases storage capacity'
        ],
        'farm' => [
            'emoji' => '🚜',
            'title' => 'Farm - Provides settlers for construction'
        ],
        'markt' => [
            'emoji' => '⚖️',
            'title' => 'Market - Enables trading with other players'
        ],
        'kaserne' => [
            'emoji' => '⚔️',
            'title' => 'Barracks - Trains military units and provides defense'
        ]
    ];
    
    // Military unit emoji configuration
    public static $units = [
        'guards' => [
            'emoji' => '🛡️',
            'title' => 'Guards - Basic defensive units'
        ],
        'soldiers' => [
            'emoji' => '⚔️',
            'title' => 'Soldiers - Primary melee combat units'
        ],
        'archers' => [
            'emoji' => '🏹',
            'title' => 'Archers - Ranged combat specialists'
        ],
        'cavalry' => [
            'emoji' => '🐎',
            'title' => 'Cavalry - Fast, powerful melee units'
        ]
    ];
    
    // UI and interface emojis
    public static $ui = [
        'player' => '👤',
        'time' => '⏱️',
        'refresh' => '🔄',
        'moon' => '🌙',
        'sun' => '☀️',
        'arrow_right' => '→',
        'arrow_bidirectional' => '↔',
        'market' => '⚖️',
        'settlement' => '🏘️',
        'map' => '🗺️',
        'status' => '🔍',
        'trade' => '🤝',
        'manage' => '⚙️'
    ];
    
    /**
     * Get resource emoji
     */
    public static function getResourceEmoji($resourceType) {
        return self::$resources[$resourceType]['emoji'] ?? '❓';
    }
    
    /**
     * Get building emoji
     */
    public static function getBuildingEmoji($buildingType) {
        return self::$buildings[$buildingType]['emoji'] ?? '🏗️';
    }
    
    /**
     * Get unit emoji
     */
    public static function getUnitEmoji($unitType) {
        return self::$units[$unitType]['emoji'] ?? '⚔️';
    }
    
    /**
     * Get unit title
     */
    public static function getUnitTitle($unitType) {
        return self::$units[$unitType]['title'] ?? $unitType;
    }
    
    /**
     * Get resource title
     */
    public static function getResourceTitle($resourceType) {
        return self::$resources[$resourceType]['title'] ?? $resourceType;
    }
    
    /**
     * Get building title
     */
    public static function getBuildingTitle($buildingType) {
        return self::$buildings[$buildingType]['title'] ?? $buildingType;
    }
    
    /**
     * Get UI emoji
     */
    public static function getUIEmoji($uiType) {
        return self::$ui[$uiType] ?? '❓';
    }
    
    /**
     * Format resource with emoji for display
     */
    public static function formatResourceWithEmoji($resourceType, $amount, $showRegen = false, $regenAmount = 0) {
        $emoji = self::getResourceEmoji($resourceType);
        $title = self::getResourceTitle($resourceType);
        $formatted = '<span class="resource-emoji" title="' . htmlspecialchars($title) . '">' . $emoji . '</span> ' . $amount;
        
        if ($showRegen && $regenAmount !== null) {
            $formatted .= '<span class="regen">(+' . $regenAmount . '/h)</span>';
        }
        
        return $formatted;
    }
    
    /**
     * Format building with emoji for display
     */
    public static function formatBuildingWithEmoji($buildingType, $name) {
        $emoji = self::getBuildingEmoji($buildingType);
        $title = self::getBuildingTitle($buildingType);
        return '<span class="building-emoji" title="' . htmlspecialchars($title) . '">' . $emoji . '</span> ' . htmlspecialchars($name);
    }
    
    /**
     * Get all resource types
     */
    public static function getResourceTypes() {
        return array_keys(self::$resources);
    }
    
    /**
     * Get all building types
     */
    public static function getBuildingTypes() {
        return array_keys(self::$buildings);
    }
}
?>