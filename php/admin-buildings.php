<?php
session_start();
require_once 'database.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../admin.php');
    exit;
}

$database = new Database();
$buildingConfigs = [];
$databaseError = null;

// Get building configurations
try {
    if ($database->isConnected()) {
        $buildingConfigs = $database->getAllBuildingConfigs();
    } else {
        $databaseError = "Database connection not available";
    }
} catch (Exception $e) {
    $databaseError = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebäude-Konfiguration - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../js/translations.js"></script>
    <script src="../js/admin-buildings.js" defer></script>
</head>
<body>
    <?php include 'admin-navigation.php'; ?>

    <?php if ($databaseError): ?>
        <div class="error-message"><?= htmlspecialchars($databaseError) ?></div>
    <?php endif; ?>

    <div class="admin-container">
        <!-- Building Configuration Management -->
        <section class="admin-section">
            <h2>Gebäude-Konfiguration</h2>
            <p>Hier können Sie die Werte für Gebäude und deren Aufstufungen verwalten.</p>
            
            <div class="admin-controls">
                <button id="refreshBuildings" class="admin-btn">Aktualisieren</button>
                <button id="createBuildingConfig" class="admin-btn">Neue Konfiguration</button>
                <button id="exportConfigs" class="admin-btn">Exportieren</button>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="form-group">
                    <label for="buildingTypeFilter">Gebäudetyp:</label>
                    <select id="buildingTypeFilter">
                        <option value="">Alle</option>
                        <!-- Options will be populated dynamically by JavaScript -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="levelFilter">Level:</label>
                    <input type="number" id="levelFilter" min="1" max="50" placeholder="Alle">
                </div>
            </div>

            <div class="table-container">
                <table id="buildingConfigsTable">
                    <thead>
                        <tr>
                            <th>Gebäudetyp</th>
                            <th>Level</th>
                            <th>Holz-Kosten</th>
                            <th>Stein-Kosten</th>
                            <th>Erz-Kosten</th>
                            <th>Siedler</th>
                            <th>Produktionsrate</th>
                            <th>Bauzeit (s)</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="buildingConfigsTableBody">
                        <?php if (empty($buildingConfigs) && !$databaseError): ?>
                            <tr><td colspan="9">Loading...</td></tr>
                        <?php elseif ($databaseError): ?>
                            <tr><td colspan="9">Daten können nicht geladen werden</td></tr>
                        <?php else: ?>
                            <?php foreach ($buildingConfigs as $config): ?>
                                <tr data-building-type="<?= htmlspecialchars($config['buildingType']) ?>" 
                                    data-level="<?= htmlspecialchars($config['level']) ?>">
                                    <td><?= htmlspecialchars($config['buildingType']) ?></td>
                                    <td><?= htmlspecialchars($config['level']) ?></td>
                                    <td><?= number_format($config['costWood'], 2) ?></td>
                                    <td><?= number_format($config['costStone'], 2) ?></td>
                                    <td><?= number_format($config['costOre'], 2) ?></td>
                                    <td><?= number_format($config['settlers'], 2) ?></td>
                                    <td><?= number_format($config['productionRate'], 2) ?></td>
                                    <td><?= htmlspecialchars($config['buildTime']) ?></td>
                                    <td>
                                        <button class="action-btn edit" 
                                                onclick="editBuildingConfig('<?= htmlspecialchars($config['buildingType']) ?>', <?= $config['level'] ?>)">
                                            Bearbeiten
                                        </button>
                                        <button class="action-btn delete" 
                                                onclick="deleteBuildingConfig('<?= htmlspecialchars($config['buildingType']) ?>', <?= $config['level'] ?>)">
                                            Löschen
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Quick Level Generator -->
        <section class="admin-section">
            <h2>Level-Generator</h2>
            <p>Generiere automatisch Konfigurationen für mehrere Level eines Gebäudetyps.</p>
            
            <div class="generator-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="genBuildingType">Gebäudetyp:</label>
                        <select id="genBuildingType" required>
                            <option value="">Wählen...</option>
                            <!-- Options will be populated dynamically by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="genStartLevel">Start Level:</label>
                        <input type="number" id="genStartLevel" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="genEndLevel">End Level:</label>
                        <input type="number" id="genEndLevel" min="1" value="10" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="genBaseCostWood">Basis Holz-Kosten:</label>
                        <input type="number" id="genBaseCostWood" step="0.01" value="100" required>
                    </div>
                    <div class="form-group">
                        <label for="genBaseCostStone">Basis Stein-Kosten:</label>
                        <input type="number" id="genBaseCostStone" step="0.01" value="100" required>
                    </div>
                    <div class="form-group">
                        <label for="genBaseCostOre">Basis Erz-Kosten:</label>
                        <input type="number" id="genBaseCostOre" step="0.01" value="100" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="genBaseSettlers">Basis Siedler:</label>
                        <input type="number" id="genBaseSettlers" step="0.01" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="genBaseProduction">Basis Produktionsrate:</label>
                        <input type="number" id="genBaseProduction" step="0.01" value="3600" required>
                    </div>
                    <div class="form-group">
                        <label for="genBaseBuildTime">Basis Bauzeit (s):</label>
                        <input type="number" id="genBaseBuildTime" value="30" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="genMultiplier">Multiplikator pro Level:</label>
                        <input type="number" id="genMultiplier" step="0.01" value="1.1" required>
                    </div>
                    <div class="form-group">
                        <label for="genTimeIncrease">Bauzeit-Erhöhung pro Level (s):</label>
                        <input type="number" id="genTimeIncrease" value="10" required>
                    </div>
                </div>
                <button id="generateLevels" class="admin-btn">Level Generieren</button>
            </div>
        </section>
    </div>

    <!-- Edit Building Config Modal -->
    <div id="editBuildingConfigModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Gebäude-Konfiguration bearbeiten</h3>
            <form id="editBuildingConfigForm">
                <input type="hidden" id="editBuildingType" name="buildingType">
                <input type="hidden" id="editLevel" name="level">
                
                <div class="form-group">
                    <label>Gebäudetyp:</label>
                    <span id="editBuildingTypeDisplay"></span>
                </div>
                <div class="form-group">
                    <label>Level:</label>
                    <span id="editLevelDisplay"></span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="editCostWood">Holz-Kosten:</label>
                        <input type="number" id="editCostWood" name="costWood" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editCostStone">Stein-Kosten:</label>
                        <input type="number" id="editCostStone" name="costStone" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editCostOre">Erz-Kosten:</label>
                        <input type="number" id="editCostOre" name="costOre" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editSettlers">Siedler:</label>
                        <input type="number" id="editSettlers" name="settlers" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="editProductionRate">Produktionsrate:</label>
                        <input type="number" id="editProductionRate" name="productionRate" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="editBuildTime">Bauzeit (s):</label>
                        <input type="number" id="editBuildTime" name="buildTime" required>
                    </div>
                </div>
                
                <button type="submit">Speichern</button>
            </form>
        </div>
    </div>

    <!-- Create Building Config Modal -->
    <div id="createBuildingConfigModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Neue Gebäude-Konfiguration erstellen</h3>
            <form id="createBuildingConfigForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="createBuildingType">Gebäudetyp:</label>
                        <select id="createBuildingType" name="buildingType" required>
                            <option value="">Wählen...</option>
                            <!-- Options will be populated dynamically by JavaScript -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="createLevel">Level:</label>
                        <input type="number" id="createLevel" name="level" min="1" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="createCostWood">Holz-Kosten:</label>
                        <input type="number" id="createCostWood" name="costWood" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="createCostStone">Stein-Kosten:</label>
                        <input type="number" id="createCostStone" name="costStone" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="createCostOre">Erz-Kosten:</label>
                        <input type="number" id="createCostOre" name="costOre" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="createSettlers">Siedler:</label>
                        <input type="number" id="createSettlers" name="settlers" step="0.01" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="createProductionRate">Produktionsrate:</label>
                        <input type="number" id="createProductionRate" name="productionRate" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="createBuildTime">Bauzeit (s):</label>
                        <input type="number" id="createBuildTime" name="buildTime" required>
                    </div>
                </div>
                
                <button type="submit">Erstellen</button>
            </form>
        </div>
    </div>

</body>
</html>