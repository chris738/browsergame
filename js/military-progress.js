/**
 * Military Progress Manager
 * 
 * Extends the unified progress system to handle military training and research progress bars
 * Provides smooth, client-side progress updates for military operations
 */

class MilitaryProgressManager {
    constructor() {
        // Training queue management
        this.activeTraining = new Map(); // Map of training in progress
        this.trainingQueue = [];
        
        // Research queue management
        this.activeResearch = new Map(); // Map of research in progress
        this.researchQueue = [];
        
        // Timing and synchronization
        this.lastServerSync = 0;
        this.updateInterval = null;
        this.apiBaseUrl = '../php/backend.php';
        
        // Configuration
        this.config = {
            serverSyncInterval: 120000, // Sync with server every 2 minutes
            progressUpdateInterval: 250, // Update progress every 250ms for smooth animation
            resourceUpdateInterval: 1000 // Update resources every second
        };
        
        // Bind methods
        this.updateProgress = this.updateProgress.bind(this);
        this.startProgressUpdates = this.startProgressUpdates.bind(this);
        this.stopProgressUpdates = this.stopProgressUpdates.bind(this);
    }

    /**
     * Initialize the military progress manager with initial data from server
     */
    initialize(settlementId) {
        console.log('Initializing Military Progress Manager for settlement:', settlementId);
        
        this.settlementId = settlementId;
        this.lastServerSync = Date.now();
        
        // Load initial data
        this.syncWithServer();
        this.startProgressUpdates();
    }

    /**
     * Start the progress update loop
     */
    startProgressUpdates() {
        if (this.updateInterval) return; // Already running

        this.updateInterval = setInterval(() => {
            this.updateProgress();
        }, this.config.progressUpdateInterval);
        
        console.log('Started military progress update loop');
    }

