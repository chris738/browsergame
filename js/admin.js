// Admin Panel JavaScript

// Utility functions
function formatNumberWithDots(number) {
    const roundedNumber = Math.floor(number);
    return roundedNumber.toLocaleString('de-DE');
}

function showMessage(message, type = 'success') {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.textContent = message;
    
    // Insert at top of admin container
    const container = document.querySelector('.admin-container');
    container.insertBefore(messageDiv, container.firstChild);
    
    // Remove after 5 seconds
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// API functions
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'API request failed');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        showMessage(error.message, 'error');
        throw error;
    }
}

// Data fetching functions
async function fetchPlayers() {
    try {
        const data = await apiRequest('../php/admin-backend.php?action=players');
        updatePlayersTable(data.players);
    } catch (error) {
        console.error('Error fetching players:', error);
    }
}

async function fetchSettlements() {
    try {
        const data = await apiRequest('../php/admin-backend.php?action=settlements');
        updateSettlementsTable(data.settlements);
    } catch (error) {
        console.error('Error fetching settlements:', error);
    }
}

async function fetchQueues() {
    try {
        const data = await apiRequest('../php/admin-backend.php?action=queues');
        updateQueuesTable(data.queues);
    } catch (error) {
        console.error('Error fetching queues:', error);
    }
}

// Table update functions
function updatePlayersTable(players) {
    const tbody = document.getElementById('playersTableBody');
    
    if (players.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5">No players found</td></tr>';
        return;
    }
    
    tbody.innerHTML = players.map(player => `
        <tr>
            <td>${player.playerId}</td>
            <td>${escapeHtml(player.name)}</td>
            <td>${formatNumberWithDots(player.points)}</td>
            <td>${formatNumberWithDots(player.gold)}</td>
            <td>
                <button class="action-btn edit" onclick="editPlayer(${player.playerId}, '${escapeHtml(player.name)}', ${player.points}, ${player.gold})">
                    Edit
                </button>
                <button class="action-btn delete" onclick="deletePlayer(${player.playerId}, '${escapeHtml(player.name)}')">
                    Delete
                </button>
            </td>
        </tr>
    `).join('');
}

function updateSettlementsTable(settlements) {
    const tbody = document.getElementById('settlementsTableBody');
    
    if (settlements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8">No settlements found</td></tr>';
        return;
    }
    
    tbody.innerHTML = settlements.map(settlement => `
        <tr>
            <td>${settlement.settlementId}</td>
            <td>${escapeHtml(settlement.name)}</td>
            <td>${escapeHtml(settlement.playerName || 'N/A')}</td>
            <td>${formatNumberWithDots(settlement.wood)}</td>
            <td>${formatNumberWithDots(settlement.stone)}</td>
            <td>${formatNumberWithDots(settlement.ore)}</td>
            <td>(${settlement.xCoordinate || 'N/A'}, ${settlement.yCoordinate || 'N/A'})</td>
            <td>
                <button class="action-btn edit" onclick="editResources(${settlement.settlementId}, ${settlement.wood}, ${settlement.stone}, ${settlement.ore})">
                    Edit Resources
                </button>
                <a href="../index.php?settlementId=${settlement.settlementId}" target="_blank" class="action-btn">
                    View Game
                </a>
            </td>
        </tr>
    `).join('');
}

function updateQueuesTable(queues) {
    const tbody = document.getElementById('queuesTableBody');
    
    if (queues.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7">No active building queues</td></tr>';
        return;
    }
    
    tbody.innerHTML = queues.map(queue => `
        <tr>
            <td>${queue.queueId}</td>
            <td>${escapeHtml(queue.settlementName || 'N/A')} (${queue.settlementId})</td>
            <td>${escapeHtml(queue.buildingType)}</td>
            <td>${queue.level}</td>
            <td>
                <div class="admin-progress-container">
                    <div class="admin-progress-bar" style="width: ${Math.max(0, Math.min(100, queue.completionPercentage || 0))}%;"></div>
                </div>
                ${Math.round(queue.completionPercentage || 0)}%
            </td>
            <td>${queue.endTime}</td>
            <td>
                <button class="action-btn delete" onclick="deleteQueue(${queue.queueId})">
                    Cancel
                </button>
            </td>
        </tr>
    `).join('');
}

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Player management functions
function createPlayer() {
    openModal('createPlayerModal');
}

