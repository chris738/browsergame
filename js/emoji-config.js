// Centralized emoji configuration for the browser game
// This file contains all emoji definitions used throughout the application

const EMOJI_CONFIG = {
    // Resource emojis
    resources: {
        wood: {
            emoji: '🪵',
            title: 'Wood - Used for construction and upgrades'
        },
        stone: {
            emoji: '🧱', 
            title: 'Stone - Used for advanced buildings'
        },
        ore: {
            emoji: '🪨',
            title: 'Ore - Used for high-level buildings'
        },
        gold: {
            emoji: '💰',
            title: 'Gold - Universal currency for trading'
        },
        storage: {
            emoji: '🏪',
            title: 'Storage Capacity - Maximum resources you can store'
        },
        settlers: {
            emoji: '👥',
            title: 'Settlers - Population available for construction'
        }
    },
    
    // Building emojis
    buildings: {
        rathaus: {
            emoji: '🏛️',
            title: 'Town Hall - Center of your settlement'
        },
        holzfäller: {
            emoji: '🌲',
            title: 'Lumberjack - Produces wood'
        },
        steinbruch: {
            emoji: '🏔️',
            title: 'Quarry - Produces stone'
        },
        erzbergwerk: {
            emoji: '⛏️',
            title: 'Mine - Produces ore'
        },
        lager: {
            emoji: '🏪',
            title: 'Storage - Increases storage capacity'
        },
        farm: {
            emoji: '🚜',
            title: 'Farm - Provides settlers for construction'
        },
        markt: {
            emoji: '⚖️',
            title: 'Market - Enables trading with other players'
        },
        kaserne: {
            emoji: '⚔️',
            title: 'Barracks - Trains military units and provides defense'
        }
    },
    
    // UI and interface emojis
    ui: {
        player: '👤',
        time: '⏱️',
        refresh: '🔄',
        moon: '🌙',
        sun: '☀️',
        arrow_right: '→',
        arrow_bidirectional: '↔',
        market: '⚖️'
    }
};

// Helper functions to get emoji strings easily
function getResourceEmoji(resourceType) {
    return EMOJI_CONFIG.resources[resourceType]?.emoji || '❓';
}

function getBuildingEmoji(buildingType) {
    return EMOJI_CONFIG.buildings[buildingType]?.emoji || '🏗️';
}

function getResourceTitle(resourceType) {
    return EMOJI_CONFIG.resources[resourceType]?.title || resourceType;
}

function getBuildingTitle(buildingType) {
    return EMOJI_CONFIG.buildings[buildingType]?.title || buildingType;
}

function getUIEmoji(uiType) {
    return EMOJI_CONFIG.ui[uiType] || '❓';
}

// Format resource with emoji for display
function formatResourceWithEmoji(resourceType, amount, showRegen = false, regenAmount = 0) {
    const emoji = getResourceEmoji(resourceType);
    const title = getResourceTitle(resourceType);
    let formatted = `<span class="resource-emoji" title="${title}">${emoji}</span> ${amount}`;
    
    if (showRegen && regenAmount !== undefined) {
        formatted += `<span class="regen">(+${regenAmount}/h)</span>`;
    }
    
    return formatted;
}

// Format building with emoji for display
function formatBuildingWithEmoji(buildingType, name) {
    const emoji = getBuildingEmoji(buildingType);
    const title = getBuildingTitle(buildingType);
    return `<span class="building-emoji" title="${title}">${emoji}</span> ${name}`;
}

// Export for Node.js environments (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        EMOJI_CONFIG,
        getResourceEmoji,
        getBuildingEmoji,
        getResourceTitle,
        getBuildingTitle,
        getUIEmoji,
        formatResourceWithEmoji,
        formatBuildingWithEmoji
    };
}