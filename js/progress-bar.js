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
                
                // Store the building progress data
                const buildingKey = `${settlementId}_${buildingType}`;
                this.activeBuildings.set(buildingKey, {
                    settlementId: settlementId,
                    buildingType: buildingType,
                    startTime: new Date(progressData.startTime).getTime(),
                    endTime: new Date(progressData.endTime).getTime(),
                    level: progressData.level,
                    queueIndex: progressData.queueIndex || 0
                });
                
                // Add or update progress bar in the UI
                this.addProgressBarToQueue(progressData);
                
                // Start the progress update loop if not already running
                this.startProgressUpdates();
                
                console.log(`Successfully started tracking ${buildingType}:`, progressData);
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

        // Check if a row for this building already exists
        const existingRow = Array.from(buildingQueueBody.querySelectorAll('tr')).find(row => {
            const buildingCell = row.querySelector('td:first-child');
            return buildingCell && buildingCell.textContent.includes(this.translateBuildingName(progressData.buildingType));
        });

        let row;
        if (existingRow) {
            row = existingRow;
        } else {
            row = document.createElement('tr');
            buildingQueueBody.appendChild(row);
        }

        // Calculate initial progress
        const now = Date.now();
        const startTime = new Date(progressData.startTime).getTime();
        const endTime = new Date(progressData.endTime).getTime();
        const totalDuration = endTime - startTime;
        const elapsed = now - startTime;
        const initialProgress = Math.max(0, Math.min(100, (elapsed / totalDuration) * 100));

        // Create or update row content
        const translatedName = this.translateBuildingName(progressData.buildingType);
        const queueStatus = progressData.queueIndex === 0 ? '' : ' (queued)';
        
        row.innerHTML = `
            <td class="${progressData.queueIndex === 0 ? 'active-building' : 'queued-building'}">${translatedName}${queueStatus}</td>
            <td>${progressData.level}</td>
            <td>
                <div class="progress-container">
                    <div class="progress-bar ${progressData.queueIndex === 0 ? 'active-building' : 'queued-building'}" 
                         style="width: ${progressData.queueIndex === 0 ? initialProgress : 0}%; transition: width 0.3s ease-out;">
                    </div>
                </div>
            </td>
            <td class="time-display">${this.formatRemainingTime(endTime - now, progressData.queueIndex)}</td>
        `;

        // Store reference to this row for updates
        row.dataset.buildingType = progressData.buildingType;
        row.dataset.settlementId = progressData.settlementId;
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

        for (const [buildingKey, buildingData] of this.activeBuildings) {
            const { buildingType, startTime, endTime, queueIndex, settlementId } = buildingData;
            
            // Find the corresponding row in the table
            const row = document.querySelector(`tr[data-building-type="${buildingType}"][data-settlement-id="${settlementId}"]`);
            if (!row) continue;

            const totalDuration = endTime - startTime;
            const elapsed = now - startTime;
            const progress = Math.max(0, Math.min(100, (elapsed / totalDuration) * 100));

            // Update progress bar only for active building (queueIndex 0)
            if (queueIndex === 0) {
                const progressBar = row.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                }
            }

            // Update time display
            const timeDisplay = row.querySelector('.time-display');
            if (timeDisplay) {
                const remainingTime = Math.max(0, endTime - now);
                timeDisplay.textContent = this.formatRemainingTime(remainingTime, queueIndex);
            }

            // Check if building is completed
            if (now >= endTime) {
                completedBuildings.push(buildingKey);
                this.onBuildingCompleted(buildingData, row);
            }
        }

        // Remove completed buildings from tracking
        completedBuildings.forEach(buildingKey => {
            this.activeBuildings.delete(buildingKey);
        });

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
    onBuildingCompleted(buildingData, row) {
        console.log(`Building completed: ${buildingData.buildingType} Level ${buildingData.level}`);
        
        // Remove the row from the queue
        if (row && row.parentNode) {
            row.parentNode.removeChild(row);
        }

        // Refresh building data and resources
        if (window.fetchBuildings) {
            window.fetchBuildings(buildingData.settlementId);
        }
        if (window.fetchResources) {
            window.fetchResources(buildingData.settlementId);
        }
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
                
                // Track all active buildings
                for (const buildingProgress of data.buildings) {
                    const buildingKey = `${settlementId}_${buildingProgress.buildingType}`;
                    this.activeBuildings.set(buildingKey, {
                        settlementId: settlementId,
                        buildingType: buildingProgress.buildingType,
                        startTime: new Date(buildingProgress.startTime).getTime(),
                        endTime: new Date(buildingProgress.endTime).getTime(),
                        level: buildingProgress.level,
                        queueIndex: buildingProgress.queueIndex || 0
                    });
                    
                    this.addProgressBarToQueue(buildingProgress);
                }
                
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