async function submitCreatePlayer(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const playerData = {
        action: 'createPlayer',
        name: formData.get('playerName'),
        gold: parseInt(formData.get('playerGold')) || 500
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'POST',
            body: JSON.stringify(playerData)
        });
        
        showMessage('Player created successfully');
        closeModal('createPlayerModal');
        form.reset();
        fetchPlayers();
    } catch (error) {
        console.error('Error creating player:', error);
    }
}

function editPlayer(playerId, name, points, gold) {
    const newPoints = prompt(`Edit points for ${name}:`, points);
    const newGold = prompt(`Edit gold for ${name}:`, gold);
    
    if (newPoints !== null && newGold !== null) {
        updatePlayerStats(playerId, parseInt(newPoints) || 0, parseInt(newGold) || 0);
    }
}

async function updatePlayerStats(playerId, points, gold) {
    const playerData = {
        action: 'updatePlayerStats',
        playerId: playerId,
        points: points,
        gold: gold
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'POST',
            body: JSON.stringify(playerData)
        });
        
        showMessage('Player stats updated successfully');
        fetchPlayers();
    } catch (error) {
        console.error('Error updating player stats:', error);
    }
}

async function deletePlayer(playerId, name) {
    if (!confirm(`Are you sure you want to delete player "${name}"? This will also delete all their settlements and cannot be undone.`)) {
        return;
    }
    
    const playerData = {
        action: 'deletePlayer',
        playerId: playerId
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'DELETE',
            body: JSON.stringify(playerData)
        });
        
        showMessage('Player deleted successfully');
        fetchPlayers();
        fetchSettlements(); // Refresh settlements as well
    } catch (error) {
        console.error('Error deleting player:', error);
    }
}

// Settlement management functions
function editResources(settlementId, wood, stone, ore) {
    document.getElementById('editSettlementId').value = settlementId;
    document.getElementById('editWood').value = wood;
    document.getElementById('editStone').value = stone;
    document.getElementById('editOre').value = ore;
    openModal('editResourcesModal');
}

async function submitEditResources(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const resourceData = {
        action: 'updateSettlementResources',
        settlementId: parseInt(formData.get('settlementId')),
        wood: parseFloat(formData.get('wood')) || 0,
        stone: parseFloat(formData.get('stone')) || 0,
        ore: parseFloat(formData.get('ore')) || 0
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'POST',
            body: JSON.stringify(resourceData)
        });
        
        showMessage('Settlement resources updated successfully');
        closeModal('editResourcesModal');
        fetchSettlements();
    } catch (error) {
        console.error('Error updating settlement resources:', error);
    }
}

// Queue management functions
async function deleteQueue(queueId) {
    if (!confirm('Are you sure you want to cancel this building queue entry?')) {
        return;
    }
    
    const queueData = {
        action: 'deleteQueue',
        queueId: queueId
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'DELETE',
            body: JSON.stringify(queueData)
        });
        
        showMessage('Queue entry deleted successfully');
        fetchQueues();
    } catch (error) {
        console.error('Error deleting queue entry:', error);
    }
}

async function clearAllQueues() {
    if (!confirm('Are you sure you want to clear ALL building queues? This cannot be undone.')) {
        return;
    }
    
    const queueData = {
        action: 'clearAllQueues'
    };
    
    try {
        await apiRequest('../php/admin-backend.php', {
            method: 'POST',
            body: JSON.stringify(queueData)
        });
        
        showMessage('All building queues cleared successfully');
        fetchQueues();
    } catch (error) {
        console.error('Error clearing all queues:', error);
    }
}

// Utility functions
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initial data load
    fetchPlayers();
    fetchSettlements();
    fetchQueues();
    
    // Set up refresh intervals
    setInterval(fetchQueues, 5000); // Refresh queues every 5 seconds
    
    // Button event listeners
    document.getElementById('refreshPlayers').addEventListener('click', fetchPlayers);
    document.getElementById('createPlayer').addEventListener('click', createPlayer);
    document.getElementById('refreshSettlements').addEventListener('click', fetchSettlements);
    document.getElementById('refreshQueues').addEventListener('click', fetchQueues);
    document.getElementById('clearAllQueues').addEventListener('click', clearAllQueues);
    
    // Form event listeners
    document.getElementById('createPlayerForm').addEventListener('submit', submitCreatePlayer);
    document.getElementById('editResourcesForm').addEventListener('submit', submitEditResources);
    
    // Modal event listeners
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', (e) => {
            const modal = e.target.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });
});