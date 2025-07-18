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

// Initialize database and fetch real settlement data
$database = new Database();
$mapData = [];

try {
    // Check if database is connected
    if ($database->isConnected()) {
        // Get all settlements with their coordinates and player information
        $allSettlements = $database->getAllSettlements();
        foreach ($allSettlements as $settlement) {
            if (isset($settlement['xCoordinate']) && isset($settlement['yCoordinate'])) {
                $mapData[] = [
                    'settlementId' => $settlement['settlementId'],
                    'xCoordinate' => $settlement['xCoordinate'],
                    'yCoordinate' => $settlement['yCoordinate'],
                    'name' => $settlement['name'],
                    'playerId' => $settlement['playerId'] ?? null,
                    'playerName' => $settlement['playerName'] ?? 'Unknown'
                ];
            }
        }
    } else {
        throw new Exception("Database not connected");
    }
} catch (Exception $e) {
    // Fallback to demo settlements when database fails
    error_log("Map data fetch failed: " . $e->getMessage());
    $mapData = [
        [
            'settlementId' => 1,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Demo Settlement 1',
            'playerId' => 1,
            'playerName' => 'Player 1'
        ],
        [
            'settlementId' => 2,
            'xCoordinate' => 3,
            'yCoordinate' => 2,
            'name' => 'Demo Settlement 2',
            'playerId' => 2,
            'playerName' => 'Player 2'
        ],
        [
            'settlementId' => 3,
            'xCoordinate' => -2,
            'yCoordinate' => -1,
            'name' => 'Demo Settlement 3',
            'playerId' => 1,
            'playerName' => 'Player 1'
        ]
    ];
}

// If no settlements found, show demo data
if (empty($mapData)) {
    $mapData = [
        [
            'settlementId' => 1,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Fallback Settlement',
            'playerId' => 1,
            'playerName' => 'Admin'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Map View - Settlement Building Game</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="../js/theme-switcher.js"></script>
    <script src="../js/backend.js" defer></script>
</head>
<body>
    <?php include 'admin-navigation.php'; ?>
    
    <main class="admin-map-content">
        <h2>Admin Map View</h2>
        <p>Overview of all settlements in the game world:</p>
        
        <div class="map-controls">
            <button id="zoomIn" class="map-btn">🔍 Zoom In</button>
            <button id="zoomOut" class="map-btn">🔍 Zoom Out</button>
            <button id="resetView" class="map-btn">🏠 Reset View</button>
            <span class="zoom-indicator">Zoom: <span id="zoomLevel">100%</span></span>
        </div>
        
        <div class="map-container-fullscreen" id="mapContainer">
            <div class="map-grid" id="mapGrid">
                <?php foreach ($mapData as $settlement): ?>
                    <?php 
                        // Determine settlement type for CSS class and status
                        $settlementClass = 'settlement-icon';
                        $statusClass = '';
                        
                        // Determine status based on some criteria (for demo purposes)
                        if ($settlement['playerId'] == 1) {
                            $statusClass = 'status-white'; // Own settlements - white dot
                        } elseif ($settlement['playerId'] <= 2) {
                            $statusClass = 'status-black'; // Allied settlements - black dot  
                        } else {
                            $statusClass = 'status-red'; // Enemy settlements - red dot
                        }
                    ?>
                    <div 
                        class="<?= $settlementClass ?> <?= $statusClass ?>" 
                        data-x="<?= $settlement['xCoordinate'] ?>"
                        data-y="<?= $settlement['yCoordinate'] ?>"
                        title="<?= htmlspecialchars($settlement['name']) ?> (<?= $settlement['xCoordinate'] ?>, <?= $settlement['yCoordinate'] ?>) - Player: <?= htmlspecialchars($settlement['playerName']) ?>"
                        onclick="selectSettlement(<?= $settlement['settlementId'] ?>)">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="map-legend">
            <h3>Legend:</h3>
            <div class="legend-item">
                <div class="legend-icon">
                    <div class="settlement-icon status-white">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                </div>
                <span>Own Settlements</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon">
                    <div class="settlement-icon status-black">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                </div>
                <span>Allied Settlements</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon">
                    <div class="settlement-icon status-red">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                </div>
                <span>Enemy Settlements</span>
            </div>
        </div>
    </main>

    <script>
        // Map interaction functionality
        let currentZoom = 1;
        let isPanning = false;
        let startX, startY, scrollLeft, scrollTop;
        
        const mapContainer = document.getElementById('mapContainer');
        const mapGrid = document.getElementById('mapGrid');
        const zoomLevel = document.getElementById('zoomLevel');
        
        // Initialize map positioning
        function initializeMap() {
            positionSettlements();
            centerMap();
        }
        
        // Position settlements based on coordinates
        function positionSettlements() {
            const settlements = document.querySelectorAll('.settlement-icon');
            settlements.forEach(settlement => {
                const x = parseInt(settlement.dataset.x);
                const y = parseInt(settlement.dataset.y);
                
                // Convert coordinates to pixel positions (center map at 0,0)
                const pixelX = (x + 20) * 40; // 40px per grid unit, offset by 20 to center
                const pixelY = (20 - y) * 40; // Invert Y axis, offset by 20 to center
                
                settlement.style.left = pixelX + 'px';
                settlement.style.top = pixelY + 'px';
            });
        }
        
        // Center the map view
        function centerMap() {
            const containerRect = mapContainer.getBoundingClientRect();
            const gridRect = mapGrid.getBoundingClientRect();
            
            mapContainer.scrollLeft = (gridRect.width - containerRect.width) / 2;
            mapContainer.scrollTop = (gridRect.height - containerRect.height) / 2;
        }
        
        // Zoom functionality
        document.getElementById('zoomIn').addEventListener('click', () => {
            currentZoom = Math.min(currentZoom * 1.2, 3);
            updateZoom();
        });
        
        document.getElementById('zoomOut').addEventListener('click', () => {
            currentZoom = Math.max(currentZoom / 1.2, 0.3);
            updateZoom();
        });
        
        document.getElementById('resetView').addEventListener('click', () => {
            currentZoom = 1;
            updateZoom();
            centerMap();
        });
        
        function updateZoom() {
            mapGrid.style.transform = `scale(${currentZoom})`;
            zoomLevel.textContent = Math.round(currentZoom * 100) + '%';
        }
        
        // Pan functionality
        mapContainer.addEventListener('mousedown', (e) => {
            isPanning = true;
            startX = e.pageX - mapContainer.offsetLeft;
            startY = e.pageY - mapContainer.offsetTop;
            scrollLeft = mapContainer.scrollLeft;
            scrollTop = mapContainer.scrollTop;
            mapContainer.style.cursor = 'grabbing';
        });
        
        mapContainer.addEventListener('mouseleave', () => {
            isPanning = false;
            mapContainer.style.cursor = 'grab';
        });
        
        mapContainer.addEventListener('mouseup', () => {
            isPanning = false;
            mapContainer.style.cursor = 'grab';
        });
        
        mapContainer.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            e.preventDefault();
            const x = e.pageX - mapContainer.offsetLeft;
            const y = e.pageY - mapContainer.offsetTop;
            const walkX = (x - startX) * 2;
            const walkY = (y - startY) * 2;
            mapContainer.scrollLeft = scrollLeft - walkX;
            mapContainer.scrollTop = scrollTop - walkY;
        });
        
        // Settlement selection
        function selectSettlement(settlementId) {
            alert(`Selected settlement ID: ${settlementId}\nIn a full implementation, this would show settlement details or allow administration actions.`);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initializeMap);
    </script>
</body>
</html>