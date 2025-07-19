// Global variables
let currentPlayerId = null;

function formatNumberWithDots(number) {
    const roundedNumber = Math.floor(number); // Round the number down
    return roundedNumber.toLocaleString('en-US'); // Formatting for English
}

function updateCostColors(resources) {
    // Dynamically find all cost elements instead of hardcoding them
    const resourceTypes = ['wood', 'stone', 'ore', 'settlers'];
    
    resourceTypes.forEach(resourceType => {
        // Find all elements that match the pattern [buildingType]Kosten[ResourceType]
        const pattern = new RegExp(`Kosten${resourceType.charAt(0).toUpperCase() + resourceType.slice(1)}$`);
        const allElements = document.querySelectorAll('[id]');
        
        allElements.forEach(element => {
            if (pattern.test(element.id)) {
                // Extract the cost value from the element
                const rawText = element.textContent.trim();
                const costValue = parseFloat(rawText.replace(',', '.').replace(/[^\d.]/g, '')) || 0;
                
                // Get available resources
                const available = resourceType === 'settlers' ? resources.freeSettlers : resources[resourceType];
                
                // Update color based on availability
                if (available < costValue) {
                    element.classList.add('insufficient');
                } else {
                    element.classList.remove('insufficient');
                }
            }
        });
    });
    
    // Update button states based on resource availability
    updateBuildingButtonStates(resources);
}

function updateBuildingButtonStates(resources) {
    // Find all upgrade buttons and check if they can be enabled
    const upgradeButtons = document.querySelectorAll('[id$="upgradeButton"]');
    
    upgradeButtons.forEach(button => {
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
    // Check if Market building exists and has level > 0
    const marketLevelElement = document.getElementById('markt');
    const marketLevel = marketLevelElement ? parseInt(marketLevelElement.textContent) || 0 : 0;
    
    // Check if Barracks building exists and has level > 0
    const barrackLevelElement = document.getElementById('kaserne');
    const barrackLevel = barrackLevelElement ? parseInt(barrackLevelElement.textContent) || 0 : 0;
    
    // Find the Trade and Military tab links
    const tradeTab = document.querySelector('a[href*="market.php"]');
    const militaryTab = document.querySelector('a[href*="kaserne.php"]');
    
    // Show/hide Trade tab based on Market building level
    if (tradeTab) {
        if (marketLevel > 0) {
            tradeTab.style.display = '';
        } else {
            tradeTab.style.display = 'none';
        }
    }
    
    // Show/hide Military tab based on Barracks building level
    if (militaryTab) {
        if (barrackLevel > 0) {
            militaryTab.style.display = '';
        } else {
            militaryTab.style.display = 'none';
        }
    }
}

function getRegen(settlementId) {
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

function fetchBuildings(settlementId) {
    // First fetch the building types dynamically
    fetch(`../php/backend.php?getBuildingTypes=true`)
        .then(response => response.json())
        .then(data => {
            if (data.buildingTypes && data.buildingTypes.buildingTypes) {
                const buildingTypes = data.buildingTypes.buildingTypes.map(b => b.name);
                fetchBuildingData(settlementId, buildingTypes);
            } else {
                // Fallback to default building types from centralized configuration
                const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
                    window.getDefaultBuildingTypes().map(b => b.buildingType) :
                    ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Markt', 'Kaserne'];
                fetchBuildingData(settlementId, defaultBuildingTypes);
            }
        })
        .catch(error => {
            console.error('Error fetching building types:', error);
            // Fallback to default building types from centralized configuration
            const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
                window.getDefaultBuildingTypes().map(b => b.buildingType) :
                ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm', 'Markt', 'Kaserne'];
            fetchBuildingData(settlementId, defaultBuildingTypes);
        });
}

function fetchBuildingData(settlementId, buildingTypes) {
    let completedRequests = 0;

    buildingTypes.forEach((buildingType, index) => {
        // Add a small delay to prevent overwhelming the database with concurrent requests
        setTimeout(() => {
            fetch(`../php/backend.php?settlementId=${settlementId}&buildingType=${buildingType}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
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
                    
                    // Only call getRegen and update cost colors once when all building requests are complete
                    completedRequests++;
                    if (completedRequests === buildingTypes.length) {
                        getRegen(settlementId);
                        // Trigger cost color update after all buildings are loaded
                        fetchResourcesForColorUpdate(settlementId);
                        // Update tab visibility based on building levels
                        updateTabVisibility();
                    }
                })
                .catch(error => {
                    console.error(`Error fetching data for ${buildingType}:`, error);
                    // On error, ensure the building level shows 0 instead of staying empty
                    const buildingId = buildingType.toLowerCase();
                    const levelElement = document.getElementById(`${buildingId}`);
                    if (levelElement) {
                        levelElement.textContent = '0';
                    }
                    
                    completedRequests++;
                    if (completedRequests === buildingTypes.length) {
                        getRegen(settlementId);
                        // Trigger cost color update after all buildings are loaded
                        fetchResourcesForColorUpdate(settlementId);
                        // Update tab visibility based on building levels
                        updateTabVisibility();
                    }
                });
        }, index * 100); // 100ms delay between each request
    });
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
                        fetchBuildingQueue(settlementId);
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

                    row.innerHTML = `
                        <td>${translatedBuildingName}</td>
                        <td>${item.level}</td>
                        <td>
                            <div class="progress-container">
                                <div class="progress-bar" style="width: ${item.completionPercentage || 0}%;"></div>
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

document.addEventListener('DOMContentLoaded', () => {
    
    fetchBuildingQueue(settlementId);
    setInterval(() => fetchBuildingQueue(settlementId), 1000);

    // Update resources every second
    fetchResources(settlementId);
    setInterval(() => fetchResources(settlementId), 1000);
    
    // Fetch player info and update every 5 seconds
    fetchPlayerInfo(settlementId);
    setInterval(() => fetchPlayerInfo(settlementId), 5000);
    
    // Load all players for the switcher
    fetchAllPlayers();

    // Update building data every 5 seconds
    fetchBuildings(settlementId);
    setInterval(() => fetchBuildings(settlementId), 5000); // 5000ms = 5 seconds
    
    // Check settlement ownership after a short delay to ensure currentPlayerId is set
    setTimeout(() => checkSettlementOwnership(settlementId), 1000);
});

