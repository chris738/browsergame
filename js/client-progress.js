/**
 * Client-side Progress Calculation System
 * Optimizes game performance by reducing server requests and providing smooth animations
 */

class ClientProgressManager {
    constructor() {
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
        
        this.buildingQueue = [];
        this.lastResourceUpdate = Date.now();
        this.lastServerSync = 0;
        this.animationFrameId = null;
        
        // Configuration
        this.config = {
            serverSyncInterval: 60000, // Sync with server every 60 seconds
            progressUpdateInterval: 100, // Update progress every 100ms for smooth animation
            resourceUpdateInterval: 1000 // Update resources every second
        };
        
        // Bind methods
        this.updateProgress = this.updateProgress.bind(this);
        this.updateResources = this.updateResources.bind(this);
    }
    
    /**
     * Initialize the progress manager with initial data from server
     */
    initialize(initialData) {
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
        } else {
            this.buildingQueue = [];
        }
        
        this.lastServerSync = Date.now();
        this.startProgressLoop();
        
        // Update displays initially
        this.updateQueueDisplay();
        this.updateResourceDisplay();
        this.updateRegenDisplay();
    }
    
    /**
     * Start the main progress update loop
     */
    startProgressLoop() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }
        
        const loop = () => {
            this.updateProgress();
            this.updateResources();
            this.animationFrameId = requestAnimationFrame(loop);
        };
        
        loop();
    }
    
    /**
     * Stop the progress loop
     */
    stop() {
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
            this.animationFrameId = null;
        }
    }
    
    /**
     * Update building progress calculations
     */
    updateProgress() {
        const now = Date.now();
        let shouldSyncWithServer = false;
        
        this.buildingQueue.forEach((item, index) => {
            const totalDuration = item.endTime - item.startTime;
            const elapsed = now - item.startTime;
            const completionPercentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
            
            // Update progress bar
            this.updateProgressBar(item, completionPercentage);
            
            // Check if building is completed
            if (now >= item.endTime && !item.completed) {
                item.completed = true;
                shouldSyncWithServer = true;
                this.onBuildingCompleted(item);
            }
        });
        
        // Remove completed buildings from queue
        this.buildingQueue = this.buildingQueue.filter(item => !item.completed);
        
        // Check if we need to sync with server
        if (shouldSyncWithServer || (now - this.lastServerSync) > this.config.serverSyncInterval) {
            this.syncWithServer();
        }
    }
    
    /**
     * Update progress bar in the DOM
     */
    updateProgressBar(item, completionPercentage) {
        const queueRows = document.querySelectorAll('#buildingQueueBody tr');
        queueRows.forEach(row => {
            const buildingCell = row.querySelector('td:first-child');
            if (buildingCell && buildingCell.textContent.includes(this.translateBuildingName(item.buildingType))) {
                const progressBar = row.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${completionPercentage}%`;
                    progressBar.style.transition = 'width 0.1s ease-out';
                }
                
                // Update end time display
                const endTimeCell = row.querySelector('td:last-child');
                if (endTimeCell) {
                    const remainingTime = Math.max(0, item.endTime - Date.now());
                    endTimeCell.textContent = this.formatRemainingTime(remainingTime);
                }
            }
        });
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
            const woodIncrease = (this.regenerationRates.wood / 3600) * seconds; // per hour to per second
            const stoneIncrease = (this.regenerationRates.stone / 3600) * seconds;
            const oreIncrease = (this.regenerationRates.ore / 3600) * seconds;
            
            // Apply increases with storage capacity limits
            this.resources.wood = Math.min(this.resources.storageCapacity, this.resources.wood + woodIncrease);
            this.resources.stone = Math.min(this.resources.storageCapacity, this.resources.stone + stoneIncrease);
            this.resources.ore = Math.min(this.resources.storageCapacity, this.resources.ore + oreIncrease);
            
            // Update DOM
            this.updateResourceDisplay();
            
            this.lastResourceUpdate = now;
        }
    }
    
    /**
     * Update resource display in DOM
     */
    updateResourceDisplay() {
        const formatNumber = (num) => Math.floor(num).toLocaleString('en-US');
        
        const woodElement = document.getElementById('holz');
        const stoneElement = document.getElementById('stein');
        const oreElement = document.getElementById('erz');
        const settlersElement = document.getElementById('settlers');
        const maxSettlersElement = document.getElementById('maxSettlers');
        const storageElement = document.getElementById('lagerKapazität');
        
        if (woodElement) woodElement.textContent = formatNumber(this.resources.wood);
        if (stoneElement) stoneElement.textContent = formatNumber(this.resources.stone);
        if (oreElement) oreElement.textContent = formatNumber(this.resources.ore);
        if (settlersElement) settlersElement.textContent = formatNumber(this.resources.freeSettlers);
        if (maxSettlersElement) maxSettlersElement.textContent = formatNumber(this.resources.maxSettlers);
        if (storageElement) storageElement.textContent = formatNumber(this.resources.storageCapacity);
        
        // Update cost colors based on current resources
        if (window.updateCostColors) {
            window.updateCostColors(this.resources);
        }
    }
    
    /**
     * Handle building completion
     */
    onBuildingCompleted(building) {
        console.log(`Building completed: ${building.buildingType} Level ${building.level}`);
        
        // Show completion notification
        this.showBuildingCompletionNotification(building);
        
        // Mark that we need fresh building data
        this.needsBuildingDataRefresh = true;
    }
    
    /**
     * Show building completion notification
     */
    showBuildingCompletionNotification(building) {
        const notification = document.createElement('div');
        notification.className = 'building-completion-notification';
        notification.innerHTML = `
            <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 10px; margin: 10px 0; border-radius: 4px; position: fixed; top: 20px; right: 20px; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                ✅ <strong>${this.translateBuildingName(building.buildingType)} Level ${building.level}</strong> completed!
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove notification after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
    
    /**
     * Sync with server for fresh data
     */
    async syncWithServer() {
        this.lastServerSync = Date.now();
        
        try {
            // Get current settlement ID from URL or global variable
            const settlementId = new URLSearchParams(window.location.search).get('settlementId') || window.settlementId;
            
            if (!settlementId) {
                console.warn('No settlement ID found for sync');
                return;
            }
            
            console.log('Syncing with server for settlement:', settlementId);
            
            // Fetch fresh data from server
            const [resourcesResponse, queueResponse, regenResponse] = await Promise.all([
                fetch(`../php/backend.php?settlementId=${settlementId}`),
                fetch(`../php/backend.php?settlementId=${settlementId}&getBuildingQueue=true`),
                fetch(`../php/backend.php?settlementId=${settlementId}&getRegen=true`)
            ]);
            
            const [resourcesData, queueData, regenData] = await Promise.all([
                resourcesResponse.json(),
                queueResponse.json(),
                regenResponse.json()
            ]);
            
            console.log('Sync data received:', { resourcesData, queueData, regenData });
            
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
                this.updateQueueDisplay();
            } else {
                console.log('No building queue items found, clearing queue');
                this.buildingQueue = [];
                this.updateQueueDisplay();
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
     * Update queue display in DOM
     */
    updateQueueDisplay() {
        const buildingQueueBody = document.getElementById('buildingQueueBody');
        if (!buildingQueueBody) return;
        
        buildingQueueBody.innerHTML = '';
        
        if (this.buildingQueue.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="4">No buildings in queue</td>';
            buildingQueueBody.appendChild(emptyRow);
            return;
        }
        
        this.buildingQueue.forEach(item => {
            const row = document.createElement('tr');
            const translatedBuildingName = this.translateBuildingName(item.buildingType);
            const remainingTime = Math.max(0, item.endTime - Date.now());
            
            row.innerHTML = `
                <td>${translatedBuildingName}</td>
                <td>${item.level}</td>
                <td>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 0%; transition: width 0.1s ease-out;"></div>
                    </div>
                </td>
                <td>${this.formatRemainingTime(remainingTime)}</td>
            `;
            
            buildingQueueBody.appendChild(row);
        });
    }
    
    /**
     * Update regeneration display
     */
    updateRegenDisplay() {
        const formatNumber = (num) => Math.floor(num).toLocaleString('en-US');
        
        const woodRegenElement = document.getElementById('holzRegen');
        const stoneRegenElement = document.getElementById('steinRegen');
        const oreRegenElement = document.getElementById('erzRegen');
        
        if (woodRegenElement) woodRegenElement.textContent = formatNumber(this.regenerationRates.wood);
        if (stoneRegenElement) stoneRegenElement.textContent = formatNumber(this.regenerationRates.stone);
        if (oreRegenElement) oreRegenElement.textContent = formatNumber(this.regenerationRates.ore);
    }
    
    /**
     * Format remaining time for display
     */
    formatRemainingTime(milliseconds) {
        if (milliseconds <= 0) return 'Complete';
        
        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        
        if (hours > 0) {
            return `${hours}h ${minutes % 60}m ${seconds % 60}s`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s`;
        } else {
            return `${seconds}s`;
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
        this.lastServerSync = 0; // Force sync on next update
        this.syncWithServer(); // Sync immediately
    }
}

// Global instance
window.clientProgressManager = new ClientProgressManager();