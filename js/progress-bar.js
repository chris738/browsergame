/**
 * New Progress Bar Implementation
 * 
 * This is a complete rewrite of the progress bar logic as requested.
 * When a building is upgraded, it fetches the end time via API call and creates a smooth progress bar.
 */

class BuildingProgressManager {
    constructor() {
        this.activeBuildings = new Map(); // Map of building upgrades in progress
        this.updateInterval = null;
        this.apiBaseUrl = '../php/backend.php';
        
        // Bind methods
        this.updateProgress = this.updateProgress.bind(this);
        this.startProgressUpdates = this.startProgressUpdates.bind(this);
        this.stopProgressUpdates = this.stopProgressUpdates.bind(this);
    }

    /**
     * Start tracking a building upgrade progress
     * @param {string} settlementId - The settlement ID
     * @param {string} buildingType - The building type that was upgraded
     */
    async trackBuildingUpgrade(settlementId, buildingType) {
        console.log(`Starting to track ${buildingType} upgrade for settlement ${settlementId}`);
        
        try {
            // Fetch the end time via API call as requested
            const response = await fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getBuildingProgress=true&buildingType=${encodeURIComponent(buildingType)}`);
            const data = await response.json();
            
            if (data.success && data.progress) {
                const progressData = data.progress;
                
                // Calculate proper queue index based on existing buildings for this settlement
                const settlementBuildings = Array.from(this.activeBuildings.values())
                    .filter(building => building.settlementId === settlementId)
                    .sort((a, b) => a.startTime - b.startTime); // Sort by start time
                
                const queueIndex = settlementBuildings.length; // Next position in queue
                
                // Store the building progress data with correct queue index
                const buildingKey = `${settlementId}_${buildingType}`;
                this.activeBuildings.set(buildingKey, {
                    settlementId: settlementId,
                    buildingType: buildingType,
                    startTime: new Date(progressData.startTime).getTime(),
                    endTime: new Date(progressData.endTime).getTime(),
                    level: progressData.level,
                    queueIndex: queueIndex,
                    completed: false
                });
                
                // Refresh the entire queue display to ensure proper ordering
                this.refreshFullQueue();
                
                // Start the progress update loop if not already running
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
     * Add or update progress bar in the building queue table
     */
    addProgressBarToQueue(progressData) {
        const buildingQueueBody = document.getElementById('buildingQueueBody');
        if (!buildingQueueBody) return;

        // Clear "No buildings in queue" message if present
        const emptyRow = buildingQueueBody.querySelector('tr td[colspan="4"]');
        if (emptyRow) {
            emptyRow.parentElement.remove();
        }

        // Re-render the entire queue to ensure proper ordering and queue indices
        this.refreshFullQueue();
    }

    /**
     * Refresh the entire building queue display
     */
    refreshFullQueue() {
        const buildingQueueBody = document.getElementById('buildingQueueBody');
        if (!buildingQueueBody) return;

        // Clear existing content
        buildingQueueBody.innerHTML = '';

        if (this.activeBuildings.size === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td colspan="4">No buildings in queue</td>';
            buildingQueueBody.appendChild(emptyRow);
            return;
        }

        // Sort buildings by queue index (start time as secondary sort)
        const sortedBuildings = Array.from(this.activeBuildings.values()).sort((a, b) => {
            if (a.queueIndex !== b.queueIndex) {
                return a.queueIndex - b.queueIndex;
            }
            return a.startTime - b.startTime;
        });

        // Re-assign correct queue indices
        sortedBuildings.forEach((building, index) => {
            building.queueIndex = index;
            const buildingKey = `${building.settlementId}_${building.buildingType}`;
            this.activeBuildings.set(buildingKey, building);
        });

        // Create rows for each building
        sortedBuildings.forEach((building, queueIndex) => {
            const row = document.createElement('tr');
            
            // Calculate progress and time
            const now = Date.now();
            const startTime = building.startTime;
            const endTime = building.endTime;
            const totalDuration = endTime - startTime;
            const elapsed = now - startTime;
            const initialProgress = queueIndex === 0 ? Math.max(0, Math.min(100, (elapsed / totalDuration) * 100)) : 0;

            // Calculate wait time for queued buildings
            let waitTime = 0;
            if (queueIndex > 0) {
                // Add remaining time from all previous buildings
                for (let i = 0; i < queueIndex; i++) {
                    const prevBuilding = sortedBuildings[i];
                    waitTime += Math.max(0, prevBuilding.endTime - now);
                }
                // Add this building's build time
                waitTime += (endTime - startTime);
            } else {
                // Active building - just show remaining time
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
                             style="width: ${initialProgress}%; transition: ${queueIndex === 0 ? 'width 0.3s ease-out' : 'none'};">
                        </div>
                    </div>
                </td>
                <td class="time-display">${this.formatRemainingTime(waitTime, queueIndex)}</td>
            `;

            // Store reference to this row for updates
            row.dataset.buildingType = building.buildingType;
            row.dataset.settlementId = building.settlementId;
            row.dataset.queueIndex = queueIndex;

            buildingQueueBody.appendChild(row);
        });
    }

    /**
     * Start the progress update loop
     */
    startProgressUpdates() {
        if (this.updateInterval) return; // Already running

        this.updateInterval = setInterval(this.updateProgress, 1000); // Update every second for smooth progress
        console.log('Started progress update loop');
    }

    /**
     * Stop the progress update loop
     */
    stopProgressUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
            console.log('Stopped progress update loop');
        }
    }

    /**
     * Update progress for all active buildings
     */
    updateProgress() {
        const now = Date.now();
        const completedBuildings = [];

        if (this.activeBuildings.size === 0) {
            this.stopProgressUpdates();
            return;
        }

        // Get sorted buildings (first one is active)
        const sortedBuildings = Array.from(this.activeBuildings.values()).sort((a, b) => {
            if (a.queueIndex !== b.queueIndex) {
                return a.queueIndex - b.queueIndex;
            }
            return a.startTime - b.startTime;
        });

        // Update only the first (active) building's progress bar
        if (sortedBuildings.length > 0) {
            const activeBuilding = sortedBuildings[0];
            const buildingKey = `${activeBuilding.settlementId}_${activeBuilding.buildingType}`;
            
            // Validate building times
            if (!activeBuilding.startTime || !activeBuilding.endTime) {
                console.warn('Invalid building times detected:', activeBuilding);
                return;
            }
            
            const totalDuration = activeBuilding.endTime - activeBuilding.startTime;
            
            // Handle edge case where duration is 0 or negative
            if (totalDuration <= 0) {
                console.warn('Invalid building duration:', totalDuration);
                activeBuilding.completed = true;
                completedBuildings.push(buildingKey);
                this.onBuildingCompleted(activeBuilding);
            } else {
                const elapsed = now - activeBuilding.startTime;
                const completionPercentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
                
                // Ensure valid progress percentage
                if (!isNaN(completionPercentage) && completionPercentage >= 0) {
                    // Find the active building row (should be first row with queueIndex 0)
                    const activeRow = document.querySelector(`tr[data-queue-index="0"]`);
                    if (activeRow) {
                        const progressBar = activeRow.querySelector('.progress-bar');
                        if (progressBar) {
                            const currentWidth = parseFloat(progressBar.style.width) || 0;
                            const widthDiff = Math.abs(completionPercentage - currentWidth);
                            
                            // Update progress bar with smooth transition
                            if (widthDiff >= 0.5 || completionPercentage >= 100 || completionPercentage === 0) {
                                progressBar.style.width = `${completionPercentage}%`;
                                progressBar.style.transition = 'width 0.5s ease-out';
                            }
                        }
                        
                        // Update time display for active building
                        const timeDisplay = activeRow.querySelector('.time-display');
                        if (timeDisplay) {
                            const remainingTime = Math.max(0, activeBuilding.endTime - now);
                            const newTimeText = this.formatRemainingTime(remainingTime, 0);
                            if (timeDisplay.textContent !== newTimeText) {
                                timeDisplay.textContent = newTimeText;
                            }
                        }
                    }
                    
                    // Check if building is completed
                    if (now >= activeBuilding.endTime && !activeBuilding.completed) {
                        activeBuilding.completed = true;
                        completedBuildings.push(buildingKey);
                        this.onBuildingCompleted(activeBuilding);
                    }
                }
            }
        }

        // Update time displays for queued buildings (but not progress bars)
        sortedBuildings.forEach((building, index) => {
            if (index > 0) { // Only queued buildings
                const row = document.querySelector(`tr[data-queue-index="${index}"]`);
                if (row) {
                    const timeDisplay = row.querySelector('.time-display');
                    if (timeDisplay) {
                        // Calculate total wait time for queued building
                        let totalWaitTime = 0;
                        
                        // Add remaining time from all previous buildings
                        for (let i = 0; i < index; i++) {
                            const prevBuilding = sortedBuildings[i];
                            if (prevBuilding && !prevBuilding.completed) {
                                totalWaitTime += Math.max(0, prevBuilding.endTime - now);
                            }
                        }
                        
                        // Add this building's build time
                        const thisBuildingDuration = building.endTime - building.startTime;
                        totalWaitTime += thisBuildingDuration;
                        
                        const newTimeText = this.formatRemainingTime(totalWaitTime, index);
                        if (timeDisplay.textContent !== newTimeText) {
                            timeDisplay.textContent = newTimeText;
                        }
                    }
                }
            }
        });

        // Remove completed buildings from tracking
        completedBuildings.forEach(buildingKey => {
            this.activeBuildings.delete(buildingKey);
        });

        // If buildings were completed, refresh the queue to update indices
        if (completedBuildings.length > 0) {
            this.refreshFullQueue();
        }

        // Stop updates if no active buildings
        if (this.activeBuildings.size === 0) {
            this.stopProgressUpdates();
            
            // Show "No buildings in queue" message
            const buildingQueueBody = document.getElementById('buildingQueueBody');
            if (buildingQueueBody && buildingQueueBody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="4">No buildings in queue</td>';
                buildingQueueBody.appendChild(emptyRow);
            }
        }
    }

    /**
     * Handle building completion
     */
    onBuildingCompleted(buildingData) {
        console.log(`Building completed: ${buildingData.buildingType} Level ${buildingData.level}`);
        
        // Refresh building data and resources
        if (window.fetchBuildings) {
            window.fetchBuildings(buildingData.settlementId);
        }
        if (window.fetchResources) {
            window.fetchResources(buildingData.settlementId);
        }
        
        // The queue will be refreshed by the calling function after removing the completed building
    }

    /**
     * Format remaining time for display
     */
    formatRemainingTime(milliseconds, queueIndex = 0) {
        if (milliseconds <= 0) {
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
     * Fetch all building progress data for a settlement (for page load)
     */
    async fetchAllBuildingProgress(settlementId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}?settlementId=${settlementId}&getAllBuildingProgress=true`);
            const data = await response.json();
            
            if (data.success && data.buildings && data.buildings.length > 0) {
                // Clear existing tracking
                this.activeBuildings.clear();
                
                // Sort buildings by queue index, then by start time
                const sortedBuildings = data.buildings.sort((a, b) => {
                    if (a.queueIndex !== b.queueIndex) {
                        return (a.queueIndex || 0) - (b.queueIndex || 0);
                    }
                    return new Date(a.startTime) - new Date(b.startTime);
                });
                
                // Track all active buildings with correct queue indices
                sortedBuildings.forEach((buildingProgress, index) => {
                    const buildingKey = `${settlementId}_${buildingProgress.buildingType}`;
                    this.activeBuildings.set(buildingKey, {
                        settlementId: settlementId,
                        buildingType: buildingProgress.buildingType,
                        startTime: new Date(buildingProgress.startTime).getTime(),
                        endTime: new Date(buildingProgress.endTime).getTime(),
                        level: buildingProgress.level,
                        queueIndex: index, // Use actual order in queue
                        completed: false
                    });
                });
                
                // Refresh the queue display
                this.refreshFullQueue();
                
                // Start updates if we have active buildings
                if (this.activeBuildings.size > 0) {
                    this.startProgressUpdates();
                }
            } else {
                // No buildings in progress
                const buildingQueueBody = document.getElementById('buildingQueueBody');
                if (buildingQueueBody) {
                    buildingQueueBody.innerHTML = '<tr><td colspan="4">No buildings in queue</td></tr>';
                }
            }
        } catch (error) {
            console.error('Error fetching all building progress:', error);
        }
    }
}

// Create global instance
window.buildingProgressManager = new BuildingProgressManager();

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BuildingProgressManager;
}