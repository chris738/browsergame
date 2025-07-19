<?php
require_once 'php/database.php';

// Get the current settlement ID from URL parameter
$currentSettlementId = $_GET['settlementId'] ?? 1;

// Initialize database and fetch real settlement data
$database = new Database();
$mapData = [];
$currentPlayerId = null;

try {
    // Check if database is connected
    if ($database->isConnected()) {
        // Get the current player ID from the selected settlement
        $currentSettlement = $database->getAllSettlements();
        foreach ($currentSettlement as $settlement) {
            if ($settlement['settlementId'] == $currentSettlementId) {
                $currentPlayerId = $settlement['playerId'] ?? null;
                break;
            }
        }
        
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
    // Fallback to demo settlement when database fails - showing only the current settlement
    error_log("Map data fetch failed: " . $e->getMessage());
    $currentPlayerId = 1; // Demo player ID
    $mapData = [
        [
            'settlementId' => $currentSettlementId,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Test Settlement',
            'playerId' => $currentPlayerId,
            'playerName' => 'Player'
        ]
    ];
}

// If no settlements found, show at least the current settlement
if (empty($mapData)) {
    $currentPlayerId = 1;
    $mapData = [
        [
            'settlementId' => $currentSettlementId,
            'xCoordinate' => 0,
            'yCoordinate' => 0,
            'name' => 'Fallback Settlement',
            'playerId' => $currentPlayerId,
            'playerName' => 'Player'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map - Settlement Building</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <script src="js/backend.js" defer></script>
</head>
<body>
    <?php include 'php/navigation.php'; ?>
    
    <main class="main-content">
        <h2>Map View</h2>
        <p>Here you can see nearby settlements:</p>
        
        <div class="map-controls">
            <button id="zoomIn" class="map-btn">🔍 Zoom In</button>
            <button id="zoomOut" class="map-btn">🔍 Zoom Out</button>
            <button id="resetView" class="map-btn">🏠 Reset View</button>
            <span class="zoom-indicator">Zoom: <span id="zoomLevel">100%</span></span>
        </div>
        
        <div class="map-container-fullscreen" id="mapContainer">
            <!-- X-axis coordinate labels (top) -->
            <div class="map-x-coordinates" id="mapXCoordinates"></div>
            
            <!-- Y-axis coordinate labels (left) -->
            <div class="map-y-coordinates" id="mapYCoordinates"></div>
            
            <div class="map-grid" id="mapGrid">
                <?php foreach ($mapData as $settlement): ?>
                    <?php 
                        // Determine settlement type for CSS class and status
                        $settlementClass = 'settlement-icon';
                        $statusClass = '';
                        
                        if ($settlement['settlementId'] == $currentSettlementId) {
                            $statusClass = 'status-white'; // Selected settlement - white dot
                        } elseif ($settlement['playerId'] == $currentPlayerId) {
                            $statusClass = 'status-black'; // Own settlements - black dot
                        } else {
                            $statusClass = 'status-red'; // Other settlements - red dot
                        }
                    ?>
                    <div 
                        class="<?= $settlementClass ?> <?= $statusClass ?>" 
                        data-x="<?= $settlement['xCoordinate'] ?>"
                        data-y="<?= $settlement['yCoordinate'] ?>"
                        data-settlement-id="<?= $settlement['settlementId'] ?>"
                        data-settlement-name="<?= htmlspecialchars($settlement['name']) ?>"
                        data-player-name="<?= htmlspecialchars($settlement['playerName']) ?>"
                        data-player-id="<?= $settlement['playerId'] ?>"
                        data-is-own="<?= ($settlement['playerId'] == $currentPlayerId) ? 'true' : 'false' ?>"
                        data-is-current="<?= ($settlement['settlementId'] == $currentSettlementId) ? 'true' : 'false' ?>"
                        title="<?= htmlspecialchars($settlement['name']) ?> (<?= $settlement['xCoordinate'] ?>, <?= $settlement['yCoordinate'] ?>) - Player: <?= htmlspecialchars($settlement['playerName']) ?>"
                        onclick="showSettlementInfo(this)">
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
                <span>Selected Settlement</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon">
                    <div class="settlement-icon status-black">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                </div>
                <span>Your Settlements</span>
            </div>
            <div class="legend-item">
                <div class="legend-icon">
                    <div class="settlement-icon status-red">
                        <div class="settlement-base">🏘️</div>
                        <div class="status-indicator"></div>
                    </div>
                </div>
                <span>Other Settlements</span>
            </div>
        </div>
    </main>

    <!-- Settlement Info Panel -->
    <div class="settlement-info-panel-overlay" id="settlementInfoOverlay" onclick="closeSettlementInfo()"></div>
    <div class="settlement-info-panel" id="settlementInfoPanel">
        <div class="settlement-info-panel-header">
            <h3 id="settlementInfoTitle">Settlement Information</h3>
            <button class="settlement-info-panel-close" onclick="closeSettlementInfo()">&times;</button>
        </div>
        <div class="settlement-info-panel-content" id="settlementInfoContent">
            <!-- Content will be populated by JavaScript -->
        </div>
    </div>

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
            generateCoordinateLabels();
            positionSettlements();
            centerMap();
        }
        
        // Generate coordinate labels for X and Y axes
        function generateCoordinateLabels() {
            const xCoordinatesContainer = document.getElementById('mapXCoordinates');
            const yCoordinatesContainer = document.getElementById('mapYCoordinates');
            
            // Clear existing labels
            xCoordinatesContainer.innerHTML = '';
            yCoordinatesContainer.innerHTML = '';
            
            // Generate X-axis labels (top) - range from -20 to 20, show every 5
            for (let x = -20; x <= 20; x += 5) {
                const label = document.createElement('div');
                label.className = 'coordinate-label x-label';
                label.textContent = x;
                const pixelX = (x + 20) * 40 + 20; // Same calculation as settlement positioning
                label.style.left = pixelX + 'px';
                xCoordinatesContainer.appendChild(label);
            }
            
            // Generate Y-axis labels (left) - range from 20 to -20 (inverted), show every 5
            for (let y = 20; y >= -20; y -= 5) {
                const label = document.createElement('div');
                label.className = 'coordinate-label y-label';
                label.textContent = y;
                const pixelY = (20 - y) * 40 + 20; // Same calculation as settlement positioning
                label.style.top = pixelY + 'px';
                yCoordinatesContainer.appendChild(label);
            }
        }
        
        // Position settlements based on coordinates
        function positionSettlements() {
            const settlements = document.querySelectorAll('.settlement-icon');
            settlements.forEach(settlement => {
                const x = parseInt(settlement.dataset.x);
                const y = parseInt(settlement.dataset.y);
                
                // Convert coordinates to pixel positions (center settlements within grid squares)
                // Place settlements in the center of grid squares rather than on grid line intersections
                const pixelX = (x + 20) * 40 + 20; // 40px per grid unit, offset by 20 to center map, +20 to center within square
                const pixelY = (20 - y) * 40 + 20; // Invert Y axis, offset by 20 to center map, +20 to center within square
                
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
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', initializeMap);
        
        // Settlement info panel functions
        function showSettlementInfo(settlementElement) {
            const settlementId = settlementElement.dataset.settlementId;
            const settlementName = settlementElement.dataset.settlementName;
            const playerName = settlementElement.dataset.playerName;
            const playerId = settlementElement.dataset.playerId;
            const isOwn = settlementElement.dataset.isOwn === 'true';
            const isCurrent = settlementElement.dataset.isCurrent === 'true';
            const xCoord = settlementElement.dataset.x;
            const yCoord = settlementElement.dataset.y;
            
            const title = document.getElementById('settlementInfoTitle');
            const content = document.getElementById('settlementInfoContent');
            const panel = document.getElementById('settlementInfoPanel');
            const overlay = document.getElementById('settlementInfoOverlay');
            
            title.textContent = `${settlementName} - Settlement Information`;
            
            let statusText, statusClass;
            if (isCurrent) {
                statusText = 'Selected Settlement';
                statusClass = 'own-settlement';
            } else if (isOwn) {
                statusText = 'Your Settlement';
                statusClass = 'own-settlement';
            } else {
                statusText = 'Foreign Settlement';
                statusClass = 'foreign-settlement';
            }
            
            let actionsHtml = '';
            if (isOwn) {
                actionsHtml = `
                    <div class="actions">
                        <a href="index.php?settlementId=${settlementId}" class="action-button manage">
                            🏛️ Manage Buildings
                        </a>
                        <a href="market.php?settlementId=${settlementId}" class="action-button">
                            ⚖️ Visit Market
                        </a>
                    </div>
                `;
            } else {
                actionsHtml = `
                    <div class="actions">
                        <a href="market.php?settlementId=<?= $currentSettlementId ?>" class="action-button">
                            ⚖️ Visit Market
                        </a>
                    </div>
                `;
            }
            
            content.innerHTML = `
                <div class="settlement-basic-info">
                    <h4>${settlementName}</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">👤 Owner:</span>
                            <span class="info-value">${playerName}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">🗺️ Location:</span>
                            <span class="info-value">(${xCoord}, ${yCoord})</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ℹ️ Status:</span>
                            <span class="info-value ${statusClass}">${statusText}</span>
                        </div>
                    </div>
                </div>
                ${actionsHtml}
            `;
            
            // Show the panel
            panel.classList.add('show');
            overlay.classList.add('show');
        }
        
        function closeSettlementInfo() {
            const panel = document.getElementById('settlementInfoPanel');
            const overlay = document.getElementById('settlementInfoOverlay');
            
            panel.classList.remove('show');
            overlay.classList.remove('show');
        }
        
        // Close panel with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeSettlementInfo();
            }
        });
    </script>
</body>
</html>