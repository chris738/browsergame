<?php
require_once 'php/database.php';

// Get the current settlement ID from URL parameter
$currentSettlementId = $_GET['settlementId'] ?? 1;

// Check if we're in target selection mode
$isSelectTargetMode = isset($_GET['mode']) && $_GET['mode'] === 'selectTarget';
$returnTo = $_GET['returnTo'] ?? null;

// Initialize database and fetch real settlement data
$database = new Database();
$mapData = [];
$currentPlayerId = null;

try {
    // Get all settlements - the repository will handle database fallback  
    $allSettlements = $database->getAllSettlements();
    
    // Get the current player ID from the selected settlement
    foreach ($allSettlements as $settlement) {
        if ($settlement['settlementId'] == $currentSettlementId) {
            $currentPlayerId = $settlement['playerId'] ?? null;
            break;
        }
    }
    
    // Process settlements with their coordinates and player information
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
} catch (Exception $e) {
    // Fallback if something goes wrong
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
        <?php if ($isSelectTargetMode): ?>
        <div class="target-selection-mode-header">
            <h2>🎯 Select Target for Attack</h2>
            <p>Click on an enemy settlement (red dot) to select it as your attack target.</p>
            <div class="selection-mode-controls">
                <?php if ($returnTo === 'battle'): ?>
                    <a href="battle.php?settlementId=<?= $currentSettlementId ?>" class="cancel-selection-btn">
                        ← Cancel and Return to Battle
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <h2>Map View</h2>
        <p>Here you can see nearby settlements:</p>
        <?php endif; ?>
        
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
                        data-x="<?= isset($settlement['xCoordinate']) ? (int)$settlement['xCoordinate'] : 0 ?>"
                        data-y="<?= isset($settlement['yCoordinate']) ? (int)$settlement['yCoordinate'] : 0 ?>"
                        data-settlement-id="<?= $settlement['settlementId'] ?>"
                        data-settlement-name="<?= htmlspecialchars($settlement['name']) ?>"
                        data-player-name="<?= htmlspecialchars($settlement['playerName']) ?>"
                        data-player-id="<?= $settlement['playerId'] ?>"
                        data-is-own="<?= ($settlement['playerId'] == $currentPlayerId) ? 'true' : 'false' ?>"
                        data-is-current="<?= ($settlement['settlementId'] == $currentSettlementId) ? 'true' : 'false' ?>"
                        title="<?= htmlspecialchars($settlement['name']) ?> (<?= isset($settlement['xCoordinate']) ? $settlement['xCoordinate'] : 0 ?>, <?= isset($settlement['yCoordinate']) ? $settlement['yCoordinate'] : 0 ?>) - Player: <?= htmlspecialchars($settlement['playerName']) ?>"
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
            positionSettlements();
            centerMap();
            // Generate initial coordinates for visible area
            updateCoordinateLabelsPosition();
        }
        
        // Update coordinate label positions based on current scroll position and zoom
        function updateCoordinateLabelsPosition() {
            const xCoordinatesContainer = document.getElementById('mapXCoordinates');
            const yCoordinatesContainer = document.getElementById('mapYCoordinates');
            const scrollLeft = mapContainer.scrollLeft;
            const scrollTop = mapContainer.scrollTop;
            
            // Clear existing labels
            xCoordinatesContainer.innerHTML = '';
            yCoordinatesContainer.innerHTML = '';
            
            // Map grid constants
            const cellSize = 40; // Each grid cell is 40px (base size)
            const scaledCellSize = cellSize * currentZoom; // Account for zoom
            const coordinateOffset = 100; // Coordinate system is offset by 100 in both directions for larger map
            
            // Calculate which coordinates are currently visible
            // For X coordinates (horizontal)
            const leftmostPixel = scrollLeft;
            const rightmostPixel = scrollLeft + mapContainer.clientWidth;
            // Account for the extra 20px offset in settlement positioning and zoom scaling
            const leftmostCoord = Math.floor(((leftmostPixel - 20 * currentZoom) / scaledCellSize) - coordinateOffset);
            const rightmostCoord = Math.ceil(((rightmostPixel - 20 * currentZoom) / scaledCellSize) - coordinateOffset);
            
            // For Y coordinates (vertical) - Y axis is inverted
            const topmostPixel = scrollTop;
            const bottommostPixel = scrollTop + mapContainer.clientHeight;
            // Account for the grid margin (25px) and settlement positioning offset (20px) and zoom scaling
            const gridOffset = 25 + 20 * currentZoom; // 25px margin-top + 20px positioning offset * zoom
            const topmostCoord = coordinateOffset - Math.floor((topmostPixel - gridOffset) / scaledCellSize);
            const bottommostCoord = coordinateOffset - Math.ceil((bottommostPixel - gridOffset) / scaledCellSize);
            
            // Calculate appropriate step size based on zoom level
            const baseStepX = Math.max(1, Math.ceil((rightmostCoord - leftmostCoord) / 15)); // Show ~15 labels max
            const baseStepY = Math.max(1, Math.ceil((topmostCoord - bottommostCoord) / 12)); // Show ~12 labels max
            
            // Adjust step size based on zoom - show fewer labels when zoomed out, more when zoomed in
            const xStep = currentZoom > 1 ? Math.max(1, Math.floor(baseStepX / currentZoom)) : baseStepX;
            const yStep = currentZoom > 1 ? Math.max(1, Math.floor(baseStepY / currentZoom)) : baseStepY;
            
            // Generate X-axis labels
            for (let x = Math.floor(leftmostCoord / xStep) * xStep; x <= rightmostCoord; x += xStep) {
                const label = document.createElement('div');
                label.className = 'coordinate-label x-label';
                label.textContent = x;
                // Calculate pixel position for this coordinate relative to scroll position
                // Match the settlement positioning formula: (x + coordinateOffset) * cellSize + 20, scaled by zoom
                const pixelX = (x + coordinateOffset) * scaledCellSize + (scaledCellSize / 2) + 20 * currentZoom - scrollLeft;
                // Only show labels that are within the container bounds
                if (pixelX >= 0 && pixelX <= mapContainer.clientWidth) {
                    label.style.left = pixelX + 'px';
                    xCoordinatesContainer.appendChild(label);
                }
            }
            
            // Generate Y-axis labels
            for (let y = Math.ceil(bottommostCoord / yStep) * yStep; y <= topmostCoord; y += yStep) {
                const label = document.createElement('div');
                label.className = 'coordinate-label y-label';
                label.textContent = y;
                // Calculate pixel position for this coordinate relative to scroll position
                // Match the settlement positioning formula: (coordinateOffset - y) * cellSize + 20, scaled by zoom
                // But account for grid margin-top (25px)
                const pixelY = (coordinateOffset - y) * scaledCellSize + (scaledCellSize / 2) + gridOffset - scrollTop;
                // Only show labels that are within the container bounds
                if (pixelY >= 0 && pixelY <= mapContainer.clientHeight) {
                    label.style.top = pixelY + 'px';
                    yCoordinatesContainer.appendChild(label);
                }
            }
        }
        
        // Position settlements based on coordinates
        function positionSettlements() {
            const settlements = document.querySelectorAll('.settlement-icon');
            const occupiedCells = new Set(); // Track occupied grid cells
            
            settlements.forEach(settlement => {
                const xAttr = settlement.dataset.x;
                const yAttr = settlement.dataset.y;
                
                // Parse coordinates with better error handling
                const x = xAttr ? parseInt(xAttr) : 0;
                const y = yAttr ? parseInt(yAttr) : 0;
                
                // Validate coordinates
                if (isNaN(x) || isNaN(y)) {
                    console.warn(`Invalid coordinates for settlement: x=${xAttr}, y=${yAttr}. Using (0,0) as fallback.`);
                    settlement.dataset.x = '0';
                    settlement.dataset.y = '0';
                    // Center position for larger map (4000px is center of 8000px map)
                    settlement.style.left = '4020px'; // Center position + 20px offset
                    settlement.style.top = '4020px';
                    return;
                }
                
                // Create a cell key to track occupation
                const cellKey = `${x},${y}`;
                
                // Check if cell is already occupied
                if (occupiedCells.has(cellKey)) {
                    console.warn(`Multiple settlements trying to occupy cell (${x}, ${y}). Only one will be displayed.`);
                    settlement.style.display = 'none'; // Hide additional settlements in same cell
                    return;
                }
                
                // Mark cell as occupied
                occupiedCells.add(cellKey);
                
                // Convert coordinates to pixel positions (center settlements within grid squares)
                // Updated for larger map with coordinate offset of 100
                const coordinateOffset = 100;
                const pixelX = (x + coordinateOffset) * 40 + 20; // 40px per grid unit, offset to center in grid square
                const pixelY = (coordinateOffset - y) * 40 + 20; // Invert Y axis, offset to center in grid square
                
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
            
            // Update coordinate labels after centering
            setTimeout(updateCoordinateLabelsPosition, 100);
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
            // Update coordinate labels after zoom change
            setTimeout(updateCoordinateLabelsPosition, 100);
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
            // Update coordinate labels during panning
            updateCoordinateLabelsPosition();
        });
        
        // Update coordinate labels when scrolling
        mapContainer.addEventListener('scroll', updateCoordinateLabelsPosition);
        
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
            
            // Check if we're in target selection mode
            const isSelectTargetMode = <?= json_encode($isSelectTargetMode) ?>;
            const currentSettlementId = <?= json_encode($currentSettlementId) ?>;
            const returnTo = <?= json_encode($returnTo) ?>;
            
            const title = document.getElementById('settlementInfoTitle');
            const content = document.getElementById('settlementInfoContent');
            const panel = document.getElementById('settlementInfoPanel');
            const overlay = document.getElementById('settlementInfoOverlay');
            
            if (isSelectTargetMode && !isOwn) {
                // In target selection mode, show selection option for enemy settlements
                title.textContent = `🎯 Select Target: ${settlementName}`;
                
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
                                <span class="info-value foreign-settlement">Attack Target</span>
                            </div>
                        </div>
                    </div>
                    <div class="actions">
                        <button onclick="selectThisTarget(${settlementId})" class="action-button select-target">
                            🎯 Select This Target
                        </button>
                        <button onclick="closeSettlementInfo()" class="action-button cancel">
                            ❌ Cancel
                        </button>
                    </div>
                `;
            } else if (isSelectTargetMode && isOwn) {
                // In target selection mode, show message for own settlements
                title.textContent = `${settlementName} - Your Settlement`;
                
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
                                <span class="info-value own-settlement">Your Settlement</span>
                            </div>
                        </div>
                    </div>
                    <div class="target-selection-message">
                        <p>⚠️ You cannot attack your own settlements. Please select an enemy settlement (red dot).</p>
                        <button onclick="closeSettlementInfo()" class="action-button cancel">
                            ❌ Close
                        </button>
                    </div>
                `;
            } else {
                // Normal mode - existing functionality
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
                            <a href="market.php?settlementId=${currentSettlementId}" class="action-button">
                                ⚖️ Visit Market
                            </a>
                            <a href="battle.php?settlementId=${currentSettlementId}&target=${settlementId}" class="action-button battle">
                                ⚔️ Attack Settlement
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
            }
            
            // Show the panel
            panel.classList.add('show');
            overlay.classList.add('show');
        }
        
        function selectThisTarget(targetSettlementId) {
            // Navigate back to battle page with selected target
            const currentSettlementId = <?= json_encode($currentSettlementId) ?>;
            const returnTo = <?= json_encode($returnTo) ?>;
            
            if (returnTo === 'battle') {
                window.location.href = `battle.php?settlementId=${currentSettlementId}&target=${targetSettlementId}`;
            } else {
                // Fallback - just close the panel
                closeSettlementInfo();
            }
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