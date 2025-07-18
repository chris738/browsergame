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

// Admin dashboard
$database = new Database();

// Get overview stats
try {
    $playerCount = $database->getPlayerCount();
    $settlementCount = $database->getSettlementCount();
    $activeQueues = $database->getActiveQueuesCount();
    
    if (!$database->isConnected()) {
        $databaseError = "Database connection not available (showing demo data)";
    }
} catch (Exception $e) {
    // Use mock data for demonstration when database is not available
    $playerCount = 5;
    $settlementCount = 8;
    $activeQueues = 3;
    $databaseError = "Database connection failed (using demo data)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Overview - Settlement Building Game</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../js/theme-switcher.js"></script>
    <script src="../js/admin.js" defer></script>
</head>
<body>
    <?php include 'admin-navigation.php'; ?>

    <?php if (isset($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($databaseError)): ?>
        <div class="error-message"><?= htmlspecialchars($databaseError) ?></div>
    <?php endif; ?>

    <div class="admin-container">
        <!-- Statistics Overview -->
        <section class="admin-section">
            <h2>System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Players</h3>
                    <div class="stat-number"><?= $playerCount ?? 'N/A' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Settlements</h3>
                    <div class="stat-number"><?= $settlementCount ?? 'N/A' ?></div>
                </div>
                <div class="stat-card">
                    <h3>Active Build Queues</h3>
                    <div class="stat-number"><?= $activeQueues ?? 'N/A' ?></div>
                </div>
            </div>
        </section>

        <!-- Player Management -->
        <section class="admin-section">
            <h2>Player Management</h2>
            <div class="admin-controls">
                <button id="refreshPlayers" class="admin-btn">Refresh Players</button>
                <button id="createPlayer" class="admin-btn">Create New Player</button>
            </div>
            <div class="table-container">
                <table id="playersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Points</th>
                            <th>Gold</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="playersTableBody">
                        <tr><td colspan="5">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Settlement Management -->
        <section class="admin-section">
            <h2>Settlement Management</h2>
            <div class="admin-controls">
                <button id="refreshSettlements" class="admin-btn">Refresh Settlements</button>
            </div>
            <div class="table-container">
                <table id="settlementsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Player</th>
                            <th>Wood</th>
                            <th>Stone</th>
                            <th>Ore</th>
                            <th>Position</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="settlementsTableBody">
                        <tr><td colspan="8">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Building Queue Management -->
        <section class="admin-section">
            <h2>Building Queue Management</h2>
            <div class="admin-controls">
                <button id="refreshQueues" class="admin-btn">Refresh Queues</button>
                <button id="clearAllQueues" class="admin-btn danger">Clear All Queues</button>
            </div>
            <div class="table-container">
                <table id="queuesTable">
                    <thead>
                        <tr>
                            <th>Queue ID</th>
                            <th>Settlement</th>
                            <th>Building Type</th>
                            <th>Level</th>
                            <th>Progress</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="queuesTableBody">
                        <tr><td colspan="7">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Modals -->
    <div id="createPlayerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Create New Player</h3>
            <form id="createPlayerForm">
                <div class="form-group">
                    <label for="playerName">Player Name:</label>
                    <input type="text" id="playerName" name="playerName" required>
                </div>
                <div class="form-group">
                    <label for="playerGold">Initial Gold:</label>
                    <input type="number" id="playerGold" name="playerGold" value="500">
                </div>
                <button type="submit">Create Player</button>
            </form>
        </div>
    </div>

    <div id="editResourcesModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Edit Settlement Resources</h3>
            <form id="editResourcesForm">
                <input type="hidden" id="editSettlementId" name="settlementId">
                <div class="form-group">
                    <label for="editWood">Wood:</label>
                    <input type="number" id="editWood" name="wood" step="0.01">
                </div>
                <div class="form-group">
                    <label for="editStone">Stone:</label>
                    <input type="number" id="editStone" name="stone" step="0.01">
                </div>
                <div class="form-group">
                    <label for="editOre">Ore:</label>
                    <input type="number" id="editOre" name="ore" step="0.01">
                </div>
                <button type="submit">Update Resources</button>
            </form>
        </div>
    </div>

</body>
</html>