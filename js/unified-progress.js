/**
 * Unified Progress Bar System
 * 
 * This file combines and replaces js/client-progress.js and js/progress-bar.js
 * Provides a unified system for all progress bars across the application
 */

class UnifiedProgressManager {
    constructor() {
        // Building queue management
        this.activeBuildings = new Map(); // Map of building upgrades in progress
        this.buildingQueue = [];
        
        // Resource management
        this.resources = {
            wood: 0,
            stone: 0,
            ore: 0,
            storageCapacity: 0,
            freeSettlers: 0,
            maxSettlers: 0
        };
        
        this.regenerationRates = {
            wood: 0,
            stone: 0,
            ore: 0
        };
        
        // Timing and synchronization
        this.lastResourceUpdate = Date.now();
        this.lastServerSync = 0;
        this.updateInterval = null;
        this.apiBaseUrl = '../php/backend.php';
        
        // Configuration
        this.config = {
            serverSyncInterval: 120000, // Sync with server every 2 minutes
            progressUpdateInterval: 250, // Update progress every 250ms for smooth animation
            resourceUpdateInterval: 1000 // Update resources every second
        };
        
        // State flags
        this.needsBuildingDataRefresh = false;
        
        // Bind methods
        this.updateProgress = this.updateProgress.bind(this);
        this.updateResources = this.updateResources.bind(this);
        this.startProgressUpdates = this.startProgressUpdates.bind(this);
        this.stopProgressUpdates = this.stopProgressUpdates.bind(this);
    }

    /**
     * Initialize the progress manager with initial data from server
     */
    initialize(initialData) {
        console.log('Initializing Unified Progress Manager with data:', initialData);
        
        if (initialData.resources) {
            this.resources = { ...initialData.resources };
            this.lastResourceUpdate = Date.now();
        }
        
        if (initialData.regenerationRates) {
            this.regenerationRates = { ...initialData.regenerationRates };
        }
        
        if (initialData.buildingQueue) {
            this.buildingQueue = initialData.buildingQueue.map(item => ({
                ...item,
                startTime: new Date(item.startTime).getTime(),
                endTime: new Date(item.endTime).getTime(),
                completed: false
            }));
            
            // Also populate activeBuildings map for consistency
            this.buildingQueue.forEach((item, index) => {
                const buildingKey = `${item.settlementId || 'default'}_${item.buildingType}`;
                this.activeBuildings.set(buildingKey, {
                    ...item,
                    queueIndex: index
                });
            });
        } else {
            this.buildingQueue = [];
            this.activeBuildings.clear();
        }
        
        this.lastServerSync = Date.now();
        this.startProgressUpdates();
        
        // Update displays initially
        this.refreshFullQueue();
        this.updateResourceDisplay();
        this.updateRegenDisplay();
    }

