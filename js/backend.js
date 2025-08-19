// Global variables
let currentPlayerId = null;

function formatNumberWithDots(number) {
    const roundedNumber = Math.floor(number); // Round the number down
    return roundedNumber.toLocaleString('en-US'); // Formatting for English
}

// Cache for DOM elements to avoid repeated queries
let costElementsCache = null;
let upgradeButtonsCache = null;

// Function to invalidate caches when DOM changes (e.g., new buildings)
function invalidateElementCaches() {
    costElementsCache = null;
    upgradeButtonsCache = null;
}

function updateCostColors(resources) {
    // Cache cost elements on first run to avoid expensive DOM queries
    if (!costElementsCache) {
        costElementsCache = {
            wood: [],
            stone: [],
            ore: [],
            settlers: []
        };
        
        const resourceTypes = ['wood', 'stone', 'ore', 'settlers'];
        resourceTypes.forEach(resourceType => {
            const pattern = new RegExp(`Kosten${resourceType.charAt(0).toUpperCase() + resourceType.slice(1)}$`);
            const allElements = document.querySelectorAll('[id]');
            
            allElements.forEach(element => {
                if (pattern.test(element.id)) {
                    costElementsCache[resourceType].push(element);
                }
            });
        });
    }
    
    // Update using cached elements
    Object.keys(costElementsCache).forEach(resourceType => {
        const available = resourceType === 'settlers' ? resources.freeSettlers : resources[resourceType];
        
        costElementsCache[resourceType].forEach(element => {
            // Extract the cost value from the element
            const rawText = element.textContent.trim();
            const costValue = parseFloat(rawText.replace(',', '.').replace(/[^\d.]/g, '')) || 0;
            
            // Update color based on availability
            if (available < costValue) {
                element.classList.add('insufficient');
            } else {
                element.classList.remove('insufficient');
            }
        });
    });
    
    // Update button states based on resource availability
    updateBuildingButtonStates(resources);
}

function updateBuildingButtonStates(resources) {
    // Cache upgrade buttons on first run to avoid expensive DOM queries
    if (!upgradeButtonsCache) {
        upgradeButtonsCache = Array.from(document.querySelectorAll('[id$="upgradeButton"]'));
    }
    
    upgradeButtonsCache.forEach(button => {
        const costWood = parseInt(button.getAttribute('data-cost-wood')) || 0;
        const costStone = parseInt(button.getAttribute('data-cost-stone')) || 0;
        const costOre = parseInt(button.getAttribute('data-cost-ore')) || 0;
        const costSettlers = parseInt(button.getAttribute('data-cost-settlers')) || 0;
        
        // Check if all resources are sufficient
        const canAfford = resources.wood >= costWood &&
                         resources.stone >= costStone &&
                         resources.ore >= costOre &&
                         resources.freeSettlers >= costSettlers;
        
        if (canAfford) {
            button.disabled = false;
            button.classList.remove('insufficient-resources');
        } else {
            button.disabled = true;
            button.classList.add('insufficient-resources');
        }
    });
}

function updateTabVisibility() {
    // Always show all navigation tabs for consistent navigation
    const tradeTab = document.querySelector('a[href*="market.php"]');
    const militaryTab = document.querySelector('a[href*="kaserne.php"]');
    
    // Ensure Trade and Military tabs are always visible
    if (tradeTab) {
        tradeTab.style.display = '';
    }
    
    if (militaryTab) {
        militaryTab.style.display = '';
    }
}

function getRegen(settlementId) {
    // Use client-side progress manager if available
    if (window.clientProgressManager) {
        // Client manager handles this, but sync occasionally
        return;
    }
    
    // Fallback to original implementation
    fetch(`../php/backend.php?settlementId=${settlementId}&getRegen=true`)
    .then(response => response.json())
    .then(data => {
        if (data.regen) {
            document.getElementById('holzRegen').textContent = formatNumberWithDots(data.regen.regens.wood);
            document.getElementById('steinRegen').textContent = formatNumberWithDots(data.regen.regens.stone);
            document.getElementById('erzRegen').textContent = formatNumberWithDots(data.regen.regens.ore);
        }
    })
    .catch(error => console.error('Error fetching regeneration in backend.js:', error));
}

