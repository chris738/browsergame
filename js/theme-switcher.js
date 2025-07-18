// Theme switcher functionality
(function() {
    'use strict';
    
    // Get theme from localStorage or default to light
    function getTheme() {
        return localStorage.getItem('theme') || 'light';
    }
    
    // Set theme and save to localStorage
    function setTheme(theme) {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        updateToggleButton(theme);
    }
    
    // Update the toggle button text and icon
    function updateToggleButton(theme) {
        const toggleButton = document.getElementById('theme-toggle');
        if (toggleButton) {
            // Use centralized emojis if available, fallback to hardcoded ones
            const darkEmoji = (typeof emojis !== 'undefined' && emojis.theme_dark) ? emojis.theme_dark : '🌙';
            const lightEmoji = (typeof emojis !== 'undefined' && emojis.theme_light) ? emojis.theme_light : '☀️';
            
            if (theme === 'dark') {
                toggleButton.innerHTML = `${lightEmoji} Light`;
                toggleButton.setAttribute('aria-label', 'Switch to light mode');
            } else {
                toggleButton.innerHTML = `${darkEmoji} Dark`;
                toggleButton.setAttribute('aria-label', 'Switch to dark mode');
            }
        }
    }
    
    // Toggle between light and dark themes
    function toggleTheme() {
        const currentTheme = getTheme();
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
    }
    
    // Initialize theme on page load
    function initTheme() {
        const theme = getTheme();
        setTheme(theme);
        
        // Add event listener to toggle button
        const toggleButton = document.getElementById('theme-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', toggleTheme);
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }
    
    // Make toggleTheme available globally for inline onclick handlers if needed
    window.toggleTheme = toggleTheme;
})();