    /**
     * Stop the progress update loop
     */
    stopProgressUpdates() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
            console.log('Stopped military progress update loop');
        }
    }

    /**
     * Update progress for all active military operations
     */
    updateProgress() {
        const now = Date.now();

        // Check if we need to sync with server
        if ((now - this.lastServerSync) > this.config.serverSyncInterval) {
            this.syncWithServer();
        }

        // Update training progress
        this.updateTrainingProgress(now);
        
        // Update research progress
        this.updateResearchProgress(now);

        // Stop if no more operations
        if (this.trainingQueue.length === 0 && this.researchQueue.length === 0) {
            this.stopProgressUpdates();
        }
    }

    /**
     * Update training progress bars
     */
    updateTrainingProgress(now) {
        if (this.trainingQueue.length === 0) return;

        const completedTraining = [];

        this.trainingQueue.forEach((training, index) => {
            if (!training.startTime || !training.endTime) return;
            
            const totalDuration = training.endTime - training.startTime;
            
            if (totalDuration <= 0) {
                training.completed = true;
                completedTraining.push(training);
                return;
            }
            
            const elapsed = now - training.startTime;
            const completionPercentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
            
            if (!isNaN(completionPercentage) && completionPercentage >= 0) {
                this.updateTrainingProgressBar(training, completionPercentage, index);
                
                // Check if training is completed
                if (now >= training.endTime && !training.completed) {
                    training.completed = true;
                    completedTraining.push(training);
                }
            }
        });

        // Remove completed training
        if (completedTraining.length > 0) {
            this.trainingQueue = this.trainingQueue.filter(item => !item.completed);
            this.refreshTrainingQueue();
            // Trigger data reload after completion
            if (window.loadMilitaryData) {
                window.loadMilitaryData(this.settlementId);
            }
        }
    }

    /**
     * Update research progress bars
     */
    updateResearchProgress(now) {
        if (this.researchQueue.length === 0) return;

        const completedResearch = [];

        this.researchQueue.forEach((research, index) => {
            if (!research.startTime || !research.endTime) return;
            
            const totalDuration = research.endTime - research.startTime;
            
            if (totalDuration <= 0) {
                research.completed = true;
                completedResearch.push(research);
                return;
            }
            
            const elapsed = now - research.startTime;
            const completionPercentage = Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
            
            if (!isNaN(completionPercentage) && completionPercentage >= 0) {
                this.updateResearchProgressBar(research, completionPercentage, index);
                
                // Check if research is completed
                if (now >= research.endTime && !research.completed) {
                    research.completed = true;
                    completedResearch.push(research);
                }
            }
        });

        // Remove completed research
        if (completedResearch.length > 0) {
            this.researchQueue = this.researchQueue.filter(item => !item.completed);
            this.refreshResearchQueue();
            // Trigger data reload after completion
            if (window.loadResearchData) {
                window.loadResearchData(this.settlementId);
            }
        }
    }

    /**
     * Update training progress bar in the DOM
     */
    updateTrainingProgressBar(training, completionPercentage, queueIndex) {
        const tbody = document.getElementById('militaryTrainingQueueBody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        if (queueIndex < rows.length) {
            const row = rows[queueIndex];
            const progressBar = row.querySelector('.progress-bar');
            const progressText = row.querySelector('.progress-percentage');
            
            if (progressBar) {
                const currentWidth = parseFloat(progressBar.style.width) || 0;
                const widthDiff = Math.abs(completionPercentage - currentWidth);
                
                // Update if change is significant or it's completion
                if (widthDiff >= 0.1 || completionPercentage >= 99.9 || currentWidth === 0) {
                    progressBar.style.width = `${Math.min(100, completionPercentage)}%`;
                    progressBar.style.transition = 'width 0.2s ease-out';
                }
            }
            
            if (progressText) {
                progressText.textContent = `${Math.round(completionPercentage)}%`;
            }
            
            // Update remaining time
            const timeCell = row.querySelector('td:last-child');
            if (timeCell) {
                const remainingTime = Math.max(0, training.endTime - Date.now());
                const newTimeText = this.formatRemainingTime(remainingTime);
                
                // Only update the time part, preserve the original end time
                const timeDiv = timeCell.querySelector('div');
                if (timeDiv) {
                    timeDiv.textContent = newTimeText;
                } else if (remainingTime > 0) {
                    const remainingDiv = document.createElement('div');
                    remainingDiv.style.fontSize = '12px';
                    remainingDiv.style.color = '#666';
                    remainingDiv.textContent = newTimeText;
                    timeCell.appendChild(remainingDiv);
                }
            }
        }
    }

    /**
     * Update research progress bar in the DOM
     */
    updateResearchProgressBar(research, completionPercentage, queueIndex) {
        const tbody = document.getElementById('researchQueueBody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr');
        if (queueIndex < rows.length) {
            const row = rows[queueIndex];
            const progressBar = row.querySelector('.progress-bar');
            const progressText = row.querySelector('.progress-percentage');
            
            if (progressBar) {
                const currentWidth = parseFloat(progressBar.style.width) || 0;
                const widthDiff = Math.abs(completionPercentage - currentWidth);
                
                // Update if change is significant or it's completion
                if (widthDiff >= 0.1 || completionPercentage >= 99.9 || currentWidth === 0) {
                    progressBar.style.width = `${Math.min(100, completionPercentage)}%`;
                    progressBar.style.transition = 'width 0.2s ease-out';
                }
            }
            
            if (progressText) {
                progressText.textContent = `${Math.round(completionPercentage)}%`;
            }
            
            // Update remaining time
            const timeCell = row.querySelector('td:last-child');
            if (timeCell) {
                const remainingTime = Math.max(0, research.endTime - Date.now());
                const newTimeText = this.formatRemainingTime(remainingTime);
                
                // Only update the time part, preserve the original end time
                const timeDiv = timeCell.querySelector('div');
                if (timeDiv) {
                    timeDiv.textContent = newTimeText;
                } else if (remainingTime > 0) {
                    const remainingDiv = document.createElement('div');
                    remainingDiv.style.fontSize = '12px';
                    remainingDiv.style.color = '#666';
                    remainingDiv.textContent = newTimeText;
                    timeCell.appendChild(remainingDiv);
                }
            }
        }

        // Also update progress in research button areas
        const progressContainer = document.getElementById(`research-progress-${research.unitType}`);
        if (progressContainer) {
            const progressBar = progressContainer.querySelector('.progress-bar');
            const progressText = progressContainer.querySelector('.progress-percentage');
            
            if (progressBar) {
                progressBar.style.width = `${Math.min(100, completionPercentage)}%`;
            }
            if (progressText) {
                progressText.textContent = `${Math.round(completionPercentage)}%`;
            }
        }
    }

    /**
     * Refresh training queue display
     */
    refreshTrainingQueue() {
        if (window.updateMilitaryQueue) {
            const queueData = this.trainingQueue.map(training => ({
                ...training,
                completionPercentage: this.calculateCurrentProgress(training),
                remainingTimeSeconds: Math.max(0, Math.floor((training.endTime - Date.now()) / 1000))
            }));
            window.updateMilitaryQueue(queueData);
        }
    }

    /**
     * Refresh research queue display
     */
    refreshResearchQueue() {
        if (window.updateResearchQueue) {
            const queueData = this.researchQueue.map(research => ({
                ...research,
                completionPercentage: this.calculateCurrentProgress(research),
                remainingTimeSeconds: Math.max(0, Math.floor((research.endTime - Date.now()) / 1000))
            }));
            window.updateResearchQueue(queueData);
        }
    }

    /**
     * Calculate current progress percentage
     */
    calculateCurrentProgress(item) {
        if (!item.startTime || !item.endTime) return 0;
        
        const now = Date.now();
        const totalDuration = item.endTime - item.startTime;
        const elapsed = now - item.startTime;
        
        return Math.min(100, Math.max(0, (elapsed / totalDuration) * 100));
    }

    /**
     * Format remaining time for display
     */
    formatRemainingTime(milliseconds) {
        if (milliseconds <= 100) {
            return 'Completing...';
        }

        const seconds = Math.floor(milliseconds / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `(${hours}h ${minutes % 60}m remaining)`;
        } else if (minutes > 0) {
            return `(${minutes}m ${seconds % 60}s remaining)`;
        } else {
            return `(${seconds}s remaining)`;
        }
    }

    /**
     * Sync with server for fresh data
     */
    async syncWithServer() {
        if (!this.settlementId) return;
        
        this.lastServerSync = Date.now();
        
        try {
            console.log('Military progress manager syncing with server...');
            
            const [trainingResponse, researchResponse] = await Promise.all([
                fetch(`${this.apiBaseUrl}?settlementId=${this.settlementId}&getMilitaryQueue=true`),
                fetch(`${this.apiBaseUrl}?settlementId=${this.settlementId}&getResearchQueue=true`)
            ]);
            
            const [trainingData, researchData] = await Promise.all([
                trainingResponse.json(),
                researchResponse.json()
            ]);
            
            // Update training queue
            if (trainingData.militaryQueue && trainingData.militaryQueue.queue) {
                this.trainingQueue = trainingData.militaryQueue.queue.map(item => ({
                    ...item,
                    startTime: new Date(item.startTime).getTime(),
                    endTime: new Date(item.endTime).getTime(),
                    completed: false
                }));
            } else {
                this.trainingQueue = [];
            }
            
            // Update research queue
            if (researchData.researchQueue && researchData.researchQueue.queue) {
                this.researchQueue = researchData.researchQueue.queue.map(item => ({
                    ...item,
                    startTime: new Date(item.startTime).getTime(),
                    endTime: new Date(item.endTime).getTime(),
                    completed: false
                }));
            } else {
                this.researchQueue = [];
            }
            
            console.log(`Military sync complete: ${this.trainingQueue.length} training, ${this.researchQueue.length} research`);
            
        } catch (error) {
            console.error('Error syncing military progress with server:', error);
        }
    }

    /**
     * Force sync with server (called when user takes action)
     */
    forceSyncWithServer() {
        console.log('Forcing military sync with server...');
        this.lastServerSync = 0;
        this.syncWithServer();
    }

    /**
     * Stop all progress tracking and cleanup
     */
    stop() {
        this.stopProgressUpdates();
        this.trainingQueue = [];
        this.researchQueue = [];
        this.activeTraining.clear();
        this.activeResearch.clear();
    }
}

// Create global instance
window.militaryProgressManager = new MilitaryProgressManager();

// Automatic initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('Military progress manager loaded');
});

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MilitaryProgressManager;
}