function fetchResources(settlementId) {
    // Use client-side progress manager if available
    if (window.clientProgressManager) {
        // Let client manager handle resources, but still fetch occasionally for accuracy
        const lastSync = window.clientProgressManager.lastServerSync;
        const now = Date.now();
        if (now - lastSync > 120000) { // Force sync every 2 minutes instead of 30 seconds
            window.clientProgressManager.forceSyncWithServer();
        }
        return;
    }
    
    // Fallback to original implementation
    fetch(`../php/backend.php?settlementId=${settlementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.resources) {
                document.getElementById('holz').textContent = formatNumberWithDots(data.resources.resources.wood);
                document.getElementById('stein').textContent = formatNumberWithDots(data.resources.resources.stone);
                document.getElementById('erz').textContent = formatNumberWithDots(data.resources.resources.ore);
                document.getElementById('lagerKapazität').textContent = formatNumberWithDots(data.resources.resources.storageCapacity);
                document.getElementById('settlers').textContent = formatNumberWithDots(data.resources.resources.freeSettlers);
                document.getElementById('maxSettlers').textContent = formatNumberWithDots(data.resources.resources.maxSettlers);

                // Farben der Kosten aktualisieren
                updateCostColors(data.resources.resources);
            }
        })
        .catch(error => console.error('Error fetching data in backend.js:', error));
}

function fetchPlayerInfo(settlementId) {
    fetch(`../php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
        .then(response => response.json())
        .then(data => {
            if (data.playerInfo) {
                document.getElementById('currentPlayer').textContent = data.playerInfo.playerName;
                document.getElementById('playerGold').textContent = formatNumberWithDots(data.playerInfo.playerGold);
                
                // Set current player ID for ownership validation (both locally and globally)
                currentPlayerId = data.playerInfo.playerId;
                window.currentPlayerId = data.playerInfo.playerId;
            }
        })
        .catch(error => console.error('Error fetching player info:', error));
}

function checkSettlementOwnership(settlementId) {
    fetch(`../php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
        .then(response => response.json())
        .then(data => {
            if (data.playerInfo) {
                const settlementOwnerId = data.playerInfo.playerId;
                const isOwnSettlement = currentPlayerId === settlementOwnerId;
                
                // Update UI based on ownership
                updateUIForOwnership(isOwnSettlement);
            }
        })
        .catch(error => console.error('Error checking settlement ownership:', error));
}

function updateUIForOwnership(isOwner) {
    // Get all upgrade buttons
    const upgradeButtons = document.querySelectorAll('[id$="upgradeButton"]');
    
    if (!isOwner) {
        // Disable upgrade buttons and add visual indicator
        upgradeButtons.forEach(button => {
            button.disabled = true;
            button.textContent = button.textContent + ' (Not your settlement)';
            button.style.opacity = '0.5';
            button.style.cursor = 'not-allowed';
        });
        
        // Add notification at the top
        addOwnershipNotification(false);
    } else {
        // Enable upgrade buttons 
        upgradeButtons.forEach(button => {
            button.disabled = false;
            button.style.opacity = '1';
            button.style.cursor = 'pointer';
            // Remove the notification text if it exists
            button.textContent = button.textContent.replace(' (Not your settlement)', '');
        });
        
        // Remove notification
        addOwnershipNotification(true);
    }
}

function addOwnershipNotification(isOwner) {
    // Remove existing notification
    const existingNotification = document.getElementById('ownershipNotification');
    if (existingNotification) {
        existingNotification.remove();
    }
    
    if (!isOwner) {
        // Add notification that this is not the player's settlement
        const notification = document.createElement('div');
        notification.id = 'ownershipNotification';
        notification.innerHTML = `
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 10px; margin: 10px 0; border-radius: 4px;">
                ⚠️ <strong>Viewing another player's settlement</strong> - You can only upgrade buildings and create trades in your own settlement.
            </div>
        `;
        
        // Insert after navigation
        const navigation = document.querySelector('.navigation');
        if (navigation && navigation.nextSibling) {
            navigation.parentNode.insertBefore(notification, navigation.nextSibling);
        }
    }
}

function fetchAllPlayers() {
    fetch(`../php/backend.php?getAllPlayers=true`)
        .then(response => response.json())
        .then(data => {
            if (data.players) {
                const playerSwitcher = document.getElementById('playerSwitcher');
                playerSwitcher.innerHTML = '';
                
                data.players.forEach(player => {
                    const option = document.createElement('option');
                    option.value = player.settlementId;
                    option.textContent = `${player.playerName} - ${player.settlementName}`;
                    if (player.settlementId == settlementId) {
                        option.selected = true;
                    }
                    playerSwitcher.appendChild(option);
                });
                
                // Add event listener for player switching
                playerSwitcher.addEventListener('change', function() {
                    if (this.value && this.value != settlementId) {
                        const currentPage = window.location.pathname.split('/').pop();
                        window.location.href = `${currentPage}?settlementId=${this.value}`;
                    }
                });
            }
        })
        .catch(error => console.error('Error fetching players:', error));
}

function fetchResourcesForColorUpdate(settlementId) {
    // Only update if we're not using client-side progress manager
    if (window.clientProgressManager) {
        return; // Let client-side manager handle this
    }
    
    fetch(`../php/backend.php?settlementId=${settlementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.resources) {
                // Only update cost colors, don't update the resource displays
                updateCostColors(data.resources.resources);
            }
        })
        .catch(error => console.error('Error fetching resources for color update:', error));
}

