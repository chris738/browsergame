<?php
/**
 * Centralized emoji configuration for the browser game
 * This file contains all emojis used throughout the application to ensure consistency
 * and improve maintainability.
 */

class Emojis {
    // Resource emojis
    const WOOD = '🪵';
    const STONE = '🧱';
    const ORE = '🪨';
    const SETTLERS = '👥';
    const STORAGE = '🏪';
    const TIME = '⏱️';
    
    // Building emojis
    const TOWN_HALL = '🏛️';
    const LUMBERJACK = '🌲';
    const QUARRY = '🏔️';
    const MINE = '⛏️';
    const FARM = '🚜';
    const DEFAULT_BUILDING = '🏗️';
    
    // UI emojis
    const THEME_DARK = '🌙';
    const THEME_LIGHT = '☀️';
    const USER = '👤';
    
    /**
     * Get all resource emojis as an associative array
     * @return array
     */
    public static function getResourceEmojis() {
        return [
            'wood' => self::WOOD,
            'stone' => self::STONE,
            'ore' => self::ORE,
            'settlers' => self::SETTLERS,
            'storage' => self::STORAGE,
            'time' => self::TIME
        ];
    }
    
    /**
     * Get UI emojis as an associative array
     * @return array
     */
    public static function getUIEmojis() {
        return [
            'theme_dark' => self::THEME_DARK,
            'theme_light' => self::THEME_LIGHT,
            'user' => self::USER
        ];
    }
    
    /**
     * Get all building emojis as an associative array
     * @return array
     */
    public static function getBuildingEmojis() {
        return [
            'rathaus' => ['emoji' => self::TOWN_HALL, 'title' => 'Town Hall - Center of your settlement'],
            'holzfäller' => ['emoji' => self::LUMBERJACK, 'title' => 'Lumberjack - Produces wood'],
            'steinbruch' => ['emoji' => self::QUARRY, 'title' => 'Quarry - Produces stone'],
            'erzbergwerk' => ['emoji' => self::MINE, 'title' => 'Mine - Produces ore'],
            'lager' => ['emoji' => self::STORAGE, 'title' => 'Storage - Increases storage capacity'],
            'farm' => ['emoji' => self::FARM, 'title' => 'Farm - Provides settlers for construction']
        ];
    }
    
    /**
     * Get resource emojis as JavaScript object for frontend use
     * @return string JSON encoded emojis
     */
    public static function getResourceEmojisAsJS() {
        return json_encode(self::getResourceEmojis());
    }
    
    /**
     * Get UI emojis as JavaScript object for frontend use
     * @return string JSON encoded emojis
     */
    public static function getUIEmojisAsJS() {
        return json_encode(self::getUIEmojis());
    }
    
    /**
     * Get all emojis (resource + UI) as JavaScript object for frontend use
     * @return string JSON encoded emojis
     */
    public static function getAllEmojisAsJS() {
        return json_encode(array_merge(self::getResourceEmojis(), self::getUIEmojis()));
    }
}