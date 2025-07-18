// Admin Buildings JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load building types and configurations on page load
    loadBuildingTypes();
    loadBuildingConfigs();
    
    // Set up event listeners
    setupEventListeners();
});

async function loadBuildingTypes() {
    try {
        const response = await fetch('../php/admin-backend.php?action=buildingTypes');
        const data = await response.json();
        
        if (data.buildingTypes) {
            populateBuildingTypeDropdowns(data.buildingTypes);
        } else {
            console.warn('No building types returned, using defaults');
            // Use dynamic defaults from centralized configuration
            const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
                window.getDefaultBuildingTypes() :
                [
                    {buildingType: 'Rathaus'},
                    {buildingType: 'Holzfäller'},
                    {buildingType: 'Steinbruch'},
                    {buildingType: 'Erzbergwerk'},
                    {buildingType: 'Lager'},
                    {buildingType: 'Farm'}
                ];
            populateBuildingTypeDropdowns(defaultBuildingTypes);
        }
    } catch (error) {
        console.error('Error loading building types:', error);
        // Use dynamic defaults from centralized configuration
        const defaultBuildingTypes = window.getDefaultBuildingTypes ? 
            window.getDefaultBuildingTypes() :
            [
                {buildingType: 'Rathaus'},
                {buildingType: 'Holzfäller'},
                {buildingType: 'Steinbruch'},
                {buildingType: 'Erzbergwerk'},
                {buildingType: 'Lager'},
                {buildingType: 'Farm'}
            ];
        populateBuildingTypeDropdowns(defaultBuildingTypes);
    }
}

function populateBuildingTypeDropdowns(buildingTypes) {
    // Get all dropdowns that need building types
    const dropdowns = [
        'buildingTypeFilter',
        'genBuildingType', 
        'createBuildingType'
    ];
    
    dropdowns.forEach(dropdownId => {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            // Clear existing options except the first one (usually "All" or "Choose...")
            const firstOption = dropdown.firstElementChild;
            dropdown.innerHTML = '';
            if (firstOption) {
                dropdown.appendChild(firstOption);
            }
            
            // Add building type options with translated names
            buildingTypes.forEach(type => {
                const option = document.createElement('option');
                option.value = type.buildingType;
                // Show translated name in dropdown but keep original value for backend
                const translatedName = window.translateBuildingName ? 
                    window.translateBuildingName(type.buildingType) : 
                    type.buildingType;
                option.textContent = translatedName;
                dropdown.appendChild(option);
            });
        }
    });
}

function setupEventListeners() {
    // Refresh button
    document.getElementById('refreshBuildings').addEventListener('click', loadBuildingConfigs);
    
    // Create new configuration button
    document.getElementById('createBuildingConfig').addEventListener('click', openCreateModal);
    
    // Export configurations button
    document.getElementById('exportConfigs').addEventListener('click', exportConfigs);
    
    // Generate levels button
    document.getElementById('generateLevels').addEventListener('click', generateLevels);
    
    // Filter controls
    document.getElementById('buildingTypeFilter').addEventListener('change', filterTable);
    document.getElementById('levelFilter').addEventListener('input', filterTable);
    
    // Modal controls
    setupModalControls();
    
    // Form submissions
    document.getElementById('editBuildingConfigForm').addEventListener('submit', handleEditSubmit);
    document.getElementById('createBuildingConfigForm').addEventListener('submit', handleCreateSubmit);
}

function setupModalControls() {
    // Close buttons
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });
    
    // Click outside modal to close
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

async function loadBuildingConfigs() {
    try {
        const response = await fetch('../php/admin-backend.php?action=buildingConfigs');
        const data = await response.json();
        
        if (data.buildingConfigs) {
            displayBuildingConfigs(data.buildingConfigs);
        } else {
            showError('Failed to load building configurations');
        }
    } catch (error) {
        console.error('Error loading building configs:', error);
        showError('Error loading building configurations');
    }
}