async function fetchBuildings(settlementId) {
    try {
        // First fetch the building types dynamically
        const response = await fetch(`../php/backend.php?getBuildingTypes=true`);
        const data = await response.json();
        
        let buildingTypes;
        if (data.buildingTypes && data.buildingTypes.buildingTypes) {
            buildingTypes = data.buildingTypes.buildingTypes.map(b => b.name);
        } else {
            // Fallback to default building types from centralized configuration
            buildingTypes = window.getDefaultBuildingTypes ? 
                window.getDefaultBuildingTypes().map(b => b.buildingType) :
                ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Markt', 'Kaserne'];
        }
        
        await fetchBuildingData(settlementId, buildingTypes);
    } catch (error) {
        console.error('Error fetching building types:', error);
        // Fallback to default building types from centralized configuration
        const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
            window.getDefaultBuildingTypes().map(b => b.buildingType) :
            ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Markt', 'Kaserne'];
        await fetchBuildingData(settlementId, defaultBuildingTypes);
    }
}

async function fetchBuildingData(settlementId, buildingTypes) {
    let completedRequests = 0;
    
    // Process buildings sequentially to avoid request bursts
    for (const buildingType of buildingTypes) {
        try {
            const response = await fetch(`../php/backend.php?settlementId=${settlementId}&buildingType=${buildingType}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                console.error(`Backend error for ${buildingType}:`, data.error);
                throw new Error(data.error);
            }
            
            if (data.building) {
                const buildingId = buildingType.toLowerCase();
                const levelElement = document.getElementById(`${buildingId}`);
                const woodElement = document.getElementById(`${buildingId}KostenHolz`);
                const stoneElement = document.getElementById(`${buildingId}KostenStein`);
                const oreElement = document.getElementById(`${buildingId}KostenErz`);
                const settlersElement = document.getElementById(`${buildingId}KostenSiedler`);
                const timeElement = document.getElementById(`${buildingId}Bauzeit`);
                const buttonElement = document.getElementById(`${buildingId}upgradeButton`);

                if (levelElement) levelElement.textContent = data.building.level;
                if (woodElement) woodElement.textContent = `${formatNumberWithDots(data.building.costWood)} 🪵`;
                if (stoneElement) stoneElement.textContent = `${formatNumberWithDots(data.building.costStone)} 🧱`;
                if (oreElement) oreElement.textContent = `${formatNumberWithDots(data.building.costOre)} 🪨`;
                if (settlersElement) settlersElement.textContent = `${formatNumberWithDots(data.building.costSettlers)} 👥`;
                if (timeElement) timeElement.textContent = `${formatNumberWithDots(data.building.buildTime)}s ⏱️`;
                
                // Update button text based on current level (Build vs Upgrade)
                // Hide button if building is at max level (10)
                if (buttonElement) {
                    const isFirstBuild = data.building.level === 0;
                    const isMaxLevel = data.building.level >= 10;
                    
                    if (isMaxLevel) {
                        buttonElement.style.display = 'none';
                    } else {
                        buttonElement.style.display = '';
                        buttonElement.textContent = isFirstBuild 
                            ? 'Build' 
                            : `Upgrade to ${formatNumberWithDots(data.building.nextLevel)}`;
                    }
                }
                
                // Store building data for later cost checking
                if (buttonElement) {
                    buttonElement.setAttribute('data-cost-wood', data.building.costWood);
                    buttonElement.setAttribute('data-cost-stone', data.building.costStone);
                    buttonElement.setAttribute('data-cost-ore', data.building.costOre);
                    buttonElement.setAttribute('data-cost-settlers', data.building.costSettlers);
                }
            } else {
                console.warn(`No building data returned for ${buildingType}, response:`, data);
                // If building data is missing, ensure level shows 0 explicitly
                const buildingId = buildingType.toLowerCase();
                const levelElement = document.getElementById(`${buildingId}`);
                if (levelElement) {
                    levelElement.textContent = '0';
                }
            }
            
            completedRequests++;
        } catch (error) {
            console.error(`Error fetching data for ${buildingType}:`, error);
            // On error, ensure the building level shows 0 instead of staying empty
            const buildingId = buildingType.toLowerCase();
            const levelElement = document.getElementById(`${buildingId}`);
            if (levelElement) {
                levelElement.textContent = '0';
            }
            
            completedRequests++;
        }
        
        // Small delay to prevent overwhelming the server, but much smaller than before
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    // Call final updates only once, after all building requests are complete
    getRegen(settlementId);
    fetchResourcesForColorUpdate(settlementId);
    updateTabVisibility();
    
    // Invalidate caches after building data changes
    invalidateElementCaches();
}

function upgradeBuilding(buildingType, settlementId) {
    // Get the owner of the settlement we're trying to upgrade
    fetch(`../php/backend.php?settlementId=${settlementId}&getPlayerInfo=true`)
        .then(response => response.json())
        .then(ownerData => {
            const settlementOwnerId = ownerData.playerInfo ? ownerData.playerInfo.playerId : null;
            
            // Check if current player owns this settlement
            if (currentPlayerId !== null && settlementOwnerId !== null && currentPlayerId !== settlementOwnerId) {
                alert('You can only upgrade buildings in your own settlement. Switch to your settlement first.');
                return;
            }
            
            // Proceed with the upgrade
            fetch('../php/backend.php?settlementId=' + settlementId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    buildingType, 
                    currentPlayerId: currentPlayerId 
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        //alert(data.message); // Shows success message
                        fetchBuildings(settlementId); // Update building data
                        fetchResources(settlementId);
                        
                        // Add a delay to ensure database is fully updated before tracking progress
                        // Increased from 500ms to 750ms to handle slower database operations
                        setTimeout(() => {
                            if (window.unifiedProgressManager) {
                                // Use the unified progress manager to force a complete refresh
                                window.unifiedProgressManager.forceSyncWithServer();
                            } else if (window.clientProgressManager) {
                                // Force sync with client progress manager if it exists
                                window.clientProgressManager.forceSyncWithServer();
                            } else {
                                // Fallback to old system
                                fetchBuildingQueue(settlementId);
                            }
                        }, 750); // 750ms delay to ensure database consistency on slower systems
                    } else {
                        alert(data.message); // Shows error message
                    }
                })
                .catch(error => {
                    console.error('Error upgrading building:', error);
                    alert('An error occurred.');
                });
        })
        .catch(error => {
            console.error('Error checking settlement ownership:', error);
            alert('Unable to verify settlement ownership.');
        });
}

function getSettlementName(settlementId) {
    fetch(`../php/backend.php?settlementId=${settlementId}&getSettlementName=True`)
        .then(response => response.json())
        .then(data => {
            if (data.info) {
                const nameElement = document.getElementById('Siedlungsname');
                if (nameElement) {
                    nameElement.textContent = data.info.SettlementName;
                }
            }
        })
        .catch(error => console.error('Error fetching data in backend.js:', error));
}

function fetchBuildingQueue(settlementId) {
    // Use client-side progress manager if available
    if (window.clientProgressManager) {
        window.clientProgressManager.forceSyncWithServer();
        return;
    }
    
    // Fallback to original implementation
    fetch(`../php/backend.php?settlementId=${settlementId}&getBuildingQueue=True`)
        .then(response => response.json())
        .then(data => {
            const buildingQueueBody = document.getElementById('buildingQueueBody');
            if (!buildingQueueBody) return; // Exit if element doesn't exist
            
            buildingQueueBody.innerHTML = '';

            // Check if data.info.queue exists
            if (data.info && data.info.queue && data.info.queue.length > 0) {
                data.info.queue.forEach(item => {
                    const row = document.createElement('tr');
                    
                    // Translate building name from German to English
                    const translatedBuildingName = window.translateBuildingName ? 
                        window.translateBuildingName(item.buildingType) : 
                        item.buildingType;

                    // Ensure completion percentage is valid (0-100)
                    const completionPercentage = Math.max(0, Math.min(100, item.completionPercentage || 0));
                    
                    row.innerHTML = `
                        <td>${translatedBuildingName}</td>
                        <td>${item.level}</td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: ${completionPercentage}%;"></div>
                            </div>
                        </td>
                        <td>${item.endTime}</td>
                    `;

                    buildingQueueBody.appendChild(row);
                });
            } else {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = '<td colspan="4">No buildings in queue</td>';
                buildingQueueBody.appendChild(emptyRow);
            }
        })
        .catch(error => console.error('Error fetching BuildingQueue:', error));
}

getSettlementName(settlementId);

// Initialize client progress manager
async function initializeClientProgressManager(settlementId) {
    if (!window.clientProgressManager) {
        console.warn('Client progress manager not available');
        return;
    }
    
    try {
        // Fetch initial data from server
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
        
        const initialData = {};
        
        if (resourcesData.resources) {
            initialData.resources = resourcesData.resources.resources;
        }
        
        if (queueData.info && queueData.info.queue) {
            initialData.buildingQueue = queueData.info.queue;
        }
        
        if (regenData.regen) {
            initialData.regenerationRates = regenData.regen.regens;
        }
        
        // Initialize the client progress manager
        window.clientProgressManager.initialize(initialData);
        console.log('Client progress manager initialized successfully');
    } catch (error) {
        console.error('Error initializing client progress manager:', error);
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // Initialize client progress manager first
    await initializeClientProgressManager(settlementId);
    
    // Initialize the new building progress manager and fetch all progress
    if (window.buildingProgressManager) {
        await window.buildingProgressManager.fetchAllBuildingProgress(settlementId);
    }
    
    // Initial data fetch
    if (!window.buildingProgressManager) {
        // Only use old system if new system is not available
        fetchBuildingQueue(settlementId);
    }
    
    fetchResources(settlementId);
    getRegen(settlementId);
    fetchPlayerInfo(settlementId);
    fetchAllPlayers();
    await fetchBuildings(settlementId);

    // Set up optimized intervals based on available progress systems
    const config = window.PerformanceConfig?.polling || {};
    
    if (window.buildingProgressManager) {
        console.log('Using new building progress bar system');
        
        // Minimal polling since new progress bar handles real-time updates
        const modernConfig = config.modernSystem || {};
        setInterval(() => fetchPlayerInfo(settlementId), modernConfig.playerInfo || 120000);
        setInterval(() => fetchBuildings(settlementId), modernConfig.buildings || 300000);
        
        // New progress bar system handles building queue updates internally
    } else if (window.clientProgressManager) {
        console.log('Using optimized client-side progress system');
        
        // Very conservative server polling when using client-side calculations
        const modernConfig = config.modernSystem || {};
        setInterval(() => fetchPlayerInfo(settlementId), modernConfig.playerInfo || 120000);
        setInterval(() => fetchBuildings(settlementId), modernConfig.buildings || 300000);
        
        // Client progress manager handles resources and queue updates
    } else {
        console.log('Falling back to original polling system');
        
        // Reduced frequency polling for fallback system
        const fallbackConfig = config.fallbackSystem || {};
        setInterval(() => fetchBuildingQueue(settlementId), fallbackConfig.queue || 2000);
        setInterval(() => fetchResources(settlementId), fallbackConfig.resources || 3000);
        setInterval(() => getRegen(settlementId), fallbackConfig.regen || 10000);
        setInterval(() => fetchPlayerInfo(settlementId), fallbackConfig.playerInfo || 15000);
        setInterval(() => fetchBuildings(settlementId), fallbackConfig.buildings || 15000);
    }
    
    // Check settlement ownership after a short delay to ensure currentPlayerId is set
    setTimeout(() => checkSettlementOwnership(settlementId), 1000);
});

