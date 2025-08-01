<?php
    require_once 'php/database.php';
    require_once 'php/emoji-config.php';
    
    $database = new Database();
    $settlementId = $_GET['settlementId'] ?? 1;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spritverbrauch Tracking</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/emoji-config.js"></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><?= EmojiConfig::getUIEmoji('fuel') ?> Spritverbrauch Tracking</h1>
            <p>Verwalten Sie Ihren Kraftstoffverbrauch und die Kosten</p>
        </div>

        <!-- Add New Fuel Record Form -->
        <section class="content-section">
            <h2><?= EmojiConfig::getUIEmoji('plus') ?> Neuen Eintrag hinzufügen</h2>
            
            <form id="fuelForm" class="fuel-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="fuelDate">Datum (DD.MM.JJJJ):</label>
                        <input type="text" 
                               id="fuelDate" 
                               name="fuelDate" 
                               placeholder="DD.MM.JJJJ" 
                               pattern="\d{2}\.\d{2}\.\d{4}"
                               title="Bitte verwenden Sie das Format DD.MM.JJJJ"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fuelType">Spritart:</label>
                        <select id="fuelType" name="fuelType" required>
                            <option value="">-- Spritart wählen --</option>
                            <option value="Super">Super</option>
                            <option value="Super E10">Super E10</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Super Premium">Super Premium</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="pricePerLiter">Preis pro Liter (€):</label>
                        <input type="text" 
                               id="pricePerLiter" 
                               name="pricePerLiter" 
                               placeholder="1.650" 
                               pattern="\d+\.\d{3}"
                               title="Verwenden Sie Punkt als Dezimaltrennzeichen (z.B. 1.650)"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="liters">Getankte Liter:</label>
                        <input type="text" 
                               id="liters" 
                               name="liters" 
                               placeholder="45.250" 
                               pattern="\d+\.\d{1,3}"
                               title="Verwenden Sie Punkt als Dezimaltrennzeichen (z.B. 45.250)"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="displayedConsumption">Angezeigter Verbrauch (L/100km):</label>
                        <input type="text" 
                               id="displayedConsumption" 
                               name="displayedConsumption" 
                               placeholder="7.50" 
                               pattern="\d+\.\d{1,2}"
                               title="Verwenden Sie Punkt als Dezimaltrennzeichen (z.B. 7.50)"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="engineRuntime">Motor Laufzeit (Minuten):</label>
                        <input type="number" 
                               id="engineRuntime" 
                               name="engineRuntime" 
                               min="1" 
                               max="9999"
                               placeholder="120"
                               required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= EmojiConfig::getUIEmoji('save') ?> Eintrag speichern
                    </button>
                    <button type="reset" class="btn btn-secondary">
                        <?= EmojiConfig::getUIEmoji('reset') ?> Zurücksetzen
                    </button>
                </div>
            </form>
        </section>

        <!-- Fuel Records Table -->
        <section class="content-section">
            <h2><?= EmojiConfig::getUIEmoji('list') ?> Spritverbrauch Übersicht</h2>
            
            <div class="table-container">
                <table id="fuelTable" class="data-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Spritart</th>
                            <th>Preis/Liter</th>
                            <th>Liter</th>
                            <th>Gesamtkosten</th>
                            <th>Verbrauch</th>
                            <th>Laufzeit</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="fuelTableBody">
                        <tr>
                            <td colspan="8" class="loading">Lade Daten...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="content-section">
            <h2><?= EmojiConfig::getUIEmoji('chart') ?> Statistiken</h2>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Gesamt getankt</h3>
                    <div class="stat-value" id="totalLiters">0 L</div>
                </div>
                <div class="stat-card">
                    <h3>Gesamtkosten</h3>
                    <div class="stat-value" id="totalCosts">0,00 €</div>
                </div>
                <div class="stat-card">
                    <h3>Ø Verbrauch</h3>
                    <div class="stat-value" id="avgConsumption">0,00 L/100km</div>
                </div>
                <div class="stat-card">
                    <h3>Ø Preis/Liter</h3>
                    <div class="stat-value" id="avgPrice">0,000 €</div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // German date formatting and validation
        function formatDateToGerman(date) {
            if (!date) return '';
            const d = new Date(date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return `${day}.${month}.${year}`;
        }

        function parseGermanDate(dateStr) {
            if (!dateStr) return null;
            const parts = dateStr.split('.');
            if (parts.length !== 3) return null;
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1; // Month is 0-based
            const year = parseInt(parts[2], 10);
            return new Date(year, month, day);
        }

        // German number formatting
        function formatGermanNumber(number, decimals = 2) {
            if (number === null || number === undefined || isNaN(number)) {
                return '0' + ',00'.substring(0, decimals + 1);
            }
            return parseFloat(number).toFixed(decimals).replace('.', ',');
        }

        function parseGermanNumber(numberStr) {
            return parseFloat(numberStr.replace(',', '.'));
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set today's date in German format
            document.getElementById('fuelDate').value = formatDateToGerman(new Date());
            
            // Load existing fuel records
            loadFuelRecords();
            
            // Setup form submission
            document.getElementById('fuelForm').addEventListener('submit', handleFormSubmit);
        });

        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            // Parse German date format
            const germanDate = formData.get('fuelDate');
            const parsedDate = parseGermanDate(germanDate);
            
            if (!parsedDate) {
                showMessage('Ungültiges Datumsformat. Bitte verwenden Sie DD.MM.JJJJ', 'error');
                return;
            }
            
            const data = {
                settlementId: settlementId,
                date: parsedDate.toISOString().split('T')[0], // Send as YYYY-MM-DD
                fuelType: formData.get('fuelType'),
                pricePerLiter: parseFloat(formData.get('pricePerLiter')),
                liters: parseFloat(formData.get('liters')),
                displayedConsumption: parseFloat(formData.get('displayedConsumption')),
                engineRuntime: parseInt(formData.get('engineRuntime'))
            };

            try {
                const response = await fetch('php/fuel-backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'addFuelRecord',
                        ...data
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showMessage('Eintrag erfolgreich gespeichert!', 'success');
                    event.target.reset();
                    document.getElementById('fuelDate').value = formatDateToGerman(new Date());
                    loadFuelRecords();
                } else {
                    showMessage('Fehler beim Speichern: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error saving fuel record:', error);
                showMessage('Fehler beim Speichern des Eintrags', 'error');
            }
        }

        async function loadFuelRecords() {
            try {
                const response = await fetch(`php/fuel-backend.php?action=getFuelRecords&settlementId=${settlementId}`);
                const result = await response.json();
                
                if (result.success) {
                    displayFuelRecords(result.records);
                    updateStatistics(result.statistics);
                } else {
                    console.error('Failed to load fuel records:', result.message);
                }
            } catch (error) {
                console.error('Error loading fuel records:', error);
            }
        }

        function displayFuelRecords(records) {
            const tbody = document.getElementById('fuelTableBody');
            
            if (records.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="no-data">Keine Einträge vorhanden</td></tr>';
                return;
            }

            tbody.innerHTML = records.map(record => `
                <tr>
                    <td>${formatDateToGerman(record.date)}</td>
                    <td>${record.fuelType}</td>
                    <td>${formatGermanNumber(record.pricePerLiter, 3)} €</td>
                    <td>${formatGermanNumber(record.liters, 3)} L</td>
                    <td>${formatGermanNumber(record.totalCost, 2)} €</td>
                    <td>${formatGermanNumber(record.displayedConsumption, 2)} L/100km</td>
                    <td>${record.engineRuntime} min</td>
                    <td>
                        <button onclick="deleteFuelRecord(${record.id})" class="btn btn-danger btn-small">
                            Löschen
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function updateStatistics(stats) {
            document.getElementById('totalLiters').textContent = formatGermanNumber(stats.totalLiters || 0, 3) + ' L';
            document.getElementById('totalCosts').textContent = formatGermanNumber(stats.totalCosts || 0, 2) + ' €';
            document.getElementById('avgConsumption').textContent = formatGermanNumber(stats.avgConsumption || 0, 2) + ' L/100km';
            document.getElementById('avgPrice').textContent = formatGermanNumber(stats.avgPrice || 0, 3) + ' €';
        }

        async function deleteFuelRecord(id) {
            if (!confirm('Möchten Sie diesen Eintrag wirklich löschen?')) {
                return;
            }

            try {
                const response = await fetch('php/fuel-backend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'deleteFuelRecord',
                        id: id
                    })
                });

                const result = await response.json();
                
                if (result.success) {
                    showMessage('Eintrag gelöscht', 'success');
                    loadFuelRecords();
                } else {
                    showMessage('Fehler beim Löschen: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Error deleting fuel record:', error);
                showMessage('Fehler beim Löschen', 'error');
            }
        }

        function showMessage(message, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message-${type}`;
            messageDiv.textContent = message;
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 10px 20px;
                border-radius: 4px;
                z-index: 1000;
                background: ${type === 'success' ? '#4CAF50' : '#f44336'};
                color: white;
            `;
            
            document.body.appendChild(messageDiv);
            
            setTimeout(() => {
                messageDiv.remove();
            }, 3000);
        }
    </script>

    <style>
        .fuel-form {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(var(--accent-color-rgb), 0.2);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover-color);
        }

        .btn-secondary {
            background: var(--border-color);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--text-secondary);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .table-container {
            overflow-x: auto;
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--bg-secondary);
        }

        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background: var(--bg-primary);
            font-weight: 600;
            color: var(--text-primary);
        }

        .data-table tr:hover {
            background: var(--bg-primary);
        }

        .loading,
        .no-data {
            text-align: center;
            color: var(--text-secondary);
            font-style: italic;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-color);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</body>
</html>