    /**
     * Start tracking a building upgrade progress
     */
    async trackBuildingUpgrade(settlementId, buildingType) {
        console.log(`Starting to track ${buildingType} upgrade for settlement ${settlementId}`);
        
        try {
            const response = await fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getBuildingProgress=true&buildingType=${encodeURIComponent(buildingType)}`);
            const data = await response.json();
            
            if (data.success && data.progress) {
                const progressData = data.progress;
                
                // Calculate queue index
                const settlementBuildings = Array.from(this.activeBuildings.values())
                    .filter(building => building.settlementId === settlementId)
                    .sort((a, b) => a.startTime - b.startTime);
                
                const queueIndex = settlementBuildings.length;
                
                // Store the building progress data
                const buildingKey = `${settlementId}_${buildingType}`;
                const buildingData = {
                    settlementId: settlementId,
                    buildingType: buildingType,
                    startTime: new Date(progressData.startTime).getTime(),
                    endTime: new Date(progressData.endTime).getTime(),
                    level: progressData.level,
                    queueIndex: queueIndex,
                    completed: false
                };
                
                this.activeBuildings.set(buildingKey, buildingData);
                this.buildingQueue.push(buildingData);
                
                // Refresh display
                this.refreshFullQueue();
                this.startProgressUpdates();
                
                console.log(`Successfully started tracking ${buildingType} at queue position ${queueIndex}:`, progressData);
            } else {
                console.warn(`No progress data available for ${buildingType}:`, data);
            }
        } catch (error) {
            console.error(`Error fetching progress for ${buildingType}:`, error);
        }
    }

    /**
     * Fetch all building progress data for a settlement (for page load)
     */
    async fetchAllBuildingProgress(settlementId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getAllBuildingProgress=true`);
            const data = await response.json();
            
            if (data.success && data.buildings && data.buildings.length > 0) {
                // Clear existing tracking
                this.activeBuildings.clear();
                this.buildingQueue = [];
                
                // Sort buildings by queue index, then by start time
                const sortedBuildings = data.buildings.sort((a, b) => {
                    if (a.queueIndex !== b.queueIndex) {
                        return (a.queueIndex || 0) - (b.queueIndex || 0);
                    }
                    return new Date(a.startTime) - new Date(b.startTime);
                });
                
                // Track all active buildings
                sortedBuildings.forEach((buildingProgress, index) => {
                    const buildingData = {
                        settlementId: settlementId,
                        buildingType: buildingProgress.buildingType,
                        startTime: new Date(buildingProgress.startTime).getTime(),
                        endTime: new Date(buildingProgress.endTime).getTime(),
                        level: buildingProgress.level,
                        queueIndex: index,
                        completed: false
                    };
                    
                    const buildingKey = `${settlementId}_${buildingProgress.buildingType}`;
                    this.activeBuildings.set(buildingKey, buildingData);
                    this.buildingQueue.push(buildingData);
                });
                
                this.refreshFullQueue();
                
                if (this.activeBuildings.size > 0) {
                    this.startProgressUpdates();
                }
            } else {
                // No buildings in progress
                this.buildingQueue = [];
                this.activeBuildings.clear();
                const buildingQueueBody = document.getElementById('buildingQueueBody');
                if (buildingQueueBody) {
                    buildingQueueBody.innerHTML = '<tr><td colspan="4">No buildings in queue</td></tr>';
                }
            }
        } catch (error) {
            console.error('Error fetching all building progress:', error);
        }
    }

    /**
     * Start the progress update loop
     */
    startProgressUpdates() {
        if (this.updateInterval) return; // Already running

        this.updateInterval = setInterval(() => {
            this.updateProgress();
            this.updateResources();
        }, this.config.progressUpdateInterval);
        
        console.log('Started unified progress update loop');
    }

    /**
     * Stop the progress update loop
     */
    stopProgressUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
            console.log('Stopped unified progress update loop');
        }
    }

    /**
     * Update progress for all active buildings
     */
    updateProgress() {
        const now = Date.now();
        const completedBuildings = [];

        // Check if we need to sync with server
        if ((now - this.lastServerSync) > this.config.serverSyncInterval) {
            this.syncWithServer();
        }

        if (this.buildingQueue.length === 0) {
            this.stopProgressUpdates();
            return;
        }

        // Update the first (active) building in the queue
        if (this.buildingQueue.length > 0) {
            const activeBuilding = this.buildingQueue[0];
            
            if (!activeBuilding.startTime || !activeBuilding.endTime) {
                console.warn('Invalid building times detected:', activeBuilding);
                return;
            }
            
            const totalDuration = activeBuilding.endTime - activeBuilding.startTime;
            
            if (totalDuration <= 0) {
                console.warn('Invalid building duration:', totalDuration);
                activeBuilding.completed = true;
                completedBuildings.push(activeBuilding);
                this.onBuildingCompleted(activeBuilding);
            } else {
                const elapsed = now - activeBuilding.startTime;
                const completionPercentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
                
                if (!isNaN(completionPercentage) && completionPercentage >= 0) {
                    this.updateProgressBar(activeBuilding, completionPercentage, 0);
                    
                    // Check if building is completed
                    if (now >= activeBuilding.endTime && !activeBuilding.completed) {
                        activeBuilding.completed = true;
                        completedBuildings.push(activeBuilding);
                        this.onBuildingCompleted(activeBuilding);
                    }
                }
            }
        }

        // Update time displays for queued buildings
        this.buildingQueue.forEach((building, index) => {
            if (index > 0) {
                this.updateTimeDisplay(building, index);
                this.updateProgressBarToZero(index);
            }
        });

        // Process completed buildings only if there are any
        if (completedBuildings.length > 0) {
            // Remove completed buildings from activeBuildings map
            completedBuildings.forEach(completedBuilding => {
                const buildingKey = `${completedBuilding.settlementId}_${completedBuilding.buildingType}`;
                this.activeBuildings.delete(buildingKey);
                console.log(`Building completed and removed: ${buildingKey}`);
            });

            const initialLength = this.buildingQueue.length;
            // Filter out completed buildings
            this.buildingQueue = this.buildingQueue.filter(item => !item.completed);
            
            // Only refresh the queue display if buildings were actually removed
            if (this.buildingQueue.length !== initialLength) {
                console.log(`Queue length changed from ${initialLength} to ${this.buildingQueue.length}, refreshing display`);
                
                // Re-assign queue indices to maintain consistency
                this.buildingQueue.forEach((building, index) => {
                    building.queueIndex = index;
                });
                
                this.refreshFullQueue();
            }
        }

        // Stop if no more buildings
        if (this.buildingQueue.length === 0) {
            console.log('No more buildings in queue, stopping updates');
            this.stopProgressUpdates();
        }
    }

    /**
     * Update resources based on regeneration rates
     */
    updateResources() {
        const now = Date.now();
        const timeDiff = now - this.lastResourceUpdate;
        
        if (timeDiff >= this.config.resourceUpdateInterval) {
            const seconds = timeDiff / 1000;
            
            // Calculate resource increases
            const woodIncrease = (this.regenerationRates.wood / 3600) * seconds;
            const stoneIncrease = (this.regenerationRates.stone / 3600) * seconds;
            const oreIncrease = (this.regenerationRates.ore / 3600) * seconds;
            
            // Apply increases with storage capacity limits
            this.resources.wood = Math.min(this.resources.storageCapacity, this.resources.wood + woodIncrease);
            this.resources.stone = Math.min(this.resources.storageCapacity, this.resources.stone + stoneIncrease);
            this.resources.ore = Math.min(this.resources.storageCapacity, this.resources.ore + oreIncrease);
            
            this.updateResourceDisplay();
            this.lastResourceUpdate = now;
        }
    }

    /**
     * Update progress bar in the DOM
     */
    updateProgressBar(item, completionPercentage, queueIndex = null) {
        const queueRows = document.querySelectorAll('#buildingQueueBody tr');
        
        if (queueIndex !== null && queueRows[queueIndex]) {
            const row = queueRows[queueIndex];
            const progressBar = row.querySelector('.progress-bar');
            if (progressBar) {
                const currentWidth = parseFloat(progressBar.style.width) || 0;
                const widthDiff = Math.abs(completionPercentage - currentWidth);
                
                // Reduced threshold from 0.1% to 0.05% for smoother updates
                // Always update if it's the first time (currentWidth === 0) or completion changes significantly
                if (widthDiff >= 0.05 || completionPercentage >= 99.9 || completionPercentage === 0 || currentWidth === 0) {
                    progressBar.style.width = `${Math.min(100, completionPercentage)}%`;
                    // Smoother transition for active buildings, instant for queued
                    progressBar.style.transition = queueIndex === 0 ? 'width 0.2s ease-out' : 'none';
                }
            }
            
            // Update end time display
            const endTimeCell = row.querySelector('td:last-child');
            if (endTimeCell) {
                const remainingTime = Math.max(0, item.endTime - Date.now());
                const newTimeText = this.formatRemainingTime(remainingTime, queueIndex);
                
                if (endTimeCell.textContent !== newTimeText) {
                    endTimeCell.textContent = newTimeText;
                }
            }
        }
    }

    /**
     * Update only the time display for a queued building
     */
    updateTimeDisplay(item, queueIndex) {
        const queueRows = document.querySelectorAll('#buildingQueueBody tr');
        if (queueIndex < queueRows.length) {
            const row = queueRows[queueIndex];
            const endTimeCell = row.querySelector('td:last-child');
            if (endTimeCell) {
                const remainingTime = Math.max(0, item.endTime - Date.now());
                const newTimeText = this.formatRemainingTime(remainingTime, queueIndex);
                
                if (endTimeCell.textContent !== newTimeText) {
                    endTimeCell.textContent = newTimeText;
                }
            }
        }
    }

    /**
     * Ensure queued buildings have 0% progress
     */
    updateProgressBarToZero(queueIndex) {
        const queueRows = document.querySelectorAll('#buildingQueueBody tr');
        if (queueIndex < queueRows.length) {
            const row = queueRows[queueIndex];
            const progressBar = row.querySelector('.progress-bar');
            if (progressBar && progressBar.style.width !== '0%') {
                progressBar.style.width = '0%';
                progressBar.style.transition = 'none';
            }
        }
    }

    /**
     * Refresh the entire building queue display
     */
    refreshFullQueue() {
        const buildingQueueBody = document.getElementById('buildingQueueBody');
        if (!buildingQueueBody) {
            console.warn('Building queue body element not found, progress system may not be fully loaded');
            return;
        }

        buildingQueueBody.innerHTML = '';

        if (this.buildingQueue.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="4">No buildings in queue</td>';
            buildingQueueBody.appendChild(emptyRow);
            console.log('Building queue is empty, showing empty message');
            return;
        }

        // Sort buildings by queue index and start time
        this.buildingQueue.sort((a, b) => {
            if (a.queueIndex !== b.queueIndex) {
                return a.queueIndex - b.queueIndex;
            }
            return a.startTime - b.startTime;
        });

        // Re-assign correct queue indices
        this.buildingQueue.forEach((building, index) => {
            building.queueIndex = index;
        });

        console.log(`Refreshing queue display with ${this.buildingQueue.length} buildings`);

        // Create rows for each building
        this.buildingQueue.forEach((building, queueIndex) => {
            const row = document.createElement('tr');
            
            const now = Date.now();
            const startTime = building.startTime;
            const endTime = building.endTime;
            const totalDuration = endTime - startTime;
            const elapsed = now - startTime;
            const initialProgress = queueIndex === 0 ? Math.max(0, Math.min(100, (elapsed / totalDuration) * 100)) : 0;

            // Calculate wait time
            let waitTime = 0;
            if (queueIndex > 0) {
                for (let i = 0; i < queueIndex; i++) {
                    const prevBuilding = this.buildingQueue[i];
                    waitTime += Math.max(0, prevBuilding.endTime - now);
                }
                waitTime += (endTime - startTime);
            } else {
                waitTime = Math.max(0, endTime - now);
            }

            const translatedName = this.translateBuildingName(building.buildingType);
            const queueStatus = queueIndex === 0 ? '' : ' (queued)';
            
            row.innerHTML = `
                <td class="${queueIndex === 0 ? 'active-building' : 'queued-building'}">${translatedName}${queueStatus}</td>
                <td>${building.level}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar ${queueIndex === 0 ? 'active-building' : 'queued-building'}" 
                             style="width: ${initialProgress}%; transition: ${queueIndex === 0 ? 'width 0.2s ease-out' : 'none'};">
                        </div>
                    </div>
                </td>
                <td class="time-display">${this.formatRemainingTime(waitTime, queueIndex)}</td>
            `;

            row.dataset.buildingType = building.buildingType;
            row.dataset.settlementId = building.settlementId;
            row.dataset.queueIndex = queueIndex;

            buildingQueueBody.appendChild(row);
        });
        
        console.log('Queue display refreshed successfully');
    }

    /**
     * Update resource display in DOM
     */
    updateResourceDisplay() {
        const formatNumber = (num) => Math.floor(num).toLocaleString('en-US');
        
        const elements = {
            wood: document.getElementById('holz'),
            stone: document.getElementById('stein'),
            ore: document.getElementById('erz'),
            settlers: document.getElementById('settlers'),
            maxSettlers: document.getElementById('maxSettlers'),
            storage: document.getElementById('lagerKapazität')
        };
        
        if (elements.wood) elements.wood.textContent = formatNumber(this.resources.wood);
        if (elements.stone) elements.stone.textContent = formatNumber(this.resources.stone);
        if (elements.ore) elements.ore.textContent = formatNumber(this.resources.ore);
        if (elements.settlers) elements.settlers.textContent = formatNumber(this.resources.freeSettlers);
        if (elements.maxSettlers) elements.maxSettlers.textContent = formatNumber(this.resources.maxSettlers);
        if (elements.storage) elements.storage.textContent = formatNumber(this.resources.storageCapacity);
        
        // Update cost colors based on current resources
        if (window.updateCostColors) {
            window.updateCostColors(this.resources);
        }
    }

    /**
     * Update regeneration display
     */
    updateRegenDisplay() {
        const formatNumber = (num) => Math.floor(num).toLocaleString('en-US');
        
        const elements = {
            woodRegen: document.getElementById('holzRegen'),
            stoneRegen: document.getElementById('steinRegen'),
            oreRegen: document.getElementById('erzRegen')
        };
        
        if (elements.woodRegen) elements.woodRegen.textContent = formatNumber(this.regenerationRates.wood);
        if (elements.stoneRegen) elements.stoneRegen.textContent = formatNumber(this.regenerationRates.stone);
        if (elements.oreRegen) elements.oreRegen.textContent = formatNumber(this.regenerationRates.ore);
    }

    /**
     * Handle building completion
     */
    onBuildingCompleted(building) {
        console.log(`Building completed: ${building.buildingType} Level ${building.level}`);
        this.needsBuildingDataRefresh = true;
    }

    /**
     * Sync with server for fresh data
     */
    async syncWithServer() {
        this.lastServerSync = Date.now();
        
        try {
            const settlementId = new URLSearchParams(window.location.search).get('settlementId') || window.settlementId;
            
            if (!settlementId) {
                console.warn('No settlement ID found for sync');
                return;
            }
            
            console.log('Syncing with server for settlement:', settlementId);
            
            const [resourcesResponse, queueResponse, regenResponse] = await Promise.all([
                fetch(`${this.apiBaseUrl}?settlementId=${settlementId}`),
                fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getBuildingQueue=true`),
                fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getRegen=true`)
            ]);
            
            const [resourcesData, queueData, regenData] = await Promise.all([
                resourcesResponse.json(),
                queueResponse.json(),
                regenResponse.json()
            ]);
            
            // Update resources
            if (resourcesData.resources) {
                this.resources = { ...resourcesData.resources.resources };
                this.updateResourceDisplay();
            }
            
            // Update building queue
            if (queueData.info && queueData.info.queue) {
                console.log('Updating building queue with', queueData.info.queue.length, 'items');
                this.buildingQueue = queueData.info.queue.map(item => ({
                    ...item,
                    startTime: new Date(item.startTime).getTime(),
                    endTime: new Date(item.endTime).getTime(),
                    completed: false
                }));
                
                // Update activeBuildings map
                this.activeBuildings.clear();
                this.buildingQueue.forEach((item, index) => {
                    const buildingKey = `${settlementId}_${item.buildingType}`;
                    this.activeBuildings.set(buildingKey, {
                        ...item,
                        queueIndex: index
                    });
                });
                
                this.refreshFullQueue();
            } else {
                this.buildingQueue = [];
                this.activeBuildings.clear();
                this.refreshFullQueue();
            }
            
            // Update regeneration rates
            if (regenData.regen) {
                this.regenerationRates = { ...regenData.regen.regens };
                this.updateRegenDisplay();
            }
            
            // Refresh building data if needed
            if (this.needsBuildingDataRefresh && window.fetchBuildings) {
                window.fetchBuildings(settlementId);
                this.needsBuildingDataRefresh = false;
            }
            
        } catch (error) {
            console.error('Error syncing with server:', error);
        }
    }

    /**
     * Format remaining time for display
     */
    formatRemainingTime(milliseconds, queueIndex = 0) {
        const COMPLETION_BUFFER = 1000; // 1 second buffer
        
        if (milliseconds <= COMPLETION_BUFFER) {
            return queueIndex === 0 ? 'Completing...' : 'Queued';
        }

        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `${hours}h ${minutes % 60}m ${seconds % 60}s${queueIndex > 0 ? ' (queued)' : ''}`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s${queueIndex > 0 ? ' (queued)' : ''}`;
        } else {
            return `${seconds}s${queueIndex > 0 ? ' (queued)' : ''}`;
        }
    }

    /**
     * Translate building names from German to English
     */
    translateBuildingName(germanName) {
        const translations = {
            'Rathaus': 'Town Hall',
            'Holzfäller': 'Lumberjack',
            'Steinbruch': 'Quarry',
            'Erzbergwerk': 'Mine',
            'Lager': 'Storage',
            'Farm': 'Farm',
            'Markt': 'Market',
            'Kaserne': 'Barracks'
        };
        return translations[germanName] || germanName;
    }

    /**
     * Force sync with server (called when user takes action)
     */
    forceSyncWithServer() {
        console.log('Forcing sync with server...');
        this.lastServerSync = 0;
        this.syncWithServer();
    }

    /**
     * Stop all progress tracking and cleanup
     */
    stop() {
        this.stopProgressUpdates();
        this.buildingQueue = [];
        this.activeBuildings.clear();
    }
}

// Create global instance
window.unifiedProgressManager = new UnifiedProgressManager();

// Maintain backwards compatibility
window.clientProgressManager = window.unifiedProgressManager;
window.buildingProgressManager = window.unifiedProgressManager;

// Add a ready check function
window.checkProgressSystemReady = function() {
    const elements = {
        buildingQueueBody: document.getElementById('buildingQueueBody'),
        resourceElements: {
            wood: document.getElementById('holz'),
            stone: document.getElementById('stein'),
            ore: document.getElementById('erz')
        }
    };
    
    const missing = [];
    if (!elements.buildingQueueBody) missing.push('buildingQueueBody');
    if (!elements.resourceElements.wood) missing.push('holz resource display');
    if (!elements.resourceElements.stone) missing.push('stein resource display');
    if (!elements.resourceElements.ore) missing.push('erz resource display');
    
    if (missing.length > 0) {
        console.warn('Progress system elements missing:', missing);
        return false;
    }
    
    console.log('Progress system is ready - all required elements found');
    return true;
};

// Automatic initialization when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking progress system readiness...');
    
    // Small delay to allow other scripts to initialize
    setTimeout(() => {
        if (window.checkProgressSystemReady()) {
            console.log('Progress system ready for initialization');
        } else {
            console.warn('Some progress system elements are missing, functionality may be limited');
        }
    }, 100);
});

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UnifiedProgressManager;
}