function formatNumberWithDots(number) {
    return number.toLocaleString('de-DE'); // Lokale Formatierung für Deutschland
}

function fetchResources() {
    const settlementId = 5; // Beispiel-Siedlungs-ID
    fetch(`backend.php?settlementId=${settlementId}`)
        .then(response => response.json())
        .then(data => {
            if (data.resources) {
                document.getElementById('holz').textContent = formatNumberWithDots(data.resources.resources.wood);
                document.getElementById('stein').textContent = formatNumberWithDots(data.resources.resources.stone);
                document.getElementById('erz').textContent = formatNumberWithDots(data.resources.resources.ore);
            }
        })
        .catch(error => console.error('Fehler beim Abrufen der Daten:', error));
}

function fetchBuildings() {
    const settlementId = 5; // Beispiel-Siedlungs-ID
    const buildingTypes = ['Holzfäller', 'Steinbruch', 'Erzbergwerk'];

    buildingTypes.forEach(buildingType => {
        fetch(`backend.php?settlementId=${settlementId}&buildingType=${buildingType}`)
            .then(response => response.json())
            .then(data => {
                if (data.building) {
                    const buildingId = buildingType.toLowerCase();
                    document.getElementById(`${buildingId}`).textContent = data.building.level;
                    document.getElementById(`${buildingId}KostenHolz`).textContent = `${data.building.costWood} Holz`;
                    document.getElementById(`${buildingId}KostenStein`).textContent = `${data.building.costStone} Stein`;
                    document.getElementById(`${buildingId}KostenErz`).textContent = `${data.building.costOre} Erz`;
                }
            })
            .catch(error => console.error(`Fehler beim Abrufen der Daten für ${buildingType}:`, error));
    });
}

function upgradeBuilding(buildingType) {
    const settlementId = 5; // Beispiel-Siedlungs-ID

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
                alert(data.message); // Zeigt Erfolgsnachricht an
                fetchBuildings(); // Aktualisiere die Gebäudedaten
            } else {
                alert(data.message); // Zeigt Fehlermeldung an
            }
        })
        .catch(error => {
            console.error('Fehler beim Upgrade des Gebäudes:', error);
            alert('Es ist ein Fehler aufgetreten.');
        });
}

document.addEventListener('DOMContentLoaded', () => {
    // Ressourcen jede Sekunde aktualisieren
    fetchResources();
    setInterval(fetchResources, 1000);

    // Gebäudedaten einmal pro Minute aktualisieren
    fetchBuildings();
    setInterval(fetchBuildings, 60000); // 60000ms = 1 Minute
});
