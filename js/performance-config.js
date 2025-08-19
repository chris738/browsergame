/**
 * Performance Configuration
 * 
 * Centralized configuration for all performance-related settings
 * to improve website responsiveness and reduce server load
 */

window.PerformanceConfig = {
    // Network request intervals (in milliseconds)
    polling: {
        // When modern progress systems are available
        modernSystem: {
            playerInfo: 120000,    // 2 minutes
            buildings: 300000,     // 5 minutes
            resources: 0,          // Handled by client-side system
            queue: 0               // Handled by client-side system
        },
        
        // Fallback for older systems
        fallbackSystem: {
            playerInfo: 15000,     // 15 seconds (reduced from 5s)
            buildings: 15000,      // 15 seconds (reduced from 5s) 
            resources: 3000,       // 3 seconds (reduced from 1s)
            queue: 2000,           // 2 seconds (reduced from 1s)
            regen: 10000           // 10 seconds (reduced from 5s)
        }
    },
    
    // Progress bar update frequencies
    progressBars: {
        updateInterval: 300,       // 300ms (reduced from 250ms)
        serverSyncInterval: 150000, // 2.5 minutes (increased from 2 minutes)
        
        // Thresholds for updating progress bars (to reduce DOM manipulation)
        updateThresholds: {
            building: 0.1,         // Update building progress every 0.1%
            military: 0.2,         // Update military progress every 0.2%
            unified: 0.1           // Update unified progress every 0.1%
        }
    },
    
    // DOM caching and optimization
    dom: {
        enableCaching: true,       // Cache DOM element references
        cacheInvalidationDelay: 1000, // Wait 1s before invalidating caches after changes
        
        // Query optimization
        useDeferredQueries: true,  // Defer non-critical DOM queries
        batchDOMUpdates: true      // Batch multiple DOM updates together
    },
    
    // Animation and CSS performance
    animations: {
        progressAnimationDuration: 1.5, // Progress bar stripe animation (seconds)
        transitionDuration: 0.2,        // Standard transition duration (seconds)
        useGPUAcceleration: true,       // Enable GPU acceleration for animations
        
        // Reduce motion for performance on slower devices
        reducedMotionFallback: {
            disableStripeAnimation: true,
            fasterTransitions: 0.1
        }
    },
    
    // Resource management
    resources: {
        updateInterval: 1000,      // Update resources every second
        enableClientSideCalculation: true, // Use client-side resource calculation
        
        // Batching updates to reduce redraws
        batchResourceUpdates: true,
        resourceUpdateDelay: 100   // Batch updates over 100ms
    },
    
    // Performance monitoring
    monitoring: {
        enabled: false,            // Disable by default (can be enabled for debugging)
        logSlowOperations: true,   // Log operations that take longer than threshold
        slowOperationThreshold: 100, // 100ms threshold
        
        // Memory usage monitoring
        trackMemoryUsage: false,
        memoryCheckInterval: 30000 // Check memory every 30 seconds
    }
};

// Performance helper functions
window.PerformanceHelpers = {
    /**
     * Debounce function to limit how often a function can be called
     */
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Throttle function to limit function calls to once per specified time
     */
    throttle: function(func, limit) {
        let lastFunc;
        let lastRan;
        return function(...args) {
            if (!lastRan) {
                func(...args);
                lastRan = Date.now();
            } else {
                clearTimeout(lastFunc);
                lastFunc = setTimeout(() => {
                    if ((Date.now() - lastRan) >= limit) {
                        func(...args);
                        lastRan = Date.now();
                    }
                }, limit - (Date.now() - lastRan));
            }
        };
    },
    
    /**
     * Batch DOM updates to reduce reflows and repaints
     */
    batchDOMUpdates: function(updates) {
        if (!window.PerformanceConfig.dom.batchDOMUpdates) {
            updates.forEach(update => update());
            return;
        }
        
        // Use requestAnimationFrame to batch updates
        requestAnimationFrame(() => {
            updates.forEach(update => update());
        });
    },
    
    /**
     * Performance measurement wrapper
     */
    measurePerformance: function(name, func) {
        if (!window.PerformanceConfig.monitoring.enabled) {
            return func();
        }
        
        const start = performance.now();
        const result = func();
        const end = performance.now();
        const duration = end - start;
        
        if (duration > window.PerformanceConfig.monitoring.slowOperationThreshold) {
            console.warn(`Slow operation detected: ${name} took ${duration.toFixed(2)}ms`);
        }
        
        return result;
    },
    
    /**
     * Check if reduced motion is preferred
     */
    prefersReducedMotion: function() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    },
    
    /**
     * Apply performance optimizations based on device capabilities
     */
    optimizeForDevice: function() {
        // Check for reduced motion preference
        if (this.prefersReducedMotion()) {
            const config = window.PerformanceConfig.animations.reducedMotionFallback;
            document.documentElement.style.setProperty('--progress-animation-duration', 'none');
            document.documentElement.style.setProperty('--transition-duration', config.fasterTransitions + 's');
        }
        
        // Check for low-end device indicators
        if (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2) {
            // Reduce update frequencies for low-end devices
            window.PerformanceConfig.progressBars.updateInterval = 500;
            window.PerformanceConfig.progressBars.updateThresholds.building = 0.2;
            window.PerformanceConfig.progressBars.updateThresholds.military = 0.5;
        }
    }
};

// Auto-initialize performance optimizations when the script loads
document.addEventListener('DOMContentLoaded', function() {
    window.PerformanceHelpers.optimizeForDevice();
    
    if (window.PerformanceConfig.monitoring.enabled) {
        console.log('Performance monitoring enabled');
        console.log('Performance config:', window.PerformanceConfig);
    }
});

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PerformanceConfig: window.PerformanceConfig, PerformanceHelpers: window.PerformanceHelpers };
}