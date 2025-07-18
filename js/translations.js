// Building name translations - centralized configuration
window.BuildingTranslations = {
    // German to English translations
    'Rathaus': 'Town Hall',
    'Holzfäller': 'Lumberjack',
    'Steinbruch': 'Quarry',
    'Erzbergwerk': 'Mine',
    'Lager': 'Storage',
    'Farm': 'Farm'
};

// Helper function to translate building names
window.translateBuildingName = function(germanName) {
    return window.BuildingTranslations[germanName] || germanName;
};

// Helper function to get all building types dynamically
window.getDefaultBuildingTypes = function() {
    return Object.keys(window.BuildingTranslations).map(germanName => ({
        buildingType: germanName,
        displayName: window.BuildingTranslations[germanName]
    }));
};