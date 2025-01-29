function formatNumberWithDots(number) {
    const roundedNumber = Math.floor(number); // Rundet die Zahl nach unten
    return roundedNumber.toLocaleString('de-DE'); // Formatierung für Deutschland
}

function updateCostColors(resources) {
    const costElements = [
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
                const available = resources[resourceType];

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
    fetch(`backend.php?settlementId=${settlementId}&getRegen=true`)
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
    fetch(`backend.php?settlementId=${settlementId}`)
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

function fetchBuildings(settlementId) {
    const buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk', 'Lager', 'Farm'];

    buildingTypes.forEach(buildingType => {
        fetch(`backend.php?settlementId=${settlementId}&buildingType=${buildingType}`)
            .then(response => response.json())
            .then(data => {
                if (data.building) {
                    const buildingId = buildingType.toLowerCase();
                    document.getElementById(`${buildingId}`).textContent = data.building.level;

                    document.getElementById(`${buildingId}KostenHolz`).textContent = `${formatNumberWithDots(data.building.costWood)} Holz`;
                    document.getElementById(`${buildingId}KostenStein`).textContent = `${formatNumberWithDots(data.building.costStone)} Stein`;
                    document.getElementById(`${buildingId}KostenErz`).textContent = `${formatNumberWithDots(data.building.costOre)} Erz`;
                    document.getElementById(`${buildingId}KostenSiedler`).textContent = `${formatNumberWithDots(data.building.costSettlers)} Siedler`;
                    document.getElementById(`${buildingId}upgradeButton`).textContent = `Upgrade auf ${formatNumberWithDots(data.building.nextLevel)}`;
                }
            })
            .catch(error => console.error(`Fehler beim Abrufen der Daten für ${buildingType}:`, error));
            getRegen(settlementId);
    });
}

function upgradeBuilding(buildingType, settlementId) {
    fetch('backend.php?settlementId=' + settlementId, {
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
    fetch(`backend.php?settlementId=${settlementId}&getSettlementName=True`)
        .then(response => response.json())
        .then(data => {
            if (data.info) {
                document.getElementById('Siedlungsname').textContent = data.info.SettlementName;
            }
        })
        .catch(error => console.error('Fehler beim Abrufen der Daten in backend.js:', error));
}

function fetchBuildingQueue(settlementId) {
    fetch(`backend.php?settlementId=${settlementId}&getBuildingQueue=True`)
        .then(response => response.json())
        .then(data => {
            const buildingQueueBody = document.getElementById('buildingQueueBody');
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

