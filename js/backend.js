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
                    ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm'];
                fetchBuildingData(settlementId, defaultBuildingTypes);
            }
        })
        .catch(error => {
            console.error('Error fetching building types:', error);
            // Fallback to default building types from centralized configuration
            const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
                window.getDefaultBuildingTypes().map(b => b.buildingType) :
                ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm'];
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
                        if (buttonElement) buttonElement.textContent = `Upgrade to ${formatNumberWithDots(data.building.nextLevel)}`;
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
                    }
                });
        }, index * 100); // 100ms delay between each request
    });
}

function upgradeBuilding(buildingType, settlementId) {
    fetch('../php/backend.php?settlementId=' + settlementId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ buildingType }),
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

    // Update building data every 5 seconds
    fetchBuildings(settlementId);
    setInterval(() => fetchBuildings(settlementId), 5000); // 5000ms = 5 seconds
});

