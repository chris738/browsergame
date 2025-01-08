function fetchData() {
    fetch('backend.php')
        .then(response => response.json())
        .then(data => {
            if (data.resources) {
                document.getElementById('holz').textContent = data.resources.holz;
                document.getElementById('stein').textContent = data.resources.stein;
                document.getElementById('erz').textContent = data.resources.erz;
            }

            if (data.buildings) {
                const buildings = data.buildings;

                // Dynamische Verarbeitung der Gebäude
                Object.keys(buildings).forEach(buildingKey => {
                    const building = buildings[buildingKey];

                    document.getElementById(`${buildingKey}`).textContent = building.level;
                    document.getElementById(`${buildingKey}KostenHolz`).textContent = building.upgradeCost.holz + ' Holz';
                    document.getElementById(`${buildingKey}KostenStein`).textContent = building.upgradeCost.stein + ' Stein';
                    document.getElementById(`${buildingKey}KostenErz`).textContent = building.upgradeCost.erz + ' Erz';
                });
            }
        })
        .catch(error => console.error('Fehler beim Abrufen der Daten:', error));
}

function upgradeBuilding(building) {
    fetch('backend.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ building })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                fetchData();
            } else {
                alert(data.message);
            }
        })
        .catch(error => console.error('Fehler beim Upgrade:', error));
}

document.addEventListener('DOMContentLoaded', fetchData);
