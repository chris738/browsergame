function formatNumberWithDots(number) {
    const roundedNumber = Math.floor(number); // Rundet die Zahl nach unten
    return roundedNumber.toLocaleString('de-DE'); // Formatierung für Deutschland
}

function updateCostColors(resources) {
    const costElements = [
        { type: 'rathaus', wood: 'rathausKostenHolz', stone: 'rathausKostenStein', ore: 'rathausKostenErz', settlers: 'rathausKostenSiedler'},
        { type: 'holzfäller', wood: 'holzfällerKostenHolz', stone: 'holzfällerKostenStein', ore: 'holzfällerKostenErz', settlers: 'holzfällerKostenSiedler'},
        { type: 'steinbruch', wood: 'steinbruchKostenHolz', stone: 'steinbruchKostenStein', ore: 'steinbruchKostenErz', settlers: 'steinbruchKostenSiedler' },
        { type: 'erzbergwerk', wood: 'erzbergwerkKostenHolz', stone: 'erzbergwerkKostenStein', ore: 'erzbergwerkKostenErz', settlers: 'erzbergwerkKostenSiedler' },
        { type: 'lager', wood: 'lagerKostenHolz', stone: 'lagerKostenStein', ore: 'lagerKostenErz', settlers: 'lagerKostenSiedler' },
        { type: 'farm', wood: 'farmKostenHolz', stone: 'farmKostenStein', ore: 'farmKostenErz', settlers: 'farmKostenSiedler' }
    ];

    costElements.forEach(element => {
        ['wood', 'stone', 'ore', 'settlers'].forEach(resourceType => {
            const elementId = element[resourceType];
            const elementNode = document.getElementById(elementId);

            if (elementNode) {
                // Extrahiere den Textinhalt des Elements
                const rawText = elementNode.textContent.trim();

                // Entferne nicht-numerische Zeichen und ersetze Komma durch Punkt
                const costValue = parseFloat(rawText.replace(',', '.').replace(/[^\d.]/g, '')) || 0;

                // Verfügbare Ressourcen
                const available = resourceType === 'settlers' ? resources.freeSettlers : resources[resourceType];

                // Überprüfe, ob die Ressourcen ausreichen
                if (available < costValue) {
                    elementNode.classList.add('insufficient');
                } else {
                    elementNode.classList.remove('insufficient');
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
    .catch(error => console.error('Fehler beim Abrufen der Regeneration in backend.js:', error));
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
        .catch(error => console.error('Fehler beim Abrufen der Daten in backend.js:', error));
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
        .catch(error => console.error('Fehler beim Abrufen der Ressourcen für Farbupdate:', error));
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
                // Fallback to default building types
                const defaultBuildingTypes = ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm'];
                fetchBuildingData(settlementId, defaultBuildingTypes);
            }
        })
        .catch(error => {
            console.error('Error fetching building types:', error);
            // Fallback to default building types
            const defaultBuildingTypes = ['Rathaus', 'Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm'];
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
                        if (woodElement) woodElement.textContent = `${formatNumberWithDots(data.building.costWood)} Holz`;
                        if (stoneElement) stoneElement.textContent = `${formatNumberWithDots(data.building.costStone)} Stein`;
                        if (oreElement) oreElement.textContent = `${formatNumberWithDots(data.building.costOre)} Erz`;
                        if (settlersElement) settlersElement.textContent = `${formatNumberWithDots(data.building.costSettlers)} Siedler`;
                        if (timeElement) timeElement.textContent = `${formatNumberWithDots(data.building.buildTime)}s Bauzeit`;
                        if (buttonElement) buttonElement.textContent = `Upgrade auf ${formatNumberWithDots(data.building.nextLevel)}`;
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
                    console.error(`Fehler beim Abrufen der Daten für ${buildingType}:`, error);
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
                //alert(data.message); // Zeigt Erfolgsnachricht an
                fetchBuildings(settlementId); // Aktualisiere die Gebäudedaten
                fetchResources(settlementId);
                fetchBuildingQueue(settlementId);
            } else {
                alert(data.message); // Zeigt Fehlermeldung an
            }
        })
        .catch(error => {
            console.error('Fehler beim Upgrade des Gebäudes:', error);
            alert('Es ist ein Fehler aufgetreten.');
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
        .catch(error => console.error('Fehler beim Abrufen der Daten in backend.js:', error));
}

function fetchBuildingQueue(settlementId) {
    fetch(`../php/backend.php?settlementId=${settlementId}&getBuildingQueue=True`)
        .then(response => response.json())
        .then(data => {
            const buildingQueueBody = document.getElementById('buildingQueueBody');
            if (!buildingQueueBody) return; // Exit if element doesn't exist
            
            buildingQueueBody.innerHTML = '';

            // Überprüfe, ob data.info.queue vorhanden ist
            if (data.info && data.info.queue && data.info.queue.length > 0) {
                data.info.queue.forEach(item => {
                    const row = document.createElement('tr');

                    row.innerHTML = `
                        <td>${item.buildingType}</td>
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
                emptyRow.innerHTML = '<td colspan="4">Keine Gebäude in der Warteschlange</td>';
                buildingQueueBody.appendChild(emptyRow);
            }
        })
        .catch(error => console.error('Fehler beim Abrufen der BuildingQueue:', error));
}

getSettlementName(settlementId);

document.addEventListener('DOMContentLoaded', () => {
    
    fetchBuildingQueue(settlementId);
    setInterval(() => fetchBuildingQueue(settlementId), 1000);

    // Ressourcen jede Sekunde aktualisieren
    fetchResources(settlementId);
    setInterval(() => fetchResources(settlementId), 1000);

    // Gebäudedaten einmal pro Minute aktualisieren
    fetchBuildings(settlementId);
    setInterval(() => fetchBuildings(settlementId), 5000); // 60000ms = 1 Minute
});