function displayBuildingConfigs(configs) {
    const tbody = document.getElementById('buildingConfigsTableBody');
    
    if (configs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9">No configurations found</td></tr>';
        return;
    }
    
    tbody.innerHTML = configs.map(config => {
        // Translate building name from German to English for display
        const translatedBuildingName = window.translateBuildingName ? 
            window.translateBuildingName(config.buildingType) : 
            config.buildingType;
            
        return `
            <tr data-building-type="${config.buildingType}" data-level="${config.level}">
                <td>${translatedBuildingName}</td>
                <td>${config.level}</td>
                <td>${parseFloat(config.costWood).toFixed(2)}</td>
                <td>${parseFloat(config.costStone).toFixed(2)}</td>
                <td>${parseFloat(config.costOre).toFixed(2)}</td>
                <td>${parseFloat(config.settlers).toFixed(2)}</td>
                <td>${parseFloat(config.productionRate).toFixed(2)}</td>
                <td>${config.buildTime}</td>
                <td>
                    <button class="action-btn edit" onclick="editBuildingConfig('${config.buildingType}', ${config.level})">
                        Edit
                    </button>
                    <button class="action-btn delete" onclick="deleteBuildingConfig('${config.buildingType}', ${config.level})">
                        Delete
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    // Apply current filters
    filterTable();
}

function filterTable() {
    const buildingTypeFilter = document.getElementById('buildingTypeFilter').value;
    const levelFilter = document.getElementById('levelFilter').value;
    const rows = document.querySelectorAll('#buildingConfigsTableBody tr[data-building-type]');
    
    rows.forEach(row => {
        const buildingType = row.getAttribute('data-building-type');
        const level = parseInt(row.getAttribute('data-level'));
        
        let show = true;
        
        if (buildingTypeFilter && buildingType !== buildingTypeFilter) {
            show = false;
        }
        
        if (levelFilter && level !== parseInt(levelFilter)) {
            show = false;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

function openCreateModal() {
    // Reset form
    document.getElementById('createBuildingConfigForm').reset();
    
    // Show modal
    document.getElementById('createBuildingConfigModal').style.display = 'block';
}

async function editBuildingConfig(buildingType, level) {
    try {
        const response = await fetch(`../php/admin-backend.php?action=buildingConfig&buildingType=${encodeURIComponent(buildingType)}&level=${level}`);
        const data = await response.json();
        
        if (data.buildingConfig) {
            const config = data.buildingConfig;
            
            // Fill form with current values
            document.getElementById('editBuildingType').value = config.buildingType;
            document.getElementById('editLevel').value = config.level;
            document.getElementById('editBuildingTypeDisplay').textContent = config.buildingType;
            document.getElementById('editLevelDisplay').textContent = config.level;
            document.getElementById('editCostWood').value = config.costWood;
            document.getElementById('editCostStone').value = config.costStone;
            document.getElementById('editCostOre').value = config.costOre;
            document.getElementById('editSettlers').value = config.settlers;
            document.getElementById('editProductionRate').value = config.productionRate;
            document.getElementById('editBuildTime').value = config.buildTime;
            
            // Show modal
            document.getElementById('editBuildingConfigModal').style.display = 'block';
        } else {
            showError('Failed to load building configuration details');
        }
    } catch (error) {
        console.error('Error loading building config:', error);
        showError('Error loading building configuration');
    }
}

async function deleteBuildingConfig(buildingType, level) {
    if (!confirm(`Are you sure you want to delete the configuration for ${buildingType} Level ${level}?`)) {
        return;
    }
    
    try {
        const response = await fetch('../php/admin-backend.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'deleteBuildingConfig',
                buildingType: buildingType,
                level: level
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.message);
            loadBuildingConfigs();
        } else {
            showError(data.error || 'Failed to delete building configuration');
        }
    } catch (error) {
        console.error('Error deleting building config:', error);
        showError('Error deleting building configuration');
    }
}

async function handleEditSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        action: 'updateBuildingConfig',
        buildingType: formData.get('buildingType'),
        level: parseInt(formData.get('level')),
        costWood: parseFloat(formData.get('costWood')),
        costStone: parseFloat(formData.get('costStone')),
        costOre: parseFloat(formData.get('costOre')),
        settlers: parseFloat(formData.get('settlers')),
        productionRate: parseFloat(formData.get('productionRate')),
        buildTime: parseInt(formData.get('buildTime'))
    };
    
    try {
        const response = await fetch('../php/admin-backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            document.getElementById('editBuildingConfigModal').style.display = 'none';
            loadBuildingConfigs();
        } else {
            showError(result.error || 'Failed to update building configuration');
        }
    } catch (error) {
        console.error('Error updating building config:', error);
        showError('Error updating building configuration');
    }
}

async function handleCreateSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        action: 'createBuildingConfig',
        buildingType: formData.get('buildingType'),
        level: parseInt(formData.get('level')),
        costWood: parseFloat(formData.get('costWood')),
        costStone: parseFloat(formData.get('costStone')),
        costOre: parseFloat(formData.get('costOre')),
        settlers: parseFloat(formData.get('settlers')),
        productionRate: parseFloat(formData.get('productionRate')),
        buildTime: parseInt(formData.get('buildTime'))
    };
    
    try {
        const response = await fetch('../php/admin-backend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            document.getElementById('createBuildingConfigModal').style.display = 'none';
            loadBuildingConfigs();
        } else {
            showError(result.error || 'Failed to create building configuration');
        }
    } catch (error) {
        console.error('Error creating building config:', error);
        showError('Error creating building configuration');
    }
}

async function generateLevels() {
    const buildingType = document.getElementById('genBuildingType').value;
    const startLevel = parseInt(document.getElementById('genStartLevel').value);
    const endLevel = parseInt(document.getElementById('genEndLevel').value);
    const baseCostWood = parseFloat(document.getElementById('genBaseCostWood').value);
    const baseCostStone = parseFloat(document.getElementById('genBaseCostStone').value);
    const baseCostOre = parseFloat(document.getElementById('genBaseCostOre').value);
    const baseSettlers = parseFloat(document.getElementById('genBaseSettlers').value);
    const baseProduction = parseFloat(document.getElementById('genBaseProduction').value);
    const baseBuildTime = parseInt(document.getElementById('genBaseBuildTime').value);
    const multiplier = parseFloat(document.getElementById('genMultiplier').value);
    const timeIncrease = parseInt(document.getElementById('genTimeIncrease').value);
    
    if (!buildingType || startLevel > endLevel) {
        showError('Please check your inputs');
        return;
    }
    
    const confirmMsg = `Generate configurations for ${buildingType} Level ${startLevel}-${endLevel}. Existing configurations will be overwritten. Continue?`;
    if (!confirm(confirmMsg)) {
        return;
    }
    
    let successCount = 0;
    let errorCount = 0;
    
    for (let level = startLevel; level <= endLevel; level++) {
        const levelMultiplier = Math.pow(multiplier, level - 1);
        
        const data = {
            action: 'createBuildingConfig',
            buildingType: buildingType,
            level: level,
            costWood: baseCostWood * levelMultiplier,
            costStone: baseCostStone * levelMultiplier,
            costOre: baseCostOre * levelMultiplier,
            settlers: baseSettlers * levelMultiplier,
            productionRate: baseProduction * levelMultiplier,
            buildTime: baseBuildTime + (timeIncrease * (level - 1))
        };
        
        try {
            // Try to create, if it fails try to update
            let response = await fetch('../php/admin-backend.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            let result = await response.json();
            
            if (!result.success) {
                // Try updating instead
                data.action = 'updateBuildingConfig';
                response = await fetch('../php/admin-backend.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                result = await response.json();
            }
            
            if (result.success) {
                successCount++;
            } else {
                errorCount++;
                console.error(`Failed to create/update level ${level}:`, result.error);
            }
        } catch (error) {
            errorCount++;
            console.error(`Error processing level ${level}:`, error);
        }
    }
    
    if (successCount > 0) {
        showSuccess(`${successCount} configurations successfully generated/updated`);
        loadBuildingConfigs();
    }
    
    if (errorCount > 0) {
        showError(`${errorCount} configurations could not be processed`);
    }
}

function exportConfigs() {
    // Simple export as JSON
    fetch('../php/admin-backend.php?action=buildingConfigs')
        .then(response => response.json())
        .then(data => {
            if (data.buildingConfigs) {
                const jsonData = JSON.stringify(data.buildingConfigs, null, 2);
                const blob = new Blob([jsonData], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                
                const a = document.createElement('a');
                a.href = url;
                a.download = 'building-configs-export.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showSuccess('Configurations exported');
            }
        })
        .catch(error => {
            console.error('Export error:', error);
            showError('Export failed');
        });
}

function showSuccess(message) {
    // Remove existing messages
    removeMessages();
    
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    
    const container = document.querySelector('.admin-container');
    container.insertBefore(successDiv, container.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.parentNode.removeChild(successDiv);
        }
    }, 5000);
}

function showError(message) {
    // Remove existing messages
    removeMessages();
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    const container = document.querySelector('.admin-container');
    container.insertBefore(errorDiv, container.firstChild);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.parentNode.removeChild(errorDiv);
        }
    }, 10000);
}

function removeMessages() {
    document.querySelectorAll('.success-message, .error-message').forEach(msg => {
        if (msg.parentNode) {
            msg.parentNode.removeChild(msg);
        }
    });
}